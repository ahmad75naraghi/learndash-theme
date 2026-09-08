<?php

defined('ABSPATH') || exit;

if ( ! is_user_logged_in() ) {
    wp_redirect('https://edu.falnic.com/login?redirect_to=https://edu.falnic.com/panel');
    exit;
}
get_header();?>

<?php get_footer();?>