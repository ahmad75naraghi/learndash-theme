<?php
/**
 * قالب برگهٔ نوشته‌ها (Blog / Posts page)
 *
 * همان چیدمان آرشیو نوشته‌ها؛ عنوان سربرگ از عنوان برگهٔ «نوشته‌ها» می‌آید.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-archive ee-blog'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'articles'));
?>

<main class="ee-archive-main">
    <?php get_template_part('template-parts/ee-archive', 'main'); ?>
</main>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'articles')); ?>
