<?php
/**
 * موتور تاریخ هجری شمسی قالب.
 *
 * اصل مهم: وردپرس و افزونه‌ها تاریخ را همچنان به‌صورت میلادی/UTC ذخیره می‌کنند؛
 * این لایه فقط تبدیل، اعتبارسنجی، ورودی و نمایش انسانی را بر عهده دارد.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/** نرمال‌سازی ارقام فارسی و عربی برای پردازش سمت سرور. */
function evented_jalali_latin_digits($value)
{
    return strtr((string) $value, array(
        '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
    ));
}

/** تبدیل ارقام خروجی به فارسی. */
function evented_jalali_persian_digits($value)
{
    return strtr((string) $value, array('0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹'));
}

/** تبدیل میلادی به شمسی؛ خروجی [سال، ماه، روز]. */
function evented_jalali_from_gregorian($gy, $gm, $gd)
{
    $gy = (int) $gy; $gm = (int) $gm; $gd = (int) $gd;
    if (!checkdate($gm, $gd, $gy)) { return false; }
    $offsets = array(0,31,59,90,120,151,181,212,243,273,304,334);
    $gy2 = $gm > 2 ? $gy + 1 : $gy;
    $days = 355666 + 365 * $gy + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
        + intdiv($gy2 + 399, 400) + $gd + $offsets[$gm - 1];
    $jy = -1595 + 33 * intdiv($days, 12053); $days %= 12053;
    $jy += 4 * intdiv($days, 1461); $days %= 1461;
    if ($days > 365) { $jy += intdiv($days - 1, 365); $days = ($days - 1) % 365; }
    if ($days < 186) { return array($jy, 1 + intdiv($days, 31), 1 + ($days % 31)); }
    return array($jy, 7 + intdiv($days - 186, 30), 1 + (($days - 186) % 30));
}

/** تبدیل شمسی به میلادی؛ خروجی [سال، ماه، روز]. */
function evented_jalali_to_gregorian($jy, $jm, $jd)
{
    $jy = (int) $jy; $jm = (int) $jm; $jd = (int) $jd;
    if (!evented_jalali_checkdate($jm, $jd, $jy)) { return false; }
    $gy = $jy <= 979 ? 621 : 1600;
    $jy -= $jy <= 979 ? 0 : 979;
    $days = 365 * $jy + intdiv($jy, 33) * 8 + intdiv(($jy % 33) + 3, 4) + 78 + $jd
        + ($jm < 7 ? ($jm - 1) * 31 : ($jm - 7) * 30 + 186);
    $gy += 400 * intdiv($days, 146097); $days %= 146097;
    if ($days > 36524) {
        $gy += 100 * intdiv(--$days, 36524); $days %= 36524;
        if ($days >= 365) { $days++; }
    }
    $gy += 4 * intdiv($days, 1461); $days %= 1461;
    if ($days > 365) { $gy += intdiv($days - 1, 365); $days = ($days - 1) % 365; }
    $gd = $days + 1;
    $months = array(0,31,($gy % 4 === 0 && $gy % 100 !== 0) || $gy % 400 === 0 ? 29 : 28,31,30,31,30,31,31,30,31,30,31);
    for ($gm = 1; $gm <= 12 && $gd > $months[$gm]; $gm++) { $gd -= $months[$gm]; }
    return array($gy, $gm, $gd);
}

/** تعداد روزهای ماه شمسی با تشخیص دقیق کبیسه. */
function evented_jalali_month_length($year, $month)
{
    $year = (int) $year; $month = (int) $month;
    if ($month >= 1 && $month <= 6) { return 31; }
    if ($month >= 7 && $month <= 11) { return 30; }
    if (12 !== $month) { return 0; }
    $start = evented_jalali_to_gregorian_unchecked($year, 1, 1);
    $next  = evented_jalali_to_gregorian_unchecked($year + 1, 1, 1);
    if (!$start || !$next) { return 29; }
    $a = gmmktime(0, 0, 0, $start[1], $start[2], $start[0]);
    $b = gmmktime(0, 0, 0, $next[1], $next[2], $next[0]);
    return (($b - $a) / DAY_IN_SECONDS) > 365 ? 30 : 29;
}

/** مبدل داخلی بدون فراخوانی اعتبارسنجی (برای محاسبهٔ کبیسه). */
function evented_jalali_to_gregorian_unchecked($jy, $jm, $jd)
{
    $gy = $jy <= 979 ? 621 : 1600; $jy -= $jy <= 979 ? 0 : 979;
    $days = 365 * $jy + intdiv($jy,33)*8 + intdiv(($jy%33)+3,4)+78+$jd+($jm<7?($jm-1)*31:($jm-7)*30+186);
    $gy += 400*intdiv($days,146097); $days%=146097;
    if ($days>36524) { $gy += 100*intdiv(--$days,36524); $days%=36524; if ($days>=365) {$days++;} }
    $gy += 4*intdiv($days,1461); $days%=1461;
    if ($days>365) { $gy += intdiv($days-1,365); $days=($days-1)%365; }
    $gd=$days+1; $months=array(0,31,($gy%4===0&&$gy%100!==0)||$gy%400===0?29:28,31,30,31,30,31,31,30,31,30,31);
    for($gm=1;$gm<=12&&$gd>$months[$gm];$gm++){$gd-=$months[$gm];}
    return array($gy,$gm,$gd);
}

function evented_jalali_checkdate($month, $day, $year)
{
    $year=(int)$year; $month=(int)$month; $day=(int)$day;
    return $year >= 1 && $year <= 3177 && $month >= 1 && $month <= 12 && $day >= 1 && $day <= evented_jalali_month_length($year, $month);
}

/** تجزیهٔ تاریخ شمسی با / یا - یا . و ارقام فارسی/عربی. */
function evented_jalali_parse($value)
{
    $value = trim(evented_jalali_latin_digits($value));
    if (!preg_match('/^(\d{3,4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})(?:[ T]+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', $value, $m)) { return false; }
    $parts = array('year'=>(int)$m[1], 'month'=>(int)$m[2], 'day'=>(int)$m[3], 'hour'=>isset($m[4])?(int)$m[4]:0, 'minute'=>isset($m[5])?(int)$m[5]:0, 'second'=>isset($m[6])?(int)$m[6]:0);
    if (!evented_jalali_checkdate($parts['month'],$parts['day'],$parts['year']) || $parts['hour']>23 || $parts['minute']>59 || $parts['second']>59) { return false; }
    return $parts;
}

/** تبدیل ورودی شمسی به timestamp در timezone سایت؛ مناسب query و ذخیرهٔ استاندارد. */
function evented_jalali_to_timestamp($value, $end_of_day = false, $timezone = null)
{
    $parts = is_array($value) ? $value : evented_jalali_parse($value);
    if (!$parts) { return 0; }
    $g = evented_jalali_to_gregorian($parts['year'],$parts['month'],$parts['day']);
    if (!$g) { return 0; }
    $hour=$end_of_day?23:(int)($parts['hour']??0); $minute=$end_of_day?59:(int)($parts['minute']??0); $second=$end_of_day?59:(int)($parts['second']??0);
    try {
        $tz = $timezone instanceof DateTimeZone ? $timezone : wp_timezone();
        $date = new DateTimeImmutable(sprintf('%04d-%02d-%02d %02d:%02d:%02d',$g[0],$g[1],$g[2],$hour,$minute,$second),$tz);
        return $date->getTimestamp();
    } catch (Exception $e) { return 0; }
}

/** قالب‌بندی حرفه‌ای تاریخ شمسی در timezone سایت. */
function evented_jalali_format($timestamp = null, $format = 'j F Y', $persian_digits = false)
{
    $timestamp = null === $timestamp ? time() : (int)$timestamp;
    try { $date=(new DateTimeImmutable('@'.$timestamp))->setTimezone(wp_timezone()); } catch(Exception $e){ return ''; }
    $j=evented_jalali_from_gregorian((int)$date->format('Y'),(int)$date->format('n'),(int)$date->format('j')); if(!$j){return '';}
    list($jy,$jm,$jd)=$j;
    $months=array(1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند');
    $short=array(1=>'فرو',2=>'ارد',3=>'خرد',4=>'تیر',5=>'مرد',6=>'شهر',7=>'مهر',8=>'آبا',9=>'آذر',10=>'دی',11=>'بهم',12=>'اسف');
    $week=array(1=>'دوشنبه',2=>'سه‌شنبه',3=>'چهارشنبه',4=>'پنجشنبه',5=>'جمعه',6=>'شنبه',7=>'یکشنبه');
    $week_short=array(1=>'دوش',2=>'سه‌',3=>'چهار',4=>'پنج',5=>'جمعه',6=>'شنبه',7=>'یک');
    $day_of_year=($jm<=6?($jm-1)*31:186+($jm-7)*30)+$jd-1;
    $tokens=array('Y'=>$jy,'y'=>substr((string)$jy,-2),'m'=>sprintf('%02d',$jm),'n'=>$jm,'d'=>sprintf('%02d',$jd),'j'=>$jd,'F'=>$months[$jm],'M'=>$short[$jm],'l'=>$week[(int)$date->format('N')],'D'=>$week_short[(int)$date->format('N')],'t'=>evented_jalali_month_length($jy,$jm),'L'=>evented_jalali_month_length($jy,12)===30?'1':'0','z'=>$day_of_year,'H'=>$date->format('H'),'G'=>$date->format('G'),'h'=>$date->format('h'),'g'=>$date->format('g'),'i'=>$date->format('i'),'s'=>$date->format('s'),'a'=>$date->format('a')==='am'?'ق.ظ':'ب.ظ','A'=>$date->format('A')==='AM'?'قبل‌ازظهر':'بعدازظهر','U'=>$timestamp);
    $out=''; $escaped=false;
    for($i=0,$len=strlen($format);$i<$len;$i++){ $char=$format[$i]; if($escaped){$out.=$char;$escaped=false;continue;} if($char==='\\'){$escaped=true;continue;} $out.=array_key_exists($char,$tokens)?$tokens[$char]:$char; }
    return $persian_digits ? evented_jalali_persian_digits($out) : $out;
}

/** خروجی ISO میلادی برای datetime و دادهٔ ماشینی، کنار متن شمسی. */
function evented_jalali_datetime_attr($timestamp)
{
    try { return (new DateTimeImmutable('@'.(int)$timestamp))->format(DateTimeInterface::ATOM); } catch(Exception $e){ return ''; }
}

/** فرمت‌های ماشینی هرگز نباید شمسی شوند. */
function evented_jalali_is_machine_format($format)
{
    return in_array((string)$format,array('c','r','U','Y-m-d\TH:i:sP','Y-m-d H:i:s','Y-m-d\TH:i:s'),true);
}

/** فیلتر امن تاریخ‌های انسانی نوشته و دیدگاه؛ REST، feed و خروجی ماشینی دست‌نخورده‌اند. */
function evented_jalali_filter_post_date($date, $format, $post)
{
    if (is_feed() || (defined('REST_REQUEST') && REST_REQUEST) || evented_jalali_is_machine_format($format)) { return $date; }
    $post=get_post($post); if(!$post){return $date;}
    $timestamp=function_exists('get_post_timestamp')?get_post_timestamp($post):(int)mysql2date('U',$post->post_date_gmt.' +0000');
    return evented_jalali_format($timestamp,$format?:get_option('date_format','j F Y'),true);
}
add_filter('get_the_date','evented_jalali_filter_post_date',20,3);

function evented_jalali_filter_modified_date($date, $format, $post)
{
    if (is_feed() || (defined('REST_REQUEST') && REST_REQUEST) || evented_jalali_is_machine_format($format)) { return $date; }
    $post=get_post($post); if(!$post){return $date;}
    $timestamp=(int)mysql2date('U',$post->post_modified_gmt.' +0000');
    return evented_jalali_format($timestamp,$format?:get_option('date_format','j F Y'),true);
}
add_filter('get_the_modified_date','evented_jalali_filter_modified_date',20,3);

function evented_jalali_filter_comment_date($date, $format, $comment)
{
    if (is_feed() || (defined('REST_REQUEST') && REST_REQUEST) || evented_jalali_is_machine_format($format)) { return $date; }
    $comment=get_comment($comment); if(!$comment){return $date;}
    $timestamp=(int)mysql2date('U',$comment->comment_date_gmt.' +0000');
    return evented_jalali_format($timestamp,$format?:get_option('date_format','j F Y'),true);
}
add_filter('get_comment_date','evented_jalali_filter_comment_date',20,3);

/** نمایش شمسی ستون تاریخ نوشته‌ها در مدیریت، بدون تغییر query یا دیتابیس. */
function evented_jalali_admin_post_date($time, $post)
{
    if (!$post instanceof WP_Post) { return $time; }
    $timestamp=function_exists('get_post_timestamp')?get_post_timestamp($post):(int)mysql2date('U',$post->post_date_gmt.' +0000');
    return evented_jalali_format($timestamp,'Y/m/d H:i',true);
}
add_filter('post_date_column_time','evented_jalali_admin_post_date',20,2);

/** ستون تاریخ عضویت شمسی در فهرست کاربران مدیریت. */
function evented_jalali_user_columns($columns)
{
    $columns['evented_registered_jalali'] = 'تاریخ عضویت';
    return $columns;
}
add_filter('manage_users_columns','evented_jalali_user_columns');
function evented_jalali_user_column($value, $column, $user_id)
{
    if ('evented_registered_jalali' !== $column) { return $value; }
    $user = get_userdata($user_id);
    if (!$user || !$user->user_registered) { return '—'; }
    try { $timestamp = (new DateTimeImmutable($user->user_registered, wp_timezone()))->getTimestamp(); }
    catch (Exception $e) { return '—'; }
    return evented_jalali_format($timestamp,'Y/m/d H:i',true);
}
add_filter('manage_users_custom_column','evented_jalali_user_column',20,3);

/** بارگذاری انتخابگر فقط در صفحاتی که واقعاً ورودی شمسی دارند. */
function evented_enqueue_jalali_picker()
{
    if (wp_script_is('evented-jalali-loader','enqueued')) { return; }
    wp_enqueue_style('jalalidatepicker-css',PATH_DIR_URL.'/assets/css/jalalidatepicker.min.css',array(),'1.0.1');
    wp_enqueue_script('evented-jalali-core',PATH_DIR_URL.'/assets/js/jalali-core.js',array(),'1.0.0',true);
    wp_enqueue_script('jalalidatepicker-js',PATH_DIR_URL.'/assets/js/jalalidatepicker.min.js',array('evented-jalali-core'),'1.0.1',true);
    wp_enqueue_script('evented-jalali-loader',PATH_DIR_URL.'/assets/js/jalali-loader.js',array('jalalidatepicker-js'),'1.0.0',true);
}
