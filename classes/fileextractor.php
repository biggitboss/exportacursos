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
