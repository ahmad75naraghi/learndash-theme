<?php
/**
 * فقط توسط bin/test-wordpress-update.sh داخل ریشهٔ disposable WordPress کپی می‌شود.
 * این فایل از مسیر قالب اجرا نمی‌شود و نباید مستقیماً در production استفاده شود.
 */

require __DIR__ . '/wp-load.php';
header('Content-Type: application/json; charset=utf-8');

$result = array('success' => false, 'checks' => array());
$finish = static function ($payload, $status = 200) {
	status_header($status);
	echo wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
};

if (!is_user_logged_in() || !current_user_can('update_themes')) {
	$result['error'] = 'authenticated administrator required';
	$finish($result, 403);
}
if (!wp_verify_nonce(isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '', 'evented_check_theme_update')) {
	$result['error'] = 'invalid nonce';
	$finish($result, 403);
}

$package   = __DIR__ . '/evented-edu-update-test.zip';
$stylesheet = get_option('stylesheet');
$theme_dir = get_theme_root($stylesheet) . '/' . $stylesheet;
$style     = $theme_dir . '/style.css';
$sentinel  = $theme_dir . '/obsolete-update-test.txt';
if (!is_file($package) || !is_file($style)) {
	$result['error'] = 'package or active theme missing';
	$finish($result, 500);
}

/* یک نصب قدیمی واقعی در کپی disposable Playground شبیه‌سازی می‌شود. */
$style_contents = file_get_contents($style);
$style_contents = preg_replace('/^Version:\s*[^\r\n]+/m', 'Version: 2.1.0', $style_contents, 1);
file_put_contents($style, $style_contents);
file_put_contents($sentinel, 'must be removed by Theme_Upgrader');
$marker = 'update-preserved-' . wp_generate_password(12, false, false);
update_option('evented_update_integration_marker', $marker, false);
wp_clean_themes_cache(true);

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/theme.php';

$skin     = new Automatic_Upgrader_Skin();
$upgrader = new Theme_Upgrader($skin);
ob_start();
$installed = $upgrader->install($package, array('overwrite_package' => true));
$skin_output = ob_get_clean();

if (is_wp_error($installed)) {
	$result['error'] = $installed->get_error_code() . ': ' . $installed->get_error_message();
	$result['skin_output'] = wp_strip_all_tags($skin_output);
	$finish($result, 500);
}
wp_clean_themes_cache(true);
$theme = wp_get_theme($stylesheet);

$result['checks'] = array(
	'upgrader_returned_true' => true === $installed,
	'version_is_2_2_0'       => '2.2.0' === (string) $theme->get('Version'),
	'theme_stays_active'      => $stylesheet === get_option('stylesheet'),
	'option_is_preserved'     => $marker === get_option('evented_update_integration_marker'),
	'obsolete_file_removed'   => !file_exists($sentinel),
	'updater_file_installed'  => file_exists($theme_dir . '/inc/theme_updater.php'),
);
$result['success'] = !in_array(false, $result['checks'], true);
$result['version'] = (string) $theme->get('Version');
$result['stylesheet'] = $stylesheet;
delete_option('evented_update_integration_marker');
$finish($result, $result['success'] ? 200 : 500);
