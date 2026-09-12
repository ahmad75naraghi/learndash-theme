<?php
/**
 * نظرات و امتیاز واقعی دوره‌ها
 *
 * - میانگین/تعداد امتیاز از متای دیدگاه‌های تأییدشده (`review_rating`) محاسبه و در
 *   متای دوره (`_course_rating_avg`, `_course_rating_count`, `_course_rating_dist`) کش می‌شود؛
 * - با هر تأیید/رد/حذف دیدگاه به‌روز می‌شود (بدون کوئری سنگین در نمایش)؛
 * - خروجی ستاره‌ها برای کارت‌ها و تک‌دوره (`evented_rating_stars_html`, `evented_rating_summary_html`)؛
 * - Schema `AggregateRating` + `Review` روی گرهٔ Course:
 *     • اگر یوست/رنک‌مث فعال نباشد → از طریق فیلتر `evented_seo_jsonld` قالب؛
 *     • اگر یوست فعال باشد → گرهٔ Course به گراف یوست (`wpseo_schema_graph`) اضافه می‌شود؛
 * - مرتب‌سازی «بالاترین امتیاز» در فیلتر دوره‌ها.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/**
 * نوع‌های پستی که امتیاز می‌گیرند (دوره‌ها + نوشته‌های بلاگ).
 *
 * @return string[]
 */
function evented_rating_post_types()
{
	return (array) apply_filters('evented_rating_post_types', array('sfwd-courses', 'post'));
}

/* ------------------------------------------------------------------ */
/* محاسبه و کش                                                          */
/* ------------------------------------------------------------------ */

/**
 * بازمحاسبهٔ امتیاز یک دوره از دیدگاه‌های تأییدشده.
 *
 * @param int $course_id
 * @return array{avg:float,count:int,dist:array<int,int>}
 */
function evented_rating_recalc($course_id)
{
	global $wpdb;
	$course_id = (int) $course_id;
	$rows      = $wpdb->get_results($wpdb->prepare(
		"SELECT m.meta_value AS r, COUNT(*) AS n
		   FROM {$wpdb->comments} c
		   INNER JOIN {$wpdb->commentmeta} m ON m.comment_id = c.comment_ID AND m.meta_key = 'review_rating'
		  WHERE c.comment_post_ID = %d AND c.comment_approved = '1' AND c.comment_parent = 0
		  GROUP BY m.meta_value",
		$course_id
	)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

	$dist  = array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0);
	$sum   = 0;
	$count = 0;
	foreach ((array) $rows as $row) {
		$r = (int) $row->r;
		$n = (int) $row->n;
		if ($r < 1 || $r > 5 || $n < 1) {
			continue;
		}
		$dist[$r] += $n;
		$sum      += $r * $n;
		$count    += $n;
	}
	$avg = $count ? round($sum / $count, 2) : 0.0;

	update_post_meta($course_id, '_course_rating_avg', $avg);
	update_post_meta($course_id, '_course_rating_count', $count);
	update_post_meta($course_id, '_course_rating_dist', $dist);

	return array('avg' => (float) $avg, 'count' => $count, 'dist' => $dist);
}

/**
 * امتیاز کش‌شدهٔ دوره (در صورت نبود، یک بار محاسبه می‌شود).
 *
 * @param int $course_id
 * @return array{avg:float,count:int,dist:array<int,int>}
 */
function evented_course_rating($course_id)
{
	$course_id = (int) $course_id;
	$count     = get_post_meta($course_id, '_course_rating_count', true);
	if ('' === $count) {
		return evented_rating_recalc($course_id);
	}
	$dist = get_post_meta($course_id, '_course_rating_dist', true);
	return array(
		'avg'   => (float) get_post_meta($course_id, '_course_rating_avg', true),
		'count' => (int) $count,
		'dist'  => is_array($dist) ? $dist : array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0),
	);
}

/**
 * پس از هر تغییر وضعیت/ویرایش/حذف دیدگاه، امتیاز دورهٔ مربوط را به‌روز کن.
 */
function evented_rating_on_comment_change($comment)
{
	$comment = $comment instanceof WP_Comment ? $comment : get_comment($comment);
	if (!$comment) {
		return;
	}
	$post_id = (int) $comment->comment_post_ID;
	if (!in_array(get_post_type($post_id), evented_rating_post_types(), true)) {
		return;
	}
	evented_rating_recalc($post_id);
}
add_action('transition_comment_status', static function ($new, $old, $comment) {
	evented_rating_on_comment_change($comment);
}, 10, 3);
add_action('wp_insert_comment', static function ($id, $comment) {
	evented_rating_on_comment_change($comment);
}, 10, 2);
add_action('edit_comment', 'evented_rating_on_comment_change');
add_action('deleted_comment', static function ($id, $comment) {
	evented_rating_on_comment_change($comment);
}, 10, 2);
add_action('updated_comment_meta', static function ($mid, $object_id, $meta_key) {
	if ('review_rating' === $meta_key) {
		evented_rating_on_comment_change($object_id);
	}
}, 10, 3);
add_action('added_comment_meta', static function ($mid, $object_id, $meta_key) {
	if ('review_rating' === $meta_key) {
		evented_rating_on_comment_change($object_id);
	}
}, 10, 3);

/**
 * پر کردن اولیهٔ کش برای دوره‌هایی که هنوز امتیاز ندارند (یک بار، در پس‌زمینه).
 */
add_action('admin_init', static function () {
	if (get_option('evented_rating_backfilled')) {
		return;
	}
	$ids = get_posts(array('post_type' => evented_rating_post_types(), 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 200, 'meta_query' => array(array('key' => '_course_rating_count', 'compare' => 'NOT EXISTS')))); // phpcs:ignore WordPress.DB.SlowDBQuery
	foreach ($ids as $id) {
		evented_rating_recalc($id);
	}
	if (count($ids) < 200) {
		update_option('evented_rating_backfilled', 1, false);
	}
});

/* ------------------------------------------------------------------ */
/* نمایش                                                                */
/* ------------------------------------------------------------------ */

/**
 * ۵ ستاره (پر/نیمه/خالی) بر اساس میانگین.
 *
 * @param float  $avg
 * @param string $class
 * @return string
 */
function evented_rating_stars_html($avg, $class = '')
{
	$avg  = max(0, min(5, (float) $avg));
	$full = (int) floor($avg + 0.001);
	$half = ($avg - $full) >= 0.25 && ($avg - $full) < 0.75 ? 1 : 0;
	if (($avg - $full) >= 0.75) {
		$full++;
	}
	$h = '<span class="ee-stars ' . esc_attr($class) . '" role="img" aria-label="' . esc_attr(sprintf('%s از ۵', number_format_i18n($avg, 1))) . '">';
	for ($i = 1; $i <= 5; $i++) {
		if ($i <= $full) {
			$h .= '<svg class="ee-ic is-on" aria-hidden="true" focusable="false"><use href="#i-star_fill"></use></svg>';
		} elseif ($half && $i === $full + 1) {
			$h .= '<svg class="ee-ic is-half" aria-hidden="true" focusable="false"><use href="#i-star_half_fill"></use></svg>';
		} else {
			$h .= '<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-star"></use></svg>';
		}
	}
	return $h . '</span>';
}

/**
 * نشان کوچک امتیاز برای کارت‌ها: ★ ۴٫۶ (۱۲).
 *
 * @param int $course_id
 * @return string خالی اگر امتیازی نیست.
 */
function evented_rating_badge_html($course_id)
{
	$r = evented_course_rating($course_id);
	if ($r['count'] < 1) {
		return '';
	}
	return '<span class="ee-rating-badge" title="' . esc_attr(sprintf('%s از ۵ — %s رأی', number_format_i18n($r['avg'], 1), number_format_i18n($r['count']))) . '">'
		. '<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-star_fill"></use></svg>'
		. '<b>' . esc_html(number_format_i18n($r['avg'], 1)) . '</b>'
		. '<small>(' . esc_html(number_format_i18n($r['count'])) . ')</small>'
		. '</span>';
}

/**
 * خلاصهٔ امتیاز برای بالای بخش نظرات تک‌دوره (عدد بزرگ + ستاره + نمودار توزیع).
 *
 * @param int $course_id
 * @return string
 */
function evented_rating_summary_html($course_id)
{
	$r = evented_course_rating($course_id);
	if ($r['count'] < 1) {
		return '<div class="ee-rating-sum is-empty"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-star"></use></svg><span>هنوز امتیازی ثبت نشده؛ اولین نفر باشید.</span></div>';
	}
	$h  = '<div class="ee-rating-sum">';
	$h .= '<div class="ee-rating-big"><strong>' . esc_html(number_format_i18n($r['avg'], 1)) . '</strong>' . evented_rating_stars_html($r['avg'], 'ee-stars-lg') . '<span>' . esc_html(sprintf('از %s رأی', number_format_i18n($r['count']))) . '</span></div>';
	$h .= '<ul class="ee-rating-dist">';
	for ($i = 5; $i >= 1; $i--) {
		$n   = isset($r['dist'][$i]) ? (int) $r['dist'][$i] : 0;
		$pct = $r['count'] ? round($n / $r['count'] * 100) : 0;
		$h  .= '<li><span class="lbl">' . esc_html(number_format_i18n($i)) . ' <svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-star_fill"></use></svg></span>'
			. '<span class="bar" aria-hidden="true"><i style="width:' . (int) $pct . '%"></i></span>'
			. '<span class="num">' . esc_html(number_format_i18n($n)) . '</span></li>';
	}
	$h .= '</ul></div>';
	return $h;
}

/* ------------------------------------------------------------------ */
/* Schema                                                               */
/* ------------------------------------------------------------------ */

/**
 * دادهٔ AggregateRating + Review برای Schema.
 *
 * @param int $course_id
 * @return array{aggregateRating?:array,review?:array}
 */
function evented_rating_schema_parts($course_id)
{
	$r = evented_course_rating($course_id);
	if ($r['count'] < 1) {
		return array();
	}
	$out = array(
		'aggregateRating' => array(
			'@type'       => 'AggregateRating',
			'ratingValue' => (string) round($r['avg'], 1),
			'ratingCount' => (int) $r['count'],
			'reviewCount' => (int) $r['count'],
			'bestRating'  => '5',
			'worstRating' => '1',
		),
	);
	$comments = get_comments(array('post_id' => $course_id, 'status' => 'approve', 'number' => 5, 'parent' => 0, 'meta_key' => 'review_rating')); // phpcs:ignore WordPress.DB.SlowDBQuery
	$reviews  = array();
	foreach ($comments as $c) {
		$rate = (int) get_comment_meta($c->comment_ID, 'review_rating', true);
		if ($rate < 1) {
			continue;
		}
		$reviews[] = array(
			'@type'         => 'Review',
			'author'        => array('@type' => 'Person', 'name' => (string) $c->comment_author),
			'datePublished' => mysql2date('c', $c->comment_date_gmt, false),
			'reviewBody'    => wp_strip_all_tags((string) $c->comment_content),
			'reviewRating'  => array('@type' => 'Rating', 'ratingValue' => (string) $rate, 'bestRating' => '5', 'worstRating' => '1'),
		);
	}
	if ($reviews) {
		$out['review'] = $reviews;
	}
	return $out;
}

/**
 * (الف) وقتی سئوی داخلی قالب فعال است: گرهٔ Course را تکمیل کن.
 */
add_filter('evented_seo_jsonld', static function ($graph) {
	if (!is_singular(evented_rating_post_types()) || !is_array($graph)) {
		return $graph;
	}
	$parts = evented_rating_schema_parts(get_queried_object_id());
	if (!$parts) {
		return $graph;
	}
	foreach ($graph as $k => $node) {
		if (isset($node['@type']) && in_array($node['@type'], array('Course', 'Article', 'BlogPosting', 'NewsArticle'), true)) {
			$graph[$k] = array_merge($node, $parts);
		}
	}
	return $graph;
});

/**
 * (ب-۲) یوست برای نوشته‌ها خودش گرهٔ Article می‌سازد → فقط امتیاز به همان گره اضافه می‌شود.
 */
add_filter('wpseo_schema_article', static function ($data) {
	if (!is_singular('post') || !is_array($data)) {
		return $data;
	}
	return array_merge($data, evented_rating_schema_parts(get_queried_object_id()));
});

/**
 * (ب) وقتی یوست فعال است: گرهٔ Course (با امتیاز) را به گراف یوست اضافه کن.
 * یوست خودش برای sfwd-courses فقط WebPage/Article می‌سازد؛ این گره rich result دوره را ممکن می‌کند.
 */
add_filter('wpseo_schema_graph', static function ($graph, $context) {
	if (!is_singular('sfwd-courses') || !is_array($graph)) {
		return $graph;
	}
	$course_id = get_queried_object_id();
	$node      = function_exists('evented_seo_course_node') ? evented_seo_course_node($course_id) : array(
		'@type'       => 'Course',
		'@id'         => get_permalink($course_id) . '#course',
		'name'        => get_the_title($course_id),
		'description' => wp_strip_all_tags(get_the_excerpt($course_id)),
		'url'         => get_permalink($course_id),
		'provider'    => array('@type' => 'Organization', 'name' => get_bloginfo('name'), 'url' => home_url('/')),
	);
	// در گراف یوست، ناشر با @id سایت/سازمان یوست شناخته می‌شود.
	if (is_object($context) && !empty($context->site_url)) {
		$node['provider'] = array('@id' => trailingslashit($context->site_url) . '#organization');
	}
	$node = array_merge($node, evented_rating_schema_parts($course_id));

	foreach ($graph as $k => $n) {
		if (isset($n['@type']) && 'Course' === $n['@type']) {
			$graph[$k] = array_merge($n, $node);
			return $graph;
		}
		// صفحهٔ اصلی گراف → mainEntity به دوره اشاره کند
		if (isset($n['@type']) && (('WebPage' === $n['@type']) || (is_array($n['@type']) && in_array('WebPage', $n['@type'], true)))) {
			$graph[$k]['mainEntity'] = array('@id' => $node['@id']);
		}
	}
	$graph[] = $node;
	return $graph;
}, 20, 2);

/* ------------------------------------------------------------------ */
/* مرتب‌سازی بر اساس امتیاز                                             */
/* ------------------------------------------------------------------ */

add_filter('evented_course_filter_defs', static function ($defs) {
	if (isset($defs['orderby']['options']) && !isset($defs['orderby']['options']['rating'])) {
		$opts = $defs['orderby']['options'];
		$new  = array();
		foreach ($opts as $k => $v) {
			$new[$k] = $v;
			if ('popular' === $k) {
				$new['rating'] = 'بالاترین امتیاز';
			}
		}
		$defs['orderby']['options'] = $new;
	}
	return $defs;
});

add_filter('evented_course_filter_query_args', static function ($args, $vals) {
	if (empty($vals['orderby']) || 'rating' !== $vals['orderby']) {
		return $args;
	}
	$mq = isset($args['meta_query']) && is_array($args['meta_query']) ? $args['meta_query'] : array();
	$mq['ee_rating'] = array('key' => '_course_rating_avg', 'type' => 'DECIMAL(4,2)', 'compare' => 'EXISTS');
	$mq['ee_rcount'] = array('key' => '_course_rating_count', 'type' => 'NUMERIC', 'compare' => 'EXISTS');
	$args['meta_query'] = $mq; // phpcs:ignore WordPress.DB.SlowDBQuery
	$args['orderby']    = array('ee_rating' => 'DESC', 'ee_rcount' => 'DESC', 'date' => 'DESC');
	return $args;
}, 10, 2);

/**
 * جلوگیری از ثبت بیش از یک امتیاز توسط هر کاربر برای هر دوره (ویرایش امتیاز قبلی).
 */
add_filter('evented_review_before_insert', static function ($comment_data, $course_id, $rating) {
	$existing = get_comments(array('post_id' => $course_id, 'user_id' => get_current_user_id(), 'parent' => 0, 'number' => 1, 'status' => 'all', 'meta_key' => 'review_rating')); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ($existing) {
		return new WP_Error('ee_dup', 'شما قبلاً برای این دوره دیدگاه ثبت کرده‌اید.');
	}
	return $comment_data;
}, 10, 3);

/* ------------------------------------------------------------------ */
/* بلاگ: امتیاز در فرم استاندارد دیدگاه وردپرس                         */
/* ------------------------------------------------------------------ */

/**
 * آیا این نوشته امتیاز می‌گیرد؟ (نوشتهٔ بلاگ؛ دوره‌ها فرم AJAX خودشان را دارند)
 */
function evented_rating_form_enabled($post_id = 0)
{
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	return 'post' === get_post_type($post_id) && (bool) apply_filters('evented_rating_form_enabled', true, $post_id);
}

/**
 * انتخاب‌گر ستاره‌ای بالای textarea فرم دیدگاه (فقط برای دیدگاه اصلی، نه پاسخ).
 */
add_filter('comment_form_field_comment', static function ($field) {
	if (!evented_rating_form_enabled()) {
		return $field;
	}
	$picker  = '<fieldset class="ee-rate-pick" data-ee-rate-pick>';
	$picker .= '<legend>امتیاز شما به این مطلب <small>(اختیاری)</small></legend>';
	$picker .= '<div class="ee-rate-stars" role="radiogroup">';
	for ($i = 1; $i <= 5; $i++) {
		$picker .= '<label class="ee-rate-star" title="' . esc_attr(sprintf('%s از ۵', number_format_i18n($i))) . '"><input type="radio" name="ee_rating" value="' . $i . '"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-star_fill"></use></svg><span class="screen-reader-text">' . esc_html(number_format_i18n($i)) . '</span></label>';
	}
	$picker .= '</div><span class="ee-rate-txt" aria-live="polite"></span></fieldset>';
	return $picker . $field;
});

/**
 * ذخیرهٔ امتیاز همراه دیدگاه (فقط دیدگاه سطح اول؛ هر کاربر/ایمیل یک امتیاز).
 */
add_action('comment_post', static function ($comment_id, $approved, $data) {
	if (empty($_POST['ee_rating']) || !empty($data['comment_parent'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}
	$rating  = (int) $_POST['ee_rating']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$post_id = (int) $data['comment_post_ID'];
	if ($rating < 1 || $rating > 5 || !evented_rating_form_enabled($post_id)) {
		return;
	}
	// امتیاز قبلی همین کاربر/ایمیل روی همین نوشته → امتیاز جدید نادیده گرفته می‌شود (دیدگاه ثبت می‌ماند).
	$args = array('post_id' => $post_id, 'parent' => 0, 'status' => 'all', 'meta_key' => 'review_rating', 'comment__not_in' => array($comment_id), 'number' => 1); // phpcs:ignore WordPress.DB.SlowDBQuery
	if (!empty($data['user_id'])) {
		$args['user_id'] = (int) $data['user_id'];
	} else {
		$args['author_email'] = (string) $data['comment_author_email'];
	}
	if (get_comments($args)) {
		return;
	}
	add_comment_meta($comment_id, 'review_rating', $rating, true);
}, 10, 3);

/**
 * نمایش ستاره‌های هر دیدگاه در فهرست استاندارد (wp_list_comments).
 */
add_filter('comment_text', static function ($text, $comment = null) {
	if (!$comment instanceof WP_Comment || is_admin() || is_feed()) {
		return $text;
	}
	$rating = (int) get_comment_meta($comment->comment_ID, 'review_rating', true);
	if ($rating < 1 || $rating > 5) {
		return $text;
	}
	return '<div class="ee-cmt-rating">' . evented_rating_stars_html($rating) . '</div>' . $text;
}, 10, 2);

/**
 * حذف/ویرایش امتیاز از ستون دیدگاه‌های پیشخوان (نمایش ستاره در ستون دیدگاه).
 */
add_filter('comment_row_actions', static function ($actions, $comment) {
	$rating = (int) get_comment_meta($comment->comment_ID, 'review_rating', true);
	if ($rating > 0) {
		$actions = array('ee_rating' => '<span style="color:#f59e0b">' . str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) . '</span>') + $actions;
	}
	return $actions;
}, 10, 2);

/**
 * مرتب‌سازی «بالاترین امتیاز» برای بایگانی نوشته‌ها: ?orderby=rating
 */
add_action('pre_get_posts', static function ($q) {
	if (is_admin() || !$q->is_main_query() || 'rating' !== (string) $q->get('orderby')) {
		return;
	}
	if (!($q->is_home() || $q->is_category() || $q->is_tag() || $q->is_author() || $q->is_post_type_archive('sfwd-courses') || $q->is_tax('ld_course_category'))) {
		return;
	}
	$q->set('meta_query', array( // phpcs:ignore WordPress.DB.SlowDBQuery
		'ee_rating' => array('key' => '_course_rating_avg', 'type' => 'DECIMAL(4,2)', 'compare' => 'EXISTS'),
		'ee_rcount' => array('key' => '_course_rating_count', 'type' => 'NUMERIC', 'compare' => 'EXISTS'),
	));
	$q->set('orderby', array('ee_rating' => 'DESC', 'ee_rcount' => 'DESC', 'date' => 'DESC'));
});

/**
 * استایل/اسکریپت انتخاب‌گر ستاره (فقط جایی که فرم دیدگاه نوشته هست).
 */
add_action('wp_enqueue_scripts', static function () {
	if (is_singular('post') || is_singular('sfwd-courses') || is_home() || is_archive() || is_front_page() || is_search()) {
		wp_enqueue_style('ee-rating', PATH_DIR_URL . '/assets/css/newhome/ee-rating.css', array('ee-shell'), '1.0.0');
	}
	if (is_singular('post') && (comments_open() || get_comments_number())) {
		wp_enqueue_script('ee-rating', PATH_DIR_URL . '/assets/js/newhome/ee-rating.js', array(), '1.0.0', true);
	}
}, 26);
