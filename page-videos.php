<?php
/**
 * Template Name: آرشیو ویدئوها
 * Template Post Type: page
 *
 * قالب برگهٔ /videos/؛ فهرست صفحه‌بندی‌شدهٔ پست‌تایپ clip را بدون نیاز به
 * has_archive یا taxonomy اختصاصی نمایش می‌دهد.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_page       = get_queried_object();
$ee_page_id    = $ee_page instanceof WP_Post ? (int) $ee_page->ID : 0;
$ee_title      = $ee_page_id ? (string) get_the_title($ee_page_id) : __('ویدئوها', 'evented-edu');
$ee_intro      = $ee_page_id ? (string) $ee_page->post_content : '';
$ee_paged      = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
$ee_clip_ready = post_type_exists('clip');
$ee_clips      = null;

if ($ee_clip_ready) {
	$ee_clips = new WP_Query(array(
		'post_type'           => 'clip',
		'post_status'         => 'publish',
		'posts_per_page'      => (int) get_option('posts_per_page', 10),
		'paged'               => $ee_paged,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => false,
	));
}

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-archive ee-videos-page'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'video'));
?>

<main id="ee-main" class="ee-archive-main ee-videos-main">
	<div class="ee-wrap ee-arch-grid">
		<div class="ee-arch-main">
			<header class="ee-arch-head ee-videos-head">
				<h1 class="ee-arch-title">
					<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-smart_display"></use></svg>
					<?php echo esc_html($ee_title); ?>
				</h1>

				<?php if ('' !== trim(wp_strip_all_tags($ee_intro))) : ?>
					<div class="ee-arch-desc ee-videos-intro">
						<?php echo apply_filters('the_content', $ee_intro); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- محتوای برگه از فیلتر استاندارد وردپرس عبور می‌کند. ?>
					</div>
				<?php endif; ?>

				<?php if ($ee_clips instanceof WP_Query) : ?>
					<div class="ee-arch-meta">
						<span>
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-smart_display"></use></svg>
							<?php
							/* translators: %s: تعداد ویدئو */
							echo esc_html(sprintf(_n('%s ویدئو', '%s ویدئو', (int) $ee_clips->found_posts, 'evented-edu'), number_format_i18n((int) $ee_clips->found_posts)));
							?>
						</span>
					</div>
				<?php endif; ?>
			</header>

			<?php if ($ee_clips instanceof WP_Query && $ee_clips->have_posts()) : ?>
				<div class="ee-arch-cards ee-video-cards">
					<?php while ($ee_clips->have_posts()) : $ee_clips->the_post();
						$ee_clip_id    = get_the_ID();
						$ee_clip_date  = function_exists('evented_post_date') ? evented_post_date($ee_clip_id, 'Y/m/d') : get_the_date('', $ee_clip_id);
						$ee_clip_views = function_exists('evented_get_post_views') ? evented_get_post_views($ee_clip_id) : 0;
						$ee_clip_cmts  = (int) get_comments_number($ee_clip_id);
						?>
						<article id="post-<?php echo (int) $ee_clip_id; ?>" <?php post_class('ee-arch-card ee-video-card'); ?>>
							<a class="ee-ac-thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
								<?php if (has_post_thumbnail()) : ?>
									<?php the_post_thumbnail('medium_large', array('loading' => 'lazy', 'decoding' => 'async')); ?>
								<?php else : ?>
									<span class="ee-ac-noimg ee-ic"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-smart_display"></use></svg></span>
								<?php endif; ?>
								<span class="ee-video-play"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-play_arrow"></use></svg></span>
								<span class="ee-ac-tag"><?php esc_html_e('ویدئو', 'evented-edu'); ?></span>
							</a>

							<div class="ee-ac-body">
								<h2 class="ee-ac-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<?php if (has_excerpt() || '' !== trim(wp_strip_all_tags(get_the_content(null, false, $ee_clip_id)))) : ?>
									<p class="ee-ac-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt($ee_clip_id), 22)); ?></p>
								<?php endif; ?>
								<div class="ee-ac-foot">
									<span class="ee-ac-date"><?php echo esc_html($ee_clip_date); ?></span>
									<span class="ee-ac-stats">
										<span title="<?php esc_attr_e('بازدید', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-visibility"></use></svg><?php echo esc_html(number_format_i18n($ee_clip_views)); ?></span>
										<span title="<?php esc_attr_e('دیدگاه', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-forum"></use></svg><?php echo esc_html(number_format_i18n($ee_clip_cmts)); ?></span>
									</span>
								</div>
								<a class="ee-ac-more" href="<?php the_permalink(); ?>"><?php esc_html_e('مشاهده ویدئو', 'evented-edu'); ?><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg></a>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
				<?php echo function_exists('evented_pagination') ? evented_pagination($ee_clips) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<?php
				$ee_empty_title = $ee_clip_ready ? __('هنوز ویدئویی منتشر نشده است', 'evented-edu') : __('بخش ویدئو در دسترس نیست', 'evented-edu');
				$ee_empty_text  = $ee_clip_ready
					? __('پس از انتشار اولین ویدئو، آن را در همین صفحه خواهید دید.', 'evented-edu')
					: __('پست‌تایپ clip ثبت نشده است؛ افزونهٔ مسئول ویدئوها را فعال کنید.', 'evented-edu');
				if (function_exists('evented_empty_state')) {
					echo evented_empty_state(array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'title'   => $ee_empty_title,
						'text'    => $ee_empty_text,
						'icon'    => 'smart_display',
						'actions' => array(array('label' => __('بازگشت به صفحهٔ اصلی', 'evented-edu'), 'url' => home_url('/'), 'primary' => true)),
					));
				} else {
					echo '<p>' . esc_html($ee_empty_text) . '</p>';
				}
				?>
			<?php endif; ?>
		</div>

		<?php get_template_part('template-parts/ee', 'sidebar'); ?>
	</div>
</main>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'video')); ?>
