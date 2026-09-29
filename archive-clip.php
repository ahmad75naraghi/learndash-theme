<?php
/** آرشیو ویدئوها. @package evented-edu */
defined('ABSPATH') || exit;
get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-archive ee-resource-archive ee-video-archive'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'video'));
?>
<main id="ee-main" class="ee-archive-main">
	<?php get_template_part('template-parts/ee-archive', 'main', array(
		'ee_title' => 'ویدئوها', 'ee_subtitle' => 'ویدئوها و محتوای چندرسانه‌ای', 'ee_taxonomy' => 'wpdmcategory',
		'ee_item_label' => 'ویدئو', 'ee_icon' => 'smart_display', 'ee_all_url' => get_post_type_archive_link('clip'),
	)); ?>
</main>
<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'video')); ?>
