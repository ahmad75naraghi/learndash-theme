<?php
/**
 * سایدبار مشترک نوشته‌ها (مطابق الگوی صفحهٔ تک‌مقالهٔ «شمیم»)
 *
 * ویجت‌ها: جستجو + اشتراک‌گذاری، آخرین مطالب، ویژه‌ها، مطالب پربازدید، دوره‌ها،
 * و بلوک «ما را دنبال کنید». در تک‌نوشته و آرشیو نوشته‌ها استفاده می‌شود.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_side_url   = function_exists('evented_current_url') ? evented_current_url() : (string) home_url('/');
$ee_side_title = is_singular() ? (string) get_the_title() : (string) wp_get_document_title();

/* آخرین مطالب */
$ee_side_recent = new WP_Query(array(
	'post_type'           => 'post',
	'posts_per_page'      => 5,
	'no_found_rows'       => true,
	'ignore_sticky_posts' => true,
));

/* ویژه‌ها: نوشته‌های چسبان؛ در نبود آن‌ها پرنظرترین‌ها */
$ee_side_sticky = get_option('sticky_posts');
$ee_side_sticky = is_array($ee_side_sticky) ? array_slice(array_values(array_filter($ee_side_sticky)), 0, 4) : array();

if (count($ee_side_sticky) < 4) {
	$ee_side_fill = new WP_Query(array(
		'post_type'           => 'post',
		'posts_per_page'      => 4 - count($ee_side_sticky),
		'post__not_in'        => $ee_side_sticky,
		'orderby'             => 'comment_count',
		'order'               => 'DESC',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	));
	foreach ($ee_side_fill->posts as $ee_sp) {
		$ee_side_sticky[] = $ee_sp->ID;
	}
	wp_reset_postdata();
}

$ee_side_featured_posts = array();
if (!empty($ee_side_sticky)) {
	$ee_side_featured_posts = get_posts(array(
		'post_type'      => 'post',
		'posts_per_page' => 4,
		'post__in'       => $ee_side_sticky,
		'orderby'        => 'post__in',
		'no_found_rows'  => true,
	));
}

/* مطالب پربازدید (بر پایهٔ متاباکس evented_post_views) */
$ee_side_popular = new WP_Query(array(
	'post_type'           => 'post',
	'posts_per_page'      => 4,
	'meta_key'            => 'evented_post_views',
	'orderby'             => 'meta_value_num',
	'order'               => 'DESC',
	'no_found_rows'       => true,
	'ignore_sticky_posts' => true,
));

/* اگر هنوز بازدیدی ثبت نشده باشد، پرنظرترین‌ها را نشان بده */
if (!$ee_side_popular->have_posts()) {
	$ee_side_popular = new WP_Query(array(
		'post_type'           => 'post',
		'posts_per_page'      => 4,
		'orderby'             => 'comment_count',
		'order'               => 'DESC',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	));
}

/* دوره‌ها */
$ee_side_courses = new WP_Query(array(
	'post_type'      => 'sfwd-courses',
	'posts_per_page' => 3,
	'no_found_rows'  => true,
));

$ee_side_shares   = function_exists('evented_share_links') ? evented_share_links($ee_side_url, $ee_side_title) : array();
$ee_side_channels = function_exists('evented_channel_links') ? evented_channel_links() : array();

/**
 * چاپ یک ردیف از فهرست‌های کوچک سایدبار.
 *
 * @param int    $post_id شناسهٔ نوشته/دوره.
 * @param string $meta    متن ردیف دوم (تاریخ یا بازدید) — خالی یعنی بدون ردیف دوم.
 * @param string $icon    آیکن جایگزین وقتی تصویر شاخص وجود ندارد.
 */
$ee_mini_row = static function ($post_id, $meta = '', $icon = 'article') {
	?>
	<a class="ee-mini" href="<?php echo esc_url(get_permalink($post_id)); ?>">
		<div class="ee-mini-txt">
			<h4><?php echo esc_html(get_the_title($post_id)); ?></h4>
			<?php if ('' !== $meta) : ?>
				<span class="ee-mini-date"><?php echo esc_html($meta); ?></span>
			<?php endif; ?>
		</div>
		<?php if (has_post_thumbnail($post_id)) : ?>
			<img class="ee-mini-th" src="<?php echo esc_url(get_the_post_thumbnail_url($post_id, 'thumbnail')); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>" loading="lazy">
		<?php else : ?>
			<span class="ee-mini-th ee-mini-noimg ee-ic"><?php echo ee_icon($icon); // phpcs:ignore ?></span>
		<?php endif; ?>
	</a>
	<?php
};
?>
<aside class="ee-side" aria-label="<?php esc_attr_e('ستون کناری', 'evented-edu'); ?>">

	<!-- جستجو + اشتراک‌گذاری -->
	<div class="ee-widget ee-widget-search">
		<form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
			<label class="screen-reader-text" for="ee-side-s"><?php esc_html_e('جستجو در مطالب', 'evented-edu'); ?></label>
			<div class="ee-search-inline">
				<input id="ee-side-s" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('جستجو در مطالب', 'evented-edu'); ?>">
				<button type="submit" aria-label="<?php esc_attr_e('جستجو', 'evented-edu'); ?>">
					<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-search"></use></svg>
				</button>
			</div>
		</form>

		<?php if (!empty($ee_side_shares)) : ?>
			<div class="ee-share-block">
				<span class="ee-share-label"><?php esc_html_e('اشتراک گذاری این صفحه در :', 'evented-edu'); ?></span>
				<div class="ee-share-row">
					<?php foreach ($ee_side_shares as $ee_share) : ?>
						<a class="ee-share-btn" style="background:<?php echo esc_attr($ee_share['color']); ?>;" href="<?php echo esc_url($ee_share['url']); ?>" target="_blank" rel="noopener nofollow" title="<?php echo esc_attr($ee_share['label']); ?>">
							<span><?php echo esc_html(mb_substr($ee_share['label'], 0, 1)); ?></span>
						</a>
					<?php endforeach; ?>
					<button type="button" class="ee-share-btn ee-share-copy" data-copy="<?php echo esc_attr($ee_side_url); ?>" title="<?php esc_attr_e('کپی لینک', 'evented-edu'); ?>">
						<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-link"></use></svg>
					</button>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<!-- آخرین مطالب -->
	<div class="ee-widget">
		<h3 class="ee-w-title"><?php esc_html_e('آخرین مطالب', 'evented-edu'); ?></h3>
		<?php if ($ee_side_recent->have_posts()) : ?>
			<div class="ee-mini-list">
				<?php
				while ($ee_side_recent->have_posts()) :
					$ee_side_recent->the_post();
					$ee_mini_date = function_exists('evented_post_date') ? evented_post_date(get_the_ID(), 'Y/m/d') : get_the_date();
					$ee_mini_row(get_the_ID(), $ee_mini_date);
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		<?php else : ?>
			<p class="ee-empty-inline"><?php esc_html_e('هنوز نوشته‌ای منتشر نشده است.', 'evented-edu'); ?></p>
		<?php endif; ?>
	</div>

	<!-- ویژه ها -->
	<?php if (!empty($ee_side_featured_posts)) : ?>
		<div class="ee-widget">
			<h3 class="ee-w-title"><?php esc_html_e('ویژه ها', 'evented-edu'); ?></h3>
			<div class="ee-mini-list">
				<?php
				foreach ($ee_side_featured_posts as $ee_fp) :
					$ee_mini_date = function_exists('evented_post_date') ? evented_post_date($ee_fp, 'Y/m/d') : get_the_date('', $ee_fp);
					$ee_mini_row($ee_fp->ID, $ee_mini_date);
				endforeach;
				?>
			</div>
		</div>
	<?php endif; ?>

	<!-- مطالب پربازدید -->
	<div class="ee-widget">
		<h3 class="ee-w-title"><?php esc_html_e('مطالب پربازدید', 'evented-edu'); ?></h3>
		<?php if ($ee_side_popular->have_posts()) : ?>
			<div class="ee-mini-list">
				<?php
				while ($ee_side_popular->have_posts()) :
					$ee_side_popular->the_post();
					$ee_views = function_exists('evented_get_post_views') ? evented_get_post_views(get_the_ID()) : 0;
					/* translators: %s: تعداد بازدید */
					$ee_mini_row(get_the_ID(), sprintf(__('بازدید %s', 'evented-edu'), number_format_i18n($ee_views)));
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		<?php else : ?>
			<p class="ee-empty-inline"><?php esc_html_e('داده‌ای برای نمایش وجود ندارد.', 'evented-edu'); ?></p>
		<?php endif; ?>
	</div>

	<!-- دوره ها -->
	<?php if ($ee_side_courses->have_posts()) : ?>
		<div class="ee-widget">
			<h3 class="ee-w-title"><?php esc_html_e('دوره ها', 'evented-edu'); ?></h3>
			<div class="ee-mini-list">
				<?php
				while ($ee_side_courses->have_posts()) :
					$ee_side_courses->the_post();
					$ee_mini_row(get_the_ID(), '', 'school');
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	<?php endif; ?>

	<!-- ما را دنبال کنید -->
	<?php if (!empty($ee_side_channels)) : ?>
		<div class="ee-follow">
			<p><?php esc_html_e('ما را در رسانه های اجتماعی دنبال کنید', 'evented-edu'); ?></p>
			<div class="ee-follow-row">
				<?php foreach ($ee_side_channels as $ee_ch) : ?>
					<a class="ee-follow-dot" style="background:<?php echo esc_attr($ee_ch['color']); ?>;" href="<?php echo esc_url($ee_ch['url']); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr($ee_ch['label']); ?>">
						<?php echo esc_html(mb_substr($ee_ch['label'], 0, 1)); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

</aside>
