<?php
namespace local_courseexport;

defined('MOODLE_INTERNAL') || die();

class embedfilter {

    public static function filter($html) {
        if (empty($html)) {
            return $html;
        }

        $html = preg_replace(
            '/<iframe[^>]*src=["\'](https?:\/\/[^"\']+)["\'][^>]*>.*?<\/iframe>/is',
            '<p class="embed-placeholder"><em>[Contenido embebido: \1]</em></p>',
            $html
        );

        $html = preg_replace(
            '/<iframe[^>]*>(.*?)<\/iframe>/is',
            '<p class="embed-placeholder"><em>[Contenido embebido]</em></p>',
            $html
        );

        $html = preg_replace(
            '/<embed[^>]*src=["\'](https?:\/\/[^"\']+)["\'][^>]*>/is',
            '<p class="embed-placeholder"><em>[Contenido embebido: \1]</em></p>',
            $html
        );

        $html = preg_replace(
            '/<embed[^>]*>/is',
            '<p class="embed-placeholder"><em>[Contenido embebido]</em></p>',
            $html
        );

        $html = preg_replace(
            '/<object[^>]*>.*?<\/object>/is',
            '<p class="embed-placeholder"><em>[Objeto embebido]</em></p>',
            $html
        );

        $html = preg_replace(
            '/<video[^>]+src=["\'](https?:\/\/[^"\']+)["\'][^>]*>.*?<\/video>/is',
            '<p class="embed-placeholder"><em>[Video embebido: \1]</em></p>',
            $html
        );

        $html = preg_replace(
            '/<video[^>]*>.*?<\/video>/is',
            '<p class="embed-placeholder"><em>[Video embebido]</em></p>',
            $html
        );

        $html = preg_replace(
            '/<audio[^>]+src=["\'](https?:\/\/[^"\']+)["\'][^>]*>.*?<\/audio>/is',
            '<p class="embed-placeholder"><em>[Audio embebido: \1]</em></p>',
            $html
        );

        $html = preg_replace(
            '/<audio[^>]*>.*?<\/audio>/is',
            '<p class="embed-placeholder"><em>[Audio embebido]</em></p>',
            $html
        );

        return $html;
    }
}
