<?php
require_once(__DIR__ . '/../../config.php');

$action = optional_param('action', 'download', PARAM_ALPHA);
$courseid = optional_param('courseid', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$recursive = optional_param('recursive', 0, PARAM_BOOL);
$sesskey = required_param('sesskey', PARAM_RAW);

require_sesskey();
$context = context_system::instance();
require_login();
require_capability('local/courseexport:export', $context);

if ($action === 'download_course') {
    $singleid = required_param('courseid', PARAM_INT);
    $course = get_course($singleid);
    \local_courseexport\exporter::export_single($course);
    exit;
}

if ($action === 'count') {
    @ini_set('display_errors', '0');
    $CFG->debugdisplay = 0;
    $CFG->debug = 0;
    ob_start();

    $courses = [];
    if ($courseid) {
        $course = get_course($courseid);
        $courses = [$course];
    } elseif ($categoryid) {
        $cat = core_course_category::get($categoryid, MUST_EXIST, true);
        $courses = $cat->get_courses(['recursive' => $recursive]);
    }
    if (empty($courses)) {
        ob_end_clean();
        echo json_encode(['courses' => 0, 'sections' => 0, 'files' => 0, 'courselist' => []]);
        exit;
    }

    $courselist = [];
    foreach ($courses as $c) {
        $courselist[] = ['id' => $c->id, 'fullname' => $c->fullname];
    }

    $exporter = new \local_courseexport\exporter($courses);
    $count = $exporter->count();
    $count['courselist'] = $courselist;
    ob_end_clean();
    echo json_encode($count);
    exit;
}

try {
    if ($courseid) {
        $course = get_course($courseid);
        $exporter = new \local_courseexport\exporter([$course]);
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
        $exporter = new \local_courseexport\exporter($courses, $cat, $recursive);
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
