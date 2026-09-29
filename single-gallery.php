<?php
/** نمای تکی گالری. @package evented-edu */
defined('ABSPATH') || exit;
get_template_part('template-parts/ee-resource', 'single', array(
	'post_type' => 'gallery', 'active' => 'gallery', 'label' => 'گالری', 'plural' => 'گالری',
	'taxonomy' => 'galery_cat', 'icon' => 'photo_library', 'comments' => false, 'image_download' => true,
));
