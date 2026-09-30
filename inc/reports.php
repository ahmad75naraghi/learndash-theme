<?php
/**
 * مرکز گزارش‌گیری بهینهٔ دوره‌ها و آزمون‌های LearnDash.
 *
 * جایگزین داخلی افزونهٔ قدیمی report_courses؛ بدون CDN و کوئری N+1.
 *
 * @package evented-edu
 */
defined('ABSPATH') || exit;

const EVENTED_REPORTS_CAPABILITY = 'manage_options';
const EVENTED_REPORTS_PAGE_SIZE  = 50;

/** آیا جدول فعالیت LearnDash در دیتابیس موجود است؟ */
function evented_reports_tables_exist()
{
	static $exists = null;
	if (null !== $exists) { return $exists; }
	global $wpdb;
	$table = $wpdb->prefix . 'learndash_user_activity';
	$meta  = $wpdb->prefix . 'learndash_user_activity_meta';
	$exists = $table === $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table))
		&& $meta === $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $meta));
	return $exists;
}

/** ثبت منوی یکپارچهٔ گزارش‌گیری. */
function evented_reports_admin_menu()
{
	add_menu_page('گزارش‌گیری', 'گزارش‌گیری', EVENTED_REPORTS_CAPABILITY, 'evented-reports', 'evented_reports_overview_page', 'dashicons-chart-bar', 24);
	add_submenu_page('evented-reports', 'نمای کلی', 'نمای کلی', EVENTED_REPORTS_CAPABILITY, 'evented-reports', 'evented_reports_overview_page');
	add_submenu_page('evented-reports', 'تکمیل دوره‌ها', 'تکمیل دوره‌ها', EVENTED_REPORTS_CAPABILITY, 'evented-reports-courses', 'evented_reports_courses_page');
	add_submenu_page('evented-reports', 'نتایج آزمون‌ها', 'نتایج آزمون‌ها', EVENTED_REPORTS_CAPABILITY, 'evented-reports-quizzes', 'evented_reports_quizzes_page');
}
add_action('admin_menu', 'evented_reports_admin_menu');

/** در دورهٔ انتقال، hookهای افزونهٔ قدیمی را خنثی می‌کند تا منو/IP تکراری نشوند. */
function evented_reports_disable_legacy_hooks()
{
	remove_action('admin_menu', 'report_courses_menu');
	remove_action('learndash_quiz_completed', 'save_user_ip_in_learndash_activity_meta', 10);
	remove_shortcode('update_meta_value');
	add_shortcode('update_meta_value', 'evented_reports_card_number_shortcode');
}
add_action('after_setup_theme', 'evented_reports_disable_legacy_hooks', 100);

/** assetها فقط در صفحات گزارش. */
function evented_reports_admin_assets($hook)
{
	if (false === strpos((string) $hook, 'evented-reports')) { return; }
	wp_enqueue_style('evented-reports', PATH_DIR_URL . '/assets/css/admin/reports.css', array(), '1.0.0');
	wp_enqueue_script('evented-reports', PATH_DIR_URL . '/assets/js/admin/reports.js', array(), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'evented_reports_admin_assets');

/** تبدیل timestamp فعالیت به تاریخ سایت. */
function evented_reports_date($timestamp, $with_time = true)
{
	$timestamp = (int) $timestamp;
	if ($timestamp < 1) { return '—'; }
	$format = $with_time ? 'Y/m/d H:i' : 'Y/m/d';
	return function_exists('wp_date') ? wp_date($format, $timestamp) : date_i18n($format, $timestamp);
}

/** جلوگیری از اجرای فرمول هنگام بازشدن CSV در Excel. */
function evented_reports_export_cell($value)
{
	$value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $value);
	return preg_match('/^[=+\-@]/u', $value) ? "'" . $value : $value;
}

/** فیلترهای مشترک و امن. */
function evented_reports_filters($type)
{
	$source = wp_unslash($_GET); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط فیلتر خواندنی مدیریت.
	$get = static function ($key) use ($source) { return isset($source[$key]) && is_scalar($source[$key]) ? (string) $source[$key] : ''; };
	$per_page = absint($get('per_page')) ?: EVENTED_REPORTS_PAGE_SIZE;
	if (!in_array($per_page, array(25, 50, 100, 200), true)) { $per_page = EVENTED_REPORTS_PAGE_SIZE; }
	$date_from = $get('date_from'); $date_to = $get('date_to'); $passed = $get('passed');
	return array(
		'search'    => sanitize_text_field($get('s')),
		'course_id' => absint($get('course_id')),
		'quiz_id'   => 'quiz' === $type ? absint($get('quiz_id')) : 0,
		'date_from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) ? $date_from : '',
		'date_to'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to) ? $date_to : '',
		'passed'    => 'quiz' === $type && in_array($passed, array('yes', 'no'), true) ? $passed : '',
		'per_page'  => $per_page,
		'paged'     => max(1, absint($get('paged'))),
	);
}

/** شرط‌های SQL مشترک؛ همهٔ مقادیر از prepare عبور می‌کنند. */
function evented_reports_where($type, $filters, &$params)
{
	global $wpdb;
	$where = array('a.activity_type = %s');
	$params[] = $type;
	if ('course' === $type) { $where[] = 'a.activity_completed > 0'; }
	if (!empty($filters['course_id'])) { $where[] = ('course' === $type ? 'a.post_id' : 'a.course_id') . ' = %d'; $params[] = $filters['course_id']; }
	if ('quiz' === $type && !empty($filters['quiz_id'])) { $where[] = 'a.post_id = %d'; $params[] = $filters['quiz_id']; }
	$date_column = 'quiz' === $type ? 'COALESCE(NULLIF(a.activity_completed,0),a.activity_updated)' : 'a.activity_completed';
	if (!empty($filters['date_from'])) { $where[] = $date_column . ' >= %d'; $params[] = strtotime($filters['date_from'] . ' 00:00:00'); }
	if (!empty($filters['date_to'])) { $where[] = $date_column . ' <= %d'; $params[] = strtotime($filters['date_to'] . ' 23:59:59'); }
	if (!empty($filters['search'])) {
		$like = '%' . $wpdb->esc_like($filters['search']) . '%';
		$where[] = '(u.user_login LIKE %s OR u.display_name LIKE %s OR p.post_title LIKE %s)';
		array_push($params, $like, $like, $like);
	}
	return implode(' AND ', $where);
}

/** گزارش تکمیل دوره با query سبک و cache گروهی user meta. */
function evented_reports_course_rows($filters, $limit = null, $offset = 0)
{
	global $wpdb;
	$a = $wpdb->prefix . 'learndash_user_activity';
	$params = array();
	$where = evented_reports_where('course', $filters, $params);
	$sql = "SELECT a.activity_id,a.user_id,a.post_id AS course_id,a.activity_started,a.activity_completed,u.user_login,u.display_name,p.post_title AS course_title
		FROM {$a} a INNER JOIN {$wpdb->users} u ON u.ID=a.user_id INNER JOIN {$wpdb->posts} p ON p.ID=a.post_id
		WHERE {$where} ORDER BY a.activity_completed DESC,a.activity_id DESC";
	if (null !== $limit) { $sql .= ' LIMIT %d OFFSET %d'; $params[] = (int) $limit; $params[] = (int) $offset; }
	$rows = $wpdb->get_results($wpdb->prepare($sql, $params));
	if ($rows) { update_meta_cache('user', array_values(array_unique(wp_list_pluck($rows, 'user_id')))); }
	return (array) $rows;
}

/** تعداد ردیف‌های تکمیل دوره. */
function evented_reports_course_count($filters)
{
	global $wpdb;
	$a = $wpdb->prefix . 'learndash_user_activity';
	$params = array(); $where = evented_reports_where('course', $filters, $params);
	$sql = "SELECT COUNT(*) FROM {$a} a INNER JOIN {$wpdb->users} u ON u.ID=a.user_id INNER JOIN {$wpdb->posts} p ON p.ID=a.post_id WHERE {$where}";
	return (int) $wpdb->get_var($wpdb->prepare($sql, $params));
}

/** نتایج آزمون با تجمیع یک‌جای meta و سازگاری IPهای افزونهٔ قدیمی. */
function evented_reports_quiz_rows($filters, $limit = null, $offset = 0)
{
	global $wpdb;
	$a = $wpdb->prefix . 'learndash_user_activity'; $m = $wpdb->prefix . 'learndash_user_activity_meta';
	$params = array(); $where = evented_reports_where('quiz', $filters, $params);
	$sql = "SELECT a.activity_id,a.user_id,a.post_id AS quiz_id,a.course_id,a.activity_started,a.activity_completed,a.activity_updated,
		u.user_login,u.display_name,p.post_title AS quiz_title,c.post_title AS course_title,
		MAX(CASE WHEN am.activity_meta_key='points' THEN am.activity_meta_value END) AS points,
		MAX(CASE WHEN am.activity_meta_key='total_points' THEN am.activity_meta_value END) AS total_points,
		MAX(CASE WHEN am.activity_meta_key='percentage' THEN am.activity_meta_value END) AS percentage,
		MAX(CASE WHEN am.activity_meta_key='pass' THEN am.activity_meta_value END) AS passed,
		MAX(CASE WHEN am.activity_meta_key='graded' THEN am.activity_meta_value END) AS graded,
		COALESCE(MAX(CASE WHEN am.activity_meta_key='user_ip' THEN am.activity_meta_value END),MAX(oldip.activity_meta_value)) AS user_ip
		FROM {$a} a INNER JOIN {$wpdb->users} u ON u.ID=a.user_id INNER JOIN {$wpdb->posts} p ON p.ID=a.post_id
		LEFT JOIN {$wpdb->posts} c ON c.ID=a.course_id LEFT JOIN {$m} am ON am.activity_id=a.activity_id
		LEFT JOIN {$m} oldip ON oldip.activity_id=CAST(CONCAT(a.user_id,a.activity_updated) AS UNSIGNED) AND oldip.activity_meta_key='user_ip'
		WHERE {$where} GROUP BY a.activity_id";
	if (!empty($filters['passed'])) { $sql .= ' HAVING ' . ('yes' === $filters['passed'] ? "passed IN ('1','true','yes')" : "COALESCE(passed,'0') NOT IN ('1','true','yes')"); }
	$sql .= ' ORDER BY COALESCE(NULLIF(a.activity_completed,0),a.activity_updated) DESC,a.activity_id DESC';
	if (null !== $limit) { $sql .= ' LIMIT %d OFFSET %d'; $params[] = (int) $limit; $params[] = (int) $offset; }
	$rows = $wpdb->get_results($wpdb->prepare($sql, $params));
	if ($rows) { update_meta_cache('user', array_values(array_unique(wp_list_pluck($rows, 'user_id')))); }
	return (array) $rows;
}

/** تعداد نتایج آزمون؛ فیلتر قبولی روی مجموعهٔ تجمیع‌شده اعمال می‌شود. */
function evented_reports_quiz_count($filters)
{
	global $wpdb;
	$a = $wpdb->prefix . 'learndash_user_activity'; $m = $wpdb->prefix . 'learndash_user_activity_meta';
	$params = array(); $where = evented_reports_where('quiz', $filters, $params);
	if (empty($filters['passed'])) {
		$sql = "SELECT COUNT(*) FROM {$a} a INNER JOIN {$wpdb->users} u ON u.ID=a.user_id INNER JOIN {$wpdb->posts} p ON p.ID=a.post_id WHERE {$where}";
		return (int) $wpdb->get_var($wpdb->prepare($sql, $params));
	}
	$sql = "SELECT COUNT(*) FROM (SELECT a.activity_id,MAX(CASE WHEN am.activity_meta_key='pass' THEN am.activity_meta_value END) passed
		FROM {$a} a INNER JOIN {$wpdb->users} u ON u.ID=a.user_id INNER JOIN {$wpdb->posts} p ON p.ID=a.post_id LEFT JOIN {$m} am ON am.activity_id=a.activity_id
		WHERE {$where} GROUP BY a.activity_id";
	$sql .= ' HAVING ' . ('yes' === $filters['passed'] ? "passed IN ('1','true','yes')" : "COALESCE(passed,'0') NOT IN ('1','true','yes')");
	$sql .= ') report_rows';
	return (int) $wpdb->get_var($wpdb->prepare($sql, $params));
}

/** مقدار امن user meta با fallback شمارهٔ موبایل. */
function evented_reports_user_data($user_id)
{
	$phone = get_user_meta($user_id, 'digits_phone_no', true);
	if (!$phone) { $phone = get_user_meta($user_id, 'billing_phone', true); }
	return array(
		'first_name' => (string) get_user_meta($user_id, 'first_name', true), 'last_name' => (string) get_user_meta($user_id, 'last_name', true),
		'state' => (string) get_user_meta($user_id, 'billing_state', true), 'city' => (string) get_user_meta($user_id, 'billing_city', true),
		'phone' => (string) $phone, 'national' => (string) get_user_meta($user_id, 'national_code', true),
		'gender' => (string) get_user_meta($user_id, 'gender', true), 'birth' => (string) get_user_meta($user_id, 'birth', true),
		'card' => (string) get_user_meta($user_id, 'card_number', true),
	);
}

/** URL پاسخ تشریحی/آپلودشده از graded. */
function evented_reports_quiz_upload($serialized)
{
	$graded = maybe_unserialize($serialized);
	if (!is_array($graded)) { return ''; }
	foreach ($graded as $item) {
		if (!is_array($item) || empty($item['post_id'])) { continue; }
		$value = get_post_meta(absint($item['post_id']), 'upload', true);
		if (is_numeric($value)) { $value = wp_get_attachment_url(absint($value)); }
		if (is_array($value)) { $value = $value['url'] ?? ''; }
		if ($value && wp_http_validate_url((string) $value)) { return esc_url_raw((string) $value); }
	}
	return '';
}

/** نمرهٔ درصدی پایدار. */
function evented_reports_quiz_score($row)
{
	if (is_numeric($row->percentage) && '' !== (string) $row->percentage) { return round((float) $row->percentage, 2); }
	return (float) $row->total_points > 0 ? round(((float) $row->points / (float) $row->total_points) * 100, 2) : 0;
}

/** گزینه‌های دوره، بدون بارگذاری meta/content. */
function evented_reports_course_options()
{
	return get_posts(array('post_type' => 'sfwd-courses', 'post_status' => array('publish', 'private'), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'fields' => 'ids', 'no_found_rows' => true));
}

/** URL خروجی امضاشده همراه فیلترهای جاری. */
function evented_reports_export_url($type, $format)
{
	$args = array('action' => 'evented_export_report', 'report_type' => $type, 'format' => $format);
	foreach (array('s', 'course_id', 'quiz_id', 'date_from', 'date_to', 'passed') as $key) {
		if (isset($_GET[$key]) && is_scalar($_GET[$key]) && '' !== (string) $_GET[$key]) { $args[$key] = sanitize_text_field(wp_unslash($_GET[$key])); } // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	return wp_nonce_url(add_query_arg($args, admin_url('admin-post.php')), 'evented_export_report');
}

/** هدر و کنترل‌های مشترک صفحات گزارش. */
function evented_reports_page_header($title, $description, $type = '')
{
	?>
	<div class="ee-report-head"><div><h1><?php echo esc_html($title); ?></h1><p><?php echo esc_html($description); ?></p></div><?php if ($type) : ?><div class="ee-report-actions"><a class="button button-primary" href="<?php echo esc_url(evented_reports_export_url($type, 'csv')); ?>">خروجی CSV برای Excel</a><a class="button" href="<?php echo esc_url(evented_reports_export_url($type, 'json')); ?>">خروجی JSON</a><button type="button" class="button" data-ee-report-print>چاپ</button></div><?php endif; ?></div>
	<?php
}

/** فرم فیلتر گزارش. */
function evented_reports_filter_form($type, $filters)
{
	$page = 'course' === $type ? 'evented-reports-courses' : 'evented-reports-quizzes';
	$courses = evented_reports_course_options();
	?>
	<form class="ee-report-filters" method="get"><input type="hidden" name="page" value="<?php echo esc_attr($page); ?>">
		<label><span>جستجو</span><input type="search" name="s" value="<?php echo esc_attr($filters['search']); ?>" placeholder="کاربر یا عنوان"></label>
		<label><span>دوره</span><select name="course_id"><option value="0">همهٔ دوره‌ها</option><?php foreach ($courses as $id) : ?><option value="<?php echo (int) $id; ?>"<?php selected($filters['course_id'], $id); ?>><?php echo esc_html(get_the_title($id)); ?></option><?php endforeach; ?></select></label>
		<?php if ('quiz' === $type) : ?><label><span>شناسه آزمون</span><input type="number" min="1" name="quiz_id" value="<?php echo $filters['quiz_id'] ?: ''; ?>" placeholder="مثلاً 123"></label><label><span>نتیجه</span><select name="passed"><option value="">همه</option><option value="yes"<?php selected($filters['passed'], 'yes'); ?>>قبول</option><option value="no"<?php selected($filters['passed'], 'no'); ?>>مردود</option></select></label><?php endif; ?>
		<label><span>از تاریخ</span><input type="date" name="date_from" value="<?php echo esc_attr($filters['date_from']); ?>"></label><label><span>تا تاریخ</span><input type="date" name="date_to" value="<?php echo esc_attr($filters['date_to']); ?>"></label>
		<label><span>در هر صفحه</span><select name="per_page"><?php foreach (array(25,50,100,200) as $size) : ?><option value="<?php echo $size; ?>"<?php selected($filters['per_page'], $size); ?>><?php echo $size; ?></option><?php endforeach; ?></select></label>
		<div class="ee-report-filter-buttons"><button class="button button-primary">اعمال فیلتر</button><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=' . $page)); ?>">پاک کردن</a></div>
	</form>
	<?php
}

/** صفحهٔ نمای کلی. */
function evented_reports_overview_page()
{
	if (!current_user_can(EVENTED_REPORTS_CAPABILITY)) { return; }
	global $wpdb; $a = $wpdb->prefix . 'learndash_user_activity';
	$stats = array('completed' => 0, 'learners' => 0, 'quizzes' => 0, 'recent' => 0);
	if (evented_reports_tables_exist()) {
		$stats['completed'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$a} WHERE activity_type='course' AND activity_completed>0"); // phpcs:ignore WordPress.DB.PreparedSQL
		$stats['learners'] = (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$a} WHERE activity_type='course' AND activity_completed>0"); // phpcs:ignore WordPress.DB.PreparedSQL
		$stats['quizzes'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$a} WHERE activity_type='quiz'"); // phpcs:ignore WordPress.DB.PreparedSQL
		$stats['recent'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$a} WHERE activity_type='course' AND activity_completed>=%d", time() - MONTH_IN_SECONDS));
	}
	?>
	<div class="wrap ee-reports"><?php evented_reports_page_header('گزارش‌گیری', 'مرکز یکپارچهٔ گزارش دوره‌ها و آزمون‌های LearnDash'); ?>
		<?php if (!evented_reports_tables_exist()) : ?><div class="notice notice-warning inline"><p>جدول فعالیت LearnDash پیدا نشد. ابتدا LearnDash را فعال کنید.</p></div><?php endif; ?>
		<div class="ee-report-stats"><article><span class="dashicons dashicons-yes-alt"></span><strong><?php echo esc_html(number_format_i18n($stats['completed'])); ?></strong><small>تکمیل دوره</small></article><article><span class="dashicons dashicons-groups"></span><strong><?php echo esc_html(number_format_i18n($stats['learners'])); ?></strong><small>یادگیرندهٔ یکتا</small></article><article><span class="dashicons dashicons-welcome-learn-more"></span><strong><?php echo esc_html(number_format_i18n($stats['quizzes'])); ?></strong><small>شرکت در آزمون</small></article><article><span class="dashicons dashicons-calendar-alt"></span><strong><?php echo esc_html(number_format_i18n($stats['recent'])); ?></strong><small>تکمیل ۳۰ روز اخیر</small></article></div>
		<div class="ee-report-links"><a href="<?php echo esc_url(admin_url('admin.php?page=evented-reports-courses')); ?>"><span class="dashicons dashicons-chart-line"></span><strong>گزارش تکمیل دوره‌ها</strong><small>فیلتر، صفحه‌بندی، CSV، JSON و چاپ</small></a><a href="<?php echo esc_url(admin_url('admin.php?page=evented-reports-quizzes')); ?>"><span class="dashicons dashicons-clipboard"></span><strong>گزارش نتایج آزمون‌ها</strong><small>نمره، IP، شماره کارت و پاسخ‌های آپلودی</small></a></div>
	</div>
	<?php
}

/** صفحهٔ جدول دوره‌های تکمیل‌شده. */
function evented_reports_courses_page()
{
	if (!current_user_can(EVENTED_REPORTS_CAPABILITY)) { return; }
	$filters = evented_reports_filters('course'); $total = evented_reports_tables_exist() ? evented_reports_course_count($filters) : 0;
	$rows = $total ? evented_reports_course_rows($filters, $filters['per_page'], ($filters['paged'] - 1) * $filters['per_page']) : array();
	?>
	<div class="wrap ee-reports"><?php evented_reports_page_header('تکمیل دوره‌ها', 'کاربران دارای فعالیت تکمیل‌شده؛ خروجی شامل تمام نتایج فیلترشده است.', 'course'); evented_reports_filter_form('course', $filters); ?>
		<div class="ee-report-summary"><strong><?php echo esc_html(number_format_i18n($total)); ?></strong> رکورد پیدا شد.</div>
		<div class="ee-report-table-wrap"><table class="wp-list-table widefat striped"><thead><tr><th>#</th><th>کاربر</th><th>دوره</th><th>شروع</th><th>تکمیل</th><th>نام و نام خانوادگی</th><th>استان / شهر</th><th>موبایل</th><th>کد ملی</th><th>جنسیت</th><th>تولد</th></tr></thead><tbody>
		<?php if (!$rows) : ?><tr><td colspan="11" class="ee-report-empty">داده‌ای مطابق فیلترها پیدا نشد.</td></tr><?php else : foreach ($rows as $index => $row) : $u=evented_reports_user_data($row->user_id); ?><tr><td><?php echo esc_html(number_format_i18n(($filters['paged']-1)*$filters['per_page']+$index+1)); ?></td><td><a href="<?php echo esc_url(get_edit_user_link($row->user_id)); ?>"><?php echo esc_html($row->user_login); ?></a></td><td><a href="<?php echo esc_url(get_edit_post_link($row->course_id)); ?>"><?php echo esc_html($row->course_title); ?></a></td><td><?php echo esc_html(evented_reports_date($row->activity_started)); ?></td><td><?php echo esc_html(evented_reports_date($row->activity_completed)); ?></td><td><?php echo esc_html(trim($u['first_name'].' '.$u['last_name']) ?: $row->display_name); ?></td><td><?php echo esc_html(trim($u['state'].' / '.$u['city'], ' /')); ?></td><td dir="ltr"><?php echo esc_html($u['phone']); ?></td><td><?php echo esc_html($u['national']); ?></td><td><?php echo esc_html($u['gender']); ?></td><td><?php echo esc_html($u['birth']); ?></td></tr><?php endforeach; endif; ?>
		</tbody></table></div><?php echo evented_reports_pagination($filters, $total); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php
}

/** صفحهٔ نتایج آزمون. */
function evented_reports_quizzes_page()
{
	if (!current_user_can(EVENTED_REPORTS_CAPABILITY)) { return; }
	$filters=evented_reports_filters('quiz'); $total=evented_reports_tables_exist()?evented_reports_quiz_count($filters):0;
	$rows=$total?evented_reports_quiz_rows($filters,$filters['per_page'],($filters['paged']-1)*$filters['per_page']):array();
	?>
	<div class="wrap ee-reports"><?php evented_reports_page_header('نتایج آزمون‌ها', 'جزئیات شرکت‌کنندگان، نمره، IP ثبت‌شده و فایل پاسخ تشریحی', 'quiz'); evented_reports_filter_form('quiz',$filters); ?>
		<div class="ee-report-summary"><strong><?php echo esc_html(number_format_i18n($total)); ?></strong> نتیجه پیدا شد.</div><div class="ee-report-table-wrap"><table class="wp-list-table widefat striped"><thead><tr><th>#</th><th>کاربر</th><th>آزمون / دوره</th><th>تکمیل</th><th>نمره</th><th>نتیجه</th><th>IP</th><th>نام</th><th>موبایل</th><th>کد ملی</th><th>شماره کارت</th><th>فایل</th></tr></thead><tbody>
		<?php if(!$rows):?><tr><td colspan="12" class="ee-report-empty">نتیجه‌ای مطابق فیلترها پیدا نشد.</td></tr><?php else:foreach($rows as $index=>$row):$u=evented_reports_user_data($row->user_id);$upload=evented_reports_quiz_upload($row->graded);$passed=in_array(strtolower((string)$row->passed),array('1','true','yes'),true);?><tr><td><?php echo esc_html(number_format_i18n(($filters['paged']-1)*$filters['per_page']+$index+1));?></td><td><a href="<?php echo esc_url(get_edit_user_link($row->user_id));?>"><?php echo esc_html($row->user_login);?></a></td><td><strong><?php echo esc_html($row->quiz_title);?></strong><?php if($row->course_title):?><small><?php echo esc_html($row->course_title);?></small><?php endif;?></td><td><?php echo esc_html(evented_reports_date($row->activity_completed?:$row->activity_updated));?></td><td><strong><?php echo esc_html(number_format_i18n(evented_reports_quiz_score($row),2));?>٪</strong><small><?php echo esc_html(number_format_i18n((float)$row->points,2).' / '.number_format_i18n((float)$row->total_points,2));?></small></td><td><span class="ee-report-status <?php echo $passed?'is-pass':'is-fail';?>"><?php echo $passed?'قبول':'مردود';?></span></td><td dir="ltr"><?php echo esc_html($row->user_ip?:'—');?></td><td><?php echo esc_html(trim($u['first_name'].' '.$u['last_name'])?:$row->display_name);?></td><td dir="ltr"><?php echo esc_html($u['phone']);?></td><td><?php echo esc_html($u['national']);?></td><td dir="ltr"><?php echo esc_html($u['card']);?></td><td><?php echo $upload?'<a class="button button-small" href="'.esc_url($upload).'" target="_blank" rel="noopener">دانلود</a>':'—'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td></tr><?php endforeach;endif;?>
		</tbody></table></div><?php echo evented_reports_pagination($filters,$total); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php
}

/** صفحه‌بندی با حفظ فیلترها. */
function evented_reports_pagination($filters, $total)
{
	$pages = (int) ceil($total / max(1, $filters['per_page']));
	if ($pages < 2) { return ''; }
	$base = str_replace('999999999', '%#%', esc_url_raw(add_query_arg('paged', '999999999')));
	$links = paginate_links(array('base'=>$base,'format'=>'','current'=>$filters['paged'],'total'=>$pages,'type'=>'array','prev_text'=>'قبلی','next_text'=>'بعدی','mid_size'=>1,'end_size'=>1));
	return $links ? '<nav class="ee-report-pagination" aria-label="صفحه‌بندی">'.implode('',array_map('wp_kses_post',$links)).'</nav>' : '';
}

/** ساخت آرایهٔ یک ردیف برای خروجی. */
function evented_reports_export_row($type, $row)
{
	$u=evented_reports_user_data($row->user_id);
	if ('course'===$type) return array($row->user_login,$row->course_title,evented_reports_date($row->activity_started),evented_reports_date($row->activity_completed),$u['first_name'],$u['last_name'],$u['state'],$u['city'],$u['phone'],$u['national'],$u['gender'],$u['birth']);
	$upload=evented_reports_quiz_upload($row->graded);$passed=in_array(strtolower((string)$row->passed),array('1','true','yes'),true);
	return array($row->user_login,$row->quiz_title,$row->course_title,evented_reports_date($row->activity_completed?:$row->activity_updated),evented_reports_quiz_score($row),$passed?'قبول':'مردود',$row->user_ip,$u['first_name'],$u['last_name'],$u['phone'],$u['national'],$u['card'],$u['state'],$u['city'],$u['gender'],$u['birth'],$upload);
}

/** خروجی stream شدهٔ CSV/JSON؛ بدون ساخت جدول ۳۰هزار ردیفی در حافظه یا مرورگر. */
function evented_reports_export()
{
	if (!current_user_can(EVENTED_REPORTS_CAPABILITY)) { wp_die('دسترسی غیرمجاز.', '', array('response'=>403)); }
	check_admin_referer('evented_export_report');
	if (!evented_reports_tables_exist()) { wp_die('جدول‌های فعالیت LearnDash پیدا نشد.', '', array('response' => 503)); }
	$type=isset($_GET['report_type'])&&'quiz'===$_GET['report_type']?'quiz':'course';
	$format=isset($_GET['format'])&&'json'===$_GET['format']?'json':'csv';
	$filters=evented_reports_filters($type);$filters['paged']=1;
	$headers='course'===$type?array('نام کاربری','دوره','شروع','تکمیل','نام','نام خانوادگی','استان','شهر','موبایل','کد ملی','جنسیت','تولد'):array('نام کاربری','آزمون','دوره','تکمیل','نمره','نتیجه','IP','نام','نام خانوادگی','موبایل','کد ملی','شماره کارت','استان','شهر','جنسیت','تولد','فایل');
	$filename='shamiim-'.$type.'-report-'.gmdate('Y-m-d-His').'.'.$format;
	nocache_headers(); header('X-Content-Type-Options: nosniff'); header('Content-Disposition: attachment; filename="'.$filename.'"');
	if('csv'===$format){header('Content-Type: text/csv; charset=UTF-8');$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,$headers);}
	else{header('Content-Type: application/json; charset=UTF-8');echo '{"columns":'.wp_json_encode($headers,JSON_UNESCAPED_UNICODE).',"rows":[';$first=true;}
	$offset=0;$chunk=500;
	do{$rows='course'===$type?evented_reports_course_rows($filters,$chunk,$offset):evented_reports_quiz_rows($filters,$chunk,$offset);foreach($rows as $row){$data=array_map('evented_reports_export_cell',evented_reports_export_row($type,$row));if('csv'===$format)fputcsv($out,$data);else{if(!$first)echo ',';echo wp_json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);$first=false;}}$offset+=$chunk;}while(count($rows)===$chunk);
	if('csv'===$format)fclose($out);else echo ']}';exit;
}
add_action('admin_post_evented_export_report','evented_reports_export');

/** IP معتبر درخواست، بدون اعتماد به headerهای قابل جعل proxy. */
function evented_reports_remote_ip()
{
	$ip=isset($_SERVER['REMOTE_ADDR'])?sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])):'';
	return filter_var($ip,FILTER_VALIDATE_IP)?$ip:'';
}

/** ثبت IP روی activity_id واقعی آزمون. */
function evented_reports_capture_quiz_ip($data)
{
	if (empty($data['user']) && !get_current_user_id()) { return; }
	global $wpdb;$a=$wpdb->prefix.'learndash_user_activity';$m=$wpdb->prefix.'learndash_user_activity_meta';
	$user_value=$data['user']??0;$user_id=$user_value instanceof WP_User?(int)$user_value->ID:(absint($user_value)?:get_current_user_id());
	$quiz_value=$data['quiz']??($data['quiz_id']??0);$quiz_id=$quiz_value instanceof WP_Post?(int)$quiz_value->ID:absint($quiz_value);$completed=absint($data['completed']??time());$activity_id=absint($data['activity_id']??0);
	if(!$activity_id&&$quiz_id){$activity_id=(int)$wpdb->get_var($wpdb->prepare("SELECT activity_id FROM {$a} WHERE user_id=%d AND post_id=%d AND activity_type='quiz' ORDER BY ABS(activity_updated-%d),activity_id DESC LIMIT 1",$user_id,$quiz_id,$completed));}
	$ip=evented_reports_remote_ip();if(!$activity_id||!$ip)return;
	$existing=$wpdb->get_var($wpdb->prepare("SELECT activity_meta_id FROM {$m} WHERE activity_id=%d AND activity_meta_key='user_ip' LIMIT 1",$activity_id));
	if($existing)$wpdb->update($m,array('activity_meta_value'=>$ip),array('activity_meta_id'=>$existing),array('%s'),array('%d'));else$wpdb->insert($m,array('activity_id'=>$activity_id,'activity_meta_key'=>'user_ip','activity_meta_value'=>$ip),array('%d','%s','%s'));
}
add_action('learndash_quiz_completed','evented_reports_capture_quiz_ip',10,1);

/** shortcode سازگار قبلی برای ثبت امن شماره کارت. */
function evented_reports_card_number_shortcode()
{
	if(!is_user_logged_in())return '';$user_id=get_current_user_id();$message='';
	if('POST'===$_SERVER['REQUEST_METHOD']&&isset($_POST['evented_card_action'])){
		if(!isset($_POST['evented_card_nonce'])||!is_scalar($_POST['evented_card_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['evented_card_nonce'])),'evented_card_number'))$message='<p class="ee-card-error">درخواست نامعتبر است؛ دوباره تلاش کنید.</p>';
		elseif(is_scalar($_POST['evented_card_action'])&&'remove'===sanitize_key(wp_unslash($_POST['evented_card_action']))){delete_user_meta($user_id,'card_number');$message='<p class="ee-card-success">شماره کارت حذف شد.</p>';}
		else{$number=isset($_POST['new_meta_value'])&&is_scalar($_POST['new_meta_value'])?preg_replace('/\D+/','',evented_normalize_digits(wp_unslash($_POST['new_meta_value']))):'';if(16!==strlen($number))$message='<p class="ee-card-error">شماره کارت باید دقیقاً ۱۶ رقم باشد.</p>';else{update_user_meta($user_id,'card_number',$number);$message='<p class="ee-card-success">شماره کارت با موفقیت ثبت شد.</p>';}}
	}
	$value=(string)get_user_meta($user_id,'card_number',true);wp_enqueue_style('evented-card-number',PATH_DIR_URL.'/assets/css/card-number.css',array(),'1.0.0');
	if (!$value) { wp_add_inline_style('evented-card-number', '.wpProQuiz_button{display:none!important}'); }
	ob_start();echo wp_kses_post($message);
	if(!$value):?><form class="ee-card-form" method="post"><?php wp_nonce_field('evented_card_number','evented_card_nonce');?><label for="eventedCardNumber">برای شرکت در آزمون، شماره کارت ۱۶ رقمی به نام خود را ثبت کنید.</label><div><input id="eventedCardNumber" name="new_meta_value" inputmode="numeric" pattern="[0-9۰-۹٠-٩]{16}" maxlength="16" required placeholder="شماره کارت"><button type="submit" name="evented_card_action" value="save">ثبت شماره کارت</button></div></form><?php else:$masked=substr($value,0,4).' **** **** '.substr($value,-4);?><div class="ee-card-saved"><span>شماره کارت ثبت‌شده: <b dir="ltr"><?php echo esc_html($masked);?></b></span><form method="post"><?php wp_nonce_field('evented_card_number','evented_card_nonce');?><button type="submit" name="evented_card_action" value="remove">ویرایش</button></form></div><?php endif;return ob_get_clean();
}
add_shortcode('update_meta_value','evented_reports_card_number_shortcode');
