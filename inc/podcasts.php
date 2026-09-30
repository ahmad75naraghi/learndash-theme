<?php
/**
 * سازگاری غیرمخرب با پادکست‌های قدیمی Sonaar.
 *
 * داده‌های اصلی در post type برابر sr_playlist و متای serializeشدهٔ
 * alb_tracklist باقی می‌مانند؛ قالب فقط آن‌ها را ثبت و برای خروجی عمومی می‌خواند.
 *
 * @package evented-edu
 */
defined('ABSPATH') || exit;

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
			'supports'            => array('title', 'editor', 'excerpt', 'thumbnail'),
			'taxonomies'          => array('playlist-category', 'playlist-tag'),
		));
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
function evented_podcast_tracks($post_id)
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
	return apply_filters('evented_podcast_tracks', $tracks, (int) $post_id, $raw);
}

/** تعداد ترک‌ها برای کارت‌های آرشیو. */
function evented_podcast_track_count($post_id)
{
	return count(evented_podcast_tracks((int) $post_id));
}

/** rewrite جدید فقط یک بار و بدون تغییر داده‌های قدیمی بازسازی می‌شود. */
function evented_podcast_maybe_flush_rewrite_rules()
{
	if ('1' === (string) get_option('evented_podcast_rewrite_version', '')) { return; }
	flush_rewrite_rules(false);
	update_option('evented_podcast_rewrite_version', '1', false);
}
add_action('init', 'evented_podcast_maybe_flush_rewrite_rules', 100);
