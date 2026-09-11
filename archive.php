<?php
/**
 * قالب آرشیو نوشته‌ها — دسته‌بندی، برچسب و بایگانی زمانی
 *
 * پوستهٔ جدید evented-edu؛ بدنهٔ فهرست در template-parts/ee-archive-main.php
 * و سایدبار در template-parts/ee-sidebar.php قرار دارد.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-archive'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'articles'));
?>

<main class="ee-archive-main">
    <?php get_template_part('template-parts/ee-archive', 'main'); ?>
</main>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'articles')); ?>
