<?php
namespace local_courseexport;

defined('MOODLE_INTERNAL') || die();

class fileextractor {

    private static $document_extensions = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'odt', 'ods', 'odp', 'csv', 'rtf',
    ];

    public static function get_resource_files($cm) {
        $fs = get_file_storage();
        $context = \context_module::instance($cm->id);
        $files = $fs->get_area_files(
            $context->id, 'mod_resource', 'content', 0,
            'sortorder DESC, id ASC', false
        );
        $result = [];
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
            if (!in_array($ext, self::$document_extensions)) {
                continue;
            }
            $result[] = [
                'file' => $file,
                'zip_path' => $file->get_filename(),
            ];
        }
        return $result;
    }

    public static function get_folder_files($cm) {
        $fs = get_file_storage();
        $context = \context_module::instance($cm->id);
        $files = $fs->get_area_files(
            $context->id, 'mod_folder', 'content', 0,
            'sortorder DESC, id ASC', false
        );
        $result = [];
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
            if (!in_array($ext, self::$document_extensions)) {
                continue;
            }
            $zip_path = ltrim($file->get_filepath() . $file->get_filename(), '/');
            $result[] = [
                'file' => $file,
                'zip_path' => $zip_path,
            ];
        }
        return $result;
    }

    public static function get_assign_submissions($cm) {
        global $DB;

        $submissions = $DB->get_records('assign_submission', [
            'assignment' => $cm->instance,
            'status' => 'submitted',
        ], 'timemodified ASC');

        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();
        $result = [];

        foreach ($submissions as $submission) {
            $user = $DB->get_record('user', ['id' => $submission->userid], 'id, firstname, lastname');
            if (!$user) {
                continue;
            }
            $studentname = fullname($user);
            $studentprefix = self::sanitise_student_name($studentname);

            $onlinetext = '';
            $onlinetextformat = FORMAT_HTML;
            $onlinetextrec = $DB->get_record('assignsubmission_onlinetext', [
                'submission' => $submission->id,
            ]);
            if ($onlinetextrec && !empty(trim(strip_tags($onlinetextrec->onlinetext)))) {
                $onlinetext = $onlinetextrec->onlinetext;
                $onlinetextformat = $onlinetextrec->onlineformat;
            }

            $files = [];
            $storedfiles = $fs->get_area_files(
                $context->id, 'assignsubmission_file', 'submission_files', $submission->id,
                'timemodified ASC', false
            );
            foreach ($storedfiles as $file) {
                $files[] = [
                    'file' => $file,
                    'zip_path' => $studentprefix . ' - ' . $file->get_filename(),
                ];
            }

            $result[] = [
                'studentname' => $studentname,
                'files' => $files,
                'onlinetext' => $onlinetext,
                'onlinetextformat' => $onlinetextformat,
            ];
        }

        return $result;
    }

    private static function sanitise_student_name($name) {
        $name = preg_replace('/[\/\\\\:*?"<>|]/', '_', $name);
        $name = preg_replace('/[\\x00-\\x1f]/', '', $name);
        return trim($name);
    }

    public static function get_url_link($cm) {
        global $DB, $CFG;
        $urlrecord = $DB->get_record('url', ['id' => $cm->instance], '*', MUST_EXIST);
        $exturl = trim($urlrecord->externalurl);
        $locallib = $CFG->dirroot . '/mod/url/locallib.php';
        if (file_exists($locallib)) {
            require_once($locallib);
            if (function_exists('url_get_full_url')) {
                $exturl = url_get_full_url($urlrecord, $cm, get_course($cm->course));
            }
        }
        return $exturl;
    }
}
