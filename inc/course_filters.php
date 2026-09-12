<?php
/**
 * فیلتر و مرتب‌سازی واقعی آرشیو دوره‌ها (سمت سرور، از طریق query string)
 *
 * پارامترها:
 *   ?level=مقدماتی|متوسط|پیشرفته     سطح دوره (متای _course_level)
 *   ?price=free|paid                  رایگان / غیررایگان (تنظیم LearnDash)
 *   ?instructor=ID                    مدرس (post_author)
 *   ?status=running|finished          وضعیت (_course_status)
 *   ?orderby=newest|oldest|popular|title|price_asc|price_desc
 *
 * روی: بایگانی دوره‌ها، دسته‌بندی دوره، برگهٔ «دوره‌ها» (evented_courses_query)
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/**
 * تعریف فیلترها و گزینه‌ها.
 */
function evented_course_filter_defs()
{
	$defs = array(
		'level'   => array(
			'label'   => 'سطح',
			'icon'    => 'signal_cellular_alt',
			'options' => array('' => 'همهٔ سطوح', 'مقدماتی' => 'مقدماتی', 'متوسط' => 'متوسط', 'پیشرفته' => 'پیشرفته'),
		),
		'price'   => array(
			'label'   => 'هزینه',
			'icon'    => 'sell',
			'options' => array('' => 'رایگان و غیررایگان', 'free' => 'فقط رایگان', 'paid' => 'فقط غیررایگان'),
		),
		'status'  => array(
			'label'   => 'وضعیت',
			'icon'    => 'event_available',
			'options' => array('' => 'همهٔ وضعیت‌ها', 'running' => 'در حال برگزاری', 'finished' => 'تمام‌شده'),
		),
		'orderby' => array(
			'label'   => 'مرتب‌سازی',
			'icon'    => 'sort',
			'options' => array(
				'newest'     => 'جدیدترین',
				'popular'    => 'پربازدیدترین',
				'title'      => 'عنوان (الفبا)',
				'oldest'     => 'قدیمی‌ترین',
				'price_asc'  => 'ارزان‌ترین',
				'price_desc' => 'گران‌ترین',
			),
		),
	);

	// مدرس‌ها (کش ۱ ساعته)
	$instr = get_transient('ee_course_instructors');
	if (!is_array($instr)) {
		global $wpdb;
		$rows  = $wpdb->get_results("SELECT p.post_author AS id, COUNT(*) AS n FROM {$wpdb->posts} p WHERE p.post_type = 'sfwd-courses' AND p.post_status = 'publish' GROUP BY p.post_author ORDER BY n DESC LIMIT 40"); // phpcs:ignore
		$instr = array();
		foreach ((array) $rows as $r) {
			$u = get_userdata((int) $r->id);
			if ($u) {
				$instr[(string) $u->ID] = $u->display_name;
			}
		}
		set_transient('ee_course_instructors', $instr, HOUR_IN_SECONDS);
	}
	if (!empty($instr)) {
		$defs['instructor'] = array(
			'label'   => 'مدرس',
			'icon'    => 'person',
			'options' => array('' => 'همهٔ مدرسان') + $instr,
		);
	}

	return (array) apply_filters('evented_course_filter_defs', $defs);
}

/**
 * مقادیر فعال از query string (پاک‌سازی‌شده).
 */
function evented_course_filter_values()
{
	$defs = evented_course_filter_defs();
	$out  = array();
	foreach ($defs as $key => $def) {
		$v = isset($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ('orderby' === $key) {
			$out[$key] = array_key_exists($v, $def['options']) ? $v : 'newest';
		} else {
			$out[$key] = array_key_exists($v, $def['options']) ? $v : '';
		}
	}
	return $out;
}

/**
 * آیا فیلتری فعال است؟
 */
function evented_course_filters_active()
{
	foreach (evented_course_filter_values() as $k => $v) {
		if ('orderby' === $k ? 'newest' !== $v : '' !== $v) {
			return true;
		}
	}
	return false;
}

/**
 * اعمال فیلترها روی آرگومان‌های WP_Query.
 *
 * @param array $args
 * @return array
 */
function evented_course_filter_args(array $args)
{
	$v          = evented_course_filter_values();
	$meta_query = isset($args['meta_query']) && is_array($args['meta_query']) ? $args['meta_query'] : array();

	if ('' !== $v['level']) {
		$meta_query[] = array('key' => '_course_level', 'value' => $v['level']);
	}
	if ('' !== $v['status']) {
		$meta_query[] = array('key' => '_course_status', 'value' => 'running' === $v['status'] ? 'در حال برگزاری' : 'تمام شده');
	}
	if ('' !== $v['price']) {
		// تنظیمات LearnDash در _sfwd-courses سریالایز شده است؛ نوع قیمت با LIKE بررسی می‌شود.
		if ('free' === $v['price']) {
			$meta_query[] = array(
				'relation' => 'OR',
				array('key' => '_sfwd-courses', 'value' => '"sfwd-courses_course_price_type";s:4:"free"', 'compare' => 'LIKE'),
				array('key' => '_sfwd-courses', 'value' => '"sfwd-courses_course_price_type";s:4:"open"', 'compare' => 'LIKE'),
				array('key' => '_sfwd-courses', 'value' => '"sfwd-courses_course_price";s:0:""', 'compare' => 'LIKE'),
			);
		} else {
			$meta_query[] = array(
				'relation' => 'AND',
				array('key' => '_sfwd-courses', 'value' => '"sfwd-courses_course_price_type";s:4:"free"', 'compare' => 'NOT LIKE'),
				array('key' => '_sfwd-courses', 'value' => '"sfwd-courses_course_price_type";s:4:"open"', 'compare' => 'NOT LIKE'),
				array('key' => '_sfwd-courses', 'value' => '"sfwd-courses_course_price";s:0:""', 'compare' => 'NOT LIKE'),
			);
		}
	}
	if (!empty($v['instructor'])) {
		$args['author'] = (int) $v['instructor'];
	}

	switch ($v['orderby']) {
		case 'oldest':
			$args['orderby'] = 'date';
			$args['order']   = 'ASC';
			break;
		case 'title':
			$args['orderby'] = 'title';
			$args['order']   = 'ASC';
			break;
		case 'popular':
			// متای evented_post_views برای همهٔ دوره‌ها (با backfill) وجود دارد.
			$meta_query['ee_views'] = array('key' => 'evented_post_views', 'type' => 'NUMERIC', 'compare' => 'EXISTS');
			$args['orderby']        = array('ee_views' => 'DESC', 'date' => 'DESC');
			break;
		case 'price_asc':
		case 'price_desc':
			// قیمت داخل آرایهٔ سریالایز است؛ متای کمکی _ee_price_num هنگام ذخیره ساخته می‌شود.
			$meta_query['ee_price'] = array('key' => '_ee_price_num', 'type' => 'NUMERIC', 'compare' => 'EXISTS');
			$args['orderby']        = array('ee_price' => 'price_asc' === $v['orderby'] ? 'ASC' : 'DESC', 'date' => 'DESC');
			break;
		default:
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
	}

	if (!empty($meta_query)) {
		$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	/** فیلتر برای افزودن مرتب‌سازی‌های سفارشی (مثلاً امتیاز در inc/reviews.php). */
	return (array) apply_filters('evented_course_filter_query_args', $args, $v);
}

/**
 * متاهای کمکی برای مرتب‌سازی (بازدید با پیش‌فرض ۰ و قیمت عددی) هنگام ذخیرهٔ دوره.
 */
add_action('save_post_sfwd-courses', static function ($post_id) {
	if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
		return;
	}
	if ('' === (string) get_post_meta($post_id, 'evented_post_views', true)) {
		update_post_meta($post_id, 'evented_post_views', 0);
	}
	$price = 0;
	if (function_exists('evented_course_pricing')) {
		$pr    = evented_course_pricing($post_id);
		$raw   = str_replace(array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'), range(0, 9), (string) $pr['price']);
		$price = !empty($pr['is_free']) ? 0 : (float) preg_replace('/[^0-9.]/', '', $raw);
	}
	update_post_meta($post_id, '_ee_price_num', $price);
	delete_transient('ee_course_instructors');
});

/**
 * یک‌بار پر کردن متاهای کمکی برای دوره‌های قدیمی (در پس‌زمینه، تکه‌تکه).
 */
add_action('init', static function () {
	if (get_option('ee_course_meta_backfilled')) {
		return;
	}
	if (wp_doing_ajax() || wp_doing_cron() || !is_admin()) {
		return;
	}
	$ids = get_posts(array('post_type' => 'sfwd-courses', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 50, 'meta_query' => array(array('key' => '_ee_price_num', 'compare' => 'NOT EXISTS')))); // phpcs:ignore
	if (empty($ids)) {
		update_option('ee_course_meta_backfilled', 1, false);
		return;
	}
	foreach ($ids as $id) {
		do_action('save_post_sfwd-courses', $id, get_post($id), true);
	}
});

/**
 * اعمال روی کوئری اصلی بایگانی/دستهٔ دوره‌ها.
 */
add_action('pre_get_posts', static function ($q) {
	if (is_admin() || !$q->is_main_query()) {
		return;
	}
	if (!($q->is_post_type_archive('sfwd-courses') || $q->is_tax('ld_course_category'))) {
		return;
	}
	$args = evented_course_filter_args(array());
	foreach ($args as $k => $v) {
		$q->set($k, $v);
	}
	$q->set('posts_per_page', (int) apply_filters('evented_courses_per_page', 12));
});

/**
 * اعمال روی evented_courses_query (برگهٔ دوره‌ها) از طریق فیلتر آرگومان‌ها.
 */
add_filter('evented_courses_query_args', 'evented_course_filter_args');

/**
 * URL فرم فیلتر (بدون صفحه‌بندی).
 */
function evented_course_filter_base_url()
{
	if (is_tax('ld_course_category')) {
		$l = get_term_link(get_queried_object());
		return is_wp_error($l) ? home_url('/') : $l;
	}
	if (is_singular('page')) {
		return get_permalink();
	}
	return get_post_type_archive_link('sfwd-courses');
}

/**
 * رندر نوار فیلتر/مرتب‌سازی.
 */
function evented_course_filter_bar($total = null)
{
	$defs   = evented_course_filter_defs();
	$vals   = evented_course_filter_values();
	$active = evented_course_filters_active();
	$base   = evented_course_filter_base_url();
	$search = (string) get_search_query();
	?>
	<form class="ee-cf" method="get" action="<?php echo esc_url($base); ?>" data-ee-course-filters>
		<?php if ('' !== $search) : ?><input type="hidden" name="s" value="<?php echo esc_attr($search); ?>"><?php endif; ?>
		<?php if (is_post_type_archive('sfwd-courses') && '' !== $search) : ?><input type="hidden" name="post_type" value="sfwd-courses"><?php endif; ?>
		<div class="ee-cf-row">
			<span class="ee-cf-lead"><?php echo ee_icon('tune'); // phpcs:ignore ?> فیلتر دوره‌ها<?php if (null !== $total) : ?> <b>(<?php echo esc_html(number_format_i18n((int) $total)); ?>)</b><?php endif; ?></span>
			<?php foreach ($defs as $key => $def) : ?>
				<label class="ee-cf-field<?php echo ('orderby' === $key ? 'newest' : '') !== $vals[$key] ? ' is-set' : ''; ?>">
					<?php echo ee_icon($def['icon']); // phpcs:ignore ?>
					<span class="screen-reader-text"><?php echo esc_html($def['label']); ?></span>
					<select name="<?php echo esc_attr($key); ?>" aria-label="<?php echo esc_attr($def['label']); ?>">
						<?php foreach ($def['options'] as $ov => $ol) : ?>
							<option value="<?php echo esc_attr($ov); ?>"<?php selected($vals[$key], (string) $ov); ?>><?php echo esc_html($ol); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo ee_icon('expand_more', 'ee-cf-caret'); // phpcs:ignore ?>
				</label>
			<?php endforeach; ?>
			<button type="submit" class="ee-btn ee-btn-primary ee-cf-apply">اعمال</button>
			<?php if ($active) : ?>
				<a class="ee-cf-reset" href="<?php echo esc_url('' !== $search ? add_query_arg('s', rawurlencode($search), $base) : $base); ?>"><?php echo ee_icon('close'); // phpcs:ignore ?> حذف فیلترها</a>
			<?php endif; ?>
		</div>
		<?php if ($active) : ?>
			<div class="ee-cf-chips">
				<?php foreach ($vals as $k => $v) :
					if ('' === $v || ('orderby' === $k && 'newest' === $v)) {
						continue;
					}
					$label = isset($defs[$k]['options'][$v]) ? $defs[$k]['options'][$v] : $v;
					$url   = remove_query_arg($k);
					?>
					<a class="ee-cf-chip" href="<?php echo esc_url($url); ?>" title="حذف این فیلتر"><?php echo esc_html($defs[$k]['label'] . ': ' . $label); ?> <?php echo ee_icon('close'); // phpcs:ignore ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</form>
	<?php
}
