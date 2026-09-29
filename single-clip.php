<?php
/** نمای تکی ویدئو. @package evented-edu */
defined('ABSPATH') || exit;
get_template_part('template-parts/ee-resource', 'single', array(
	'post_type' => 'clip', 'active' => 'video', 'label' => 'ویدئو', 'plural' => 'ویدئوها',
	'taxonomy' => 'wpdmcategory', 'icon' => 'smart_display', 'comments' => true, 'show_meta' => true,
));
