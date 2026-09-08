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

/* ۲) بنر شاخص: جدیدترین دورهٔ دارای تصویر شاخص */
$ee_featured_q = new WP_Query(array(
    'post_type'           => 'sfwd-courses',
    'posts_per_page'      => 1,
    'meta_key'            => '_thumbnail_id',
    'orderby'             => 'date',
    'order'               => 'DESC',
    'no_found_rows'       => true,
));
$ee_featured = $ee_featured_q->have_posts() ? $ee_featured_q->posts[0] : null;
wp_reset_postdata();

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

/* ۵) اساتید: کاربران دارای نقش group_leader */
$ee_instructors = get_users(array(
    'role'    => 'group_leader',
    'number'  => 8,
    'orderby' => 'display_name',
));

/* ۶) آمار سادهٔ دوره‌ها */
$ee_course_count = wp_count_posts('sfwd-courses');
$ee_course_count = isset($ee_course_count->publish) ? (int) $ee_course_count->publish : 0;

// تصویر بنر: تصویر شاخص دورهٔ ویژه یا تصویر پیش‌فرض محلی
$ee_hero_img = get_the_post_thumbnail_url($ee_featured ? $ee_featured->ID : 0, 'large');
if (!$ee_hero_img) {
    $ee_hero_img = PATH_DIR_URL . '/assets/img/front-page/evented-edu-hero.png';
}

/* یافتن «بله» یا پیام‌رسان در فوتر فعلی: placeholder ثابت استفاده می‌شود (انتخاب کاربر) */
$ee_channel_id = 'channel-id'; // TODO: شناسهٔ واقعی کانال

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class('ee-home ee-body-pad'); ?>>


    <!-- ======= نوار ابزار بالایی (فقط دسکتاپ) ======= -->
    <div class="ee-topbar">
        <div class="ee-wrap ee-topbar-in">
            <div class="tb-right">
                <span class="ee-tb-item">
                    <span class="material-symbols-outlined ee-ic" style="color:var(--ee-tealP);font-size:1rem;">calendar_month</span>
                    <?php
                    /* تاریخ شمسی: اگر افزونهٔ جلالی نبود از میلادی استفاده می‌کنیم */
                    $ee_now = current_time('Y/m/d');
                    echo esc_html('امروز: ' . $ee_now);
                    ?>
                </span>
                <span class="ee-tb-sep">|</span>
                <span class="ee-tb-item"><span class="text-slate-500 font-medium">کانال‌های رسمی:</span></span>
                <a class="ee-tb-item" href="#" style="color:var(--ee-tealP);font-weight:600;"><span class="dot" style="background:#10b981;"></span>بله</a>
                <a class="ee-tb-item" href="#" style="color:#b45309;font-weight:600;"><span class="dot" style="background:#f59e0b;"></span>ایتا</a>
                <a class="ee-tb-item" href="#" style="color:#7e22ce;font-weight:600;"><span class="dot" style="background:#a855f7;"></span>روبیکا</a>
            </div>
            <div class="ee-tb-links">
                <a href="#">راهنمای دوره‌ها</a>
                <span class="ee-tb-sep">|</span>
                <a href="#">گواهی پایان دوره</a>
                <span class="ee-tb-sep">|</span>
                <a href="#">پشتیبانی آنلاین</a>
            </div>
        </div>
    </div>

    <!-- ======= هدر (چسبان، شیشه‌ای) ======= -->
    <header class="ee-header">
        <div class="ee-wrap">
            <div class="ee-header-row">
                <div class="ee-hamb ee-ic" id="eeHamb" aria-label="منو"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg></div>

                <a class="ee-logo" href="<?php echo esc_url(home_url('/')); ?>">
                    <img src="<?php echo esc_url(PATH_DIR_URL . '/assets/img/front-page/evented-edu-logo.webp'); ?>" alt="evented-edu">
                </a>

                <form class="ee-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <span class="s-ic material-symbols-outlined ee-ic">search</span>
                    <input type="search" name="s" placeholder="جستجو در دوره‌ها، اساتید، مقالات..." value="<?php echo esc_attr(get_search_query()); ?>">
                    <span class="s-tune material-symbols-outlined ee-ic">tune</span>
                </form>

                <div class="ee-h-actions">
                    <?php if (is_user_logged_in()) : ?>
                        <a class="ee-btn ee-btn-ghost" href="<?php echo esc_url(home_url('/panel')); ?>"><span class="material-symbols-outlined ee-ic">person</span> پنل کاربری</a>
                    <?php else : ?>
                        <a class="ee-btn ee-btn-ghost" href="<?php echo esc_url(home_url('/login')); ?>"><span class="material-symbols-outlined ee-ic">person</span> ورود / عضویت</a>
                    <?php endif; ?>
                    <button class="ee-btn ee-btn-soft ee-ic" type="button" aria-label="اعلان‌ها"><span class="material-symbols-outlined ee-ic">notifications_none</span></button>
                </div>
            </div>

            <div class="ee-search-m">
                <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <div class="ee-search" style="display:block;">
                        <span class="s-ic material-symbols-outlined ee-ic">search</span>
                        <input type="search" name="s" placeholder="جستجو در دوره‌ها، مقالات، اساتید..." value="<?php echo esc_attr(get_search_query()); ?>">
                    </div>
                </form>
            </div>

            <nav class="ee-nav" id="eeNav">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="ee-active">صفحه نخست</a>
                <a href="#ee-courses">دوره‌های آموزشی</a>
                <a href="#ee-articles">مقالات و پژوهش‌ها</a>
                <a href="#ee-instructors">اساتید و کارشناسان</a>
                <a href="#ee-contact">درباره و تماس</a>
            </nav>
        </div>
    </header>

    <main class="ee-home-main">

        <!-- ======= هیرو: بنر شاخص + آخرین مقالات ======= -->
        <section class="ee-hero">
            <div class="ee-wrap ee-hero-grid">

                <div class="ee-feature ee-fade">
                    <div class="feat-media">
                        <img src="<?php echo esc_url($ee_hero_img); ?>" alt="evented-edu">
                        <div class="feat-shade"></div>
                    </div>
                    <div class="feat-tags">
                        <span class="ee-chip ee-chip-amber">دورهٔ ویژه</span>
                        <span class="ee-chip ee-chip-glass"><?php echo $ee_featured ? esc_html(get_the_title($ee_featured)) : 'دوره‌های تخصصی IT'; ?></span>
                    </div>
                    <div class="feat-body">
                        <div class="feat-kicker"><?php echo esc_html('آموزشگاه آنلاین تخصصی فناوری اطلاعات'); ?></div>
                        <h1 class="feat-title"><?php echo $ee_featured ? esc_html(get_the_title($ee_featured)) : 'متخصص شدن در دنیای IT'; ?></h1>
                        <p class="feat-desc"><?php echo esc_html('دوره‌های کاربردی شبکه، سرور، امنیت و مجازی‌سازی با اساتید خبره — مسیر یادگیری تا بازار کار.'); ?></p>
                        <div class="feat-cta-row">
                            <?php if ($ee_featured) : ?>
                                <a class="ee-btn ee-btn-light" href="<?php echo esc_url(get_permalink($ee_featured)); ?>"><span class="material-symbols-outlined ee-ic">visibility</span> مشاهده دوره</a>
                            <?php endif; ?>
                            <a class="ee-btn ee-btn-solid" href="#ee-courses">مشاهده همه دوره‌ها</a>
                        </div>
                    </div>
                </div>

                <aside class="ee-latest ee-fade">
                    <div class="ee-latest-card">
                        <div class="lc-head">
                            <div class="lc-title"><span class="material-symbols-outlined ee-ic">auto_stories</span> آخرین مقالات</div>
                            <a class="lc-more" href="<?php echo esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/')); ?>">مشاهده همه <span class="material-symbols-outlined ee-ic">arrow_back</span></a>
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
                    <a class="ee-qitem" href="#ee-articles"><span class="qi-ic material-symbols-outlined ee-ic">menu_book</span><span>مقالات</span></a>
                    <a class="ee-qitem ee-q-amber" href="#"><span class="qi-ic material-symbols-outlined ee-ic">military_tech</span><span>آزمون‌ها</span></a>
                    <a class="ee-qitem ee-q-purple" href="#ee-courses"><span class="qi-ic material-symbols-outlined ee-ic">podcasts</span><span>دوره‌ها</span></a>
                    <a class="ee-qitem ee-q-teal" href="#ee-courses"><span class="qi-ic material-symbols-outlined ee-ic">smart_display</span><span>ویدیوها</span></a>
                    <a class="ee-qitem ee-q-amber" href="#"><span class="qi-ic material-symbols-outlined ee-ic">download_for_offline</span><span>دانلودها</span></a>
                    <a class="ee-qitem" href="#ee-instructors"><span class="qi-ic material-symbols-outlined ee-ic">local_library</span><span>اساتید</span></a>
                    <a class="ee-qitem ee-q-rose" href="#"><span class="qi-ic material-symbols-outlined ee-ic">quiz</span><span>گواهی‌ها</span></a>
                    <a class="ee-qitem ee-q-teal" href="#"><span class="qi-ic material-symbols-outlined ee-ic">psychology</span><span>مشاوره</span></a>
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
                        <button class="ee-chip-btn ee-on" type="button">همه دوره‌ها</button>
                        <?php foreach ($ee_cats as $cat) : ?>
                            <button class="ee-chip-btn" type="button"><?php echo esc_html($cat->name); ?></button>
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

        <!-- ======= مقالات منتخب ======= -->
        <section class="ee-articles" id="ee-articles">
            <div class="ee-wrap">
                <div class="ee-sec-head">
                    <div>
                        <h3 class="ee-sec-title purple"><span class="bar"></span> گزیده مقالات و دانستنی‌های فناوری اطلاعات</h3>
                    </div>
                    <a class="lc-more" style="color:#7e22ce;" href="<?php echo esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/')); ?>">مشاهده همه مقالات <span class="material-symbols-outlined ee-ic">arrow_back</span></a>
                </div>
                <div class="ee-agrid">
                    <?php foreach ($ee_articles as $a) : ?>
                        <article class="ee-art-card">
                            <a class="art-thumb" href="<?php echo esc_url(get_permalink($a)); ?>">
                                <?php if (has_post_thumbnail($a)) : ?>
                                    <img src="<?php echo esc_url(get_the_post_thumbnail_url($a, 'medium')); ?>" alt="<?php echo esc_attr(get_the_title($a)); ?>" loading="lazy">
                                <?php else : ?>
                                    <span class="ee-ic material-symbols-outlined" style="width:100%;height:100%;font-size:2.4rem;color:var(--ee-tealP);">article</span>
                                <?php endif; ?>
                            </a>
                            <div>
                                <?php
                                $ee_pcat = get_the_category($a->ID);
                                $ee_pcat_name = $ee_pcat ? $ee_pcat[0]->name : 'مقالات';
                                ?>
                                <span class="art-tag" style="background:var(--ee-mint);color:#065f46;"><?php echo esc_html($ee_pcat_name); ?></span>
                                <h4><a href="<?php echo esc_url(get_permalink($a)); ?>"><?php echo esc_html(get_the_title($a)); ?></a></h4>
                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt($a), 16)); ?></p>
                            </div>
                            <div class="art-foot">
                                <span><?php echo esc_html(get_the_date('', $a)); ?></span>
                                <a class="read" href="<?php echo esc_url(get_permalink($a)); ?>">مطالعه کامل <span class="material-symbols-outlined ee-ic">arrow_back</span></a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
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

    <!-- ======= فوتر ======= -->
    <footer class="ee-footer" id="ee-contact">
        <div class="ee-wrap ee-fwrap">
            <div class="ee-fgrid">
                <div class="about">
                    <div class="ee-f-logo">
                        <img src="<?php echo esc_url(PATH_DIR_URL . '/assets/img/front-page/evented-edu-logo.webp'); ?>" alt="evented-edu" style="height:38px;width:auto;">
                    </div>
                    <p>مرجع تخصصی آموزش‌های آنلاین فناوری اطلاعات؛ شبکه، سرور، امنیت، مجازی‌سازی و CRM با همراهی برترین اساتید کشور.</p>
                    <div class="ee-license"><span class="pulse"></span> دارای مجوز رسمی برگزاری دوره‌های آموزش فناوری</div>
                </div>
                <div>
                    <h4>دوره‌های تخصصی</h4>
                    <ul>
                        <li><a href="#ee-courses">• شبکه و زیرساخت</a></li>
                        <li><a href="#ee-courses">• سرور و مجازی‌سازی</a></li>
                        <li><a href="#ee-courses">• امنیت اطلاعات</a></li>
                        <li><a href="#ee-courses">• مدیریت سیستم و CRM</a></li>
                    </ul>
                </div>
                <div>
                    <h4>بخش‌های پایگاه</h4>
                    <ul>
                        <li><a href="#ee-articles">• مقالات تخصصی</a></li>
                        <li><a href="#ee-instructors">• اساتید و کارشناسان</a></li>
                        <li><a href="#ee-courses">• آزمون و گواهی</a></li>
                        <li><a href="#">• درباره ما</a></li>
                    </ul>
                </div>
                <div>
                    <h4>کانال‌های رسمی در پیام‌رسان‌ها</h4>
                    <div class="ee-fchan">
                        <a href="#"><span class="cn"><span class="dot" style="background:#10b981;"></span> پیام‌رسان بله</span><span class="id"><em>ble.ir/</em><?php echo esc_html($ee_channel_id); ?></span></a>
                        <a href="#"><span class="cn"><span class="dot" style="background:#f59e0b;"></span> پیام‌رسان ایتا</span><span class="id"><em>eitaa.com/</em><?php echo esc_html($ee_channel_id); ?></span></a>
                        <a href="#"><span class="cn"><span class="dot" style="background:#a855f7;"></span> پیام‌رسان روبیکا</span><span class="id"><em>rubika.ir/</em><?php echo esc_html($ee_channel_id); ?></span></a>
                    </div>
                    <div class="ee-fmeta">
                        <div><span class="mk">شناسه کانال‌ها:</span><span class="mv" style="direction:ltr;">@<?php echo esc_html($ee_channel_id); ?></span></div>
                        <div><span class="mk">پشتیبانی:</span><span class="mv"><?php echo esc_html(get_bloginfo('admin_email')); ?></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="ee-fbottom">
            <div class="ee-wrap">تمام حقوق مادی و معنوی این وب‌سایت متعلق به آموزشگاه آنلاین evented-edu است.</div>
        </div>
    </footer>

    <!-- نوار پایین موبایل -->
    <nav class="ee-mnav">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="ee-active"><span class="material-symbols-outlined ee-ic">home</span> خانه</a>
        <a href="#ee-courses"><span class="material-symbols-outlined ee-ic">school</span> دوره‌ها</a>
        <a href="#ee-articles"><span class="material-symbols-outlined ee-ic">article</span> مقالات</a>
        <a href="<?php echo is_user_logged_in() ? esc_url(home_url('/panel')) : esc_url(home_url('/login')); ?>"><span class="material-symbols-outlined ee-ic">account_circle</span> حساب من</a>
    </nav>

<?php wp_footer(); ?>
</body>
</html>
