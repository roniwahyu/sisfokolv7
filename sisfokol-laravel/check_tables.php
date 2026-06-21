<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = [
    'formative_assessments',
    'formative_assessment_scores',
    'summative_assessments',
    'summative_assessment_scores',
    'student_monthly_scores',
    'student_semester_scores',
    'student_yearly_scores',
    'curriculum_competencies',
    'curriculum_learning_materials',
    'subject_descriptions',
    'report_notes'
];

foreach ($tables as $t) {
    if (Schema::hasTable($t)) {
        echo $t . ": " . implode(', ', Schema::getColumnListing($t)) . PHP_EOL;
    } else {
        echo $t . " (DOES NOT EXIST)" . PHP_EOL;
    }
}
