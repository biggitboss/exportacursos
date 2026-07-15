<?php
namespace local_courseexport;

defined('MOODLE_INTERNAL') || die();

class exporter {
    private $courses;
    private $category;
    private $recursive;

    public function __construct(array $courses, $category = null, $recursive = false) {
        $this->courses = $courses;
        $this->category = $category;
        $this->recursive = $recursive;
    }

    public function count(): array {
        $total = ['courses' => 0, 'sections' => 0, 'files' => 0];
        foreach ($this->courses as $coursedata) {
            $course = get_course($coursedata->id);
            $coursereader = new coursereader($course);
            $sections = $coursereader->get_sections_with_modules();
            $total['courses']++;
            foreach ($sections as $section) {
                $hassection = false;
                foreach ($section['modules'] as $module) {
                    if (!empty($module['files'])) {
                        $hassection = true;
                        $total['files'] += count($module['files']);
                    }
                    if ($module['url']) {
                        $hassection = true;
                    }
                    if (!empty($module['submissions'])) {
                        $hassection = true;
                        foreach ($module['submissions'] as $submission) {
                            $total['files'] += count($submission['files']);
                        }
                    }
                }
                if ($hassection) {
                    $total['sections']++;
                }
            }
        }
        return $total;
    }

    public function export() {
        if (empty($this->courses)) {
            redirect(
                new \moodle_url('/local/courseexport/index.php'),
                get_string('nofilesfound', 'local_courseexport'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }

        \core\session\manager::write_close();
        @set_time_limit(0);

        if (ob_get_level()) {
            ob_end_clean();
        }

        @ini_set('display_errors', '0');
        global $CFG;
        $CFG->debugdisplay = 0;
        $CFG->debug = 0;

        try {
            if ($this->category) {
                $rootname = self::sanitise_path($this->category->name);
                $zipname = clean_filename($this->category->name) . '-export.zip';
            } else {
                $rootname = self::sanitise_path($this->courses[0]->fullname);
                $zipname = clean_filename($this->courses[0]->shortname) . '-export.zip';
            }

            $zip = new \ZipStream\ZipStream(
                outputName: $zipname,
                sendHttpHeaders: true,
            );

            foreach ($this->courses as $coursedata) {
                $course = get_course($coursedata->id);
                $coursereader = new coursereader($course);
                $sections = $coursereader->get_sections_with_modules();

                if (empty($sections)) {
                    continue;
                }

                $coursename = self::sanitise_path($course->fullname);
                if ($this->category) {
                    $courseroot = $rootname . '/' . $coursename;
                } else {
                    $courseroot = $coursename;
                }

                foreach ($sections as $section) {
                    $sectionpath = $courseroot . '/' . $section['path'];

                    foreach ($section['modules'] as $module) {
                        $modpath = $sectionpath . '/' . self::sanitise_path($module['name']);

                        foreach ($module['files'] as $fileentry) {
                            $filepath = $sectionpath . '/' . $fileentry['zip_path'];
                            $handle = $fileentry['file']->get_content_file_handle();
                            $zip->addFileFromStream($filepath, $handle);
                            fclose($handle);
                        }

                        if (!empty($module['submissions'])) {
                            foreach ($module['submissions'] as $submission) {
                                foreach ($submission['files'] as $fileentry) {
                                    $filepath = $modpath . '/' . $fileentry['zip_path'];
                                    $handle = $fileentry['file']->get_content_file_handle();
                                    $zip->addFileFromStream($filepath, $handle);
                                    fclose($handle);
                                }
                            }
                        }
                    }
                }

            }

            $zip->finish();
        } catch (\Throwable $e) {
            debugging('Export failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw new \moodle_exception('exportfailed', 'local_courseexport', '', $e->getMessage());
        }
    }

    private static function sanitise_path($name) {
        $name = preg_replace('/[\/\\\\:*?"<>|]/', '_', $name);
        $name = preg_replace('/[\\x00-\\x1f]/', '', $name);
        $name = trim($name, ' .');
        if ($name === '' || $name === '.') {
            $name = '_';
        }
        return $name;
    }
}
