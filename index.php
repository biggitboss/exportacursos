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
    'selectall', 'deselectall', 'largeexportwarning', 'exportinprogress',
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

$filetypes = ['documents', 'videos', 'archives', 'other'];
$filetype_labels = [
    'typedocuments', 'typevideos', 'typearchives', 'typeother',
];

echo html_writer::start_tag('form', ['method' => 'post', 'action' => 'export.php', 'class' => 'courseexport-form', 'id' => 'form-course']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::tag('label', get_string('selectcourse', 'local_courseexport') . ' ', ['for' => 'courseid', 'style' => 'margin-right:1em']);
echo html_writer::select($options, 'courseid', '', ['' => '...']);
echo html_writer::empty_tag('br');

echo html_writer::tag('strong', get_string('filetypes', 'local_courseexport'));
echo html_writer::empty_tag('br');
$cnt = 0;
foreach ($filetypes as $ft) {
    echo html_writer::tag('label', html_writer::empty_tag('input', [
        'type' => 'checkbox', 'name' => 'filetypes[]', 'value' => $ft, 'checked' => '',
    ]) . ' ' . get_string($filetype_labels[$cnt], 'local_courseexport'), ['style' => 'margin-right:1em;white-space:nowrap']);
    $cnt++;
}
echo html_writer::tag('a', get_string('deselectall', 'local_courseexport'), [
    'href' => '#', 'class' => 'toggle-filetypes', 'data-form' => 'form-course',
    'style' => 'font-size:.9em;margin-left:.5em',
]);
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

echo html_writer::tag('strong', get_string('filetypes', 'local_courseexport'));
echo html_writer::empty_tag('br');
$cnt = 0;
foreach ($filetypes as $ft) {
    echo html_writer::tag('label', html_writer::empty_tag('input', [
        'type' => 'checkbox', 'name' => 'filetypes[]', 'value' => $ft, 'checked' => '',
    ]) . ' ' . get_string($filetype_labels[$cnt], 'local_courseexport'), ['style' => 'margin-right:1em;white-space:nowrap']);
    $cnt++;
}
echo html_writer::tag('a', get_string('deselectall', 'local_courseexport'), [
    'href' => '#', 'class' => 'toggle-filetypes', 'data-form' => 'form-category',
    'style' => 'font-size:.9em;margin-left:.5em',
]);
echo html_writer::empty_tag('br');

echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('export', 'local_courseexport')]);
echo html_writer::end_tag('form');

echo html_writer::end_div();

echo html_writer::start_div('', ['id' => 'progress-modal', 'style' => 'display:none;margin-top:1.5em;padding:1.5em;background:#f5f7fa;border-radius:8px;border:1px solid #d0d7de']);
echo html_writer::tag('h3', get_string('progressscanning', 'local_courseexport'), ['id' => 'progress-title']);
echo html_writer::tag('div', '', ['id' => 'largeexport-warning', 'style' => 'display:none;color:#856404;background:#fff3cd;border:1px solid #ffc107;border-radius:4px;padding:.5em;margin-bottom:.5em;font-size:.9em']);
echo html_writer::tag('div', html_writer::tag('div', '', ['id' => 'progress-bar-fill', 'style' => 'width:0%;height:20px;background:#0f6cbf;border-radius:10px;transition:width .3s']), ['style' => 'background:#e0e0e0;border-radius:10px;margin:1em 0']);
echo html_writer::tag('p', '', ['id' => 'progress-status']);
echo html_writer::tag('div', '', ['id' => 'progress-stats', 'style' => 'display:flex;gap:2em;font-size:.9em;color:#555']);
echo html_writer::end_div();

$PAGE->requires->js_call_amd('local_courseexport/export', 'init');

echo $OUTPUT->footer();
