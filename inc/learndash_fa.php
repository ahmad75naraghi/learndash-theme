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
        'Loading'=>'در حال بارگذاری','Quiz is loading...'=>'آزمون در حال بارگذاری است…','Please wait'=>'لطفاً صبر کنید','Please wait for Quiz Results'=>'لطفاً برای نمایش نتایج آزمون صبر کنید','Time Limit'=>'محدودیت زمانی','Your response'=>'پاسخ شما',
        'Course Status'=>'وضعیت دوره','Your Course Status'=>'وضعیت دورهٔ شما','Expand All'=>'بازکردن همه','Collapse All'=>'بستن همه','Materials'=>'منابع',
        'Login to Enroll'=>'برای ثبت‌نام وارد شوید','Take this Course'=>'شرکت در این دوره','Enrolled'=>'ثبت‌نام‌شده','Free'=>'رایگان','Buy Now'=>'خرید دوره','Closed'=>'بسته',
        'Certificate'=>'گواهینامه','Download Certificate'=>'دریافت گواهینامه','Available on'=>'قابل دسترسی از','Estimated Time'=>'زمان تقریبی','Prerequisites'=>'پیش‌نیازها',
        'Assignment'=>'تکلیف','Assignments'=>'تکالیف','Upload Assignment'=>'بارگذاری تکلیف','Comments'=>'دیدگاه‌ها','Skip question'=>'ردشدن از سؤال','Back'=>'بازگشت',
        'Answered'=>'پاسخ‌داده‌شده','Review'=>'مرور','Your time'=>'زمان شما','Time has elapsed'=>'زمان به پایان رسیده است',
        'You must fill out this field.'=>'تکمیل این فیلد الزامی است.','This field is required.'=>'تکمیل این فیلد الزامی است.','You must answer this question.'=>'پاسخ‌دادن به این سؤال الزامی است.',
        'Your answer was correct!'=>'پاسخ شما صحیح بود!','Your answer was incorrect.'=>'پاسخ شما نادرست بود.','You have already completed this quiz.'=>'شما قبلاً این آزمون را تکمیل کرده‌اید.',
        'Congratulations! You have passed this quiz.'=>'تبریک! شما در این آزمون قبول شدید.','You have failed this quiz.'=>'شما در این آزمون قبول نشدید.',
        'Please go back and complete the previous lesson.'=>'لطفاً بازگردید و درس قبلی را تکمیل کنید.','Please go back and complete the previous topic.'=>'لطفاً بازگردید و موضوع قبلی را تکمیل کنید.',
        "You don't have access to this content"=>'شما به این محتوا دسترسی ندارید','This content is protected, please log in or enroll in the course to view this content.'=>'این محتوا محافظت شده است؛ برای مشاهده وارد شوید یا در دوره ثبت‌نام کنید.',
        'You have earned %s out of %s points.'=>'شما از %s امتیاز، %s امتیاز کسب کرده‌اید.','Your score is %s.'=>'امتیاز شما %s است.','Average score'=>'میانگین امتیاز','Your score'=>'امتیاز شما',
        'Back to %s'=>'بازگشت به %s','Start %s'=>'شروع %s','%s Content'=>'محتوای %s','Next %s'=>'%s بعدی','Previous %s'=>'%s قبلی',
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
    /* پیام‌هایی که label فارسی داخل جملهٔ انگلیسی تزریق شده است. */
    $mixed = str_ireplace(
        array('Click Here to Continue','Please wait','In Progress','Not Started','Time Limit','Download Certificate','Mark Complete','Expand All','Collapse All','Back to','Start','Restart','Finish','Content','Questions','Question','Results','Assignments','Assignment','Materials','Certificate','Course','Lesson','Topic','Quiz','Correct','Incorrect','Completed','Complete','Progress','Points','Point','Score','Next','Previous','Continue','Submit','Review','Skip','Loading'),
        array('برای ادامه کلیک کنید','لطفاً صبر کنید','در حال پیشرفت','شروع نشده','محدودیت زمانی','دریافت گواهینامه','علامت‌گذاری به‌عنوان تکمیل‌شده','بازکردن همه','بستن همه','بازگشت به','شروع','شروع دوباره','پایان','محتوا','سؤال‌ها','سؤال','نتایج','تکالیف','تکلیف','منابع','گواهینامه','دوره','درس','موضوع','آزمون','صحیح','نادرست','تکمیل‌شده','تکمیل','پیشرفت','امتیازها','امتیاز','نمره','بعدی','قبلی','ادامه','ارسال','مرور','ردشدن','در حال بارگذاری'),
        $trimmed
    );
    if ($mixed !== $trimmed && preg_match('/[A-Za-z]/', $mixed)) {
        $words = array('the'=>'','of'=>'از','to'=>'به','for'=>'برای','and'=>'و','or'=>'یا','your'=>'شما','you'=>'شما','have'=>'','has'=>'','is'=>'است','are'=>'هستند','was'=>'بود','must'=>'باید','this'=>'این','that'=>'آن','before'=>'پیش از','after'=>'پس از','available'=>'در دسترس','required'=>'الزامی','optional'=>'اختیاری','show'=>'نمایش','hide'=>'پنهان‌کردن','view'=>'مشاهده','time'=>'زمان','elapsed'=>'سپری‌شده','status'=>'وضعیت','all'=>'همه');
        $mixed = preg_replace_callback('/\b(?:the|of|to|for|and|or|your|you|have|has|is|are|was|must|this|that|before|after|available|required|optional|show|hide|view|time|elapsed|status|all)\b/i', static function ($match) use ($words) { return $words[strtolower($match[0])]; }, $mixed);
        $mixed = preg_replace('/\s{2,}/u', ' ', trim($mixed));
    }
    return $mixed !== $trimmed ? $mixed : $value;
}

function evented_learndash_fa_gettext($translation, $text, $domain)
{
    $domains = array('learndash','learndash-theme','learndash-propanel','wp-pro-quiz','learndash-quiz','ld-content-cloner');
    if (!in_array((string) $domain, $domains, true)) { return $translation; }
    $source_fa = evented_learndash_fa_text($text);
    if ($source_fa !== $text && !preg_match('/[A-Za-z]/', $source_fa)) { return $source_fa; }
    $translated = evented_learndash_fa_text($translation);
    return $translated === $translation ? $source_fa : $translated;
}
add_filter('gettext','evented_learndash_fa_gettext',50,3);
add_filter('gettext_with_context',static function($translation,$text,$context,$domain){ return evented_learndash_fa_gettext($translation,$text,$domain); },50,4);
add_filter('ngettext',static function($translation,$single,$plural,$number,$domain){ return evented_learndash_fa_gettext($translation,1===(int)$number?$single:$plural,$domain); },50,5);
add_filter('ngettext_with_context',static function($translation,$single,$plural,$number,$context,$domain){ return evented_learndash_fa_gettext($translation,1===(int)$number?$single:$plural,$domain); },50,6);

/**
 * شورت‌کد آزمون در یک برگهٔ معمولی wrapper قالب تکی را ندارد؛ بدون این wrapper
 * selectorهای ایزولهٔ ee-lms عمداً روی آن اعمال نمی‌شوند.
 */
function evented_wrap_embedded_learndash_shortcode($output, $tag, $attr, $m)
{
    if (!in_array($tag, array('ld_quiz','learndash_quiz'), true) || is_admin() || is_singular('sfwd-quiz')) {
        return $output;
    }
    if (false !== strpos((string) $output, 'data-ee-embedded-quiz')) { return $output; }
    return '<section class="ee-qz-engine ee-embedded-quiz" data-ee-embedded-quiz><div class="ee-quiz-body">' . $output . '</div></section>';
}
add_filter('do_shortcode_tag','evented_wrap_embedded_learndash_shortcode',20,4);
