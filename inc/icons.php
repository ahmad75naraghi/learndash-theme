<?php
/**
 * آیکن‌های SVG محلی (جایگزین فونت Material Symbols)
 *
 * تمام آیکن‌ها در یک اسپرایت (assets/icons/ee-icons.svg) قرار دارند که یک بار
 * در ابتدای <body> تزریق می‌شود؛ هیچ درخواستی به خارج از سرور ارسال نمی‌شود.
 *
 * @package evented-edu
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * مسیر فایل اسپرایت.
 */
function ee_icon_sprite_path()
{
	return get_template_directory() . '/assets/icons/ee-icons.svg';
}

/**
 * آیا آیکنی با این نام در اسپرایت وجود دارد؟ (کش در حافظه)
 */
function ee_icon_exists($name)
{
	static $ids = null;
	if (null === $ids) {
		$ids = array();
		$svg = @file_get_contents(ee_icon_sprite_path()); // phpcs:ignore
		if ($svg && preg_match_all('/<symbol id="i-([a-z0-9_\-]+)"/', $svg, $m)) {
			$ids = array_fill_keys($m[1], true);
		}
	}
	return isset($ids[$name]);
}

/**
 * برگرداندن HTML یک آیکن.
 *
 * @param string $name  نام آیکن (همان نام‌های Material Symbols).
 * @param string $class کلاس‌های اضافه.
 * @param array  $attrs ویژگی‌های اضافه (کلید => مقدار).
 */
function ee_icon($name, $class = '', $attrs = array())
{
	$name = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $name));
	if ('' === $name) {
		return '';
	}
	if (!ee_icon_exists($name)) {
		$name = 'link';
	}
	$cls = trim('ee-ic ' . $class);
	$a   = '';
	if (!isset($attrs['aria-hidden']) && !isset($attrs['aria-label'])) {
		$attrs['aria-hidden'] = 'true';
	}
	foreach ($attrs as $k => $v) {
		$a .= ' ' . esc_attr($k) . '="' . esc_attr($v) . '"';
	}
	return '<svg class="' . esc_attr($cls) . '" focusable="false"' . $a . '><use href="#i-' . esc_attr($name) . '"></use></svg>';
}

/**
 * چاپ آیکن.
 */
function ee_the_icon($name, $class = '', $attrs = array())
{
	echo ee_icon($name, $class, $attrs); // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * تزریق اسپرایت در ابتدای body (روی همهٔ نماهای ee و پنل).
 */
function ee_icon_print_sprite()
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	$svg  = @file_get_contents(ee_icon_sprite_path()); // phpcs:ignore
	if ($svg) {
		echo "\n" . $svg . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action('wp_body_open', 'ee_icon_print_sprite', 1);
add_action('login_header', 'ee_icon_print_sprite', 1);
