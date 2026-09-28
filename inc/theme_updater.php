<?php
/**
 * به‌روزرسان خودکار قالب از GitHub Releases.
 *
 * انتشار باید در مخزن عمومی GitHub با tag نسخه (برای نمونه v2.2.0) ساخته شود.
 * اگر asset با نام evented-edu.zip وجود داشته باشد در اولویت است؛ در غیر این صورت
 * zipball رسمی همان Release مصرف و پوشهٔ تصادفی GitHub پیش از نصب به slug قالب
 * نرمال می‌شود.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

if (!defined('EVENTED_THEME_UPDATE_REPO')) {
	define('EVENTED_THEME_UPDATE_REPO', 'ahmad75naraghi/learndash-theme');
}
if (!defined('EVENTED_THEME_UPDATE_CACHE')) {
	define('EVENTED_THEME_UPDATE_CACHE', 'evented_theme_github_release_v1');
}

/**
 * نشانی مخزن عمومی منبع انتشارها.
 *
 * @return string
 */
function evented_theme_updater_repo()
{
	return (string) apply_filters('evented_theme_updater_repo', EVENTED_THEME_UPDATE_REPO);
}

/**
 * slug پوشهٔ قالب نصب‌شده.
 *
 * @return string
 */
function evented_theme_updater_slug()
{
	return basename((string) get_template_directory());
}

/**
 * اعتبارسنجی نشانی بسته؛ فقط میزبان‌های رسمی GitHub پذیرفته می‌شوند.
 *
 * @param string $url نشانی بسته.
 * @return bool
 */
function evented_theme_updater_package_url_is_safe($url)
{
	$host = strtolower((string) wp_parse_url((string) $url, PHP_URL_HOST));
	return in_array($host, array(
		'github.com',
		'api.github.com',
		'codeload.github.com',
		'objects.githubusercontent.com',
	), true);
}

/**
 * پاسخ JSON گیت‌هاب را به قرارداد داخلی تبدیل می‌کند.
 *
 * این تابع عمداً مستقل و قطعی نگه داشته شده تا بدون شبکه unit test شود.
 *
 * @param array $payload پاسخ releases/latest.
 * @return array|null {version,tag,url,package,body,published_at}
 */
function evented_theme_updater_normalize_release($payload)
{
	if (!is_array($payload) || !empty($payload['draft']) || !empty($payload['prerelease'])) {
		return null;
	}

	$tag     = isset($payload['tag_name']) ? trim((string) $payload['tag_name']) : '';
	$version = preg_replace('/^[vV]/', '', $tag);
	if (!is_string($version) || !preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version)) {
		return null;
	}

	$package = '';
	foreach (isset($payload['assets']) && is_array($payload['assets']) ? $payload['assets'] : array() as $asset) {
		$name = isset($asset['name']) ? strtolower((string) $asset['name']) : '';
		$url  = isset($asset['browser_download_url']) ? (string) $asset['browser_download_url'] : '';
		if (in_array($name, array('evented-edu.zip', 'learndash-theme.zip'), true) && evented_theme_updater_package_url_is_safe($url)) {
			$package = $url;
			break;
		}
	}

	if ('' === $package) {
		$fallback = isset($payload['zipball_url']) ? (string) $payload['zipball_url'] : '';
		if (evented_theme_updater_package_url_is_safe($fallback)) {
			$package = $fallback;
		}
	}
	if ('' === $package) {
		return null;
	}

	$url = isset($payload['html_url']) ? (string) $payload['html_url'] : '';
	if (!evented_theme_updater_package_url_is_safe($url)) {
		$url = 'https://github.com/' . evented_theme_updater_repo() . '/releases/tag/' . rawurlencode($tag);
	}

	return array(
		'version'      => $version,
		'tag'          => $tag,
		'url'          => $url,
		'package'      => $package,
		'body'         => isset($payload['body']) ? (string) $payload['body'] : '',
		'published_at' => isset($payload['published_at']) ? (string) $payload['published_at'] : '',
	);
}

/**
 * آخرین Release را با کش شش‌ساعته می‌گیرد.
 *
 * @param bool $force نادیده‌گرفتن کش.
 * @return array|null
 */
function evented_theme_updater_get_release($force = false)
{
	if (!$force) {
		$cached = get_site_transient(EVENTED_THEME_UPDATE_CACHE);
		if (is_array($cached) && !empty($cached['no_release'])) {
			return null;
		}
		if (is_array($cached) && !empty($cached['version'])) {
			return $cached;
		}
	}

	$repo = evented_theme_updater_repo();
	if (!preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repo)) {
		return null;
	}

	$response = wp_safe_remote_get('https://api.github.com/repos/' . $repo . '/releases/latest', array(
		'timeout'     => 8,
		'redirection' => 3,
		'headers'     => array(
			'Accept'     => 'application/vnd.github+json',
			'User-Agent' => 'evented-edu-theme-updater',
		),
	));

	if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
		/* خطای موقت شبکه را کوتاه کش می‌کنیم تا پیشخوان کند نشود. */
		set_site_transient(EVENTED_THEME_UPDATE_CACHE, array('no_release' => 1), 15 * MINUTE_IN_SECONDS);
		return null;
	}

	$payload = json_decode((string) wp_remote_retrieve_body($response), true);
	$release = evented_theme_updater_normalize_release($payload);
	set_site_transient(
		EVENTED_THEME_UPDATE_CACHE,
		$release ? $release : array('no_release' => 1),
		$release ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS
	);
	return $release;
}

/**
 * پاسخ استاندارد update_themes وردپرس را می‌سازد.
 *
 * @param array  $release انتشار نرمال‌شده.
 * @param string $installed نسخهٔ نصب‌شده.
 * @param string $slug      slug قالب.
 * @return array|null
 */
function evented_theme_updater_build_update($release, $installed, $slug)
{
	if (!is_array($release) || empty($release['version']) || empty($release['package'])) {
		return null;
	}
	if (!version_compare((string) $release['version'], (string) $installed, '>')) {
		return null;
	}
	return array(
		'theme'        => (string) $slug,
		'new_version'  => (string) $release['version'],
		'url'          => (string) $release['url'],
		'package'      => (string) $release['package'],
		'requires'     => '6.0',
		'requires_php' => '7.4',
	);
}

/* معرفی نسخهٔ جدید به صفحهٔ «به‌روزرسانی‌ها»ی وردپرس. */
add_filter('pre_set_site_transient_update_themes', function ($transient) {
	if (!is_object($transient)) {
		$transient = new stdClass();
	}
	if (!isset($transient->response) || !is_array($transient->response)) {
		$transient->response = array();
	}

	$slug    = evented_theme_updater_slug();
	$theme   = wp_get_theme($slug);
	$current = $theme->exists() ? (string) $theme->get('Version') : '';
	$update  = evented_theme_updater_build_update(evented_theme_updater_get_release(), $current, $slug);
	if ($update) {
		$transient->response[$slug] = $update;
	} else {
		unset($transient->response[$slug]);
	}
	return $transient;
});

/* اطلاعات پنجرهٔ جزئیات نسخه در پیشخوان. */
add_filter('themes_api', function ($result, $action, $args) {
	if ('theme_information' !== $action || !is_object($args) || !isset($args->slug) || evented_theme_updater_slug() !== (string) $args->slug) {
		return $result;
	}
	$release = evented_theme_updater_get_release();
	if (!$release) {
		return $result;
	}
	return (object) array(
		'name'          => 'evented-edu',
		'slug'          => evented_theme_updater_slug(),
		'version'       => $release['version'],
		'author'        => '<a href="https://github.com/ahmad75naraghi">evented team</a>',
		'homepage'      => $release['url'],
		'requires'      => '6.0',
		'requires_php'  => '7.4',
		'download_link' => $release['package'],
		'sections'      => array('changelog' => nl2br(esc_html($release['body']))),
	);
}, 20, 3);

/**
 * zipball گیت‌هاب پوشه‌ای با نام owner-repo-hash دارد. فقط هنگام آپدیت همین قالب،
 * آن را به slug ثابت تغییر می‌دهیم تا قالب پس از نصب غیرفعال یا گم نشود.
 */
add_filter('upgrader_source_selection', function ($source, $remote_source, $upgrader, $hook_extra = array()) {
	$slug   = evented_theme_updater_slug();
	$themes = isset($hook_extra['themes']) ? (array) $hook_extra['themes'] : array();
	$is_ours = (isset($hook_extra['theme']) && $slug === $hook_extra['theme']) || in_array($slug, $themes, true);
	if (!$is_ours || !is_string($source) || !is_string($remote_source) || !file_exists(trailingslashit($source) . 'style.css')) {
		return $source;
	}
	if ($slug === basename(untrailingslashit($source))) {
		return $source;
	}

	global $wp_filesystem;
	if (!$wp_filesystem) {
		return new WP_Error('evented_updater_filesystem', __('دسترسی فایل‌سیستم برای آماده‌سازی بستهٔ قالب وجود ندارد.', 'evented-edu'));
	}
	$target = trailingslashit($remote_source) . $slug . '/';
	if ($wp_filesystem->exists($target)) {
		$wp_filesystem->delete($target, true);
	}
	if (!$wp_filesystem->move($source, $target, true)) {
		return new WP_Error('evented_updater_move', __('پوشهٔ بستهٔ به‌روزرسانی قالب قابل نرمال‌سازی نیست.', 'evented-edu'));
	}
	return $target;
}, 10, 4);

/* پاک‌کردن کش Release بعد از پایان آپدیت قالب. */
add_action('upgrader_process_complete', function ($upgrader, $hook_extra) {
	if ('theme' !== (isset($hook_extra['type']) ? $hook_extra['type'] : '')) {
		return;
	}
	$slug   = evented_theme_updater_slug();
	$themes = isset($hook_extra['themes']) ? (array) $hook_extra['themes'] : array();
	if ((isset($hook_extra['theme']) && $slug === $hook_extra['theme']) || in_array($slug, $themes, true)) {
		delete_site_transient(EVENTED_THEME_UPDATE_CACHE);
		delete_site_transient('update_themes');
	}
}, 10, 2);

/* دکمهٔ بررسی اجباری در صفحهٔ تنظیمات قالب. */
add_action('admin_post_evented_check_theme_update', function () {
	if (!current_user_can('update_themes')) {
		wp_die(esc_html__('شما اجازهٔ بررسی به‌روزرسانی قالب را ندارید.', 'evented-edu'), '', array('response' => 403));
	}
	check_admin_referer('evented_check_theme_update');
	delete_site_transient(EVENTED_THEME_UPDATE_CACHE);
	delete_site_transient('update_themes');
	evented_theme_updater_get_release(true);
	wp_update_themes();
	wp_safe_redirect(add_query_arg('evented_update_checked', '1', admin_url('admin.php?page=evented-theme-settings')));
	exit;
});
