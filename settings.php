<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_courseexport',
        get_string('pluginname', 'local_courseexport'),
        new moodle_url('/local/courseexport/index.php')
    ));
}
