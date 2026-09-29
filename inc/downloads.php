<?php
/**
 * پشتیبانی مستقل از داده‌های WordPress Download Manager پس از حذف افزونه.
 *
 * هیچ رکوردی مهاجرت یا حذف نمی‌شود: post type `wpdmpro` و متاهای
 * `__wpdm_*` با همان کلیدهای اصلی خوانده می‌شوند. دانلود عمداً هیچ taxonomy ندارد.
 *
 * @package evented-edu
 */
defined('ABSPATH') || exit;

/** ثبت همان کلیدهای WPDM فقط وقتی افزونه آن‌ها را ثبت نکرده باشد. */
function evented_register_wpdm_content()
{
	/* wpdmcategory فقط taxonomy کتابخانه است و هرگز به دانلود متصل نمی‌شود. */
	if (!taxonomy_exists('wpdmcategory')) {
		register_taxonomy('wpdmcategory', array('lib'), array(
			'labels' => array(
				'name' => 'دسته‌های کتابخانه', 'singular_name' => 'دستهٔ کتابخانه',
				'all_items' => 'همهٔ دسته‌ها', 'edit_item' => 'ویرایش دسته',
				'add_new_item' => 'افزودن دسته', 'search_items' => 'جستجوی دسته‌ها',
			),
			'public' => true, 'publicly_queryable' => true, 'hierarchical' => true,
			'show_ui' => true, 'show_admin_column' => true, 'show_in_rest' => true,
			'show_in_nav_menus' => true, 'query_var' => true,
			'rewrite' => array('slug' => 'wpdmcategory', 'with_front' => true, 'hierarchical' => true),
		));
	}
	if (!post_type_exists('wpdmpro')) {
		register_post_type('wpdmpro', array(
			'labels' => function_exists('evented_content_type_labels') ? evented_content_type_labels('دانلودها', 'دانلود') : array('name' => 'دانلودها'),
			'public' => true, 'publicly_queryable' => true, 'show_ui' => true,
			'show_in_menu' => true, 'show_in_rest' => true, 'exclude_from_search' => false,
			'capability_type' => 'post', 'map_meta_cap' => true, 'hierarchical' => false,
			'has_archive' => false, 'query_var' => true, 'menu_icon' => 'dashicons-download',
			'rewrite' => array('slug' => 'download', 'with_front' => false),
			'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'author', 'custom-fields'),
			'taxonomies' => array(),
		));
	}
	if (taxonomy_exists('wpdmcategory')) {
		register_taxonomy_for_object_type('wpdmcategory', 'lib');
	}
}
add_action('init', 'evented_register_wpdm_content', 21);

/**
 * دانلود هیچ دسته/برچسبی ندارد؛ association افزونه فقط از runtime جدا می‌شود.
 * termها و relationshipهای قدیمی در دیتابیس حذف نمی‌شوند.
 */
function evented_detach_download_taxonomies()
{
	foreach ((array) get_object_taxonomies('wpdmpro') as $taxonomy) {
		if (taxonomy_exists($taxonomy) && is_object_in_taxonomy('wpdmpro', $taxonomy)) {
			unregister_taxonomy_for_object_type($taxonomy, 'wpdmpro');
		}
	}
}
add_action('init', 'evented_detach_download_taxonomies', 100);

/** هر زیرمنوی taxonomy باقی‌مانده از WPDM نیز از منوی دانلود حذف می‌شود. */
function evented_remove_download_taxonomy_menus()
{
	$parent = 'edit.php?post_type=wpdmpro';
	remove_submenu_page($parent, 'edit-tags.php?taxonomy=wpdmcategory&post_type=wpdmpro');
	remove_submenu_page($parent, 'edit-tags.php?taxonomy=wpdmtag&post_type=wpdmpro');
}
add_action('admin_menu', 'evented_remove_download_taxonomy_menus', 999);

/** رشتهٔ متا با سازگاری کلیدهای قدیمی/جدید WPDM. */
function evented_wpdm_meta($post_id, $names, $default = '')
{
	foreach ((array) $names as $name) {
		$value = get_post_meta((int) $post_id, (string) $name, true);
		if (!(null === $value || '' === $value || array() === $value)) {
			return $value;
		}
	}
	return $default;
}

/** تبدیل حجم فایل به متن خوانا بدون وابستگی به افزونه. */
function evented_download_size_label($bytes)
{
	$bytes = (int) $bytes;
	if ($bytes < 1) { return ''; }
	$units = array('بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت', 'ترابایت');
	$pow = min((int) floor(log($bytes, 1024)), count($units) - 1);
	return number_format_i18n($bytes / pow(1024, $pow), $pow ? 1 : 0) . ' ' . $units[$pow];
}

/** مسیر محلی امن یک فایل WPDM را فقط درون uploads پیدا می‌کند. */
function evented_download_local_path($stored)
{
	$stored = html_entity_decode(trim((string) $stored), ENT_QUOTES, 'UTF-8');
	if ('' === $stored || preg_match('#^https?://#i', $stored)) { return ''; }
	$uploads = wp_get_upload_dir();
	$base_real = realpath($uploads['basedir']);
	if (!$base_real) { return ''; }
	$relative = ltrim(str_replace(array('\\', '../'), array('/', ''), $stored), '/');
	$candidates = array(
		$uploads['basedir'] . '/download-manager-files/' . $relative,
		$uploads['basedir'] . '/' . $relative,
		$stored,
	);
	foreach ($candidates as $candidate) {
		$real = realpath($candidate);
		if ($real && is_file($real) && 0 === strpos($real, $base_real . DIRECTORY_SEPARATOR)) {
			return $real;
		}
	}
	return '';
}

/**
 * فایل‌های یک package را از متاهای اصلی WPDM استخراج می‌کند.
 *
 * @return array<int,array{label:string,stored:string,path:string,external:string,size:int,extension:string,url:string}>
 */
function evented_download_files($post_id)
{
	$post_id = (int) $post_id;
	$raw = evented_wpdm_meta($post_id, array('__wpdm_files', '_wpdm_files', '_wpdm_file', 'files'), array());
	$raw = maybe_unserialize($raw);
	if (!is_array($raw)) { $raw = '' !== (string) $raw ? array($raw) : array(); }
	$fileinfo = evented_wpdm_meta($post_id, array('__wpdm_fileinfo', '_wpdm_fileinfo'), array());
	$fileinfo = is_array($fileinfo) ? $fileinfo : array();
	$files = array();
	foreach ($raw as $key => $stored) {
		if (is_array($stored) && isset($stored['file'])) { $stored = $stored['file']; }
		if (!is_scalar($stored) || '' === trim((string) $stored)) { continue; }
		$stored = trim((string) $stored);
		$external = preg_match('#^https?://#i', $stored) ? esc_url_raw($stored) : '';
		$path = $external ? '' : evented_download_local_path($stored);
		$info = isset($fileinfo[$key]) && is_array($fileinfo[$key]) ? $fileinfo[$key] : array();
		$basename = basename((string) wp_parse_url($external ?: $stored, PHP_URL_PATH));
		$label = sanitize_text_field((string) ($info['title'] ?? $info['label'] ?? urldecode($basename)));
		$extension = strtolower((string) pathinfo($basename, PATHINFO_EXTENSION));
		$index = count($files);
		$files[] = array(
			'label' => $label ?: sprintf(__('فایل شمارهٔ %s', 'evented-edu'), number_format_i18n($index + 1)),
			'stored' => $stored, 'path' => $path, 'external' => $external,
			'size' => $path ? (int) filesize($path) : 0, 'extension' => $extension,
			'url' => add_query_arg(array('action' => 'evented_public_download', 'package' => $post_id, 'file' => $index), admin_url('admin-post.php')),
		);
	}
	return (array) apply_filters('evented_download_files', $files, $post_id);
}

/** اطلاعات استاندارد کارت/صفحهٔ دانلود از متاهای بدون تغییر WPDM. */
function evented_download_data($post_id)
{
	$post_id = (int) $post_id;
	$files = evented_download_files($post_id);
	$stored_size = (string) evented_wpdm_meta($post_id, array('__wpdm_package_size', '_wpdm_package_size'), '');
	$total_bytes = array_sum(array_map(static function ($file) { return (int) $file['size']; }, $files));
	$preview_raw = evented_wpdm_meta($post_id, array('__wpdm_preview_image', '_wpdm_preview_image'), '');
	$preview = is_numeric($preview_raw) ? (string) wp_get_attachment_image_url(absint($preview_raw), 'medium_large') : esc_url_raw((string) $preview_raw);
	if (has_post_thumbnail($post_id)) { $preview = (string) get_the_post_thumbnail_url($post_id, 'medium_large'); }
	return array(
		'id' => $post_id, 'files' => $files, 'preview' => $preview,
		'version' => sanitize_text_field((string) evented_wpdm_meta($post_id, array('__wpdm_version', '_wpdm_version'), '')),
		'downloads' => absint(evented_wpdm_meta($post_id, array('__wpdm_download_count', '_wpdm_download_count', 'download_count'), 0)),
		'views' => absint(evented_wpdm_meta($post_id, array('__wpdm_view_count', '_wpdm_view_count', 'view_count'), 0)),
		'size' => $stored_size ?: evented_download_size_label($total_bytes),
		'file_count' => count($files),
	);
}

/** دانلود عمومی انتخاب‌شده توسط کاربر؛ کاربر صراحتاً همهٔ packageها را عمومی خواسته است. */
function evented_serve_public_download()
{
	$post_id = isset($_GET['package']) ? absint($_GET['package']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$index   = isset($_GET['file']) ? absint($_GET['file']) : -1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post = get_post($post_id);
	$files = $post instanceof WP_Post && 'wpdmpro' === $post->post_type && 'publish' === $post->post_status ? evented_download_files($post_id) : array();
	if (!isset($files[$index])) { status_header(404); exit; }
	$file = $files[$index];
	update_post_meta($post_id, '__wpdm_download_count', absint(evented_wpdm_meta($post_id, array('__wpdm_download_count', '_wpdm_download_count'), 0)) + 1);
	if ($file['external']) {
		wp_redirect($file['external'], 302, 'Evented Downloads'); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}
	if (!$file['path'] || !is_readable($file['path'])) { status_header(404); exit; }
	while (ob_get_level()) { ob_end_clean(); }
	$mime = wp_check_filetype($file['path']);
	header('Content-Type: ' . ($mime['type'] ?: 'application/octet-stream'));
	header('Content-Disposition: attachment; filename="' . rawurlencode(basename($file['path'])) . '"');
	header('Content-Length: ' . (string) filesize($file['path']));
	header('X-Content-Type-Options: nosniff');
	readfile($file['path']); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	exit;
}
add_action('admin_post_evented_public_download', 'evented_serve_public_download');
add_action('admin_post_nopriv_evented_public_download', 'evented_serve_public_download');
