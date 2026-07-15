<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/book/lib.php');
require_once($CFG->dirroot . '/mod/glossary/lib.php');
require_once($CFG->dirroot . '/mod/data/lib.php');

$courseid = 0;
$course = null;

$courses = get_courses('all', 'c.id ASC');
foreach ($courses as $c) {
    if ($c->id != SITEID && $c->shortname === 'TESTEXPORT') {
        $courseid = $c->id;
        $course = $c;
        break;
    }
}

if (!$course) {
    echo "Creating test course...\n";
    $course = new stdClass();
    $course->fullname = 'Test Export Course';
    $course->shortname = 'TESTEXPORT';
    $course->category = 1;
    $course->summary = 'Course for functional testing of exporter plugin';
    $course->format = 'topics';
    $course->numsections = 1;
    $courseid = create_course($course)->id;
    $course = get_course($courseid);
    echo "  Created course ID $courseid\n";
} else {
    echo "Using existing test course ID $courseid\n";
    // Delete existing cm for these modules to recreate
    $DB->delete_records('course_modules', ['course' => $courseid]);
    rebuild_course_cache($courseid);
}

$modinfo = get_fast_modinfo($course);
$DB = $GLOBALS['DB'];

function create_cm($courseid, $modname, $instanceid, $sectionnum = 0) {
    global $DB;
    $module = $DB->get_record('modules', ['name' => $modname], '*', MUST_EXIST);
    $cm = new stdClass();
    $cm->course = $courseid;
    $cm->module = $module->id;
    $cm->instance = $instanceid;
    $cm->section = 0;
    $cm->visible = 1;
    $cm->visibleoncoursepage = 1;
    $cm->deletioninprogress = 0;
    $cm->id = $DB->insert_record('course_modules', $cm);
    course_add_cm_to_section($courseid, $cm->id, $sectionnum);
    return $cm->id;
}

// 1. Create Book module
echo "\nCreating Book module...\n";
$book = new stdClass();
$book->course = $courseid;
$book->name = 'Test Book';
$book->intro = 'This is a test book for export';
$book->introformat = FORMAT_HTML;
$book->numbering = 1;
$book->navstyle = 1;
$book->customtitles = 0;
$book->section = 0;
$book->visible = 1;
$book->modulename = 'book';
$book->cmidnumber = '';
$bookid = book_add_instance($book, null);
$cmid = create_cm($courseid, 'book', $bookid, 0);
echo "  Book instance id: $bookid, cm id: $cmid\n";

$chapters_data = [
    ['title' => 'Introduction', 'content' => '<p>Welcome to the test book. This chapter covers the basics.</p>'],
    ['title' => 'Advanced Topics', 'content' => '<p>This chapter dives deeper into advanced concepts.</p><p>With multiple paragraphs.</p>'],
    ['title' => 'Subchapter 1', 'content' => '<p>This is a subchapter of Advanced Topics.</p>', 'subchapter' => 1, 'pagenum' => 3],
];
foreach ($chapters_data as $i => $chdata) {
    $ch = new stdClass();
    $ch->bookid = $bookid;
    $ch->pagenum = $chdata['pagenum'] ?? ($i + 1);
    $ch->subchapter = $chdata['subchapter'] ?? 0;
    $ch->title = $chdata['title'];
    $ch->content = $chdata['content'];
    $ch->contentformat = FORMAT_HTML;
    $ch->hidden = 0;
    $ch->timecreated = time();
    $ch->timemodified = time();
    $ch->id = $DB->insert_record('book_chapters', $ch);
    echo "  Added chapter: {$ch->title} (id: {$ch->id})\n";
}
echo "  Book created with " . count($chapters_data) . " chapters\n";

// 2. Create Glossary module
echo "\nCreating Glossary module...\n";
$glossary = new stdClass();
$glossary->course = $courseid;
$glossary->name = 'Test Glossary';
$glossary->intro = 'A glossary for testing export';
$glossary->introformat = FORMAT_HTML;
$glossary->section = 0;
$glossary->visible = 1;
$glossary->modulename = 'glossary';
$glossary->cmidnumber = '';
$glossary->allowduplicatedentries = 0;
$glossary->displayformat = 'dictionary';
$glossary->mainglossary = 1;
$glossary->showspecial = 1;
$glossary->showalphabet = 1;
$glossary->showall = 1;
$glossary->editalways = 0;
$glossary->entbypage = 10;
$glossary->defaultapproval = 1;
$glossary->globalglossary = 0;
$glossary->usedynalink = 0;
$glossary->completionentries = 0;
$glossaryid = glossary_add_instance($glossary);
$cmid = create_cm($courseid, 'glossary', $glossaryid, 0);
echo "  Glossary instance id: $glossaryid, cm id: $cmid\n";

$entries_data = [
    ['concept' => 'PHP', 'definition' => '<p>PHP is a popular general-purpose scripting language.</p>'],
    ['concept' => 'Moodle', 'definition' => '<p>Moodle is a learning management system written in PHP.</p>'],
];
foreach ($entries_data as $ed) {
    $entry = new stdClass();
    $entry->glossaryid = $glossaryid;
    $entry->userid = 2;
    $entry->concept = $ed['concept'];
    $entry->definition = $ed['definition'];
    $entry->definitionformat = FORMAT_HTML;
    $entry->definitiontrust = 0;
    $entry->attachment = 0;
    $entry->timecreated = time();
    $entry->timemodified = time();
    $entry->teacherentry = 1;
    $entry->sourceglossaryid = 0;
    $entry->usedynalink = 0;
    $entry->approved = 1;
    $entry->id = $DB->insert_record('glossary_entries', $entry);
    echo "  Added entry: {$entry->concept} (id: {$entry->id})\n";
}
echo "  Glossary created with " . count($entries_data) . " entries\n";

// 3. Create Database module
echo "\nCreating Database module...\n";
$data = new stdClass();
$data->course = $courseid;
$data->name = 'Test Database';
$data->intro = 'A database for testing export';
$data->introformat = FORMAT_HTML;
$data->section = 0;
$data->visible = 1;
$data->modulename = 'data';
$data->cmidnumber = '';
$data->comments = 0;
$data->requiredentries = 0;
$data->requiredentriestoview = 0;
$data->maxentries = 10;
$data->rssarticles = 0;
$data->approval = 0;
$data->defaultsort = 0;
$data->defaultsortdir = 0;
$data->editany = 0;
$data->notification = 0;
$data->scale = 0;
$data->assessed = 0;
$dataid = data_add_instance($data, null);
$cmid = create_cm($courseid, 'data', $dataid, 0);
echo "  Database instance id: $dataid, cm id: $cmid\n";

$fields_data = [
    ['name' => 'Title', 'type' => 'text', 'description' => 'Entry title', 'param1' => 60],
    ['name' => 'Body', 'type' => 'textarea', 'description' => 'Entry body', 'param1' => 60, 'param2' => 20],
    ['name' => 'Active', 'type' => 'checkbox', 'description' => 'Is active?'],
];
$field_ids = [];
foreach ($fields_data as $fd) {
    $field = new stdClass();
    $field->dataid = $dataid;
    $field->type = $fd['type'];
    $field->name = $fd['name'];
    $field->description = $fd['description'];
    $field->param1 = $fd['param1'] ?? '';
    $field->param2 = $fd['param2'] ?? '';
    $field->param3 = '';
    $field->param4 = '';
    $field->param5 = '';
    $field->id = $DB->insert_record('data_fields', $field);
    $field_ids[$fd['name']] = $field->id;
    echo "  Added field: {$field->name} ({$field->type}) id: {$field->id}\n";
}

$records_data = [
    ['Title' => 'First Entry', 'Body' => '<p>This is the <strong>body</strong> of the first entry.</p>', 'Active' => 1],
    ['Title' => 'Second Entry', 'Body' => '<p>Body of the second entry with a <a href="#">link</a>.</p>', 'Active' => 0],
];
foreach ($records_data as $rd) {
    $record = new stdClass();
    $record->dataid = $dataid;
    $record->userid = 2;
    $record->groupid = 0;
    $record->timecreated = time();
    $record->timemodified = time();
    $record->approved = 1;
    $record->id = $DB->insert_record('data_records', $record);
    echo "  Added record id: {$record->id}\n";
    foreach ($rd as $fname => $value) {
        $dc = new stdClass();
        $dc->fieldid = $field_ids[$fname];
        $dc->recordid = $record->id;
        $dc->content = (string)$value;
        $dc->content1 = '';
        $dc->content2 = '';
        $dc->content3 = '';
        $dc->content4 = '';
        $dc->id = $DB->insert_record('data_content', $dc);
    }
}
echo "  Database created with " . count($records_data) . " records\n";

rebuild_course_cache($courseid);

echo "\n=== Test data created. Run the export test now: ===\n";
echo "php local/courseexport/cli/test_export.php --course=$courseid\n";
