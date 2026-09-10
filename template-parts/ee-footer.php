<?php
/**
 * فوتر مشترک طراحی «evented-edu» (نسخهٔ pastel) + نوار ناوبری پایین موبایل
 *
 * از front-page.php استخراج شده تا صفحهٔ اصلی، تک‌نوشته و آرشیو
 * فوتر یکسانی داشته باشند.
 *
 * آرگومان‌های اختیاری (از طریق get_template_part):
 *   ee_active  — کلید آیتم فعال نوار موبایل: home|courses|articles|account
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_active = isset($args['ee_active']) ? $args['ee_active'] : '';

$ee_on_front = is_front_page();

/** آدرس لنگر/برگه برای لینک‌های فوتر. */
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

$ee_posts_page_id = (int) get_option('page_for_posts');
$ee_articles_url  = $ee_on_front
	? '#ee-articles'
	: ($ee_posts_page_id ? (string) get_permalink($ee_posts_page_id) : home_url('/#ee-articles'));

$ee_url_courses     = $ee_nav_url('ee-courses', 'courses');
$ee_url_instructors = $ee_nav_url('ee-instructors', 'instructors');
$ee_url_home        = (string) home_url('/');
$ee_url_account     = is_user_logged_in() ? home_url('/panel') : home_url('/login');

/** کلاس فعال بودن آیتم نوار موبایل. */
$ee_is_active = static function ($key) use ($ee_active) {
	return $ee_active === $key ? ' class="ee-active"' : '';
};

/* شناسهٔ کانال‌های پیام‌رسان — با فیلتر evented_channel_id قابل تنظیم است */
$ee_channel_id = (string) apply_filters('evented_channel_id', 'channel-id');
?>
    <!-- ======= فوتر ======= -->
    <footer class="ee-footer" id="ee-contact">
        <div class="ee-wrap ee-fwrap">
            <div class="ee-fgrid">
                <div class="about">
                    <div class="ee-f-logo">
                        <img src="<?php echo esc_url(PATH_DIR_URL . '/assets/img/front-page/evented-edu-logo.webp'); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" style="height:38px;width:auto;">
                    </div>
                    <p>مرجع تخصصی آموزش‌های آنلاین فناوری اطلاعات؛ شبکه، سرور، امنیت، مجازی‌سازی و CRM با همراهی برترین اساتید کشور.</p>
                    <div class="ee-license"><span class="pulse"></span> دارای مجوز رسمی برگزاری دوره‌های آموزش فناوری</div>
                </div>
                <div>
                    <h4>دوره‌های تخصصی</h4>
                    <ul>
                        <li><a href="<?php echo esc_url($ee_url_courses); ?>">• شبکه و زیرساخت</a></li>
                        <li><a href="<?php echo esc_url($ee_url_courses); ?>">• سرور و مجازی‌سازی</a></li>
                        <li><a href="<?php echo esc_url($ee_url_courses); ?>">• امنیت اطلاعات</a></li>
                        <li><a href="<?php echo esc_url($ee_url_courses); ?>">• مدیریت سیستم و CRM</a></li>
                    </ul>
                </div>
                <div>
                    <h4>بخش‌های پایگاه</h4>
                    <ul>
                        <li><a href="<?php echo esc_url($ee_articles_url); ?>">• مقالات تخصصی</a></li>
                        <li><a href="<?php echo esc_url($ee_url_instructors); ?>">• اساتید و کارشناسان</a></li>
                        <li><a href="<?php echo esc_url($ee_url_courses); ?>">• آزمون و گواهی</a></li>
                        <li><a href="<?php echo esc_url($ee_on_front ? '#ee-contact' : home_url('/#ee-contact')); ?>">• درباره ما</a></li>
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
            <div class="ee-wrap">تمام حقوق مادی و معنوی این وب‌سایت متعلق به آموزشگاه آنلاین <?php echo esc_html(get_bloginfo('name')); ?> است.</div>
        </div>
    </footer>

    <!-- نوار پایین موبایل -->
    <nav class="ee-mnav">
        <a href="<?php echo esc_url($ee_url_home); ?>"<?php echo $ee_is_active('home'); ?>><span class="material-symbols-outlined ee-ic">home</span> خانه</a>
        <a href="<?php echo esc_url($ee_url_courses); ?>"<?php echo $ee_is_active('courses'); ?>><span class="material-symbols-outlined ee-ic">school</span> دوره‌ها</a>
        <a href="<?php echo esc_url($ee_articles_url); ?>"<?php echo $ee_is_active('articles'); ?>><span class="material-symbols-outlined ee-ic">article</span> مقالات</a>
        <a href="<?php echo esc_url($ee_url_account); ?>"<?php echo $ee_is_active('account'); ?>><span class="material-symbols-outlined ee-ic">account_circle</span> حساب من</a>
    </nav>

<?php wp_footer(); ?>
</body>
</html>
