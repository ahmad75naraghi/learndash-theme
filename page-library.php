<?php
/**
 * Template Name: کتابخانه
 * Template Post Type: page
 * @package evented-edu
 */
defined('ABSPATH') || exit;
get_template_part('template-parts/ee-resource', 'page', array(
	'post_type' => 'lib', 'active' => 'library', 'title' => 'کتابخانه', 'label' => 'کتاب',
	'subtitle' => 'مجموعه‌ای از کتاب‌ها و منابع مطالعاتی برای دسترسی سریع و آسان',
	'taxonomy' => 'wpdmcategory', 'icon' => 'local_library', 'modifier' => 'library',
));
