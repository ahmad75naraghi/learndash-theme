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
	if (function_exists('evented_empty_state')) {
		$ee_acts = array();
		if (function_exists('evented_course_filters_active') && evented_course_filters_active()) {
			$ee_acts[] = array('label' => __('پاک کردن فیلترها', 'evented-edu'), 'url' => evented_course_filter_base_url(), 'primary' => true);
		}
		$ee_acts[] = array('label' => __('مشاهدهٔ همهٔ دوره‌ها', 'evented-edu'), 'url' => function_exists('evented_nav_url') ? evented_nav_url('sfwd-courses') : get_post_type_archive_link('sfwd-courses'), 'primary' => empty($ee_acts));
		echo evented_empty_state(array('title' => $ee_empty, 'text' => __('فیلترها را تغییر دهید یا عبارت دیگری جست‌وجو کنید.', 'evented-edu'), 'icon' => 'school', 'actions' => $ee_acts)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo '<p class="ee-empty">' . esc_html($ee_empty) . '</p>';
	}
	return;
}
?>
<div class="ee-cgrid ee-cgrid-v2">
	<?php $ee_i = 0; foreach ($ee_posts as $ee_post) : ?>
		<?php
		$ee_pid = $ee_post instanceof WP_Post ? $ee_post->ID : (int) $ee_post;
		if (function_exists('evented_catalog_card_html')) {
			echo evented_catalog_card_html(evented_catalog_card_data($ee_pid), $ee_i++); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- مارک‌آپ امن.
		} else {
			echo '<article class="ee-ccard"><h3 class="ee-ccard-title"><a href="' . esc_url(get_permalink($ee_pid)) . '">' . esc_html(get_the_title($ee_pid)) . '</a></h3></article>';
		}
		?>
	<?php endforeach; ?>
</div>
