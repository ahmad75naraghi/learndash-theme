<?php
/** نمای تکی کتاب. @package evented-edu */
defined('ABSPATH') || exit;
get_template_part('template-parts/ee-resource', 'single', array(
	'post_type' => 'lib', 'active' => 'library', 'label' => 'کتاب', 'plural' => 'کتابخانه',
	'taxonomy' => 'wpdmcategory', 'icon' => 'local_library', 'comments' => false,
));
