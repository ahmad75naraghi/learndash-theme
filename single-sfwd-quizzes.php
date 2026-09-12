<?php
/**
 * قالب تکی آزمون (LearnDash) — پوستهٔ «evented-edu»
 *
 * ساختار: عنوان + نوار اطلاعات + نوار وضعیت/مسیریابی، کارت اطلاعات آزمون
 * (تعداد سوال، محدودیت زمانی، تعداد دفعات، درصد قبولی، گواهینامه)، بدنهٔ
 * آزمون (از شورت‌کد لرن‌دش) و سایدبار (فهرست درس‌های دوره + سایر دوره‌ها).
 *
 * بدنهٔ آزمون با شورت‌کد لرن‌دش ساخته می‌شود؛ اگر شورت‌کد در دسترس نبود به
 * `the_content()` برمی‌گردیم تا در همهٔ نسخه‌های لرن‌دش کار کند.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-lms ee-quiz-page'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'courses'));

while (have_posts()) :
	the_post();

	$ee_quiz_id = get_the_ID();

	if (function_exists('evented_track_post_view')) {
		evented_track_post_view($ee_quiz_id);
	}

	$ee_quiz   = function_exists('evented_quiz_data') ? evented_quiz_data($ee_quiz_id) : array();
	$ee_course_id = !empty($ee_quiz['course_id']) ? (int) $ee_quiz['course_id'] : 0;

	if (!$ee_course_id) {
		$ee_course_post = function_exists('learndash_course_get_course_by_step_id') ? learndash_course_get_course_by_step_id($ee_quiz_id) : null;
		if ($ee_course_post instanceof WP_Post) {
			$ee_course_id = (int) $ee_course_post->ID;
		}
	}

	$ee_steps    = ($ee_course_id && function_exists('evented_course_steps')) ? evented_course_steps($ee_course_id) : array();
	$ee_pricing  = $ee_course_id && function_exists('evented_course_pricing') ? evented_course_pricing($ee_course_id) : array('is_free' => true, 'has_access' => false);
	$ee_progress = $ee_course_id && function_exists('evented_course_progress') ? evented_course_progress($ee_course_id) : array('percentage' => 0, 'completed' => 0, 'total' => 0);

	$ee_date  = function_exists('evented_post_date') ? evented_post_date($ee_quiz_id) : get_the_date();
	$ee_time  = function_exists('evented_post_time') ? evented_post_time($ee_quiz_id) : get_the_time();
	$ee_views = function_exists('evented_get_post_views') ? evented_get_post_views($ee_quiz_id) : 0;

	/* بدنهٔ آزمون از لرن‌دش */
	$ee_quiz_body = '';
	if (function_exists('shortcode_exists') && shortcode_exists('ld_quiz')) {
		$ee_quiz_body = (string) do_shortcode('[ld_quiz id="' . $ee_quiz_id . '"]');
	} elseif (function_exists('shortcode_exists') && shortcode_exists('learndash_quiz')) {
		$ee_quiz_body = (string) do_shortcode('[learndash_quiz id="' . $ee_quiz_id . '"]');
	}

	$ee_logged_in = is_user_logged_in();
	?>

	<main class="ee-lms-main">
		<div class="ee-wrap ee-lms-grid is-quiz">

			<article id="quiz-<?php echo esc_attr($ee_quiz_id); ?>" <?php post_class('ee-quiz-main'); ?>>

				<h1 class="ee-quiz-title"><?php the_title(); ?></h1>

				<div class="ee-lesson-meta">
					<span class="ee-meta-item"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-calendar_month"></use></svg><?php echo esc_html($ee_date); ?></span>
					<span class="ee-meta-sep" aria-hidden="true"></span>
					<span class="ee-meta-item"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-schedule"></use></svg><?php echo esc_html($ee_time); ?></span>
					<span class="ee-meta-sep" aria-hidden="true"></span>
					<span class="ee-meta-item"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-visibility"></use></svg>
						<?php
						/* translators: %s: تعداد بازدید */
						echo esc_html(sprintf(__('تعداد بازدید : %s', 'evented-edu'), number_format_i18n($ee_views)));
						?>
					</span>
				</div>

				<div class="ee-lesson-ribbon">
					<span class="ee-lr-state is-active"><?php esc_html_e('آزمون', 'evented-edu'); ?></span>
					<nav class="ee-lr-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
						<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'evented-edu'); ?></a>
						<?php if ($ee_course_id) : ?>
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-chevron_left"></use></svg>
							<a href="<?php echo esc_url(get_permalink($ee_course_id)); ?>"><?php echo esc_html(get_the_title($ee_course_id)); ?></a>
						<?php endif; ?>
						<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-chevron_left"></use></svg>
						<span class="ee-lr-current"><?php the_title(); ?></span>
					</nav>
				</div>

				<!-- اطلاعات آزمون -->
				<ul class="ee-quiz-facts">
					<?php if (!empty($ee_quiz['questions'])) : ?>
						<li>
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-help_center"></use></svg>
							<span class="ee-qf-k"><?php esc_html_e('تعداد سوال', 'evented-edu'); ?></span>
							<b><?php echo esc_html(number_format_i18n((int) $ee_quiz['questions'])); ?></b>
						</li>
					<?php endif; ?>
					<?php if (!empty($ee_quiz['time_limit'])) : ?>
						<li>
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-timer"></use></svg>
							<span class="ee-qf-k"><?php esc_html_e('محدودیت زمانی', 'evented-edu'); ?></span>
							<b>
								<?php
								/* translators: %s: دقیقه */
								echo esc_html(sprintf(__('%s دقیقه', 'evented-edu'), number_format_i18n((int) $ee_quiz['time_limit'])));
								?>
							</b>
						</li>
					<?php endif; ?>
					<?php if (!empty($ee_quiz['attempts'])) : ?>
						<li>
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-replay"></use></svg>
							<span class="ee-qf-k"><?php esc_html_e('تعداد دفعات مجاز', 'evented-edu'); ?></span>
							<b><?php echo esc_html(number_format_i18n((int) $ee_quiz['attempts'])); ?></b>
						</li>
					<?php endif; ?>
					<?php if (!empty($ee_quiz['passing'])) : ?>
						<li>
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-trending_up"></use></svg>
							<span class="ee-qf-k"><?php esc_html_e('درصد قبولی', 'evented-edu'); ?></span>
							<b><?php echo esc_html(number_format_i18n((int) $ee_quiz['passing'])); ?>٪</b>
						</li>
					<?php endif; ?>
					<?php if (!empty($ee_quiz['certificate'])) : ?>
						<li>
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-workspace_premium"></use></svg>
							<span class="ee-qf-k"><?php esc_html_e('گواهینامه', 'evented-edu'); ?></span>
							<b><?php esc_html_e('دارد', 'evented-edu'); ?></b>
						</li>
					<?php endif; ?>
				</ul>

				<?php if (!$ee_logged_in) : ?>
					<div class="ee-quiz-notice">
						<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg>
						<p>
							<?php esc_html_e('برای شرکت در آزمون ابتدا وارد حساب کاربری شوید.', 'evented-edu'); ?>
							<a href="<?php echo esc_url(home_url('/login?redirect_to=' . rawurlencode((string) get_permalink($ee_quiz_id)))); ?>"><?php esc_html_e('ورود / ثبت‌نام', 'evented-edu'); ?></a>
						</p>
					</div>
				<?php endif; ?>

				<!-- بدنهٔ آزمون -->
				<div class="ee-quiz-body">
					<?php if ('' !== trim($ee_quiz_body)) : ?>
						<?php echo $ee_quiz_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- خروجی شورت‌کد لرن‌دش. ?>
					<?php else : ?>
						<?php the_content(); ?>
					<?php endif; ?>
				</div>

				<!-- بازگشت -->
				<nav class="ee-step-nav" aria-label="<?php esc_attr_e('ناوبری', 'evented-edu'); ?>">
					<?php if ($ee_course_id) : ?>
						<a class="ee-step-btn is-back" href="<?php echo esc_url(get_permalink($ee_course_id)); ?>">
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg>
							<span><?php esc_html_e('بازگشت به دوره', 'evented-edu'); ?></span>
						</a>
					<?php else : ?>
						<span class="ee-step-btn is-empty" aria-hidden="true"></span>
					<?php endif; ?>

					<?php
					$ee_adjacent = ($ee_course_id && function_exists('evented_adjacent_steps')) ? evented_adjacent_steps($ee_course_id, $ee_quiz_id) : array();
					if (!empty($ee_adjacent['prev']) && $ee_adjacent['prev'] instanceof WP_Post) :
						?>
						<a class="ee-step-btn is-prev" href="<?php echo esc_url(get_permalink($ee_adjacent['prev'])); ?>">
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg>
							<span class="ee-step-name"><?php echo esc_html(get_the_title($ee_adjacent['prev'])); ?></span>
						</a>
					<?php endif; ?>
				</nav>

			</article>

			<?php if ($ee_course_id) : ?>
				<?php
				get_template_part('template-parts/lms/lesson', 'list', array(
					'ee_steps'      => $ee_steps,
					'ee_course_id'  => $ee_course_id,
					'ee_current_id' => 0,
					'ee_progress'   => $ee_progress,
				));
				?>
			<?php endif; ?>

			<aside class="ee-side">
				<?php if ($ee_course_id && empty($ee_pricing['has_access'])) : ?>
					<?php
					get_template_part('template-parts/lms/enroll', 'card', array(
						'ee_course_id' => $ee_course_id,
						'ee_steps'     => $ee_steps,
						'ee_pricing'   => $ee_pricing,
						'ee_progress'  => $ee_progress,
					));
					?>
				<?php endif; ?>

				<?php
				$ee_other_courses = function_exists('evented_related_courses') ? evented_related_courses($ee_course_id, 4) : array();
				if (!empty($ee_other_courses)) :
					?>
					<div class="ee-widget">
						<h3 class="ee-w-title"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-grid_view"></use></svg> <?php esc_html_e('سایر دوره‌ها', 'evented-edu'); ?></h3>
						<div class="ee-mini-list">
							<?php foreach ($ee_other_courses as $ee_oc) : ?>
								<a class="ee-mini" href="<?php echo esc_url(get_permalink($ee_oc)); ?>">
									<span class="ee-mini-txt">
										<h4><?php echo esc_html(get_the_title($ee_oc)); ?></h4>
									</span>
									<?php if (has_post_thumbnail($ee_oc)) : ?>
										<img class="ee-mini-th" src="<?php echo esc_url(get_the_post_thumbnail_url($ee_oc, 'thumbnail')); ?>" alt="<?php echo esc_attr(get_the_title($ee_oc)); ?>" loading="lazy">
									<?php else : ?>
										<span class="ee-mini-th ee-mini-noimg ee-ic"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-image"></use></svg></span>
									<?php endif; ?>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</aside>

		</div>
	</main>

<?php endwhile; ?>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'courses')); ?>
