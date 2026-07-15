<?php
namespace local_courseexport;

defined('MOODLE_INTERNAL') || die();

class htmlgenerator {

    private static function get_styles() {
        return 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;'
            . 'max-width:960px;margin:2em auto;padding:0 1em;color:#333;line-height:1.6}'
            . 'h1{border-bottom:2px solid #0f6cbf;padding-bottom:.3em;color:#0f6cbf}'
            . 'h2{color:#0f6cbf;margin-top:1.5em}'
            . 'a{color:#0f6cbf;text-decoration:none}'
            . 'a:hover{text-decoration:underline}'
            . 'ul{list-style:none;padding:0}'
            . 'li{margin:.4em 0;padding:.4em .6em;background:#f5f7fa;border-radius:4px}'
            . 'li:hover{background:#e8ecf0}';
    }

    public static function generate_course_index($coursename, array $sections) {
        $html = '<!DOCTYPE html>';
        $html .= '<html lang="' . s(current_language()) . '">';
        $html .= '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<title>' . s($coursename) . '</title>';
        $html .= '<style>' . self::get_styles() . '</style></head><body>';
        $html .= '<h1>' . s($coursename) . '</h1>';
        $html .= '<ul>';
        foreach ($sections as $i => $sec) {
            $link = s($sec['path']) . '/index.html';
            $html .= '<li><a href="' . $link . '">' . s($sec['name']) . '</a></li>';
        }
        $html .= '</ul></body></html>';
        return $html;
    }

    public static function generate_section_index($sectionname, $modules) {
        $html = '<!DOCTYPE html>';
        $html .= '<html lang="' . s(current_language()) . '">';
        $html .= '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<title>' . s($sectionname) . '</title>';
        $html .= '<style>' . self::get_styles() . '</style></head><body>';
        $html .= '<h1>' . s($sectionname) . '</h1>';

        foreach ($modules as $module) {
            $type = $module['type'];
            $name = $module['name'];

            if ($type === 'url') {
                $html .= '<h2>' . s($name) . '</h2>';
                $html .= '<p><a href="' . s($module['url']) . '" target="_blank">' . s($module['url']) . '</a></p>';
                continue;
            }

            $html .= '<h2>' . s($name) . '</h2>';
            if (!empty($module['files'])) {
                $html .= '<ul>';
                foreach ($module['files'] as $f) {
                    $href = s($f['zip_path']);
                    $label = s(basename($f['zip_path']));
                    $html .= '<li><a href="' . $href . '">' . $label . '</a></li>';
                }
                $html .= '</ul>';
            }
        }

        $html .= '</body></html>';
        return $html;
    }
}
