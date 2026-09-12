<?php
/**
 * کاتالوگ دوره‌ها در صفحهٔ اصلی — تب به‌ازای هر دسته + «بارگذاری بیشتر» (AJAX)
 *
 * - داده‌ی هر صفحه/دسته با کش نسخه‌دار ذخیره می‌شود (با ذخیره/حذف دوره یا
 *   تغییر دسته، نسخه بالا می‌رود و کش قدیمی بی‌اثر می‌شود).
 * - تب اول «همهٔ دوره‌ها» است؛ سایر تب‌ها با AJAX و کش سمت مرورگر پر می‌شوند.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/** تعداد کارت در هر بارگذاری. */
function evented_catalog_per_page()
{
	return (int) apply_filters('evented_catalog_per_page', 8);
}

/** نسخهٔ کش کاتالوگ (برای باطل‌کردن یک‌جای همهٔ صفحات). */
function evented_catalog_cache_ver()
{
	$v = (int) get_option('evented_catalog_ver', 1);
	return $v > 0 ? $v : 1;
}

/** باطل‌کردن کش کاتالوگ. */
function evented_catalog_flush()
{
	update_option('evented_catalog_ver', evented_catalog_cache_ver() + 1, false);
}
add_action('save_post_sfwd-courses', 'evented_catalog_flush');
add_action('deleted_post', static function ($post_id) {
	if ('sfwd-courses' === get_post_type($post_id)) {
		evented_catalog_flush();
	}
});
add_action('set_object_terms', static function ($object_id) {
	if ('sfwd-courses' === get_post_type($object_id)) {
		evented_catalog_flush();
	}
});

/** تبدیل ارقام به فارسی. */
if (!function_exists('evented_fa_digits')) {
	function evented_fa_digits($n)
	{
		return strtr((string) $n, array('0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹', ',' => '٬'));
	}
}

/**
 * تب‌های کاتالوگ: «همه» + دسته‌های دارای دوره (به ترتیب تعداد).
 *
 * @return array<int,array{key:string,id:int,name:string,count:int,url:string}>
 */
function evented_catalog_tabs()
{
	$ver  = evented_catalog_cache_ver();
	$tabs = get_transient('evented_catalog_tabs_' . $ver);
	if (is_array($tabs)) {
		return $tabs;
	}

	$total = wp_count_posts('sfwd-courses');
	$total = isset($total->publish) ? (int) $total->publish : 0;

	$tabs   = array();
	$tabs[] = array('key' => 'all', 'id' => 0, 'name' => 'همهٔ دوره‌ها', 'count' => $total, 'url' => function_exists('evented_nav_url') ? evented_nav_url('courses') : (string) get_post_type_archive_link('sfwd-courses'));

	$terms = get_terms(array('taxonomy' => 'ld_course_category', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 12));
	if (!is_wp_error($terms)) {
		foreach ($terms as $t) {
			$link   = get_term_link($t);
			$tabs[] = array('key' => 'cat-' . (int) $t->term_id, 'id' => (int) $t->term_id, 'name' => (string) $t->name, 'count' => (int) $t->count, 'url' => is_wp_error($link) ? '' : (string) $link);
		}
	}

	set_transient('evented_catalog_tabs_' . $ver, $tabs, 12 * HOUR_IN_SECONDS);
	return $tabs;
}

/** شمارهٔ رنگ پاستلی برای یک دسته (ثابت و قابل پیش‌بینی). */
function evented_catalog_tone($term_id)
{
	$term_id = (int) $term_id;
	if ($term_id <= 0) {
		return 0;
	}
	return 1 + ($term_id % 5);
}

/**
 * دادهٔ یک کارت دوره.
 *
 * @param int $id شناسهٔ دوره.
 * @return array<string,mixed>
 */
function evented_catalog_card_data($id)
{
	$id   = (int) $id;
	$post = get_post($id);
	if (!$post instanceof WP_Post) {
		return array();
	}

	$terms = get_the_terms($id, 'ld_course_category');
	$term  = (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : null;

	$settings = get_post_meta($id, '_sfwd-courses', true);
	$settings = is_array($settings) ? $settings : array();
	$ptype    = isset($settings['sfwd-courses_course_price_type']) ? (string) $settings['sfwd-courses_course_price_type'] : 'open';
	$price    = isset($settings['sfwd-courses_course_price']) ? (string) $settings['sfwd-courses_course_price'] : '';
	$is_free  = ('free' === $ptype || 'open' === $ptype || '' === trim($price) || 0.0 === (float) $price);

	$lessons = 0;
	if (function_exists('learndash_course_get_steps_count')) {
		$lessons = (int) learndash_course_get_steps_count($id);
	} elseif (function_exists('learndash_get_course_lessons_list')) {
		$l       = learndash_get_course_lessons_list($id, 0, array('per_page' => 0));
		$lessons = is_array($l) ? count($l) : 0;
	}

	$rating = function_exists('evented_course_rating') ? evented_course_rating($id) : array('avg' => 0, 'count' => 0);

	$age_days = (int) floor((time() - get_post_time('U', true, $post)) / DAY_IN_SECONDS);

	return array(
		'id'         => $id,
		'url'        => (string) get_permalink($id),
		'title'      => get_the_title($id),
		'thumb'      => has_post_thumbnail($id) ? (string) get_the_post_thumbnail_url($id, 'medium_large') : '',
		'cat'        => $term instanceof WP_Term ? (string) $term->name : '',
		'cat_url'    => $term instanceof WP_Term ? (string) get_term_link($term) : '',
		'tone'       => evented_catalog_tone($term instanceof WP_Term ? $term->term_id : 0),
		'instructor' => (string) get_the_author_meta('display_name', (int) $post->post_author),
		'lessons'    => $lessons,
		'duration'   => (string) get_post_meta($id, '_total_duration', true),
		'views'      => function_exists('evented_get_post_views') ? (int) evented_get_post_views($id) : 0,
		'rating'     => round((float) $rating['avg'], 1),
		'votes'      => (int) $rating['count'],
		'is_free'    => $is_free,
		'price'      => $is_free ? '' : number_format((float) $price),
		'is_new'     => $age_days <= 21,
		'excerpt'    => (mb_strlen($excerpt = wp_trim_words(wp_strip_all_tags(strip_shortcodes(has_excerpt($id) ? $post->post_excerpt : $post->post_content)), 14, '…')) >= 12) ? $excerpt : '',
	);
}

/**
 * HTML یک کارت.
 *
 * @param array $c خروجی evented_catalog_card_data.
 * @param int   $i اندیس (برای تأخیر انیمیشن ورود).
 * @return string
 */
function evented_catalog_card_html($c, $i = 0)
{
	if (empty($c)) {
		return '';
	}
	$fa = 'evented_fa_digits';
	ob_start();
	?>
	<article class="ee-cat-card tone-<?php echo (int) $c['tone']; ?>" style="--i:<?php echo (int) $i; ?>">
		<a class="ee-cat-media" href="<?php echo esc_url($c['url']); ?>" tabindex="-1" aria-hidden="true">
			<?php if ($c['thumb']) : ?>
				<img src="<?php echo esc_url($c['thumb']); ?>" alt="" loading="lazy" decoding="async">
			<?php else : ?>
				<span class="ee-cat-noimg"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-school"></use></svg></span>
			<?php endif; ?>
			<span class="ee-cat-shine" aria-hidden="true"></span>
			<span class="ee-cat-badges">
				<?php if ($c['is_free']) : ?><span class="ee-cat-badge is-free">رایگان</span><?php endif; ?>
				<?php if ($c['is_new']) : ?><span class="ee-cat-badge is-new"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-auto_awesome"></use></svg> جدید</span><?php endif; ?>
			</span>
			<span class="ee-cat-play" aria-hidden="true"><svg class="ee-ic" focusable="false"><use href="#i-play_arrow"></use></svg></span>
		</a>
		<div class="ee-cat-body">
			<div class="ee-cat-top">
				<?php if ($c['cat']) : ?>
					<a class="ee-cat-chip" href="<?php echo esc_url($c['cat_url']); ?>"><i></i><?php echo esc_html($c['cat']); ?></a>
				<?php else : ?>
					<span class="ee-cat-chip"><i></i>دورهٔ تخصصی</span>
				<?php endif; ?>
				<?php if ($c['votes'] > 0) : ?>
					<span class="ee-cat-rate" title="<?php echo esc_attr($fa($c['rating']) . ' از ۵ — ' . $fa($c['votes']) . ' رأی'); ?>">
						<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-star_fill"></use></svg>
						<b><?php echo esc_html($fa($c['rating'])); ?></b>
						<small>(<?php echo esc_html($fa($c['votes'])); ?>)</small>
					</span>
				<?php endif; ?>
			</div>
			<h3 class="ee-cat-title"><a href="<?php echo esc_url($c['url']); ?>"><?php echo esc_html($c['title']); ?></a></h3>
			<p class="ee-cat-ex"><?php echo esc_html($c['excerpt']); ?></p>
			<div class="ee-cat-meta">
				<span><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-person"></use></svg><?php echo esc_html($c['instructor'] ?: 'مدرس سایت'); ?></span>
				<?php if ($c['lessons'] > 0) : ?><span><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-play_lesson"></use></svg><?php echo esc_html($fa($c['lessons'])); ?> درس</span><?php endif; ?>
				<?php if ($c['duration']) : ?><span><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-schedule"></use></svg><?php echo esc_html($c['duration']); ?></span><?php endif; ?>
				<?php if ($c['views'] > 0 && $c['lessons'] < 1) : ?><span><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-visibility"></use></svg><?php echo esc_html($fa(number_format($c['views']))); ?></span><?php endif; ?>
			</div>
			<div class="ee-cat-foot">
				<span class="ee-cat-price<?php echo $c['is_free'] ? ' is-free' : ''; ?>">
					<?php if ($c['is_free']) : ?>
						<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-verified"></use></svg> رایگان
					<?php else : ?>
						<b><?php echo esc_html($fa($c['price'])); ?></b><small>تومان</small>
					<?php endif; ?>
				</span>
				<a class="ee-cat-go" href="<?php echo esc_url($c['url']); ?>" aria-label="<?php echo esc_attr('مشاهدهٔ ' . $c['title']); ?>">
					<span>مشاهده</span><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg>
				</a>
			</div>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * یک صفحه از کاتالوگ (کش‌شده).
 *
 * @param int $cat  شناسهٔ دسته (۰ = همه).
 * @param int $page شمارهٔ صفحه.
 * @return array{html:string,total:int,shown:int,has_more:bool,page:int}
 */
function evented_catalog_page($cat = 0, $page = 1)
{
	$cat  = max(0, (int) $cat);
	$page = max(1, (int) $page);
	$per  = evented_catalog_per_page();
	$key  = sprintf('evented_catalog_%d_%d_%d_%d', evented_catalog_cache_ver(), $cat, $page, $per);

	$out = get_transient($key);
	if (is_array($out) && isset($out['html'])) {
		return $out;
	}

	$args = array(
		'post_type'           => 'sfwd-courses',
		'post_status'         => 'publish',
		'posts_per_page'      => $per,
		'paged'               => $page,
		'ignore_sticky_posts' => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'fields'              => 'ids',
	);
	if ($cat) {
		$args['tax_query'] = array(array('taxonomy' => 'ld_course_category', 'field' => 'term_id', 'terms' => $cat));
	}
	$q = new WP_Query(apply_filters('evented_catalog_query_args', $args, $cat, $page));

	$html = '';
	$i    = 0;
	foreach ($q->posts as $id) {
		$html .= evented_catalog_card_html(evented_catalog_card_data($id), $i++);
	}
	wp_reset_postdata();

	$total = (int) $q->found_posts;
	$shown = min($total, $page * $per);
	$out   = array('html' => $html, 'total' => $total, 'shown' => $shown, 'has_more' => $shown < $total, 'page' => $page);

	set_transient($key, $out, 6 * HOUR_IN_SECONDS);
	return $out;
}

/** AJAX: بارگذاری صفحه/دسته. */
function evented_catalog_ajax()
{
	check_ajax_referer('evented_catalog', 'nonce');
	$cat  = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;
	$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
	if ($cat && !term_exists($cat, 'ld_course_category')) {
		wp_send_json_error(array('message' => 'دسته یافت نشد.'), 404);
	}
	wp_send_json_success(evented_catalog_page($cat, $page));
}
add_action('wp_ajax_evented_catalog', 'evented_catalog_ajax');
add_action('wp_ajax_nopriv_evented_catalog', 'evented_catalog_ajax');

/** دارایی‌های بخش (فقط صفحهٔ اصلی). */
add_action('wp_enqueue_scripts', static function () {
	if (!is_front_page() || !function_exists('evented_is_ee_view') || !evented_is_ee_view()) {
		return;
	}
	wp_enqueue_style('ee-catalog', PATH_DIR_URL . '/assets/css/newhome/ee-catalog.css', array('ee-shell'), '1.0.0');
	wp_enqueue_script('ee-catalog', PATH_DIR_URL . '/assets/js/newhome/ee-catalog.js', array(), '1.0.0', true);
	wp_localize_script('ee-catalog', 'eeCatalog', array(
		'ajax'  => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('evented_catalog'),
		'per'   => evented_catalog_per_page(),
	));
}, 20);
