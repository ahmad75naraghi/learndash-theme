<?php
/**
 * مسیریابی خودکار پنل کاربری
 *
 * هر برگه‌ای که مسیرش `panel/<بخش>` باشد (یا نامکش یکی از بخش‌های پنل باشد و
 * زیرمجموعهٔ برگهٔ panel باشد) بدون نیاز به انتخاب دستی «قالب برگه» با
 * فایل مربوطه در پوشهٔ panel/ رندر می‌شود. اگر برگه‌ای ساخته نشده باشد،
 * نشانی `/panel/<بخش>/` هم به‌صورت مجازی پاسخ می‌دهد.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/**
 * نقشهٔ بخش‌های پنل → فایل قالب (نامک‌های جایگزین هم پذیرفته می‌شوند).
 *
 * @return array<string,string> slug => file (relative to theme)
 */
function evented_panel_sections()
{
	return array(
		'dashboard'     => 'panel/dashboard.php',
		'my-courses'    => 'panel/my-courses.php',
		'courses'       => 'panel/my-courses.php',
		'certificates'  => 'panel/certificates.php',
		'certificate'   => 'panel/certificates.php',
		'wishlist'      => 'panel/wishlist.php',
		'favorites'     => 'panel/wishlist.php',
		'payments'      => 'panel/payments.php',
		'transactions'  => 'panel/payments.php',
		'profile'       => 'panel/profile.php',
		'settings'      => 'panel/settings.php',
		'account'       => 'panel/settings.php',
	);
}

/**
 * قالب مناسب برای برگهٔ جاری پنل (یا خالی).
 *
 * @param WP_Post $page
 * @return string
 */
function evented_panel_template_for_page($page)
{
	$sections = evented_panel_sections();
	$slug     = (string) $page->post_name;

	/* خود برگهٔ panel → پیشخوان */
	if ('panel' === $slug && !$page->post_parent) {
		return 'panel/dashboard.php';
	}

	/* برگهٔ فرزند panel با نامک شناخته‌شده */
	if ($page->post_parent && isset($sections[$slug])) {
		$parent = get_post($page->post_parent);
		if ($parent instanceof WP_Post && 'panel' === (string) $parent->post_name) {
			return $sections[$slug];
		}
	}

	/* برگه‌ای که قالب «Panel - …» را دارد (انتخاب دستی قدیمی) */
	$tpl = (string) get_page_template_slug($page);
	if ($tpl && 0 === strpos($tpl, 'panel/') && isset(array_flip($sections)[$tpl])) {
		return $tpl;
	}

	return '';
}

/**
 * جایگزینی قالب برگه‌های پنل.
 */
add_filter('template_include', function ($template) {
	if (!is_page()) {
		return $template;
	}
	$page = get_queried_object();
	if (!$page instanceof WP_Post) {
		return $template;
	}
	$file = evented_panel_template_for_page($page);
	if (!$file) {
		return $template;
	}
	$path = get_theme_file_path($file);
	return file_exists($path) ? $path : $template;
}, 20);

/* ---------- مسیر مجازی /panel/<بخش>/ وقتی برگهٔ فرزند ساخته نشده ---------- */

add_action('init', function () {
	add_rewrite_tag('%ee_panel%', '([a-z-]+)');
	add_rewrite_rule('^panel/([a-z-]+)/?$', 'index.php?pagename=panel&ee_panel=$matches[1]', 'top');
});

/**
 * اگر برگهٔ واقعی با مسیر panel/<بخش> وجود دارد، قانون بالا نباید آن را بدزدد.
 */
add_action('parse_request', function ($wp) {
	if (empty($wp->query_vars['ee_panel'])) {
		return;
	}
	$section = sanitize_key($wp->query_vars['ee_panel']);
	$real    = get_page_by_path('panel/' . $section);
	if ($real instanceof WP_Post && 'publish' === $real->post_status) {
		$wp->query_vars['pagename'] = 'panel/' . $section;
		unset($wp->query_vars['ee_panel']);
	}
});

add_filter('template_include', function ($template) {
	$section = sanitize_key((string) get_query_var('ee_panel'));
	if (!$section || !is_page('panel')) {
		return $template;
	}
	$sections = evented_panel_sections();
	if (!isset($sections[$section])) {
		return $template;
	}
	$path = get_theme_file_path($sections[$section]);
	return file_exists($path) ? $path : $template;
}, 30);

/**
 * پس از تعویض پوسته یک‌بار قوانین بازنویسی تازه شود.
 */
add_action('after_switch_theme', 'flush_rewrite_rules');
add_action('init', function () {
	if (get_option('evented_panel_rewrite_v') !== '1') {
		flush_rewrite_rules(false);
		update_option('evented_panel_rewrite_v', '1', false);
	}
}, 99);
