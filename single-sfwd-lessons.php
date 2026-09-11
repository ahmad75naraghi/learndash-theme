<?php
/**
 * قالب تکی درس (LearnDash) — طراحی «evented-edu» مطابق الگوی صفحهٔ درس شمیم
 *
 * ساختار: ستون اصلی (عنوان، نوار اطلاعات، نوار وضعیت + مسیریابی، آکاردئون‌های
 * کلیپ/پادکست/متن، آزمون‌ها، دکمهٔ «تکمیل شد»، ناوبری قبلی/بعدی) + سایدبار
 * (فهرست درس‌های دوره با نشانهٔ پیشرفت و کارت ثبت‌نام در صورت نداشتن دسترسی).
 *
 * پرفورمنس: رسانهٔ درس فقط همین‌جا و یک‌بار ساخته می‌شود (بدون oEmbed داخل
 * حلقه)؛ دادهٔ گام‌ها با evented_course_steps() یک‌بار محاسبه می‌شود.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-lms ee-lesson-page'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'courses'));

while (have_posts()) :
	the_post();

	$ee_lesson_id = get_the_ID();

	if (function_exists('evented_track_post_view')) {
		evented_track_post_view($ee_lesson_id);
	}

	/* --- دورهٔ مادر درس --- */
	$ee_course_post = null;
	if (function_exists('learndash_course_get_course_by_step_id')) {
		$ee_course_post = learndash_course_get_course_by_step_id($ee_lesson_id);
	}
	if (!$ee_course_post instanceof WP_Post && function_exists('learndash_get_course_id')) {
		$ee_parent_course_id = (int) learndash_get_course_id($ee_lesson_id);
		if ($ee_parent_course_id) {
			$ee_course_post = get_post($ee_parent_course_id);
		}
	}
	$ee_course_id = $ee_course_post instanceof WP_Post ? (int) $ee_course_post->ID : 0;

	$ee_steps    = $ee_course_id && function_exists('evented_course_steps') ? evented_course_steps($ee_course_id) : array();
	$ee_pricing  = $ee_course_id && function_exists('evented_course_pricing') ? evented_course_pricing($ee_course_id) : array('is_free' => true, 'price_type' => 'open', 'price' => '', 'has_access' => false);
	$ee_progress = $ee_course_id && function_exists('evented_course_progress') ? evented_course_progress($ee_course_id) : array('percentage' => 0, 'completed' => 0, 'total' => 0);

	/* --- دسترسی و وضعیت همین درس --- */
	$ee_is_sample = (function_exists('learndash_get_setting') && learndash_get_setting($ee_lesson_id, 'sample_lesson') === 'on');
	$ee_has_access = !empty($ee_pricing['has_access']) || $ee_is_sample;

	$ee_current_step = array(
		'id'           => $ee_lesson_id,
		'is_unlocked'  => $ee_has_access,
		'is_sample'    => $ee_is_sample,
		'is_completed' => false,
	);
	foreach ($ee_steps as $ee_step) {
		if ((int) $ee_step['id'] === $ee_lesson_id) {
			$ee_current_step = $ee_step;
			break;
		}
	}
	$ee_state = function_exists('evented_step_state')
		? evented_step_state($ee_current_step, true)
		: array('class' => 'is-open', 'label' => '');

	/* --- رسانهٔ درس --- */
	$ee_media  = function_exists('evented_lesson_media') ? evented_lesson_media($ee_lesson_id) : array('video' => '', 'poster' => '', 'audio' => array(), 'files' => array());
	$ee_quizzes = function_exists('evented_lesson_quizzes') ? evented_lesson_quizzes($ee_lesson_id) : array();
	$ee_adjacent = ($ee_course_id && function_exists('evented_adjacent_steps')) ? evented_adjacent_steps($ee_course_id, $ee_lesson_id) : array('prev' => null, 'next' => null);

	$ee_date     = function_exists('evented_post_date') ? evented_post_date($ee_lesson_id) : get_the_date();
	$ee_time     = function_exists('evented_post_time') ? evented_post_time($ee_lesson_id) : get_the_time();
	$ee_views    = function_exists('evented_get_post_views') ? evented_get_post_views($ee_lesson_id) : 0;
	$ee_comments = (int) get_comments_number($ee_lesson_id);
	$ee_duration = (int) get_post_meta($ee_lesson_id, '_learndash_course_grid_duration', true);
	?>

	<main class="ee-lms-main">
		<div class="ee-wrap ee-lms-grid is-lesson">

			<article id="lesson-<?php echo esc_attr($ee_lesson_id); ?>" <?php post_class('ee-lesson-main'); ?>>

				<h1 class="ee-lesson-title"><?php the_title(); ?></h1>

				<!-- نوار اطلاعات -->
				<div class="ee-lesson-meta">
					<span class="ee-meta-item"><span class="material-symbols-outlined ee-ic">calendar_month</span><?php echo esc_html($ee_date); ?></span>
					<span class="ee-meta-sep" aria-hidden="true"></span>
					<span class="ee-meta-item"><span class="material-symbols-outlined ee-ic">schedule</span><?php echo esc_html($ee_time); ?></span>
					<span class="ee-meta-sep" aria-hidden="true"></span>
					<span class="ee-meta-item"><span class="material-symbols-outlined ee-ic">forum</span>
						<?php
						if ($ee_comments > 0) {
							/* translators: %s: تعداد دیدگاه */
							echo esc_html(sprintf(_n('%s دیدگاه', '%s دیدگاه', $ee_comments, 'evented-edu'), number_format_i18n($ee_comments)));
						} else {
							esc_html_e('بدون دیدگاه', 'evented-edu');
						}
						?>
					</span>
					<span class="ee-meta-sep" aria-hidden="true"></span>
					<span class="ee-meta-item"><span class="material-symbols-outlined ee-ic">visibility</span>
						<?php
						/* translators: %s: تعداد بازدید */
						echo esc_html(sprintf(__('تعداد بازدید : %s', 'evented-edu'), number_format_i18n($ee_views)));
						?>
					</span>
					<?php if ($ee_duration > 0 && function_exists('evented_format_duration')) : ?>
						<span class="ee-meta-sep" aria-hidden="true"></span>
						<span class="ee-meta-item"><span class="material-symbols-outlined ee-ic">timer</span><?php echo esc_html(evented_format_duration($ee_duration)); ?></span>
					<?php endif; ?>
				</div>

				<!-- نوار وضعیت + مسیریابی -->
				<div class="ee-lesson-ribbon">
					<span class="ee-lr-state <?php echo esc_attr($ee_state['class']); ?>"><?php echo esc_html($ee_state['label']); ?></span>
					<nav class="ee-lr-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
						<?php if ($ee_course_post instanceof WP_Post) : ?>
							<a href="<?php echo esc_url(get_permalink($ee_course_post)); ?>"><?php echo esc_html(get_the_title($ee_course_post)); ?></a>
							<span class="material-symbols-outlined ee-ic">chevron_left</span>
						<?php endif; ?>
						<span class="ee-lr-current"><?php the_title(); ?></span>
					</nav>
				</div>

				<?php if (!$ee_has_access) : ?>

					<!-- درس قفل‌شده -->
					<div class="ee-locked">
						<span class="material-symbols-outlined ee-ic">lock</span>
						<h2><?php esc_html_e('محتوای این درس قفل است', 'evented-edu'); ?></h2>
						<p><?php esc_html_e('برای تماشای این درس ابتدا در دوره ثبت‌نام کنید.', 'evented-edu'); ?></p>
						<?php if ($ee_course_post instanceof WP_Post) : ?>
							<a class="ee-btn-solid" href="<?php echo esc_url(get_permalink($ee_course_post)); ?>#ee-enroll">
								<?php esc_html_e('ثبت‌نام در دوره', 'evented-edu'); ?>
							</a>
						<?php endif; ?>
					</div>

				<?php else : ?>

					<!-- کلیپ -->
					<?php if (!empty($ee_media['video'])) : ?>
						<section class="ee-acc is-open" data-acc>
							<button type="button" class="ee-acc-head" aria-expanded="true">
								<span><?php esc_html_e('کلیپ', 'evented-edu'); ?></span>
								<span class="material-symbols-outlined ee-ic ee-acc-ic">expand_more</span>
							</button>
							<div class="ee-acc-body">
								<div class="ee-player">
									<?php echo evented_media_player($ee_media['video'], isset($ee_media['poster']) ? $ee_media['poster'] : ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- مارک‌آپ امن درون تابع ساخته می‌شود. ?>
								</div>
								<?php if (preg_match('/\.(mp4|webm|ogg|ogv|m4v)(\?|#|$)/i', $ee_media['video'])) : ?>
									<div class="ee-player-actions">
										<a class="ee-btn ee-btn-primary" href="<?php echo esc_url($ee_media['video']); ?>" download>
											<span class="material-symbols-outlined ee-ic">download</span>
											<?php esc_html_e('دانلود کلیپ', 'evented-edu'); ?>
										</a>
									</div>
								<?php endif; ?>
							</div>
						</section>
					<?php endif; ?>

					<!-- پادکست -->
					<?php if (!empty($ee_media['audio'])) : ?>
						<section class="ee-acc" data-acc>
							<button type="button" class="ee-acc-head" aria-expanded="false">
								<span><?php esc_html_e('پادکست', 'evented-edu'); ?></span>
								<span class="material-symbols-outlined ee-ic ee-acc-ic">expand_more</span>
							</button>
							<div class="ee-acc-body">
								<?php foreach ($ee_media['audio'] as $ee_audio) : ?>
									<div class="ee-audio-row">
										<span class="material-symbols-outlined ee-ic">podcasts</span>
										<?php echo evented_media_player($ee_audio); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- مارک‌آپ امن درون تابع ساخته می‌شود. ?>
										<a class="ee-dl" href="<?php echo esc_url($ee_audio); ?>" download>
											<?php esc_html_e('دانلود پادکست', 'evented-edu'); ?>
										</a>
									</div>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endif; ?>

					<!-- متن درس -->
					<section class="ee-acc<?php echo empty($ee_media['video']) && empty($ee_media['audio']) ? ' is-open' : ''; ?>" data-acc>
						<button type="button" class="ee-acc-head" aria-expanded="<?php echo empty($ee_media['video']) && empty($ee_media['audio']) ? 'true' : 'false'; ?>">
							<span><?php esc_html_e('متن', 'evented-edu'); ?></span>
							<span class="material-symbols-outlined ee-ic ee-acc-ic">expand_more</span>
						</button>
						<div class="ee-acc-body">
							<div class="ee-lesson-body">
								<?php the_content(); ?>
							</div>

							<?php if (!empty($ee_media['files'])) : ?>
								<div class="ee-files">
									<span class="ee-files-label"><span class="material-symbols-outlined ee-ic">folder</span> <?php esc_html_e('فایل‌های پیوست', 'evented-edu'); ?></span>
									<?php foreach ($ee_media['files'] as $ee_file) : ?>
										<a class="ee-file" href="<?php echo esc_url($ee_file['url']); ?>" download>
											<span class="material-symbols-outlined ee-ic">download</span>
											<?php echo esc_html($ee_file['title'] ? $ee_file['title'] : __('دانلود فایل', 'evented-edu')); ?>
										</a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</section>

					<!-- آزمون‌ها -->
					<?php if (!empty($ee_quizzes)) : ?>
						<div class="ee-quiz-cta">
							<?php foreach ($ee_quizzes as $ee_quiz) : ?>
								<a class="ee-quiz-btn" href="<?php echo esc_url(get_permalink($ee_quiz)); ?>">
									<span class="material-symbols-outlined ee-ic">quiz</span>
									<?php echo esc_html($ee_quiz->post_title ? $ee_quiz->post_title : __('آزمون', 'evented-edu')); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<!-- ثبت تکمیل درس -->
					<?php if (is_user_logged_in() && !empty($ee_course_id)) : ?>
						<?php
						$ee_is_done     = !empty($ee_current_step['is_completed']);
						$ee_mark_nonce  = wp_create_nonce('mark_complete_nonce_' . $ee_lesson_id);
						?>
						<div class="ee-mark" data-mark-wrapper>
							<button type="button"
								class="ee-mark-btn<?php echo $ee_is_done ? ' is-done' : ''; ?>"
								data-action="custom_mark_lesson_complete"
								data-nonce="<?php echo esc_attr($ee_mark_nonce); ?>"
								data-lesson="<?php echo esc_attr($ee_lesson_id); ?>"
								data-course="<?php echo esc_attr($ee_course_id); ?>"
								data-is-done="<?php echo $ee_is_done ? '1' : '0'; ?>"
								aria-pressed="<?php echo $ee_is_done ? 'true' : 'false'; ?>">
								<span class="material-symbols-outlined ee-ic"><?php echo $ee_is_done ? 'task_alt' : 'radio_button_unchecked'; ?></span>
								<span data-mark-label><?php echo $ee_is_done ? esc_html__('تکمیل شد', 'evented-edu') : esc_html__('علامت‌گذاری به‌عنوان تکمیل‌شده', 'evented-edu'); ?></span>
							</button>
							<span class="ee-mark-msg" role="status" aria-live="polite"></span>
						</div>
					<?php endif; ?>

				<?php endif; ?>

				<!-- ناوبری قبلی / بعدی -->
				<nav class="ee-step-nav" aria-label="<?php esc_attr_e('ناوبری بین درس‌ها', 'evented-edu'); ?>">
					<?php if (!empty($ee_adjacent['next']) && $ee_adjacent['next'] instanceof WP_Post) : ?>
						<a class="ee-step-btn is-next" href="<?php echo esc_url(get_permalink($ee_adjacent['next'])); ?>">
							<span><?php esc_html_e('بعدی', 'evented-edu'); ?></span>
							<span class="ee-step-name"><?php echo esc_html(get_the_title($ee_adjacent['next'])); ?></span>
							<span class="material-symbols-outlined ee-ic">arrow_forward</span>
						</a>
					<?php else : ?>
						<span class="ee-step-btn is-empty" aria-hidden="true"></span>
					<?php endif; ?>

					<?php if (!empty($ee_adjacent['prev']) && $ee_adjacent['prev'] instanceof WP_Post) : ?>
						<a class="ee-step-btn is-prev" href="<?php echo esc_url(get_permalink($ee_adjacent['prev'])); ?>">
							<span class="material-symbols-outlined ee-ic">arrow_back</span>
							<span class="ee-step-name"><?php echo esc_html(get_the_title($ee_adjacent['prev'])); ?></span>
							<span><?php esc_html_e('قبلی', 'evented-edu'); ?></span>
						</a>
					<?php elseif ($ee_course_post instanceof WP_Post) : ?>
						<a class="ee-step-btn is-back" href="<?php echo esc_url(get_permalink($ee_course_post)); ?>">
							<span class="material-symbols-outlined ee-ic">arrow_back</span>
							<span><?php esc_html_e('بازگشت به دوره', 'evented-edu'); ?></span>
						</a>
					<?php endif; ?>
				</nav>

			</article>

			<?php
			get_template_part('template-parts/lms/lesson', 'list', array(
				'ee_steps'      => $ee_steps,
				'ee_course_id'  => $ee_course_id,
				'ee_current_id' => $ee_lesson_id,
				'ee_progress'   => $ee_progress,
			));
			?>

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

		</div>
	</main>

<?php endwhile; ?>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'courses')); ?>
