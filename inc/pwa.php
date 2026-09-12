<?php
/**
 * PWA سبک — manifest + service worker + صفحهٔ آفلاین
 *
 * - `/?ee_manifest=1` → manifest.webmanifest (پویا: نام/رنگ/آیکن از تنظیمات قالب)؛
 * - `/ee-sw.js` → سرویس‌ورکر در ریشهٔ سایت (scope کامل) با هدر Service-Worker-Allowed؛
 *     • asset‌های قالب (css/js/fonts/svg/img): Cache-First با نسخه‌بندی؛
 *     • صفحات HTML: Network-First با fallback به کش و سپس صفحهٔ آفلاین؛
 *     • درخواست‌های wp-admin / admin-ajax / REST / wp-login هرگز کش نمی‌شوند؛
 * - `/?ee_offline=1` → صفحهٔ آفلاین سبک (بدون وابستگی)؛
 * - تگ‌های `<head>`: manifest، theme-color، apple-touch-icon، apple-mobile-web-app-*؛
 * - دکمهٔ «نصب برنامه» (beforeinstallprompt) در assets/js/newhome/ee-pwa.js؛
 * - تب «برنامهٔ وب (PWA)» در تنظیمات قالب.
 *
 * هیچ درخواست بیرونی ندارد.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

const EVENTED_PWA_VER = '1.0.0';

/* ------------------------------------------------------------------ */
/* تنظیمات                                                              */
/* ------------------------------------------------------------------ */

add_filter('evented_options_schema', static function ($schema) {
	$schema['pwa'] = array(
		'title'  => 'برنامهٔ وب (PWA)',
		'icon'   => 'dashicons-smartphone',
		'fields' => array(
			'pwa_enabled'     => array('label' => 'PWA فعال باشد (نصب روی موبایل + کش آفلاین)', 'type' => 'checkbox', 'default' => 1),
			'pwa_name'        => array('label' => 'نام کامل برنامه', 'type' => 'text', 'default' => '', 'desc' => 'خالی = نام سایت'),
			'pwa_short_name'  => array('label' => 'نام کوتاه (زیر آیکن، حداکثر ۱۲ حرف)', 'type' => 'text', 'default' => '', 'desc' => 'خالی = نام سایت'),
			'pwa_theme_color' => array('label' => 'رنگ تم (نوار مرورگر)', 'type' => 'text', 'default' => '#00897B', 'dir' => 'ltr', 'placeholder' => '#00897B'),
			'pwa_bg_color'    => array('label' => 'رنگ پس‌زمینهٔ اسپلش', 'type' => 'text', 'default' => '#F8FAF9', 'dir' => 'ltr'),
			'pwa_icon_192'    => array('label' => 'آیکن ۱۹۲×۱۹۲ (URL، PNG)', 'type' => 'url', 'default' => '', 'dir' => 'ltr', 'desc' => 'خالی = آیکن پیش‌فرض قالب'),
			'pwa_icon_512'    => array('label' => 'آیکن ۵۱۲×۵۱۲ (URL، PNG)', 'type' => 'url', 'default' => '', 'dir' => 'ltr'),
			'pwa_install_btn' => array('label' => 'نمایش دکمهٔ «نصب برنامه» در کشوی موبایل', 'type' => 'checkbox', 'default' => 1),
			'pwa_cache_ver'   => array('label' => 'نسخهٔ کش (برای بی‌اعتبار کردن کش همهٔ کاربران عدد را زیاد کنید)', 'type' => 'number', 'default' => 1, 'min' => 1, 'max' => 99999),
		),
	);
	return $schema;
});

function evented_pwa_enabled()
{
	return function_exists('evented_opt') ? 1 === (int) evented_opt('pwa_enabled', 1) : true;
}

function evented_pwa_opt($key, $default = '')
{
	$v = function_exists('evented_opt') ? evented_opt($key, $default) : $default;
	return '' === (string) $v ? $default : $v;
}

function evented_pwa_color($key, $default)
{
	$c = (string) evented_pwa_opt($key, $default);
	return preg_match('/^#[0-9a-fA-F]{3,8}$/', $c) ? $c : $default;
}

/**
 * آیکن‌ها.
 */
function evented_pwa_icons()
{
	$base = PATH_DIR_URL . '/assets/pwa/';
	$i192 = (string) evented_pwa_opt('pwa_icon_192', $base . 'icon-192.png');
	$i512 = (string) evented_pwa_opt('pwa_icon_512', $base . 'icon-512.png');
	$icons = array(
		array('src' => $i192, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'),
		array('src' => $i512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'),
	);
	if ($i512 === $base . 'icon-512.png') {
		$icons[] = array('src' => $base . 'maskable-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable');
		$icons[] = array('src' => $base . 'maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable');
	}
	return $icons;
}

/**
 * نسخهٔ کش (تغییر با هر نسخهٔ قالب یا دستی از تنظیمات).
 */
function evented_pwa_cache_ver()
{
	$theme = wp_get_theme();
	return 'ee-' . preg_replace('/[^a-z0-9.]/i', '', (string) $theme->get('Version')) . '-' . (int) evented_pwa_opt('pwa_cache_ver', 1) . '-' . EVENTED_PWA_VER;
}

/* ------------------------------------------------------------------ */
/* مسیرها                                                               */
/* ------------------------------------------------------------------ */

add_action('init', static function () {
	add_rewrite_rule('^ee-sw\.js$', 'index.php?ee_sw=1', 'top');
	add_rewrite_rule('^manifest\.webmanifest$', 'index.php?ee_manifest=1', 'top');
});
add_filter('query_vars', static function ($vars) {
	$vars[] = 'ee_sw';
	$vars[] = 'ee_manifest';
	$vars[] = 'ee_offline';
	return $vars;
});
add_action('after_switch_theme', 'flush_rewrite_rules', 21);

add_action('parse_request', static function ($wp) {
	// هم با rewrite و هم با query string کار می‌کند (اگر قوانین هنوز تازه نشده باشند).
	$qv = $wp->query_vars;
	$is = static function ($k) use ($qv) {
		return !empty($qv[$k]) || !empty($_GET[$k]); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	};
	if ($is('ee_manifest')) {
		evented_pwa_serve_manifest();
	} elseif ($is('ee_sw')) {
		evented_pwa_serve_sw();
	} elseif ($is('ee_offline')) {
		evented_pwa_serve_offline();
	}
});

function evented_pwa_serve_manifest()
{
	if (!evented_pwa_enabled()) {
		status_header(404);
		exit;
	}
	$name  = (string) evented_pwa_opt('pwa_name', get_bloginfo('name'));
	$short = (string) evented_pwa_opt('pwa_short_name', $name);
	if (function_exists('mb_substr')) {
		$short = mb_substr($short, 0, 12);
	}
	$m = array(
		'id'               => home_url('/'),
		'name'             => $name,
		'short_name'       => $short,
		'description'      => (string) get_bloginfo('description'),
		'start_url'        => home_url('/?utm_source=pwa'),
		'scope'            => home_url('/'),
		'display'          => 'standalone',
		'orientation'      => 'portrait',
		'dir'              => 'rtl',
		'lang'             => 'fa',
		'background_color' => evented_pwa_color('pwa_bg_color', '#F8FAF9'),
		'theme_color'      => evented_pwa_color('pwa_theme_color', '#00897B'),
		'icons'            => evented_pwa_icons(),
		'shortcuts'        => array(
			array('name' => 'دوره‌ها', 'url' => home_url('/courses/?utm_source=pwa'), 'icons' => array(array('src' => PATH_DIR_URL . '/assets/pwa/icon-192.png', 'sizes' => '192x192'))),
			array('name' => 'پنل کاربری', 'url' => home_url('/panel/?utm_source=pwa'), 'icons' => array(array('src' => PATH_DIR_URL . '/assets/pwa/icon-192.png', 'sizes' => '192x192'))),
		),
	);
	$m = apply_filters('evented_pwa_manifest', $m);
	nocache_headers();
	header('Content-Type: application/manifest+json; charset=utf-8');
	header('Cache-Control: public, max-age=3600');
	echo wp_json_encode($m, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

function evented_pwa_serve_sw()
{
	if (!evented_pwa_enabled()) {
		// سرویس‌ورکر خودحذف‌شونده تا کاربران قبلی رها شوند.
		header('Content-Type: application/javascript; charset=utf-8');
		header('Cache-Control: no-cache');
		echo "self.addEventListener('install',function(){self.skipWaiting()});self.addEventListener('activate',function(e){e.waitUntil(caches.keys().then(function(k){return Promise.all(k.filter(function(n){return n.indexOf('ee-')===0}).map(function(n){return caches.delete(n)}))}).then(function(){return self.registration.unregister()}))});";
		exit;
	}
	$file = get_template_directory() . '/assets/js/newhome/ee-sw.js';
	$js   = file_exists($file) ? (string) file_get_contents($file) : '';
	$cfg  = array(
		'ver'       => evented_pwa_cache_ver(),
		'home'      => home_url('/'),
		'themeUrl'  => PATH_DIR_URL . '/',
		'offline'   => home_url('/?ee_offline=1'),
		'precache'  => array_values(array_unique(array_map('strval', apply_filters('evented_pwa_precache', array(
			home_url('/?ee_offline=1'),
			PATH_DIR_URL . '/assets/css/newhome/ee-fonts.css',
			PATH_DIR_URL . '/assets/css/newhome/ee-shell.css',
			PATH_DIR_URL . '/assets/fonts/Vazirmatn-Variable.woff2',
			PATH_DIR_URL . '/assets/icons/ee-icons.svg',
			PATH_DIR_URL . '/assets/pwa/icon-192.png',
		))))),
		'never'     => array('/wp-admin', '/wp-login.php', '/admin-ajax.php', '/wp-json', 'rest_route=', '/wp-cron.php', '/xmlrpc.php', '/panel', '/login', 'ee_sw=', 'ee-sw.js', 'ee_manifest', 'utm_source=pwa&nocache'),
		'maxPages'  => 40,
	);
	header('Content-Type: application/javascript; charset=utf-8');
	header('Service-Worker-Allowed: /');
	header('Cache-Control: no-cache, max-age=0');
	echo 'self.EE_SW = ' . wp_json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n" . $js;
	exit;
}

function evented_pwa_serve_offline()
{
	status_header(200);
	nocache_headers();
	header('Content-Type: text/html; charset=utf-8');
	$name  = esc_html((string) evented_pwa_opt('pwa_name', get_bloginfo('name')));
	$color = esc_attr(evented_pwa_color('pwa_theme_color', '#00897B'));
	$icon  = esc_url(PATH_DIR_URL . '/assets/pwa/icon-192.png');
	$font  = esc_url(PATH_DIR_URL . '/assets/fonts/Vazirmatn-Variable.woff2');
	echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>آفلاین هستید — ' . $name . '</title>'
		. '<style>@font-face{font-family:V;src:url(' . $font . ') format("woff2");font-display:swap}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#F8FAF9;color:#334155;font:16px/1.8 V,Vazirmatn,Tahoma,sans-serif;text-align:center;padding:1.5rem;box-sizing:border-box}img{width:84px;height:84px;border-radius:22px;box-shadow:0 8px 20px -3px rgba(0,137,123,.25)}h1{font-size:1.25rem;margin:1rem 0 .3rem}p{color:#64748B;margin:0 0 1.2rem;font-size:.9rem}button{background:' . $color . ';color:#fff;border:0;border-radius:999px;padding:.7rem 1.6rem;font:inherit;font-weight:700;cursor:pointer}</style></head>'
		. '<body><div><img src="' . $icon . '" alt=""><h1>اتصال اینترنت برقرار نیست</h1><p>این صفحه هنوز در دستگاه شما ذخیره نشده است. بعد از وصل شدن به اینترنت دوباره تلاش کنید.</p><button onclick="location.reload()">تلاش مجدد</button></div>'
		. '<script>addEventListener("online",function(){location.reload()})</script></body></html>';
	exit;
}

/* ------------------------------------------------------------------ */
/* تگ‌های head + ثبت SW                                                 */
/* ------------------------------------------------------------------ */

add_action('wp_head', static function () {
	if (!evented_pwa_enabled()) {
		return;
	}
	$color = evented_pwa_color('pwa_theme_color', '#00897B');
	$apple = (string) evented_pwa_opt('pwa_icon_192', PATH_DIR_URL . '/assets/pwa/apple-touch-icon.png');
	echo '<link rel="manifest" href="' . esc_url(home_url('/?ee_manifest=1')) . '">' . "\n";
	echo '<meta name="theme-color" content="' . esc_attr($color) . '">' . "\n";
	echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
	echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr((string) evented_pwa_opt('pwa_short_name', get_bloginfo('name'))) . '">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url($apple) . '">' . "\n";
}, 2);

add_action('wp_enqueue_scripts', static function () {
	if (!evented_pwa_enabled()) {
		return;
	}
	wp_enqueue_script('ee-pwa', PATH_DIR_URL . '/assets/js/newhome/ee-pwa.js', array(), EVENTED_PWA_VER, true);
	wp_localize_script('ee-pwa', 'eePwa', array(
		'sw'         => home_url('/ee-sw.js'),
		'swFallback' => home_url('/?ee_sw=1'),
		'installBtn' => 1 === (int) evented_pwa_opt('pwa_install_btn', 1),
		'i18n'       => array('install' => 'نصب برنامه', 'installed' => 'برنامه نصب شد', 'offline' => 'آفلاین هستید — برخی بخش‌ها در دسترس نیست', 'online' => 'اتصال برقرار شد', 'update' => 'نسخهٔ جدید سایت آماده است', 'reload' => 'به‌روزرسانی'),
	));
	wp_enqueue_style('ee-pwa', PATH_DIR_URL . '/assets/css/newhome/ee-pwa.css', array('ee-shell'), EVENTED_PWA_VER);
}, 27);

/**
 * جای دکمهٔ نصب در کشوی موبایل (به‌صورت placeholder؛ JS فقط وقتی نصب ممکن باشد نمایش می‌دهد).
 */
function evented_pwa_install_button_html()
{
	if (!evented_pwa_enabled() || 1 !== (int) evented_pwa_opt('pwa_install_btn', 1)) {
		return '';
	}
	return '<button type="button" class="ee-pwa-install" id="eePwaInstall" hidden><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-download_for_offline"></use></svg><span>نصب برنامه روی گوشی</span></button>';
}
