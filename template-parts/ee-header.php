<?php
/**
 * هدر مشترک طراحی «evented-edu» (نسخهٔ pastel)
 *
 * منوی اصلی استاتیک است و آیتم‌ها/زیرمنوها از `evented_nav_items()` (کش یک‌هفته‌ای)
 * خوانده می‌شوند. جستجوی هدر یک فیلتر «بخش» دارد (مقالات، دوره‌ها، کتابخانه، …).
 * روی موبایل، منو به‌صورت کشوی کناری (off-canvas) با آکاردئون زیرمنو باز می‌شود.
 *
 * آرگومان‌های اختیاری (get_template_part):
 *   ee_active — کلید آیتم فعال منو؛ اگر داده نشود خودکار تشخیص داده می‌شود.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_active = isset($args['ee_active']) ? (string) $args['ee_active'] : '';
if ('' === $ee_active && function_exists('evented_nav_current_key')) {
	$ee_active = evented_nav_current_key();
}

$ee_items = function_exists('evented_nav_items') ? evented_nav_items() : array();

$ee_url_home    = (string) home_url('/');
$ee_url_courses = function_exists('evented_nav_url') ? evented_nav_url('courses') : home_url('/courses/');
$ee_url_contact = function_exists('evented_nav_url') ? evented_nav_url('contact') : home_url('/contact/');
$ee_url_account = is_user_logged_in() ? home_url('/panel') : home_url('/login');

/* تاریخ امروز (در صورت فعال بودن افزونهٔ شمسی‌ساز، خودکار جلالی است) */
$ee_today = function_exists('evented_wp_date') ? evented_wp_date('Y/m/d') : date('Y/m/d');

/* پیام‌رسان‌ها (فیلتر evented_channel_links در template_helpers) */
$ee_channels = function_exists('evented_channel_links') ? (array) evented_channel_links() : array();
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
                <?php if (!empty($ee_channels)) : foreach ($ee_channels as $ee_ch) : ?>
                    <a class="ee-tb-item" href="<?php echo esc_url($ee_ch['url']); ?>" target="_blank" rel="noopener" style="color:<?php echo esc_attr($ee_ch['color']); ?>;font-weight:600;"><span class="dot" style="background:<?php echo esc_attr($ee_ch['color']); ?>;"></span><?php echo esc_html($ee_ch['label']); ?></a>
                <?php endforeach; else : ?>
                    <a class="ee-tb-item" href="<?php echo esc_url($ee_url_contact); ?>" style="color:var(--ee-tealP);font-weight:600;"><span class="dot" style="background:#10b981;"></span>بله</a>
                    <a class="ee-tb-item" href="<?php echo esc_url($ee_url_contact); ?>" style="color:#b45309;font-weight:600;"><span class="dot" style="background:#f59e0b;"></span>ایتا</a>
                    <a class="ee-tb-item" href="<?php echo esc_url($ee_url_contact); ?>" style="color:#7e22ce;font-weight:600;"><span class="dot" style="background:#a855f7;"></span>روبیکا</a>
                <?php endif; ?>
            </div>
            <div class="ee-tb-links">
                <a href="<?php echo esc_url($ee_url_courses); ?>">راهنمای دوره‌ها</a>
                <span class="ee-tb-sep">|</span>
                <a href="<?php echo esc_url(is_user_logged_in() ? home_url('/panel/certificates') : home_url('/login')); ?>">گواهی پایان دوره</a>
                <span class="ee-tb-sep">|</span>
                <a href="<?php echo esc_url($ee_url_contact); ?>">پشتیبانی آنلاین</a>
            </div>
        </div>
    </div>

    <!-- ======= هدر (چسبان، شیشه‌ای) ======= -->
    <header class="ee-header">
        <div class="ee-wrap">
            <div class="ee-header-row">
                <button class="ee-hamb ee-ic" id="eeHamb" type="button" aria-label="باز کردن منو" aria-controls="eeDrawer" aria-expanded="false">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>

                <a class="ee-logo" href="<?php echo esc_url($ee_url_home); ?>" rel="home">
                    <?php echo function_exists('evented_logo_html') ? evented_logo_html('header') : esc_html(get_bloginfo('name')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- مارک‌آپ امن درون هلپر ساخته می‌شود. ?>
                </a>

                <?php if (function_exists('evented_search_form')) : ?>
                    <?php evented_search_form('desktop'); ?>
                <?php endif; ?>

                <div class="ee-h-actions">
                    <?php if (is_user_logged_in()) : ?>
                        <a class="ee-btn ee-btn-ghost" href="<?php echo esc_url(home_url('/panel')); ?>"><span class="material-symbols-outlined ee-ic">person</span> <span class="ee-btn-txt">پنل کاربری</span></a>
                    <?php else : ?>
                        <a class="ee-btn ee-btn-ghost" href="<?php echo esc_url(home_url('/login')); ?>"><span class="material-symbols-outlined ee-ic">person</span> <span class="ee-btn-txt">ورود / عضویت</span></a>
                    <?php endif; ?>
                    <button class="ee-btn ee-btn-soft ee-ic ee-search-toggle" id="eeSearchToggle" type="button" aria-label="جستجو" aria-expanded="false" aria-controls="eeSearchM"><span class="material-symbols-outlined ee-ic">search</span></button>
                </div>
            </div>

            <div class="ee-search-m" id="eeSearchM" hidden>
                <?php if (function_exists('evented_search_form')) : ?>
                    <?php evented_search_form('mobile'); ?>
                <?php endif; ?>
            </div>

            <nav class="ee-nav" id="eeNav" aria-label="منوی اصلی">
                <?php foreach ($ee_items as $ee_it) :
                    $ee_has_sub = !empty($ee_it['children']);
                    $ee_cls     = array('ee-nav-item');
                    if ($ee_has_sub) { $ee_cls[] = 'has-sub'; }
                    if ($ee_active === $ee_it['key']) { $ee_cls[] = 'ee-active'; }
                    ?>
                    <div class="<?php echo esc_attr(implode(' ', $ee_cls)); ?>">
                        <a href="<?php echo esc_url($ee_it['url']); ?>"<?php echo $ee_active === $ee_it['key'] ? ' class="ee-active" aria-current="page"' : ''; ?>>
                            <?php echo esc_html($ee_it['title']); ?>
                            <?php if ($ee_has_sub) : ?><span class="material-symbols-outlined ee-ic ee-caret" aria-hidden="true">expand_more</span><?php endif; ?>
                        </a>
                        <?php if ($ee_has_sub) : ?>
                            <div class="ee-sub" role="menu">
                                <?php foreach ($ee_it['children'] as $ee_sub) : ?>
                                    <a role="menuitem" href="<?php echo esc_url($ee_sub['url']); ?>">
                                        <span><?php echo esc_html($ee_sub['title']); ?></span>
                                        <?php if (!empty($ee_sub['count'])) : ?><small><?php echo esc_html(number_format_i18n((int) $ee_sub['count'])); ?></small><?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                                <a class="ee-sub-all" href="<?php echo esc_url($ee_it['url']); ?>">همهٔ <?php echo esc_html($ee_it['title']); ?> <span class="material-symbols-outlined ee-ic">arrow_back</span></a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <!-- ======= کشوی منوی موبایل ======= -->
    <div class="ee-drawer-backdrop" id="eeDrawerBackdrop" hidden></div>
    <aside class="ee-drawer" id="eeDrawer" aria-label="منوی موبایل" aria-hidden="true" tabindex="-1">
        <div class="ee-drawer-head">
            <a class="ee-logo" href="<?php echo esc_url($ee_url_home); ?>" rel="home">
                <?php echo function_exists('evented_logo_html') ? evented_logo_html('drawer') : esc_html(get_bloginfo('name')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </a>
            <button class="ee-drawer-close ee-ic" id="eeDrawerClose" type="button" aria-label="بستن منو"><span class="material-symbols-outlined ee-ic">close</span></button>
        </div>

        <div class="ee-drawer-account">
            <?php if (is_user_logged_in()) :
                $ee_cu = wp_get_current_user(); ?>
                <span class="ee-drawer-av"><?php echo get_avatar($ee_cu->ID, 40); ?></span>
                <span class="ee-drawer-who">
                    <strong><?php echo esc_html($ee_cu->display_name); ?></strong>
                    <a href="<?php echo esc_url(home_url('/panel')); ?>">پنل کاربری</a>
                    <span class="ee-sep">·</span>
                    <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">خروج</a>
                </span>
            <?php else : ?>
                <a class="ee-btn ee-btn-primary" href="<?php echo esc_url(home_url('/login')); ?>"><span class="material-symbols-outlined ee-ic">login</span> ورود / عضویت</a>
            <?php endif; ?>
        </div>

        <nav class="ee-drawer-nav" aria-label="منوی موبایل">
            <?php foreach ($ee_items as $ee_i => $ee_it) :
                $ee_has_sub = !empty($ee_it['children']);
                $ee_sub_id  = 'eeDSub' . $ee_i;
                ?>
                <div class="ee-dn-item<?php echo $ee_active === $ee_it['key'] ? ' ee-active' : ''; ?><?php echo $ee_has_sub ? ' has-sub' : ''; ?>">
                    <div class="ee-dn-row">
                        <a class="ee-dn-link" href="<?php echo esc_url($ee_it['url']); ?>"<?php echo $ee_active === $ee_it['key'] ? ' aria-current="page"' : ''; ?>>
                            <span class="material-symbols-outlined ee-ic ee-dn-ic"><?php echo esc_html($ee_it['icon']); ?></span>
                            <span><?php echo esc_html($ee_it['title']); ?></span>
                        </a>
                        <?php if ($ee_has_sub) : ?>
                            <button class="ee-dn-toggle ee-ic" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr($ee_sub_id); ?>" aria-label="زیرمنوی <?php echo esc_attr($ee_it['title']); ?>">
                                <span class="material-symbols-outlined ee-ic">expand_more</span>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php if ($ee_has_sub) : ?>
                        <div class="ee-dn-sub" id="<?php echo esc_attr($ee_sub_id); ?>" hidden>
                            <?php foreach ($ee_it['children'] as $ee_sub) : ?>
                                <a href="<?php echo esc_url($ee_sub['url']); ?>">
                                    <span><?php echo esc_html($ee_sub['title']); ?></span>
                                    <?php if (!empty($ee_sub['count'])) : ?><small><?php echo esc_html(number_format_i18n((int) $ee_sub['count'])); ?></small><?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                            <a class="ee-dn-all" href="<?php echo esc_url($ee_it['url']); ?>">همهٔ <?php echo esc_html($ee_it['title']); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <?php if (!empty($ee_channels)) : ?>
            <div class="ee-drawer-foot">
                <span class="ee-drawer-foot-label">کانال‌های رسمی</span>
                <div class="ee-drawer-chans">
                    <?php foreach ($ee_channels as $ee_ch) : ?>
                        <a href="<?php echo esc_url($ee_ch['url']); ?>" target="_blank" rel="noopener" style="--c:<?php echo esc_attr($ee_ch['color']); ?>;"><span class="dot"></span><?php echo esc_html($ee_ch['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </aside>
