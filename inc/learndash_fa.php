<?php
/** ترجمهٔ یکپارچهٔ رشته‌های رابط LearnDash/WpProQuiz که در بستهٔ فارسی ناقص مانده‌اند. */
defined('ABSPATH') || exit;

function evented_learndash_fa_text($value)
{
    $value = (string) $value;
    $map = array(
        'In Progress'=>'در حال پیشرفت','Not Started'=>'شروع نشده','Completed'=>'تکمیل‌شده','Complete'=>'تکمیل‌شده',
        'Passed'=>'قبول‌شده','Failed'=>'قبول‌نشده','Pending'=>'در انتظار','Locked'=>'قفل‌شده','Available'=>'در دسترس',
        'Course Content'=>'محتوای دوره','Lesson Content'=>'محتوای درس','Topic Content'=>'محتوای موضوع','Quiz Content'=>'محتوای آزمون',
        'Back to Course'=>'بازگشت به دوره','Back to Lesson'=>'بازگشت به درس','Back to Topic'=>'بازگشت به موضوع',
        'Start Quiz'=>'شروع آزمون','Restart Quiz'=>'شروع دوبارهٔ آزمون','Finish Quiz'=>'پایان آزمون','Review Questions'=>'مرور سؤال‌ها',
        'View Questions'=>'مشاهدهٔ سؤال‌ها','Next'=>'بعدی','Previous'=>'قبلی','Continue'=>'ادامه','Mark Complete'=>'علامت‌گذاری به‌عنوان تکمیل‌شده',
        'Click Here to Continue'=>'برای ادامه کلیک کنید','Correct'=>'صحیح','Incorrect'=>'نادرست','Question'=>'سؤال','Questions'=>'سؤال‌ها',
        'Results'=>'نتایج','Print Results'=>'چاپ نتایج','Leaderboard'=>'جدول برترین‌ها','Show Leaderboard'=>'نمایش جدول برترین‌ها',
        'Loading'=>'در حال بارگذاری','Please wait'=>'لطفاً صبر کنید','Time Limit'=>'محدودیت زمانی','Your response'=>'پاسخ شما',
        'You have already completed this quiz.'=>'شما قبلاً این آزمون را تکمیل کرده‌اید.',
    );
    $trimmed = trim($value);
    if (isset($map[$trimmed])) {
        $leading = substr($value, 0, strlen($value) - strlen(ltrim($value)));
        $trailing = substr($value, strlen(rtrim($value)));
        return $leading . $map[$trimmed] . $trailing;
    }
    if (preg_match('/^Back to\s+(.+)$/iu', $trimmed, $m)) { return 'بازگشت به ' . $m[1]; }
    if (preg_match('/^Start\s+(.+)$/iu', $trimmed, $m)) { return 'شروع ' . $m[1]; }
    if (preg_match('/^(.+)\s+Content$/iu', $trimmed, $m)) { return 'محتوای ' . $m[1]; }
    return $value;
}

function evented_learndash_fa_gettext($translation, $text, $domain)
{
    $domains = array('learndash','learndash-theme','learndash-propanel','wp-pro-quiz','learndash-quiz','ld-content-cloner');
    if (!in_array((string) $domain, $domains, true)) { return $translation; }
    $translated = evented_learndash_fa_text($translation);
    return $translated === $translation ? evented_learndash_fa_text($text) : $translated;
}
add_filter('gettext','evented_learndash_fa_gettext',50,3);
add_filter('gettext_with_context',static function($translation,$text,$context,$domain){ return evented_learndash_fa_gettext($translation,$text,$domain); },50,4);
