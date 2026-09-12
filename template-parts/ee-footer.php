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
$ee_url_instructors = function_exists('evented_nav_manual_url') ? evented_nav_manual_url('instructors') : '';
if ('' === $ee_url_instructors) {
	$ee_instr_page      = get_page_by_path('instructors');
	$ee_url_instructors = $ee_instr_page instanceof WP_Post ? (string) get_permalink($ee_instr_page) : home_url('/#ee-instructors');
}

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

/* کانال‌ها/شبکه‌ها/تماس از تنظیمات قالب */
$ee_opt       = static function ($k, $d = '') { return function_exists('evented_opt') ? evented_opt($k, $d) : $d; };
$ee_channels  = function_exists('evented_channel_links') ? (array) evented_channel_links() : array();
$ee_socials   = function_exists('evented_social_links') ? (array) evented_social_links() : array();
$ee_email     = function_exists('evented_support_email') ? evented_support_email() : (string) get_bloginfo('admin_email');
$ee_phone     = (string) $ee_opt('phone', '');
$ee_copyright = (string) $ee_opt('copyright', '');
?>
    <!-- ======= فوتر ======= -->
    <footer class="ee-footer" id="ee-contact">
        <div class="ee-wrap ee-fwrap">
            <div class="ee-fgrid">
                <div class="about">
                    <div class="ee-f-logo">
                        <?php echo function_exists('evented_logo_html') ? evented_logo_html('footer') : esc_html(get_bloginfo('name')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- مارک‌آپ امن درون هلپر ساخته می‌شود. ?>
                    </div>
                    <p><?php echo esc_html($ee_opt('site_tagline_fa')); ?></p>
                    <?php if ('' !== (string) $ee_opt('license_text')) : ?><div class="ee-license"><span class="pulse"></span> <?php echo esc_html($ee_opt('license_text')); ?></div><?php endif; ?>
                    <?php if (!empty($ee_socials)) : ?>
                        <div class="ee-fsocial">
                            <?php foreach ($ee_socials as $ee_so) : ?>
                                <a href="<?php echo esc_url($ee_so['url']); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr($ee_so['label']); ?>" title="<?php echo esc_attr($ee_so['label']); ?>"><?php echo function_exists('ee_icon') ? ee_icon($ee_so['icon']) : ''; // phpcs:ignore ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
                    <ul class="ee-f-2col">
                        <?php foreach ($ee_nav_items as $ee_fi) : if ('home' === $ee_fi['key']) { continue; } ?>
                            <li><a href="<?php echo esc_url($ee_fi['url']); ?>">• <?php echo esc_html($ee_fi['title']); ?></a></li>
                        <?php endforeach; ?>
                        <li><a href="<?php echo esc_url($ee_url_instructors); ?>">• اساتید و کارشناسان</a></li>
                    </ul>
                </div>
                <div>
                    <h4>کانال‌های رسمی و تماس</h4>
                    <?php if (!empty($ee_channels)) : ?>
                        <div class="ee-fchan">
                            <?php foreach ($ee_channels as $ee_ch) : ?>
                                <a href="<?php echo esc_url($ee_ch['url']); ?>" target="_blank" rel="noopener"><span class="cn"><span class="dot" style="background:<?php echo esc_attr($ee_ch['color']); ?>;"></span> پیام‌رسان <?php echo esc_html($ee_ch['label']); ?></span><span class="id"><?php echo esc_html(preg_replace('#^https?://#', '', $ee_ch['url'])); ?></span></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="ee-fmeta">
                        <?php if ('' !== $ee_phone) : ?><div><span class="mk">تلفن:</span><a class="mv" href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $ee_phone)); ?>"><?php echo esc_html($ee_phone); ?></a></div><?php endif; ?>
                        <?php if ('' !== (string) $ee_opt('phone_hours')) : ?><div><span class="mk">پاسخگویی:</span><span class="mv" style="direction:rtl;font-family:inherit;"><?php echo esc_html($ee_opt('phone_hours')); ?></span></div><?php endif; ?>
                        <div><span class="mk">پشتیبانی:</span><a class="mv" href="mailto:<?php echo esc_attr($ee_email); ?>"><?php echo esc_html($ee_email); ?></a></div>
                        <?php if ('' !== (string) $ee_opt('address')) : ?><div><span class="mk">آدرس:</span><span class="mv" style="direction:rtl;font-family:inherit;text-align:right;"><?php echo esc_html($ee_opt('address')); ?></span></div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="ee-fbottom">
            <div class="ee-wrap"><?php echo '' !== $ee_copyright ? esc_html($ee_copyright) : esc_html('تمام حقوق مادی و معنوی این وب‌سایت متعلق به ' . get_bloginfo('name') . ' است.'); ?></div>
        </div>
    </footer>

    <!-- نوار پایین موبایل -->
    <nav class="ee-mnav">
        <a href="<?php echo esc_url($ee_url_home); ?>"<?php echo $ee_is_active('home'); ?>><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-home"></use></svg> خانه</a>
        <a href="<?php echo esc_url($ee_url_courses); ?>"<?php echo $ee_is_active('courses'); ?>><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-school"></use></svg> دوره‌ها</a>
        <a href="<?php echo esc_url($ee_articles_url); ?>"<?php echo $ee_is_active('articles'); ?>><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-article"></use></svg> مقالات</a>
        <a href="<?php echo esc_url($ee_url_account); ?>"<?php echo $ee_is_active('account'); ?>><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-account_circle"></use></svg> حساب من</a>
        <button type="button" class="ee-mnav-menu" data-ee-drawer-open aria-label="منو"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-menu"></use></svg> منو</button>
    </nav>

<?php wp_footer(); ?>
</body>
</html>
