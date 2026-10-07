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

const EVENTED_DOWNLOAD_FILES_META = '_evented_download_files';

/** پاک‌سازی آرایهٔ واحد فایل‌ها برای REST، Gutenberg و ذخیرهٔ مدیریت. */
function evented_sanitize_download_files($value)
{
	if (is_string($value)) {
		$decoded = json_decode(wp_unslash($value), true);
		$value   = is_array($decoded) ? $decoded : array();
	}
	$output = array();
	foreach ((array) $value as $row) {
		if (!is_array($row)) { continue; }
		$attachment_id = absint($row['attachment_id'] ?? 0);
		$url = esc_url_raw(trim((string) ($row['url'] ?? '')));
		$legacy_path = sanitize_text_field((string) ($row['legacy_path'] ?? ''));
		if ($attachment_id && !get_post($attachment_id)) { $attachment_id = 0; }
		if (!$attachment_id && !wp_http_validate_url($url) && '' === $legacy_path) { continue; }
		$output[] = array(
			'title' => sanitize_text_field((string) ($row['title'] ?? '')),
			'attachment_id' => $attachment_id,
			'url' => wp_http_validate_url($url) ? $url : '',
			'legacy_path' => $legacy_path,
		);
	}
	return array_values($output);
}

/** ثبت متای canonical واحد؛ دانلود taxonomy ندارد ولی فایل‌هایش schema مشخص دارند. */
function evented_register_download_files_meta()
{
	register_post_meta('wpdmpro', EVENTED_DOWNLOAD_FILES_META, array(
		'type' => 'array', 'single' => true, 'default' => array(),
		'sanitize_callback' => 'evented_sanitize_download_files',
		'auth_callback' => static function ($allowed, $meta_key, $post_id) { return current_user_can('edit_post', (int) $post_id); },
		'show_in_rest' => array('schema' => array(
			'type' => 'array',
			'items' => array('type' => 'object', 'additionalProperties' => false, 'properties' => array(
				'title' => array('type' => 'string'),
				'attachment_id' => array('type' => 'integer'),
				'url' => array('type' => 'string'),
				'legacy_path' => array('type' => 'string'),
			)),
		)),
	));
}
add_action('init', 'evented_register_download_files_meta', 30);

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
	if (!post_type_supports('wpdmpro', 'custom-fields')) {
		add_post_type_support('wpdmpro', 'custom-fields');
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
add_action('wp_loaded', 'evented_detach_download_taxonomies', 100);

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
	$post_id  = (int) $post_id;
	$rows     = array();
	$canonical = evented_sanitize_download_files(get_post_meta($post_id, EVENTED_DOWNLOAD_FILES_META, true));
	if ($canonical) {
		foreach ($canonical as $row) {
			$attachment_path = $row['attachment_id'] ? (string) get_attached_file($row['attachment_id']) : '';
			$stored = $attachment_path ?: ($row['url'] ?: $row['legacy_path']);
			$rows[] = array('stored' => $stored, 'title' => $row['title']);
		}
	} else {
		$raw = evented_wpdm_meta($post_id, array('__wpdm_files', '_wpdm_files', '_wpdm_file', 'files'), array());
		$raw = maybe_unserialize($raw);
		if (!is_array($raw)) { $raw = '' !== (string) $raw ? array($raw) : array(); }
		$fileinfo = evented_wpdm_meta($post_id, array('__wpdm_fileinfo', '_wpdm_fileinfo'), array());
		$fileinfo = is_array($fileinfo) ? $fileinfo : array();
		foreach ($raw as $key => $stored) {
			if (is_array($stored) && isset($stored['file'])) { $stored = $stored['file']; }
			if (!is_scalar($stored) || '' === trim((string) $stored)) { continue; }
			$info = isset($fileinfo[$key]) && is_array($fileinfo[$key]) ? $fileinfo[$key] : array();
			$rows[] = array('stored' => trim((string) $stored), 'title' => (string) ($info['title'] ?? $info['label'] ?? ''));
		}
	}

	$files = array();
	foreach ($rows as $row) {
		$stored = $row['stored'];
		$external = preg_match('#^https?://#i', $stored) ? esc_url_raw($stored) : '';
		$path = $external ? '' : evented_download_local_path($stored);
		$basename = basename((string) wp_parse_url($external ?: $stored, PHP_URL_PATH));
		$label = sanitize_text_field($row['title'] ?: urldecode($basename));
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

/** تبدیل خروجی legacy reader به ساختار متای واحد جدید. */
function evented_download_canonical_rows($files)
{
	$rows = array();
	foreach ((array) $files as $file) {
		if (!is_array($file)) { continue; }
		$stored = (string) ($file['stored'] ?? '');
		$rows[] = array(
			'title' => sanitize_text_field((string) ($file['label'] ?? '')),
			'attachment_id' => 0,
			'url' => !empty($file['external']) ? esc_url_raw((string) $file['external']) : '',
			'legacy_path' => empty($file['external']) ? sanitize_text_field($stored) : '',
		);
	}
	return evented_sanitize_download_files($rows);
}

/** متاباکس سازگار با Gutenberg برای نگهداری تمام فایل‌ها در یک meta_value. */
function evented_add_download_files_meta_box()
{
	add_meta_box('evented-download-files', __('فایل‌های دانلود', 'evented-edu'), 'evented_render_download_files_meta_box', 'wpdmpro', 'normal', 'high', array('__block_editor_compatible_meta_box' => true));
}
add_action('add_meta_boxes_wpdmpro', 'evented_add_download_files_meta_box');

/** رندر یک ردیف فایل در مدیریت. */
function evented_download_admin_row($row = array(), $index = '__INDEX__')
{
	$row = wp_parse_args((array) $row, array('title' => '', 'attachment_id' => 0, 'url' => '', 'legacy_path' => ''));
	$name = 'evented_download_files[' . $index . ']';
	$file_name = $row['attachment_id'] ? basename((string) get_attached_file((int) $row['attachment_id'])) : basename((string) ($row['legacy_path'] ?: wp_parse_url($row['url'], PHP_URL_PATH)));
	?>
	<article class="ee-download-admin-row" data-ee-download-row>
		<header><span class="dashicons dashicons-media-default" aria-hidden="true"></span><strong data-ee-download-heading><?php echo esc_html($row['title'] ?: ($file_name ?: __('فایل جدید', 'evented-edu'))); ?></strong><div><button type="button" class="button-link" data-ee-download-up aria-label="انتقال به بالا"><span class="dashicons dashicons-arrow-up-alt2"></span></button><button type="button" class="button-link" data-ee-download-down aria-label="انتقال به پایین"><span class="dashicons dashicons-arrow-down-alt2"></span></button><button type="button" class="button-link-delete" data-ee-download-remove>حذف</button></div></header>
		<div class="ee-download-admin-fields">
			<label><span>عنوان فایل</span><input type="text" name="<?php echo esc_attr($name); ?>[title]" value="<?php echo esc_attr($row['title']); ?>" data-ee-download-title placeholder="مثلاً نسخه PDF"></label>
			<label><span>URL خارجی، در صورت نیاز</span><input type="url" dir="ltr" name="<?php echo esc_attr($name); ?>[url]" value="<?php echo esc_url($row['url']); ?>" placeholder="https://example.com/file.zip"></label>
			<input type="hidden" name="<?php echo esc_attr($name); ?>[attachment_id]" value="<?php echo (int) $row['attachment_id']; ?>" data-ee-download-attachment>
			<input type="hidden" name="<?php echo esc_attr($name); ?>[legacy_path]" value="<?php echo esc_attr($row['legacy_path']); ?>" data-ee-download-legacy>
			<div class="ee-download-admin-file"><span data-ee-download-file-name><?php echo esc_html($file_name ?: __('فایلی انتخاب نشده است', 'evented-edu')); ?></span><button type="button" class="button" data-ee-download-media>انتخاب از رسانه</button></div>
		</div>
	</article>
	<?php
}

/** رابط فایل‌ها؛ دادهٔ WPDM قدیمی تا زمان اولین ذخیره به‌صورت پیش‌نمایش وارد می‌شود. */
function evented_render_download_files_meta_box($post)
{
	$stored = evented_sanitize_download_files(get_post_meta($post->ID, EVENTED_DOWNLOAD_FILES_META, true));
	$legacy_preview = false;
	if (!$stored) {
		$stored = evented_download_canonical_rows(evented_download_files($post->ID));
		$legacy_preview = !empty($stored);
	}
	wp_nonce_field('evented_save_download_files', 'evented_download_files_nonce');
	?>
	<div class="ee-download-admin" data-ee-download-editor>
		<p class="description">هر تعداد فایل لازم است با دکمهٔ + اضافه کنید. همهٔ فایل‌ها با ترتیب فعلی در یک متای استاندارد ذخیره می‌شوند.</p>
		<?php if ($legacy_preview) : ?><div class="notice notice-info inline"><p>فایل‌های قدیمی WPDM بازیابی شده‌اند. با به‌روزرسانی نوشته، در فیلد استاندارد جدید ذخیره می‌شوند.</p></div><?php endif; ?>
		<div class="ee-download-admin-list" data-ee-download-list><?php foreach ($stored as $index => $row) { evented_download_admin_row($row, $index); } ?></div>
		<button type="button" class="button button-primary ee-download-admin-add" data-ee-download-add><span class="dashicons dashicons-plus-alt2"></span> افزودن فایل</button>
		<script type="text/html" data-ee-download-template><?php evented_download_admin_row(); ?></script>
	</div>
	<?php
}

/** ذخیرهٔ امن فایل‌های جدید در یک متا. */
function evented_save_download_files($post_id)
{
	if (!isset($_POST['evented_download_files_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['evented_download_files_nonce'])), 'evented_save_download_files')) { return; }
	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) { return; }
	$raw = isset($_POST['evented_download_files']) ? wp_unslash($_POST['evented_download_files']) : array();
	$files = evented_sanitize_download_files($raw);
	if ($files) { update_post_meta($post_id, EVENTED_DOWNLOAD_FILES_META, $files); }
	else { delete_post_meta($post_id, EVENTED_DOWNLOAD_FILES_META); }
}
add_action('save_post_wpdmpro', 'evented_save_download_files');

/** assetهای مدیریت فقط در ویرایش دانلود. */
function evented_enqueue_download_admin_assets($hook)
{
	$screen = get_current_screen();
	if (!$screen || 'wpdmpro' !== $screen->post_type || !in_array($hook, array('post.php', 'post-new.php'), true)) { return; }
	wp_enqueue_media();
	wp_enqueue_style('evented-download-admin', PATH_DIR_URL . '/assets/css/admin/download-files.css', array(), '1.0.0');
	wp_enqueue_script('evented-download-admin', PATH_DIR_URL . '/assets/js/admin/download-files.js', array(), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'evented_enqueue_download_admin_assets');

/** مهاجرت idempotent همهٔ packageهای قدیمی به فیلد canonical؛ دادهٔ WPDM حذف نمی‌شود. */
function evented_sync_download_files()
{
	$result = array('scanned' => 0, 'synced' => 0, 'skipped' => 0, 'empty' => 0);
	$ids = get_posts(array('post_type' => 'wpdmpro', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true));
	foreach ($ids as $post_id) {
		$result['scanned']++;
		if (get_post_meta($post_id, EVENTED_DOWNLOAD_FILES_META, true)) { $result['skipped']++; continue; }
		$rows = evented_download_canonical_rows(evented_download_files($post_id));
		if (!$rows) { $result['empty']++; continue; }
		update_post_meta($post_id, EVENTED_DOWNLOAD_FILES_META, $rows);
		$result['synced']++;
	}
	return $result;
}

function evented_register_download_sync_page()
{
	add_submenu_page('edit.php?post_type=wpdmpro', 'همگام‌سازی فایل‌ها', 'همگام‌سازی فایل‌ها', 'edit_others_posts', 'evented-download-sync', 'evented_render_download_sync_page');
}
add_action('admin_menu', 'evented_register_download_sync_page', 20);

function evented_render_download_sync_page()
{
	if (!current_user_can('edit_others_posts')) { wp_die(esc_html__('شما اجازهٔ اجرای این ابزار را ندارید.', 'evented-edu')); }
	$result = null;
	if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['evented_sync_downloads'])) {
		check_admin_referer('evented_sync_downloads');
		$result = evented_sync_download_files();
	}
	?>
	<div class="wrap"><h1>همگام‌سازی فایل‌های دانلود</h1><p>فایل‌های قدیمی `__wpdm_files` در متای واحد `_evented_download_files` ثبت می‌شوند. دادهٔ قبلی حذف یا بازنویسی نمی‌شود و اجرای دوباره امن است.</p><?php if ($result) : ?><div class="notice notice-success"><p><?php echo esc_html(sprintf('بررسی‌شده: %1$d — همگام‌شده: %2$d — قبلاً استاندارد: %3$d — بدون فایل: %4$d', $result['scanned'], $result['synced'], $result['skipped'], $result['empty'])); ?></p></div><?php endif; ?><form method="post"><?php wp_nonce_field('evented_sync_downloads'); ?><button type="submit" name="evented_sync_downloads" value="1" class="button button-primary button-hero">شروع همگام‌سازی امن</button></form></div>
	<?php
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
