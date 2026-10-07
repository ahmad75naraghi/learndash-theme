<?php
/* Template Name: Panel - Dashboard */

defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
	wp_safe_redirect(add_query_arg('redirect_to', rawurlencode(home_url('/panel')), home_url('/login')));
	exit;
}

$current_user_id = get_current_user_id();
$enrolled_courses = function_exists('learndash_user_get_enrolled_courses')
	? (array) learndash_user_get_enrolled_courses($current_user_id)
	: array();
$enrolled_courses = array_values(array_unique(array_filter(array_map('absint', $enrolled_courses))));
$courses_count     = count($enrolled_courses);
$visible_courses   = array_slice($enrolled_courses, 0, 4);
if (function_exists('evented_panel_prime_courses')) {
	evented_panel_prime_courses($visible_courses);
}

$favorites = array_filter(array_map('absint', explode(',', (string) get_user_meta($current_user_id, 'fav_courses', true))));
$favorites_count = count(array_unique($favorites));

/* آمار تراکنش بدون SELECT * و بدون خطا در نصب‌هایی که جدول درگاه را ندارند. */
global $wpdb;
$payments_table_name = $wpdb->prefix . 'evented_transactions';
$transactions_count  = 0;
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($payments_table_name))) === $payments_table_name) {
	$transactions_count = (int) $wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM {$payments_table_name} WHERE user_id = %d",
		$current_user_id
	));
}

$stats = array(
	array('label' => 'دوره‌های من', 'value' => $courses_count, 'icon' => 'school', 'url' => home_url('/panel/my-courses')),
	array('label' => 'علاقه‌مندی‌ها', 'value' => $favorites_count, 'icon' => 'favorite', 'url' => home_url('/panel/wishlist')),
	array('label' => 'تراکنش‌ها', 'value' => $transactions_count, 'icon' => 'receipt_long', 'url' => home_url('/panel/payments')),
);

get_template_part('template-parts/panel/shell', 'open', array('ee_panel_current' => 'dashboard', 'ee_panel_title' => 'پیشخوان'));
?>

<?php echo function_exists('evented_resume_card_html') ? evented_resume_card_html() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<div class="top-stats" aria-label="خلاصهٔ حساب کاربری">
	<?php foreach ($stats as $stat) : ?>
		<a class="stat-item" href="<?php echo esc_url($stat['url']); ?>">
			<span class="ee-stat-icon"><?php echo ee_icon($stat['icon']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span><b><?php echo esc_html(number_format_i18n($stat['value'])); ?></b><?php echo esc_html($stat['label']); ?></span>
		</a>
	<?php endforeach; ?>
</div>

<section aria-labelledby="eeCurrentCoursesTitle">
	<div class="ee-panel-section-head">
		<h2 class="section-title" id="eeCurrentCoursesTitle">دوره‌های جاری</h2>
		<?php if ($courses_count > 4) : ?>
			<a href="<?php echo esc_url(home_url('/panel/my-courses')); ?>">مشاهده همهٔ <?php echo esc_html(number_format_i18n($courses_count)); ?> دوره</a>
		<?php endif; ?>
	</div>

	<div class="course-grid">
		<?php if ($visible_courses) : ?>
			<?php foreach ($visible_courses as $course_id) :
				$course_title = get_the_title($course_id);
				$course_link  = get_permalink($course_id);
				$author_id    = (int) get_post_field('post_author', $course_id);
				$thumbnail    = get_the_post_thumbnail_url($course_id, 'medium');
				$progress     = function_exists('learndash_course_progress') ? learndash_course_progress(array(
					'user_id' => $current_user_id, 'course_id' => $course_id, 'array' => true,
				)) : array();
				$percentage = isset($progress['percentage']) ? max(0, min(100, (int) $progress['percentage'])) : 0;
			?>
				<article class="course-card ee-dashboard-course">
					<a href="<?php echo esc_url($course_link); ?>" class="ee-u-plain-link">
						<div class="card-header">
							<?php if ($thumbnail) : ?>
								<img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($course_title); ?>" loading="lazy" decoding="async">
							<?php else : ?>
								<span class="image-placeholder"><?php echo ee_icon('school'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php endif; ?>
						</div>
						<div class="card-body">
							<h3 class="card-title"><?php echo esc_html($course_title); ?></h3>
							<p class="card-author"><?php echo esc_html(get_the_author_meta('display_name', $author_id)); ?></p>
							<div class="progress-wrapper" aria-label="<?php echo esc_attr('پیشرفت ' . $percentage . ' درصد'); ?>">
								<div class="progress-track"><span class="progress-fill" style="width:<?php echo esc_attr($percentage); ?>%"></span></div>
								<b class="progress-percent"><?php echo esc_html(number_format_i18n($percentage)); ?>٪</b>
							</div>
							<span class="ee-dashboard-continue">ادامه دوره <?php echo ee_icon('arrow_back'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</div>
					</a>
				</article>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="no-exist-notice ee-u-grid-empty">
				<p><strong>هنوز دوره‌ای در حساب شما نیست.</strong></p>
				<p>از فهرست دوره‌ها یکی را انتخاب کنید تا مسیر یادگیری از همین‌جا شروع شود.</p>
				<a class="btn-primary" href="<?php echo esc_url(get_post_type_archive_link('sfwd-courses') ?: home_url('/')); ?>">مشاهده دوره‌ها</a>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php get_template_part('template-parts/panel/shell', 'close'); ?>
