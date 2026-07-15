<?php
namespace local_courseexport;

defined('MOODLE_INTERNAL') || die();

class fileextractor {

    public static function get_resource_files($cm) {
        $fs = get_file_storage();
        $context = \context_module::instance($cm->id);
        $files = $fs->get_area_files(
            $context->id, 'mod_resource', 'content', 0,
            'sortorder DESC, id ASC', false
        );
        $result = [];
        foreach ($files as $file) {
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
            $zip_path = ltrim($file->get_filepath() . $file->get_filename(), '/');
            $result[] = [
                'file' => $file,
                'zip_path' => $zip_path,
            ];
        }
        return $result;
    }

    public static function get_page_content($cm) {
        global $DB;
        $page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $content = file_rewrite_pluginfile_urls(
            $page->content,
            'pluginfile.php',
            $context->id,
            'mod_page',
            'content',
            $page->revision
        );
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id, 'mod_page', 'content', 0,
            'sortorder DESC, id ASC', false
        );
        $fileentries = [];
        foreach ($files as $file) {
            $fileentries[] = [
                'file' => $file,
                'zip_path' => $file->get_filename(),
            ];
        }
        return [
            'content' => $content,
            'contentformat' => $page->contentformat,
            'files' => $fileentries,
        ];
    }

    public static function get_label_content($cm) {
        global $DB;
        $label = $DB->get_record('label', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $content = file_rewrite_pluginfile_urls(
            $label->intro,
            'pluginfile.php',
            $context->id,
            'mod_label',
            'intro',
            0
        );
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id, 'mod_label', 'intro', 0,
            'sortorder DESC, id ASC', false
        );
        $fileentries = [];
        foreach ($files as $file) {
            $fileentries[] = [
                'file' => $file,
                'zip_path' => $file->get_filename(),
            ];
        }
        return [
            'content' => $content,
            'contentformat' => $label->introformat,
            'files' => $fileentries,
        ];
    }

    public static function get_book_chapters($cm) {
        global $DB;
        $book = $DB->get_record('book', ['id' => $cm->instance], '*', MUST_EXIST);
        $chapters = $DB->get_records('book_chapters', ['bookid' => $book->id], 'pagenum ASC');
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();

        $chaptersdata = [];
        $files = [];

        foreach ($chapters as $chapter) {
            $content = file_rewrite_pluginfile_urls(
                $chapter->content,
                'pluginfile.php',
                $context->id,
                'mod_book',
                'chapter',
                $chapter->id
            );
            $chaptersdata[] = [
                'title' => $chapter->title,
                'content' => $content,
                'contentformat' => $chapter->contentformat,
                'pagenum' => $chapter->pagenum,
                'subchapter' => $chapter->subchapter,
                'hidden' => $chapter->hidden,
            ];
            $chapterfiles = $fs->get_area_files(
                $context->id, 'mod_book', 'chapter', $chapter->id,
                'sortorder DESC, id ASC', false
            );
            foreach ($chapterfiles as $file) {
                $files[] = [
                    'file' => $file,
                    'zip_path' => $file->get_filename(),
                ];
            }
        }
        return [
            'chapters' => $chaptersdata,
            'files' => $files,
        ];
    }

    public static function get_glossary_entries($cm) {
        global $DB;
        $glossary = $DB->get_record('glossary', ['id' => $cm->instance], '*', MUST_EXIST);
        $entries = $DB->get_records('glossary_entries', ['glossaryid' => $glossary->id], 'id ASC');
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();

        $entriesdata = [];
        $files = [];

        foreach ($entries as $entry) {
            $definition = file_rewrite_pluginfile_urls(
                $entry->definition,
                'pluginfile.php',
                $context->id,
                'mod_glossary',
                'entry',
                $entry->id
            );
            $entriesdata[] = [
                'concept' => $entry->concept,
                'definition' => $definition,
                'definitionformat' => $entry->definitionformat,
            ];
            $entryfiles = $fs->get_area_files(
                $context->id, 'mod_glossary', 'attachment', $entry->id,
                'sortorder DESC, id ASC', false
            );
            foreach ($entryfiles as $file) {
                $files[] = [
                    'file' => $file,
                    'zip_path' => $file->get_filename(),
                ];
            }
        }
        return [
            'entries' => $entriesdata,
            'files' => $files,
        ];
    }

    public static function get_database_records($cm) {
        global $DB;
        $data = $DB->get_record('data', ['id' => $cm->instance], '*', MUST_EXIST);
        $fields = $DB->get_records('data_fields', ['dataid' => $data->id], 'id ASC');
        $records = $DB->get_records('data_records', ['dataid' => $data->id], 'id ASC');
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();

        $recordsdata = [];
        $allfiles = [];

        foreach ($records as $record) {
            $fieldsdata = [];
            $contents = $DB->get_records('data_content', ['recordid' => $record->id], 'id ASC');
            foreach ($contents as $content) {
                $field = $fields[$content->fieldid] ?? null;
                if (!$field) {
                    continue;
                }
                $value = $content->content;
                if ($field->type === 'textarea') {
                    $value = file_rewrite_pluginfile_urls(
                        $value,
                        'pluginfile.php',
                        $context->id,
                        'mod_data',
                        'content',
                        $content->id
                    );
                }
                if ($field->type === 'file' || $field->type === 'image') {
                    $contentfiles = $fs->get_area_files(
                        $context->id, 'mod_data', 'content', $content->id,
                        'sortorder DESC, id ASC', false
                    );
                    foreach ($contentfiles as $f) {
                        $allfiles[] = [
                            'file' => $f,
                            'zip_path' => $f->get_filename(),
                        ];
                    }
                }
                $fieldsdata[] = [
                    'name' => $field->name,
                    'type' => $field->type,
                    'value' => $value,
                    'value1' => $content->content1,
                ];
            }
            $recordsdata[] = [
                'id' => $record->id,
                'fields' => $fieldsdata,
            ];
        }
        return [
            'fields' => $fields,
            'records' => $recordsdata,
            'files' => $allfiles,
        ];
    }

    public static function get_imscp_files($cm) {
        global $DB;
        $imscp = $DB->get_record('imscp', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();

        $files = $fs->get_area_files(
            $context->id, 'mod_imscp', 'content', $imscp->revision,
            'sortorder DESC, id ASC', false
        );
        $result = [];
        foreach ($files as $file) {
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

    private static $filetype_extensions = [
        'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'odt', 'ods', 'odp', 'csv', 'rtf'],
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'ico', 'tiff', 'tif'],
        'videos' => ['mp4', 'avi', 'mov', 'mkv', 'wmv', 'flv', 'webm', 'm4v', 'mpg', 'mpeg'],
        'audio' => ['mp3', 'wav', 'ogg', 'aac', 'wma', 'flac', 'm4a'],
        'archives' => ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'],
    ];

    public static function filter_file($filename, array $selectedtypes): bool {
        if (empty($selectedtypes)) {
            return true;
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext === '') {
            return in_array('other', $selectedtypes);
        }
        foreach ($selectedtypes as $type) {
            if (isset(self::$filetype_extensions[$type])) {
                if (in_array($ext, self::$filetype_extensions[$type])) {
                    return true;
                }
            }
        }
        if (in_array('other', $selectedtypes)) {
            $allknown = [];
            foreach (self::$filetype_extensions as $exts) {
                $allknown = array_merge($allknown, $exts);
            }
            if (!in_array($ext, $allknown)) {
                return true;
            }
        }
        return false;
    }
}
