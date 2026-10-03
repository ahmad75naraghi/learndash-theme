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
 * آیا برگهٔ جاری صفحهٔ مستقل ورود است؟
 *
 * page-login.php HTML کامل خودش را چاپ می‌کند و پوستهٔ ee-* را نمی‌خواهد.
 * پنل کاربری (panel/*.php) از نسخهٔ ۲ روی همان پوستهٔ ee-* سوار است.
 *
 * @return bool
 */
function evented_is_standalone_page()
{
	if (!is_page()) {
		return false;
	}

	$template = (string) get_page_template_slug();
	if ('page-login.php' === $template) {
		return true;
	}

	$ee_page = get_queried_object();

	return $ee_page instanceof WP_Post && 'login' === (string) $ee_page->post_name;
}

/**
 * آیا صفحهٔ جاری از طراحی جدید «evented-edu» (کلاس‌های ee-*) استفاده می‌کند؟
 *
 * همهٔ نماهای عمومی سایت با پوستهٔ جدید رندر می‌شوند؛ تنها استثنا دو برگهٔ
 * مستقل بالا هستند. برای بارگذاری پوستهٔ مشترک (ee-shell.css) استفاده می‌شود.
 *
 * @return bool
 */
function evented_is_ee_view()
{
	if (is_admin()) {
		return false;
	}

	return !evented_is_standalone_page();
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
		$pt = get_query_var('post_type');
		if (is_array($pt)) {
			$pt = (string) reset($pt);
		}
		return (string) get_post_type_archive_link((string) $pt);
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
		// Eitaa (ایتا)
		array(
			'key'   => 'eitaa',
			'label' => 'ایتا',
			'url'   => 'https://eitaa.com/share/url?url=' . rawurlencode($url),
			'color' => '#f97316', // Orange
			'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" role="img"><path d="M5.968 23.942a6.624 6.624 0 0 1-2.332-.83c-1.62-.929-2.829-2.593-3.217-4.426-.151-.717-.17-1.623-.15-7.207C.288 5.47.274 5.78.56 4.79c.142-.493.537-1.34.823-1.767C2.438 1.453 3.99.445 5.913.08c.384-.073.94-.08 6.056-.08 6.251 0 6.045-.009 7.066.314a6.807 6.807 0 0 1 4.314 4.184c.33.937.346 1.087.369 3.555l.02 2.23-.391.268c-.558.381-1.29 1.06-2.316 2.15-1.182 1.256-2.376 2.42-2.982 2.907-1.309 1.051-2.508 1.651-3.726 1.864-.634.11-1.682.067-2.302-.095-.553-.144-.517-.168-.726.464a6.355 6.355 0 0 0-.318 1.546l-.031.407-.146-.03c-1.215-.241-2.419-1.285-2.884-2.5a3.583 3.583 0 0 1-.26-1.219l-.016-.34-.309-.284c-.644-.59-1.063-1.312-1.195-2.061-.212-1.193.34-2.542 1.538-3.756 1.264-1.283 3.127-2.29 4.953-2.68.658-.14 1.818-.177 2.403-.075 1.138.198 2.067.773 2.645 1.639.182.271.195.31.177.555a.812.812 0 0 1-.183.493c-.465.651-1.848 1.348-3.336 1.68-2.625.585-4.294-.142-4.033-1.759.026-.163.04-.304.031-.313-.032-.032-.293.104-.575.3-.479.334-.903.984-1.05 1.607-.036.156-.05.406-.034.65.02.331.053.454.192.736.092.186.275.45.408.589l.24.251-.096.122a4.845 4.845 0 0 0-.677 1.217 3.635 3.635 0 0 0-.105 1.815c.103.461.421 1.095.739 1.468.242.285.797.764.886.764.024 0 .044-.048.044-.106.001-.23.184-.973.326-1.327.423-1.058 1.351-1.96 2.82-2.74.245-.13.952-.47 1.572-.757 1.36-.63 2.103-1.015 2.511-1.305 1.176-.833 1.903-2.065 2.14-3.625.086-.57.086-1.634 0-2.207-.368-2.438-2.195-4.096-4.818-4.37-2.925-.307-6.648 1.953-8.942 5.427-1.116 1.69-1.87 3.565-2.187 5.443-.123.728-.169 2.08-.093 2.75.193 1.704.822 3.078 1.903 4.156a6.531 6.531 0 0 0 1.87 1.313c2.368 1.13 4.99 1.155 7.295.071.996-.469 1.974-1.196 3.023-2.25 1.02-1.025 1.71-1.88 3.592-4.458 1.04-1.423 1.864-2.368 2.272-2.605l.15-.086-.019 3.091c-.018 2.993-.022 3.107-.123 3.561-.6 2.678-2.54 4.636-5.195 5.242l-.468.107-5.775.01c-4.734.008-5.85-.002-6.19-.056z"/></svg>'
		),
		// Bale (بله)
		array(
			'key'   => 'bale',
			'label' => 'بله',
			'url'   => 'https://ble.ir/share?text=' . rawurlencode($title . ' ' . $url),
			'color' => '#10b981', // Green
			'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" role="img"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 3.01 1 4.28L2 22l5.72-1c1.27.64 2.74 1 4.28 1 5.52 0 10-4.48 10-10S17.52 2 12 2zm-1.16 13.5l-3.34-3.34 1.42-1.42 1.92 1.92 5.34-5.34 1.42 1.42-6.76 6.76z"/></svg>'
		),
		// Soroush (سروش)
		array(
			'key'   => 'soroush',
			'label' => 'سروش',
			'url'   => 'https://splus.ir/share?url=' . rawurlencode($url) . '&text=' . rawurlencode($title),
			'color' => '#3b82f6', // Blue
			'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" role="img"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>'
		),
		// Rubika (روبیکا)
		array(
			'key'   => 'rubika',
			'label' => 'روبیکا',
			'url'   => 'https://rubika.ir/share?text=' . rawurlencode($title . ' ' . $url),
			'color' => '#ec4899', // Pink / Magenta
			'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" role="img"><path d="M18.69 5.88c-2.4 0-4.72 1.52-6.69 4.38-1.97-2.86-4.29-4.38-6.69-4.38-3.07 0-5.31 2.37-5.31 5.5S2.24 16.88 5.31 16.88c2.4 0 4.72-1.52 6.69-4.38 1.97 2.86 4.29 4.38 6.69 4.38 3.07 0 5.31-2.37 5.31-5.5s-2.24-5.5-5.31-5.5zm0 9c-2.06 0-3.95-1.58-5.38-3.5 1.43-1.92 3.32-3.5 5.38-3.5 1.96 0 3.31 1.57 3.31 3.5s-1.35 3.5-3.31 3.5zm-13.38 0c-1.96 0-3.31-1.57-3.31-3.5s1.35-3.5 3.31-3.5c2.06 0 3.95 1.58 5.38 3.5-1.43 1.92-3.32 3.5-5.38 3.5z"/></svg>'
		),
	);

	/* آی‌گپ share URL عمومی ندارد؛ در بلوک اشتراک به کانال تنظیم‌شدهٔ سایت می‌رود. */
	$igap_url = 'https://profile.igap.net/';
	foreach (evented_channel_links() as $channel) {
		if ('igap' === ($channel['key'] ?? '')) { $igap_url = (string) $channel['url']; break; }
	}
	$links[] = array('key' => 'igap', 'label' => 'آی‌گپ', 'url' => $igap_url, 'color' => '#84cc16', 'svg' => '');
	/* همهٔ محل‌های اشتراک از asset واقعی و محلی برند استفاده می‌کنند. */
	foreach ($links as &$link) {
		$link['svg'] = evented_channel_icon_html($link, 'ee-channel-icon-share');
	}
	unset($link);

	/**
	 * Filters the share links array.
	 *
	 * @param array  $links The list of share links.
	 * @param string $url   The URL to share.
	 * @param string $title The title to share.
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
			'key'   => 'igap',
			'label' => 'آی‌گپ',
			'url'   => 'https://profile.igap.net/' . $id,
			'color' => '#84cc16',
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
 * نشان محلی و واقعی پیام‌رسان را برای هدر، فوتر و سایدبار می‌سازد.
 *
 * @param array  $channel دادهٔ خروجی evented_channel_links().
 * @param string $class   کلاس اختیاری wrapper.
 * @return string HTML امن یا رشتهٔ خالی برای کلید ناشناخته.
 */
function evented_channel_icon_html($channel, $class = '')
{
	$key = isset($channel['key']) ? sanitize_key($channel['key']) : '';
	$assets = array(
		'eitaa'   => 'eitaa.svg',
		'bale'    => 'bale.svg',
		'rubika'  => 'rubika.svg',
		'igap'    => 'igap.png',
		'soroush' => 'soroush.svg',
	);
	$classes = trim('ee-channel-icon ee-channel-icon-' . ($key ? $key : 'generic') . ' ' . sanitize_html_class($class));
	$color   = isset($channel['color']) ? sanitize_hex_color($channel['color']) : '';
	$style   = $color ? ' style="--ee-channel-color:' . esc_attr($color) . '"' : '';
	if (!isset($assets[$key])) {
		$label = isset($channel['label']) ? (string) $channel['label'] : '';
		$first = function_exists('mb_substr') ? mb_substr($label, 0, 1) : substr($label, 0, 1);
		return '<span class="' . esc_attr($classes . ' ee-channel-icon-fallback') . '"' . $style . ' aria-hidden="true">' . esc_html($first) . '</span>';
	}

	$src = get_template_directory_uri() . '/assets/images/social/' . $assets[$key];
	return '<span class="' . esc_attr($classes) . '"' . $style . ' aria-hidden="true">'
		. '<img src="' . esc_url($src) . '" alt="" width="20" height="20" decoding="async">'
		. '</span>';
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
		/* learndash_get_course_steps() ممکن است آرایه (با کلید post) یا خود WP_Post برگرداند */
		$step_post = is_array($step) ? (isset($step['post']) ? $step['post'] : null) : $step;
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
		$step      = $steps[$i];
		$step_post = is_array($step) ? (isset($step['post']) ? $step['post'] : null) : $step;

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

/**
 * مارک‌آپ لوگو: لوگوی انتخاب‌شده در «سفارشی‌سازی › هویت سایت» وردپرس،
 * و در نبود آن نام سایت (تا هدر/فوتر هیچ‌وقت خالی نمانند).
 *
 * @param string $context فقط برای فیلتر (header|footer).
 * @return string HTML امن.
 */
function evented_logo_html($context = 'header')
{
	$site_name = (string) get_bloginfo('name');
	$logo_id   = function_exists('get_theme_mod') ? (int) get_theme_mod('custom_logo') : 0;
	$html      = '';

	if ($logo_id && wp_get_attachment_image_url($logo_id, 'full')) {
		$html = (string) wp_get_attachment_image($logo_id, 'full', false, array(
			'class'    => 'ee-logo-img',
			'alt'      => $site_name ? $site_name : __('لوگوی سایت', 'evented-edu'),
			'loading'  => 'header' === $context ? 'eager' : 'lazy',
			'decoding' => 'async',
		));
	}

	if ('' === $html) {
		$html = '<span class="logo-fallback"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-school"></use></svg> '
			. '<span class="ee-logo-text">' . esc_html($site_name ? $site_name : 'evented-edu') . '</span></span>';
	}

	/**
	 * مارک‌آپ لوگو را قابل بازنویسی می‌کند.
	 *
	 * @param string $html    مارک‌آپ لوگو.
	 * @param string $context محل استفاده (header|footer).
	 */
	return (string) apply_filters('evented_logo_html', $html, $context);
}

/**
 * نوشته‌های ویژه برای بخش «بلاگ ویژه» صفحهٔ اصلی.
 *
 * اولویت: نوشته‌های چسبان (Sticky) وردپرس، و در ادامه آخرین نوشته‌ها تا رسیدن
 * به تعداد خواسته‌شده. اگر هیچ نوشته‌ای نباشد آرایهٔ خالی برمی‌گردد و قالب
 * بخش را چاپ نمی‌کند.
 *
 * @param int $limit حداکثر تعداد نوشته.
 * @return WP_Post[]
 */
function evented_featured_posts($limit = 4)
{
	$limit = max(1, (int) $limit);
	$ids   = array();

	$sticky = get_option('sticky_posts');
	if (is_array($sticky)) {
		$sticky = array_filter(array_map('absint', $sticky));
		$ids    = array_slice(array_values($sticky), 0, $limit);
	}

	$need = $limit - count($ids);
	if ($need > 0) {
		$query = new WP_Query(array(
			'post_type'           => 'post',
			'posts_per_page'      => $need,
			'post__not_in'        => $ids,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
		));

		foreach ($query->posts as $ee_fp) {
			if ($ee_fp instanceof WP_Post) {
				$ids[] = (int) $ee_fp->ID;
			}
		}

		wp_reset_postdata();
	}

	$posts = array();
	foreach (array_slice($ids, 0, $limit) as $ee_id) {
		$ee_post = get_post($ee_id);
		if ($ee_post instanceof WP_Post) {
			$posts[] = $ee_post;
		}
	}

	/**
	 * فهرست نوشته‌های ویژهٔ صفحهٔ اصلی.
	 *
	 * @param WP_Post[] $posts نوشته‌ها.
	 * @param int       $limit تعداد درخواستی.
	 */
	return apply_filters('evented_featured_posts', $posts, $limit);
}

/* =========================================================================
 * بخش LMS — کارت دوره، بایگانی دوره‌ها، اساتید و آزمون
 * ========================================================================= */

/**
 * دادهٔ یک کارت دوره (برای گرید دوره‌ها در بایگانی/دسته/مدرس/برگه‌ها).
 *
 * @param int|WP_Post $course دوره.
 * @return array
 */
function evented_course_card_data($course)
{
	$course = $course instanceof WP_Post ? $course : get_post($course);
	if (!$course instanceof WP_Post) {
		return array();
	}

	$id    = (int) $course->ID;
	$terms = get_the_terms($id, 'ld_course_category');
	$term  = (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : null;

	$pricing = function_exists('evented_course_pricing') ? evented_course_pricing($id) : array('is_free' => true, 'price' => '', 'has_access' => false);

	$lesson_count = 0;
	if (function_exists('learndash_get_course_lessons_list')) {
		$lessons      = learndash_get_course_lessons_list($id, get_current_user_id());
		$lesson_count = is_array($lessons) ? count($lessons) : 0;
	}

	return array(
		'id'           => $id,
		'url'          => (string) get_permalink($id),
		'title'        => get_the_title($id),
		'thumb'        => has_post_thumbnail($id) ? (string) get_the_post_thumbnail_url($id, 'medium_large') : '',
		'cat_name'     => $term instanceof WP_Term ? (string) $term->name : '',
		'cat_url'      => $term instanceof WP_Term ? (string) get_term_link($term) : '',
		'date'         => function_exists('evented_post_date') ? evented_post_date($id) : get_the_date('', $id),
		'level'        => (string) get_post_meta($id, '_course_level', true),
		'duration'     => (string) get_post_meta($id, '_total_duration', true),
		'lesson_count' => $lesson_count,
		'views'        => function_exists('evented_get_post_views') ? evented_get_post_views($id) : 0,
		'is_free'      => !empty($pricing['is_free']),
		'price'        => isset($pricing['price']) ? $pricing['price'] : '',
		'has_access'   => !empty($pricing['has_access']),
		'instructor'   => (string) get_the_author_meta('display_name', (int) $course->post_author),
	);
}

/**
 * کوئری دوره‌ها با مقدارهای پیش‌فرض (بدون شمارش کل، بدون sticky).
 *
 * @param array $args آرگومان‌های WP_Query.
 * @return WP_Query
 */
function evented_courses_query($args = array())
{
	$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));

	$defaults = array(
		'post_type'           => 'sfwd-courses',
		'post_status'         => 'publish',
		'posts_per_page'      => 12,
		'paged'               => $paged,
		'ignore_sticky_posts' => true,
	);

	return new WP_Query((array) apply_filters('evented_courses_query_args', array_merge($defaults, (array) $args)));
}

/**
 * صفحه‌بندی با paginate_links (RTL و کلاس‌های ee-*) — نسخهٔ ۲:
 * قبلی/بعدی با برچسب، «صفحهٔ X از Y»، در موبایل فقط قبلی/بعدی + شماره.
 *
 * @param WP_Query $query کوئری جاری.
 * @return string
 */
function evented_pagination($query)
{
	if (!$query instanceof WP_Query) {
		return '';
	}

	$total = (int) $query->max_num_pages;
	if ($total < 2) {
		return '';
	}

	$current = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
	$fa      = function_exists('evented_fa_digits') ? 'evented_fa_digits' : 'strval';

	$links = paginate_links(array(
		'total'     => $total,
		'current'   => $current,
		'type'      => 'array',
		'mid_size'  => 1,
		'end_size'  => 1,
		'prev_next' => false,
	));

	if (empty($links)) {
		return '';
	}

	$prev = $current > 1 ? get_pagenum_link($current - 1) : '';
	$next = $current < $total ? get_pagenum_link($current + 1) : '';

	$out  = '<nav class="ee-pager v2" aria-label="' . esc_attr__('صفحه‌بندی', 'evented-edu') . '">';
	$out .= $prev
		? '<a class="ee-pager-btn is-prev" href="' . esc_url($prev) . '" rel="prev"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_forward"></use></svg><span>' . esc_html__('قبلی', 'evented-edu') . '</span></a>'
		: '<span class="ee-pager-btn is-prev is-off" aria-disabled="true"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_forward"></use></svg><span>' . esc_html__('قبلی', 'evented-edu') . '</span></span>';
	$out .= '<div class="ee-pager-nums">';
	foreach ($links as $link) {
		$out .= str_replace('page-numbers', 'ee-page-num', (string) $link);
	}
	$out .= '</div>';
	/* translators: 1: صفحهٔ فعلی 2: تعداد صفحات */
	$out .= '<span class="ee-pager-of">' . esc_html(sprintf(__('صفحهٔ %1$s از %2$s', 'evented-edu'), $fa($current), $fa($total))) . '</span>';
	$out .= $next
		? '<a class="ee-pager-btn is-next" href="' . esc_url($next) . '" rel="next"><span>' . esc_html__('بعدی', 'evented-edu') . '</span><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg></a>'
		: '<span class="ee-pager-btn is-next is-off" aria-disabled="true"><span>' . esc_html__('بعدی', 'evented-edu') . '</span><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg></span>';
	$out .= '</nav>';

	return $out;
}

/**
 * حالت خالی یکدست (تصویر + عنوان + توضیح + دکمه‌ها).
 *
 * @param array $args {
 *   @type string $title   عنوان.
 *   @type string $text    توضیح.
 *   @type string $icon    نام آیکن اسپرایت (پیش‌فرض search_off).
 *   @type array  $actions آرایه‌ای از ['label'=>..., 'url'=>..., 'primary'=>bool].
 *   @type string $class   کلاس اضافه.
 * }
 * @return string
 */
function evented_empty_state($args = array())
{
	$a = wp_parse_args($args, array(
		'title'   => __('چیزی پیدا نشد', 'evented-edu'),
		'text'    => '',
		'icon'    => 'search_off',
		'actions' => array(),
		'class'   => '',
	));
	$out  = '<div class="ee-empty-state ' . esc_attr($a['class']) . '">';
	$out .= '<span class="ee-es-art" aria-hidden="true">';
	$out .= '<svg class="ee-es-bg" viewBox="0 0 200 120" fill="none"><ellipse cx="100" cy="106" rx="78" ry="8" fill="#E0F2F1"/><circle cx="46" cy="38" r="10" fill="#F3E8FF"/><circle cx="160" cy="26" r="6" fill="#FFEDD5"/><circle cx="172" cy="70" r="4" fill="#D1FAE5"/><path d="M28 78c10-24 40-32 66-24" stroke="#B2DFDB" stroke-width="3" stroke-linecap="round" stroke-dasharray="2 8"/><rect x="62" y="30" width="76" height="60" rx="14" fill="#fff" stroke="#B2DFDB" stroke-width="2.5"/><rect x="76" y="46" width="48" height="6" rx="3" fill="#E2E8F0"/><rect x="76" y="60" width="34" height="6" rx="3" fill="#E2E8F0"/><rect x="76" y="74" width="20" height="6" rx="3" fill="#E2E8F0"/></svg>';
	$out .= '<span class="ee-es-ic">' . ee_icon($a['icon']) . '</span></span>'; // phpcs:ignore
	$out .= '<h3 class="ee-es-title">' . esc_html($a['title']) . '</h3>';
	if ('' !== $a['text']) {
		$out .= '<p class="ee-es-text">' . esc_html($a['text']) . '</p>';
	}
	if (!empty($a['actions'])) {
		$out .= '<div class="ee-es-actions">';
		foreach ((array) $a['actions'] as $act) {
			if (empty($act['url']) || empty($act['label'])) {
				continue;
			}
			$out .= '<a class="ee-btn ' . (!empty($act['primary']) ? 'ee-btn-primary' : 'ee-btn-ghost') . '" href="' . esc_url($act['url']) . '">' . esc_html($act['label']) . '</a>';
		}
		$out .= '</div>';
	}
	$out .= '</div>';
	return $out;
}

/**
 * استخراج URLها از مقدار متا (scalar، آرایه یا آبجکت).
 *
 * @param mixed $value مقدار متا.
 * @return string[]
 */
function evented_resource_meta_urls($value)
{
	$urls = array();
	if (is_array($value) || is_object($value)) {
		foreach ((array) $value as $item) {
			$urls = array_merge($urls, evented_resource_meta_urls($item));
		}
		return array_values(array_unique($urls));
	}
	if (!is_scalar($value) || is_bool($value)) {
		return $urls;
	}
	$text = str_replace('\\/', '/', html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8'));
	if (preg_match_all('~https?://[^\s<>"\']+~iu', $text, $matches)) {
		foreach ($matches[0] as $url) {
			$url = rtrim($url, '.,;:!?)]}');
			if (wp_http_validate_url($url)) {
				$urls[] = esc_url_raw($url);
			}
		}
	}
	return array_values(array_unique($urls));
}

/**
 * پلی‌لیست ویدئوی قدیمی را از دادهٔ Elementor یا متای ساختاریافته بازیابی می‌کند.
 *
 * ویجت Video Playlist المنتور فایل واقعی را داخل settings.tabs نگه می‌دارد؛
 * بنابراین اجرای Elementor برای حفظ و نمایش داده‌های قبلی لازم نیست.
 *
 * @param int $post_id شناسهٔ ویدئو.
 * @return array<int,array{title:string,description:string,url:string,poster:string,thumbnail_id:int,direct:bool}>
 */
function evented_clip_playlist($post_id)
{
	$items = array();
	$add   = static function ($row) use (&$items) {
		if (!is_array($row)) {
			return;
		}
		$external = isset($row['external_url']) && is_array($row['external_url']) ? $row['external_url'] : array();
		$hosted   = isset($row['hosted_url']) && is_array($row['hosted_url']) ? $row['hosted_url'] : array();
		$url      = (string) ($external['url'] ?? $hosted['url'] ?? $row['video_url'] ?? $row['url'] ?? '');
		if ('' === $url) {
			$type = isset($row['type']) ? sanitize_key($row['type']) : '';
			$url  = 'youtube' === $type ? (string) ($row['youtube_url'] ?? '') : ('vimeo' === $type ? (string) ($row['vimeo_url'] ?? '') : '');
		}
		$url = esc_url_raw(str_replace('\\/', '/', html_entity_decode(trim($url), ENT_QUOTES, 'UTF-8')));
		if (!in_array((string) wp_parse_url($url, PHP_URL_SCHEME), array('http', 'https'), true) || !wp_parse_url($url, PHP_URL_HOST)) {
			return;
		}
		$thumbnail = isset($row['thumbnail']) && is_array($row['thumbnail']) ? $row['thumbnail'] : array();
		$poster    = esc_url_raw((string) ($thumbnail['url'] ?? $row['thumbnail_url'] ?? $row['poster'] ?? ''));
		if (!empty($row['thumbnail_id'])) {
			$attachment_poster = wp_get_attachment_image_url(absint($row['thumbnail_id']), 'medium_large');
			$poster = $attachment_poster ?: $poster;
		}
		$path      = (string) wp_parse_url($url, PHP_URL_PATH);
		$items[]   = array(
			'title'       => sanitize_text_field((string) ($row['title'] ?? '')),
			'description' => sanitize_textarea_field((string) ($row['description'] ?? '')),
			'url'         => $url,
			'poster'      => $poster,
			'thumbnail_id'=> absint($thumbnail['id'] ?? $row['thumbnail_id'] ?? 0),
			'direct'      => (bool) preg_match('/\.(?:mp4|webm|ogv|ogg|m3u8)$/i', $path),
		);
	};
	$walk = static function ($node) use (&$walk, $add) {
		if (!is_array($node)) {
			return;
		}
		if (isset($node['tabs']) && is_array($node['tabs'])) {
			foreach ($node['tabs'] as $tab) {
				$add($tab);
			}
		}
		foreach ($node as $child) {
			if (is_array($child)) {
				$walk($child);
			}
		}
	};

	$canonical = defined('EVENTED_VIDEO_PLAYLIST_META') ? get_post_meta((int) $post_id, EVENTED_VIDEO_PLAYLIST_META, true) : array();
	if (is_array($canonical) && !empty($canonical)) {
		foreach ($canonical as $row) {
			$add($row);
		}
	} else {
		foreach ((array) get_post_meta((int) $post_id, '_elementor_data', false) as $raw) {
			$data = is_string($raw) ? json_decode($raw, true) : $raw;
			if (is_array($data)) {
				$walk($data);
			}
		}
		foreach ((array) get_post_meta((int) $post_id, 'clip_playlist', true) as $row) {
			$add($row);
		}
		/* آخرین fallback: URLهای مستقیم پراکنده نیز به یک پلی‌لیست استاندارد تبدیل‌پذیرند. */
		if (!$items && function_exists('evented_resource_public_meta')) {
			$legacy_urls = array();
			foreach (evented_resource_public_meta((int) $post_id) as $meta_row) {
				$legacy_urls = array_merge($legacy_urls, (array) $meta_row['urls']);
			}
			$legacy_urls = array_values(array_unique($legacy_urls));
			$direct_index = 0;
			foreach ($legacy_urls as $legacy_url) {
				$legacy_path = (string) wp_parse_url($legacy_url, PHP_URL_PATH);
				if (!preg_match('/\.(?:mp4|webm|ogv|ogg|m3u8)$/i', $legacy_path)) {
					continue;
				}
				$add(array(
					'title' => 0 === $direct_index ? get_the_title((int) $post_id) : sprintf(__('قسمت %s', 'evented-edu'), number_format_i18n($direct_index + 1)),
					'url' => $legacy_url,
					'thumbnail_id' => get_post_thumbnail_id((int) $post_id),
				));
				$direct_index++;
			}
		}
	}
	foreach ($items as $index => &$item) {
		if ('' === $item['title']) {
			$item['title'] = sprintf(__('قسمت %s', 'evented-edu'), number_format_i18n($index + 1));
		}
	}
	unset($item);

	return (array) apply_filters('evented_clip_playlist', $items, (int) $post_id);
}

/**
 * متاهای قابل نمایش عمومی یک منبع را برمی‌گرداند.
 *
 * کلیدهای دارای نشانهٔ رمز، توکن، نشست یا اطلاعات تماس هرگز عمومی نمی‌شوند؛
 * بقیهٔ کلیدها (از جمله کلیدهای قدیمی افزونهٔ ویدئو) برای بازیابی داده حفظ می‌شوند.
 *
 * @param int $post_id شناسه نوشته.
 * @return array<int, array{key:string,values:array,urls:array}>
 */
function evented_resource_public_meta($post_id)
{
	$post_id = (int) $post_id;
	$all     = get_post_meta($post_id);
	$output  = array();
	$blocked = '/(?:pass(?:word|wd)?|secret|token|nonce|api[_-]?key|license|credential|session|cookie|e-?mail|phone|mobile|auth)/i';
	$internal = '/^(?:_edit_|_wp_page_template$|_elementor_|_yoast_wpseo_|_astra_|_thumbnail_id$|_course_rating_|classic-editor-remember$|site-(?:sidebar-layout|content-layout|post-title)$|theme-transparent-header-meta$|stick-header-meta$|ast-|ekit_post_views_count$)/i';

	foreach (array_keys((array) $all) as $key) {
		$key = (string) $key;
		if ('' === $key || preg_match($blocked, $key) || preg_match($internal, $key) || 0 === strpos($key, '_oembed_')) {
			continue;
		}
		$values = get_post_meta($post_id, $key, false);
		$values = array_values(array_filter((array) $values, static function ($value) {
			return !(null === $value || '' === $value || array() === $value);
		}));
		if (empty($values)) {
			continue;
		}
		$urls = array();
		foreach ($values as $value) {
			$urls = array_merge($urls, evented_resource_meta_urls($value));
		}
		$output[] = array('key' => $key, 'values' => $values, 'urls' => array_values(array_unique($urls)));
	}

	return (array) apply_filters('evented_resource_public_meta', $output, $post_id);
}

/**
 * تبدیل امن مقدار متا به HTML خوانا؛ هیچ HTML ذخیره‌شده‌ای اجرا نمی‌شود.
 *
 * @param mixed $value مقدار متا.
 * @param int   $depth عمق آرایه.
 * @return string
 */
function evented_resource_meta_value_html($value, $depth = 0)
{
	$value = maybe_unserialize($value);
	if ((is_array($value) || is_object($value)) && $depth < 5) {
		$items = '';
		foreach ((array) $value as $key => $item) {
			$label = is_int($key) ? '' : '<strong>' . esc_html((string) $key) . '</strong>';
			$items .= '<li>' . $label . evented_resource_meta_value_html($item, $depth + 1) . '</li>';
		}
		return '<ul class="ee-resource-meta-list">' . $items . '</ul>';
	}
	if (is_bool($value)) {
		return '<span>' . ($value ? esc_html__('بله', 'evented-edu') : esc_html__('خیر', 'evented-edu')) . '</span>';
	}
	if (!is_scalar($value)) {
		return '<span>—</span>';
	}
	$text = (string) $value;
	if (function_exists('mb_strlen') && mb_strlen($text) > 4000) {
		$text = mb_substr($text, 0, 4000) . '…';
	} elseif (strlen($text) > 4000) {
		$text = substr($text, 0, 4000) . '…';
	}
	if (wp_http_validate_url($text)) {
		return '<a href="' . esc_url($text) . '" target="_blank" rel="noopener nofollow">' . esc_html($text) . '</a>';
	}
	return '<span>' . nl2br(esc_html($text)) . '</span>';
}

/**
 * نوار ابزار بالای فهرست نتایج: «نمایش X دوره» + مرتب‌سازی جمع‌وجور.
 *
 * @param int    $total تعداد کل.
 * @param string $unit  واحد (دوره/نوشته).
 * @return string
 */
function evented_results_toolbar($total, $unit = '')
{
	$fa   = function_exists('evented_fa_digits') ? 'evented_fa_digits' : 'strval';
	$unit = '' !== $unit ? $unit : __('دوره', 'evented-edu');
	$out  = '<div class="ee-rtb">';
	/* translators: 1: تعداد 2: واحد */
	$out .= '<span class="ee-rtb-n">' . ee_icon('grid_view') . ' ' . esc_html(sprintf(__('نمایش %1$s %2$s', 'evented-edu'), $fa((int) $total), $unit)) . '</span>'; // phpcs:ignore
	if (function_exists('evented_course_filter_defs')) {
		$defs = evented_course_filter_defs();
		$vals = evented_course_filter_values();
		if (isset($defs['orderby'])) {
			$out .= '<form class="ee-rtb-sort" method="get" action="' . esc_url(function_exists('evented_course_filter_base_url') ? evented_course_filter_base_url() : '') . '" data-ee-rtb-sort>';
			foreach ($vals as $k => $v) {
				if ('orderby' !== $k && '' !== $v) {
					$out .= '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr($v) . '">';
				}
			}
			if ('' !== get_search_query()) {
				$out .= '<input type="hidden" name="s" value="' . esc_attr(get_search_query()) . '">';
			}
			$out .= '<label>' . ee_icon('sort') . '<span class="screen-reader-text">' . esc_html__('مرتب‌سازی', 'evented-edu') . '</span><select name="orderby">'; // phpcs:ignore
			foreach ($defs['orderby']['options'] as $ok => $ol) {
				$out .= '<option value="' . esc_attr($ok) . '"' . selected($vals['orderby'], $ok, false) . '>' . esc_html($ol) . '</option>';
			}
			$out .= '</select>' . ee_icon('expand_more') . '</label></form>'; // phpcs:ignore
		}
	}
	$out .= '</div>';
	return $out;
}

/**
 * تصویر یک دستهٔ دوره (کلید `ld_cat_image_id` در term-meta).
 *
 * @param int $term_id شناسهٔ دسته.
 * @param string $size سایز تصویر.
 * @return string
 */
function evented_term_image($term_id, $size = 'medium_large')
{
	$image_id = (int) get_term_meta((int) $term_id, 'ld_cat_image_id', true);
	if (!$image_id) {
		return '';
	}

	$url = wp_get_attachment_image_url($image_id, $size);
	return $url ? (string) $url : '';
}

/**
 * دادهٔ یک مدرس (کاربر با نقش group_leader).
 *
 * @param int|WP_User $user کاربر.
 * @return array
 */
function evented_instructor_data($user)
{
	$user_id = is_object($user) ? (int) $user->ID : (int) $user;
	if (!$user_id) {
		return array();
	}

	$cache_key = 'evented_instructor_' . $user_id;
	$cached    = get_transient($cache_key);
	if (is_array($cached)) {
		return $cached;
	}

	$course_count = function_exists('count_user_posts') ? (int) count_user_posts($user_id, 'sfwd-courses') : 0;

	/* تعداد دانشجویان یکتا در همهٔ دوره‌های این مدرس */
	$students = 0;
	if (function_exists('learndash_get_users_for_course')) {
		$course_ids = get_posts(array(
			'post_type'      => 'sfwd-courses',
			'author'         => $user_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		));

		if (!empty($course_ids)) {
			$unique = array();
			foreach ($course_ids as $course_id) {
				$user_query = learndash_get_users_for_course((int) $course_id, array('fields' => 'ID'), false);
				if ($user_query instanceof WP_User_Query) {
					foreach ((array) $user_query->get_results() as $student) {
						$student_id             = is_object($student) ? (int) $student->ID : (int) $student;
						$unique[$student_id]    = true;
					}
				}
			}
			$students = count($unique);
		}
	}

	$data = array(
		'id'           => $user_id,
		'name'         => (string) get_the_author_meta('display_name', $user_id),
		'bio'          => (string) get_the_author_meta('description', $user_id),
		'about'        => (string) get_the_author_meta('about_teacher', $user_id),
		'avatar'       => function_exists('get_avatar_url') ? (string) get_avatar_url($user_id, array('size' => 160)) : '',
		'url'          => (string) get_author_posts_url($user_id),
		'course_count' => $course_count,
		'students'     => $students,
		'rating'       => (string) get_user_meta($user_id, 'instructor_rating_average', true),
		'social'       => array(
			'youtube'   => (string) get_user_meta($user_id, 'youtube', true),
			'linkedin'  => (string) get_user_meta($user_id, 'linkedin', true),
			'instagram' => (string) get_user_meta($user_id, 'instagram', true),
			'facebook'  => (string) get_user_meta($user_id, 'facebook', true),
		),
	);

	set_transient($cache_key, $data, 15 * MINUTE_IN_SECONDS);
	return $data;
}

/** پاک‌سازی کش کارت/آمار مدرس در تغییرات مرتبط. */
function evented_instructor_cache_flush($user_id)
{
	$user_id = (int) $user_id;
	if ($user_id > 0) {
		delete_transient('evented_instructor_' . $user_id);
	}
}
add_action('profile_update', 'evented_instructor_cache_flush');
add_action('save_post_sfwd-courses', static function ($post_id) {
	evented_instructor_cache_flush((int) get_post_field('post_author', $post_id));
}, 20);
add_action('learndash_update_course_access', static function ($user_id, $course_id) {
	evented_instructor_cache_flush((int) get_post_field('post_author', $course_id));
}, 20, 2);

/**
 * فهرست اساتید (نقش group_leader) با صفحه‌بندی.
 *
 * @param array $args {per_page, paged, orderby, order}.
 * @return array {items: array, total: int, pages: int, current: int}
 */
function evented_instructors($args = array())
{
	$args = wp_parse_args($args, array(
		'per_page' => 8,
		'paged'    => 1,
		'orderby'  => 'display_name',
		'order'    => 'ASC',
	));

	if (!class_exists('WP_User_Query')) {
		return array('items' => array(), 'total' => 0, 'pages' => 0, 'current' => 1);
	}

	$per_page = max(1, (int) $args['per_page']);
	$paged    = max(1, (int) $args['paged']);

	$query = new WP_User_Query(array(
		'role'    => 'group_leader',
		'number'  => $per_page,
		'offset'  => ($paged - 1) * $per_page,
		'orderby' => $args['orderby'],
		'order'   => $args['order'],
		'count_total' => true,
	));

	$total = (int) $query->get_total();
	$items = array();
	foreach ((array) $query->get_results() as $user) {
		$data = evented_instructor_data($user);
		if (!empty($data)) {
			$items[] = $data;
		}
	}

	return array(
		'items'   => $items,
		'total'   => $total,
		'pages'   => (int) ceil($total / $per_page),
		'current' => $paged,
	);
}

/**
 * دادهٔ یک آزمون لرن‌دش (محدودیت زمانی، تعداد دفعات، درصد قبولی، گواهینامه).
 *
 * @param int $quiz_id شناسهٔ آزمون.
 * @return array
 */
function evented_quiz_data($quiz_id)
{
	$quiz_id = (int) $quiz_id;
	$settings = get_post_meta($quiz_id, '_sfwd-quiz', true);
	$settings = is_array($settings) ? $settings : array();

	$get = static function ($key) use ($settings, $quiz_id) {
		$value = null;
		if (function_exists('learndash_get_setting')) {
			$value = learndash_get_setting($quiz_id, $key);
		}
		if (null === $value || '' === $value) {
			$full  = 'sfwd-quiz_' . $key;
			$value = isset($settings[$full]) ? $settings[$full] : '';
		}
		return $value;
	};

	$question_count = 0;
	if (function_exists('learndash_get_quiz_questions')) {
		$questions      = learndash_get_quiz_questions($quiz_id, array(), true);
		$question_count = is_array($questions) ? count($questions) : (int) $questions;
	}

	return array(
		'time_limit'   => (int) $get('quiz_pro_time_limit'),
		'attempts'     => (int) $get('quiz_pro_limit_times'),
		'passing'      => (int) $get('quiz_pro_passing_percentage'),
		'certificate'  => (int) $get('quiz_pro_certificate'),
		'questions'    => $question_count,
		'course_id'    => function_exists('learndash_get_course_id') ? (int) learndash_get_course_id($quiz_id) : 0,
	);
}


/**
 * رندر هر دیدگاه در فهرست استاندارد (wp_list_comments) — تاریخ شمسی، برچسب‌های فارسی.
 *
 * @param WP_Comment $comment
 * @param array      $args
 * @param int        $depth
 */
function evented_comment_callback($comment, $args, $depth)
{
	$tag       = ('div' === $args['style']) ? 'div' : 'li';
	$ts        = get_comment_time('U', true, false, $comment);
	$date      = function_exists('evented_format_jalali') ? evented_format_jalali((int) $ts, 'j F Y') : get_comment_date('', $comment);
	$time      = get_comment_time('H:i', false, false, $comment);
	$is_author = (int) $comment->user_id && (int) $comment->user_id === (int) get_post_field('post_author', $comment->comment_post_ID);
?>
	<<?php echo $tag; // phpcs:ignore 
		?> id="comment-<?php comment_ID(); ?>" <?php comment_class($comment->has_children ? 'parent' : '', $comment); ?>>
		<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
			<footer class="comment-meta">
				<div class="comment-author vcard">
					<?php echo 0 !== (int) $args['avatar_size'] ? get_avatar($comment, $args['avatar_size']) : ''; ?>
					<b class="fn"><?php echo esc_html(get_comment_author($comment)); ?></b>
					<?php if ($is_author) : ?><span class="ee-cmt-badge">نویسنده</span><?php endif; ?>
				</div>
				<div class="comment-metadata">
					<time datetime="<?php comment_time('c'); ?>"><?php echo esc_html($date . ' · ' . $time); ?></time>
					<?php if ('0' === $comment->comment_approved) : ?>
						<em class="comment-awaiting-moderation">دیدگاه شما در انتظار تأیید است.</em>
					<?php endif; ?>
				</div>
			</footer>
			<div class="comment-content"><?php comment_text($comment, $args); ?></div>
			<?php
			comment_reply_link(array_merge($args, array(
				'add_below' => 'div-comment',
				'depth'     => $depth,
				'max_depth' => $args['max_depth'],
				'before'    => '<div class="reply">',
				'after'     => '</div>',
			)));
			?>
		</article>
	<?php
}


/**
 * فاصلهٔ زمانی فارسی («۸ دقیقه پیش»، «دیروز»، «۳ روز پیش»).
 *
 * @param int $from timestamp
 * @param int $to   timestamp (پیش‌فرض اکنون)
 * @return string
 */
function evented_time_ago($from, $to = 0)
{
	$to   = $to ? (int) $to : time();
	$diff = max(0, $to - (int) $from);
	$fa   = static function ($n) {
		return strtr((string) $n, array('0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹'));
	};
	if ($diff < 60) {
		return 'همین حالا';
	}
	if ($diff < HOUR_IN_SECONDS) {
		return $fa((int) floor($diff / 60)) . ' دقیقه پیش';
	}
	if ($diff < DAY_IN_SECONDS) {
		return $fa((int) floor($diff / HOUR_IN_SECONDS)) . ' ساعت پیش';
	}
	if ($diff < 2 * DAY_IN_SECONDS) {
		return 'دیروز';
	}
	if ($diff < 30 * DAY_IN_SECONDS) {
		return $fa((int) floor($diff / DAY_IN_SECONDS)) . ' روز پیش';
	}
	if ($diff < 365 * DAY_IN_SECONDS) {
		return $fa((int) floor($diff / (30 * DAY_IN_SECONDS))) . ' ماه پیش';
	}
	return $fa((int) floor($diff / (365 * DAY_IN_SECONDS))) . ' سال پیش';
}

/**
 * تاریخ امروز برای نوار بالایی: «شنبه ۲۱ شهریور ۱۴۰۵» (اگر افزونهٔ شمسی‌ساز نباشد، تبدیل داخلی).
 */
function evented_today_label()
{
	$wp = function_exists('evented_wp_date') ? evented_wp_date('l j F Y') : date_i18n('l j F Y');
	if ($wp && !preg_match('/[0-9]/', $wp)) {
		return $wp; // افزونهٔ شمسی‌ساز
	}
	$days = array('Saturday' => 'شنبه', 'Sunday' => 'یکشنبه', 'Monday' => 'دوشنبه', 'Tuesday' => 'سه‌شنبه', 'Wednesday' => 'چهارشنبه', 'Thursday' => 'پنجشنبه', 'Friday' => 'جمعه');
	$d    = function_exists('evented_wp_date') ? evented_wp_date('l') : date('l');
	return (isset($days[$d]) ? $days[$d] . ' ' : '') . evented_format_jalali(current_time('timestamp'), 'j F Y');
}

/**
 * نام دسته/برچسب‌ها گاهی با تگ HTML (مثل <span>…</span>) ذخیره شده‌اند و در خروجی
 * escape‌شده به‌صورت «&lt;span&gt;» دیده می‌شوند. همه‌جا تگ‌ها را از نام ترم حذف می‌کنیم.
 */
function evented_clean_term_name($name)
{
	if (!is_string($name) || false === strpos($name, '<') && false === strpos($name, '&lt;')) {
		return $name;
	}
	$name = html_entity_decode($name, ENT_QUOTES, 'UTF-8');
	return trim(wp_strip_all_tags($name));
}
add_filter('term_name', 'evented_clean_term_name', 5);
add_filter('single_cat_title', 'evented_clean_term_name', 5);
add_filter('single_tag_title', 'evented_clean_term_name', 5);
add_filter('single_term_title', 'evented_clean_term_name', 5);
add_filter('get_term', function ($term) {
	if ($term instanceof WP_Term) {
		$term->name = evented_clean_term_name($term->name);
	}
	return $term;
}, 5);
add_filter('get_terms', function ($terms) {
	foreach ((array) $terms as $t) {
		if ($t instanceof WP_Term) {
			$t->name = evented_clean_term_name($t->name);
		}
	}
	return $terms;
}, 5);
add_filter('get_the_terms', function ($terms) {
	foreach ((array) $terms as $t) {
		if ($t instanceof WP_Term) {
			$t->name = evented_clean_term_name($t->name);
		}
	}
	return $terms;
}, 5);
