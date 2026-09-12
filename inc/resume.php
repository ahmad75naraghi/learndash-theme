<?php
/**
 * ادامهٔ یادگیری — «آخرین درسی که دیدید» + یادآور کاربران غیرفعال
 *
 * - هر بازدید از درس/تاپیک/آزمون یک دوره (کاربر لاگین‌شده) در متای کاربر `_ee_last_lesson` ذخیره می‌شود
 *   (course_id, step_id, time). فقط وقتی کاربر به دوره دسترسی دارد.
 * - چیپ «ادامهٔ یادگیری» در هدر (کنار زنگوله) و کشوی موبایل → `evented_resume_chip_html()`.
 * - کارت «ادامه دهید» در پیشخوان پنل → `evented_resume_card_html()`.
 * - کرون روزانه `evented_resume_reminder`: کاربرانی که ≥N روز (پیش‌فرض ۷) هیچ درسی ندیده‌اند
 *   و دورهٔ ناتمام دارند، یک اعلان (و در صورت فعال بودن، پیامک) می‌گیرند؛ حداکثر یک یادآور در هر N روز.
 *
 * تنظیمات: تب «اعلان‌ها» → resume_reminder_enabled, resume_reminder_days, resume_reminder_sms
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

const EVENTED_RESUME_META      = '_ee_last_lesson';
const EVENTED_RESUME_SENT_META = '_ee_resume_reminded_at';

if (!function_exists('evented_fa_num')) {
	/** تبدیل ارقام به فارسی (مستقل از locale). */
	function evented_fa_num($n)
	{
		return strtr((string) $n, array('0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹'));
	}
}

/* ------------------------------------------------------------------ */
/* تنظیمات                                                              */
/* ------------------------------------------------------------------ */

add_filter('evented_options_schema', static function ($schema) {
	if (isset($schema['notify']['fields'])) {
		$schema['notify']['fields']['resume_reminder_enabled'] = array('label' => 'یادآور «ادامهٔ دوره» برای کاربران غیرفعال', 'type' => 'checkbox', 'default' => 1);
		$schema['notify']['fields']['resume_reminder_days']    = array('label' => 'پس از چند روز بی‌فعالیتی؟', 'type' => 'number', 'default' => 7, 'min' => 2, 'max' => 60);
		$schema['notify']['fields']['resume_reminder_sms']     = array('label' => 'یادآور با پیامک هم ارسال شود', 'type' => 'checkbox', 'default' => 0, 'desc' => 'نیازمند فعال بودن «پیامک اعلان‌ها» و الگوی اعلان.');
	}
	return $schema;
});

/* ------------------------------------------------------------------ */
/* ثبت آخرین گام                                                        */
/* ------------------------------------------------------------------ */

/**
 * شناسهٔ دورهٔ یک گام (درس/تاپیک/آزمون).
 */
function evented_resume_course_of($post_id)
{
	$post_id = (int) $post_id;
	if (function_exists('learndash_get_course_id')) {
		$cid = (int) learndash_get_course_id($post_id);
		if ($cid) {
			return $cid;
		}
	}
	$cid = (int) get_post_meta($post_id, 'course_id', true);
	return $cid;
}

/**
 * ذخیرهٔ «آخرین گام دیده‌شده» در بازدید از درس/تاپیک/آزمون.
 */
add_action('template_redirect', static function () {
	if (!is_user_logged_in() || !is_singular(array('sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'))) {
		return;
	}
	$step_id   = get_queried_object_id();
	$course_id = evented_resume_course_of($step_id);
	if (!$course_id) {
		return;
	}
	$uid = get_current_user_id();
	if (function_exists('sfwd_lms_has_access') && !sfwd_lms_has_access($course_id, $uid)) {
		return;
	}
	$prev = get_user_meta($uid, EVENTED_RESUME_META, true);
	// جلوگیری از نوشتن پیاپی برای همان گام در بازهٔ کوتاه
	if (is_array($prev) && (int) ($prev['step_id'] ?? 0) === $step_id && (time() - (int) ($prev['time'] ?? 0)) < 120) {
		return;
	}
	update_user_meta($uid, EVENTED_RESUME_META, array('course_id' => $course_id, 'step_id' => $step_id, 'time' => time()));
});

/**
 * پس از تکمیل یک گام هم زمان فعالیت را تازه کن (کاربر ممکن است با AJAX تکمیل کند).
 */
add_action('learndash_lesson_completed', static function ($data) {
	if (empty($data['user']) || empty($data['lesson']) || empty($data['course'])) {
		return;
	}
	update_user_meta((int) $data['user']->ID, EVENTED_RESUME_META, array('course_id' => (int) $data['course']->ID, 'step_id' => (int) $data['lesson']->ID, 'time' => time()));
});
add_action('learndash_topic_completed', static function ($data) {
	if (empty($data['user']) || empty($data['topic']) || empty($data['course'])) {
		return;
	}
	update_user_meta((int) $data['user']->ID, EVENTED_RESUME_META, array('course_id' => (int) $data['course']->ID, 'step_id' => (int) $data['topic']->ID, 'time' => time()));
});

/* ------------------------------------------------------------------ */
/* خواندن                                                               */
/* ------------------------------------------------------------------ */

/**
 * دادهٔ ادامهٔ یادگیری کاربر.
 *
 * @param int $user_id
 * @return array|null {course_id, course_title, course_url, step_id, step_title, step_url, next_url, time, progress, is_done}
 */
function evented_resume_get($user_id = 0)
{
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if (!$user_id) {
		return null;
	}
	static $cache = array();
	if (isset($cache[$user_id])) {
		return $cache[$user_id];
	}
	$cache[$user_id] = null;

	$m = get_user_meta($user_id, EVENTED_RESUME_META, true);
	if (!is_array($m) || empty($m['course_id'])) {
		return null;
	}
	$course = get_post((int) $m['course_id']);
	if (!$course || 'publish' !== $course->post_status) {
		return null;
	}
	if (function_exists('sfwd_lms_has_access') && !sfwd_lms_has_access($course->ID, $user_id)) {
		return null;
	}
	$step     = !empty($m['step_id']) ? get_post((int) $m['step_id']) : null;
	$step_ok  = $step && 'publish' === $step->post_status;
	$progress = function_exists('evented_course_progress') ? evented_course_progress($course->ID, $user_id) : array('percentage' => 0, 'completed' => 0, 'total' => 0);
	$is_done  = (int) $progress['percentage'] >= 100;

	// گام بعدی: اگر گام فعلی تکمیل شده، درس بعدی؛ وگرنه همان گام.
	$next_url = $step_ok ? get_permalink($step) : get_permalink($course);
	if ($step_ok && function_exists('learndash_is_lesson_complete') && function_exists('learndash_next_post_link')) {
		$done = 'sfwd-lessons' === $step->post_type ? learndash_is_lesson_complete($user_id, $step->ID, $course->ID)
			: (function_exists('learndash_is_topic_complete') && 'sfwd-topic' === $step->post_type ? learndash_is_topic_complete($user_id, $step->ID, $course->ID) : false);
		if ($done) {
			$n = learndash_next_post_link('', true, $step);
			if (is_string($n) && '' !== $n) {
				$next_url = $n;
			}
		}
	}

	$cache[$user_id] = array(
		'course_id'    => (int) $course->ID,
		'course_title' => get_the_title($course),
		'course_url'   => get_permalink($course),
		'thumb'        => has_post_thumbnail($course) ? (string) get_the_post_thumbnail_url($course, 'medium') : '',
		'step_id'      => $step_ok ? (int) $step->ID : 0,
		'step_title'   => $step_ok ? get_the_title($step) : '',
		'step_url'     => $step_ok ? get_permalink($step) : get_permalink($course),
		'next_url'     => $next_url,
		'time'         => (int) ($m['time'] ?? 0),
		'progress'     => $progress,
		'is_done'      => $is_done,
	);
	return $cache[$user_id];
}

/**
 * چیپ کوچک هدر: «ادامهٔ یادگیری ▸ عنوان درس».
 */
function evented_resume_chip_html($variant = 'header')
{
	$r = evented_resume_get();
	if (!$r || $r['is_done']) {
		return '';
	}
	$label = '' !== $r['step_title'] ? $r['step_title'] : $r['course_title'];
	$pct   = (int) $r['progress']['percentage'];
	ob_start();
	?>
	<a class="ee-resume ee-resume-<?php echo esc_attr($variant); ?>" href="<?php echo esc_url($r['next_url']); ?>" title="<?php echo esc_attr(sprintf('ادامهٔ دورهٔ «%s» — %s٪ پیشرفت', $r['course_title'], number_format_i18n($pct))); ?>">
		<span class="ee-resume-ic"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-play_circle"></use></svg></span>
		<span class="ee-resume-txt">
			<small>ادامهٔ یادگیری</small>
			<strong><?php echo esc_html($label); ?></strong>
		</span>
		<span class="ee-resume-ring" style="--p:<?php echo (int) $pct; ?>" aria-hidden="true"><i><?php echo esc_html(number_format_i18n($pct)); ?>٪</i></span>
	</a>
	<?php
	return (string) ob_get_clean();
}

/**
 * کارت «ادامه دهید» برای پیشخوان پنل.
 */
function evented_resume_card_html()
{
	$r = evented_resume_get();
	if (!$r) {
		return '';
	}
	$pct  = (int) $r['progress']['percentage'];
	$ago  = $r['time'] ? human_time_diff($r['time'], time()) . ' پیش' : '';
	ob_start();
	?>
	<section class="ee-resume-card<?php echo $r['is_done'] ? ' is-done' : ''; ?>" aria-label="ادامهٔ یادگیری">
		<div class="ee-resume-card-media">
			<?php if ($r['thumb']) : ?>
				<img src="<?php echo esc_url($r['thumb']); ?>" alt="" loading="lazy" decoding="async">
			<?php else : ?>
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-school"></use></svg>
			<?php endif; ?>
		</div>
		<div class="ee-resume-card-body">
			<span class="ee-resume-kicker"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-history"></use></svg> <?php echo $r['is_done'] ? 'آخرین دوره' : 'از همان‌جا که رها کردید'; ?><?php echo $ago ? ' · ' . esc_html($ago) : ''; ?></span>
			<h3><a href="<?php echo esc_url($r['course_url']); ?>"><?php echo esc_html($r['course_title']); ?></a></h3>
			<?php if ('' !== $r['step_title']) : ?>
				<p class="ee-resume-step"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-play_lesson"></use></svg> <?php echo esc_html($r['step_title']); ?></p>
			<?php endif; ?>
			<div class="ee-resume-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $pct; ?>"><i style="width:<?php echo (int) $pct; ?>%"></i></div>
			<div class="ee-resume-foot">
				<span><?php echo esc_html(sprintf('%s از %s درس · %s٪', number_format_i18n((int) $r['progress']['completed']), number_format_i18n((int) $r['progress']['total']), number_format_i18n($pct))); ?></span>
				<a class="ee-btn ee-btn-primary" href="<?php echo esc_url($r['is_done'] ? $r['course_url'] : $r['next_url']); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-<?php echo $r['is_done'] ? 'replay' : 'play_circle'; ?>"></use></svg> <?php echo $r['is_done'] ? 'مرور دوره' : 'ادامه بده'; ?></a>
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/* یادآور روزانه                                                        */
/* ------------------------------------------------------------------ */

add_action('init', static function () {
	if (!wp_next_scheduled('evented_resume_reminder')) {
		wp_schedule_event(time() + 2 * HOUR_IN_SECONDS, 'daily', 'evented_resume_reminder');
	}
});

add_action('evented_resume_reminder', 'evented_resume_reminder_run');

/**
 * اجرای یادآور (حداکثر ۳۰۰ کاربر در هر اجرا).
 *
 * @return int تعداد اعلان‌های ارسال‌شده
 */
function evented_resume_reminder_run()
{
	if (!function_exists('evented_opt') || 1 !== (int) evented_opt('resume_reminder_enabled', 1) || !function_exists('evented_notify')) {
		return 0;
	}
	$days   = max(2, (int) evented_opt('resume_reminder_days', 7));
	$sms    = 1 === (int) evented_opt('resume_reminder_sms', 0);
	$cutoff = time() - $days * DAY_IN_SECONDS;
	$sent   = 0;

	$users = get_users(array(
		'fields'     => 'ids',
		'number'     => 300,
		'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			'relation' => 'AND',
			array('key' => EVENTED_RESUME_META, 'compare' => 'EXISTS'),
			array(
				'relation' => 'OR',
				array('key' => EVENTED_RESUME_SENT_META, 'compare' => 'NOT EXISTS'),
				array('key' => EVENTED_RESUME_SENT_META, 'value' => $cutoff, 'compare' => '<', 'type' => 'NUMERIC'),
			),
		),
	));

	foreach ($users as $uid) {
		$m = get_user_meta($uid, EVENTED_RESUME_META, true);
		if (!is_array($m) || (int) ($m['time'] ?? 0) > $cutoff) {
			continue; // هنوز فعال است
		}
		$r = evented_resume_get($uid);
		if (!$r || $r['is_done']) {
			continue;
		}
		$pct   = (int) $r['progress']['percentage'];
		$title = sprintf('دورهٔ «%s» منتظر شماست', $r['course_title']);
		$next  = $r['next_url'] !== $r['step_url'] ? get_the_title(url_to_postid($r['next_url'])) : $r['step_title'];
		$body  = '' !== (string) $next
			? sprintf('%s روز است سراغ دوره نیامده‌اید. از «%s» ادامه دهید — %s٪ را رفته‌اید!', evented_fa_num($days), $next, evented_fa_num($pct))
			: sprintf('%s روز است سراغ دوره نیامده‌اید؛ همین حالا ادامه دهید.', evented_fa_num($days));
		$ok = evented_notify($uid, $title, $body, $r['next_url'], 'lesson', $sms, $r['course_id']);
		update_user_meta($uid, EVENTED_RESUME_SENT_META, time());
		if ($ok) {
			$sent++;
		}
	}
	return $sent;
}

/* ------------------------------------------------------------------ */
/* استایل                                                               */
/* ------------------------------------------------------------------ */

add_action('wp_enqueue_scripts', static function () {
	if (!is_user_logged_in()) {
		return;
	}
	wp_enqueue_style('ee-resume', PATH_DIR_URL . '/assets/css/newhome/ee-resume.css', array('ee-shell'), '1.0.0');
}, 26);
