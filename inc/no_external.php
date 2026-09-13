<?php
/**
 * حذف همهٔ درخواست‌های فرانت‌اند به سرورهای خارجی
 *
 * وردپرس به‌صورت پیش‌فرض چند منبع خارجی تزریق می‌کند (اسکریپت اموجی از s.w.org،
 * dns-prefetch، کشف oEmbed، …). فونت‌ها و آیکن‌ها هم در قالب محلی‌اند
 * (ee-fonts.css + اسپرایت SVG). این فایل باقی‌ماندهٔ درخواست‌های خارجی را حذف می‌کند.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

add_action('init', static function () {
	// اموجی (svg از s.w.org)
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_action('admin_print_scripts', 'print_emoji_detection_script');
	remove_action('admin_print_styles', 'print_emoji_styles');
	remove_filter('the_content_feed', 'wp_staticize_emoji');
	remove_filter('comment_text_rss', 'wp_staticize_emoji');
	remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
	add_filter('emoji_svg_url', '__return_false');
	add_filter('tiny_mce_plugins', static function ($plugins) {
		return is_array($plugins) ? array_diff($plugins, array('wpemoji')) : $plugins;
	});

	// کشف oEmbed و اسکریپت embed (اجزای صفحه را به سایت‌های دیگر وصل نمی‌کند)
	remove_action('wp_head', 'wp_oembed_add_discovery_links');
	remove_action('wp_head', 'wp_oembed_add_host_js');
	// لینک‌های غیرضروری head
	remove_action('wp_head', 'wp_generator');
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'rsd_link');
});

// dns-prefetch به s.w.org و سایر دامنه‌ها
add_filter('wp_resource_hints', static function ($urls, $relation_type) {
	if (in_array($relation_type, array('dns-prefetch', 'preconnect'), true)) {
		$home = wp_parse_url(home_url(), PHP_URL_HOST);
		$urls = array_filter((array) $urls, static function ($u) use ($home) {
			$h = is_array($u) ? (isset($u['href']) ? $u['href'] : '') : $u;
			$host = wp_parse_url($h, PHP_URL_HOST);
			return !$host || $host === $home;
		});
	}
	return array_values($urls);
}, 10, 2);

// هیچ استایل/اسکریپتی از دامنهٔ خارجی روی فرانت‌اند بارگذاری نشود (به‌جز آنچه صریحاً مجاز شده)
add_action('wp_enqueue_scripts', static function () {
	if (is_admin()) {
		return;
	}
	$home    = (string) wp_parse_url(home_url(), PHP_URL_HOST);
	$allowed = (array) apply_filters('evented_allowed_external_hosts', array());
	foreach (array(wp_styles(), wp_scripts()) as $deps) {
		if (!$deps instanceof WP_Dependencies) {
			continue;
		}
		foreach ($deps->registered as $handle => $dep) {
			if (empty($dep->src)) {
				continue;
			}
			$host = wp_parse_url($dep->src, PHP_URL_HOST);
			if ($host && $host !== $home && !in_array($host, $allowed, true)) {
				$deps->dequeue($handle);
				$deps->remove($handle);
			}
		}
	}
}, 999);
