<?php
require_once(__DIR__ . '/../../config.php');

$action = optional_param('action', 'download', PARAM_ALPHA);
$courseid = optional_param('courseid', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$recursive = optional_param('recursive', 0, PARAM_BOOL);
$filetypes = optional_param_array('filetypes', [], PARAM_ALPHA);
$sesskey = required_param('sesskey', PARAM_RAW);

require_sesskey();
$context = context_system::instance();
require_login();
require_capability('local/courseexport:export', $context);

if ($action === 'count') {
    $courses = [];
    if ($courseid) {
        $course = get_course($courseid);
        $courses = [$course];
    } elseif ($categoryid) {
        $cat = core_course_category::get($categoryid, MUST_EXIST, true);
        $courses = $cat->get_courses(['recursive' => $recursive]);
    }
    if (empty($courses)) {
        echo json_encode(['courses' => 0, 'sections' => 0, 'files' => 0]);
        exit;
    }
    $exporter = new \local_courseexport\exporter($courses, null, $recursive, $filetypes);
    $count = $exporter->count();
    echo json_encode($count);
    exit;
}

try {
    if ($courseid) {
        $course = get_course($courseid);
        $exporter = new \local_courseexport\exporter([$course], null, false, $filetypes);
        $exporter->export();
    } elseif ($categoryid) {
        $cat = core_course_category::get($categoryid, MUST_EXIST, true);
        $courses = $cat->get_courses(['recursive' => $recursive]);
        if (empty($courses)) {
            redirect(
                new \moodle_url('/local/courseexport/index.php'),
                get_string('nocourses', 'local_courseexport'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
        $exporter = new \local_courseexport\exporter($courses, $cat, $recursive, $filetypes);
        $exporter->export();
    } else {
        redirect(
            new \moodle_url('/local/courseexport/index.php'),
            get_string('invalidcourse', 'local_courseexport'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
} catch (\moodle_exception $e) {
    redirect(
        new \moodle_url('/local/courseexport/index.php'),
        $e->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
