<?php
/**
 * سئو و دادهٔ ساخت‌یافته (Schema.org / Open Graph / Twitter Card)
 *
 * - عنوان سند با title-tag وردپرس؛
 * - متای description / canonical / robots / OG / Twitter در <head>؛
 * - JSON-LD: Organization + WebSite (خانه)، Course، Article، BreadcrumbList، CollectionPage؛
 * - اگر Yoast SEO یا Rank Math یا The SEO Framework فعال باشد، همهٔ خروجی‌های این فایل غیرفعال می‌شود.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

add_action('after_setup_theme', static function () {
	add_theme_support('title-tag');
});

/**
 * آیا قالب باید متا/اسکیما چاپ کند؟
 */
function evented_seo_active()
{
	static $active = null;
	if (null !== $active) {
		return $active;
	}
	$plugin = defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('THE_SEO_FRAMEWORK_VERSION') || class_exists('AIOSEO\\Plugin\\AIOSEO');
	$opt    = function_exists('evented_opt') ? (int) evented_opt('seo_enabled', 1) : 1;
	$active = (bool) apply_filters('evented_seo_active', !$plugin && 1 === $opt);
	return $active;
}

/**
 * جداکنندهٔ عنوان.
 */
add_filter('document_title_separator', static function () {
	return '|';
});

/**
 * عنوان صفحهٔ اصلی از تنظیمات.
 */
add_filter('document_title_parts', static function ($parts) {
	if (!evented_seo_active()) {
		return $parts;
	}
	if (is_front_page()) {
		$t = function_exists('evented_opt') ? (string) evented_opt('seo_home_title', '') : '';
		if ('' !== $t) {
			return array('title' => $t);
		}
	}
	return $parts;
});

/**
 * متن خلاصهٔ ۱۶۰ کاراکتری از یک نوشته.
 */
function evented_seo_excerpt($post, $len = 160)
{
	$post = get_post($post);
	if (!$post instanceof WP_Post) {
		return '';
	}
	$txt = has_excerpt($post) ? $post->post_excerpt : $post->post_content;
	$txt = wp_strip_all_tags(strip_shortcodes((string) $txt));
	$txt = preg_replace('/\s+/u', ' ', $txt);
	$txt = trim((string) $txt);
	if (function_exists('mb_substr') && mb_strlen($txt) > $len) {
		$txt = rtrim(mb_substr($txt, 0, $len - 1)) . '…';
	}
	return $txt;
}

/**
 * توضیحات متای صفحهٔ جاری.
 */
function evented_seo_description()
{
	if (is_front_page()) {
		$d = function_exists('evented_opt') ? (string) evented_opt('seo_home_desc', '') : '';
		return '' !== $d ? $d : (string) get_bloginfo('description');
	}
	if (is_singular()) {
		return evented_seo_excerpt(get_queried_object());
	}
	if (is_category() || is_tag() || is_tax()) {
		$d = term_description();
		$d = trim(wp_strip_all_tags((string) $d));
		return '' !== $d ? $d : sprintf('همهٔ مطالب دسته‌بندی %s در %s', single_term_title('', false), get_bloginfo('name'));
	}
	if (is_post_type_archive('sfwd-courses')) {
		return sprintf('فهرست دوره‌های آموزشی %s', get_bloginfo('name'));
	}
	if (is_author()) {
		$d = trim((string) get_the_author_meta('description'));
		return '' !== $d ? evented_seo_excerpt_text($d) : sprintf('دوره‌ها و مقالات %s', get_the_author());
	}
	if (is_search()) {
		return sprintf('نتایج جستجو برای «%s»', get_search_query());
	}
	return (string) get_bloginfo('description');
}

function evented_seo_excerpt_text($txt, $len = 160)
{
	$txt = preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) $txt));
	return (function_exists('mb_strlen') && mb_strlen($txt) > $len) ? rtrim(mb_substr($txt, 0, $len - 1)) . '…' : $txt;
}

/**
 * تصویر اشتراک‌گذاری صفحهٔ جاری.
 */
function evented_seo_image()
{
	if (is_singular() && has_post_thumbnail()) {
		$img = wp_get_attachment_image_src(get_post_thumbnail_id(), 'large');
		if ($img) {
			return array('url' => $img[0], 'w' => (int) $img[1], 'h' => (int) $img[2]);
		}
	}
	if ((is_tax() || is_category()) && function_exists('evented_term_image')) {
		$u = evented_term_image(get_queried_object_id(), 'large');
		if ($u) {
			return array('url' => $u, 'w' => 0, 'h' => 0);
		}
	}
	$d = function_exists('evented_opt') ? (string) evented_opt('seo_default_img', '') : '';
	if ('' === $d && has_custom_logo()) {
		$img = wp_get_attachment_image_src((int) get_theme_mod('custom_logo'), 'large');
		if ($img) {
			$d = $img[0];
		}
	}
	return '' !== $d ? array('url' => $d, 'w' => 0, 'h' => 0) : null;
}

/**
 * URL کانونی.
 */
function evented_seo_canonical()
{
	if (is_singular()) {
		return (string) get_permalink(get_queried_object_id());
	}
	if (is_front_page()) {
		return (string) home_url('/');
	}
	$url = function_exists('evented_current_url') ? (string) evented_current_url() : '';
	if ('' === $url) {
		return '';
	}
	// حذف پارامترهای فیلتر/جستجو از canonical (به‌جز صفحه‌بندی مسیر)
	$parts = wp_parse_url($url);
	$path  = isset($parts['path']) ? $parts['path'] : '/';
	return (string) home_url($path);
}

/**
 * چاپ متاها در <head>.
 */
add_action('wp_head', static function () {
	if (!evented_seo_active() || is_admin()) {
		return;
	}
	remove_action('wp_head', 'rel_canonical');

	$title = wp_get_document_title();
	$desc  = evented_seo_description();
	$canon = evented_seo_canonical();
	$img   = evented_seo_image();
	$site  = (string) get_bloginfo('name');

	$noindex = is_search() || is_404() || (is_paged() && is_search()) || (isset($_GET['orderby']) || isset($_GET['level']) || isset($_GET['price']) || isset($_GET['instructor'])); // phpcs:ignore
	if (function_exists('evented_is_panel_view') && evented_is_panel_view()) {
		$noindex = true;
	}

	echo "\n<!-- evented-edu SEO -->\n";
	if ('' !== $desc) {
		echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
	}
	echo '<meta name="robots" content="' . ($noindex ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1') . '">' . "\n";
	if ('' !== $canon && !$noindex) {
		echo '<link rel="canonical" href="' . esc_url($canon) . '">' . "\n";
	}

	echo '<meta property="og:locale" content="' . esc_attr(str_replace('-', '_', get_locale())) . '">' . "\n";
	echo '<meta property="og:type" content="' . (is_singular('post') ? 'article' : 'website') . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr($site) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
	if ('' !== $desc) {
		echo '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
	}
	if ('' !== $canon) {
		echo '<meta property="og:url" content="' . esc_url($canon) . '">' . "\n";
	}
	if ($img) {
		echo '<meta property="og:image" content="' . esc_url($img['url']) . '">' . "\n";
		if (!empty($img['w'])) {
			echo '<meta property="og:image:width" content="' . (int) $img['w'] . '">' . "\n";
			echo '<meta property="og:image:height" content="' . (int) $img['h'] . '">' . "\n";
		}
	}
	if (is_singular('post')) {
		$p = get_queried_object();
		echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c', $p)) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c', $p)) . '">' . "\n";
		foreach ((array) get_the_category($p->ID) as $c) {
			echo '<meta property="article:section" content="' . esc_attr($c->name) . '">' . "\n";
		}
	}
	echo '<meta name="twitter:card" content="' . ($img ? 'summary_large_image' : 'summary') . '">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
	if ('' !== $desc) {
		echo '<meta name="twitter:description" content="' . esc_attr($desc) . '">' . "\n";
	}
	if ($img) {
		echo '<meta name="twitter:image" content="' . esc_url($img['url']) . '">' . "\n";
	}

	$ld = evented_seo_jsonld();
	if (!empty($ld)) {
		echo '<script type="application/ld+json">' . wp_json_encode(array('@context' => 'https://schema.org', '@graph' => $ld), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
	}
	echo "<!-- /evented-edu SEO -->\n";
}, 1);

/**
 * گرهٔ Organization.
 */
function evented_seo_org_node()
{
	$name = function_exists('evented_opt') ? (string) evented_opt('org_name', '') : '';
	$name = '' !== $name ? $name : (string) get_bloginfo('name');
	$logo = function_exists('evented_opt') ? (string) evented_opt('org_logo', '') : '';
	if ('' === $logo && has_custom_logo()) {
		$src = wp_get_attachment_image_src((int) get_theme_mod('custom_logo'), 'full');
		$logo = $src ? $src[0] : '';
	}
	$same = array();
	if (function_exists('evented_social_links')) {
		foreach ((array) evented_social_links() as $s) {
			$same[] = $s['url'];
		}
	}
	if (function_exists('evented_channel_links')) {
		foreach ((array) evented_channel_links() as $c) {
			$same[] = $c['url'];
		}
	}
	$node = array(
		'@type' => 'EducationalOrganization',
		'@id'   => home_url('/#organization'),
		'name'  => $name,
		'url'   => home_url('/'),
	);
	if ('' !== $logo) {
		$node['logo'] = array('@type' => 'ImageObject', 'url' => $logo);
	}
	$phone = function_exists('evented_opt') ? (string) evented_opt('phone', '') : '';
	if ('' !== $phone) {
		$node['contactPoint'] = array('@type' => 'ContactPoint', 'telephone' => $phone, 'contactType' => 'customer support', 'availableLanguage' => 'fa');
	}
	$email = function_exists('evented_support_email') ? evented_support_email() : '';
	if ('' !== $email) {
		$node['email'] = $email;
	}
	$addr = function_exists('evented_opt') ? (string) evented_opt('address', '') : '';
	if ('' !== $addr) {
		$node['address'] = array('@type' => 'PostalAddress', 'streetAddress' => $addr, 'addressCountry' => 'IR');
	}
	if (!empty($same)) {
		$node['sameAs'] = array_values(array_unique($same));
	}
	return $node;
}

/**
 * گرهٔ BreadcrumbList.
 */
function evented_seo_breadcrumb_node()
{
	$items = array(array('name' => 'خانه', 'url' => home_url('/')));

	if (is_singular('post')) {
		$cats = get_the_category();
		if (!empty($cats)) {
			$items[] = array('name' => $cats[0]->name, 'url' => get_category_link($cats[0]));
		}
		$items[] = array('name' => get_the_title(), 'url' => get_permalink());
	} elseif (is_singular('sfwd-courses')) {
		$items[] = array('name' => 'دوره‌ها', 'url' => get_post_type_archive_link('sfwd-courses'));
		$terms = get_the_terms(get_the_ID(), 'ld_course_category');
		if (!is_wp_error($terms) && !empty($terms)) {
			$items[] = array('name' => $terms[0]->name, 'url' => get_term_link($terms[0]));
		}
		$items[] = array('name' => get_the_title(), 'url' => get_permalink());
	} elseif (is_singular(array('sfwd-lessons', 'sfwd-topic', 'sfwd-quizzes'))) {
		$cid = function_exists('learndash_get_course_id') ? (int) learndash_get_course_id(get_the_ID()) : 0;
		$items[] = array('name' => 'دوره‌ها', 'url' => get_post_type_archive_link('sfwd-courses'));
		if ($cid) {
			$items[] = array('name' => get_the_title($cid), 'url' => get_permalink($cid));
		}
		$items[] = array('name' => get_the_title(), 'url' => get_permalink());
	} elseif (is_singular()) {
		$items[] = array('name' => get_the_title(), 'url' => get_permalink());
	} elseif (is_post_type_archive('sfwd-courses')) {
		$items[] = array('name' => 'دوره‌ها', 'url' => get_post_type_archive_link('sfwd-courses'));
	} elseif (is_tax('ld_course_category')) {
		$items[] = array('name' => 'دوره‌ها', 'url' => get_post_type_archive_link('sfwd-courses'));
		$items[] = array('name' => single_term_title('', false), 'url' => get_term_link(get_queried_object()));
	} elseif (is_category() || is_tag() || is_tax()) {
		$items[] = array('name' => single_term_title('', false), 'url' => get_term_link(get_queried_object()));
	} elseif (is_author()) {
		$items[] = array('name' => get_the_author(), 'url' => get_author_posts_url(get_queried_object_id()));
	} else {
		return null;
	}

	$list = array();
	foreach ($items as $i => $it) {
		if (empty($it['url']) || is_wp_error($it['url'])) {
			continue;
		}
		$list[] = array('@type' => 'ListItem', 'position' => $i + 1, 'name' => wp_strip_all_tags((string) $it['name']), 'item' => (string) $it['url']);
	}
	return array('@type' => 'BreadcrumbList', '@id' => evented_seo_canonical() . '#breadcrumb', 'itemListElement' => $list);
}

/**
 * گرهٔ Course برای تک‌دوره.
 */
function evented_seo_course_node($course_id)
{
	$course_id = (int) $course_id;
	$org       = array('@id' => home_url('/#organization'));
	$node      = array(
		'@type'       => 'Course',
		'@id'         => get_permalink($course_id) . '#course',
		'name'        => get_the_title($course_id),
		'description' => evented_seo_excerpt($course_id, 300),
		'url'         => get_permalink($course_id),
		'provider'    => $org,
		'inLanguage'  => 'fa',
	);
	if (has_post_thumbnail($course_id)) {
		$node['image'] = get_the_post_thumbnail_url($course_id, 'large');
	}
	$author = get_userdata((int) get_post_field('post_author', $course_id));
	if ($author) {
		$node['instructor'] = array('@type' => 'Person', 'name' => $author->display_name, 'url' => get_author_posts_url($author->ID));
	}
	$level = (string) get_post_meta($course_id, '_course_level', true);
	if ('' !== $level) {
		$node['educationalLevel'] = $level;
	}
	$terms = get_the_terms($course_id, 'ld_course_category');
	if (!is_wp_error($terms) && !empty($terms)) {
		$node['about'] = wp_list_pluck($terms, 'name');
	}

	// پیشنهاد قیمت
	$pricing = function_exists('evented_course_pricing') ? evented_course_pricing($course_id) : array('is_free' => true, 'price' => '');
	$offer   = array('@type' => 'Offer', 'category' => !empty($pricing['is_free']) ? 'Free' : 'Paid', 'availability' => 'https://schema.org/InStock', 'url' => get_permalink($course_id));
	$price   = preg_replace('/[^0-9.]/', '', str_replace(array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'), range(0, 9), (string) $pricing['price']));
	$offer['price']         = !empty($pricing['is_free']) || '' === $price ? '0' : $price;
	$offer['priceCurrency'] = (string) apply_filters('evented_seo_currency', 'IRR');
	$node['offers'] = $offer;

	// hasCourseInstance (آنلاین/خودآموز) — الزام گوگل برای rich result دوره
	$node['hasCourseInstance'] = array(
		'@type'          => 'CourseInstance',
		'courseMode'     => 'Online',
		'courseWorkload' => evented_seo_iso_duration((string) get_post_meta($course_id, '_total_duration', true)),
	);
	if (empty($node['hasCourseInstance']['courseWorkload'])) {
		unset($node['hasCourseInstance']['courseWorkload']);
	}

	// امتیاز (اگر متای نظرات وجود دارد)
	$rating = get_post_meta($course_id, '_course_rating_avg', true);
	$count  = (int) get_post_meta($course_id, '_course_rating_count', true);
	if ($rating && $count > 0) {
		$node['aggregateRating'] = array('@type' => 'AggregateRating', 'ratingValue' => (string) round((float) $rating, 1), 'ratingCount' => $count, 'bestRating' => '5');
	}
	return $node;
}

/**
 * تبدیل «۱۲ ساعت» / «12:30» / «۹۰ دقیقه» به ISO 8601 (PT12H).
 */
function evented_seo_iso_duration($txt)
{
	$txt = str_replace(array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'), range(0, 9), (string) $txt);
	if (preg_match('/(\d+)\s*:\s*(\d+)/', $txt, $m)) {
		return 'PT' . (int) $m[1] . 'H' . (int) $m[2] . 'M';
	}
	$h = preg_match('/(\d+)\s*ساعت/u', $txt, $m) ? (int) $m[1] : 0;
	$mi = preg_match('/(\d+)\s*دقیقه/u', $txt, $m) ? (int) $m[1] : 0;
	if (!$h && !$mi && preg_match('/^\s*(\d+)\s*$/', $txt, $m)) {
		$h = (int) $m[1];
	}
	if (!$h && !$mi) {
		return '';
	}
	return 'PT' . ($h ? $h . 'H' : '') . ($mi ? $mi . 'M' : '');
}

/**
 * گرهٔ Article برای نوشته.
 */
function evented_seo_article_node($post)
{
	$post   = get_post($post);
	$author = get_userdata((int) $post->post_author);
	$node   = array(
		'@type'            => 'Article',
		'@id'              => get_permalink($post) . '#article',
		'headline'         => wp_strip_all_tags(get_the_title($post)),
		'description'      => evented_seo_excerpt($post, 200),
		'url'              => get_permalink($post),
		'datePublished'    => get_the_date('c', $post),
		'dateModified'     => get_the_modified_date('c', $post),
		'inLanguage'       => 'fa',
		'mainEntityOfPage' => array('@type' => 'WebPage', '@id' => get_permalink($post)),
		'publisher'        => array('@id' => home_url('/#organization')),
		'isAccessibleForFree' => true,
	);
	if ($author) {
		$node['author'] = array('@type' => 'Person', 'name' => $author->display_name, 'url' => get_author_posts_url($author->ID));
	}
	if (has_post_thumbnail($post)) {
		$node['image'] = get_the_post_thumbnail_url($post, 'large');
	}
	$cats = get_the_category($post->ID);
	if (!empty($cats)) {
		$node['articleSection'] = wp_list_pluck($cats, 'name');
	}
	$tags = get_the_tags($post->ID);
	if (!empty($tags) && !is_wp_error($tags)) {
		$node['keywords'] = implode(', ', wp_list_pluck($tags, 'name'));
	}
	$words = str_word_count(wp_strip_all_tags((string) $post->post_content));
	if ($words) {
		$node['wordCount'] = $words;
	}
	return $node;
}

/**
 * گراف JSON-LD صفحهٔ جاری.
 */
function evented_seo_jsonld()
{
	$graph   = array();
	$graph[] = evented_seo_org_node();

	if (is_front_page()) {
		$graph[] = array(
			'@type'           => 'WebSite',
			'@id'             => home_url('/#website'),
			'url'             => home_url('/'),
			'name'            => get_bloginfo('name'),
			'inLanguage'      => 'fa',
			'publisher'       => array('@id' => home_url('/#organization')),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array('@type' => 'EntryPoint', 'urlTemplate' => home_url('/?s={search_term_string}')),
				'query-input' => 'required name=search_term_string',
			),
		);
	}

	$bc = evented_seo_breadcrumb_node();
	if ($bc) {
		$graph[] = $bc;
	}

	if (is_singular('sfwd-courses')) {
		$graph[] = evented_seo_course_node(get_queried_object_id());
	} elseif (is_singular('post')) {
		$graph[] = evented_seo_article_node(get_queried_object());
	} elseif (is_singular(array('sfwd-lessons', 'sfwd-topic'))) {
		$cid = function_exists('learndash_get_course_id') ? (int) learndash_get_course_id(get_the_ID()) : 0;
		$node = array(
			'@type'      => 'LearningResource',
			'@id'        => get_permalink() . '#lesson',
			'name'       => get_the_title(),
			'url'        => get_permalink(),
			'inLanguage' => 'fa',
			'learningResourceType' => 'Lesson',
			'provider'   => array('@id' => home_url('/#organization')),
		);
		if ($cid) {
			$node['isPartOf'] = array('@type' => 'Course', '@id' => get_permalink($cid) . '#course', 'name' => get_the_title($cid), 'url' => get_permalink($cid));
		}
		$graph[] = $node;
	} elseif (is_post_type_archive('sfwd-courses') || is_tax('ld_course_category')) {
		global $wp_query;
		$list = array();
		$pos  = 0;
		if ($wp_query instanceof WP_Query) {
			foreach ($wp_query->posts as $p) {
				if (!$p instanceof WP_Post) {
					continue;
				}
				$pos++;
				$list[] = array('@type' => 'ListItem', 'position' => $pos, 'url' => get_permalink($p), 'name' => get_the_title($p));
			}
		}
		$graph[] = array(
			'@type'      => 'CollectionPage',
			'@id'        => evented_seo_canonical() . '#collection',
			'url'        => evented_seo_canonical(),
			'name'       => wp_get_document_title(),
			'inLanguage' => 'fa',
			'mainEntity' => array('@type' => 'ItemList', 'itemListElement' => $list),
		);
	} elseif (is_author()) {
		$u = get_queried_object();
		if ($u instanceof WP_User) {
			$graph[] = array(
				'@type'       => 'Person',
				'@id'         => get_author_posts_url($u->ID) . '#person',
				'name'        => $u->display_name,
				'url'         => get_author_posts_url($u->ID),
				'description' => evented_seo_excerpt_text((string) get_the_author_meta('description', $u->ID)),
				'image'       => get_avatar_url($u->ID, array('size' => 256)),
				'worksFor'    => array('@id' => home_url('/#organization')),
			);
		}
	}

	/**
	 * فیلتر گراف JSON-LD.
	 *
	 * @param array $graph
	 */
	return (array) apply_filters('evented_seo_jsonld', $graph);
}
