<?php
require_once(__DIR__ . '/../../config.php');

$context = context_system::instance();
require_login();
require_capability('local/courseexport:export', $context);

$PAGE->set_url('/local/courseexport/index.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_courseexport'));
$PAGE->set_heading(get_string('pluginname', 'local_courseexport'));

$PAGE->requires->strings_for_js([
    'progressscanning', 'progressready', 'progresscourses', 'progresssections', 'progressfiles',
    'exportinprogress',
], 'local_courseexport');

echo $OUTPUT->header();

$courses = core_course_category::get(0)->get_courses(['recursive' => true]);

echo html_writer::start_div('courseexport-modes');

echo html_writer::tag('h3', get_string('exportcourse', 'local_courseexport'));

$options = [];
foreach ($courses as $course) {
    $catnames = [];
    $cat = core_course_category::get($course->category, IGNORE_MISSING);
    if ($cat) {
        $parents = $cat->get_parents();
        $parentcats = core_course_category::get_many($parents);
        foreach ($parentcats as $pc) {
            $catnames[] = format_string($pc->name);
        }
        $catnames[] = format_string($cat->name);
    }
    $prefix = $catnames ? implode(' / ', $catnames) . ' / ' : '';
    $options[$course->id] = $prefix . format_string($course->fullname);
}

echo html_writer::start_tag('form', ['method' => 'post', 'action' => 'export.php', 'class' => 'courseexport-form', 'id' => 'form-course']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::tag('label', get_string('selectcourse', 'local_courseexport') . ' ', ['for' => 'courseid', 'style' => 'margin-right:1em']);
echo html_writer::select($options, 'courseid', '', ['' => '...']);
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('export', 'local_courseexport')]);
echo html_writer::end_tag('form');

echo html_writer::tag('hr', '');

echo html_writer::tag('h3', get_string('exportcategory', 'local_courseexport'));

$categories = core_course_category::make_categories_list();

echo html_writer::start_tag('form', ['method' => 'post', 'action' => 'export.php', 'class' => 'courseexport-form', 'id' => 'form-category']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::tag('label', get_string('selectcategory', 'local_courseexport') . ' ', ['for' => 'categoryid', 'style' => 'margin-right:1em']);
echo html_writer::select($categories, 'categoryid', '', ['' => '...']);
echo html_writer::empty_tag('br');
echo html_writer::tag('label', html_writer::empty_tag('input', [
    'type' => 'checkbox', 'name' => 'recursive', 'value' => '1', 'checked' => '',
]) . ' ' . get_string('includingsubcategories', 'local_courseexport'));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('export', 'local_courseexport')]);
echo html_writer::end_tag('form');

echo html_writer::end_div();

echo html_writer::start_div('', ['id' => 'progress-modal', 'style' => 'display:none;margin-top:1.5em;padding:1.5em;background:#f5f7fa;border-radius:8px;border:1px solid #d0d7de']);
echo html_writer::tag('h3', get_string('progressscanning', 'local_courseexport'), ['id' => 'progress-title']);
echo html_writer::tag('div', html_writer::tag('div', '', ['id' => 'progress-bar-fill', 'style' => 'width:0%;height:20px;background:#0f6cbf;border-radius:10px;transition:width .3s']), ['style' => 'background:#e0e0e0;border-radius:10px;margin:1em 0']);
echo html_writer::tag('p', '', ['id' => 'progress-status']);
echo html_writer::tag('div', '', ['id' => 'progress-stats']);
echo html_writer::tag('style', '
.course-progress-header{margin-bottom:1em}.course-progress-header .c-count{font-weight:600;font-size:.95em;color:#333;margin-bottom:.4em}.course-progress-header .c-track{background:#e0e0e0;border-radius:6px;height:8px;overflow:hidden}.course-progress-header .c-track .c-fill{height:100%;border-radius:6px;background:#0f6cbf;transition:width .4s ease}
.c-item{display:flex;align-items:center;padding:.6em .8em;margin-bottom:.35em;border-radius:6px;background:#fff;border:1px solid #e8e8e8;transition:all .15s}.c-item .c-icon{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8em;font-weight:700;flex-shrink:0}.c-item .c-icon.ci-ready{background:#d4edda;color:#28a745}.c-item .c-icon.ci-dl{background:#cce5ff;color:#0f6cbf}.c-item .c-icon.ci-pend{background:#f0f0f0;color:#bbb}.c-item .c-name{flex:1;margin-left:.7em;font-size:.88em;color:#333;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.c-dl-btn{background:#0f6cbf;color:#fff;border:none;border-radius:4px;padding:.35em .75em;font-size:.8em;font-weight:500;cursor:pointer;transition:background .15s;white-space:nowrap}.c-dl-btn:hover{background:#0a5090}.c-dl-btn:disabled{background:#adb5bd;color:#fff;cursor:default}.c-done{color:#28a745;font-weight:600;font-size:.85em;white-space:nowrap}
');
echo html_writer::end_div();

$PAGE->requires->js_call_amd('local_courseexport/export', 'init');

echo $OUTPUT->footer();
