<?php
namespace local_courseexport;

defined('MOODLE_INTERNAL') || die();

class coursereader {
    private $course;

    private const SUPPORTED_MODULES = ['resource', 'folder', 'page', 'label', 'url', 'book', 'glossary', 'data', 'imscp'];

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
            'content' => null,
            'contentformat' => null,
            'url' => null,
        ];

        switch ($cm->modname) {
            case 'resource':
                $data['files'] = fileextractor::get_resource_files($cm);
                break;

            case 'folder':
                $data['files'] = fileextractor::get_folder_files($cm);
                break;

            case 'page':
                $pagedata = fileextractor::get_page_content($cm);
                $data['content'] = $pagedata['content'];
                $data['contentformat'] = $pagedata['contentformat'];
                $data['files'] = $pagedata['files'];
                break;

            case 'label':
                $labeldata = fileextractor::get_label_content($cm);
                $data['content'] = $labeldata['content'];
                $data['contentformat'] = $labeldata['contentformat'];
                $data['files'] = $labeldata['files'];
                break;

            case 'url':
                $data['url'] = fileextractor::get_url_link($cm);
                break;

            case 'book':
                $bookdata = fileextractor::get_book_chapters($cm);
                $data['chapters'] = $bookdata['chapters'];
                $data['files'] = $bookdata['files'];
                break;

            case 'glossary':
                $glossarydata = fileextractor::get_glossary_entries($cm);
                $data['entries'] = $glossarydata['entries'];
                $data['files'] = $glossarydata['files'];
                break;

            case 'data':
                $datadata = fileextractor::get_database_records($cm);
                $data['fields'] = $datadata['fields'];
                $data['records'] = $datadata['records'];
                $data['files'] = $datadata['files'];
                break;

            case 'imscp':
                $data['files'] = fileextractor::get_imscp_files($cm);
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
