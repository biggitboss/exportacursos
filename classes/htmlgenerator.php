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
            . 'li:hover{background:#e8ecf0}'
            . '.module-name{font-weight:600;margin:1em 0 .3em;color:#555}'
            . '.embed-placeholder{color:#888;font-style:italic;padding:.5em;background:#fafafa;border-left:3px solid #ddd}'
            . '.content-box{padding:.8em;background:#fff;border:1px solid #e0e0e0;border-radius:4px;margin:.5em 0}'
            . '.nav-links{display:flex;justify-content:space-between;margin-top:2em;padding-top:1em;border-top:1px solid #eee}';
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

            if ($type === 'label') {
                if (!empty($module['content'])) {
                    $html .= '<div class="content-box">';
                    $content = format_text($module['content'], $module['contentformat'], [
                        'noclean' => true, 'filter' => false,
                    ]);
                    $content = self::filter($content);
                    $html .= $content;
                    $html .= '</div>';
                }
                if (!empty($module['files'])) {
                    $html .= '<ul>';
                    foreach ($module['files'] as $f) {
                        $href = s($f['zip_path']);
                        $label = s(basename($f['zip_path']));
                        $html .= '<li><a href="' . $href . '">' . $label . '</a></li>';
                    }
                    $html .= '</ul>';
                }
                continue;
            }

            if ($type === 'page') {
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
                if (!empty($module['content'])) {
                    $html .= '<div class="content-box">';
                    $content = format_text($module['content'], $module['contentformat'], [
                        'noclean' => true, 'filter' => false,
                    ]);
                    $content = self::filter($content);
                    $html .= $content;
                    $html .= '</div>';
                }
                continue;
            }

            if ($type === 'book' && !empty($module['chapters'])) {
                $html .= '<h2>' . s($name) . '</h2>';
                foreach ($module['chapters'] as $ch) {
                    if ($ch['hidden']) {
                        continue;
                    }
                    $html .= '<h3>' . s($ch['title']) . '</h3>';
                    if (!empty($ch['content'])) {
                        $html .= '<div class="content-box">';
                        $content = format_text($ch['content'], $ch['contentformat'], [
                            'noclean' => true, 'filter' => false,
                        ]);
                        $content = self::filter($content);
                        $html .= $content;
                        $html .= '</div>';
                    }
                }
                if (!empty($module['files'])) {
                    $html .= '<ul>';
                    foreach ($module['files'] as $f) {
                        $href = s($f['zip_path']);
                        $label = s(basename($f['zip_path']));
                        $html .= '<li><a href="' . $href . '">' . $label . '</a></li>';
                    }
                    $html .= '</ul>';
                }
                continue;
            }

            if ($type === 'glossary' && !empty($module['entries'])) {
                $html .= '<h2>' . s($name) . '</h2>';
                $html .= '<dl>';
                foreach ($module['entries'] as $entry) {
                    $html .= '<dt><strong>' . s($entry['concept']) . '</strong></dt>';
                    if (!empty($entry['definition'])) {
                        $html .= '<dd class="content-box">';
                        $content = format_text($entry['definition'], $entry['definitionformat'], [
                            'noclean' => true, 'filter' => false,
                        ]);
                        $content = self::filter($content);
                        $html .= $content;
                        $html .= '</dd>';
                    }
                }
                $html .= '</dl>';
                if (!empty($module['files'])) {
                    $html .= '<ul>';
                    foreach ($module['files'] as $f) {
                        $href = s($f['zip_path']);
                        $label = s(basename($f['zip_path']));
                        $html .= '<li><a href="' . $href . '">' . $label . '</a></li>';
                    }
                    $html .= '</ul>';
                }
                continue;
            }

            if ($type === 'data' && !empty($module['records'])) {
                $html .= '<h2>' . s($name) . '</h2>';
                foreach ($module['records'] as $ri => $rec) {
                    $html .= '<div class="content-box">';
                    $html .= '<h3>' . get_string('record', 'local_courseexport') . ' #' . ($ri + 1) . '</h3>';
                    foreach ($rec['fields'] as $field) {
                        $html .= '<p><strong>' . s($field['name']) . ':</strong> ';
                        if ($field['type'] === 'textarea') {
                            $content = format_text($field['value'], FORMAT_HTML, [
                                'noclean' => true, 'filter' => false,
                            ]);
                            $content = self::filter($content);
                            $html .= $content;
                        } else if ($field['type'] === 'checkbox') {
                            $html .= $field['value'] ? '✓' : '✗';
                        } else {
                            $html .= s($field['value']);
                        }
                        $html .= '</p>';
                    }
                    $html .= '</div>';
                }
                if (!empty($module['files'])) {
                    $html .= '<ul>';
                    foreach ($module['files'] as $f) {
                        $href = s($f['zip_path']);
                        $label = s(basename($f['zip_path']));
                        $html .= '<li><a href="' . $href . '">' . $label . '</a></li>';
                    }
                    $html .= '</ul>';
                }
                continue;
            }

            $html .= '<div class="module-name">' . s($name) . '</div>';
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

    private static function filter($content) {
        return embedfilter::filter($content);
    }
}
