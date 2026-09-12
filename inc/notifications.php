<?php
/**
 * اعلان‌های درون‌سایتی (زنگولهٔ هدر + پاپ‌آور) و پیامک اختیاری
 *
 * ذخیره‌سازی: جدول اختصاصی {prefix}evented_notifications
 * رویدادها:
 *   - انتشار درس/مبحث جدید در دوره → همهٔ دانشجویان دوره
 *   - پاسخ به دیدگاه کاربر → نویسندهٔ دیدگاه اصلی
 *   - تکمیل دوره / صدور گواهی → دانشجو
 *   - ثبت‌نام در دوره → دانشجو (خوش‌آمد)
 *   - API عمومی: evented_notify($user_id, $title, $body, $url, $type, $sms = false)
 * REST: GET /evented/v1/notifications, POST /evented/v1/notifications/read, POST …/read-all
 *
 * فایل‌های همراه: assets/js/newhome/ee-notify.js, assets/css/newhome/ee-notify.css
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

define('EVENTED_NOTIFY_DB_VERSION', '1');

/**
 * نام جدول.
 */
function evented_notify_table()
{
	global $wpdb;
	return $wpdb->prefix . 'evented_notifications';
}

/**
 * ساخت/به‌روزرسانی جدول.
 */
function evented_notify_install()
{
	global $wpdb;
	if (get_option('evented_notify_db_version') === EVENTED_NOTIFY_DB_VERSION) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset = $wpdb->get_charset_collate();
	$table   = evented_notify_table();
	dbDelta("CREATE TABLE {$table} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		type VARCHAR(40) NOT NULL DEFAULT 'info',
		title VARCHAR(190) NOT NULL,
		body TEXT NULL,
		url VARCHAR(500) NULL,
		icon VARCHAR(40) NULL,
		object_id BIGINT UNSIGNED NULL,
		is_read TINYINT(1) NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL,
		read_at DATETIME NULL,
		PRIMARY KEY  (id),
		KEY user_read (user_id, is_read, created_at),
		KEY object_type (object_id, type)
	) {$charset};");
	update_option('evented_notify_db_version', EVENTED_NOTIFY_DB_VERSION, false);
}
add_action('after_switch_theme', 'evented_notify_install');
add_action('admin_init', 'evented_notify_install');

/**
 * آیا سامانهٔ اعلان فعال است؟
 */
function evented_notify_enabled()
{
	return function_exists('evented_opt') ? 1 === (int) evented_opt('notify_enabled', 1) : true;
}

/**
 * ثبت اعلان برای یک یا چند کاربر.
 *
 * @param int|int[] $user_ids
 * @param string    $title
 * @param string    $body
 * @param string    $url
 * @param string    $type   کلید نوع: lesson|comment|certificate|course|info|warning
 * @param bool      $sms    ارسال پیامک (در صورت فعال بودن در تنظیمات و وجود الگو)
 * @param int       $object_id
 * @return int تعداد اعلان‌های ثبت‌شده
 */
function evented_notify($user_ids, $title, $body = '', $url = '', $type = 'info', $sms = false, $object_id = 0)
{
	if (!evented_notify_enabled()) {
		return 0;
	}
	global $wpdb;
	$table    = evented_notify_table();
	$user_ids = array_values(array_unique(array_filter(array_map('intval', (array) $user_ids))));
	if (empty($user_ids)) {
		return 0;
	}
	$icons = array('lesson' => 'play_lesson', 'comment' => 'forum', 'certificate' => 'workspace_premium', 'course' => 'school', 'warning' => 'warning', 'info' => 'notifications');
	$icon  = isset($icons[$type]) ? $icons[$type] : 'notifications';
	$now   = current_time('mysql');
	$title = wp_strip_all_tags((string) $title);
	$body  = wp_strip_all_tags((string) $body);
	$n     = 0;

	foreach ($user_ids as $uid) {
		// جلوگیری از اعلان تکراری برای یک شیء/نوع/کاربر در ۱ ساعت
		if ($object_id) {
			$dup = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE user_id = %d AND type = %s AND object_id = %d AND created_at > %s LIMIT 1", $uid, $type, $object_id, gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS))); // phpcs:ignore
			if ($dup) {
				continue;
			}
		}
		$ok = $wpdb->insert($table, array(
			'user_id'    => $uid,
			'type'       => sanitize_key($type),
			'title'      => mb_substr($title, 0, 190),
			'body'       => $body,
			'url'        => esc_url_raw((string) $url),
			'icon'       => $icon,
			'object_id'  => (int) $object_id,
			'is_read'    => 0,
			'created_at' => $now,
		), array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s'));
		if ($ok) {
			$n++;
			wp_cache_delete('ee_notify_unread_' . $uid, 'evented');
			if ($sms) {
				evented_notify_sms($uid, $title, $url);
			}
		}
	}

	/**
	 * پس از ثبت اعلان.
	 */
	do_action('evented_notified', $user_ids, $title, $body, $url, $type);
	return $n;
}

/**
 * پیامک اعلان (اختیاری).
 */
function evented_notify_sms($user_id, $title, $url = '')
{
	if (!function_exists('evented_opt') || 1 !== (int) evented_opt('notify_sms', 0)) {
		return false;
	}
	$body_id = (int) evented_opt('sms_notify_body_id', 0);
	if (!$body_id || !function_exists('send_pattern_sms')) {
		return false;
	}
	$user = get_userdata((int) $user_id);
	if (!$user) {
		return false;
	}
	$mobile = (string) get_user_meta($user->ID, 'mobile', true);
	if ('' === $mobile && preg_match('/^09\d{9}$/', (string) $user->user_login)) {
		$mobile = (string) $user->user_login;
	}
	if ('' === $mobile) {
		return false;
	}
	// جلوگیری از سیل پیامک: حداکثر ۳ پیامک اعلان در روز برای هر کاربر
	$k = 'ee_nsms_' . $user->ID;
	$c = (int) get_transient($k);
	if ($c >= 3) {
		return false;
	}
	set_transient($k, $c + 1, DAY_IN_SECONDS);
	return send_pattern_sms($mobile, array($title, '' !== $url ? wp_make_link_relative($url) : ''), $body_id);
}

/**
 * تعداد خوانده‌نشده‌های کاربر.
 */
function evented_notify_unread_count($user_id = 0)
{
	global $wpdb;
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if (!$user_id) {
		return 0;
	}
	$c = wp_cache_get('ee_notify_unread_' . $user_id, 'evented');
	if (false === $c) {
		$c = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . evented_notify_table() . ' WHERE user_id = %d AND is_read = 0', $user_id)); // phpcs:ignore
		wp_cache_set('ee_notify_unread_' . $user_id, $c, 'evented', 5 * MINUTE_IN_SECONDS);
	}
	return $c;
}

/**
 * فهرست اعلان‌های کاربر.
 */
function evented_notify_list($user_id, $limit = 15, $offset = 0)
{
	global $wpdb;
	$rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . evented_notify_table() . ' WHERE user_id = %d ORDER BY is_read ASC, created_at DESC LIMIT %d OFFSET %d', (int) $user_id, (int) $limit, (int) $offset)); // phpcs:ignore
	$out  = array();
	foreach ((array) $rows as $r) {
		$ts    = strtotime($r->created_at);
		$out[] = array(
			'id'      => (int) $r->id,
			'type'    => $r->type,
			'icon'    => $r->icon ?: 'notifications',
			'title'   => $r->title,
			'body'    => (string) $r->body,
			'url'     => (string) $r->url,
			'is_read' => (bool) $r->is_read,
			'time'    => function_exists('evented_format_jalali') ? evented_format_jalali($ts, 'j F') : date_i18n('j F', $ts),
			'ago'     => human_time_diff($ts, current_time('timestamp')) . ' پیش',
		);
	}
	return $out;
}

/* ------------------------------------------------------------------ */
/* REST                                                                */
/* ------------------------------------------------------------------ */
add_action('rest_api_init', static function () {
	$auth = static function () {
		return is_user_logged_in();
	};
	register_rest_route('evented/v1', '/notifications', array(
		'methods'             => WP_REST_Server::READABLE,
		'permission_callback' => $auth,
		'callback'            => static function (WP_REST_Request $r) {
			$uid = get_current_user_id();
			return rest_ensure_response(array(
				'unread' => evented_notify_unread_count($uid),
				'items'  => evented_notify_list($uid, min(30, max(1, (int) $r->get_param('limit') ?: 15))),
			));
		},
	));
	register_rest_route('evented/v1', '/notifications/read', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'permission_callback' => $auth,
		'callback'            => static function (WP_REST_Request $r) {
			global $wpdb;
			$uid = get_current_user_id();
			$id  = (int) $r->get_param('id');
			if ($id) {
				$wpdb->update(evented_notify_table(), array('is_read' => 1, 'read_at' => current_time('mysql')), array('id' => $id, 'user_id' => $uid), array('%d', '%s'), array('%d', '%d'));
			}
			wp_cache_delete('ee_notify_unread_' . $uid, 'evented');
			return rest_ensure_response(array('ok' => true, 'unread' => evented_notify_unread_count($uid)));
		},
	));
	register_rest_route('evented/v1', '/notifications/read-all', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'permission_callback' => $auth,
		'callback'            => static function () {
			global $wpdb;
			$uid = get_current_user_id();
			$wpdb->update(evented_notify_table(), array('is_read' => 1, 'read_at' => current_time('mysql')), array('user_id' => $uid, 'is_read' => 0), array('%d', '%s'), array('%d', '%d'));
			wp_cache_delete('ee_notify_unread_' . $uid, 'evented');
			return rest_ensure_response(array('ok' => true, 'unread' => 0));
		},
	));
	register_rest_route('evented/v1', '/notifications/clear', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'permission_callback' => $auth,
		'callback'            => static function () {
			global $wpdb;
			$uid = get_current_user_id();
			$wpdb->delete(evented_notify_table(), array('user_id' => $uid), array('%d'));
			wp_cache_delete('ee_notify_unread_' . $uid, 'evented');
			return rest_ensure_response(array('ok' => true, 'unread' => 0));
		},
	));
});

/* ------------------------------------------------------------------ */
/* دارایی‌ها + زنگوله                                                   */
/* ------------------------------------------------------------------ */
add_action('wp_enqueue_scripts', static function () {
	if (!is_user_logged_in() || !evented_notify_enabled()) {
		return;
	}
	if (!function_exists('evented_is_ee_view') || !evented_is_ee_view()) {
		return;
	}
	wp_enqueue_style('ee-notify', PATH_DIR_URL . '/assets/css/newhome/ee-notify.css', array('ee-shell'), '1.0.0');
	wp_enqueue_script('ee-notify', PATH_DIR_URL . '/assets/js/newhome/ee-notify.js', array(), '1.0.0', true);
	wp_localize_script('ee-notify', 'eeNotify', array(
		'endpoint' => esc_url_raw(rest_url('evented/v1/notifications')),
		'nonce'    => wp_create_nonce('wp_rest'),
		'poll'     => (int) apply_filters('evented_notify_poll_seconds', 90),
		'i18n'     => array(
			'title'    => 'اعلان‌ها',
			'empty'    => 'اعلان جدیدی ندارید.',
			'emptySub' => 'وقتی درس تازه‌ای منتشر شود یا کسی به دیدگاه شما پاسخ دهد، اینجا خبرش می‌رسد.',
			'readAll'  => 'همه را خواندم',
			'clear'    => 'پاک‌کردن همه',
			'loading'  => 'در حال دریافت…',
			'error'    => 'خطا در دریافت اعلان‌ها',
			'new'      => 'جدید',
		),
	));
}, 22);

/**
 * دکمهٔ زنگوله برای هدر.
 */
function evented_notify_bell_html()
{
	if (!is_user_logged_in() || !evented_notify_enabled()) {
		return '';
	}
	$n = evented_notify_unread_count();
	ob_start();
	?>
	<div class="ee-nb" data-ee-notify>
		<button type="button" class="ee-btn ee-btn-soft ee-ic ee-nb-btn" id="eeNotifyBtn" aria-label="اعلان‌ها" aria-haspopup="dialog" aria-expanded="false" aria-controls="eeNotifyPop">
			<?php echo ee_icon($n ? 'notifications_active' : 'notifications'); // phpcs:ignore ?>
			<span class="ee-nb-badge"<?php echo $n ? '' : ' hidden'; ?> data-ee-notify-count><?php echo esc_html($n > 99 ? '۹۹+' : number_format_i18n($n)); ?></span>
		</button>
		<div class="ee-nb-pop" id="eeNotifyPop" role="dialog" aria-label="اعلان‌ها" hidden></div>
	</div>
	<?php
	return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/* رویدادها                                                             */
/* ------------------------------------------------------------------ */

/**
 * دانشجویان یک دوره.
 */
function evented_notify_course_students($course_id)
{
	if (!function_exists('learndash_get_users_for_course')) {
		return array();
	}
	$q = learndash_get_users_for_course((int) $course_id, array('fields' => 'ID'), false);
	if ($q instanceof WP_User_Query) {
		return array_map('intval', (array) $q->get_results());
	}
	return is_array($q) ? array_map('intval', $q) : array();
}

/**
 * درس/مبحث جدید → دانشجویان دوره.
 */
add_action('transition_post_status', static function ($new, $old, $post) {
	if ('publish' !== $new || 'publish' === $old || !$post instanceof WP_Post) {
		return;
	}
	if (!in_array($post->post_type, array('sfwd-lessons', 'sfwd-topic'), true)) {
		return;
	}
	if (function_exists('evented_opt') && 1 !== (int) evented_opt('notify_new_lesson', 1)) {
		return;
	}
	$course_id = function_exists('learndash_get_course_id') ? (int) learndash_get_course_id($post->ID) : 0;
	if (!$course_id) {
		return;
	}
	$students = evented_notify_course_students($course_id);
	if (empty($students)) {
		return;
	}
	evented_notify(
		$students,
		sprintf('درس جدید در «%s»', get_the_title($course_id)),
		get_the_title($post),
		get_permalink($post),
		'lesson',
		false,
		$post->ID
	);
}, 10, 3);

/**
 * پاسخ به دیدگاه → نویسندهٔ دیدگاه والد.
 */
add_action('wp_insert_comment', static function ($id, $comment) {
	if (!$comment instanceof WP_Comment || !$comment->comment_parent || '1' !== (string) $comment->comment_approved) {
		return;
	}
	evented_notify_comment_reply($comment);
}, 10, 2);
add_action('transition_comment_status', static function ($new, $old, $comment) {
	if ('approved' === $new && 'approved' !== $old && $comment instanceof WP_Comment && $comment->comment_parent) {
		evented_notify_comment_reply($comment);
	}
}, 10, 3);
function evented_notify_comment_reply(WP_Comment $comment)
{
	if (function_exists('evented_opt') && 1 !== (int) evented_opt('notify_comment', 1)) {
		return;
	}
	$parent = get_comment($comment->comment_parent);
	if (!$parent || !(int) $parent->user_id || (int) $parent->user_id === (int) $comment->user_id) {
		return;
	}
	evented_notify(
		(int) $parent->user_id,
		sprintf('%s به دیدگاه شما پاسخ داد', $comment->comment_author ?: 'کاربر'),
		wp_trim_words(wp_strip_all_tags($comment->comment_content), 18),
		get_comment_link($comment),
		'comment',
		false,
		(int) $comment->comment_ID
	);
}

/**
 * تکمیل دوره → گواهی/تبریک.
 */
add_action('learndash_course_completed', static function ($data) {
	if (empty($data['user']) || empty($data['course'])) {
		return;
	}
	$uid    = (int) $data['user']->ID;
	$course = $data['course'];
	$cid    = (int) $course->ID;
	$cert   = function_exists('learndash_get_course_certificate_link') ? (string) learndash_get_course_certificate_link($cid, $uid) : '';
	evented_notify(
		$uid,
		'' !== $cert ? 'گواهی پایان دوره آمادهٔ دریافت است' : sprintf('تبریک! دورهٔ «%s» را تمام کردید', get_the_title($cid)),
		'' !== $cert ? sprintf('گواهی دورهٔ «%s» صادر شد.', get_the_title($cid)) : 'برای مشاهدهٔ دوره‌های پیشنهادی به پنل سر بزنید.',
		'' !== $cert ? home_url('/panel/certificates') : get_permalink($cid),
		'certificate',
		true,
		$cid
	);
});

/**
 * ثبت‌نام در دوره → خوش‌آمد.
 */
add_action('learndash_update_course_access', static function ($user_id, $course_id, $access_list, $remove) {
	if ($remove) {
		return;
	}
	evented_notify(
		(int) $user_id,
		sprintf('به دورهٔ «%s» خوش آمدید', get_the_title($course_id)),
		'دسترسی شما فعال شد؛ اولین درس را شروع کنید.',
		get_permalink($course_id),
		'course',
		false,
		(int) $course_id
	);
}, 10, 4);

/**
 * پاک‌سازی اعلان‌های قدیمی (روزانه).
 */
add_action('init', static function () {
	if (!wp_next_scheduled('evented_notify_cleanup')) {
		wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'evented_notify_cleanup');
	}
});
add_action('evented_notify_cleanup', static function () {
	global $wpdb;
	$days = function_exists('evented_opt') ? max(7, (int) evented_opt('notify_keep_days', 90)) : 90;
	$wpdb->query($wpdb->prepare('DELETE FROM ' . evented_notify_table() . ' WHERE created_at < %s', gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS))); // phpcs:ignore
});

/**
 * ابزار مدیر: ارسال اعلان دستی از پیشخوان (Appearance → تنظیمات قالب → اعلان‌ها) از طریق admin-post.
 */
add_action('admin_post_evented_notify_broadcast', static function () {
	if (!current_user_can('manage_options') || !check_admin_referer('evented_notify_broadcast')) {
		wp_die('دسترسی غیرمجاز');
	}
	$title  = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
	$body   = sanitize_textarea_field(wp_unslash($_POST['body'] ?? ''));
	$url    = esc_url_raw(wp_unslash($_POST['url'] ?? ''));
	$target = sanitize_key($_POST['target'] ?? 'all');
	$sms    = !empty($_POST['sms']);
	$course = (int) ($_POST['course_id'] ?? 0);
	if ('' === $title) {
		wp_safe_redirect(add_query_arg(array('page' => 'evented-theme-settings', 'tab' => 'notify', 'ee_msg' => 'empty'), admin_url('themes.php')));
		exit;
	}
	$users = array();
	if ('course' === $target && $course) {
		$users = evented_notify_course_students($course);
	} else {
		$users = get_users(array('fields' => 'ID', 'number' => 5000));
	}
	$n = evented_notify($users, $title, $body, $url, 'info', $sms);
	wp_safe_redirect(add_query_arg(array('page' => 'evented-theme-settings', 'tab' => 'notify', 'ee_msg' => 'sent', 'n' => $n), admin_url('themes.php')));
	exit;
});

/**
 * بازسازی قوانین بازنویسی (Permalink) هنگام فعال‌سازی قالب — تا آدرس نوشته‌ها/صفحه‌ها بلافاصله درست کار کند.
 */
add_action('after_switch_theme', 'flush_rewrite_rules', 20);
