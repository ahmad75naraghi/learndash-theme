<?php
/**
 * Template Name: ویدئوها
 * Template Post Type: page
 * @package evented-edu
 */
defined('ABSPATH') || exit;
get_template_part('template-parts/ee-resource', 'page', array(
	'post_type' => 'clip', 'active' => 'video', 'title' => 'ویدئوها', 'label' => 'ویدئو',
	'subtitle' => 'مجموعه ویدئوهای آموزشی، فرهنگی و رسانه‌ای را تماشا کنید',
	'taxonomy' => '', 'icon' => 'smart_display', 'modifier' => 'video',
));
