<?php
namespace local_courseexport;

defined('MOODLE_INTERNAL') || die();

class htmlgenerator {

    private static function get_styles() {
        return 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;'
            . 'max-width:960px;margin:2em auto;padding:0 1em;color:#333;line-height:1.6}'
            . 'h1{border-bottom:2px solid #0f6cbf;padding-bottom:.3em;color:#0f6cbf}'
            . 'h2{color:#0f6cbf;margin-top:1.5em}'
            . 'h3{color:#0f6cbf;margin-top:1em;margin-bottom:.3em}'
            . 'h4{margin:.5em 0 .2em;color:#555}'
            . 'ul{list-style:none;padding:0}'
            . 'li{margin:.3em 0;padding:.3em .6em;background:#f5f7fa;border-radius:4px}'
            . '.content-box{padding:.8em;background:#fff;border:1px solid #e0e0e0;border-radius:4px;margin:.5em 0}';
    }

    public static function generate_course_index($coursename, array $sections) {
        $html = '<!DOCTYPE html>';
        $html .= '<html lang="' . s(current_language()) . '">';
        $html .= '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<title>' . s($coursename) . '</title>';
        $html .= '<style>' . self::get_styles() . '</style></head><body>';
        $html .= '<h1>' . s($coursename) . '</h1>';

        foreach ($sections as $section) {
            $html .= '<h2>' . s($section['name']) . '</h2>';

            foreach ($section['modules'] as $module) {
                $type = $module['type'];
                $name = $module['name'];

                if ($type === 'url') {
                    $html .= '<h3>' . s($name) . '</h3>';
                    $html .= '<p>' . s($module['url']) . '</p>';
                    continue;
                }

                if ($type === 'assign' && !empty($module['submissions'])) {
                    $html .= '<h3>' . s($name) . '</h3>';
                    foreach ($module['submissions'] as $submission) {
                        $html .= '<h4>' . s($submission['studentname']) . '</h4>';
                        if (!empty($submission['onlinetext'])) {
                            $content = format_text($submission['onlinetext'], $submission['onlinetextformat'], [
                                'noclean' => true, 'filter' => false,
                            ]);
                            $html .= '<div class="content-box">' . $content . '</div>';
                        }
                        if (!empty($submission['files'])) {
                            $html .= '<ul>';
                            foreach ($submission['files'] as $f) {
                                $html .= '<li>' . s(basename($f['zip_path'])) . '</li>';
                            }
                            $html .= '</ul>';
                        }
                    }
                    continue;
                }

                $html .= '<h3>' . s($name) . '</h3>';
                if (!empty($module['files'])) {
                    $html .= '<ul>';
                    foreach ($module['files'] as $f) {
                        $html .= '<li>' . s(basename($f['zip_path'])) . '</li>';
                    }
                    $html .= '</ul>';
                }
            }
        }

        $html .= '</body></html>';
        return $html;
    }
}
