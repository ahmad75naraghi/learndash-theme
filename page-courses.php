<?php
// فایل: page-courses.php
get_header(); ?>

<div class="courses-container">
    <?php
    $args = array(
        'post_type'      => 'sfwd-courses',
        'posts_per_page' => 12,
        'post_status'    => 'publish'
    );
    $courses_query = new WP_Query( $args );

    if ( $courses_query->have_posts() ) {
        while ( $courses_query->have_posts() ) {
            $courses_query->the_post();
            
            // نمونه واکشی اطلاعات بدون نیاز به پلاگین‌های جانبی
            $course_id = get_the_ID();
            // $custom_meta = get_post_meta( $course_id, 'your_custom_meta_key', true );
            
            echo '<h2>' . get_the_title() . '</h2>';
            // کدهای HTML کارت دوره را اینجا قرار دهید
        }
        wp_reset_postdata();
    } else {
        echo '<p>دوره‌ای یافت نشد.</p>';
    }
    ?>
</div>

<?php get_footer(); ?>