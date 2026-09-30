<?php
/**
 * Template Name: پادکست‌ها
 * Template Post Type: page
 *
 * برگهٔ مستقل /podcast/؛ عمداً محتوای قدیمی برگه/Elementor را اجرا نمی‌کند.
 * اگر افزونه‌ای یک post type عمومی پادکست ثبت کرده باشد، همان داده‌ها نمایش
 * داده می‌شوند و در غیر این صورت صفحه حالت خالیِ صحیح نشان می‌دهد.
 *
 * @package evented-edu
 */
defined('ABSPATH') || exit;

$ee_podcast_type = '';
$ee_candidates   = array('podcast', 'podcasts', 'episode', 'episodes', 'audio', 'seriously-simple-podcasting');
$ee_candidates   = (array) apply_filters('evented_podcast_post_type_candidates', $ee_candidates);

foreach ($ee_candidates as $ee_candidate) {
	$ee_candidate = sanitize_key($ee_candidate);
	if (!$ee_candidate || !post_type_exists($ee_candidate)) {
		continue;
	}

	$ee_object = get_post_type_object($ee_candidate);
	if ($ee_object && !empty($ee_object->publicly_queryable)) {
		$ee_podcast_type = $ee_candidate;
		break;
	}
}

get_template_part('template-parts/ee-resource', 'page', array(
	'post_type'  => $ee_podcast_type,
	'active'     => 'podcast',
	'title'      => 'پادکست‌ها',
	'label'      => 'پادکست',
	'subtitle'   => 'مجموعه برنامه‌های صوتی و پادکست‌های آموزشی شمیم را بشنوید',
	'intro'      => '',
	'taxonomy'   => '',
	'icon'       => 'podcasts',
	'modifier'   => 'podcast',
	'empty_title' => 'هنوز پادکستی منتشر نشده است',
	'empty_text'  => 'به‌محض انتشار برنامه‌های صوتی، از همین صفحه در دسترس خواهند بود.',
));
