<?php
/**
 * آغاز پوستهٔ پنل کاربری — هدر ee-* + سایدبار پنل
 *
 * آرگومان‌ها (get_template_part):
 *   ee_panel_current — کلید صفحهٔ فعال: dashboard|my-courses|certificates|wishlist|payments|profile|settings
 *   ee_panel_title   — عنوان صفحه (برای <title> از طریق document_title_parts استفاده نمی‌شود؛ صرفاً نمایشی)
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_panel_current = isset($args['ee_panel_current']) ? (string) $args['ee_panel_current'] : '';
$ee_panel_title   = isset($args['ee_panel_title']) ? (string) $args['ee_panel_title'] : '';

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-panel-body'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'account'));

$ee_user       = wp_get_current_user();
$ee_first      = (string) get_user_meta($ee_user->ID, 'first_name', true);
$ee_name       = '' !== $ee_first ? $ee_first : (string) $ee_user->display_name;
$ee_panel_menu = array(
	'dashboard'    => array('پیشخوان', 'dashboard', home_url('/panel')),
	'my-courses'   => array('دوره‌های من', 'school', home_url('/panel/my-courses')),
	'certificates' => array('گواهینامه‌ها', 'workspace_premium', home_url('/panel/certificates')),
	'wishlist'     => array('علاقه‌مندی‌ها', 'favorite', home_url('/panel/wishlist')),
	'payments'     => array('تراکنش‌ها', 'receipt_long', home_url('/panel/payments')),
	'profile'      => array('پروفایل', 'person', home_url('/panel/profile')),
	'settings'     => array('حساب کاربری', 'settings', home_url('/panel/settings')),
);
$ee_panel_menu = apply_filters('evented_panel_menu', $ee_panel_menu);
$ee_panel_help = array(
	'dashboard'    => 'در پیشخوان، خلاصهٔ وضعیت آموزشی، دوره‌های در حال یادگیری و مسیر ادامهٔ مطالعه را می‌بینید.',
	'my-courses'   => 'در این بخش همهٔ دوره‌هایی که در آن‌ها ثبت‌نام کرده‌اید، میزان پیشرفت و دکمهٔ ادامهٔ آموزش نمایش داده می‌شود.',
	'certificates' => 'گواهینامه‌های دریافت‌شده از دوره‌ها و آزمون‌ها در این بخش قرار می‌گیرند و می‌توانید آن‌ها را مشاهده یا دریافت کنید.',
	'wishlist'     => 'دوره‌هایی که برای بررسی یا ثبت‌نام در آینده ذخیره کرده‌اید اینجا هستند؛ هر مورد را می‌توانید باز یا حذف کنید.',
	'payments'     => 'سوابق پرداخت، وضعیت تراکنش و اطلاعات پیگیری خریدهای شما در این بخش نمایش داده می‌شود.',
	'profile'      => 'نام فارسی و انگلیسی، جنسیت و تاریخ تولد را اینجا تکمیل کنید؛ این اطلاعات برای پروفایل و صدور گواهینامه استفاده می‌شوند.',
	'settings'     => 'شمارهٔ همراه، ایمیل و رمز عبور حساب از این بخش مدیریت می‌شوند. شمارهٔ همراه، شناسهٔ ورود شماست.',
);
$ee_panel_help = apply_filters('evented_panel_help_texts', $ee_panel_help);
$ee_help_text  = isset($ee_panel_help[$ee_panel_current]) ? (string) $ee_panel_help[$ee_panel_current] : '';
$ee_help_id    = 'ee-panel-help-' . sanitize_html_class($ee_panel_current ?: 'page');
?>
<main id="ee-main" class="ee-panel">
	<div class="ee-wrap ee-panel-grid">

		<aside class="ee-panel-side" aria-label="منوی پنل کاربری">
			<div class="ee-panel-user">
				<span class="ee-panel-avatar"><?php echo get_avatar($ee_user->ID, 56, '', $ee_name, array('class' => 'av')); ?></span>
				<div class="ee-panel-user-txt">
					<strong><?php echo esc_html($ee_name); ?></strong>
					<span><?php echo esc_html($ee_user->user_login); ?></span>
				</div>
			</div>
			<label class="ee-panel-mobile-select">
				<span>بخش پنل</span>
				<select data-ee-panel-nav aria-label="انتخاب بخش پنل کاربری">
					<?php foreach ($ee_panel_menu as $ee_k => $ee_m) : ?>
						<option value="<?php echo esc_url($ee_m[2]); ?>"<?php selected($ee_k, $ee_panel_current); ?>><?php echo esc_html($ee_m[0]); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<button type="button" class="ee-panel-mobile-logout" data-ee-logout="<?php echo esc_url(wp_logout_url(home_url('/login'))); ?>">
				<?php echo ee_icon('logout'); // phpcs:ignore ?> <span>خروج</span>
			</button>
			<nav class="ee-panel-nav">
				<?php foreach ($ee_panel_menu as $ee_k => $ee_m) : ?>
					<a href="<?php echo esc_url($ee_m[2]); ?>" class="ee-panel-nav-item<?php echo $ee_k === $ee_panel_current ? ' is-active' : ''; ?>"<?php echo $ee_k === $ee_panel_current ? ' aria-current="page"' : ''; ?>>
						<?php echo ee_icon($ee_m[1]); // phpcs:ignore ?>
						<span><?php echo esc_html($ee_m[0]); ?></span>
					</a>
				<?php endforeach; ?>
				<button type="button" class="ee-panel-nav-item ee-panel-logout" data-ee-logout="<?php echo esc_url(wp_logout_url(home_url('/login'))); ?>">
					<?php echo ee_icon('logout'); // phpcs:ignore ?>
					<span>خروج از حساب</span>
				</button>
			</nav>
		</aside>

		<section class="ee-panel-main">
			<?php if ('' !== $ee_panel_title) : ?>
				<header class="ee-panel-head">
					<div class="ee-panel-title-row">
						<h1 class="ee-panel-title"><?php echo esc_html($ee_panel_title); ?></h1>
						<?php if ('' !== $ee_help_text) : ?>
							<button type="button" class="ee-panel-help-button" data-ee-panel-help aria-expanded="false" aria-controls="<?php echo esc_attr($ee_help_id); ?>" aria-label="راهنمای بخش <?php echo esc_attr($ee_panel_title); ?>">؟</button>
						<?php endif; ?>
					</div>
					<?php if ('' !== $ee_help_text) : ?>
						<p class="ee-panel-help-text" id="<?php echo esc_attr($ee_help_id); ?>" hidden><?php echo esc_html($ee_help_text); ?></p>
					<?php endif; ?>
					<nav class="ee-crumb" aria-label="مسیر صفحه">
						<a href="<?php echo esc_url(home_url('/')); ?>">خانه</a>
						<?php echo ee_icon('chevron_left'); // phpcs:ignore ?>
						<a href="<?php echo esc_url(home_url('/panel')); ?>">پنل کاربری</a>
						<?php echo ee_icon('chevron_left'); // phpcs:ignore ?>
						<span class="ee-crumb-current"><?php echo esc_html($ee_panel_title); ?></span>
					</nav>
				</header>
			<?php endif; ?>
