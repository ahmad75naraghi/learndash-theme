<?php
/**
 * پارت: گرید کارت دوره‌ها (بایگانی دوره، دسته، صفحهٔ دوره‌ها، پروفایل مدرس)
 *
 * آرگومان‌ها (get_template_part):
 *   ee_posts  — آرایه‌ای از WP_Post
 *   ee_empty  — پیام حالت خالی (اختیاری)
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_posts = isset($args['ee_posts']) && is_array($args['ee_posts']) ? $args['ee_posts'] : array();
$ee_empty = isset($args['ee_empty']) ? (string) $args['ee_empty'] : __('دوره‌ای یافت نشد.', 'evented-edu');

if (empty($ee_posts)) {
	echo '<p class="ee-empty">' . esc_html($ee_empty) . '</p>';
	return;
}
?>
<div class="ee-cgrid">
	<?php foreach ($ee_posts as $ee_post) : ?>
		<?php $ee_card = function_exists('evented_course_card_data') ? evented_course_card_data($ee_post) : array(); ?>
		<?php if (empty($ee_card)) : continue; endif; ?>

		<article class="ee-ccard">
			<a class="ee-ccard-media" href="<?php echo esc_url($ee_card['url']); ?>">
				<?php if (!empty($ee_card['thumb'])) : ?>
					<img src="<?php echo esc_url($ee_card['thumb']); ?>" alt="<?php echo esc_attr($ee_card['title']); ?>" loading="lazy" decoding="async">
				<?php else : ?>
					<span class="ee-ccard-noimg"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-school"></use></svg></span>
				<?php endif; ?>

				<?php if (!empty($ee_card['cat_name'])) : ?>
					<span class="ee-ccard-cat"><?php echo esc_html($ee_card['cat_name']); ?></span>
				<?php endif; ?>

				<span class="ee-ccard-price<?php echo !empty($ee_card['is_free']) ? ' is-free' : ''; ?>">
					<?php if (!empty($ee_card['is_free'])) : ?>
						<?php esc_html_e('رایگان', 'evented-edu'); ?>
					<?php else : ?>
						<?php echo esc_html(number_format_i18n((float) $ee_card['price'])); ?>
						<small><?php esc_html_e('تومان', 'evented-edu'); ?></small>
					<?php endif; ?>
				</span>
			</a>

			<div class="ee-ccard-body">
				<h3 class="ee-ccard-title">
					<a href="<?php echo esc_url($ee_card['url']); ?>"><?php echo esc_html($ee_card['title']); ?></a>
				</h3>

				<ul class="ee-ccard-meta">
					<?php if (!empty($ee_card['lesson_count'])) : ?>
						<li><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-menu_book"></use></svg>
							<?php
							/* translators: %s: تعداد جلسه */
							echo esc_html(sprintf(__('%s جلسه', 'evented-edu'), number_format_i18n($ee_card['lesson_count'])));
							?>
						</li>
					<?php endif; ?>
					<?php if (!empty($ee_card['duration'])) : ?>
						<li><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-timer"></use></svg><?php echo esc_html($ee_card['duration']); ?></li>
					<?php endif; ?>
					<?php if (!empty($ee_card['level'])) : ?>
						<li><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-signal_cellular_alt"></use></svg><?php echo esc_html($ee_card['level']); ?></li>
					<?php endif; ?>
				</ul>

				<div class="ee-ccard-foot">
					<span class="ee-ccard-by">
						<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-person"></use></svg>
						<?php echo esc_html($ee_card['instructor'] ? $ee_card['instructor'] : __('evented-edu', 'evented-edu')); ?>
					</span>
					<?php if (!empty($ee_card['has_access'])) : ?>
						<span class="ee-ccard-badge is-joined"><?php esc_html_e('ثبت‌نام شده', 'evented-edu'); ?></span>
					<?php else : ?>
						<span class="ee-ccard-cta">
							<?php esc_html_e('مشاهده دوره', 'evented-edu'); ?>
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg>
						</span>
					<?php endif; ?>
				</div>
			</div>
		</article>
	<?php endforeach; ?>
</div>
