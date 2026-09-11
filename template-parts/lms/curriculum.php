<?php
/**
 * سرفصل‌های دوره (فصل‌ها + ردیف درس‌ها) — مطابق الگوی صفحهٔ دورهٔ «شمیم»
 *
 * برخلاف نسخهٔ قبلی، برای هر درس مودال ویدیو ساخته نمی‌شود (N فراخوانی oEmbed
 * و N مودال در DOM حذف شد)؛ ردیف‌ها به صفحهٔ درس لینک می‌شوند.
 *
 * آرگومان‌ها (get_template_part):
 *   ee_steps     — خروجی evented_course_steps()
 *   ee_course_id — شناسهٔ دوره
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_steps     = isset($args['ee_steps']) && is_array($args['ee_steps']) ? $args['ee_steps'] : array();
$ee_course_id = isset($args['ee_course_id']) ? (int) $args['ee_course_id'] : get_the_ID();

if (empty($ee_steps)) {
	echo '<p class="ee-empty-inline">' . esc_html__('هنوز درسی برای این دوره منتشر نشده است.', 'evented-edu') . '</p>';
	return;
}

$ee_last_section = null;
$ee_number       = 0;
?>
<section class="ee-curriculum" id="ee-curriculum" aria-label="<?php esc_attr_e('سرفصل‌های دوره', 'evented-edu'); ?>">

	<div class="ee-cur-head">
		<h2 class="ee-w-title">
			<span class="material-symbols-outlined ee-ic">list_alt</span>
			<?php esc_html_e('محتوای دوره', 'evented-edu'); ?>
		</h2>
		<span class="ee-cur-count"><?php echo esc_html(number_format_i18n(count($ee_steps))); ?> <?php esc_html_e('جلسه', 'evented-edu'); ?></span>
	</div>

	<div class="ee-cur-list">
		<?php
		foreach ($ee_steps as $ee_step) :
			$ee_number++;

			/* سربرگ فصل */
			if ($ee_step['section_title'] && $ee_step['section_title'] !== $ee_last_section) :
				$ee_last_section = $ee_step['section_title'];
				?>
				<div class="ee-cur-section">
					<span class="material-symbols-outlined ee-ic">folder_open</span>
					<?php echo esc_html($ee_last_section); ?>
				</div>
			<?php endif; ?>

			<?php
			$ee_locked = empty($ee_step['is_unlocked']);
			$ee_row_class = 'ee-cur-item';
			if ($ee_locked) {
				$ee_row_class .= ' is-locked';
			} elseif (!empty($ee_step['is_completed'])) {
				$ee_row_class .= ' is-done';
			} elseif (!empty($ee_step['is_sample'])) {
				$ee_row_class .= ' is-sample';
			}
			?>

			<?php if ($ee_locked) : ?>
				<div class="<?php echo esc_attr($ee_row_class); ?>">
					<span class="ee-cur-num"><?php echo esc_html(number_format_i18n($ee_number)); ?></span>
					<span class="ee-cur-title"><?php echo esc_html($ee_step['title']); ?></span>
					<span class="ee-cur-side">
						<?php if (!empty($ee_step['duration_text'])) : ?>
							<em class="ee-cur-dur"><?php echo esc_html($ee_step['duration_text']); ?></em>
						<?php endif; ?>
						<a class="ee-cur-lock" href="#ee-enroll" title="<?php esc_attr_e('برای دسترسی، دوره را ثبت‌نام کنید', 'evented-edu'); ?>">
							<span class="material-symbols-outlined ee-ic">lock</span>
						</a>
					</span>
				</div>
			<?php else : ?>
				<a class="<?php echo esc_attr($ee_row_class); ?>" href="<?php echo esc_url($ee_step['permalink']); ?>">
					<span class="ee-cur-num"><?php echo esc_html(number_format_i18n($ee_number)); ?></span>
					<span class="ee-cur-title"><?php echo esc_html($ee_step['title']); ?></span>
					<span class="ee-cur-side">
						<?php if (!empty($ee_step['duration_text'])) : ?>
							<em class="ee-cur-dur"><?php echo esc_html($ee_step['duration_text']); ?></em>
						<?php endif; ?>
						<?php if (!empty($ee_step['quizzes'])) : ?>
							<em class="ee-cur-quiz" title="<?php esc_attr_e('دارای آزمون', 'evented-edu'); ?>">
								<span class="material-symbols-outlined ee-ic">quiz</span>
								<?php echo esc_html(number_format_i18n((int) $ee_step['quizzes'])); ?>
							</em>
						<?php endif; ?>
						<?php if (!empty($ee_step['is_sample'])) : ?>
							<em class="ee-cur-sample"><?php esc_html_e('پیش‌نمایش', 'evented-edu'); ?></em>
						<?php endif; ?>
						<span class="ee-cur-dot" aria-hidden="true">
							<?php if (!empty($ee_step['is_completed'])) : ?>
								<span class="material-symbols-outlined ee-ic">check_circle</span>
							<?php else : ?>
								<span class="material-symbols-outlined ee-ic">play_circle</span>
							<?php endif; ?>
						</span>
					</span>
				</a>
			<?php endif; ?>

		<?php endforeach; ?>
	</div>
</section>
