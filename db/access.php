<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/courseexport:export' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
