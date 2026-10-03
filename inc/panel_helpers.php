<?php
/**
 * ابزارهای مشترک پنل کاربری.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/**
 * کش‌های post/meta/term دوره‌ها را در یک مرحله prime می‌کند تا کارت‌ها N+1 نسازند.
 *
 * @param int[] $course_ids شناسه‌ها.
 */
function evented_panel_prime_courses($course_ids)
{
	$ids = array_values(array_unique(array_filter(array_map('absint', (array) $course_ids))));
	if (!$ids) {
		return;
	}
	_prime_post_caches($ids, true, true);
}

/**
 * صفحه‌بندی کوچک و قابل‌دسترسی پنل.
 *
 * @param int    $current صفحه جاری.
 * @param int    $pages   تعداد کل صفحه‌ها.
 * @param string $arg     نام query arg.
 * @return string
 */
function evented_panel_pagination($current, $pages, $arg)
{
	$current = max(1, (int) $current);
	$pages   = max(1, (int) $pages);
	$arg     = sanitize_key($arg);
	if ($pages < 2 || '' === $arg) {
		return '';
	}

	$base  = str_replace('999999999', '%#%', esc_url(add_query_arg($arg, '999999999')));
	$links = paginate_links(array(
		'base'      => $base,
		'format'    => '',
		'current'   => $current,
		'total'     => $pages,
		'mid_size'  => 1,
		'end_size'  => 1,
		'prev_text' => 'قبلی',
		'next_text' => 'بعدی',
		'type'      => 'array',
	));
	if (!$links) {
		return '';
	}
	return '<nav class="ee-panel-pager" aria-label="صفحه‌بندی"><span class="ee-panel-page-count">صفحه ' . esc_html(number_format_i18n($current)) . ' از ' . esc_html(number_format_i18n($pages)) . '</span>' . implode('', $links) . '</nav>';
}

/**
 * ارقام فارسی/عربی را برای ذخیرهٔ مقدارهای عددی استاندارد می‌کند.
 *
 * @param string $value مقدار ورودی.
 * @return string
 */
function evented_normalize_digits($value)
{
	return strtr((string) $value, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
		'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
		'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
	));
}
