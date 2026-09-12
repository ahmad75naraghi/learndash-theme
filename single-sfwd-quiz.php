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

	/* بدنهٔ آزمون از لرن‌دش:
	   مسیر استاندارد لرن‌دش فیلتر the_content است (موتور آزمون، اسکریپت‌ها و
	   کنترل دسترسی را خودش تزریق می‌کند). اگر به هر دلیل موتور آزمون در محتوا
	   نبود، از شورت‌کد [ld_quiz] استفاده می‌کنیم تا دوبار رندر نشود. */
	ob_start();
	the_content();
	$ee_quiz_body = (string) ob_get_clean();
	$ee_has_engine = (false !== strpos($ee_quiz_body, 'wpProQuiz') || false !== strpos($ee_quiz_body, 'ld-quiz') || false !== strpos($ee_quiz_body, 'learndash-wrapper'));
	if (!$ee_has_engine && function_exists('shortcode_exists')) {
		if (shortcode_exists('ld_quiz')) {
			$ee_quiz_body .= (string) do_shortcode('[ld_quiz quiz_id="' . $ee_quiz_id . '"]');
		} elseif (shortcode_exists('learndash_quiz')) {
			$ee_quiz_body .= (string) do_shortcode('[learndash_quiz quiz_id="' . $ee_quiz_id . '"]');
		}
	}

	$ee_logged_in = is_user_logged_in();
	$ee_fa        = function_exists('evented_fa_digits') ? 'evented_fa_digits' : 'strval';

	/* سوابق آزمون کاربر (LearnDash: user meta _sfwd-quizzes) */
	$ee_attempts = array();
	if ($ee_logged_in) {
		$ee_all = get_user_meta(get_current_user_id(), '_sfwd-quizzes', true);
		if (is_array($ee_all)) {
			foreach ($ee_all as $a) {
				if (isset($a['quiz']) && (int) $a['quiz'] === $ee_quiz_id) {
					$ee_attempts[] = array(
						'time'  => isset($a['time']) ? (int) $a['time'] : 0,
						'pct'   => isset($a['percentage']) ? (float) $a['percentage'] : 0,
						'pass'  => !empty($a['pass']),
						'score' => isset($a['score']) ? (int) $a['score'] : null,
						'count' => isset($a['count']) ? (int) $a['count'] : null,
					);
				}
			}
			usort($ee_attempts, static function ($x, $y) { return $y['time'] <=> $x['time']; });
		}
	}
	$ee_best   = null;
	$ee_passed = false;
	foreach ($ee_attempts as $a) {
		if (null === $ee_best || $a['pct'] > $ee_best['pct']) { $ee_best = $a; }
		if ($a['pass']) { $ee_passed = true; }
	}
	$ee_used     = count($ee_attempts);
	$ee_max_try  = !empty($ee_quiz['attempts']) ? (int) $ee_quiz['attempts'] : 0;
	$ee_left     = $ee_max_try ? max(0, $ee_max_try - $ee_used) : -1;
	$ee_has_access = !$ee_course_id || !empty($ee_pricing['has_access']) || !empty($ee_pricing['is_free']);

	$ee_cert_link = '';
	if ($ee_passed && !empty($ee_quiz['certificate']) && function_exists('learndash_get_certificate_link')) {
		$ee_cert_link = (string) learndash_get_certificate_link($ee_quiz_id, get_current_user_id());
	}

	/* وضعیت کلی برای هیرو */
	if (!$ee_logged_in) {
		$ee_state = array('key' => 'login', 'label' => 'برای شرکت وارد شوید', 'icon' => 'lock');
	} elseif ($ee_passed) {
		$ee_state = array('key' => 'passed', 'label' => 'قبول شده‌اید', 'icon' => 'verified');
	} elseif ($ee_used > 0) {
		$ee_state = array('key' => 'retry', 'label' => 'قبول نشده‌اید', 'icon' => 'replay');
	} else {
		$ee_state = array('key' => 'ready', 'label' => 'آمادهٔ شروع', 'icon' => 'bolt');
	}
	?>

	<main class="ee-lms-main ee-quiz-page-main">
		<div class="ee-wrap ee-lms-grid is-quiz">

			<article id="quiz-<?php echo esc_attr($ee_quiz_id); ?>" <?php post_class('ee-quiz-main'); ?>>

				<!-- هیرو -->
				<header class="ee-qz-hero is-<?php echo esc_attr($ee_state['key']); ?>">
					<span class="ee-qz-orb o1" aria-hidden="true"></span>
					<span class="ee-qz-orb o2" aria-hidden="true"></span>

					<nav class="ee-qz-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
						<a href="<?php echo esc_url(home_url('/')); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-home"></use></svg><?php esc_html_e('خانه', 'evented-edu'); ?></a>
						<?php if ($ee_course_id) : ?>
							<svg class="ee-ic sep" aria-hidden="true" focusable="false"><use href="#i-chevron_left"></use></svg>
							<a href="<?php echo esc_url(get_permalink($ee_course_id)); ?>"><?php echo esc_html(get_the_title($ee_course_id)); ?></a>
						<?php endif; ?>
						<svg class="ee-ic sep" aria-hidden="true" focusable="false"><use href="#i-chevron_left"></use></svg>
						<span aria-current="page"><?php esc_html_e('آزمون', 'evented-edu'); ?></span>
					</nav>

					<div class="ee-qz-hero-row">
						<div class="ee-qz-hero-ic" aria-hidden="true">
							<svg class="ee-ic" focusable="false"><use href="#i-quiz"></use></svg>
							<span class="ring"></span>
						</div>
						<div class="ee-qz-hero-tx">
							<span class="ee-qz-kicker"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-task_alt"></use></svg> <?php esc_html_e('آزمون پایان دوره', 'evented-edu'); ?></span>
							<h1 class="ee-quiz-title"><?php the_title(); ?></h1>
							<div class="ee-qz-sub">
								<span class="ee-qz-state"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-<?php echo esc_attr($ee_state['icon']); ?>"></use></svg><?php echo esc_html($ee_state['label']); ?></span>
								<span class="ee-qz-dot" aria-hidden="true"></span>
								<span><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-calendar_month"></use></svg><?php echo esc_html($ee_date); ?></span>
								<span class="ee-qz-dot" aria-hidden="true"></span>
								<span><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-visibility"></use></svg><?php echo esc_html($ee_fa(number_format($ee_views))); ?> <?php esc_html_e('بازدید', 'evented-edu'); ?></span>
							</div>
						</div>
					</div>

					<!-- آمار آزمون -->
					<ul class="ee-qz-stats">
						<li class="t1">
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-help_center"></use></svg>
							<b><?php echo !empty($ee_quiz['questions']) ? esc_html($ee_fa($ee_quiz['questions'])) : '—'; ?></b>
							<small><?php esc_html_e('سؤال', 'evented-edu'); ?></small>
						</li>
						<li class="t2">
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-timer"></use></svg>
							<b><?php echo !empty($ee_quiz['time_limit']) ? esc_html($ee_fa((int) ceil($ee_quiz['time_limit'] / 60))) : '∞'; ?></b>
							<small><?php echo !empty($ee_quiz['time_limit']) ? esc_html__('دقیقه', 'evented-edu') : esc_html__('بدون محدودیت زمان', 'evented-edu'); ?></small>
						</li>
						<li class="t3">
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-trending_up"></use></svg>
							<b><?php echo !empty($ee_quiz['passing']) ? esc_html($ee_fa($ee_quiz['passing'])) . '٪' : '—'; ?></b>
							<small><?php esc_html_e('حد نصاب قبولی', 'evented-edu'); ?></small>
						</li>
						<li class="t4">
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-replay"></use></svg>
							<b><?php echo $ee_max_try ? esc_html($ee_fa($ee_max_try)) : '∞'; ?></b>
							<small><?php echo $ee_max_try ? esc_html__('بار مجاز', 'evented-edu') : esc_html__('تلاش نامحدود', 'evented-edu'); ?></small>
						</li>
						<li class="t5<?php echo empty($ee_quiz['certificate']) ? ' is-off' : ''; ?>">
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-workspace_premium"></use></svg>
							<b><?php echo !empty($ee_quiz['certificate']) ? esc_html__('دارد', 'evented-edu') : esc_html__('ندارد', 'evented-edu'); ?></b>
							<small><?php esc_html_e('گواهینامه', 'evented-edu'); ?></small>
						</li>
					</ul>
				</header>

				<?php if ($ee_logged_in && $ee_used > 0) : ?>
					<!-- نتیجهٔ من -->
					<section class="ee-qz-result is-<?php echo $ee_passed ? 'pass' : 'fail'; ?>" aria-label="<?php esc_attr_e('نتیجهٔ من', 'evented-edu'); ?>">
						<div class="ee-qz-gauge" style="--p:<?php echo (int) round($ee_best['pct']); ?>">
							<svg viewBox="0 0 36 36" aria-hidden="true"><path class="bg" d="M18 2.5a15.5 15.5 0 1 1 0 31 15.5 15.5 0 0 1 0-31"/><path class="fg" d="M18 2.5a15.5 15.5 0 1 1 0 31 15.5 15.5 0 0 1 0-31"/></svg>
							<b><?php echo esc_html($ee_fa((int) round($ee_best['pct']))); ?><i>٪</i></b>
							<small><?php esc_html_e('بهترین نتیجه', 'evented-edu'); ?></small>
						</div>
						<div class="ee-qz-result-tx">
							<h2>
								<?php if ($ee_passed) : ?>
									<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-verified"></use></svg> <?php esc_html_e('تبریک! این آزمون را با موفقیت گذرانده‌اید.', 'evented-edu'); ?>
								<?php else : ?>
									<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg> <?php esc_html_e('هنوز به حد نصاب قبولی نرسیده‌اید.', 'evented-edu'); ?>
								<?php endif; ?>
							</h2>
							<p>
								<?php
								echo esc_html(sprintf(
									/* translators: 1: تعداد تلاش، 2: تلاش باقی‌مانده */
									$ee_max_try ? __('%1$s بار شرکت کرده‌اید؛ %2$s تلاش دیگر باقی مانده است.', 'evented-edu') : __('%1$s بار شرکت کرده‌اید؛ تلاش‌های شما نامحدود است.', 'evented-edu'),
									$ee_fa($ee_used),
									$ee_fa($ee_left)
								));
								?>
							</p>
							<ul class="ee-qz-history">
								<?php foreach (array_slice($ee_attempts, 0, 5) as $i => $a) : ?>
									<li class="<?php echo $a['pass'] ? 'ok' : 'no'; ?>" title="<?php echo esc_attr($a['time'] ? evented_format_jalali($a['time'], 'j F Y - H:i') : ''); ?>">
										<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#<?php echo $a['pass'] ? 'i-check_circle' : 'i-close'; ?>"></use></svg>
										<span><?php echo esc_html($ee_fa((int) round($a['pct']))); ?>٪</span>
										<small><?php echo $a['time'] ? esc_html(function_exists('evented_time_ago') ? evented_time_ago($a['time']) : human_time_diff($a['time'])) : ''; ?></small>
									</li>
								<?php endforeach; ?>
							</ul>
							<?php if ($ee_cert_link) : ?>
								<a class="ee-btn ee-btn-primary ee-qz-cert" href="<?php echo esc_url($ee_cert_link); ?>" target="_blank" rel="noopener">
									<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-workspace_premium"></use></svg> <?php esc_html_e('دریافت گواهینامه', 'evented-edu'); ?>
								</a>
							<?php endif; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if (!$ee_logged_in) : ?>
					<div class="ee-qz-gate">
						<div class="ee-qz-gate-ic" aria-hidden="true"><svg class="ee-ic" focusable="false"><use href="#i-lock"></use></svg></div>
						<div class="ee-qz-gate-tx">
							<h2><?php esc_html_e('برای شرکت در آزمون وارد حساب کاربری شوید', 'evented-edu'); ?></h2>
							<p><?php esc_html_e('نتیجهٔ آزمون و گواهینامه فقط برای کاربران واردشده ثبت می‌شود.', 'evented-edu'); ?></p>
						</div>
						<a class="ee-btn ee-btn-primary" href="<?php echo esc_url(home_url('/login?redirect_to=' . rawurlencode((string) get_permalink($ee_quiz_id)))); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-login"></use></svg> <?php esc_html_e('ورود / ثبت‌نام', 'evented-edu'); ?></a>
					</div>
				<?php elseif (!$ee_has_access) : ?>
					<div class="ee-qz-gate is-buy">
						<div class="ee-qz-gate-ic" aria-hidden="true"><svg class="ee-ic" focusable="false"><use href="#i-shopping_cart"></use></svg></div>
						<div class="ee-qz-gate-tx">
							<h2><?php esc_html_e('این آزمون مخصوص دانشجویان دوره است', 'evented-edu'); ?></h2>
							<p><?php esc_html_e('برای شرکت در آزمون ابتدا در دوره ثبت‌نام کنید.', 'evented-edu'); ?></p>
						</div>
						<a class="ee-btn ee-btn-primary" href="<?php echo esc_url(get_permalink($ee_course_id)); ?>#ee-enroll"><?php esc_html_e('ثبت‌نام در دوره', 'evented-edu'); ?></a>
					</div>
				<?php endif; ?>

				<!-- راهنمای کوتاه -->
				<details class="ee-qz-guide"<?php echo (!$ee_used && $ee_logged_in) ? ' open' : ''; ?>>
					<summary><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg> <?php esc_html_e('نکات قبل از شروع', 'evented-edu'); ?><svg class="ee-ic chev" aria-hidden="true" focusable="false"><use href="#i-expand_more"></use></svg></summary>
					<ul>
						<?php if (!empty($ee_quiz['time_limit'])) : ?><li><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-timer"></use></svg><?php echo esc_html(sprintf(__('زمان آزمون از لحظهٔ شروع محاسبه می‌شود (%s دقیقه). با پایان زمان، پاسخ‌ها به‌صورت خودکار ثبت می‌شوند.', 'evented-edu'), $ee_fa((int) ceil($ee_quiz['time_limit'] / 60)))); ?></li><?php endif; ?>
						<li><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-check_circle"></use></svg><?php esc_html_e('پیش از فشردن «پایان آزمون» همهٔ سؤال‌ها را مرور کنید؛ می‌توانید بین سؤال‌ها جابه‌جا شوید.', 'evented-edu'); ?></li>
						<?php if ($ee_max_try) : ?><li><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-replay"></use></svg><?php echo esc_html(sprintf(__('حداکثر %s بار می‌توانید در این آزمون شرکت کنید.', 'evented-edu'), $ee_fa($ee_max_try))); ?></li><?php endif; ?>
						<?php if (!empty($ee_quiz['passing'])) : ?><li><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-trending_up"></use></svg><?php echo esc_html(sprintf(__('برای قبولی باید حداقل %s٪ امتیاز کسب کنید.', 'evented-edu'), $ee_fa($ee_quiz['passing']))); ?></li><?php endif; ?>
						<?php if (!empty($ee_quiz['certificate'])) : ?><li><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-workspace_premium"></use></svg><?php esc_html_e('پس از قبولی، گواهینامه در همین صفحه و در «پنل کاربری → گواهینامه‌ها» قابل دریافت است.', 'evented-edu'); ?></li><?php endif; ?>
						<li><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-warning"></use></svg><?php esc_html_e('در حین آزمون صفحه را رفرش نکنید یا نبندید.', 'evented-edu'); ?></li>
					</ul>
				</details>

				<!-- بدنهٔ آزمون -->
				<section class="ee-qz-engine" id="ee-quiz" aria-label="<?php esc_attr_e('بدنهٔ آزمون', 'evented-edu'); ?>">
					<div class="ee-qz-engine-head">
						<span class="ee-qz-engine-title"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-play_circle"></use></svg> <?php esc_html_e('شروع آزمون', 'evented-edu'); ?></span>
						<?php if ($ee_logged_in && $ee_max_try) : ?>
							<span class="ee-qz-engine-try"><?php echo esc_html(sprintf(__('تلاش %1$s از %2$s', 'evented-edu'), $ee_fa(min($ee_used + 1, $ee_max_try)), $ee_fa($ee_max_try))); ?></span>
						<?php endif; ?>
					</div>
					<div class="ee-quiz-body learndash">
						<?php if ('' !== trim($ee_quiz_body)) : ?>
							<?php echo $ee_quiz_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- خروجی لرن‌دش. ?>
						<?php else : ?>
							<div class="ee-quiz-empty">
								<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-help_center"></use></svg>
								<p><?php esc_html_e('محتوای این آزمون هنوز آماده نشده است.', 'evented-edu'); ?></p>
							</div>
						<?php endif; ?>
					</div>
				</section>

				<!-- ناوبری -->
				<?php $ee_adjacent = ($ee_course_id && function_exists('evented_adjacent_steps')) ? evented_adjacent_steps($ee_course_id, $ee_quiz_id) : array(); ?>
				<nav class="ee-qz-nav" aria-label="<?php esc_attr_e('ناوبری', 'evented-edu'); ?>">
					<?php if (!empty($ee_adjacent['prev']) && $ee_adjacent['prev'] instanceof WP_Post) : ?>
						<a class="ee-qz-nav-btn is-prev" href="<?php echo esc_url(get_permalink($ee_adjacent['prev'])); ?>">
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_forward"></use></svg>
							<span><small><?php esc_html_e('درس قبلی', 'evented-edu'); ?></small><b><?php echo esc_html(get_the_title($ee_adjacent['prev'])); ?></b></span>
						</a>
					<?php else : ?><span></span><?php endif; ?>
					<?php if ($ee_course_id) : ?>
						<a class="ee-qz-nav-btn is-course" href="<?php echo esc_url(get_permalink($ee_course_id)); ?>">
							<span><small><?php esc_html_e('بازگشت به', 'evented-edu'); ?></small><b><?php echo esc_html(get_the_title($ee_course_id)); ?></b></span>
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg>
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

			<aside class="ee-side ee-side-quiz">
				<?php if ($ee_course_id) : ?>
					<a class="ee-qz-course" href="<?php echo esc_url(get_permalink($ee_course_id)); ?>">
						<span class="ee-qz-course-th"><?php if (has_post_thumbnail($ee_course_id)) : ?><img src="<?php echo esc_url(get_the_post_thumbnail_url($ee_course_id, 'medium')); ?>" alt="" loading="lazy"><?php else : ?><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-school"></use></svg><?php endif; ?></span>
						<span class="ee-qz-course-tx">
							<small><?php esc_html_e('این آزمون متعلق به دوره', 'evented-edu'); ?></small>
							<b><?php echo esc_html(get_the_title($ee_course_id)); ?></b>
							<?php if (!empty($ee_progress['total'])) : ?>
								<span class="ee-qz-course-bar"><i style="width:<?php echo (int) $ee_progress['percentage']; ?>%"></i></span>
								<em><?php echo esc_html($ee_fa((int) $ee_progress['percentage'])); ?>٪ <?php esc_html_e('پیشرفت دوره', 'evented-edu'); ?> · <?php echo esc_html($ee_fa((int) $ee_progress['completed'])); ?>/<?php echo esc_html($ee_fa((int) $ee_progress['total'])); ?></em>
							<?php endif; ?>
						</span>
					</a>
				<?php endif; ?>
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
