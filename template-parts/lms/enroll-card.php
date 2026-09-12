<?php
/**
 * کارت ثبت‌نام / پیشرفت دوره (سایدبار صفحهٔ دوره)
 *
 * آرگومان‌ها (get_template_part):
 *   ee_course_id — شناسهٔ دوره
 *   ee_steps     — خروجی evented_course_steps()
 *   ee_pricing   — خروجی evented_course_pricing()
 *   ee_progress  — خروجی evented_course_progress()
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_course_id = isset($args['ee_course_id']) ? (int) $args['ee_course_id'] : get_the_ID();
$ee_steps     = isset($args['ee_steps']) && is_array($args['ee_steps']) ? $args['ee_steps'] : array();
$ee_pricing   = isset($args['ee_pricing']) && is_array($args['ee_pricing']) ? $args['ee_pricing'] : array();
$ee_progress  = isset($args['ee_progress']) && is_array($args['ee_progress']) ? $args['ee_progress'] : array('percentage' => 0);

$ee_has_access = !empty($ee_pricing['has_access']);
$ee_percentage = isset($ee_progress['percentage']) ? (int) $ee_progress['percentage'] : 0;
$ee_completed  = isset($ee_progress['completed']) ? (int) $ee_progress['completed'] : 0;
$ee_total      = isset($ee_progress['total']) ? (int) $ee_progress['total'] : count($ee_steps);

/* اطلاعات دوره */
$ee_duration = (string) get_post_meta($ee_course_id, '_total_duration', true);
$ee_level    = (string) get_post_meta($ee_course_id, '_course_level', true);
$ee_status   = (string) get_post_meta($ee_course_id, '_course_status', true);

$ee_lesson_count  = count($ee_steps);
$ee_section_count = 0;
$ee_section_seen  = array();
foreach ($ee_steps as $ee_step) {
	if (!empty($ee_step['section_id']) && !isset($ee_section_seen[$ee_step['section_id']])) {
		$ee_section_seen[$ee_step['section_id']] = true;
		$ee_section_count++;
	}
}

/* اطلاعات دوره (Course Materials لرن‌دش) */
$ee_materials_on   = function_exists('learndash_get_setting') ? learndash_get_setting($ee_course_id, 'course_materials_enabled') : '';
$ee_materials_html = function_exists('learndash_get_setting') ? (string) learndash_get_setting($ee_course_id, 'course_materials') : '';

if (empty($ee_materials_html)) {
	$ee_ld_settings      = get_post_meta($ee_course_id, '_sfwd-courses', true);
	if (is_array($ee_ld_settings)) {
		$ee_materials_on   = isset($ee_ld_settings['sfwd-courses_course_materials_enabled']) ? $ee_ld_settings['sfwd-courses_course_materials_enabled'] : '';
		$ee_materials_html = isset($ee_ld_settings['sfwd-courses_course_materials']) ? (string) $ee_ld_settings['sfwd-courses_course_materials'] : '';
	}
}

$ee_materials_items = array();
if ('on' === $ee_materials_on && '' !== trim($ee_materials_html)) {
	if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $ee_materials_html, $ee_m)) {
		foreach ($ee_m[1] as $ee_item) {
			$ee_item = trim(wp_strip_all_tags($ee_item));
			if ('' !== $ee_item) {
				$ee_materials_items[] = $ee_item;
			}
		}
	} else {
		foreach (explode("\n", str_replace(array('<br>', '<br/>', '<br />', '</p>'), "\n", $ee_materials_html)) as $ee_line) {
			$ee_line = trim(wp_strip_all_tags($ee_line));
			if ('' !== $ee_line) {
				$ee_materials_items[] = $ee_line;
			}
		}
	}
}

/* گواهینامه */
$ee_certificate_id = function_exists('learndash_get_setting') ? learndash_get_setting($ee_course_id, 'certificate') : 0;
$ee_cert_link      = '';
if ($ee_certificate_id && function_exists('learndash_course_completed') && learndash_course_completed(get_current_user_id(), $ee_course_id)) {
	if (function_exists('learndash_get_course_certificate_link')) {
		$ee_cert_link = (string) learndash_get_course_certificate_link($ee_course_id, get_current_user_id());
	}
}

/* علاقه‌مندی */
$ee_user_id     = get_current_user_id();
$ee_is_favorite = false;
if ($ee_user_id) {
	$ee_fav = (string) get_user_meta($ee_user_id, 'fav_courses', true);
	$ee_fav = $ee_fav ? array_map('intval', explode(',', $ee_fav)) : array();
	$ee_is_favorite = in_array($ee_course_id, $ee_fav, true);
}

/* لینک ادامهٔ دوره */
$ee_resume_url = '';
if ($ee_has_access && function_exists('learndash_course_get_resume_step_url')) {
	$ee_resume_url = (string) learndash_course_get_resume_step_url($ee_course_id, $ee_user_id);
}
if ('' === $ee_resume_url) {
	foreach ($ee_steps as $ee_step) {
		if (!empty($ee_step['is_unlocked']) && empty($ee_step['is_completed'])) {
			$ee_resume_url = $ee_step['permalink'];
			break;
		}
	}
}
if ('' === $ee_resume_url && !empty($ee_steps)) {
	$ee_resume_url = $ee_steps[0]['permalink'];
}
?>
<div class="ee-widget ee-enroll" id="ee-enroll">

	<div class="ee-enroll-price">
		<?php if (!empty($ee_pricing['is_free'])) : ?>
			<span class="ee-price-free"><?php esc_html_e('رایگان', 'evented-edu'); ?></span>
		<?php else : ?>
			<span class="ee-price-amount"><?php echo esc_html(number_format_i18n((float) $ee_pricing['price'])); ?></span>
			<span class="ee-price-unit"><?php esc_html_e('تومان', 'evented-edu'); ?></span>
		<?php endif; ?>
	</div>

	<?php if ($ee_has_access) : ?>

		<!-- پیشرفت کاربر -->
		<div class="ee-progress" id="eeProgress" data-percent="<?php echo esc_attr($ee_percentage); ?>">
			<div class="ee-progress-top">
				<span><?php esc_html_e('پیشرفت شما', 'evented-edu'); ?></span>
				<strong class="ee-progress-num"><?php echo esc_html(number_format_i18n($ee_percentage)); ?>٪</strong>
			</div>
			<div class="ee-progress-track">
				<span class="ee-progress-fill" style="width:<?php echo esc_attr($ee_percentage); ?>%;"></span>
			</div>
			<div class="ee-progress-meta">
				<?php
				/* translators: 1: گام‌های تکمیل‌شده 2: کل گام‌ها */
				echo esc_html(sprintf(__('%1$s از %2$s گام تکمیل شده', 'evented-edu'), number_format_i18n($ee_completed), number_format_i18n($ee_total)));
				?>
			</div>
		</div>

		<?php if ('' !== $ee_resume_url) : ?>
			<a class="ee-btn-primary ee-enroll-cta" href="<?php echo esc_url($ee_resume_url); ?>">
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-play_circle"></use></svg>
				<?php
				if (0 === $ee_percentage) {
					esc_html_e('شروع یادگیری', 'evented-edu');
				} elseif ($ee_percentage >= 100) {
					esc_html_e('مرور دوره', 'evented-edu');
				} else {
					esc_html_e('ادامهٔ یادگیری', 'evented-edu');
				}
				?>
			</a>
		<?php endif; ?>

		<?php if ('' !== $ee_cert_link) : ?>
			<a class="ee-btn-ghost ee-enroll-cta" href="<?php echo esc_url($ee_cert_link); ?>" target="_blank" rel="noopener">
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-workspace_premium"></use></svg>
				<?php esc_html_e('دریافت گواهینامه', 'evented-edu'); ?>
			</a>
		<?php endif; ?>

	<?php else : ?>

		<?php if (!is_user_logged_in() && 'closed' !== $ee_pricing['price_type']) : ?>
			<a class="ee-btn-primary ee-enroll-cta" href="<?php echo esc_url(home_url('/login?redirect_to=' . rawurlencode((string) get_permalink($ee_course_id)))); ?>">
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-login"></use></svg>
				<?php esc_html_e('برای ثبت‌نام وارد شوید', 'evented-edu'); ?>
			</a>
		<?php else : ?>
			<div class="ee-payment">
				<?php
				/*
				 * learndash_payment_buttons() در بعضی نسخه‌ها خروجی را echo و در بعضی
				 * نسخه‌ها return می‌کند؛ هر دو حالت پوشش داده می‌شود. (خروجی توسط خود
				 * لرن‌دش escape شده است.)
				 */
				if (function_exists('learndash_payment_buttons')) {
					ob_start();
					$ee_payment_return = learndash_payment_buttons(get_post($ee_course_id));
					$ee_payment_html   = (string) ob_get_clean();

					if ('' === trim($ee_payment_html) && is_string($ee_payment_return)) {
						$ee_payment_html = $ee_payment_return;
					}

					echo $ee_payment_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- خروجی لرن‌دش.
				}
				?>
			</div>
		<?php endif; ?>

	<?php endif; ?>

	<!-- دکمهٔ علاقه‌مندی -->
	<button type="button" class="ee-wishlist<?php echo $ee_is_favorite ? ' is-on' : ''; ?>"
		data-action="toggle_course_wishlist"
		data-course="<?php echo esc_attr($ee_course_id); ?>"
		data-nonce="<?php echo esc_attr(wp_create_nonce('wishlist_nonce')); ?>"
		data-logged-in="<?php echo is_user_logged_in() ? '1' : '0'; ?>"
		data-login-url="<?php echo esc_url(home_url('/login')); ?>">
		<?php echo ee_icon($ee_is_favorite ? 'favorite' : 'favorite_border'); // phpcs:ignore ?>
		<span class="ee-wishlist-text"><?php echo $ee_is_favorite ? esc_html__('در علاقه‌مندی‌ها', 'evented-edu') : esc_html__('افزودن به علاقه‌مندی‌ها', 'evented-edu'); ?></span>
	</button>

	<!-- مشخصات دوره -->
	<ul class="ee-facts">
		<li>
			<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-play_lesson"></use></svg>
			<span class="ee-fact-k"><?php esc_html_e('تعداد جلسه', 'evented-edu'); ?></span>
			<strong><?php echo esc_html(number_format_i18n($ee_lesson_count)); ?></strong>
		</li>
		<?php if ($ee_section_count > 0) : ?>
			<li>
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-segment"></use></svg>
				<span class="ee-fact-k"><?php esc_html_e('فصل', 'evented-edu'); ?></span>
				<strong><?php echo esc_html(number_format_i18n($ee_section_count)); ?></strong>
			</li>
		<?php endif; ?>
		<?php if ('' !== $ee_duration) : ?>
			<li>
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-schedule"></use></svg>
				<span class="ee-fact-k"><?php esc_html_e('مدت دوره', 'evented-edu'); ?></span>
				<strong><?php echo esc_html($ee_duration); ?></strong>
			</li>
		<?php endif; ?>
		<?php if ('' !== $ee_level) : ?>
			<li>
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-signal_cellular_alt"></use></svg>
				<span class="ee-fact-k"><?php esc_html_e('سطح', 'evented-edu'); ?></span>
				<strong><?php echo esc_html($ee_level); ?></strong>
			</li>
		<?php endif; ?>
		<?php if ('' !== $ee_status) : ?>
			<li>
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-event_available"></use></svg>
				<span class="ee-fact-k"><?php esc_html_e('وضعیت', 'evented-edu'); ?></span>
				<strong><?php echo esc_html($ee_status); ?></strong>
			</li>
		<?php endif; ?>
		<li>
			<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-visibility"></use></svg>
			<span class="ee-fact-k"><?php esc_html_e('بازدید', 'evented-edu'); ?></span>
			<strong><?php echo esc_html(number_format_i18n(function_exists('evented_get_post_views') ? evented_get_post_views($ee_course_id) : 0)); ?></strong>
		</li>
	</ul>

	<?php if (!empty($ee_materials_items)) : ?>
		<div class="ee-materials">
			<h4 class="ee-w-title"><?php esc_html_e('اطلاعات دوره', 'evented-edu'); ?></h4>
			<ol class="ee-materials-list">
				<?php foreach ($ee_materials_items as $ee_item) : ?>
					<li><?php echo esc_html($ee_item); ?></li>
				<?php endforeach; ?>
			</ol>
		</div>
	<?php endif; ?>

</div>
