<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params([
    'course' => false,
    'debug' => false,
]);

$courses = [];
if ($options['course']) {
    $course = get_course($options['course']);
    $courses[] = $course;
} else {
    $all = get_courses('all', 'c.sortorder ASC');
    foreach ($all as $c) {
        if ($c->id == SITEID) continue;
        $courses[] = $c;
    }
}

echo "=== Moodle Course Exporter - Functional Test ===\n\n";

// Test: coursereader + extract_module
echo "--- Testing coursereader ---\n";
$count_modules = ['book' => 0, 'glossary' => 0, 'data' => 0, 'imscp' => 0];
$total_sections = 0;

foreach ($courses as $course) {
    try {
        $reader = new \local_courseexport\coursereader($course);
        $sections = $reader->get_sections_with_modules();
        $total_sections += count($sections);

        foreach ($sections as $sec) {
            foreach ($sec['modules'] as $mod) {
                $type = $mod['type'];
                if (isset($count_modules[$type])) {
                    $count_modules[$type]++;
                }
                if ($type === 'book' && !empty($mod['chapters'])) {
                    echo "  Book '{$mod['name']}': " . count($mod['chapters']) . " chapters\n";
                }
                if ($type === 'glossary' && !empty($mod['entries'])) {
                    echo "  Glossary '{$mod['name']}': " . count($mod['entries']) . " entries\n";
                }
                if ($type === 'data' && !empty($mod['records'])) {
                    echo "  Database '{$mod['name']}': " . count($mod['records']) . " records\n";
                }
                if ($type === 'imscp' && !empty($mod['files'])) {
                    echo "  IMSCP '{$mod['name']}': " . count($mod['files']) . " files\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "  ERROR in course {$course->id}: " . $e->getMessage() . "\n";
    }
}

echo "\n--- Module Counts ---\n";
foreach ($count_modules as $type => $count) {
    echo "  $type: $count\n";
}
echo "  Total sections read: $total_sections\n";

// Test: exporter::count()
echo "\n--- Testing exporter::count() ---\n";
$exporter = new \local_courseexport\exporter($courses);
$count = $exporter->count();
echo "  Courses: {$count['courses']}, Sections: {$count['sections']}, Files: {$count['files']}\n";

// Test with filetype filter
$exporter_filtered = new \local_courseexport\exporter($courses, null, false, ['documents', 'images']);
$countf = $exporter_filtered->count();
echo "  (filtered: documents+images) Sections: {$countf['sections']}, Files: {$countf['files']}\n";

// Test: exporter::export() - output to temp file
echo "\n--- Testing exporter::export() to temp file ---\n";
$tmpfile = tempnam(sys_get_temp_dir(), 'courseexport_') . '.zip';
echo "  Output: $tmpfile\n";

// We can't easily test export() since it sends HTTP headers, but we can verify
// that the method doesn't throw before reaching that point.
echo "  SKIP - export() sends HTTP headers directly, use browser instead\n";

echo "\n=== Test complete ===\n";
