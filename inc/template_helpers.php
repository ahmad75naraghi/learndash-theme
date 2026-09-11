<?php
/**
 * هلپرهای مشترک قالب‌های «ee-*» (تک‌نوشته، آرشیو نوشته‌ها و پوستهٔ مشترک)
 *
 * این فایل فقط توابع کمکی دارد؛ هیچ خروجی چاپ نمی‌کند.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/**
 * آیا صفحهٔ جاری از طراحی جدید «evented-edu» (کلاس‌های ee-*) استفاده می‌کند؟
 *
 * برای بارگذاری پوستهٔ مشترک (ee-shell.css) استفاده می‌شود.
 *
 * @return bool
 */
function evented_is_ee_view()
{
	if (is_front_page()) {
		return true;
	}

	if (is_singular('post') || is_home() || is_search()) {
		return true;
	}

	// آرشیوهای خودِ نوشته‌ها (دسته، برچسب، بایگانی زمانی)
	if (is_category() || is_tag() || is_date()) {
		return true;
	}

	// تک‌دوره و تک‌درس (LearnDash) — قالب‌های evented-edu
	if (is_singular(array('sfwd-courses', 'sfwd-lessons'))) {
		return true;
	}

	return false;
}

/**
 * آدرس canonیک صفحهٔ جاری (برای اشتراک‌گذاری).
 *
 * @return string
 */
function evented_current_url()
{
	if (is_singular()) {
		return (string) get_permalink();
	}

	if (is_front_page() || is_home()) {
		return (string) home_url('/');
	}

	if (is_search()) {
		return (string) get_search_link();
	}

	if (is_category() || is_tag()) {
		$term = get_queried_object();
		if ($term instanceof WP_Term) {
			$link = get_term_link($term);
			if (!is_wp_error($link)) {
				return (string) $link;
			}
		}
	}

	if (is_post_type_archive()) {
		return (string) get_post_type_archive_link((string) get_query_var('post_type'));
	}

	if (is_author()) {
		return (string) get_author_posts_url((int) get_queried_object_id());
	}

	return (string) home_url('/');
}

/**
 * فراخوانی امن wp_date() با بازگشت به date_i18n() در وردپرس‌های قدیمی‌تر از ۵.۳.
 *
 * @param string   $format    الگوی تاریخ.
 * @param int|null $timestamp زمان (timestamp) یا null برای الان.
 * @return string
 */
function evented_wp_date($format, $timestamp = null)
{
	if (function_exists('wp_date')) {
		return (string) wp_date($format, $timestamp);
	}

	return (string) date_i18n($format, $timestamp);
}

/**
 * تبدیل تاریخ میلادی به هجری شمسی.
 *
 * الگوریتم استاندارد jalaali (همان الگوریتم jdf.ir / jalaali-js).
 *
 * @param int $gy سال میلادی.
 * @param int $gm ماه میلادی (۱ تا ۱۲).
 * @param int $gd روز میلادی.
 * @return int[] {jy, jm, jd}
 */
function evented_gregorian_to_jalali($gy, $gm, $gd)
{
	$gy = (int) $gy;
	$gm = (int) $gm;
	$gd = (int) $gd;

	$g_days_in_month = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
	$gy2             = ($gm > 2) ? ($gy + 1) : $gy;

	$days = 355666
		+ (365 * $gy)
		+ (int) (($gy2 + 3) / 4)
		- (int) (($gy2 + 99) / 100)
		+ (int) (($gy2 + 399) / 400)
		+ $gd
		+ $g_days_in_month[$gm - 1];

	$jy    = -1595 + (33 * (int) ($days / 12053));
	$days %= 12053;

	$jy   += 4 * (int) ($days / 1461);
	$days %= 1461;

	if ($days > 365) {
		$jy   += (int) (($days - 1) / 365);
		$days  = ($days - 1) % 365;
	}

	if ($days < 186) {
		$jm = 1 + (int) ($days / 31);
		$jd = 1 + ($days % 31);
	} else {
		$jm = 7 + (int) (($days - 186) / 30);
		$jd = 1 + (($days - 186) % 30);
	}

	return array($jy, $jm, $jd);
}

/**
 * قالب‌بندی تاریخ هجری شمسی با مجموعه‌ای کوچک از توکن‌ها.
 *
 * توکن‌های پشتیبانی‌شده: Y (سال کامل)، y (دو رقم آخر)، m (ماه عددی)،
 * d (روز عددی)، j (روز بدون صفر)، F (نام ماه)، l (نام روز هفته).
 *
 * @param int    $timestamp زمان (timestamp) در منطقهٔ زمانی سایت.
 * @param string $format    الگوی تاریخ.
 * @return string
 */
function evented_format_jalali($timestamp, $format = 'j F Y')
{
	$months = array(
		1  => 'فروردین',
		2  => 'اردیبهشت',
		3  => 'خرداد',
		4  => 'تیر',
		5  => 'مرداد',
		6  => 'شهریور',
		7  => 'مهر',
		8  => 'آبان',
		9  => 'آذر',
		10 => 'دی',
		11 => 'بهمن',
		12 => 'اسفند',
	);

	/* نام روز هفته بر پایهٔ N (روز ISO در منطقهٔ زمانی سایت: ۱=دوشنبه … ۷=یکشنبه) */
	$weekdays = array(
		'6' => 'شنبه',
		'7' => 'یکشنبه',
		'1' => 'دوشنبه',
		'2' => 'سه‌شنبه',
		'3' => 'چهارشنبه',
		'4' => 'پنجشنبه',
		'5' => 'جمعه',
	);

	$timestamp = (int) $timestamp;

	list($jy, $jm, $jd) = evented_gregorian_to_jalali(
		(int) evented_wp_date('Y', $timestamp),
		(int) evented_wp_date('n', $timestamp),
		(int) evented_wp_date('j', $timestamp)
	);

	$weekday_no = (string) evented_wp_date('N', $timestamp);
	$weekday_fa = isset($weekdays[$weekday_no]) ? $weekdays[$weekday_no] : '';

	$replace = array(
		'Y' => (string) $jy,
		'y' => substr((string) $jy, -2),
		'm' => str_pad((string) $jm, 2, '0', STR_PAD_LEFT),
		'd' => str_pad((string) $jd, 2, '0', STR_PAD_LEFT),
		'j' => (string) $jd,
		'F' => isset($months[$jm]) ? $months[$jm] : '',
		'l' => $weekday_fa,
	);

	$out = '';
	$len = strlen($format);
	for ($i = 0; $i < $len; $i++) {
		$char = $format[$i];
		if ('\\' === $char && $i + 1 < $len) {
			$out .= $format[++$i];
			continue;
		}
		$out .= isset($replace[$char]) ? $replace[$char] : $char;
	}

	return $out;
}

/**
 * تاریخ انتشار نوشته به شمسی.
 *
 * اگر افزونهٔ شمسی‌ساز (مثل wp-parsidate) فعال باشد، خروجی وردپرس
 * بدون تغییر برمی‌گردد؛ در غیر این صورت تبدیل داخلی انجام می‌شود.
 *
 * @param WP_Post|int|null $post   نوشته.
 * @param string           $format الگوی تاریخ در حالت تبدیل داخلی.
 * @return string
 */
function evented_post_date($post = null, $format = 'j F Y')
{
	$post = get_post($post);
	if (!$post instanceof WP_Post) {
		return '';
	}

	$from_wp = get_the_date('', $post);
	if ($from_wp && !preg_match('/[0-9]/', $from_wp)) {
		return $from_wp; // خروجی افزونهٔ شمسی‌ساز (بدون رقم لاتین)
	}

	return evented_format_jalali(evented_post_timestamp($post), $format);
}

/**
 * زمان انتشار نوشته (ساعت:دقیقه) به‌همراه تاریخ شمسی در صورت نیاز.
 *
 * @param WP_Post|int|null $post نوشته.
 * @return string
 */
function evented_post_time($post = null)
{
	$post = get_post($post);
	if (!$post instanceof WP_Post) {
		return '';
	}

	$from_wp = get_the_time('', $post);
	if ($from_wp && !preg_match('/[a-zA-Z]/', $from_wp)) {
		return $from_wp;
	}

	return evented_wp_date('H:i', evented_post_timestamp($post));
}

/**
 * timestamp انتشار نوشته در منطقهٔ زمانی سایت.
 *
 * @param WP_Post $post نوشته.
 * @return int
 */
function evented_post_timestamp($post)
{
	if (function_exists('get_post_timestamp')) {
		return (int) get_post_timestamp($post);
	}

	return (int) mysql2date('U', $post->post_date_gmt . ' +0000');
}

/**
 * زمان تقریبی مطالعهٔ نوشته (دقیقه).
 *
 * @param WP_Post|int|null $post نوشته.
 * @return int حداقل ۱ دقیقه.
 */
function evented_reading_time($post = null)
{
	$post = get_post($post);
	if (!$post instanceof WP_Post) {
		return 1;
	}

	$text  = wp_strip_all_tags((string) $post->post_content);
	$words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
	$count = is_array($words) ? count($words) : 0;

	/* سرعت مطالعهٔ فارسی: حدود ۱۸۰ کلمه در دقیقه */
	return max(1, (int) ceil($count / 180));
}

/**
 * تعداد بازدید نوشته.
 *
 * @param int $post_id شناسهٔ نوشته.
 * @return int
 */
function evented_get_post_views($post_id)
{
	return (int) get_post_meta((int) $post_id, 'evented_post_views', true);
}

/**
 * ثبت یک بازدید برای نوشته (فقط در سمت سایت، بدون شمارش مدیران و پیش‌نمایش).
 *
 * نکته: با کش صفحه (page cache) شمارش کمتر از واقع خواهد بود؛
 * برای آمار دقیق‌تر می‌تید از فیلتر/افزونهٔ آماری استفاده کنید.
 *
 * @param int $post_id شناسهٔ نوشته.
 * @return void
 */
function evented_track_post_view($post_id)
{
	$post_id = (int) $post_id;

	if (!$post_id || is_admin() || is_preview()) {
		return;
	}

	if (current_user_can('manage_options')) {
		return;
	}

	update_post_meta($post_id, 'evented_post_views', evented_get_post_views($post_id) + 1);
}

/**
 * فهرست لینک‌های اشتراک‌گذاری برای یک نوشته.
 *
 * فقط سرویس‌هایی که آدرس اشتراک‌گذاری وبِ مستند دارند اینجا آمده‌اند؛
 * با فیلتر `evented_share_links` می‌توان موارد دیگر (بله، روبیکا، سروش و…)
 * را افزود یا عوض کرد.
 *
 * @param string $url   آدرس صفحه.
 * @param string $title عنوان صفحه.
 * @return array[] هر مورد: {key, label, url, color}
 */
function evented_share_links($url, $title)
{
	$url   = (string) $url;
	$title = (string) $title;

	$links = array(
		array(
			'key'   => 'eitaa',
			'label' => 'ایتا',
			'url'   => 'https://eitaa.com/share/url?url=' . rawurlencode($url),
			'color' => '#f97316',
		),
		array(
			'key'   => 'telegram',
			'label' => 'تلگرام',
			'url'   => 'https://t.me/share/url?url=' . rawurlencode($url) . '&text=' . rawurlencode($title),
			'color' => '#0ea5e9',
		),
		array(
			'key'   => 'whatsapp',
			'label' => 'واتس‌اپ',
			'url'   => 'https://api.whatsapp.com/send?text=' . rawurlencode($title . ' ' . $url),
			'color' => '#22c55e',
		),
	);

	/**
	 * فیلتر لینک‌های اشتراک‌گذاری.
	 *
	 * @param array  $links فهرست لینک‌ها.
	 * @param string $url   آدرس صفحه.
	 * @param string $title عنوان صفحه.
	 */
	return (array) apply_filters('evented_share_links', $links, $url, $title);
}

/**
 * آدرس کانال‌های پیام‌رسان سایت (برای بلوک «ما را دنبال کنید»).
 *
 * @return array[] هر مورد: {key, label, url, color}
 */
function evented_channel_links()
{
	$id = (string) apply_filters('evented_channel_id', 'channel-id');

	$channels = array(
		array(
			'key'   => 'eitaa',
			'label' => 'ایتا',
			'url'   => 'https://eitaa.com/' . $id,
			'color' => '#f97316',
		),
		array(
			'key'   => 'bale',
			'label' => 'بله',
			'url'   => 'https://ble.ir/' . $id,
			'color' => '#3b82f6',
		),
		array(
			'key'   => 'rubika',
			'label' => 'روبیکا',
			'url'   => 'https://rubika.ir/' . $id,
			'color' => '#06b6d4',
		),
		array(
			'key'   => 'soroush',
			'label' => 'سروش',
			'url'   => 'https://splus.ir/' . $id,
			'color' => '#10b981',
		),
	);

	/**
	 * فیلتر کانال‌های پیام‌رسان.
	 *
	 * @param array $channels فهرست کانال‌ها.
	 */
	return (array) apply_filters('evented_channel_links', $channels);
}

/**
 * نوشته‌های مرتبط (هم‌دسته) برای انتهای تک‌نوشته.
 *
 * @param int $post_id  شناسهٔ نوشتهٔ جاری.
 * @param int $per_page تعداد.
 * @return WP_Post[]
 */
function evented_related_posts($post_id, $per_page = 3)
{
	$post_id  = (int) $post_id;
	$cat_ids  = wp_get_post_categories($post_id, array('fields' => 'ids'));
	$tag_ids  = wp_get_post_tags($post_id, array('fields' => 'ids'));

	$args = array(
		'post_type'           => 'post',
		'posts_per_page'      => (int) $per_page,
		'post__not_in'        => array($post_id),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	if (!empty($cat_ids) || !empty($tag_ids)) {
		$args['tax_query'] = array(
			'relation' => 'OR',
		);
		if (!empty($cat_ids)) {
			$args['tax_query'][] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $cat_ids,
			);
		}
		if (!empty($tag_ids)) {
			$args['tax_query'][] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => $tag_ids,
			);
		}
	}

	$query = new WP_Query($args);
	$posts = $query->posts;
	wp_reset_postdata();

	return is_array($posts) ? $posts : array();
}

/* =========================================================
   هلپرهای لرن‌دش (صفحهٔ دوره و درس) — پوستهٔ ee-*
   ========================================================= */

/**
 * قالب‌بندی مدت‌زمان برحسب ثانیه به متن فارسی.
 *
 * @param int $seconds ثانیه.
 * @return string
 */
function evented_format_duration($seconds)
{
	$seconds = (int) $seconds;
	if ($seconds <= 0) {
		return '';
	}

	$minutes = (int) floor($seconds / 60);
	$hours   = (int) floor($minutes / 60);
	$mins    = $minutes % 60;

	if ($hours > 0) {
		/* translators: 1: ساعت 2: دقیقه */
		return sprintf(__('%1$s:%2$s ساعت', 'evented-edu'), $hours, str_pad((string) $mins, 2, '0', STR_PAD_LEFT));
	}

	/* translators: %d: دقیقه */
	return sprintf(_n('%d دقیقه', '%d دقیقه', $minutes, 'evented-edu'), $minutes);
}

/**
 * فهرست گام‌های دوره (درس‌ها + فصل‌ها) — یک‌بار محاسبه و کش در همان درخواست.
 *
 * خروجی هر آیتم:
 *   id, title, permalink, duration, duration_text, is_sample, is_unlocked,
 *   is_completed, section_id, section_title, quizzes, index
 *
 * @param int $course_id شناسهٔ دوره.
 * @return array
 */
function evented_course_steps($course_id)
{
	$course_id = (int) $course_id;
	static $cache = array();

	if (isset($cache[$course_id])) {
		return $cache[$course_id];
	}

	$steps = array();

	if (!function_exists('learndash_get_course_lessons_list')) {
		$cache[$course_id] = $steps;
		return $steps;
	}

	$user_id    = get_current_user_id();
	$has_access = function_exists('sfwd_lms_has_access') ? (bool) sfwd_lms_has_access($course_id, $user_id) : false;
	$lessons    = learndash_get_course_lessons_list($course_id, $user_id);

	if (empty($lessons) || !is_array($lessons)) {
		$cache[$course_id] = $steps;
		return $steps;
	}

	$sections = function_exists('learndash_30_get_course_sections') ? (array) learndash_30_get_course_sections($course_id) : array();

	/* درس‌های تکمیل‌شدهٔ کاربر (یک کوئری به‌جای N کوئری) */
	$completed_ids = array();
	if ($user_id) {
		$progress_meta = get_user_meta($user_id, '_sfwd-course_progress', true);
		if (is_array($progress_meta) && isset($progress_meta[$course_id]['lessons']) && is_array($progress_meta[$course_id]['lessons'])) {
			$completed_ids = array_keys(array_filter($progress_meta[$course_id]['lessons']));
			$completed_ids = array_map('intval', $completed_ids);
		}
	}

	$current_section_id    = 0;
	$current_section_title = '';

	foreach ($lessons as $index => $lesson) {
		$lesson_post = isset($lesson['post']) ? $lesson['post'] : null;
		if (!$lesson_post instanceof WP_Post) {
			continue;
		}

		$lesson_id = (int) $lesson_post->ID;

		/* شروع فصل جدید؟ */
		if (isset($sections[$lesson_id])) {
			$section_value = (int) $sections[$lesson_id];
			if ($section_value && 'sfwd-topic' === get_post_type($section_value)) {
				$current_section_id    = $section_value;
				$current_section_title = get_the_title($section_value);
			} else {
				$current_section_id    = 0;
				$current_section_title = '';
			}
		}

		$duration = (int) get_post_meta($lesson_id, '_learndash_course_grid_duration', true);
		$is_sample = (function_exists('learndash_get_setting') && learndash_get_setting($lesson_id, 'sample_lesson') === 'on');

		/* تعداد آزمون‌های متصل به درس */
		$quiz_count = 0;
		if (function_exists('learndash_get_lesson_quiz_list')) {
			$lesson_quizzes = learndash_get_lesson_quiz_list($lesson_id, $user_id);
			$quiz_count     = is_array($lesson_quizzes) ? count($lesson_quizzes) : 0;
		}

		$steps[] = array(
			'id'            => $lesson_id,
			'title'         => get_the_title($lesson_id),
			'permalink'     => function_exists('learndash_course_get_step_permalink')
				? (string) learndash_course_get_step_permalink($lesson_id, $course_id)
				: (string) get_permalink($lesson_id),
			'duration'      => $duration,
			'duration_text' => evented_format_duration($duration),
			'is_sample'     => (bool) $is_sample,
			'is_unlocked'   => $has_access || $is_sample,
			'is_completed'  => in_array($lesson_id, $completed_ids, true),
			'section_id'    => $current_section_id,
			'section_title' => $current_section_title,
			'quizzes'       => $quiz_count,
			'index'         => count($steps),
		);
	}

	$cache[$course_id] = $steps;
	return $steps;
}

/**
 * درصد پیشرفت کاربر در دوره + تعداد گام‌های تکمیل‌شده.
 *
 * @param int $course_id شناسهٔ دوره.
 * @param int $user_id   شناسهٔ کاربر (۰ = کاربر جاری).
 * @return array {percentage, completed, total}
 */
function evented_course_progress($course_id, $user_id = 0)
{
	$course_id = (int) $course_id;
	$user_id   = $user_id ? (int) $user_id : get_current_user_id();

	$out = array(
		'percentage' => 0,
		'completed'  => 0,
		'total'      => 0,
	);

	if (!$user_id || !function_exists('learndash_course_progress')) {
		return $out;
	}

	$progress = learndash_course_progress(array(
		'user_id'   => $user_id,
		'course_id' => $course_id,
		'array'     => true,
	));

	if (is_array($progress)) {
		$out['percentage'] = isset($progress['percentage']) ? (int) $progress['percentage'] : 0;
		$out['completed']  = isset($progress['completed']) ? (int) $progress['completed'] : 0;
		$out['total']      = isset($progress['total']) ? (int) $progress['total'] : 0;
	}

	return $out;
}

/**
 * وضعیت قیمت/دسترسی دوره برای نمایش در کارت ثبت‌نام.
 *
 * @param int $course_id شناسهٔ دوره.
 * @return array {price_type, price, price_html, has_access}
 */
function evented_course_pricing($course_id)
{
	$course_id = (int) $course_id;
	$settings  = get_post_meta($course_id, '_sfwd-courses', true);
	$settings  = is_array($settings) ? $settings : array();

	$price_type = isset($settings['sfwd-courses_course_price_type']) ? $settings['sfwd-courses_course_price_type'] : 'open';
	$price      = isset($settings['sfwd-courses_course_price']) ? $settings['sfwd-courses_course_price'] : '';
	$user_id    = get_current_user_id();

	return array(
		'price_type' => $price_type,
		'price'      => $price,
		'is_free'    => ('free' === $price_type || '' === $price || null === $price),
		'has_access' => function_exists('sfwd_lms_has_access') ? (bool) sfwd_lms_has_access($course_id, $user_id) : false,
	);
}

/**
 * سایر دوره‌ها (هم‌دسته) برای سایدبار دوره.
 *
 * @param int $course_id شناسهٔ دورهٔ جاری.
 * @param int $limit     تعداد.
 * @return WP_Post[]
 */
function evented_related_courses($course_id, $limit = 8)
{
	$course_id = (int) $course_id;
	$terms     = function_exists('wp_get_post_terms') ? wp_get_post_terms($course_id, 'ld_course_category', array('fields' => 'ids')) : array();

	$args = array(
		'post_type'           => 'sfwd-courses',
		'posts_per_page'      => (int) $limit,
		'post__not_in'        => array($course_id),
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
	);

	if (!is_wp_error($terms) && !empty($terms)) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'ld_course_category',
				'field'    => 'term_id',
				'terms'    => $terms,
			),
		);
	}

	$query = new WP_Query($args);
	$posts = $query->posts;
	wp_reset_postdata();

	return is_array($posts) ? $posts : array();
}

/**
 * آخرین دوره‌ها برای سایدبار.
 *
 * @param int $limit تعداد.
 * @return WP_Post[]
 */
function evented_latest_courses($limit = 6)
{
	$query = new WP_Query(array(
		'post_type'           => 'sfwd-courses',
		'posts_per_page'      => (int) $limit,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	));

	$posts = $query->posts;
	wp_reset_postdata();

	return is_array($posts) ? $posts : array();
}

/**
 * مارک‌آپ پخش‌کنندهٔ ویدیو/صوت بدون oEmbed اضافی (پرفورمنس).
 *
 * فایل مستقیم → <video>/<audio> با preload="none"؛
 * در غیر این صورت iframe با loading="lazy".
 *
 * @param string $url    آدرس رسانه.
 * @param string $poster تصویر پوستر (اختیاری).
 * @return string HTML امن.
 */
function evented_media_player($url, $poster = '')
{
	$url = trim((string) $url);
	if ('' === $url) {
		return '';
	}

	if (preg_match('/\.(mp4|webm|ogg|ogv|m4v)(\?|#|$)/i', $url)) {
		return sprintf(
			'<video class="ee-video" controls preload="none" playsinline%s src="%s"></video>',
			$poster ? ' poster="' . esc_url($poster) . '"' : '',
			esc_url($url)
		);
	}

	if (preg_match('/\.(mp3|m4a|wav|aac)(\?|#|$)/i', $url)) {
		return sprintf('<audio class="ee-audio" controls preload="none" src="%s"></audio>', esc_url($url));
	}

	return sprintf(
		'<iframe class="ee-embed" src="%s" loading="lazy" title="%s" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>',
		esc_url($url),
		esc_attr__('پخش‌کنندهٔ ویدیو', 'evented-edu')
	);
}

/**
 * یافتن آزمون(های) متصل به یک درس.
 *
 * @param int $lesson_id شناسهٔ درس.
 * @return WP_Post[]
 */
function evented_lesson_quizzes($lesson_id)
{
	$lesson_id = (int) $lesson_id;

	if (!function_exists('learndash_get_lesson_quiz_list')) {
		return array();
	}

	$quizzes = learndash_get_lesson_quiz_list($lesson_id, get_current_user_id());
	if (!is_array($quizzes)) {
		return array();
	}

	$out = array();
	foreach ($quizzes as $quiz) {
		$quiz_post = isset($quiz['post']) ? $quiz['post'] : $quiz;
		if ($quiz_post instanceof WP_Post) {
			$out[] = $quiz_post;
		}
	}

	return $out;
}

/**
 * گام قبلی/بعدی یک درس در میان گام‌های دوره.
 *
 * @param int $course_id شناسهٔ دوره.
 * @param int $step_id   شناسهٔ گام جاری.
 * @return array {prev: WP_Post|null, next: WP_Post|null}
 */
function evented_adjacent_steps($course_id, $step_id)
{
	$course_id = (int) $course_id;
	$step_id   = (int) $step_id;
	$out       = array('prev' => null, 'next' => null);

	if (!function_exists('learndash_get_course_steps')) {
		return $out;
	}

	$steps = learndash_get_course_steps($course_id);
	if (empty($steps) || !is_array($steps)) {
		return $out;
	}

	$index = null;
	foreach ($steps as $i => $step) {
		$step_post = isset($step['post']) ? $step['post'] : $step;
		if ($step_post instanceof WP_Post && (int) $step_post->ID === $step_id) {
			$index = $i;
			break;
		}
	}

	if (null === $index) {
		return $out;
	}

	$pick = static function ($i) use ($steps) {
		if (!isset($steps[$i])) {
			return null;
		}
		$step_post = isset($steps[$i]['post']) ? $steps[$i]['post'] : $steps[$i];
		return $step_post instanceof WP_Post ? $step_post : null;
	};

	$out['prev'] = $pick($index - 1);
	$out['next'] = $pick($index + 1);

	return $out;
}

/**
 * رسانه‌های یک درس: ویدیو، صوت و فایل‌های پیوست.
 *
 * کلیدهای LearnDash و متاهای سفارشی قالب هر دو پشتیبانی می‌شوند؛
 * اگر هیچ‌کدام تنظیم نشده باشند آرایهٔ خالی برمی‌گردد و قالب آن بخش را چاپ نمی‌کند.
 *
 * @param int $lesson_id شناسهٔ درس.
 * @return array {video: string, poster: string, audio: array<int,string>, files: array<int,array>}
 */
function evented_lesson_media($lesson_id)
{
	$lesson_id = (int) $lesson_id;
	$settings  = get_post_meta($lesson_id, '_sfwd-lessons', true);
	$settings  = is_array($settings) ? $settings : array();

	$video = '';
	if (function_exists('learndash_get_setting')) {
		$video = (string) learndash_get_setting($lesson_id, 'lesson_video_url');
	}
	if ('' === $video && isset($settings['sfwd-lessons_lesson_video_url'])) {
		$video = (string) $settings['sfwd-lessons_lesson_video_url'];
	}

	$audio = array();
	$audio_raw = array(
		get_post_meta($lesson_id, '_lesson_audio', true),
		get_post_meta($lesson_id, '_lesson_audio_url', true),
		isset($settings['sfwd-lessons_lesson_audio_url']) ? $settings['sfwd-lessons_lesson_audio_url'] : '',
	);
	foreach ($audio_raw as $candidate) {
		$candidate = trim((string) $candidate);
		if ('' !== $candidate && !in_array($candidate, $audio, true)) {
			$audio[] = $candidate;
		}
	}

	$files = array();
	$attachments = get_post_meta($lesson_id, '_lesson_attachments', true);
	if (is_array($attachments)) {
		foreach ($attachments as $attachment_id) {
			$attachment_id = (int) $attachment_id;
			if (!$attachment_id) {
				continue;
			}
			$file_url = wp_get_attachment_url($attachment_id);
			if ($file_url) {
				$files[] = array(
					'url'   => (string) $file_url,
					'title' => get_the_title($attachment_id),
				);
			}
		}
	}

	return array(
		'video'  => $video,
		'poster' => has_post_thumbnail($lesson_id) ? (string) get_the_post_thumbnail_url($lesson_id, 'large') : '',
		'audio'  => $audio,
		'files'  => $files,
	);
}

/**
 * برچسب وضعیت یک گام (برای نوار وضعیت صفحهٔ درس).
 *
 * @param array $step   یک آیتم از خروجی evented_course_steps().
 * @param bool  $active آیا گام جاری است؟
 * @return array {class: string, label: string}
 */
function evented_step_state($step, $active = false)
{
	if (!is_array($step)) {
		return array('class' => 'is-locked', 'label' => __('قفل شده', 'evented-edu'));
	}

	if (!empty($step['is_completed'])) {
		return array('class' => 'is-complete', 'label' => __('تکمیل شده', 'evented-edu'));
	}

	if ($active) {
		return array('class' => 'is-active', 'label' => __('در حال مشاهده', 'evented-edu'));
	}

	if (empty($step['is_unlocked'])) {
		return array('class' => 'is-locked', 'label' => __('قفل شده', 'evented-edu'));
	}

	if (!empty($step['is_sample'])) {
		return array('class' => 'is-sample', 'label' => __('نمونهٔ رایگان', 'evented-edu'));
	}

	return array('class' => 'is-open', 'label' => __('در دسترس', 'evented-edu'));
}
