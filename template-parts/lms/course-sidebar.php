<?php
/**
 * سایدبار صفحهٔ دوره (مطابق الگوی «شمیم» + کارت ثبت‌نام لرن‌دش)
 *
 * آرگومان‌ها (get_template_part):
 *   ee_course_id, ee_steps, ee_pricing, ee_progress
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_course_id = isset($args['ee_course_id']) ? (int) $args['ee_course_id'] : get_the_ID();

$ee_related = function_exists('evented_related_courses') ? evented_related_courses($ee_course_id, 8) : array();
$ee_latest  = function_exists('evented_latest_courses') ? evented_latest_courses(6) : array();
$ee_shares  = function_exists('evented_share_links') ? evented_share_links((string) get_permalink($ee_course_id), (string) get_the_title($ee_course_id)) : array();
$ee_channels = function_exists('evented_channel_links') ? evented_channel_links() : array();
?>
<aside class="ee-side" aria-label="<?php esc_attr_e('ستون کناری دوره', 'evented-edu'); ?>">

	<?php
	get_template_part('template-parts/lms/enroll', 'card', array(
		'ee_course_id' => $ee_course_id,
		'ee_steps'     => isset($args['ee_steps']) ? $args['ee_steps'] : array(),
		'ee_pricing'   => isset($args['ee_pricing']) ? $args['ee_pricing'] : array(),
		'ee_progress'  => isset($args['ee_progress']) ? $args['ee_progress'] : array(),
	));
	?>

	<!-- جستجو + اشتراک‌گذاری -->
	<div class="ee-widget ee-widget-search">
		<form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
			<label class="screen-reader-text" for="ee-course-s"><?php esc_html_e('جستجو در دوره‌ها', 'evented-edu'); ?></label>
			<div class="ee-search-inline">
				<input id="ee-course-s" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('جستجو در دوره‌ها', 'evented-edu'); ?>">
				<button type="submit" aria-label="<?php esc_attr_e('جستجو', 'evented-edu'); ?>">
					<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-search"></use></svg>
				</button>
			</div>
		</form>

		<?php if (!empty($ee_shares)) : ?>
			<div class="ee-share-block">
				<span class="ee-share-label"><?php esc_html_e('اشتراک گذاری این صفحه در:', 'evented-edu'); ?></span>
				<div class="ee-share-row">
					<?php foreach ($ee_shares as $ee_share) : ?>
						<a class="ee-share-btn" style="background:<?php echo esc_attr($ee_share['color']); ?>;" href="<?php echo esc_url($ee_share['url']); ?>" target="_blank" rel="noopener nofollow" title="<?php echo esc_attr($ee_share['label']); ?>">
							<span><?php echo esc_html(mb_substr($ee_share['label'], 0, 1)); ?></span>
						</a>
					<?php endforeach; ?>
					<button type="button" class="ee-share-btn ee-share-copy" data-copy="<?php echo esc_attr(get_permalink($ee_course_id)); ?>" title="<?php esc_attr_e('کپی لینک', 'evented-edu'); ?>">
						<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-link"></use></svg>
					</button>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<!-- سایر دوره‌ها -->
	<?php if (!empty($ee_related)) : ?>
		<div class="ee-widget">
			<h3 class="ee-w-title"><?php esc_html_e('سایر دوره ها', 'evented-edu'); ?></h3>
			<div class="ee-mini-list">
				<?php foreach ($ee_related as $ee_rc) : ?>
					<a class="ee-mini" href="<?php echo esc_url(get_permalink($ee_rc)); ?>">
						<div class="ee-mini-txt">
							<h4><?php echo esc_html(get_the_title($ee_rc)); ?></h4>
							<?php
							$ee_rc_price = function_exists('evented_course_pricing') ? evented_course_pricing($ee_rc->ID) : array();
							if (!empty($ee_rc_price['is_free'])) :
								?>
								<span class="ee-mini-date"><?php esc_html_e('رایگان', 'evented-edu'); ?></span>
							<?php endif; ?>
						</div>
						<?php if (has_post_thumbnail($ee_rc)) : ?>
							<img class="ee-mini-th" src="<?php echo esc_url(get_the_post_thumbnail_url($ee_rc, 'thumbnail')); ?>" alt="<?php echo esc_attr(get_the_title($ee_rc)); ?>" loading="lazy">
						<?php else : ?>
							<span class="ee-mini-th ee-mini-noimg ee-ic"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-school"></use></svg></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- آخرین دوره‌ها -->
	<?php if (!empty($ee_latest)) : ?>
		<div class="ee-widget">
			<h3 class="ee-w-title"><?php esc_html_e('آخرین دوره ها', 'evented-edu'); ?></h3>
			<div class="ee-mini-list">
				<?php foreach ($ee_latest as $ee_lc) : ?>
					<a class="ee-mini" href="<?php echo esc_url(get_permalink($ee_lc)); ?>">
						<div class="ee-mini-txt">
							<h4><?php echo esc_html(get_the_title($ee_lc)); ?></h4>
							<span class="ee-mini-date"><?php echo esc_html(function_exists('evented_post_date') ? evented_post_date($ee_lc, 'Y/m/d') : get_the_date('', $ee_lc)); ?></span>
						</div>
						<?php if (has_post_thumbnail($ee_lc)) : ?>
							<img class="ee-mini-th" src="<?php echo esc_url(get_the_post_thumbnail_url($ee_lc, 'thumbnail')); ?>" alt="<?php echo esc_attr(get_the_title($ee_lc)); ?>" loading="lazy">
						<?php else : ?>
							<span class="ee-mini-th ee-mini-noimg ee-ic"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-school"></use></svg></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- ما را دنبال کنید -->
	<?php if (!empty($ee_channels)) : ?>
		<div class="ee-follow">
			<p><?php esc_html_e('ما را در رسانه های اجتماعی دنبال کنید', 'evented-edu'); ?></p>
			<div class="ee-follow-row">
				<?php foreach ($ee_channels as $ee_ch) : ?>
					<a class="ee-follow-dot" style="background:<?php echo esc_attr($ee_ch['color']); ?>;" href="<?php echo esc_url($ee_ch['url']); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr($ee_ch['label']); ?>">
						<?php echo esc_html(mb_substr($ee_ch['label'], 0, 1)); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

</aside>
