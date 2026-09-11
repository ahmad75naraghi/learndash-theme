<?php
/**
 * پارت: سایدبار صفحه‌های فهرست دوره‌ها (بایگانی، دسته، برگهٔ دوره‌ها، مدرس)
 *
 * ویجت‌ها: جستجو، دسته‌بندی دوره‌ها، آخرین دوره‌ها، اشتراک‌گذاری، دنبال‌کردن.
 *
 * آرگومان‌ها (get_template_part):
 *   ee_current_term — شناسهٔ دستهٔ جاری (اختیاری؛ برای علامت‌گذاری آیتم فعال)
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_current_term = isset($args['ee_current_term']) ? (int) $args['ee_current_term'] : 0;

$ee_cats = get_terms(array(
	'taxonomy'   => 'ld_course_category',
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
));
if (is_wp_error($ee_cats)) {
	$ee_cats = array();
}

$ee_latest = function_exists('evented_latest_courses') ? evented_latest_courses(5) : array();

$ee_channels = function_exists('evented_channel_links') ? evented_channel_links() : array();

$ee_side_url    = function_exists('evented_current_url') ? evented_current_url() : (string) home_url('/');
$ee_side_title  = is_singular() ? (string) get_the_title() : (string) wp_get_document_title();
$ee_side_shares = function_exists('evented_share_links') ? evented_share_links($ee_side_url, $ee_side_title) : array();
?>
<aside class="ee-side">

	<div class="ee-widget ee-widget-search">
		<h3 class="ee-w-title"><span class="material-symbols-outlined">search</span> <?php esc_html_e('جستجو در دوره‌ها', 'evented-edu'); ?></h3>
		<form class="ee-search-inline" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
			<label class="screen-reader-text" for="ee-lms-s"><?php esc_html_e('جستجو', 'evented-edu'); ?></label>
			<input id="ee-lms-s" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('نام دوره یا کلیدواژه...', 'evented-edu'); ?>">
			<button type="submit"><span class="material-symbols-outlined">search</span></button>
		</form>
	</div>

	<?php if (!empty($ee_cats)) : ?>
		<div class="ee-widget">
			<h3 class="ee-w-title"><span class="material-symbols-outlined">category</span> <?php esc_html_e('دسته‌بندی دوره‌ها', 'evented-edu'); ?></h3>
			<ul class="ee-cats">
				<?php foreach ($ee_cats as $ee_cat) : ?>
					<?php if (!$ee_cat instanceof WP_Term) : continue; endif; ?>
					<?php $ee_cat_link = get_term_link($ee_cat); ?>
					<?php if (is_wp_error($ee_cat_link)) : continue; endif; ?>
					<li<?php echo (int) $ee_cat->term_id === $ee_current_term ? ' class="is-active"' : ''; ?>>
						<a href="<?php echo esc_url($ee_cat_link); ?>">
							<span><?php echo esc_html($ee_cat->name); ?></span>
							<b><?php echo esc_html(number_format_i18n((int) $ee_cat->count)); ?></b>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<div class="ee-widget">
		<h3 class="ee-w-title"><span class="material-symbols-outlined">new_releases</span> <?php esc_html_e('آخرین دوره‌ها', 'evented-edu'); ?></h3>
		<?php if (!empty($ee_latest)) : ?>
			<div class="ee-mini-list">
				<?php foreach ($ee_latest as $ee_lc) : ?>
					<a class="ee-mini" href="<?php echo esc_url(get_permalink($ee_lc)); ?>">
						<span class="ee-mini-txt">
							<h4><?php echo esc_html(get_the_title($ee_lc)); ?></h4>
							<span class="ee-mini-date"><?php echo esc_html(function_exists('evented_post_date') ? evented_post_date($ee_lc) : get_the_date('', $ee_lc)); ?></span>
						</span>
						<?php if (has_post_thumbnail($ee_lc)) : ?>
							<img class="ee-mini-th" src="<?php echo esc_url(get_the_post_thumbnail_url($ee_lc, 'thumbnail')); ?>" alt="<?php echo esc_attr(get_the_title($ee_lc)); ?>" loading="lazy">
						<?php else : ?>
							<span class="ee-mini-th ee-mini-noimg ee-ic"><span class="material-symbols-outlined">image</span></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="ee-empty-inline"><?php esc_html_e('هنوز دوره‌ای منتشر نشده است.', 'evented-edu'); ?></p>
		<?php endif; ?>
	</div>

	<div class="ee-widget">
		<h3 class="ee-w-title"><span class="material-symbols-outlined">share</span> <?php esc_html_e('اشتراک‌گذاری', 'evented-edu'); ?></h3>
		<div class="ee-share-block" style="border:0;margin:0;padding:0">
			<span class="ee-share-label"><?php esc_html_e('اشتراک‌گذاری این صفحه در:', 'evented-edu'); ?></span>
			<div class="ee-share-row">
				<?php foreach ($ee_side_shares as $ee_share) : ?>
					<a class="ee-share-btn" style="background:<?php echo esc_attr($ee_share['color']); ?>;" href="<?php echo esc_url($ee_share['url']); ?>" target="_blank" rel="noopener nofollow" title="<?php echo esc_attr($ee_share['label']); ?>">
						<span><?php echo esc_html(mb_substr($ee_share['label'], 0, 1)); ?></span>
					</a>
				<?php endforeach; ?>
				<button type="button" class="ee-share-btn ee-share-copy" data-copy="<?php echo esc_attr($ee_side_url); ?>" title="<?php esc_attr_e('کپی لینک', 'evented-edu'); ?>">
					<span class="material-symbols-outlined ee-ic">link</span>
				</button>
			</div>
		</div>
	</div>

	<?php if (!empty($ee_channels)) : ?>
		<div class="ee-widget">
			<div class="ee-follow">
				<p><?php esc_html_e('ما را در رسانه‌های اجتماعی دنبال کنید', 'evented-edu'); ?></p>
				<div class="ee-follow-row">
					<?php foreach ($ee_channels as $ee_channel) : ?>
						<a class="ee-follow-dot" style="background:<?php echo esc_attr($ee_channel['color']); ?>;" href="<?php echo esc_url($ee_channel['url']); ?>" target="_blank" rel="noopener nofollow" title="<?php echo esc_attr($ee_channel['label']); ?>">
							<?php echo esc_html(mb_substr($ee_channel['label'], 0, 1)); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

</aside>
