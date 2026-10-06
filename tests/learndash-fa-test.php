<?php
/** Standalone contracts for LearnDash Persian UI normalization. */
define('ABSPATH', __DIR__ . '/');
function add_filter() {}
require dirname(__DIR__) . '/inc/learndash_fa.php';
$cases = array(
    'In Progress' => 'در حال پیشرفت',
    'Lesson Content' => 'محتوای درس',
    'Back to Course' => 'بازگشت به دوره',
    'Start Quiz' => 'شروع آزمون',
    'درس Content' => 'محتوای درس',
    'Back to دوره' => 'بازگشت به دوره',
    'Start آزمون' => 'شروع آزمون',
    'Quiz is loading...' => 'آزمون در حال بارگذاری است…',
    'Please wait for Quiz Results' => 'لطفاً برای نمایش نتایج آزمون صبر کنید',
);
foreach ($cases as $source => $expected) {
    if (evented_learndash_fa_text($source) !== $expected) {
        fwrite(STDERR, "FAIL: {$source}\n"); exit(1);
    }
}
if (evented_learndash_fa_gettext('In Progress','In Progress','unrelated-plugin') !== 'In Progress') {
    fwrite(STDERR, "FAIL: unrelated domains must remain untouched\n"); exit(1);
}
echo "LearnDash Persian UI tests passed.\n";
