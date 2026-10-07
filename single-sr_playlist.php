<?php
/**
 * نمای تکی پادکست‌های قدیمی Sonaar، مستقل از افزونه و Elementor.
 *
 * @package evented-edu
 */
defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-single ee-podcast-single'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'podcast'));

while (have_posts()) :
	the_post();
	$ee_id     = get_the_ID();
	$ee_tracks = function_exists('evented_podcast_tracks') ? evented_podcast_tracks($ee_id) : array();
	$ee_first  = !empty($ee_tracks) ? $ee_tracks[0] : array('title' => '', 'url' => '');
	$ee_terms  = taxonomy_exists('playlist-category') ? get_the_terms($ee_id, 'playlist-category') : array();
	$ee_terms  = is_wp_error($ee_terms) ? array() : (array) $ee_terms;
	?>
	<main id="ee-main" class="ee-podcast-main">
		<div class="ee-wrap ee-podcast-wrap">
			<nav class="ee-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
				<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'evented-edu'); ?></a>
				<svg class="ee-ic" aria-hidden="true"><use href="#i-chevron_left"></use></svg>
				<a href="<?php echo esc_url(home_url('/podcast/')); ?>"><?php esc_html_e('پادکست‌ها', 'evented-edu'); ?></a>
				<svg class="ee-ic" aria-hidden="true"><use href="#i-chevron_left"></use></svg>
				<span><?php the_title(); ?></span>
			</nav>

			<article <?php post_class('ee-podcast-shell'); ?>>
				<header class="ee-podcast-head">
					<div class="ee-podcast-cover">
						<?php if (has_post_thumbnail()) : ?>
							<?php the_post_thumbnail('large', array('loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async')); ?>
						<?php else : ?>
							<span><svg class="ee-ic" aria-hidden="true"><use href="#i-podcasts"></use></svg></span>
						<?php endif; ?>
					</div>
					<div class="ee-podcast-summary">
						<span class="ee-resource-eyebrow"><svg class="ee-ic" aria-hidden="true"><use href="#i-podcasts"></use></svg><?php esc_html_e('پادکست شمیم', 'evented-edu'); ?></span>
						<h1><?php the_title(); ?></h1>
						<div class="ee-podcast-meta">
							<span><svg class="ee-ic" aria-hidden="true"><use href="#i-calendar_month"></use></svg><?php echo esc_html(get_the_date()); ?></span>
							<span><svg class="ee-ic" aria-hidden="true"><use href="#i-podcasts"></use></svg><?php echo esc_html(sprintf('%s قسمت', number_format_i18n(count($ee_tracks)))); ?></span>
						</div>
						<?php if (!empty($ee_terms)) : ?><div class="ee-podcast-terms"><?php foreach ($ee_terms as $ee_term) : $ee_link = get_term_link($ee_term); if (!is_wp_error($ee_link)) : ?><a href="<?php echo esc_url($ee_link); ?>"><?php echo esc_html($ee_term->name); ?></a><?php endif; endforeach; ?></div><?php endif; ?>
						<?php if (!empty($ee_tracks)) : ?>
							<div class="ee-podcast-player" data-ee-podcast-player>
								<div class="ee-podcast-now"><span><?php esc_html_e('در حال پخش', 'evented-edu'); ?></span><strong data-ee-podcast-title><?php echo esc_html($ee_first['title']); ?></strong></div>
								<audio controls preload="metadata" src="<?php echo esc_url($ee_first['url']); ?>" data-ee-podcast-audio><?php esc_html_e('مرورگر شما پخش صوت را پشتیبانی نمی‌کند.', 'evented-edu'); ?></audio>
							</div>
						<?php endif; ?>
					</div>
				</header>

				<?php if (trim((string) get_the_content())) : ?><div class="ee-podcast-content ee-post-content"><?php the_content(); ?></div><?php endif; ?>

				<section class="ee-podcast-track-section" aria-labelledby="eePodcastTracks">
					<div class="ee-podcast-section-head"><div><span><?php esc_html_e('فهرست پخش', 'evented-edu'); ?></span><h2 id="eePodcastTracks"><?php esc_html_e('قسمت‌های این پادکست', 'evented-edu'); ?></h2></div><strong><?php echo esc_html(number_format_i18n(count($ee_tracks))); ?></strong></div>
					<?php if (!empty($ee_tracks)) : ?>
						<ol class="ee-podcast-tracks">
							<?php foreach ($ee_tracks as $ee_index => $ee_track) : ?>
								<li>
									<button type="button" data-ee-podcast-src="<?php echo esc_url($ee_track['url']); ?>" data-ee-podcast-name="<?php echo esc_attr($ee_track['title']); ?>"<?php echo 0 === $ee_index ? ' aria-current="true"' : ''; ?>>
										<span class="ee-podcast-track-number"><?php echo esc_html(number_format_i18n($ee_index + 1)); ?></span>
										<span class="ee-podcast-track-copy"><strong><?php echo esc_html($ee_track['title']); ?></strong><?php if ($ee_track['artist']) : ?><small><?php echo esc_html($ee_track['artist']); ?></small><?php endif; ?><?php if ($ee_track['description']) : ?><small><?php echo esc_html($ee_track['description']); ?></small><?php endif; ?></span>
										<svg class="ee-ic" aria-hidden="true"><use href="#i-play_arrow"></use></svg>
									</button>
									<a class="ee-podcast-download" href="<?php echo esc_url($ee_track['url']); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr(sprintf('باز کردن فایل %s', $ee_track['title'])); ?>"><svg class="ee-ic" aria-hidden="true"><use href="#i-download"></use></svg></a>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php else : ?>
						<?php echo function_exists('evented_empty_state') ? evented_empty_state(array('title' => 'فایل صوتی پیدا نشد', 'text' => 'دادهٔ پادکست وجود دارد، اما URL قابل پخشی در فهرست قدیمی پیدا نشد.', 'icon' => 'podcasts')) : '<p>' . esc_html__('فایل صوتی پیدا نشد.', 'evented-edu') . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				</section>
			</article>
		</div>
	</main>
	<?php
endwhile;

get_template_part('template-parts/ee', 'footer', array('ee_active' => 'podcast'));
