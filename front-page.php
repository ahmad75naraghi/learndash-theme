<?php

/**
 * Template Name: Front Page (evented-edu redesign)
 *
 * صفحهٔ اصلی جدید — چیدمان pastel مطابق الگوی «شمیم»؛ تمام بخش‌ها داینامیک‌اند
 * و از محتوای وردپرس (دوره‌ها/دسته‌ها/مقالات/اساتید) خوانده می‌شوند.
 *
 * نکته: برای جلوگیری از برخورد با استایل سراسری قالب، همهٔ کلاس‌ها با ee- پیشوندگذاری شده‌اند.
 */

defined('ABSPATH') || exit;

// ---------- داده‌های داینامیک (یک‌بار کوئری می‌زنیم) ----------

/* ۱) دسته‌های لرن‌دش */
$ee_cats = get_terms(array(
    'taxonomy'   => 'ld_course_category',
    'hide_empty' => true,
    'number'     => 12,
));
if (is_wp_error($ee_cats)) {
    $ee_cats = array();
}

/* ۳) دوره‌ها: تب «همه» (۴ دورهٔ آخر) + هر دستهٔ دوره یک تب با ۴ دوره (کش ۱۲ ساعته) */
$ee_course_tabs = get_transient('evented_home_course_tabs');
if (!is_array($ee_course_tabs)) {
    $ee_course_tabs = array();
    $ee_tab_q = new WP_Query(array('post_type' => 'sfwd-courses', 'posts_per_page' => 4, 'no_found_rows' => true, 'fields' => 'ids'));
    $ee_course_tabs[] = array('key' => 'all', 'name' => 'همه', 'ids' => $ee_tab_q->posts, 'link' => '');
    foreach ($ee_cats as $cat) {
        $ee_tab_q = new WP_Query(array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => 4,
            'no_found_rows' => true,
            'fields' => 'ids',
            'tax_query' => array(array('taxonomy' => 'ld_course_category', 'field' => 'term_id', 'terms' => (int) $cat->term_id)),
        ));
        if (empty($ee_tab_q->posts)) {
            continue;
        }
        $ee_link = get_term_link($cat);
        $ee_course_tabs[] = array('key' => 'cat-' . (int) $cat->term_id, 'name' => $cat->name, 'count' => (int) $cat->count, 'ids' => $ee_tab_q->posts, 'link' => is_wp_error($ee_link) ? '' : $ee_link);
    }
    wp_reset_postdata();
    set_transient('evented_home_course_tabs', $ee_course_tabs, 12 * HOUR_IN_SECONDS);
}
$ee_courses = !empty($ee_course_tabs[0]['ids']) ? array_filter(array_map('get_post', $ee_course_tabs[0]['ids'])) : array();

/* ۴) آخرین مقالات (پست‌های عادی) — یک کوئری، دو مصرف */
$ee_posts_q = new WP_Query(array(
    'post_type'           => 'post',
    'posts_per_page'      => 4,
    'no_found_rows'       => true,
));
$ee_latest_posts = $ee_posts_q->posts;
wp_reset_postdata();

$ee_articles = array_slice($ee_latest_posts, 0, 3);

/* ۴٫۲) تب‌های مقالات — ۵ دستهٔ تصادفی (کش یک‌هفته‌ای) */
$ee_article_tabs = function_exists('evented_home_article_tabs') ? evented_home_article_tabs((int) (function_exists('evented_opt') ? evented_opt('tabs_count', 5) : 5), (int) (function_exists('evented_opt') ? evented_opt('tabs_per', 3) : 3)) : array();

/* آدرس‌های منوی استاتیک (کش‌شده) */
$ee_nav = static function ($key, $fallback = '') {
    if (function_exists('evented_nav_url')) {
        return evented_nav_url($key);
    }
    return $fallback ?: home_url('/');
};
$ee_opt = static function ($k, $d = '') {
    return function_exists('evented_opt') ? evented_opt($k, $d) : $d;
};
$ee_meta_on = static function ($key) {
    return !function_exists('evented_post_meta_visible') || evented_post_meta_visible($key);
};
$ee_blog_url = $ee_nav('articles', get_permalink(get_option('page_for_posts')) ?: home_url('/'));

/* ۴٫۱) بلاگ ویژه — نوشته‌های چسبان و در ادامه آخرین نوشته‌ها */
$ee_blog_feature = function_exists('evented_featured_posts') ? evented_featured_posts(4) : array();

/* ۵) تجربهٔ دانشجویان: آخرین دیدگاه‌های تأییدشدهٔ دارای امتیاز روی دوره‌ها */
$ee_testimonials = get_comments(array(
    'status'    => 'approve',
    'post_type' => 'sfwd-courses',
    'number'    => 4,
    'orderby'   => 'comment_date_gmt',
    'order'     => 'DESC',
    'meta_key'  => 'review_rating', // phpcs:ignore WordPress.DB.SlowDBQuery
));

/* ۶) اساتید: کاربران دارای نقش group_leader */
$ee_instructors = get_users(array(
    'role'    => 'group_leader',
    'number'  => 8,
    'orderby' => 'display_name',
));

/* ۷) آمار سادهٔ دوره‌ها */
$ee_course_count = wp_count_posts('sfwd-courses');
$ee_course_count = isset($ee_course_count->publish) ? (int) $ee_course_count->publish : 0;

/* ۷) اسلایدر هیرو — فقط اسلایدهایی که در «تنظیمات قالب» پیشخوان ثبت شده‌اند.
   اگر اسلایدی تنظیم نشده باشد، بنر هیرو نمایش داده نمی‌شود (بدون محتوای جایگزین). */
$ee_feature_slides = array();

$ee_saved_slides = function_exists('evented_get_home_slides') ? evented_get_home_slides() : array();

foreach ($ee_saved_slides as $s) {
    // اول سایز میانه (large) پیوست؛ اگر نبود همان URL ذخیره‌شده
    $slide_img = wp_get_attachment_image_url(absint($s['id']), 'large');
    if (!$slide_img) {
        $slide_img = $s['image'];
    }
    $ee_feature_slides[] = array(
        'img'       => $slide_img,
        'badge'     => !empty($s['badge']) ? $s['badge'] : '',
        'title'     => !empty($s['title']) ? $s['title'] : '',
        'desc'      => !empty($s['desc']) ? $s['desc'] : '',
        'link'      => !empty($s['link']) ? $s['link'] : '',
        'link_text' => !empty($s['link_text']) ? $s['link_text'] : '',
        'btn2_text' => !empty($s['btn2_text']) ? $s['btn2_text'] : '',
        'btn2_link' => !empty($s['btn2_link']) ? $s['btn2_link'] : '',
    );
}

$ee_slide_count  = count($ee_feature_slides);
$ee_slider_mode  = $ee_slide_count > 1;

?>
<?php get_template_part('template-parts/ee', 'head'); ?>


<?php get_template_part('template-parts/ee', 'header', array('ee_active' => 'home')); ?>

<main id="ee-main" class="ee-home-main">

    <!-- ======= هیرو: اسلایدر بنر + آخرین مقالات ======= -->
    <section class="ee-hero<?php echo empty($ee_feature_slides) ? ' is-no-slider' : ''; ?>">
        <div class="ee-wrap ee-hero-grid">

            <div class="ee-hero-feat ee-fade">

                <?php if ($ee_slider_mode) : ?>
                    <div class="ee-slider" id="eeSlider" data-count="<?php echo esc_attr($ee_slide_count); ?>">
                    <?php endif; ?>

                    <?php foreach ($ee_feature_slides as $ee_i => $ee_s) :
                        $ee_has_text = ($ee_s['title'] !== '' || $ee_s['desc'] !== '');
                        $ee_btn1     = ($ee_s['link'] !== '' && $ee_s['link_text'] !== '');
                        $ee_btn2     = ($ee_s['btn2_link'] !== '' && $ee_s['btn2_text'] !== '');
                        $ee_has_body = $ee_has_text || $ee_btn1 || $ee_btn2;
                        $ee_alt      = $ee_s['title'] !== '' ? $ee_s['title'] : 'اسلاید ' . ($ee_i + 1);
                    ?>
                        <div class="ee-feature<?php echo $ee_slider_mode ? ' ee-slide' : ''; ?><?php echo ($ee_slider_mode && $ee_i === 0) ? ' is-active' : ''; ?><?php echo $ee_has_body ? '' : ' is-bare'; ?>" <?php echo $ee_slider_mode ? ' data-index="' . esc_attr($ee_i) . '"' : ''; ?>>
                            <?php if ($ee_s['link'] !== '') : ?>
                                <a class="feat-link" href="<?php echo esc_url($ee_s['link']); ?>" aria-label="<?php echo esc_attr($ee_alt); ?>"></a>
                            <?php endif; ?>
                            <div class="feat-media">
                                <img src="<?php echo esc_url($ee_s['img']); ?>" alt="<?php echo esc_attr($ee_alt); ?>" loading="<?php echo $ee_i === 0 ? 'eager' : 'lazy'; ?>" draggable="false">
                                <?php if ($ee_has_body) : ?><div class="feat-shade"></div><?php endif; ?>
                            </div>
                            <?php if (!empty($ee_s['badge'])) : ?>
                                <div class="feat-tags">
                                    <span class="ee-chip ee-chip-amber"><?php echo esc_html($ee_s['badge']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($ee_has_body) : ?>
                                <div class="feat-body">
                                    <?php if ($ee_s['title'] !== '') : ?>
                                        <<?php echo $ee_i === 0 ? 'h1' : 'h2'; ?> class="feat-title"><?php echo esc_html($ee_s['title']); ?></<?php echo $ee_i === 0 ? 'h1' : 'h2'; ?>>
                                    <?php endif; ?>
                                    <?php if ($ee_s['desc'] !== '') : ?>
                                        <p class="feat-desc"><?php echo esc_html($ee_s['desc']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($ee_btn1 || $ee_btn2) : ?>
                                        <div class="feat-cta-row">
                                            <?php if ($ee_btn1) : ?>
                                                <a class="ee-btn ee-btn-solid" href="<?php echo esc_url($ee_s['link']); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false">
                                                        <use href="#i-play_circle"></use>
                                                    </svg> <?php echo esc_html($ee_s['link_text']); ?></a>
                                            <?php endif; ?>
                                            <?php if ($ee_btn2) : ?>
                                                <a class="ee-btn ee-btn-glass" href="<?php echo esc_url($ee_s['btn2_link']); ?>"><?php echo esc_html($ee_s['btn2_text']); ?> <svg class="ee-ic" aria-hidden="true" focusable="false">
                                                        <use href="#i-arrow_back"></use>
                                                    </svg></a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($ee_slider_mode) : ?>
                        <div class="ee-slider-controls" id="eeSliderControls">
                            <button type="button" class="ee-slide-arrow ee-slide-arrow-prev" data-dir="-1" aria-label="اسلاید قبلی"><svg class="ee-ic" aria-hidden="true" focusable="false">
                                    <use href="#i-chevron_right"></use>
                                </svg></button>
                            <button type="button" class="ee-slide-arrow ee-slide-arrow-next" data-dir="1" aria-label="اسلاید بعدی"><svg class="ee-ic" aria-hidden="true" focusable="false">
                                    <use href="#i-chevron_left"></use>
                                </svg></button>
                            <div class="ee-slider-dots" id="eeSliderDots">
                                <?php for ($ee_d = 0; $ee_d < $ee_slide_count; $ee_d++) : ?>
                                    <button type="button" class="ee-slide-dot<?php echo $ee_d === 0 ? ' is-active' : ''; ?>" data-go="<?php echo esc_attr($ee_d); ?>" aria-label="اسلاید <?php echo esc_attr($ee_d + 1); ?>"></button>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div><!-- /.ee-slider -->
                <?php endif; ?>

            </div>

            <aside class="ee-latest ee-fade">
                <div class="ee-latest-card">
                    <div class="lc-head">
                        <div class="lc-title"><svg class="ee-ic" aria-hidden="true" focusable="false">
                                <use href="#i-auto_stories"></use>
                            </svg> آخرین مقالات</div>
                        <a class="lc-more" href="<?php echo esc_url($ee_blog_url); ?>">مشاهده همه <svg class="ee-ic" aria-hidden="true" focusable="false">
                                <use href="#i-arrow_back"></use>
                            </svg></a>
                    </div>
                    <div class="ee-news-list">
                        <?php foreach ($ee_latest_posts as $p) : ?>
                            <a href="<?php echo esc_url(get_permalink($p)); ?>">
                                <?php if (has_post_thumbnail($p)) : ?>
                                    <img class="th" src="<?php echo esc_url(get_the_post_thumbnail_url($p, 'thumbnail')); ?>" alt="<?php echo esc_attr(get_the_title($p)); ?>">
                                <?php else : ?>
                                    <svg class="th ee-ic ee-u-teal" aria-hidden="true" focusable="false">
                                        <use href="#i-article"></use>
                                    </svg>
                                <?php endif; ?>
                                <div class="ee-u-min0">
                                    <h4><?php echo esc_html(get_the_title($p)); ?></h4>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
                $ee_radio_url = (string) $ee_opt('radio_url');
                if ('' === $ee_radio_url) {
                    $ee_radio_url = $ee_nav('podcast');
                } elseif (0 === strpos($ee_radio_url, '/')) {
                    $ee_radio_url = home_url($ee_radio_url);
                }
                ?>
                <a class="ee-radio-card" href="<?php echo esc_url($ee_radio_url); ?>" aria-label="<?php echo esc_attr($ee_opt('radio_title')); ?>">
                    <span class="ee-radio-glow" aria-hidden="true"></span>
                    <span class="ee-radio-rings" aria-hidden="true"><i></i><i></i><i></i></span>
                    <span class="ee-radio-ic" aria-hidden="true">
                        <svg class="ee-ic">
                            <use href="#i-podcasts"></use>
                        </svg>
                    </span>
                    <span class="ee-radio-body">
                        <span class="ee-radio-title">
                            <?php echo esc_html($ee_opt('radio_title')); ?>
                            <?php if ('' !== (string) $ee_opt('radio_badge')) : ?><span class="ee-radio-live"><i></i> <?php echo esc_html($ee_opt('radio_badge')); ?></span><?php endif; ?>
                        </span>
                        <span class="ee-radio-sub"><?php echo esc_html($ee_opt('radio_sub')); ?></span>
                    </span>
                    <span class="ee-radio-eq" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
                    <span class="ee-radio-play" aria-hidden="true"><svg class="ee-ic">
                            <use href="#i-play_arrow"></use>
                        </svg></span>
                </a>
            </aside>

        </div>
    </section>

    <!-- ======= دسترسی سریع (کاشی‌ها) ======= -->
    <section class="ee-quick" id="ee-quick">
        <div class="ee-wrap">
            <div class="ee-sec-head">
                <h3 class="ee-sec-title"><span class="bar"></span> <?php echo esc_html($ee_opt('quick_title')); ?></h3>
                <span class="ee-sec-sub"><?php echo esc_html($ee_opt('quick_sub')); ?></span>
            </div>
            <div class="ee-qgrid">
                <a class="ee-qitem" href="<?php echo esc_url($ee_blog_url); ?>"><svg class="qi-ic ee-ic" aria-hidden="true" focusable="false">
                        <use href="#i-menu_book"></use>
                    </svg><span>مقالات</span></a>
                <a class="ee-qitem ee-q-purple" href="<?php echo esc_url($ee_nav('courses')); ?>"><svg class="qi-ic ee-ic" aria-hidden="true" focusable="false">
                        <use href="#i-school"></use>
                    </svg><span>دوره‌ها</span></a>
                <a class="ee-qitem ee-q-teal" href="<?php echo esc_url($ee_nav('video')); ?>"><svg class="qi-ic ee-ic" aria-hidden="true" focusable="false">
                        <use href="#i-smart_display"></use>
                    </svg><span>ویدیوها</span></a>
                <a class="ee-qitem ee-q-amber" href="<?php echo esc_url($ee_nav('downloads')); ?>"><svg class="qi-ic ee-ic" aria-hidden="true" focusable="false">
                        <use href="#i-download_for_offline"></use>
                    </svg><span>دانلودها</span></a>
                <a class="ee-qitem ee-q-rose" href="<?php echo esc_url($ee_nav('podcast')); ?>"><svg class="qi-ic ee-ic" aria-hidden="true" focusable="false">
                        <use href="#i-podcasts"></use>
                    </svg><span>پادکست</span></a>
                <a class="ee-qitem" href="<?php echo esc_url($ee_nav('library')); ?>"><svg class="qi-ic ee-ic" aria-hidden="true" focusable="false">
                        <use href="#i-local_library"></use>
                    </svg><span>کتابخانه</span></a>
                <a class="ee-qitem ee-q-purple" href="<?php echo esc_url($ee_nav('gallery')); ?>"><svg class="qi-ic ee-ic" aria-hidden="true" focusable="false">
                        <use href="#i-photo_library"></use>
                    </svg><span>گالری</span></a>
                <a class="ee-qitem ee-q-amber" href="<?php echo esc_url($ee_nav('contests')); ?>"><svg class="qi-ic ee-ic" aria-hidden="true" focusable="false">
                        <use href="#i-emoji_events"></use>
                    </svg><span>مسابقات</span></a>
            </div>
        </div>
    </section>

    <!-- ======= کاتالوگ همهٔ دوره‌ها (تب دسته‌ها + بارگذاری بیشتر) ======= -->
    <?php
    $ee_ct_tabs  = function_exists('evented_catalog_tabs') ? evented_catalog_tabs() : array();
    $ee_ct_first = function_exists('evented_catalog_page') ? evented_catalog_page(0, 1) : array('html' => '', 'total' => 0, 'shown' => 0, 'has_more' => false);
    $ee_ct_fa    = function_exists('evented_fa_digits') ? 'evented_fa_digits' : 'strval';
    if (!empty($ee_ct_first['html'])) :
    ?>
        <section class="ee-catalog" id="ee-catalog" data-ee-catalog>
            <span class="ee-ct-blob b1" aria-hidden="true"></span>
            <span class="ee-ct-blob b2" aria-hidden="true"></span>
            <span class="ee-ct-blob b3" aria-hidden="true"></span>
            <div class="ee-wrap">
                <header class="ee-ct-head">
                    <div class="ee-ct-heading">
                        <span class="ee-ct-kicker"><svg class="ee-ic" aria-hidden="true" focusable="false">
                                <use href="#i-auto_awesome"></use>
                            </svg> کتابخانهٔ کامل دوره‌ها</span>
                        <h2 class="ee-ct-title">همهٔ دوره‌های <em>شمیم آشنا</em> در یک نگاه</h2>
                        <p class="ee-ct-sub">دسته‌بندی دلخواه را انتخاب کنید؛ دوره‌ها به‌ترتیب جدیدترین نمایش داده می‌شوند.</p>
                    </div>
                    <div class="ee-ct-stats" aria-hidden="true">
                        <span class="ee-ct-stat"><b data-ee-ct-total><?php echo esc_html($ee_ct_fa($ee_ct_first['total'])); ?></b><small>دوره</small></span>
                        <span class="ee-ct-stat"><b><?php echo esc_html($ee_ct_fa(max(0, count($ee_ct_tabs) - 1))); ?></b><small>دسته</small></span>
                    </div>
                </header>

                <div class="ee-ct-tabs-wrap">
                    <div class="ee-ct-tabs" role="tablist" aria-label="دسته‌بندی دوره‌ها" data-ee-ct-tabs>
                        <?php foreach ($ee_ct_tabs as $ee_ti => $ee_t) : ?>
                            <button type="button" class="ee-ct-tab tone-<?php echo (int) evented_catalog_tone($ee_t['id']); ?><?php echo 0 === $ee_ti ? ' is-on' : ''; ?>" role="tab" id="eeCt-<?php echo esc_attr($ee_t['key']); ?>" aria-selected="<?php echo 0 === $ee_ti ? 'true' : 'false'; ?>" aria-controls="eeCtPanel" tabindex="<?php echo 0 === $ee_ti ? '0' : '-1'; ?>" data-cat="<?php echo (int) $ee_t['id']; ?>" data-url="<?php echo esc_url($ee_t['url']); ?>" data-name="<?php echo esc_attr($ee_t['name']); ?>">
                                <span class="ee-ct-tab-ic" aria-hidden="true"><svg class="ee-ic" focusable="false">
                                        <use href="#<?php echo 0 === $ee_ti ? 'i-grid_view' : 'i-folder_open'; ?>"></use>
                                    </svg></span>
                                <span class="ee-ct-tab-tx"><?php echo esc_html($ee_t['name']); ?></span>
                                <span class="ee-ct-tab-n"><?php echo esc_html($ee_ct_fa($ee_t['count'])); ?></span>
                            </button>
                        <?php endforeach; ?>
                        <span class="ee-ct-ink" aria-hidden="true"></span>
                    </div>
                </div>

                <div class="ee-ct-bar">
                    <p class="ee-ct-status" aria-live="polite" data-ee-ct-status>نمایش <b data-ee-ct-shown><?php echo esc_html($ee_ct_fa($ee_ct_first['shown'])); ?></b> از <b data-ee-ct-total2><?php echo esc_html($ee_ct_fa($ee_ct_first['total'])); ?></b> دوره</p>
                    <span class="ee-ct-progress" aria-hidden="true"><i data-ee-ct-progress style="width:<?php echo $ee_ct_first['total'] ? (int) round($ee_ct_first['shown'] * 100 / $ee_ct_first['total']) : 100; ?>%"></i></span>
                </div>

                <div class="ee-ct-panel" id="eeCtPanel" role="tabpanel" aria-labelledby="eeCt-all" aria-busy="false" data-ee-ct-panel>
                    <div class="ee-ct-grid" data-ee-ct-grid data-cat="0" data-page="1" data-total="<?php echo (int) $ee_ct_first['total']; ?>">
                        <?php echo $ee_ct_first['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML ساخته‌شده در قالب. 
                        ?>
                    </div>
                    <div class="ee-ct-skel" data-ee-ct-skel hidden aria-hidden="true">
                        <?php for ($k = 0; $k < 4; $k++) : ?><div class="ee-ct-skel-card" style="--i:<?php echo $k; ?>"><i class="sk-m"></i><i class="sk-l"></i><i class="sk-l w60"></i><i class="sk-l w40"></i></div><?php endfor; ?>
                    </div>
                </div>

                <div class="ee-ct-actions">
                    <button type="button" class="ee-ct-more" data-ee-ct-more<?php echo $ee_ct_first['has_more'] ? '' : ' hidden'; ?>>
                        <span class="ee-ct-more-ring" aria-hidden="true"></span>
                        <svg class="ee-ic ee-ct-more-ic" aria-hidden="true" focusable="false">
                            <use href="#i-expand_more"></use>
                        </svg>
                        <span class="ee-ct-more-tx">بارگذاری دوره‌های بیشتر</span>
                    </button>
                    <a class="ee-ct-all" href="<?php echo esc_url($ee_nav('courses')); ?>" data-ee-ct-all data-base="<?php echo esc_url($ee_nav('courses')); ?>">
                        <span data-ee-ct-all-tx>مشاهدهٔ صفحهٔ همهٔ دوره‌ها</span>
                        <svg class="ee-ic" aria-hidden="true" focusable="false">
                            <use href="#i-arrow_back"></use>
                        </svg>
                    </a>
                    <p class="ee-ct-end" data-ee-ct-end hidden><svg class="ee-ic" aria-hidden="true" focusable="false">
                            <use href="#i-done_all"></use>
                        </svg> همهٔ دوره‌های این دسته را دیدید.</p>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ======= ۳ گام عضویت ======= -->
    <section class="ee-steps" id="ee-steps">
        <div class="ee-wrap">
            <div class="ee-steps-top">
                <div>
                    <span class="st-badge"><svg class="ee-ic" aria-hidden="true" focusable="false">
                            <use href="#i-route"></use>
                        </svg> مسیر آسان و گام‌به‌گام آموزش</span>
                    <h3 class="ee-u-mt-2"><?php echo esc_html($ee_opt('steps_title')); ?></h3>
                    <p><?php echo esc_html($ee_opt('steps_sub')); ?></p>
                </div>
            </div>
            <div class="ee-steps-grid">
                <div class="ee-step">
                    <div>
                        <div class="st-top"><span class="st-num">۱</span><svg class="st-ic ee-ic" aria-hidden="true" focusable="false">
                                <use href="#i-how_to_reg"></use>
                            </svg></div>
                        <h5 class="ee-u-mt6"><?php echo esc_html($ee_opt('step1_title')); ?></h5>
                        <p><?php echo esc_html($ee_opt('step1_text')); ?></p>
                    </div>
                    <div class="st-foot"><span>شروع سریع</span><svg class="ee-ic" aria-hidden="true" focusable="false">
                            <use href="#i-arrow_back"></use>
                        </svg></div>
                </div>
                <div class="ee-step st2">
                    <div>
                        <div class="st-top"><span class="st-num">۲</span><svg class="st-ic ee-ic" aria-hidden="true" focusable="false">
                                <use href="#i-checklist_rtl"></use>
                            </svg></div>
                        <h5 class="ee-u-mt6"><?php echo esc_html($ee_opt('step2_title')); ?></h5>
                        <p><?php echo esc_html($ee_opt('step2_text')); ?></p>
                    </div>
                    <div class="st-foot"><span>انتخاب مبحث</span><svg class="ee-ic" aria-hidden="true" focusable="false">
                            <use href="#i-arrow_back"></use>
                        </svg></div>
                </div>
                <div class="ee-step st3">
                    <div>
                        <div class="st-top"><span class="st-num">۳</span><svg class="st-ic ee-ic" aria-hidden="true" focusable="false">
                                <use href="#i-all_inclusive"></use>
                            </svg></div>
                        <h5 class="ee-u-mt6"><?php echo esc_html($ee_opt('step3_title')); ?></h5>
                        <p><?php echo esc_html($ee_opt('step3_text')); ?></p>
                    </div>
                    <div class="st-foot"><span>مشاهده دوره‌ها</span><svg class="ee-ic" aria-hidden="true" focusable="false">
                            <use href="#i-arrow_back"></use>
                        </svg></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======= بلاگ ویژه ======= -->
    <?php if (!empty($ee_blog_feature)) : ?>
        <?php
        $ee_bf_hero  = $ee_blog_feature[0];
        $ee_bf_rest  = array_slice($ee_blog_feature, 1, 3);
        $ee_bf_url   = get_permalink(get_option('page_for_posts')) ?: home_url('/');
        $ee_bf_hcat  = get_the_category($ee_bf_hero->ID);
        $ee_bf_hcat  = (is_array($ee_bf_hcat) && !empty($ee_bf_hcat)) ? $ee_bf_hcat[0]->name : __('مقالات', 'evented-edu');
        ?>
        <section class="ee-blog-feat" id="ee-blog">
            <div class="ee-wrap">
                <div class="ee-sec-head">
                    <div>
                        <h3 class="ee-sec-title purple"><span class="bar"></span> <?php echo esc_html($ee_opt('blog_feat_title')); ?></h3>
                        <p class="sec-sub"><?php echo esc_html($ee_opt('blog_feat_sub')); ?></p>
                    </div>
                    <a class="lc-more ee-u-lav" href="<?php echo esc_url($ee_bf_url); ?>">همهٔ نوشته‌ها <svg class="ee-ic" aria-hidden="true" focusable="false">
                            <use href="#i-arrow_back"></use>
                        </svg></a>
                </div>

                <div class="ee-bf-grid">
                    <!-- نوشتهٔ شاخص -->
                    <article class="ee-bf-hero">
                        <a class="bf-hero-media" href="<?php echo esc_url(get_permalink($ee_bf_hero)); ?>">
                            <?php if (has_post_thumbnail($ee_bf_hero)) : ?>
                                <img src="<?php echo esc_url(get_the_post_thumbnail_url($ee_bf_hero, 'large')); ?>" alt="<?php echo esc_attr(get_the_title($ee_bf_hero)); ?>" loading="lazy">
                            <?php else : ?>
                                <span class="bf-hero-noimg"><svg class="ee-ic" aria-hidden="true" focusable="false">
                                        <use href="#i-auto_stories"></use>
                                    </svg></span>
                            <?php endif; ?>
                            <?php if ($ee_meta_on('category')) : ?><span class="bf-hero-chip"><?php echo esc_html($ee_bf_hcat); ?></span><?php endif; ?>
                        </a>
                        <div class="bf-hero-body">
                            <span class="bf-hero-badge"><svg class="ee-ic" aria-hidden="true" focusable="false">
                                    <use href="#i-workspace_premium"></use>
                                </svg> <?php esc_html_e('نوشتهٔ ویژه', 'evented-edu'); ?></span>
                            <h4><a href="<?php echo esc_url(get_permalink($ee_bf_hero)); ?>"><?php echo esc_html(get_the_title($ee_bf_hero)); ?></a></h4>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt($ee_bf_hero), 26)); ?></p>
                            <div class="bf-hero-foot">
                                <span class="ee-article-meta-list">
                                    <?php if ($ee_meta_on('author')) : ?><span class="bf-by"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-person"></use></svg><?php echo esc_html(get_the_author_meta('display_name', (int) $ee_bf_hero->post_author)); ?></span><?php endif; ?>
                                    <?php if ($ee_meta_on('date')) : ?><span class="bf-date"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-calendar_month"></use></svg><?php echo esc_html(function_exists('evented_post_date') ? evented_post_date($ee_bf_hero) : get_the_date('', $ee_bf_hero)); ?></span><?php endif; ?>
                                    <?php if ($ee_meta_on('reading')) : ?><span title="<?php esc_attr_e('زمان مطالعه', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-schedule"></use></svg><?php echo esc_html(number_format_i18n(function_exists('evented_reading_time') ? evented_reading_time($ee_bf_hero->ID) : 1)); ?></span><?php endif; ?>
                                    <?php if ($ee_meta_on('views')) : ?><span title="<?php esc_attr_e('بازدید', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-visibility"></use></svg><?php echo esc_html(number_format_i18n(function_exists('evented_get_post_views') ? evented_get_post_views($ee_bf_hero->ID) : 0)); ?></span><?php endif; ?>
                                    <?php if ($ee_meta_on('comments')) : ?><span title="<?php esc_attr_e('دیدگاه', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-forum"></use></svg><?php echo esc_html(number_format_i18n((int) get_comments_number($ee_bf_hero->ID))); ?></span><?php endif; ?>
                                </span>
                                <a class="bf-read" href="<?php echo esc_url(get_permalink($ee_bf_hero)); ?>">مطالعه <svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg></a>
                            </div>
                        </div>
                    </article>

                    <!-- سایر نوشته‌های ویژه -->
                    <?php if (!empty($ee_bf_rest)) : ?>
                        <div class="ee-bf-list">
                            <?php foreach ($ee_bf_rest as $ee_bf) : ?>
                                <?php $ee_bf_cat = get_the_category($ee_bf->ID); ?>
                                <a class="ee-bf-row" href="<?php echo esc_url(get_permalink($ee_bf)); ?>">
                                    <span class="bf-row-media">
                                        <?php if (has_post_thumbnail($ee_bf)) : ?>
                                            <img src="<?php echo esc_url(get_the_post_thumbnail_url($ee_bf, 'medium')); ?>" alt="<?php echo esc_attr(get_the_title($ee_bf)); ?>" loading="lazy">
                                        <?php else : ?>
                                            <svg class="ee-ic" aria-hidden="true" focusable="false">
                                                <use href="#i-article"></use>
                                            </svg>
                                        <?php endif; ?>
                                    </span>
                                    <span class="bf-row-txt">
                                        <?php if ($ee_meta_on('category')) : ?><span class="bf-row-cat"><?php echo esc_html((is_array($ee_bf_cat) && !empty($ee_bf_cat)) ? $ee_bf_cat[0]->name : __('مقالات', 'evented-edu')); ?></span><?php endif; ?>
                                        <strong><?php echo esc_html(get_the_title($ee_bf)); ?></strong>
                                        <span class="bf-row-meta ee-article-meta-list">
                                            <?php if ($ee_meta_on('author')) : ?><span title="<?php esc_attr_e('نویسنده', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-person"></use></svg><?php echo esc_html(get_the_author_meta('display_name', (int) $ee_bf->post_author)); ?></span><?php endif; ?>
                                            <?php if ($ee_meta_on('date')) : ?><span title="<?php esc_attr_e('تاریخ انتشار', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-calendar_month"></use></svg><?php echo esc_html(function_exists('evented_post_date') ? evented_post_date($ee_bf) : get_the_date('', $ee_bf)); ?></span><?php endif; ?>
                                            <?php if ($ee_meta_on('reading')) : ?><span title="<?php esc_attr_e('زمان مطالعه', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-schedule"></use></svg><?php echo esc_html(number_format_i18n(function_exists('evented_reading_time') ? evented_reading_time($ee_bf->ID) : 1)); ?></span><?php endif; ?>
                                            <?php if ($ee_meta_on('views')) : ?><span title="<?php esc_attr_e('بازدید', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-visibility"></use></svg><?php echo esc_html(number_format_i18n(function_exists('evented_get_post_views') ? evented_get_post_views($ee_bf->ID) : 0)); ?></span><?php endif; ?>
                                            <?php if ($ee_meta_on('comments')) : ?><span title="<?php esc_attr_e('دیدگاه', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-forum"></use></svg><?php echo esc_html(number_format_i18n((int) get_comments_number($ee_bf->ID))); ?></span><?php endif; ?>
                                        </span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ======= مقالات (تب‌بندی بر اساس ۵ دستهٔ تصادفی؛ کش هفتگی) ======= -->
    <section class="ee-articles" id="ee-articles">
        <div class="ee-wrap">
            <div class="ee-sec-head">
                <div>
                    <h3 class="ee-sec-title purple"><span class="bar"></span> <?php echo esc_html($ee_opt('articles_title')); ?></h3>
                </div>
                <a class="lc-more ee-u-lav" href="<?php echo esc_url($ee_blog_url); ?>">مشاهده همه مقالات <svg class="ee-ic" aria-hidden="true" focusable="false">
                        <use href="#i-arrow_back"></use>
                    </svg></a>
            </div>

            <?php
            /* رندر یک کارت مقاله (برای تب‌ها و حالت بدون دسته) */
            $ee_render_article = static function ($a) use ($ee_meta_on) {
                $ee_pcat      = get_the_category($a->ID);
                $ee_pcat_name = $ee_pcat ? $ee_pcat[0]->name : 'مقالات';
            ?>
                <article class="ee-art-card">
                    <a class="art-thumb" href="<?php echo esc_url(get_permalink($a)); ?>">
                        <?php if (has_post_thumbnail($a)) : ?>
                            <img src="<?php echo esc_url(get_the_post_thumbnail_url($a, 'medium')); ?>" alt="<?php echo esc_attr(get_the_title($a)); ?>" loading="lazy">
                        <?php else : ?>
                            <svg class="ee-ic ee-u-fill-ic" aria-hidden="true" focusable="false">
                                <use href="#i-article"></use>
                            </svg>
                        <?php endif; ?>
                    </a>
                    <div>
                        <?php if ($ee_meta_on('category')) : ?><span class="art-tag ee-u-mint"><?php echo esc_html($ee_pcat_name); ?></span><?php endif; ?>
                        <h4><a href="<?php echo esc_url(get_permalink($a)); ?>"><?php echo esc_html(get_the_title($a)); ?></a></h4>
                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt($a), 16)); ?></p>
                    </div>
                    <div class="art-foot">
                        <span class="ee-article-meta-list">
                            <?php if ($ee_meta_on('author')) : ?><span title="<?php esc_attr_e('نویسنده', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-person"></use></svg><?php echo esc_html(get_the_author_meta('display_name', (int) $a->post_author)); ?></span><?php endif; ?>
                            <?php if ($ee_meta_on('date')) : ?><span title="<?php esc_attr_e('تاریخ انتشار', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-calendar_month"></use></svg><?php echo esc_html(function_exists('evented_post_date') ? evented_post_date($a) : get_the_date('', $a)); ?></span><?php endif; ?>
                            <?php if ($ee_meta_on('reading')) : ?><span title="<?php esc_attr_e('زمان مطالعه', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-schedule"></use></svg><?php echo esc_html(number_format_i18n(function_exists('evented_reading_time') ? evented_reading_time($a->ID) : 1)); ?></span><?php endif; ?>
                            <?php if ($ee_meta_on('views')) : ?><span title="<?php esc_attr_e('بازدید', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-visibility"></use></svg><?php echo esc_html(number_format_i18n(function_exists('evented_get_post_views') ? evented_get_post_views($a->ID) : 0)); ?></span><?php endif; ?>
                            <?php if ($ee_meta_on('comments')) : ?><span title="<?php esc_attr_e('دیدگاه', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-forum"></use></svg><?php echo esc_html(number_format_i18n((int) get_comments_number($a->ID))); ?></span><?php endif; ?>
                            <?php echo function_exists('evented_rating_badge_html') ? evented_rating_badge_html($a->ID) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </span>
                        <a class="read" href="<?php echo esc_url(get_permalink($a)); ?>">مطالعه کامل <svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg></a>
                    </div>
                </article>
            <?php
            };
            ?>

            <?php if (!empty($ee_article_tabs)) : ?>
                <div class="ee-chips ee-art-tabs" id="eeArtTabs" role="tablist" aria-label="دسته‌بندی مقالات">
                    <?php foreach ($ee_article_tabs as $ee_ti => $ee_tab) : ?>
                        <button class="ee-chip-btn<?php echo 0 === $ee_ti ? ' ee-on' : ''; ?>" type="button" role="tab"
                            id="eeArtTab<?php echo esc_attr($ee_ti); ?>"
                            aria-controls="eeArtPanel<?php echo esc_attr($ee_ti); ?>"
                            aria-selected="<?php echo 0 === $ee_ti ? 'true' : 'false'; ?>"
                            tabindex="<?php echo 0 === $ee_ti ? '0' : '-1'; ?>">
                            <?php echo esc_html($ee_tab['term']->name); ?>
                            <span class="ee-chip-count"><?php echo esc_html(number_format_i18n((int) $ee_tab['term']->count)); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($ee_article_tabs as $ee_ti => $ee_tab) : ?>
                    <div class="ee-art-panel" id="eeArtPanel<?php echo esc_attr($ee_ti); ?>" role="tabpanel" aria-labelledby="eeArtTab<?php echo esc_attr($ee_ti); ?>" <?php echo 0 === $ee_ti ? '' : ' hidden'; ?>>
                        <div class="ee-agrid">
                            <?php foreach ($ee_tab['posts'] as $a) {
                                $ee_render_article($a);
                            } ?>
                        </div>
                        <div class="ee-art-panel-foot">
                            <a class="ee-btn ee-btn-ghost" href="<?php echo esc_url(get_term_link($ee_tab['term'])); ?>">همهٔ مطالب «<?php echo esc_html($ee_tab['term']->name); ?>» <svg class="ee-ic" aria-hidden="true" focusable="false">
                                    <use href="#i-arrow_back"></use>
                                </svg></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif (!empty($ee_articles)) : ?>
                <div class="ee-agrid">
                    <?php foreach ($ee_articles as $a) {
                        $ee_render_article($a);
                    } ?>
                </div>
            <?php else : ?>
                <div class="ee-empty">هنوز مقاله‌ای منتشر نشده است.</div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($ee_testimonials)) : ?>
        <!-- ======= تجربهٔ دانشجویان (دیدگاه‌های واقعی دوره‌ها) ======= -->
        <section class="ee-testimonials" id="ee-testimonials">
            <div class="ee-wrap">
                <div class="ee-sec-head">
                    <div>
                        <h3 class="ee-sec-title"><span class="bar"></span> <?php echo esc_html($ee_opt('reviews_title')); ?></h3>
                        <p class="sec-sub"><?php echo esc_html($ee_opt('reviews_sub')); ?></p>
                    </div>
                </div>
                <div class="ee-testimonial-grid">
                    <?php foreach ($ee_testimonials as $ee_review) :
                        $ee_review_rating = max(1, min(5, (int) get_comment_meta($ee_review->comment_ID, 'review_rating', true)));
                        $ee_review_course = get_post($ee_review->comment_post_ID);
                        if (!$ee_review_course) {
                            continue;
                        }
                        ?>
                        <article class="ee-testimonial-card">
                            <div class="ee-testimonial-head">
                                <?php echo get_avatar($ee_review, 48, '', $ee_review->comment_author, array('loading' => 'lazy')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <div>
                                    <strong><?php echo esc_html($ee_review->comment_author); ?></strong>
                                    <a href="<?php echo esc_url(get_permalink($ee_review_course)); ?>"><?php echo esc_html(get_the_title($ee_review_course)); ?></a>
                                </div>
                                <span class="ee-testimonial-rating" aria-label="<?php echo esc_attr($ee_review_rating . ' از ۵'); ?>">★ <?php echo esc_html($ee_review_rating); ?></span>
                            </div>
                            <blockquote><?php echo esc_html(wp_trim_words(wp_strip_all_tags($ee_review->comment_content), 28)); ?></blockquote>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ======= اساتید ======= -->
    <section class="ee-instructors" id="ee-instructors">
        <div class="ee-wrap">
            <h3 class="ee-sec-title ee-u-center"><span class="bar"></span> <?php echo esc_html($ee_opt('instr_title')); ?></h3>
            <p class="sec-sub"><?php echo esc_html($ee_opt('instr_sub')); ?></p>
            <div class="ee-instr-row">
                <?php if (!empty($ee_instructors)) : foreach ($ee_instructors as $ins) :
                        $ins_av = get_avatar_url($ins->ID, array('size' => 96));
                        $ins_role = get_user_meta($ins->ID, 'instructor_user_role', true);
                ?>
                        <a class="ee-instr" href="<?php echo esc_url(get_author_posts_url($ins->ID)); ?>">
                            <span class="av-wrap"><span class="av"><?php if ($ins_av) : ?><img src="<?php echo esc_url($ins_av); ?>" alt="<?php echo esc_attr($ins->display_name); ?>"><?php else : ?><svg class="noimg ee-ic" aria-hidden="true" focusable="false">
                                            <use href="#i-person"></use>
                                        </svg><?php endif; ?></span></span>
                            <strong><?php echo esc_html($ins->display_name); ?></strong>
                            <span class="ee-role"><?php echo $ins_role ? esc_html($ins_role) : esc_html('مدرس'); ?></span>
                        </a>
                    <?php endforeach;
                else : ?>
                    <div class="ee-empty">هنوز استادی ثبت نشده است.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

</main>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'home')); ?>