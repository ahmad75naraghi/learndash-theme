<?php
/**
 * قالب نمایش برگه‌های تکی (Pages)
 */
get_header(); // فراخوانی هدر
?>

<main class="page-main-container">
    <div class="container"> <!-- کلاس container برای محدود کردن عرض محتوا (اختیاری) -->
        
        <?php
        // شروع حلقه وردپرس برای دریافت اطلاعات برگه
        if ( have_posts() ) :
            while ( have_posts() ) : the_post(); 
        ?>

            <article id="page-<?php the_ID(); ?>" <?php post_class('single-page-article'); ?>>

                <!-- محتوای اصلی برگه (متن، عکس و کدهایی که در ویرایشگر وردپرس وارد کردید) -->
                <div class="page-content">
                    <?php 
                        // فراخوانی محتوای برگه
                        the_content(); 
                        
                        // اگر در برگه از صفحه‌بندی (تگ Nextpage) استفاده شده باشد
                        wp_link_pages( array(
                            'before' => '<div class="page-links">' . esc_html__( 'صفحات:', 'evented-edu' ),
                            'after'  => '</div>',
                        ) );
                    ?>
                </div>

            </article>

        <?php
            endwhile; // پایان حلقه
        endif; 
        ?>

    </div>
</main>

<?php
get_footer(); // فراخوانی فوتر
?>