<?php
/**
 * Template Name: گالری تصاویر
 * Template Post Type: page
 * @package evented-edu
 */
defined('ABSPATH') || exit;
get_template_part('template-parts/ee-resource', 'page', array(
	'post_type' => 'gallery', 'active' => 'gallery', 'title' => 'گالری تصاویر', 'label' => 'تصویر',
	'subtitle' => 'روایت تصویری رویدادها، فعالیت‌ها و لحظه‌های به‌یادماندنی',
	'taxonomy' => 'galery_cat', 'icon' => 'photo_library', 'modifier' => 'gallery',
));
