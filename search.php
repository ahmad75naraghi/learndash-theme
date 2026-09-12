<?php
/**
 * قالب نتایج جستجو
 *
 * همان چیدمان آرشیو نوشته‌ها با سربرگ «نتایج جستجو برای: …».
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-archive ee-search-results'));
get_template_part('template-parts/ee', 'header');
?>

<main class="ee-archive-main">
    <?php get_template_part('template-parts/ee-archive', 'main'); ?>
</main>

<?php get_template_part('template-parts/ee', 'footer'); ?>
