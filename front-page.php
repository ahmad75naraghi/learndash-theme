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

/* ۳) دوره‌های «پیشنهادی» (آخرین دوره‌ها برای شبکهٔ کارت‌ها) */
$ee_courses_q = new WP_Query(array(
    'post_type'      => 'sfwd-courses',
    'posts_per_page' => 8,
    'no_found_rows'  => true,
));
$ee_courses = $ee_courses_q->posts;
wp_reset_postdata();

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
$ee_article_tabs = function_exists('evented_home_article_tabs') ? evented_home_article_tabs(5, 3) : array();

/* آدرس‌های منوی استاتیک (کش‌شده) */
$ee_nav = static function ($key, $fallback = '') {
    if (function_exists('evented_nav_url')) {
        return evented_nav_url($key);
    }
    return $fallback ?: home_url('/');
};
$ee_blog_url = $ee_nav('articles', get_permalink(get_option('page_for_posts')) ?: home_url('/'));

/* ۴٫۱) بلاگ ویژه — نوشته‌های چسبان و در ادامه آخرین نوشته‌ها */
$ee_blog_feature = function_exists('evented_featured_posts') ? evented_featured_posts(4) : array();

/* ۵) اساتید: کاربران دارای نقش group_leader */
$ee_instructors = get_users(array(
    'role'    => 'group_leader',
    'number'  => 8,
    'orderby' => 'display_name',
));

/* ۶) آمار سادهٔ دوره‌ها */
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
        'title'     => !empty($s['title']) ? $s['title'] : 'دوره‌های تخصصی فناوری اطلاعات',
        'desc'      => $s['desc'],
        'link'      => $s['link'],
        'link_text' => 'مشاهده و شروع',
    );
}

$ee_slide_count  = count($ee_feature_slides);
$ee_slider_mode  = $ee_slide_count > 1;

?>
<?php get_template_part('template-parts/ee', 'head'); ?>


    <?php get_template_part('template-parts/ee', 'header', array('ee_active' => 'home')); ?>

    <main class="ee-home-main">

        <!-- ======= هیرو: اسلایدر بنر + آخرین مقالات ======= -->
        <section class="ee-hero<?php echo empty($ee_feature_slides) ? ' is-no-slider' : ''; ?>">
            <div class="ee-wrap ee-hero-grid">

                <div class="ee-hero-feat ee-fade">

                    <?php if ($ee_slider_mode) : ?>
                        <div class="ee-slider" id="eeSlider" data-count="<?php echo esc_attr($ee_slide_count); ?>">
                    <?php endif; ?>

                    <?php foreach ($ee_feature_slides as $ee_i => $ee_s) : ?>
                        <div class="ee-feature<?php echo $ee_slider_mode ? ' ee-slide' : ''; ?><?php echo ($ee_slider_mode && $ee_i === 0) ? ' is-active' : ''; ?>"<?php echo $ee_slider_mode ? ' data-index="' . esc_attr($ee_i) . '"' : ''; ?>>
                            <div class="feat-media">
                                <img src="<?php echo esc_url($ee_s['img']); ?>" alt="<?php echo esc_attr($ee_s['title']); ?>" loading="<?php echo $ee_i === 0 ? 'eager' : 'lazy'; ?>">
                                <div class="feat-shade"></div>
                            </div>
                            <?php if (!empty($ee_s['badge'])) : ?>
                                <div class="feat-tags">
                                    <span class="ee-chip ee-chip-amber"><?php echo esc_html($ee_s['badge']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="feat-body">
                                <h1 class="feat-title"><?php echo esc_html($ee_s['title']); ?></h1>
                                <?php if (!empty($ee_s['desc'])) : ?>
                                    <p class="feat-desc"><?php echo esc_html($ee_s['desc']); ?></p>
                                <?php endif; ?>
                                <div class="feat-cta-row">
                                    <?php if (!empty($ee_s['link'])) : ?>
                                        <a class="ee-btn ee-btn-light" href="<?php echo esc_url($ee_s['link']); ?>"><span class="material-symbols-outlined ee-ic">visibility</span> <?php echo esc_html($ee_s['link_text']); ?></a>
                                    <?php endif; ?>
                                    <a class="ee-btn ee-btn-solid" href="#ee-courses">مشاهده همه دوره‌ها</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($ee_slider_mode) : ?>
                        </div><!-- /.ee-slider -->
                        <div class="ee-slider-controls" id="eeSliderControls">
                            <button type="button" class="ee-slide-arrow" data-dir="-1" aria-label="اسلاید قبلی"><span class="material-symbols-outlined ee-ic">chevron_right</span></button>
                            <div class="ee-slider-dots" id="eeSliderDots">
                                <?php for ($ee_d = 0; $ee_d < $ee_slide_count; $ee_d++) : ?>
                                    <button type="button" class="ee-slide-dot<?php echo $ee_d === 0 ? ' is-active' : ''; ?>" data-go="<?php echo esc_attr($ee_d); ?>" aria-label="اسلاید <?php echo esc_attr($ee_d + 1); ?>"></button>
                                <?php endfor; ?>
                            </div>
                            <button type="button" class="ee-slide-arrow" data-dir="1" aria-label="اسلاید بعدی"><span class="material-symbols-outlined ee-ic">chevron_left</span></button>
                        </div>
                    <?php endif; ?>

                </div>

                <aside class="ee-latest ee-fade">
                    <div class="ee-latest-card">
                        <div class="lc-head">
                            <div class="lc-title"><span class="material-symbols-outlined ee-ic">auto_stories</span> آخرین مقالات</div>
                            <a class="lc-more" href="<?php echo esc_url($ee_blog_url); ?>">مشاهده همه <span class="material-symbols-outlined ee-ic">arrow_back</span></a>
                        </div>
                        <div class="ee-news-list">
                            <?php foreach ($ee_latest_posts as $p) : ?>
                                <a href="<?php echo esc_url(get_permalink($p)); ?>">
                                    <?php if (has_post_thumbnail($p)) : ?>
                                        <img class="th" src="<?php echo esc_url(get_the_post_thumbnail_url($p, 'thumbnail')); ?>" alt="<?php echo esc_attr(get_the_title($p)); ?>">
                                    <?php else : ?>
                                        <span class="th ee-ic material-symbols-outlined" style="color:var(--ee-tealP);">article</span>
                                    <?php endif; ?>
                                    <div style="min-width:0;">
                                        <h4><?php echo esc_html(get_the_title($p)); ?></h4>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="ee-latest-card">
                        <div class="lc-foot">
                            <span class="channels-ic material-symbols-outlined ee-ic">hub</span>
                            <div>
                                <div class="ch-label">کانال‌های رسمی evented-edu</div>
                                <div class="ch-sub">عضویت در پیام‌رسان‌ها و شبکه‌های اجتماعی</div>
                            </div>
                        </div>
                    </div>
                </aside>

            </div>
        </section>

        <!-- ======= دسترسی سریع (کاشی‌ها) ======= -->
        <section class="ee-quick" id="ee-quick">
            <div class="ee-wrap">
                <div class="ee-sec-head">
                    <h3 class="ee-sec-title"><span class="bar"></span> دسترسی سریع به بخش‌های evented-edu</h3>
                    <span class="ee-sec-sub">مرجع تخصصی آموزش شبکه، سرور و امنیت</span>
                </div>
                <div class="ee-qgrid">
                    <a class="ee-qitem" href="<?php echo esc_url($ee_blog_url); ?>"><span class="qi-ic material-symbols-outlined ee-ic">menu_book</span><span>مقالات</span></a>
                    <a class="ee-qitem ee-q-purple" href="<?php echo esc_url($ee_nav('courses')); ?>"><span class="qi-ic material-symbols-outlined ee-ic">school</span><span>دوره‌ها</span></a>
                    <a class="ee-qitem ee-q-teal" href="<?php echo esc_url($ee_nav('video')); ?>"><span class="qi-ic material-symbols-outlined ee-ic">smart_display</span><span>ویدیوها</span></a>
                    <a class="ee-qitem ee-q-amber" href="<?php echo esc_url($ee_nav('downloads')); ?>"><span class="qi-ic material-symbols-outlined ee-ic">download_for_offline</span><span>دانلودها</span></a>
                    <a class="ee-qitem ee-q-rose" href="<?php echo esc_url($ee_nav('podcast')); ?>"><span class="qi-ic material-symbols-outlined ee-ic">podcasts</span><span>پادکست</span></a>
                    <a class="ee-qitem" href="<?php echo esc_url($ee_nav('library')); ?>"><span class="qi-ic material-symbols-outlined ee-ic">local_library</span><span>کتابخانه</span></a>
                    <a class="ee-qitem ee-q-purple" href="<?php echo esc_url($ee_nav('gallery')); ?>"><span class="qi-ic material-symbols-outlined ee-ic">photo_library</span><span>گالری</span></a>
                    <a class="ee-qitem ee-q-amber" href="<?php echo esc_url($ee_nav('contests')); ?>"><span class="qi-ic material-symbols-outlined ee-ic">emoji_events</span><span>مسابقات</span></a>
                </div>
            </div>
        </section>

        <!-- ======= دوره‌های تخصصی ======= -->
        <section class="ee-courses" id="ee-courses">
            <div class="ee-wrap">
                <div class="ee-courses-head">
                    <div>
                        <div class="ch2"><span class="dot"></span> دوره‌های آموزشی تخصصی</div>
                        <small>آموزش کاربردی فناوری اطلاعات با حضور اساتید و متخصصان تراز اول</small>
                    </div>
                    <span class="ee-count-badge"><?php echo esc_html($ee_course_count); ?> دوره فعال</span>
                </div>

                <?php if (!empty($ee_cats)) : ?>
                    <div class="ee-chips">
                        <a class="ee-chip-btn ee-on" href="<?php echo esc_url($ee_nav('courses')); ?>">همه دوره‌ها</a>
                        <?php foreach ($ee_cats as $cat) :
                            $ee_cat_link = get_term_link($cat);
                            if (is_wp_error($ee_cat_link)) { continue; } ?>
                            <a class="ee-chip-btn" href="<?php echo esc_url($ee_cat_link); ?>"><?php echo esc_html($cat->name); ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="ee-cgrid" id="eeCourseGrid">
                    <?php if (!empty($ee_courses)) : foreach ($ee_courses as $c) :
                        $ee_thumb = get_the_post_thumbnail_url($c->ID, 'medium');
                        $ee_price = get_post_meta($c->ID, '_sfwd-courses', true);
                        $ee_ptype = isset($ee_price['sfwd-courses_course_price_type']) ? $ee_price['sfwd-courses_course_price_type'] : '';
                        $ee_amount = isset($ee_price['sfwd-courses_course_price']) ? $ee_price['sfwd-courses_course_price'] : '';
                        $ee_free = ($ee_ptype === 'free' || empty($ee_amount));
                        $ee_author = get_userdata((int) $c->post_author);
                        $ee_cat_terms = wp_get_post_terms($c->ID, 'ld_course_category', array('fields' => 'names'));
                    ?>
                        <article class="ee-course-card">
                            <a class="cc-thumb" href="<?php echo esc_url(get_permalink($c)); ?>">
                                <?php if ($ee_thumb) : ?>
                                    <img src="<?php echo esc_url($ee_thumb); ?>" alt="<?php echo esc_attr(get_the_title($c)); ?>" loading="lazy">
                                <?php else : ?>
                                    <span class="ee-ic material-symbols-outlined" style="font-size:2.6rem;color:var(--ee-tealP);position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">school</span>
                                <?php endif; ?>
                                <?php if ($ee_free) : ?>
                                    <span class="cc-badge free">رایگان</span>
                                <?php else : ?>
                                    <span class="cc-badge hot">ویژه</span>
                                <?php endif; ?>
                            </a>
                            <div class="cc-body">
                                <h3 class="cc-title"><a href="<?php echo esc_url(get_permalink($c)); ?>"><?php echo esc_html(get_the_title($c)); ?></a></h3>
                                <div class="cc-instructor"><span class="material-symbols-outlined ee-ic">school</span> <?php echo $ee_author ? esc_html($ee_author->display_name) : 'نامشخص'; ?></div>
                                <div class="cc-foot">
                                    <span class="cc-lessons"><?php echo $ee_cat_terms ? esc_html(implode('، ', array_slice($ee_cat_terms, 0, 2))) : 'دوره تخصصی'; ?></span>
                                    <span class="cc-price <?php echo $ee_free ? '' : 'amber'; ?>"><?php echo $ee_free ? 'رایگان' : esc_html(number_format((float) $ee_amount) . ' تومان'); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; else : ?>
                        <div class="ee-empty">هنوز دوره‌ای ثبت نشده است. به‌زودی دوره‌های تخصصی اضافه می‌شوند.</div>
                    <?php endif; ?>
                </div>

                <!-- نوار CTA کهربایی -->
                <div class="ee-cta-amber">
                    <div class="cta-text">
                        <span class="cta-ic material-symbols-outlined ee-ic">workspace_premium</span>
                        <div>
                            <h4>ثبت‌نام رسمی، شرکت در آزمون و دریافت مدرک معتبر</h4>
                            <p>فعال‌سازی دسترسی به جزوات تخصصی، آزمون‌های دوره و گواهی پایان دوره</p>
                        </div>
                    </div>
                    <a class="ee-btn-amber" href="<?php echo is_user_logged_in() ? esc_url(home_url('/panel')) : esc_url(home_url('/login')); ?>">شروع یادگیری رایگان</a>
                </div>
            </div>
        </section>

        <!-- ======= ۳ گام عضویت ======= -->
        <section class="ee-steps" id="ee-steps">
            <div class="ee-wrap">
                <div class="ee-steps-top">
                    <div>
                        <span class="st-badge"><span class="material-symbols-outlined ee-ic">route</span> مسیر آسان و گام‌به‌گام آموزش</span>
                        <h3 style="margin-top:.5rem;">۳ گام ساده تا یادگیری مهارت IT</h3>
                        <p>در چند دقیقه و بدون پیچیدگی، به دوره‌های تخصصی شبکه، سرور و امنیت دسترسی پیدا کنید.</p>
                    </div>
                </div>
                <div class="ee-steps-grid">
                    <div class="ee-step">
                        <div>
                            <div class="st-top"><span class="st-num">۱</span><span class="st-ic material-symbols-outlined ee-ic">how_to_reg</span></div>
                            <h5 style="margin:.6rem 0 .2rem;">عضویت کاملاً رایگان</h5>
                            <p>ثبت‌نام سریع تنها با یک شماره همراه، بدون نیاز به مدارک پیچیده</p>
                        </div>
                        <div class="st-foot"><span>شروع سریع</span><span class="material-symbols-outlined ee-ic">arrow_back</span></div>
                    </div>
                    <div class="ee-step st2">
                        <div>
                            <div class="st-top"><span class="st-num">۲</span><span class="st-ic material-symbols-outlined ee-ic">checklist_rtl</span></div>
                            <h5 style="margin:.6rem 0 .2rem;">انتخاب دورهٔ دلخواه</h5>
                            <p>فعال‌سازی دوره‌های شبکه، سرور، امنیت و مجازی‌سازی متناسب با سطح خود</p>
                        </div>
                        <div class="st-foot"><span>انتخاب مبحث</span><span class="material-symbols-outlined ee-ic">arrow_back</span></div>
                    </div>
                    <div class="ee-step st3">
                        <div>
                            <div class="st-top"><span class="st-num">۳</span><span class="st-ic material-symbols-outlined ee-ic">all_inclusive</span></div>
                            <h5 style="margin:.6rem 0 .2rem;">دسترسی نامحدود و دائمی</h5>
                            <p>مشاهدهٔ ویدیوها، دریافت جزوات و شرکت در آزمون در هر زمان و مکان</p>
                        </div>
                        <div class="st-foot"><span>مشاهده دوره‌ها</span><span class="material-symbols-outlined ee-ic">arrow_back</span></div>
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
                            <h3 class="ee-sec-title purple"><span class="bar"></span> بلاگ ویژه</h3>
                            <p class="sec-sub">منتخبی از نوشته‌های تیم آموزشی؛ تازه‌ترین تجربه‌ها و راهنماهای کاربردی</p>
                        </div>
                        <a class="lc-more" style="color:#7e22ce;" href="<?php echo esc_url($ee_bf_url); ?>">همهٔ نوشته‌ها <span class="material-symbols-outlined ee-ic">arrow_back</span></a>
                    </div>

                    <div class="ee-bf-grid">
                        <!-- نوشتهٔ شاخص -->
                        <article class="ee-bf-hero">
                            <a class="bf-hero-media" href="<?php echo esc_url(get_permalink($ee_bf_hero)); ?>">
                                <?php if (has_post_thumbnail($ee_bf_hero)) : ?>
                                    <img src="<?php echo esc_url(get_the_post_thumbnail_url($ee_bf_hero, 'large')); ?>" alt="<?php echo esc_attr(get_the_title($ee_bf_hero)); ?>" loading="lazy">
                                <?php else : ?>
                                    <span class="bf-hero-noimg"><span class="material-symbols-outlined ee-ic">auto_stories</span></span>
                                <?php endif; ?>
                                <span class="bf-hero-chip"><?php echo esc_html($ee_bf_hcat); ?></span>
                            </a>
                            <div class="bf-hero-body">
                                <span class="bf-hero-badge"><span class="material-symbols-outlined ee-ic">workspace_premium</span> <?php esc_html_e('نوشتهٔ ویژه', 'evented-edu'); ?></span>
                                <h4><a href="<?php echo esc_url(get_permalink($ee_bf_hero)); ?>"><?php echo esc_html(get_the_title($ee_bf_hero)); ?></a></h4>
                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt($ee_bf_hero), 26)); ?></p>
                                <div class="bf-hero-foot">
                                    <span class="bf-by">
                                        <span class="material-symbols-outlined ee-ic">person</span>
                                        <?php echo esc_html(get_the_author_meta('display_name', (int) $ee_bf_hero->post_author)); ?>
                                    </span>
                                    <span class="bf-date">
                                        <span class="material-symbols-outlined ee-ic">calendar_month</span>
                                        <?php echo esc_html(function_exists('evented_post_date') ? evented_post_date($ee_bf_hero) : get_the_date('', $ee_bf_hero)); ?>
                                    </span>
                                    <a class="bf-read" href="<?php echo esc_url(get_permalink($ee_bf_hero)); ?>">مطالعه <span class="material-symbols-outlined ee-ic">arrow_back</span></a>
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
                                                <span class="material-symbols-outlined ee-ic">article</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="bf-row-txt">
                                            <span class="bf-row-cat"><?php echo esc_html((is_array($ee_bf_cat) && !empty($ee_bf_cat)) ? $ee_bf_cat[0]->name : __('مقالات', 'evented-edu')); ?></span>
                                            <strong><?php echo esc_html(get_the_title($ee_bf)); ?></strong>
                                            <span class="bf-row-meta">
                                                <span class="material-symbols-outlined ee-ic">calendar_month</span>
                                                <?php echo esc_html(function_exists('evented_post_date') ? evented_post_date($ee_bf) : get_the_date('', $ee_bf)); ?>
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
                        <h3 class="ee-sec-title purple"><span class="bar"></span> گزیده مقالات و دانستنی‌های فناوری اطلاعات</h3>
                    </div>
                    <a class="lc-more" style="color:#7e22ce;" href="<?php echo esc_url($ee_blog_url); ?>">مشاهده همه مقالات <span class="material-symbols-outlined ee-ic">arrow_back</span></a>
                </div>

                <?php
                /* رندر یک کارت مقاله (برای تب‌ها و حالت بدون دسته) */
                $ee_render_article = static function ($a) {
                    $ee_pcat      = get_the_category($a->ID);
                    $ee_pcat_name = $ee_pcat ? $ee_pcat[0]->name : 'مقالات';
                    ?>
                    <article class="ee-art-card">
                        <a class="art-thumb" href="<?php echo esc_url(get_permalink($a)); ?>">
                            <?php if (has_post_thumbnail($a)) : ?>
                                <img src="<?php echo esc_url(get_the_post_thumbnail_url($a, 'medium')); ?>" alt="<?php echo esc_attr(get_the_title($a)); ?>" loading="lazy">
                            <?php else : ?>
                                <span class="ee-ic material-symbols-outlined" style="width:100%;height:100%;font-size:2.4rem;color:var(--ee-tealP);">article</span>
                            <?php endif; ?>
                        </a>
                        <div>
                            <span class="art-tag" style="background:var(--ee-mint);color:#065f46;"><?php echo esc_html($ee_pcat_name); ?></span>
                            <h4><a href="<?php echo esc_url(get_permalink($a)); ?>"><?php echo esc_html(get_the_title($a)); ?></a></h4>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt($a), 16)); ?></p>
                        </div>
                        <div class="art-foot">
                            <span><?php echo esc_html(function_exists('evented_post_date') ? evented_post_date($a) : get_the_date('', $a)); ?></span>
                            <a class="read" href="<?php echo esc_url(get_permalink($a)); ?>">مطالعه کامل <span class="material-symbols-outlined ee-ic">arrow_back</span></a>
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
                        <div class="ee-art-panel" id="eeArtPanel<?php echo esc_attr($ee_ti); ?>" role="tabpanel" aria-labelledby="eeArtTab<?php echo esc_attr($ee_ti); ?>"<?php echo 0 === $ee_ti ? '' : ' hidden'; ?>>
                            <div class="ee-agrid">
                                <?php foreach ($ee_tab['posts'] as $a) { $ee_render_article($a); } ?>
                            </div>
                            <div class="ee-art-panel-foot">
                                <a class="ee-btn ee-btn-ghost" href="<?php echo esc_url(get_term_link($ee_tab['term'])); ?>">همهٔ مطالب «<?php echo esc_html($ee_tab['term']->name); ?>» <span class="material-symbols-outlined ee-ic">arrow_back</span></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php elseif (!empty($ee_articles)) : ?>
                    <div class="ee-agrid">
                        <?php foreach ($ee_articles as $a) { $ee_render_article($a); } ?>
                    </div>
                <?php else : ?>
                    <div class="ee-empty">هنوز مقاله‌ای منتشر نشده است.</div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ======= اساتید ======= -->
        <section class="ee-instructors" id="ee-instructors">
            <div class="ee-wrap">
                <h3 class="ee-sec-title" style="justify-content:center;"><span class="bar"></span> اساتید و متخصصان برجسته evented-edu</h3>
                <p class="sec-sub">همراهی اساتید تراز اول در حوزه شبکه، سرور، امنیت و زیرساخت</p>
                <div class="ee-instr-row">
                    <?php if (!empty($ee_instructors)) : foreach ($ee_instructors as $ins) :
                        $ins_av = get_avatar_url($ins->ID, array('size' => 96));
                        $ins_role = get_user_meta($ins->ID, 'instructor_user_role', true);
                    ?>
                        <a class="ee-instr" href="<?php echo esc_url(get_author_posts_url($ins->ID)); ?>">
                            <span class="av-wrap"><span class="av"><?php if ($ins_av) : ?><img src="<?php echo esc_url($ins_av); ?>" alt="<?php echo esc_attr($ins->display_name); ?>"><?php else : ?><span class="noimg material-symbols-outlined ee-ic">person</span><?php endif; ?></span></span>
                            <strong><?php echo esc_html($ins->display_name); ?></strong>
                            <span class="ee-role"><?php echo $ins_role ? esc_html($ins_role) : esc_html('مدرس evented-edu'); ?></span>
                        </a>
                    <?php endforeach; else : ?>
                        <div class="ee-empty">هنوز استادی ثبت نشده است.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    </main>

    <?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'home')); ?>
