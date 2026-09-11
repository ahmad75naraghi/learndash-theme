<?php
/* Template Name: Panel - Wishlist */

if ( ! is_user_logged_in() ) {
    wp_redirect(add_query_arg('redirect_to', home_url('/panel/wishlist'), wp_login_url()));
    exit;
}

$current_user_id = get_current_user_id();

// دریافت لیست دوره‌های علاقمندی بر اساس ساختار جدید شما (رشته متنی با کاما)
$fav_courses_str = get_user_meta( $current_user_id, 'fav_courses', true );
$wishlist_courses = $fav_courses_str ? explode(',', $fav_courses_str) : array();

// پاکسازی آرایه برای جلوگیری از آیدی‌های خالی
$wishlist_courses = array_filter( array_map( 'intval', $wishlist_courses ) );

// تولید Nonce برای امنیت درخواست‌های ایجکس
$wishlist_nonce = wp_create_nonce( 'wishlist_nonce' );

get_header(); ?>
<div class="container">

    <!-- Sidebar -->
    <?php locate_template('panel/sidebar.php', true, false); ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" id="wishlist-search" placeholder="جستجو دوره ها">
            <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M7.92037 15.8407C3.55451 15.8407 0 12.2862 0 7.92037C0 3.55451 3.55451 0 7.92037 0C12.2862 0 15.8407 3.55451 15.8407 7.92037C15.8407 12.2862 12.2862 15.8407 7.92037 15.8407ZM7.92037 1.15908C4.18814 1.15908 1.15908 4.19586 1.15908 7.92037C1.15908 11.6449 4.18814 14.6817 7.92037 14.6817C11.6526 14.6817 14.6817 11.6449 14.6817 7.92037C14.6817 4.19586 11.6526 1.15908 7.92037 1.15908Z" fill="#444444"/>
                <path d="M16.0342 16.6134C15.8874 16.6134 15.7405 16.5593 15.6246 16.4434L14.0792 14.8979C13.8551 14.6738 13.8551 14.3029 14.0792 14.0788C14.3033 13.8547 14.6742 13.8547 14.8983 14.0788L16.4437 15.6243C16.6678 15.8484 16.6678 16.2193 16.4437 16.4434C16.3278 16.5593 16.181 16.6134 16.0342 16.6134Z" fill="#444444"/>
            </svg>
        </div>

        <!-- My Courses Section -->
        <div>
            <h2 class="section-title">علاقه‌مندی‌ها</h2>

            <div class="course-grid" id="wishlist-grid">
                <?php 
                if ( ! empty( $wishlist_courses ) ) : 
                    
                    // برعکس کردن آرایه تا دوره‌هایی که جدیدتر اضافه شده‌اند بالاتر نمایش داده شوند
                    $wishlist_courses = array_reverse( $wishlist_courses );

                    foreach ( $wishlist_courses as $course_id ) : 
                        
                        if ( get_post_type( $course_id ) !== 'sfwd-courses' || get_post_status( $course_id ) !== 'publish' ) {
                            continue;
                        }

                        $course_title = get_the_title( $course_id );
                        $course_link = get_permalink( $course_id );
                        $author_id = get_post_field( 'post_author', $course_id );
                        $course_author = get_the_author_meta( 'display_name', $author_id );
                        
                        $course_thumbnail = get_the_post_thumbnail_url( $course_id, 'medium' );
                        if ( ! $course_thumbnail ) {
                            $course_thumbnail = 'https://via.placeholder.com/300x200?text=No+Image';
                        }

                        $meta = get_post_meta( $course_id, '_sfwd-courses', true );
                        $price_type = isset($meta['sfwd-courses_course_price_type']) ? $meta['sfwd-courses_course_price_type'] : 'free';
                        $price = isset($meta['sfwd-courses_course_price']) ? $meta['sfwd-courses_course_price'] : '';
                        $price_text = ( $price_type === 'free' || empty($price) ) ? 'رایگان' : $price . ' تومان';

                        $categories = get_the_terms( $course_id, 'ld_course_category' );
                ?>
                    <div class="course-card wishlist-item" data-title="<?php echo esc_attr($course_title); ?>">
                        
                        <!-- دکمه حذف از علاقه‌مندی‌ها با کلاس یکسان -->
                        <button class="remove-from-wishlist" data-course-id="<?php echo esc_attr($course_id); ?>" title="حذف از علاقه‌مندی‌ها" style="position: absolute; top: 10px; left: 10px; z-index: 10; background: rgba(255,255,255,0.8); border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; color: #ff4757; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/></svg>
                        </button>

                        <a href="<?php echo esc_url($course_link); ?>" style="text-decoration: none; color: inherit; display: block;">
                            <div class="card-header">
                                <div class="image-placeholder">
                                    <img src="<?php echo esc_url($course_thumbnail); ?>" />
                                </div>
                            </div>
                            <div class="card-body">
                                <h3 class="card-title"><?php echo esc_html($course_title); ?></h3>
                                <p class="card-author"><?php echo esc_html($course_author); ?></p>
                                
                                <div class="card-tags">
                                    <?php if ( !empty($categories) && !is_wp_error($categories) ) : ?>
                                        <?php foreach ( $categories as $cat ) : ?>
                                            <span class="cat-badge"><?php echo esc_html($cat->name); ?></span>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <span class="cat-badge">عمومی</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-price"><?php echo esc_html($price_text); ?></div>
                            </div>
                        </a>
                    </div>
                <?php 
                    endforeach; 
                else : 
                ?>
                    <div class="no-exist-notice" style="grid-column: 1 / -1; padding: 20px; text-align: center; color: #666;">
                        <p>
                            هنوز دوره ای به لیست علاقمندی خود اضافه نکردی!!
                        </p>
                        <p>
                            دوره‌هایی که بعداً می‌خواهی ببینی را با دکمهٔ علاقه‌مندی اینجا نگه دار.
                        </p>
                        <a class="btn-primary" href="<?php echo esc_url(get_post_type_archive_link('sfwd-courses') ?: home_url('/')); ?>">
                            مشاهده دوره ها
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- کدهای جاوااسکریپت مخصوص این صفحه -->
<script>
jQuery(document).ready(function($) {
    
    // دریافت Nonce تولید شده در PHP و آدرس ایجکس
    var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var securityNonce = '<?php echo esc_js($wishlist_nonce); ?>';

    // 1. هندل کردن دکمه حذف
    $('.remove-from-wishlist').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var courseId = button.data('course-id');
        var card = button.closest('.course-card');

        card.css('opacity', '0.5');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'toggle_course_wishlist',
                course_id: courseId,
                security: securityNonce // ارسال توکن امنیتی هماهنگ با فانکشن شما
            },
            success: function(response) {
                // بررسی وضعیت بازگشتی از سمت فانکشن شما
                if (response.success && response.data.status === 'removed') {
                    card.fadeOut(300, function() {
                        $(this).remove();
                        if ($('.wishlist-item').length === 0) {
                            location.reload(); 
                        }
                    });
                } else {
                    alert('خطایی در حذف دوره رخ داد.');
                    card.css('opacity', '1');
                }
            },
            error: function() {
                alert('خطای ارتباط با سرور.');
                card.css('opacity', '1');
            }
        });
    });

    // 2. سرچ زنده در لیست علاقه‌مندی‌ها
    $('#wishlist-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        
        $('.wishlist-item').each(function() {
            var title = $(this).data('title').toLowerCase();
            if (title.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

});
</script>

<?php get_footer(); ?>