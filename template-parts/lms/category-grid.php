<?php
/**
 * پارت: گرید دسته‌های دوره (برگهٔ «دسته‌بندی دوره‌ها»)
 *
 * آرگومان‌ها (get_template_part):
 *   ee_terms — آرایه‌ای از WP_Term (ld_course_category)
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_terms = isset($args['ee_terms']) && is_array($args['ee_terms']) ? $args['ee_terms'] : array();

if (empty($ee_terms)) {
	echo '<p class="ee-empty">' . esc_html__('دسته‌بندی‌ای یافت نشد.', 'evented-edu') . '</p>';
	return;
}
?>
<div class="ee-catgrid">
	<?php foreach ($ee_terms as $ee_term) : ?>
		<?php if (!$ee_term instanceof WP_Term) : continue; endif; ?>
		<?php
		$ee_link  = get_term_link($ee_term);
		$ee_image = function_exists('evented_term_image') ? evented_term_image($ee_term->term_id) : '';
		if (is_wp_error($ee_link)) {
			continue;
		}
		?>
		<a class="ee-catcard" href="<?php echo esc_url($ee_link); ?>">
			<span class="ee-catcard-media">
				<?php if ('' !== $ee_image) : ?>
					<img src="<?php echo esc_url($ee_image); ?>" alt="<?php echo esc_attr($ee_term->name); ?>" loading="lazy" decoding="async">
				<?php else : ?>
					<span class="material-symbols-outlined ee-ic">category</span>
				<?php endif; ?>
			</span>
			<span class="ee-catcard-txt">
				<span class="ee-catcard-name"><?php echo esc_html($ee_term->name); ?></span>
				<span class="ee-catcard-count">
					<?php
					/* translators: %s: تعداد دوره */
					echo esc_html(sprintf(_n('%s دوره', '%s دوره', (int) $ee_term->count, 'evented-edu'), number_format_i18n((int) $ee_term->count)));
					?>
				</span>
			</span>
		</a>
	<?php endforeach; ?>
</div>
