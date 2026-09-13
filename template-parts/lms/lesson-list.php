<?php
/**
 * پارت: فهرست درس‌های دوره در سایدبار صفحهٔ درس (الگوی صفحهٔ درس شمیم)
 *
 * ورودی‌ها (get_template_part $args):
 *  - ee_steps      : آرایه از خروجی evented_course_steps()
 *  - ee_course_id  : شناسهٔ دوره
 *  - ee_current_id : شناسهٔ درس جاری
 *  - ee_progress   : خروجی evented_course_progress()
 *
 * اگر درس‌ها «فصل» (sfwd-topic) داشته باشند فهرست گروه‌بندی و جمع‌شونده رندر
 * می‌شود و دکمهٔ «باز کردن همه» فعال است؛ در غیر این صورت فهرست ساده است.
 * درس‌های قفل‌شده غیرفعال رندر می‌شوند (بدون ناوبری).
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_steps      = isset($args['ee_steps']) && is_array($args['ee_steps']) ? $args['ee_steps'] : array();
$ee_course_id  = isset($args['ee_course_id']) ? (int) $args['ee_course_id'] : 0;
$ee_current_id = isset($args['ee_current_id']) ? (int) $args['ee_current_id'] : 0;
$ee_progress   = isset($args['ee_progress']) && is_array($args['ee_progress']) ? $args['ee_progress'] : array();

if (empty($ee_steps) || !$ee_course_id) {
	return;
}

$ee_course_url   = (string) get_permalink($ee_course_id);
$ee_course_title = get_the_title($ee_course_id);
$ee_total        = count($ee_steps);
$ee_done         = isset($ee_progress['completed']) ? (int) $ee_progress['completed'] : 0;
$ee_percent      = isset($ee_progress['percentage']) ? (int) $ee_progress['percentage'] : 0;

/* گروه‌بندی بر اساس فصل — بدون فصل، فهرست ساده می‌ماند. */
$ee_groups       = array();
$ee_has_sections = false;
foreach ($ee_steps as $ee_step) {
	$ee_key = !empty($ee_step['section_id']) ? (int) $ee_step['section_id'] : 0;
	if ($ee_key) {
		$ee_has_sections = true;
	}
	if (!isset($ee_groups[$ee_key])) {
		$ee_groups[$ee_key] = array(
			'id'    => $ee_key,
			'title' => !empty($ee_step['section_title']) ? (string) $ee_step['section_title'] : __('سرفصل‌های دوره', 'evented-edu'),
			'items' => array(),
		);
	}
	$ee_groups[$ee_key]['items'][] = $ee_step;
}

/**
 * چاپ بدنهٔ یک آیتم درس (بدون تگ بیرونی).
 *
 * @param array $item        آیتم گام.
 * @param bool  $is_current  آیا گام جاری است؟
 */
$ee_render_inner = static function ($item, $is_current) {
	$ee_state = function_exists('evented_step_state')
		? evented_step_state($item, $is_current)
		: array('class' => 'is-open');
	?>
	<span class="ee-ln-ic <?php echo esc_attr($ee_state['class']); ?>" aria-hidden="true">
		<?php if (!empty($item['is_completed'])) : ?>
			<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg>
		<?php elseif (empty($item['is_unlocked'])) : ?>
			<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-lock"></use></svg>
		<?php endif; ?>
	</span>

	<span class="ee-ln-txt">
		<span class="ee-ln-title"><?php echo esc_html($item['title']); ?></span>
		<span class="ee-ln-meta">
			<?php if (!empty($item['duration_text'])) : ?>
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-schedule"></use></svg><?php echo esc_html($item['duration_text']); ?>
			<?php endif; ?>
			<?php if (!empty($item['quizzes'])) : ?>
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-quiz"></use></svg>
				<?php
				/* translators: %s: تعداد آزمون */
				echo esc_html(sprintf(_n('%s آزمون', '%s آزمون', $item['quizzes'], 'evented-edu'), number_format_i18n($item['quizzes'])));
				?>
			<?php endif; ?>
			<?php if (!empty($item['is_sample'])) : ?>
				<span class="ee-ln-sample"><?php esc_html_e('نمونهٔ رایگان', 'evented-edu'); ?></span>
			<?php endif; ?>
		</span>
	</span>
	<?php
};
?>
<aside class="ee-side ee-side-lesson">
	<div class="ee-ln" data-lesson-nav>

		<div class="ee-ln-head">
			<a class="ee-ln-home" href="<?php echo esc_url($ee_course_url); ?>">
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-home"></use></svg>
				<span><?php esc_html_e('صفحهٔ دوره', 'evented-edu'); ?></span>
			</a>
			<?php if ($ee_has_sections) : ?>
				<button type="button" class="ee-ln-expand" data-expand-all aria-expanded="false">
					<?php esc_html_e('باز کردن همه', 'evented-edu'); ?>
				</button>
			<?php endif; ?>
			<span class="ee-ln-dot" aria-hidden="true"></span>
		</div>

		<div class="ee-ln-progress">
			<div class="ee-ln-bar"><span style="width:<?php echo esc_attr(max(0, min(100, $ee_percent))); ?>%"></span></div>
			<span class="ee-ln-count">
				<?php
				/* translators: 1: تعداد تکمیل‌شده 2: کل درس‌ها */
				echo esc_html(sprintf(__('%1$s از %2$s درس', 'evented-edu'), number_format_i18n($ee_done), number_format_i18n($ee_total)));
				?>
			</span>
		</div>

		<nav class="ee-ln-list" aria-label="<?php esc_attr_e('فهرست درس‌های دوره', 'evented-edu'); ?>">
			<?php foreach ($ee_groups as $ee_group) : ?>
				<?php
				$ee_group_open = false;
				foreach ($ee_group['items'] as $ee_item) {
					if ((int) $ee_item['id'] === $ee_current_id) {
						$ee_group_open = true;
						break;
					}
				}
				?>

				<?php if ($ee_has_sections) : ?>
					<div class="ee-ln-group<?php echo $ee_group_open ? ' is-open' : ''; ?>" data-ln-group>
						<button type="button" class="ee-ln-group-head" aria-expanded="<?php echo $ee_group_open ? 'true' : 'false'; ?>">
							<span><?php echo esc_html($ee_group['title']); ?></span>
							<span class="ee-ln-group-count"><?php echo esc_html(number_format_i18n(count($ee_group['items']))); ?></span>
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-expand_more"></use></svg>
						</button>
						<ul class="ee-ln-items">
				<?php else : ?>
					<ul class="ee-ln-items is-flat">
				<?php endif; ?>

				<?php foreach ($ee_group['items'] as $ee_item) : ?>
					<?php
					$ee_is_current = ((int) $ee_item['id'] === $ee_current_id);
					$ee_unlocked   = !empty($ee_item['is_unlocked']);
					?>
					<li class="ee-ln-item<?php echo $ee_is_current ? ' is-current' : ''; ?><?php echo $ee_unlocked ? '' : ' is-locked-item'; ?>">
						<?php if ($ee_unlocked) : ?>
							<a class="ee-ln-link" href="<?php echo esc_url($ee_item['permalink']); ?>"<?php echo $ee_is_current ? ' aria-current="page"' : ''; ?>>
								<?php $ee_render_inner($ee_item, $ee_is_current); ?>
							</a>
						<?php else : ?>
							<span class="ee-ln-link is-disabled"<?php echo $ee_is_current ? ' aria-current="page"' : ''; ?> title="<?php esc_attr_e('برای دسترسی به این درس، در دوره ثبت‌نام کنید', 'evented-edu'); ?>">
								<?php $ee_render_inner($ee_item, $ee_is_current); ?>
							</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>

				</ul>
				<?php if ($ee_has_sections) : ?>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>

		<a class="ee-ln-back" href="<?php echo esc_url($ee_course_url); ?>">
			<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg>
			<span class="ee-ln-back-txt"><?php echo esc_html($ee_course_title); ?></span>
		</a>
	</div>
</aside>
