<?php
/**
 * شورت‌کد اختصاصی برای نمایش پست‌تایپ‌های ثبت‌شده (lib, clip, gallery)
 * استفاده: [show_my_cpt post_type="lib" count="9"]
 */
function custom_cpt_display_shortcode( $atts ) {
    $atts = shortcode_atts(
        [
            'post_type' => 'post', // می‌تواند lib, clip یا gallery باشد
            'count'     => 12,
            'paged'     => true,
        ],
        $atts,
        'show_my_cpt'
    );

    $paged = 1;
    if ( filter_var( $atts['paged'], FILTER_VALIDATE_BOOLEAN ) ) {
        $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : ( ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1 );
    }

    $query = new WP_Query( [
        'post_type'      => sanitize_key( $atts['post_type'] ),
        'posts_per_page' => intval( $atts['count'] ),
        'post_status'    => 'publish',
        'paged'          => $paged,
    ] );

    if ( ! $query->have_posts() ) {
        return '<p class="cpt-empty">هیچ موردی یافت نشد.</p>';
    }

    ob_start();
    ?>
    <div class="cpt-shortcode-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'cpt-item' ); ?> style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="cpt-thumb" style="margin-bottom: 10px;">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail( 'medium', ['style' => 'width: 100%; height: auto; border-radius: 4px;'] ); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <h3 class="cpt-title" style="font-size: 1.2rem; margin: 0 0 10px;">
                    <a href="<?php the_permalink(); ?>" style="text-decoration: none; color: #333;"><?php the_title(); ?></a>
                </h3>

                <div class="cpt-excerpt" style="font-size: 0.9rem; color: #666;">
                    <?php the_excerpt(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>

    <?php if ( filter_var( $atts['paged'], FILTER_VALIDATE_BOOLEAN ) && $query->max_num_pages > 1 ) : ?>
        <nav class="cpt-pagination" style="margin-top: 20px; text-align: center;">
            <?php
            echo paginate_links( [
                'total'     => $query->max_num_pages,
                'current'   => $paged,
                'prev_text' => 'قبلی',
                'next_text' => 'بعدی',
            ] );
            ?>
        </nav>
    <?php endif; ?>

    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode( 'show_my_cpt', 'custom_cpt_display_shortcode' );







