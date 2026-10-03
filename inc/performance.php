<?php
/**
 * بهینه‌سازی‌های کم‌ریسک فرانت‌اند.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/* ایموجی‌های وردپرس برای رابط فارسی این قالب لازم نیستند و یک درخواست/اسکریپت حذف می‌شود. */
add_action('init', static function () {
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
	remove_action('admin_print_scripts', 'print_emoji_detection_script');
	remove_action('admin_print_styles', 'print_emoji_styles');
	remove_action('admin_enqueue_scripts', 'wp_enqueue_emoji_styles');
	remove_filter('the_content_feed', 'wp_staticize_emoji');
	remove_filter('comment_text_rss', 'wp_staticize_emoji');
	remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
});

/* فونت اصلی محلی زودتر دریافت شود؛ فقط روی نماهایی که واقعاً ee-fonts را مصرف می‌کنند. */
add_action('wp_head', static function () {
	if (!function_exists('evented_is_ee_view') || !evented_is_ee_view()) {
		return;
	}
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>%s',
		esc_url(PATH_DIR_URL . '/assets/fonts/Vazirmatn-Variable.woff2'),
		"\n"
	);
}, 2);

/* اسکریپت‌های مستقل قالب در مرورگرهای جدید بدون مسدودکردن parse اجرا شوند. */
add_filter('script_loader_tag', static function ($tag, $handle) {
	$defer = array(
		'ee-home-js', 'ee-lms', 'ee-panel', 'ee-catalog', 'ee-live-search',
		'ee-notify', 'ee-rating', 'ee-resume', 'ee-pwa',
	);
	if (in_array($handle, $defer, true) && false === strpos($tag, ' defer')) {
		$tag = str_replace(' src=', ' defer src=', $tag);
	}
	return $tag;
}, 10, 2);

/* کش‌های سبک صفحهٔ اصلی هنگام تغییر دادهٔ مرتبط بی‌اعتبار شوند. */
$evented_flush_testimonials = static function () { delete_transient('evented_home_testimonial_ids_v1'); };
add_action('comment_post', $evented_flush_testimonials);
add_action('edited_comment', $evented_flush_testimonials);
add_action('deleted_comment', $evented_flush_testimonials);
add_action('transition_comment_status', $evented_flush_testimonials);

$evented_flush_instructors = static function () { delete_transient('evented_home_instructor_ids_v1'); };
add_action('profile_update', $evented_flush_instructors);
add_action('user_register', $evented_flush_instructors);
add_action('deleted_user', $evented_flush_instructors);
add_action('set_user_role', $evented_flush_instructors);
