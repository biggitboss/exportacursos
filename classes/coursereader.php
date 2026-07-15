<?php
namespace local_courseexport;

defined('MOODLE_INTERNAL') || die();

class coursereader {
    private $course;

    private const SUPPORTED_MODULES = ['resource', 'url', 'folder', 'assign'];

    public function __construct($course) {
        $this->course = $course;
    }

    public function get_sections_with_modules() {
        $modinfo = get_fast_modinfo($this->course);
        $sectioninfos = $modinfo->get_section_info_all();
        $allcms = $modinfo->get_cms();

        $result = [];
        $seqnumber = 0;

        $cmsbysection = [];
        foreach ($allcms as $cm) {
            if (in_array($cm->modname, self::SUPPORTED_MODULES)) {
                $cmsbysection[$cm->sectionnum][] = $cm;
            }
        }

        foreach ($sectioninfos as $sectionnum => $sectioninfo) {
            $cms = $cmsbysection[$sectionnum] ?? [];

            if (empty($cms)) {
                continue;
            }

            $seqnumber++;
            $modules = [];

            foreach ($cms as $cm) {
                $moduledata = $this->extract_module($cm);
                if ($moduledata) {
                    $modules[] = $moduledata;
                }
            }

            if (empty($modules)) {
                continue;
            }

            $sectionname = $sectioninfo->name;
            if (empty($sectionname)) {
                $sectionname = get_string('section') . ' ' . $sectionnum;
            }

            $sectionpath = sprintf('%02d', $seqnumber) . '. ' . self::sanitise_path($sectionname);

            $result[] = [
                'id' => $sectioninfo->id,
                'name' => $sectionname,
                'number' => $seqnumber,
                'path' => $sectionpath,
                'modules' => $modules,
            ];
        }

        return $result;
    }

    private function extract_module($cm) {
        $data = [
            'cmid' => $cm->id,
            'type' => $cm->modname,
            'name' => $cm->name,
            'files' => [],
            'url' => null,
            'submissions' => [],
        ];

        switch ($cm->modname) {
            case 'resource':
                $data['files'] = fileextractor::get_resource_files($cm);
                break;

            case 'folder':
                $data['files'] = fileextractor::get_folder_files($cm);
                break;

            case 'url':
                $data['url'] = fileextractor::get_url_link($cm);
                break;

            case 'assign':
                $data['submissions'] = fileextractor::get_assign_submissions($cm);
                break;
        }

        return $data;
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
