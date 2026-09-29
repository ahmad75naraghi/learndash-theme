<?php
/** آرشیو گالری. @package evented-edu */
defined('ABSPATH') || exit;
get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-archive ee-resource-archive ee-gallery-archive'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'gallery'));
?>
<main id="ee-main" class="ee-archive-main">
	<?php get_template_part('template-parts/ee-archive', 'main', array(
		'ee_title' => 'گالری', 'ee_subtitle' => 'گزارش‌های تصویری و مجموعه عکس‌ها', 'ee_taxonomy' => 'galery_cat',
		'ee_item_label' => 'مجموعه', 'ee_icon' => 'photo_library', 'ee_all_url' => get_post_type_archive_link('gallery'),
	)); ?>
</main>
<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'gallery')); ?>
