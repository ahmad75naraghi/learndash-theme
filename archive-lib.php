<?php
/** آرشیو کتابخانه. @package evented-edu */
defined('ABSPATH') || exit;
get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-archive ee-resource-archive ee-library-archive'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'library'));
?>
<main id="ee-main" class="ee-archive-main">
	<?php get_template_part('template-parts/ee-archive', 'main', array(
		'ee_title' => 'کتابخانه', 'ee_subtitle' => 'کتاب‌ها و منابع مطالعاتی', 'ee_taxonomy' => 'wpdmcategory',
		'ee_item_label' => 'کتاب', 'ee_icon' => 'local_library', 'ee_all_url' => get_post_type_archive_link('lib'),
	)); ?>
</main>
<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'library')); ?>
