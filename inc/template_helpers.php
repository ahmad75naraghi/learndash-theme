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
