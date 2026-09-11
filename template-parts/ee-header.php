<?php
/**
 * هدر مشترک طراحی «evented-edu» (نسخهٔ pastel)
 *
 * این فایل از front-page.php استخراج شده تا صفحهٔ اصلی، تک‌نوشته و
 * آرشیو نوشته‌ها یک هدر واحد داشته باشند (بدون تکرار مارک‌آپ).
 *
 * آرگومان‌های اختیاری (از طریق get_template_part):
 *   ee_active  — کلید آیتم فعال منو: home|courses|articles|instructors|contact
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_active = isset($args['ee_active']) ? $args['ee_active'] : '';

/* در صفحهٔ اصلی، لینک‌های منو به لنگرهای همان صفحه می‌روند؛ در سایر صفحات به آدرس کامل */
$ee_on_front = is_front_page();

/**
 * ساخت آدرس لنگر/صفحهٔ مقصد برای آیتم‌های ناوبری.
 *
 * @param string $anchor   شناسهٔ بخش در صفحهٔ اصلی (بدون #).
 * @param string $page     مسیر برگهٔ واقعی (در صورت وجود) — مثلاً 'courses'.
 * @return string
 */
$ee_nav_url = static function ($anchor, $page = '') use ($ee_on_front) {
	if ($ee_on_front) {
		return '#' . $anchor;
	}

	if ('' !== $page) {
		$ee_page = get_page_by_path($page);
		if ($ee_page instanceof WP_Post) {
			return (string) get_permalink($ee_page);
		}
	}

	return home_url('/#' . $anchor);
};

/* «مقالات» به برگهٔ نوشته‌ها می‌رود (اگر تنظیم شده باشد) */
$ee_posts_page_id = (int) get_option('page_for_posts');
$ee_articles_url  = $ee_on_front
	? '#ee-articles'
	: ($ee_posts_page_id ? (string) get_permalink($ee_posts_page_id) : home_url('/#ee-articles'));

$ee_url_home        = (string) home_url('/');
$ee_url_courses     = $ee_nav_url('ee-courses', 'courses');
$ee_url_instructors = $ee_nav_url('ee-instructors', 'instructors');
$ee_url_contact     = $ee_on_front ? '#ee-contact' : home_url('/#ee-contact');

/** کلاس فعال بودن آیتم منو. */
$ee_is_active = static function ($key) use ($ee_active) {
	return $ee_active === $key ? ' class="ee-active"' : '';
};

/* تاریخ امروز (در صورت فعال بودن افزونهٔ شمسی‌ساز، خودکار جلالی است) */
$ee_today = function_exists('evented_wp_date') ? evented_wp_date('Y/m/d') : date('Y/m/d');
?>
    <!-- ======= نوار ابزار بالایی (فقط دسکتاپ) ======= -->
    <div class="ee-topbar">
        <div class="ee-wrap ee-topbar-in">
            <div class="tb-right">
                <span class="ee-tb-item">
                    <span class="material-symbols-outlined ee-ic" style="color:var(--ee-tealP);font-size:1rem;">calendar_month</span>
                    <?php echo esc_html('امروز: ' . $ee_today); ?>
                </span>
                <span class="ee-tb-sep">|</span>
                <span class="ee-tb-item"><span class="ee-tb-label">کانال‌های رسمی:</span></span>
                <a class="ee-tb-item" href="<?php echo esc_url($ee_url_contact); ?>" style="color:var(--ee-tealP);font-weight:600;"><span class="dot" style="background:#10b981;"></span>بله</a>
                <a class="ee-tb-item" href="<?php echo esc_url($ee_url_contact); ?>" style="color:#b45309;font-weight:600;"><span class="dot" style="background:#f59e0b;"></span>ایتا</a>
                <a class="ee-tb-item" href="<?php echo esc_url($ee_url_contact); ?>" style="color:#7e22ce;font-weight:600;"><span class="dot" style="background:#a855f7;"></span>روبیکا</a>
            </div>
            <div class="ee-tb-links">
                <a href="<?php echo esc_url($ee_url_courses); ?>">راهنمای دوره‌ها</a>
                <span class="ee-tb-sep">|</span>
                <a href="<?php echo esc_url($ee_url_courses); ?>">گواهی پایان دوره</a>
                <span class="ee-tb-sep">|</span>
                <a href="<?php echo esc_url($ee_url_contact); ?>">پشتیبانی آنلاین</a>
            </div>
        </div>
    </div>

    <!-- ======= هدر (چسبان، شیشه‌ای) ======= -->
    <header class="ee-header">
        <div class="ee-wrap">
            <div class="ee-header-row">
                <div class="ee-hamb ee-ic" id="eeHamb" aria-label="منو"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg></div>

                <a class="ee-logo" href="<?php echo esc_url($ee_url_home); ?>" rel="home">
                    <?php echo function_exists('evented_logo_html') ? evented_logo_html('header') : esc_html(get_bloginfo('name')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- مارک‌آپ امن درون هلپر ساخته می‌شود. ?>
                </a>

                <form class="ee-search" role="search" method="get" action="<?php echo esc_url($ee_url_home); ?>">
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
                <form role="search" method="get" action="<?php echo esc_url($ee_url_home); ?>">
                    <div class="ee-search" style="display:block;">
                        <span class="s-ic material-symbols-outlined ee-ic">search</span>
                        <input type="search" name="s" placeholder="جستجو در دوره‌ها، مقالات، اساتید..." value="<?php echo esc_attr(get_search_query()); ?>">
                    </div>
                </form>
            </div>

            <nav class="ee-nav" id="eeNav">
                <a href="<?php echo esc_url($ee_url_home); ?>"<?php echo $ee_is_active('home'); ?>>صفحه نخست</a>
                <a href="<?php echo esc_url($ee_url_courses); ?>"<?php echo $ee_is_active('courses'); ?>>دوره‌های آموزشی</a>
                <a href="<?php echo esc_url($ee_articles_url); ?>"<?php echo $ee_is_active('articles'); ?>>مقالات و پژوهش‌ها</a>
                <a href="<?php echo esc_url($ee_url_instructors); ?>"<?php echo $ee_is_active('instructors'); ?>>اساتید و کارشناسان</a>
                <a href="<?php echo esc_url($ee_url_contact); ?>"<?php echo $ee_is_active('contact'); ?>>درباره و تماس</a>
            </nav>
        </div>
    </header>
