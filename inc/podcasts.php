<?php
/**
 * سازگاری غیرمخرب با پادکست‌های قدیمی Sonaar.
 *
 * داده‌های جدید در `_evented_podcast_tracks` ذخیره می‌شوند و داده‌های قدیمی
 * `alb_tracklist` در همان post type برابر `sr_playlist` بدون حذف یا بازنویسی
 * به‌عنوان fallback خوانده و با ابزار مدیریت به ساختار canonical همگام می‌شوند.
 *
 * @package evented-edu
 */
defined('ABSPATH') || exit;

const EVENTED_PODCAST_TRACKS_META = '_evented_podcast_tracks';

/** پاک‌سازی ساختار استاندارد فایل‌های صوتی پادکست. */
function evented_sanitize_podcast_tracks($value)
{
	if (is_string($value)) {
		$decoded = json_decode(wp_unslash($value), true);
		$value   = is_array($decoded) ? $decoded : array();
	}
	$output = array();
	foreach ((array) $value as $row) {
		if (!is_array($row)) { continue; }
		$attachment_id = absint($row['attachment_id'] ?? 0);
		if ($attachment_id && !get_post($attachment_id)) { $attachment_id = 0; }
		$audio_url = esc_url_raw(trim((string) ($row['audio_url'] ?? $row['url'] ?? '')));
		if (!wp_http_validate_url($audio_url)) { $audio_url = ''; }
		if (!$attachment_id && !$audio_url) { continue; }
		$output[] = array(
			'title'         => sanitize_text_field((string) ($row['title'] ?? '')),
			'artist'        => sanitize_text_field((string) ($row['artist'] ?? '')),
			'description'   => sanitize_textarea_field((string) ($row['description'] ?? '')),
			'attachment_id' => $attachment_id,
			'audio_url'     => $audio_url,
		);
	}
	return array_values($output);
}

/** ثبت متای canonical برای REST، Gutenberg و ویرایشگر مدیریت. */
function evented_register_podcast_tracks_meta()
{
	register_post_meta('sr_playlist', EVENTED_PODCAST_TRACKS_META, array(
		'type' => 'array', 'single' => true, 'default' => array(),
		'sanitize_callback' => 'evented_sanitize_podcast_tracks',
		'auth_callback' => static function ($allowed, $meta_key, $post_id) { return current_user_can('edit_post', (int) $post_id); },
		'show_in_rest' => array('schema' => array(
			'type' => 'array',
			'items' => array('type' => 'object', 'additionalProperties' => false, 'properties' => array(
				'title' => array('type' => 'string'),
				'artist' => array('type' => 'string'),
				'description' => array('type' => 'string'),
				'attachment_id' => array('type' => 'integer'),
				'audio_url' => array('type' => 'string'),
			)),
		)),
	));
}
add_action('init', 'evented_register_podcast_tracks_meta', 30);

/** ثبت runtime داده‌های Sonaar وقتی خود افزونه فعال نیست. */
function evented_register_legacy_podcasts()
{
	if (!taxonomy_exists('playlist-category')) {
		register_taxonomy('playlist-category', array('sr_playlist'), array(
			'labels' => array(
				'name'          => 'دسته‌های پادکست',
				'singular_name' => 'دستهٔ پادکست',
				'all_items'     => 'همهٔ دسته‌ها',
				'edit_item'     => 'ویرایش دسته',
				'add_new_item'  => 'افزودن دسته',
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array('slug' => 'podcast-category', 'with_front' => true),
		));
	}

	if (!taxonomy_exists('playlist-tag')) {
		register_taxonomy('playlist-tag', array('sr_playlist'), array(
			'labels' => array('name' => 'برچسب‌های پادکست', 'singular_name' => 'برچسب پادکست'),
			'public'       => true,
			'hierarchical' => false,
			'show_ui'      => true,
			'show_in_rest' => true,
			'rewrite'      => array('slug' => 'podcast-tag', 'with_front' => true),
		));
	}

	if (!post_type_exists('sr_playlist')) {
		register_post_type('sr_playlist', array(
			'label'               => 'پادکست‌ها',
			'labels'              => function_exists('evented_content_type_labels') ? evented_content_type_labels('پادکست‌ها', 'پادکست') : array('name' => 'پادکست‌ها', 'singular_name' => 'پادکست'),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_rest'        => true,
			'exclude_from_search' => false,
			'delete_with_user'    => false,
			'has_archive'         => false,
			'rewrite'             => array('slug' => 'podcast-episode', 'with_front' => true),
			'query_var'           => true,
			'menu_icon'           => 'dashicons-microphone',
			'supports'            => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
			'taxonomies'          => array('playlist-category', 'playlist-tag'),
		));
	}

	if (!post_type_supports('sr_playlist', 'custom-fields')) {
		add_post_type_support('sr_playlist', 'custom-fields');
	}

	foreach (array('playlist-category', 'playlist-tag') as $taxonomy) {
		if (taxonomy_exists($taxonomy) && !is_object_in_taxonomy('sr_playlist', $taxonomy)) {
			register_taxonomy_for_object_type($taxonomy, 'sr_playlist');
		}
	}
}
add_action('init', 'evented_register_legacy_podcasts', 25);

/**
 * نخستین URL قابل استفاده را از مقدار تو‌در‌توی Sonaar پیدا می‌کند.
 *
 * @param mixed $value مقدار فیلد.
 * @param bool  $allow_stream پذیرش URL بدون پسوند برای stream.
 * @return string
 */
function evented_podcast_media_url($value, $allow_stream = false)
{
	if (is_numeric($value)) {
		$url = wp_get_attachment_url((int) $value);
		return $url ? (string) $url : '';
	}
	if (is_string($value)) {
		$value = trim($value);
		if (0 === strpos($value, '/')) { $value = home_url($value); }
		if (!wp_http_validate_url($value)) { return ''; }
		if ($allow_stream || preg_match('/\.(?:mp3|m4a|aac|wav|ogg|oga|opus|flac)(?:[?#].*)?$/i', $value)) {
			return esc_url_raw($value);
		}
		return '';
	}
	if (is_array($value)) {
		foreach (array('url', 'src', 'file', 'id', 'attachment_id') as $key) {
			if (isset($value[$key])) {
				$url = evented_podcast_media_url($value[$key], $allow_stream);
				if ($url) { return $url; }
			}
		}
		foreach ($value as $child) {
			$url = evented_podcast_media_url($child, $allow_stream);
			if ($url) { return $url; }
		}
	}
	return '';
}

/**
 * آرایهٔ ترک‌های Sonaar را به ساختار پایدار قالب تبدیل می‌کند.
 *
 * @return array<int,array{title:string,url:string,description:string,artist:string}>
 */
function evented_legacy_podcast_tracks($post_id)
{
	$raw = get_post_meta((int) $post_id, 'alb_tracklist', true);
	if (!is_array($raw)) { return array(); }

	$tracks = array();
	foreach ($raw as $index => $item) {
		if (!is_array($item)) { continue; }
		$url = '';
		foreach (array('stream_link', 'stream_url', 'track_stream', 'audio_url', 'track_mp3', 'mp3', 'file', 'url') as $key) {
			if (!isset($item[$key])) { continue; }
			$url = evented_podcast_media_url($item[$key], in_array($key, array('stream_link', 'stream_url', 'track_stream'), true));
			if ($url) { break; }
		}
		/* نسخه‌های مختلف Sonaar نام فیلد متفاوت دارند؛ کلیدهای رسانه‌ای ناشناخته نیز پوشش داده می‌شوند. */
		if (!$url) {
			foreach ($item as $key => $value) {
				if (!is_string($key) || preg_match('/(?:image|img|artwork|cover|poster)/i', $key) || !preg_match('/(?:stream|audio|mp3|source|file|url)/i', $key)) { continue; }
				$url = evented_podcast_media_url($value, true);
				if ($url) { break; }
			}
		}
		/* fallback فقط فایل صوتی با پسوند معتبر؛ تا تصویر کاور به‌اشتباه پخش نشود. */
		if (!$url) { $url = evented_podcast_media_url($item, false); }
		if (!$url) { continue; }

		$title = '';
		foreach (array('track_title', 'stream_title', 'song_title', 'title', 'name') as $key) {
			if (isset($item[$key]) && is_scalar($item[$key]) && '' !== trim((string) $item[$key])) {
				$title = sanitize_text_field((string) $item[$key]);
				break;
			}
		}
		if (!$title) { $title = sprintf('قسمت %s', number_format_i18n((int) $index + 1)); }

		$description = '';
		foreach (array('track_description', 'description', 'track_desc') as $key) {
			if (isset($item[$key]) && is_scalar($item[$key])) { $description = sanitize_textarea_field((string) $item[$key]); break; }
		}
		$artist = '';
		foreach (array('track_artist', 'artist', 'stream_artist') as $key) {
			if (isset($item[$key]) && is_scalar($item[$key])) { $artist = sanitize_text_field((string) $item[$key]); break; }
		}
		$tracks[] = array('title' => $title, 'url' => $url, 'description' => $description, 'artist' => $artist);
	}
	return apply_filters('evented_legacy_podcast_tracks', $tracks, (int) $post_id, $raw);
}

/**
 * خواندن قسمت‌ها با اولویت متای canonical و fallback غیرمخرب به Sonaar.
 *
 * @return array<int,array{title:string,url:string,description:string,artist:string}>
 */
function evented_podcast_tracks($post_id)
{
	$post_id   = (int) $post_id;
	$canonical = evented_sanitize_podcast_tracks(get_post_meta($post_id, EVENTED_PODCAST_TRACKS_META, true));
	$tracks    = array();
	if ($canonical) {
		foreach ($canonical as $index => $row) {
			$url = $row['attachment_id'] ? (string) wp_get_attachment_url($row['attachment_id']) : (string) $row['audio_url'];
			if (!$url) { continue; }
			$tracks[] = array(
				'title' => $row['title'] ?: sprintf('قسمت %s', number_format_i18n($index + 1)),
				'url' => $url,
				'description' => $row['description'],
				'artist' => $row['artist'],
			);
		}
	} else {
		$tracks = evented_legacy_podcast_tracks($post_id);
	}
	return (array) apply_filters('evented_podcast_tracks', $tracks, $post_id);
}

/** تبدیل reader قدیمی Sonaar به ساختار استاندارد جدید. */
function evented_podcast_canonical_rows($tracks)
{
	$rows = array();
	foreach ((array) $tracks as $track) {
		if (!is_array($track) || empty($track['url'])) { continue; }
		$attachment_id = attachment_url_to_postid((string) $track['url']);
		$rows[] = array(
			'title' => (string) ($track['title'] ?? ''),
			'artist' => (string) ($track['artist'] ?? ''),
			'description' => (string) ($track['description'] ?? ''),
			'attachment_id' => $attachment_id,
			'audio_url' => $attachment_id ? '' : (string) $track['url'],
		);
	}
	return evented_sanitize_podcast_tracks($rows);
}

/** تعداد ترک‌ها برای کارت‌های آرشیو. */
function evented_podcast_track_count($post_id)
{
	return count(evented_podcast_tracks((int) $post_id));
}

/** افزودن ویرایشگر قسمت‌های صوتی به صفحهٔ پادکست. */
function evented_add_podcast_tracks_meta_box()
{
	add_meta_box('evented-podcast-tracks', __('قسمت‌های صوتی', 'evented-edu'), 'evented_render_podcast_tracks_meta_box', 'sr_playlist', 'normal', 'high', array('__block_editor_compatible_meta_box' => true));
}
add_action('add_meta_boxes_sr_playlist', 'evented_add_podcast_tracks_meta_box');

/** HTML یک ردیف از ویرایشگر قسمت‌های صوتی. */
function evented_podcast_admin_row($row = array(), $index = '__INDEX__')
{
	$row  = wp_parse_args((array) $row, array('title' => '', 'artist' => '', 'description' => '', 'attachment_id' => 0, 'audio_url' => ''));
	$name = 'evented_podcast_tracks[' . $index . ']';
	$file = $row['attachment_id'] ? basename((string) get_attached_file((int) $row['attachment_id'])) : basename((string) wp_parse_url($row['audio_url'], PHP_URL_PATH));
	?>
	<article class="ee-podcast-admin-row" data-ee-podcast-row>
		<header><span class="dashicons dashicons-microphone ee-podcast-admin-handle" aria-hidden="true"></span><strong data-ee-podcast-heading><?php echo esc_html($row['title'] ?: ($file ?: __('قسمت جدید', 'evented-edu'))); ?></strong><div><button type="button" class="button-link" data-ee-podcast-up aria-label="انتقال به بالا"><span class="dashicons dashicons-arrow-up-alt2"></span></button><button type="button" class="button-link" data-ee-podcast-down aria-label="انتقال به پایین"><span class="dashicons dashicons-arrow-down-alt2"></span></button><button type="button" class="button-link-delete" data-ee-podcast-remove><?php esc_html_e('حذف', 'evented-edu'); ?></button></div></header>
		<div class="ee-podcast-admin-fields">
			<label><span><?php esc_html_e('عنوان قسمت', 'evented-edu'); ?></span><input type="text" name="<?php echo esc_attr($name); ?>[title]" value="<?php echo esc_attr($row['title']); ?>" data-ee-podcast-title placeholder="مثلاً قسمت اول"></label>
			<label><span><?php esc_html_e('نام گوینده یا مهمان', 'evented-edu'); ?></span><input type="text" name="<?php echo esc_attr($name); ?>[artist]" value="<?php echo esc_attr($row['artist']); ?>" placeholder="اختیاری"></label>
			<label class="ee-podcast-admin-url"><span><?php esc_html_e('URL فایل صوتی یا stream', 'evented-edu'); ?></span><input type="url" dir="ltr" name="<?php echo esc_attr($name); ?>[audio_url]" value="<?php echo esc_url($row['audio_url']); ?>" data-ee-podcast-url placeholder="https://example.com/audio.mp3"></label>
			<input type="hidden" name="<?php echo esc_attr($name); ?>[attachment_id]" value="<?php echo (int) $row['attachment_id']; ?>" data-ee-podcast-attachment>
			<div class="ee-podcast-admin-file"><span data-ee-podcast-file><?php echo esc_html($file ?: __('فایلی انتخاب نشده است', 'evented-edu')); ?></span><button type="button" class="button" data-ee-podcast-media><?php esc_html_e('انتخاب فایل صوتی از رسانه', 'evented-edu'); ?></button></div>
			<label class="ee-podcast-admin-description"><span><?php esc_html_e('توضیح کوتاه قسمت', 'evented-edu'); ?></span><textarea name="<?php echo esc_attr($name); ?>[description]" rows="2"><?php echo esc_textarea($row['description']); ?></textarea></label>
		</div>
	</article>
	<?php
}

/** متاباکس؛ دادهٔ Sonaar تا اولین ذخیره فقط به‌صورت پیش‌نمایش بازیابی می‌شود. */
function evented_render_podcast_tracks_meta_box($post)
{
	$tracks = evented_sanitize_podcast_tracks(get_post_meta($post->ID, EVENTED_PODCAST_TRACKS_META, true));
	$legacy_preview = false;
	if (!$tracks) {
		$tracks = evented_podcast_canonical_rows(evented_legacy_podcast_tracks($post->ID));
		$legacy_preview = !empty($tracks);
	}
	wp_nonce_field('evented_save_podcast_tracks', 'evented_podcast_tracks_nonce');
	?>
	<div class="ee-podcast-admin" data-ee-podcast-editor>
		<p class="description"><?php esc_html_e('هر تعداد فایل صوتی لازم است اضافه کنید، ترتیب را تغییر دهید و برای هر قسمت عنوان، گوینده، URL و توضیح ثبت کنید.', 'evented-edu'); ?></p>
		<?php if ($legacy_preview) : ?><div class="notice notice-info inline"><p><?php esc_html_e('قسمت‌های قدیمی Sonaar بازیابی شده‌اند. با به‌روزرسانی نوشته، همین فهرست در متای استاندارد جدید ذخیره می‌شود و دادهٔ قدیمی دست‌نخورده باقی می‌ماند.', 'evented-edu'); ?></p></div><?php endif; ?>
		<div class="ee-podcast-admin-list" data-ee-podcast-list><?php foreach ($tracks as $index => $row) { evented_podcast_admin_row($row, $index); } ?></div>
		<button type="button" class="button button-primary ee-podcast-admin-add" data-ee-podcast-add><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('افزودن فایل صوتی', 'evented-edu'); ?></button>
		<script type="text/html" data-ee-podcast-template><?php evented_podcast_admin_row(); ?></script>
	</div>
	<?php
}

/** ذخیرهٔ امن همهٔ قسمت‌ها در متای canonical واحد. */
function evented_save_podcast_tracks($post_id)
{
	if (!isset($_POST['evented_podcast_tracks_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['evented_podcast_tracks_nonce'])), 'evented_save_podcast_tracks')) { return; }
	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) { return; }
	$raw = isset($_POST['evented_podcast_tracks']) ? wp_unslash($_POST['evented_podcast_tracks']) : array();
	$tracks = evented_sanitize_podcast_tracks($raw);
	if ($tracks) { update_post_meta($post_id, EVENTED_PODCAST_TRACKS_META, $tracks); }
	else { delete_post_meta($post_id, EVENTED_PODCAST_TRACKS_META); }
}
add_action('save_post_sr_playlist', 'evented_save_podcast_tracks');

/** assetهای ویرایشگر فقط روی پست‌تایپ پادکست. */
function evented_enqueue_podcast_admin_assets($hook)
{
	$screen = get_current_screen();
	if (!$screen || 'sr_playlist' !== $screen->post_type || !in_array($hook, array('post.php', 'post-new.php'), true)) { return; }
	wp_enqueue_media();
	wp_enqueue_style('evented-podcast-admin', PATH_DIR_URL . '/assets/css/admin/podcast-tracks.css', array(), '1.0.0');
	wp_enqueue_script('evented-podcast-admin', PATH_DIR_URL . '/assets/js/admin/podcast-tracks.js', array(), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'evented_enqueue_podcast_admin_assets');

/** اجرای همگام‌سازی idempotent Sonaar به متای canonical؛ منبع هرگز حذف نمی‌شود. */
function evented_sync_podcast_tracks()
{
	$result = array('scanned' => 0, 'migrated' => 0, 'skipped' => 0, 'empty' => 0);
	$ids = get_posts(array('post_type' => 'sr_playlist', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true));
	foreach ($ids as $post_id) {
		$result['scanned']++;
		if (evented_sanitize_podcast_tracks(get_post_meta($post_id, EVENTED_PODCAST_TRACKS_META, true))) { $result['skipped']++; continue; }
		$tracks = evented_podcast_canonical_rows(evented_legacy_podcast_tracks($post_id));
		if (!$tracks) { $result['empty']++; continue; }
		update_post_meta($post_id, EVENTED_PODCAST_TRACKS_META, $tracks);
		$result['migrated']++;
	}
	return $result;
}

/** زیرمنوی ابزار همگام‌سازی. */
function evented_register_podcast_sync_page()
{
	add_submenu_page('edit.php?post_type=sr_playlist', __('همگام‌سازی قسمت‌ها', 'evented-edu'), __('همگام‌سازی قسمت‌ها', 'evented-edu'), 'edit_others_posts', 'evented-podcast-sync', 'evented_render_podcast_sync_page');
}
add_action('admin_menu', 'evented_register_podcast_sync_page');

/** صفحهٔ امن اجرای همگام‌سازی Sonaar. */
function evented_render_podcast_sync_page()
{
	if (!current_user_can('edit_others_posts')) { wp_die(esc_html__('شما اجازهٔ اجرای این ابزار را ندارید.', 'evented-edu')); }
	$result = null;
	if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['evented_sync_podcasts'])) {
		check_admin_referer('evented_sync_podcast_tracks');
		$result = evented_sync_podcast_tracks();
	}
	?>
	<div class="wrap"><h1><?php esc_html_e('همگام‌سازی فایل‌های صوتی پادکست', 'evented-edu'); ?></h1>
		<p><?php esc_html_e('این ابزار alb_tracklist افزونهٔ Sonaar را به متای استاندارد _evented_podcast_tracks منتقل می‌کند. دادهٔ Sonaar حذف یا بازنویسی نمی‌شود و پادکست‌های قبلاً همگام‌شده نیز دست‌نخورده می‌مانند.', 'evented-edu'); ?></p>
		<?php if ($result) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html(sprintf(__('بررسی‌شده: %1$d — همگام‌شده: %2$d — قبلاً استاندارد: %3$d — بدون فایل صوتی: %4$d', 'evented-edu'), $result['scanned'], $result['migrated'], $result['skipped'], $result['empty'])); ?></p></div><?php endif; ?>
		<form method="post"><?php wp_nonce_field('evented_sync_podcast_tracks'); ?><p><button type="submit" name="evented_sync_podcasts" value="1" class="button button-primary button-hero"><?php esc_html_e('شروع همگام‌سازی امن', 'evented-edu'); ?></button></p></form>
	</div>
	<?php
}

/** rewrite جدید فقط یک بار و بدون تغییر داده‌های قدیمی بازسازی می‌شود. */
function evented_podcast_maybe_flush_rewrite_rules()
{
	if ('1' === (string) get_option('evented_podcast_rewrite_version', '')) { return; }
	flush_rewrite_rules(false);
	update_option('evented_podcast_rewrite_version', '1', false);
}
add_action('init', 'evented_podcast_maybe_flush_rewrite_rules', 100);
