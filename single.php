<?php
/**
 * قالب تک‌نوشته (Single Post) — طراحی «evented-edu»
 *
 * چیدمان مطابق الگوی صفحهٔ تک‌مقاله: کارت سفید مقاله در ستون راست (۸ ستون)
 * و سایدبار ویجت‌ها در ستون چپ (۴ ستون).
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/* این قالب — مانند front-page.php — سند HTML کامل را خودش چاپ می‌کند
   و هدر/فوتر پوستهٔ جدید را از template-parts می‌گیرد؛ پس get_header()/get_footer()
   (که هدر و فوتر قدیمی قالب‌اند) اینجا صدا زده نمی‌شوند. */
get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-single'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'articles'));
?>

<main class="ee-single-main">
    <?php
    while (have_posts()) :
        the_post();

        /* ثبت بازدید (بدون شمارش مدیران و پیش‌نمایش) */
        if (function_exists('evented_track_post_view')) {
            evented_track_post_view(get_the_ID());
        }

        $ee_post_id   = get_the_ID();
        $ee_date      = function_exists('evented_post_date') ? evented_post_date($ee_post_id) : get_the_date();
        $ee_time      = function_exists('evented_post_time') ? evented_post_time($ee_post_id) : get_the_time();
        $ee_reading   = function_exists('evented_reading_time') ? evented_reading_time($ee_post_id) : 1;
        $ee_views     = function_exists('evented_get_post_views') ? evented_get_post_views($ee_post_id) : 0;
        $ee_comments  = (int) get_comments_number($ee_post_id);
        $ee_cats      = get_the_category($ee_post_id);
        $ee_cat_first = !empty($ee_cats) ? $ee_cats[0] : null;
        $ee_tags      = get_the_tags($ee_post_id);
        $ee_author_id = (int) get_the_author_meta('ID');
        $ee_shares    = function_exists('evented_share_links') ? evented_share_links((string) get_permalink($ee_post_id), (string) get_the_title($ee_post_id)) : array();
        $ee_related   = function_exists('evented_related_posts') ? evented_related_posts($ee_post_id, 3) : array();
        ?>
        <div class="ee-wrap ee-single-grid">

            <article id="post-<?php echo esc_attr($ee_post_id); ?>" <?php post_class('ee-post'); ?>>

                <!-- مسیریابی -->
                <nav class="ee-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
                    <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'evented-edu'); ?></a>
                    <span class="material-symbols-outlined ee-ic">chevron_left</span>
                    <?php if ($ee_cat_first instanceof WP_Term) : ?>
                        <a href="<?php echo esc_url(get_term_link($ee_cat_first)); ?>"><?php echo esc_html($ee_cat_first->name); ?></a>
                        <span class="material-symbols-outlined ee-ic">chevron_left</span>
                    <?php endif; ?>
                    <span class="ee-crumb-current"><?php the_title(); ?></span>
                </nav>

                <!-- عنوان -->
                <h1 class="ee-post-title"><?php the_title(); ?></h1>

                <!-- تصویر شاخص -->
                <?php if (has_post_thumbnail()) : ?>
                    <figure class="ee-post-hero">
                        <?php the_post_thumbnail('large', array('loading' => 'eager')); ?>
                    </figure>
                <?php endif; ?>

                <!-- نوار اطلاعات -->
                <div class="ee-post-meta">
                    <?php if ($ee_cat_first instanceof WP_Term) : ?>
                        <a class="ee-meta-cat" href="<?php echo esc_url(get_term_link($ee_cat_first)); ?>">
                            <span class="material-symbols-outlined ee-ic">sell</span><?php echo esc_html($ee_cat_first->name); ?>
                        </a>
                    <?php endif; ?>

                    <span class="ee-meta-item" title="<?php esc_attr_e('تاریخ انتشار', 'evented-edu'); ?>">
                        <span class="material-symbols-outlined ee-ic">calendar_month</span><?php echo esc_html($ee_date); ?>
                    </span>

                    <span class="ee-meta-item" title="<?php esc_attr_e('ساعت انتشار', 'evented-edu'); ?>">
                        <span class="material-symbols-outlined ee-ic">schedule</span><?php echo esc_html($ee_time); ?>
                    </span>

                    <span class="ee-meta-item" title="<?php esc_attr_e('زمان مطالعه', 'evented-edu'); ?>">
                        <span class="material-symbols-outlined ee-ic">menu_book</span>
                        <?php
                        /* translators: %s: تعداد دقیقه */
                        echo esc_html(sprintf(_n('%s دقیقه مطالعه', '%s دقیقه مطالعه', $ee_reading, 'evented-edu'), number_format_i18n($ee_reading)));
                        ?>
                    </span>

                    <a class="ee-meta-item" href="#ee-comments" title="<?php esc_attr_e('دیدگاه‌ها', 'evented-edu'); ?>">
                        <span class="material-symbols-outlined ee-ic">forum</span>
                        <?php
                        if ($ee_comments > 0) {
                            /* translators: %s: تعداد دیدگاه */
                            echo esc_html(sprintf(_n('%s دیدگاه', '%s دیدگاه', $ee_comments, 'evented-edu'), number_format_i18n($ee_comments)));
                        } else {
                            esc_html_e('بدون دیدگاه', 'evented-edu');
                        }
                        ?>
                    </a>

                    <span class="ee-meta-item ee-meta-views" title="<?php esc_attr_e('تعداد بازدید', 'evented-edu'); ?>">
                        <span class="material-symbols-outlined ee-ic">visibility</span>
                        <?php
                        /* translators: %s: تعداد بازدید */
                        echo esc_html(sprintf(__('تعداد بازدید %s نفر', 'evented-edu'), number_format_i18n($ee_views)));
                        ?>
                    </span>
                </div>

                <!-- نویسنده -->
                <div class="ee-post-authorline">
                    <span class="ee-av"><?php echo get_avatar($ee_author_id, 44); ?></span>
                    <span class="ee-al-txt">
                        <strong><?php the_author(); ?></strong>
                        <span><?php echo esc_html(get_the_author_meta('description') ? wp_trim_words(get_the_author_meta('description'), 12) : __('نویسندهٔ پایگاه', 'evented-edu')); ?></span>
                    </span>
                    <a class="ee-al-link" href="<?php echo esc_url(get_author_posts_url($ee_author_id)); ?>">
                        <?php esc_html_e('همهٔ نوشته‌ها', 'evented-edu'); ?>
                        <span class="material-symbols-outlined ee-ic">arrow_back</span>
                    </a>
                </div>

                <!-- متن مقاله -->
                <div class="ee-post-body">
                    <?php
                    the_content();

                    wp_link_pages(array(
                        'before' => '<div class="ee-page-links">' . esc_html__('صفحات:', 'evented-edu'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>

                <!-- برچسب‌ها -->
                <?php if (!empty($ee_tags)) : ?>
                    <div class="ee-post-tags">
                        <span class="ee-tags-label">
                            <span class="material-symbols-outlined ee-ic">tag</span>
                            <?php esc_html_e('برچسب‌ها:', 'evented-edu'); ?>
                        </span>
                        <?php foreach ($ee_tags as $ee_tag) : ?>
                            <a href="<?php echo esc_url(get_tag_link($ee_tag->term_id)); ?>"><?php echo esc_html($ee_tag->name); ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- اشتراک‌گذاری -->
                <?php if (!empty($ee_shares)) : ?>
                    <div class="ee-post-share">
                        <span class="ee-share-label"><?php esc_html_e('این نوشته را منتشر کنید:', 'evented-edu'); ?></span>
                        <div class="ee-share-row">
                            <?php foreach ($ee_shares as $ee_share) : ?>
                                <a class="ee-share-btn" style="background:<?php echo esc_attr($ee_share['color']); ?>;" href="<?php echo esc_url($ee_share['url']); ?>" target="_blank" rel="noopener nofollow" title="<?php echo esc_attr($ee_share['label']); ?>">
                                    <span><?php echo esc_html(mb_substr($ee_share['label'], 0, 1)); ?></span>
                                </a>
                            <?php endforeach; ?>
                            <button type="button" class="ee-share-btn ee-share-copy" data-copy="<?php echo esc_attr(get_permalink($ee_post_id)); ?>" title="<?php esc_attr_e('کپی لینک', 'evented-edu'); ?>">
                                <span class="material-symbols-outlined ee-ic">link</span>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- نوشتهٔ قبلی/بعدی -->
                <nav class="ee-post-nav" aria-label="<?php esc_attr_e('میان‌بر بین نوشته‌ها', 'evented-edu'); ?>">
                    <?php
                    $ee_prev = get_previous_post();
                    $ee_next = get_next_post();
                    ?>
                    <div class="ee-pn">
                        <?php if ($ee_prev instanceof WP_Post) : ?>
                            <a href="<?php echo esc_url(get_permalink($ee_prev)); ?>">
                                <span class="material-symbols-outlined ee-ic">arrow_forward</span>
                                <span>
                                    <em><?php esc_html_e('نوشتهٔ پیشین', 'evented-edu'); ?></em>
                                    <strong><?php echo esc_html(get_the_title($ee_prev)); ?></strong>
                                </span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="ee-pn ee-pn-next">
                        <?php if ($ee_next instanceof WP_Post) : ?>
                            <a href="<?php echo esc_url(get_permalink($ee_next)); ?>">
                                <span>
                                    <em><?php esc_html_e('نوشتهٔ پسین', 'evented-edu'); ?></em>
                                    <strong><?php echo esc_html(get_the_title($ee_next)); ?></strong>
                                </span>
                                <span class="material-symbols-outlined ee-ic">arrow_back</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </nav>

                <!-- دربارهٔ نویسنده -->
                <div class="ee-author-box">
                    <span class="ee-ab-av"><?php echo get_avatar($ee_author_id, 72); ?></span>
                    <div class="ee-ab-txt">
                        <h3><?php the_author(); ?></h3>
                        <p><?php echo esc_html(get_the_author_meta('description') ? get_the_author_meta('description') : __('نویسندهٔ پایگاه آموزش evented-edu', 'evented-edu')); ?></p>
                        <a class="ee-ab-link" href="<?php echo esc_url(get_author_posts_url($ee_author_id)); ?>">
                            <?php esc_html_e('مشاهدهٔ پروفایل و نوشته‌ها', 'evented-edu'); ?>
                            <span class="material-symbols-outlined ee-ic">arrow_back</span>
                        </a>
                    </div>
                </div>

                <!-- نوشته‌های مرتبط -->
                <?php if (!empty($ee_related)) : ?>
                    <section class="ee-related">
                        <h3 class="ee-w-title ee-related-title">
                            <span class="material-symbols-outlined ee-ic">auto_awesome</span>
                            <?php esc_html_e('نوشته‌های مرتبط', 'evented-edu'); ?>
                        </h3>
                        <div class="ee-related-grid">
                            <?php foreach ($ee_related as $ee_rp) : ?>
                                <a class="ee-related-card" href="<?php echo esc_url(get_permalink($ee_rp)); ?>">
                                    <span class="ee-rc-thumb">
                                        <?php if (has_post_thumbnail($ee_rp)) : ?>
                                            <img src="<?php echo esc_url(get_the_post_thumbnail_url($ee_rp, 'medium')); ?>" alt="<?php echo esc_attr(get_the_title($ee_rp)); ?>" loading="lazy">
                                        <?php else : ?>
                                            <span class="material-symbols-outlined ee-ic">article</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="ee-rc-txt">
                                        <strong><?php echo esc_html(get_the_title($ee_rp)); ?></strong>
                                        <em><?php echo esc_html(function_exists('evented_post_date') ? evented_post_date($ee_rp, 'Y/m/d') : get_the_date('', $ee_rp)); ?></em>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- دیدگاه‌ها -->
                <section class="ee-comments" id="ee-comments">
                    <?php
                    if (comments_open() || get_comments_number()) {
                        comments_template();
                    }
                    ?>
                </section>

            </article>

            <?php get_template_part('template-parts/ee', 'sidebar'); ?>

        </div>
    <?php endwhile; ?>
</main>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'articles')); ?>

