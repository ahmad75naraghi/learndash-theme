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

$ee_nav_items = function_exists('evented_nav_items') ? evented_nav_items() : array();
$ee_nav       = static function ($key) {
	return function_exists('evented_nav_url') ? evented_nav_url($key) : (string) home_url('/');
};

$ee_url_home        = (string) home_url('/');
$ee_url_courses     = $ee_nav('courses');
$ee_articles_url    = $ee_nav('articles');
$ee_url_about       = $ee_nav('about');
$ee_url_contact     = $ee_nav('contact');
$ee_url_account     = is_user_logged_in() ? home_url('/panel') : home_url('/login');
$ee_instr_page      = get_page_by_path('instructors');
$ee_url_instructors = $ee_instr_page instanceof WP_Post ? (string) get_permalink($ee_instr_page) : home_url('/#ee-instructors');

/* دسته‌های دوره برای ستون «دوره‌های تخصصی» (از کش منو) */
$ee_course_item = function_exists('evented_nav_item') ? evented_nav_item('courses') : null;
$ee_course_cats = ($ee_course_item && !empty($ee_course_item['children'])) ? array_slice($ee_course_item['children'], 0, 5) : array();

if ('' === $ee_active && function_exists('evented_nav_current_key')) {
	$ee_active = evented_nav_current_key();
}

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
                        <?php echo function_exists('evented_logo_html') ? evented_logo_html('footer') : esc_html(get_bloginfo('name')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- مارک‌آپ امن درون هلپر ساخته می‌شود. ?>
                    </div>
                    <p>مرجع تخصصی آموزش‌های آنلاین فناوری اطلاعات؛ شبکه، سرور، امنیت، مجازی‌سازی و CRM با همراهی برترین اساتید کشور.</p>
                    <div class="ee-license"><span class="pulse"></span> دارای مجوز رسمی برگزاری دوره‌های آموزش فناوری</div>
                </div>
                <div>
                    <h4>دوره‌های تخصصی</h4>
                    <ul>
                        <?php if (!empty($ee_course_cats)) : foreach ($ee_course_cats as $ee_cc) : ?>
                            <li><a href="<?php echo esc_url($ee_cc['url']); ?>">• <?php echo esc_html($ee_cc['title']); ?></a></li>
                        <?php endforeach; else : ?>
                            <li><a href="<?php echo esc_url($ee_url_courses); ?>">• همهٔ دوره‌ها</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo esc_url($ee_url_courses); ?>">• مشاهدهٔ همهٔ دوره‌ها</a></li>
                    </ul>
                </div>
                <div>
                    <h4>بخش‌های پایگاه</h4>
                    <ul>
                        <?php foreach ($ee_nav_items as $ee_fi) : if ('home' === $ee_fi['key']) { continue; } ?>
                            <li><a href="<?php echo esc_url($ee_fi['url']); ?>">• <?php echo esc_html($ee_fi['title']); ?></a></li>
                        <?php endforeach; ?>
                        <li><a href="<?php echo esc_url($ee_url_instructors); ?>">• اساتید و کارشناسان</a></li>
                    </ul>
                </div>
                <div>
                    <h4>کانال‌های رسمی در پیام‌رسان‌ها</h4>
                    <div class="ee-fchan">
                        <a href="<?php echo esc_url('https://ble.ir/' . $ee_channel_id); ?>" target="_blank" rel="noopener"><span class="cn"><span class="dot" style="background:#10b981;"></span> پیام‌رسان بله</span><span class="id"><em>ble.ir/</em><?php echo esc_html($ee_channel_id); ?></span></a>
                        <a href="<?php echo esc_url('https://eitaa.com/' . $ee_channel_id); ?>" target="_blank" rel="noopener"><span class="cn"><span class="dot" style="background:#f59e0b;"></span> پیام‌رسان ایتا</span><span class="id"><em>eitaa.com/</em><?php echo esc_html($ee_channel_id); ?></span></a>
                        <a href="<?php echo esc_url('https://rubika.ir/' . $ee_channel_id); ?>" target="_blank" rel="noopener"><span class="cn"><span class="dot" style="background:#a855f7;"></span> پیام‌رسان روبیکا</span><span class="id"><em>rubika.ir/</em><?php echo esc_html($ee_channel_id); ?></span></a>
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
        <button type="button" class="ee-mnav-menu" data-ee-drawer-open aria-label="منو"><span class="material-symbols-outlined ee-ic">menu</span> منو</button>
    </nav>

<?php wp_footer(); ?>
</body>
</html>
