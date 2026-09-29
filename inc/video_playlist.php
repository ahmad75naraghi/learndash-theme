<?php
/**
 * ویرایش، ذخیره و یکپارچه‌سازی پلی‌لیست‌های ویدئو.
 *
 * تمام قسمت‌های هر نوشتهٔ clip در یک متای استاندارد ذخیره می‌شوند:
 * `_evented_video_playlist` => array<array{title,description,video_url,thumbnail_id,thumbnail_url}>.
 *
 * @package evented-edu
 */
defined('ABSPATH') || exit;

const EVENTED_VIDEO_PLAYLIST_META = '_evented_video_playlist';

/** پاک‌سازی ساختار واحد پلی‌لیست پیش از ذخیره در دیتابیس یا REST. */
function evented_sanitize_video_playlist($value)
{
	if (is_string($value)) {
		$decoded = json_decode(wp_unslash($value), true);
		$value   = is_array($decoded) ? $decoded : array();
	}
	$output = array();
	foreach ((array) $value as $row) {
		if (!is_array($row)) {
			continue;
		}
		$url = esc_url_raw(trim((string) ($row['video_url'] ?? $row['url'] ?? '')));
		if (!in_array((string) wp_parse_url($url, PHP_URL_SCHEME), array('http', 'https'), true) || !wp_parse_url($url, PHP_URL_HOST)) {
			continue;
		}
		$thumbnail_id  = absint($row['thumbnail_id'] ?? 0);
		$thumbnail_url = esc_url_raw(trim((string) ($row['thumbnail_url'] ?? $row['poster'] ?? '')));
		if ($thumbnail_id) {
			$attachment_url = wp_get_attachment_image_url($thumbnail_id, 'medium');
			if ($attachment_url) {
				$thumbnail_url = $attachment_url;
			}
		}
		$output[] = array(
			'title'         => sanitize_text_field((string) ($row['title'] ?? '')),
			'description'   => sanitize_textarea_field((string) ($row['description'] ?? '')),
			'video_url'     => $url,
			'thumbnail_id'  => $thumbnail_id,
			'thumbnail_url' => $thumbnail_url,
		);
	}
	return array_values($output);
}

/** ثبت متای واحد برای دسترسی استاندارد در REST/Gutenberg. */
function evented_register_video_playlist_meta()
{
	register_post_meta('clip', EVENTED_VIDEO_PLAYLIST_META, array(
		'type'              => 'array',
		'single'            => true,
		'default'           => array(),
		'sanitize_callback' => 'evented_sanitize_video_playlist',
		'auth_callback'     => static function ($allowed, $meta_key, $post_id) {
			return current_user_can('edit_post', (int) $post_id);
		},
		'show_in_rest'      => array(
			'schema' => array(
				'type'    => 'array',
				'items'   => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'title'         => array('type' => 'string'),
						'description'   => array('type' => 'string'),
						'video_url'     => array('type' => 'string', 'format' => 'uri'),
						'thumbnail_id'  => array('type' => 'integer'),
						'thumbnail_url' => array('type' => 'string'),
					),
				),
			),
		),
	));
}
add_action('init', 'evented_register_video_playlist_meta', 30);

/** افزودن ویرایشگر قسمت‌ها به صفحهٔ ویرایش ویدئو (سازگار با Gutenberg). */
function evented_add_video_playlist_meta_box()
{
	add_meta_box(
		'evented-video-playlist',
		__('قسمت‌های ویدئو', 'evented-edu'),
		'evented_render_video_playlist_meta_box',
		'clip',
		'normal',
		'high',
		array('__block_editor_compatible_meta_box' => true)
	);
}
add_action('add_meta_boxes_clip', 'evented_add_video_playlist_meta_box');

/** HTML یک ردیف از ویرایشگر پلی‌لیست. */
function evented_video_playlist_admin_row($row = array(), $index = '__INDEX__')
{
	$row = wp_parse_args((array) $row, array(
		'title' => '', 'description' => '', 'video_url' => '', 'thumbnail_id' => 0, 'thumbnail_url' => '',
	));
	$name  = 'evented_video_playlist[' . $index . ']';
	$image = $row['thumbnail_id'] ? wp_get_attachment_image_url((int) $row['thumbnail_id'], 'medium') : $row['thumbnail_url'];
	?>
	<article class="ee-video-admin-row" data-ee-video-row>
		<header>
			<span class="ee-video-admin-handle dashicons dashicons-move" aria-hidden="true"></span>
			<strong data-ee-video-row-title><?php echo esc_html($row['title'] ?: __('قسمت جدید', 'evented-edu')); ?></strong>
			<div class="ee-video-admin-order">
				<button type="button" class="button-link" data-ee-video-up aria-label="<?php esc_attr_e('انتقال به بالا', 'evented-edu'); ?>"><span class="dashicons dashicons-arrow-up-alt2"></span></button>
				<button type="button" class="button-link" data-ee-video-down aria-label="<?php esc_attr_e('انتقال به پایین', 'evented-edu'); ?>"><span class="dashicons dashicons-arrow-down-alt2"></span></button>
				<button type="button" class="button-link-delete" data-ee-video-remove><?php esc_html_e('حذف', 'evented-edu'); ?></button>
			</div>
		</header>
		<div class="ee-video-admin-fields">
			<div class="ee-video-admin-thumb">
				<div class="ee-video-admin-preview" data-ee-video-preview><?php if ($image) : ?><img src="<?php echo esc_url($image); ?>" alt=""><?php else : ?><span class="dashicons dashicons-format-video"></span><?php endif; ?></div>
				<input type="hidden" name="<?php echo esc_attr($name); ?>[thumbnail_id]" value="<?php echo esc_attr((int) $row['thumbnail_id']); ?>" data-ee-video-thumb-id>
				<input type="hidden" name="<?php echo esc_attr($name); ?>[thumbnail_url]" value="<?php echo esc_url($row['thumbnail_url']); ?>" data-ee-video-thumb-url>
				<button type="button" class="button" data-ee-video-media><?php esc_html_e('انتخاب تصویر', 'evented-edu'); ?></button>
			</div>
			<div class="ee-video-admin-main">
				<label><span><?php esc_html_e('عنوان قسمت', 'evented-edu'); ?></span><input type="text" name="<?php echo esc_attr($name); ?>[title]" value="<?php echo esc_attr($row['title']); ?>" data-ee-video-title placeholder="مثلاً قسمت اول"></label>
				<label><span><?php esc_html_e('آدرس فایل ویدئو', 'evented-edu'); ?></span><input type="url" dir="ltr" name="<?php echo esc_attr($name); ?>[video_url]" value="<?php echo esc_url($row['video_url']); ?>" placeholder="https://example.com/video.mp4"></label>
				<label class="ee-video-admin-description"><span><?php esc_html_e('متن کوتاه', 'evented-edu'); ?></span><textarea name="<?php echo esc_attr($name); ?>[description]" rows="2" placeholder="توضیحی که زیر عنوان این قسمت نمایش داده می‌شود"><?php echo esc_textarea($row['description']); ?></textarea></label>
			</div>
		</div>
	</article>
	<?php
}

/** رندر متاباکس. */
function evented_render_video_playlist_meta_box($post)
{
	$stored   = get_post_meta($post->ID, EVENTED_VIDEO_PLAYLIST_META, true);
	$playlist = evented_sanitize_video_playlist($stored);
	$legacy_preview = false;
	if (!$playlist && function_exists('evented_clip_playlist')) {
		$playlist = evented_sanitize_video_playlist(evented_clip_playlist($post->ID));
		$legacy_preview = !empty($playlist);
	}
	wp_nonce_field('evented_save_video_playlist', 'evented_video_playlist_nonce');
	?>
	<div class="ee-video-admin" data-ee-video-editor>
		<p class="description"><?php esc_html_e('هر تعداد قسمت لازم است اضافه کنید. همهٔ قسمت‌ها با ترتیب فعلی در یک متا ذخیره می‌شوند و در صفحهٔ عمومی زیر پخش‌کننده نمایش داده خواهند شد.', 'evented-edu'); ?></p>
		<?php if ($legacy_preview) : ?><div class="notice notice-info inline"><p><?php esc_html_e('قسمت‌های قدیمی Elementor بازیابی شده‌اند. با به‌روزرسانی نوشته، همین فهرست در ساختار استاندارد جدید ذخیره می‌شود.', 'evented-edu'); ?></p></div><?php endif; ?>
		<div class="ee-video-admin-list" data-ee-video-list>
			<?php foreach ($playlist as $index => $row) { evented_video_playlist_admin_row($row, $index); } ?>
		</div>
		<button type="button" class="button button-primary ee-video-admin-add" data-ee-video-add><span class="dashicons dashicons-plus-alt2"></span><?php esc_html_e('افزودن ویدئو', 'evented-edu'); ?></button>
		<script type="text/html" data-ee-video-template><?php evented_video_playlist_admin_row(); ?></script>
	</div>
	<?php
}

/** ذخیرهٔ همهٔ ردیف‌ها در یک meta_value. */
function evented_save_video_playlist($post_id)
{
	if (!isset($_POST['evented_video_playlist_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['evented_video_playlist_nonce'])), 'evented_save_video_playlist')) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
		return;
	}
	$raw      = isset($_POST['evented_video_playlist']) ? wp_unslash($_POST['evented_video_playlist']) : array();
	$playlist = evented_sanitize_video_playlist($raw);
	if ($playlist) {
		update_post_meta($post_id, EVENTED_VIDEO_PLAYLIST_META, $playlist);
	} else {
		delete_post_meta($post_id, EVENTED_VIDEO_PLAYLIST_META);
	}
}
add_action('save_post_clip', 'evented_save_video_playlist');

/** دارایی‌های ویرایشگر فقط در صفحهٔ clip بارگذاری می‌شوند. */
function evented_enqueue_video_playlist_admin_assets($hook)
{
	$screen = get_current_screen();
	if (!$screen || 'clip' !== $screen->post_type || !in_array($hook, array('post.php', 'post-new.php'), true)) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style('evented-video-admin', PATH_DIR_URL . '/assets/css/admin/video-playlist.css', array(), '1.0.0');
	wp_enqueue_script('evented-video-admin', PATH_DIR_URL . '/assets/js/admin/video-playlist.js', array(), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'evented_enqueue_video_playlist_admin_assets');

/** ابزار یک‌بارهٔ تبدیل داده‌های Elementor/قدیمی به متای استاندارد. */
function evented_register_video_playlist_migration_page()
{
	add_submenu_page(
		'edit.php?post_type=clip',
		__('یکپارچه‌سازی پلی‌لیست‌ها', 'evented-edu'),
		__('یکپارچه‌سازی پلی‌لیست‌ها', 'evented-edu'),
		'edit_others_posts',
		'evented-video-migration',
		'evented_render_video_playlist_migration_page'
	);
}
add_action('admin_menu', 'evented_register_video_playlist_migration_page');

/** اجرای مهاجرت idempotent؛ متای استاندارد موجود هرگز بازنویسی نمی‌شود. */
function evented_migrate_video_playlists()
{
	$result = array('scanned' => 0, 'migrated' => 0, 'skipped' => 0, 'empty' => 0);
	$ids = get_posts(array(
		'post_type' => 'clip', 'post_status' => 'any', 'posts_per_page' => -1,
		'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true,
	));
	foreach ($ids as $post_id) {
		$result['scanned']++;
		if (get_post_meta($post_id, EVENTED_VIDEO_PLAYLIST_META, true)) {
			$result['skipped']++;
			continue;
		}
		$playlist = function_exists('evented_clip_playlist') ? evented_clip_playlist($post_id) : array();
		$playlist = evented_sanitize_video_playlist($playlist);
		if (!$playlist) {
			$result['empty']++;
			continue;
		}
		update_post_meta($post_id, EVENTED_VIDEO_PLAYLIST_META, $playlist);
		$result['migrated']++;
	}
	return $result;
}

/** صفحهٔ مدیریت مهاجرت با گزارش روشن و nonce. */
function evented_render_video_playlist_migration_page()
{
	if (!current_user_can('edit_others_posts')) {
		wp_die(esc_html__('شما اجازهٔ اجرای این ابزار را ندارید.', 'evented-edu'));
	}
	$result = null;
	if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['evented_migrate_video_playlists'])) {
		check_admin_referer('evented_migrate_video_playlists');
		$result = evented_migrate_video_playlists();
	}
	?>
	<div class="wrap"><h1><?php esc_html_e('یکپارچه‌سازی پلی‌لیست‌های ویدئو', 'evented-edu'); ?></h1>
		<p><?php esc_html_e('این ابزار قسمت‌های ذخیره‌شده در Video Playlist المنتور و متاهای قدیمی را به متای واحد _evented_video_playlist منتقل می‌کند. داده‌های قبلی حذف نمی‌شوند و پلی‌لیست استاندارد موجود بازنویسی نخواهد شد.', 'evented-edu'); ?></p>
		<?php if ($result) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html(sprintf(__('بررسی‌شده: %1$d — منتقل‌شده: %2$d — قبلاً استاندارد: %3$d — بدون ویدئو: %4$d', 'evented-edu'), $result['scanned'], $result['migrated'], $result['skipped'], $result['empty'])); ?></p></div><?php endif; ?>
		<form method="post"><?php wp_nonce_field('evented_migrate_video_playlists'); ?><p><button type="submit" name="evented_migrate_video_playlists" value="1" class="button button-primary button-hero"><?php esc_html_e('شروع یکپارچه‌سازی امن', 'evented-edu'); ?></button></p></form>
	</div>
	<?php
}
