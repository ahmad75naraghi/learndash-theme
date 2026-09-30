<?php
/**
 * برگهٔ حرفه‌ای فهرست منابع (کتابخانه، ویدئو و گالری).
 *
 * @package evented-edu
 */
defined('ABSPATH') || exit;

$ee_type       = isset($args['post_type']) ? sanitize_key($args['post_type']) : '';
$ee_active     = isset($args['active']) ? sanitize_key($args['active']) : '';
$ee_title      = isset($args['title']) ? (string) $args['title'] : '';
$ee_subtitle   = isset($args['subtitle']) ? (string) $args['subtitle'] : '';
$ee_label      = isset($args['label']) ? (string) $args['label'] : __('محتوا', 'evented-edu');
$ee_taxonomy   = isset($args['taxonomy']) ? sanitize_key($args['taxonomy']) : '';
$ee_icon       = isset($args['icon']) ? sanitize_key($args['icon']) : 'article';
$ee_modifier   = isset($args['modifier']) ? sanitize_html_class($args['modifier']) : 'resources';
$ee_page       = get_queried_object();
$ee_page_id    = $ee_page instanceof WP_Post ? (int) $ee_page->ID : 0;
/* آرگومان intro اجازه می‌دهد برگه‌های اختصاصی محتوای قدیمی page builder را نادیده بگیرند. */
$ee_intro_raw  = array_key_exists('intro', $args)
	? (string) $args['intro']
	: ($ee_page_id && '' !== trim((string) $ee_page->post_excerpt) ? (string) $ee_page->post_excerpt : ($ee_page_id ? (string) $ee_page->post_content : ''));
$ee_intro_raw  = function_exists('excerpt_remove_blocks') ? excerpt_remove_blocks($ee_intro_raw) : $ee_intro_raw;
$ee_intro      = trim(wp_strip_all_tags(strip_shortcodes($ee_intro_raw))); // متن ساده؛ از اجرای دوبارهٔ shortcode/Query Loop برگه جلوگیری می‌کند.
$ee_empty_title = isset($args['empty_title']) ? (string) $args['empty_title'] : __('موردی پیدا نشد', 'evented-edu');
$ee_empty_text  = isset($args['empty_text']) ? (string) $args['empty_text'] : __('عبارت یا فیلتر دیگری را امتحان کنید.', 'evented-edu');
$ee_base_url   = $ee_page_id ? (string) get_permalink($ee_page_id) : (string) get_post_type_archive_link($ee_type);
$ee_base_url   = $ee_base_url ?: home_url('/');
$ee_paged      = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
$ee_search     = isset($_GET['resource_search']) ? sanitize_text_field(wp_unslash($_GET['resource_search'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ee_term_id    = isset($_GET['resource_cat']) ? absint($_GET['resource_cat']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ee_order      = isset($_GET['resource_order']) ? sanitize_key(wp_unslash($_GET['resource_order'])) : 'newest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ee_orders     = array('newest' => __('جدیدترین', 'evented-edu'), 'oldest' => __('قدیمی‌ترین', 'evented-edu'), 'title' => __('عنوان', 'evented-edu'));
if (!isset($ee_orders[$ee_order])) { $ee_order = 'newest'; }

$ee_query_args = array(
	'post_type' => $ee_type, 'post_status' => 'publish', 'posts_per_page' => 12, 'paged' => $ee_paged,
	'ignore_sticky_posts' => true, 'no_found_rows' => false,
);
if ('gallery' === $ee_type) {
	$ee_query_args['meta_query'] = array(array('key' => '_thumbnail_id', 'compare' => 'EXISTS'));
}
if ('' !== $ee_search) { $ee_query_args['s'] = $ee_search; }
if ($ee_term_id && $ee_taxonomy && taxonomy_exists($ee_taxonomy)) {
	$ee_query_args['tax_query'] = array(array('taxonomy' => $ee_taxonomy, 'field' => 'term_id', 'terms' => $ee_term_id));
}
if ('oldest' === $ee_order) { $ee_query_args['order'] = 'ASC'; }
if ('title' === $ee_order) { $ee_query_args['orderby'] = 'title'; $ee_query_args['order'] = 'ASC'; }
$ee_items = post_type_exists($ee_type) ? new WP_Query($ee_query_args) : null;
$ee_terms = ($ee_taxonomy && taxonomy_exists($ee_taxonomy)) ? get_terms(array('taxonomy' => $ee_taxonomy, 'hide_empty' => true, 'number' => 20, 'orderby' => 'count', 'order' => 'DESC')) : array();
$ee_terms = is_wp_error($ee_terms) ? array() : (array) $ee_terms;

/*
 * fallback ضروری: page-videos.php ممکن است از سلسله‌مراتب page-{slug}.php انتخاب شود
 * و در آن حالت is_page_template() روی بعضی نسخه‌ها/تنظیمات false است.
 */
if (!wp_style_is('archive-post', 'enqueued')) {
	$ee_theme_url = defined('PATH_DIR_URL') ? PATH_DIR_URL : get_template_directory_uri();
	wp_enqueue_style('archive-post', $ee_theme_url . '/assets/css/archive-post.css', array('ee-shell'), '1.7.0');
}

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-archive ee-resource-page ee-resource-page-' . $ee_modifier));
get_template_part('template-parts/ee', 'header', array('ee_active' => $ee_active));
?>
<main id="ee-main" class="ee-resource-page-main">
	<section class="ee-resource-hero">
		<div class="ee-wrap ee-resource-hero-in">
			<div class="ee-resource-hero-copy">
				<span class="ee-resource-eyebrow"><svg class="ee-ic" aria-hidden="true"><use href="#i-<?php echo esc_attr($ee_icon); ?>"></use></svg><?php echo esc_html($ee_label); ?></span>
				<h1><?php echo esc_html($ee_title); ?></h1>
				<p><?php echo esc_html($ee_subtitle); ?></p>
				<?php if ('' !== $ee_intro) : ?><div class="ee-resource-intro"><?php echo wpautop(esc_html($ee_intro)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- متن پیش‌تر escape شده است. ?></div><?php endif; ?>
			</div>
			<div class="ee-resource-hero-stat" aria-label="<?php echo esc_attr(sprintf(__('%s مورد منتشرشده', 'evented-edu'), $ee_items instanceof WP_Query ? number_format_i18n($ee_items->found_posts) : 0)); ?>">
				<strong><?php echo esc_html($ee_items instanceof WP_Query ? number_format_i18n($ee_items->found_posts) : 0); ?></strong>
				<span><?php esc_html_e('مورد منتشرشده', 'evented-edu'); ?></span>
			</div>
		</div>
	</section>

	<div class="ee-wrap ee-resource-page-wrap">
		<form class="ee-resource-tools" method="get" action="<?php echo esc_url($ee_base_url); ?>">
			<label class="ee-resource-search" for="eeResourceSearch">
				<svg class="ee-ic" aria-hidden="true"><use href="#i-search"></use></svg>
				<span class="screen-reader-text"><?php echo esc_html(sprintf(__('جستجو در %s', 'evented-edu'), $ee_title)); ?></span>
				<input id="eeResourceSearch" type="search" name="resource_search" value="<?php echo esc_attr($ee_search); ?>" placeholder="<?php echo esc_attr(sprintf(__('جستجو در %s…', 'evented-edu'), $ee_title)); ?>">
			</label>
			<?php if (!empty($ee_terms)) : ?>
				<label class="ee-resource-select"><span><?php esc_html_e('دسته‌بندی', 'evented-edu'); ?></span><select name="resource_cat"><option value="0"><?php esc_html_e('همه دسته‌ها', 'evented-edu'); ?></option><?php foreach ($ee_terms as $term) : ?><option value="<?php echo (int) $term->term_id; ?>"<?php selected($ee_term_id, (int) $term->term_id); ?>><?php echo esc_html($term->name); ?></option><?php endforeach; ?></select></label>
			<?php endif; ?>
			<label class="ee-resource-select"><span><?php esc_html_e('مرتب‌سازی', 'evented-edu'); ?></span><select name="resource_order"><?php foreach ($ee_orders as $value => $text) : ?><option value="<?php echo esc_attr($value); ?>"<?php selected($ee_order, $value); ?>><?php echo esc_html($text); ?></option><?php endforeach; ?></select></label>
			<button class="ee-btn ee-btn-primary" type="submit"><?php esc_html_e('اعمال فیلتر', 'evented-edu'); ?></button>
			<?php if ('' !== $ee_search || $ee_term_id || 'newest' !== $ee_order) : ?><a class="ee-resource-reset" href="<?php echo esc_url($ee_base_url); ?>"><?php esc_html_e('پاک کردن', 'evented-edu'); ?></a><?php endif; ?>
		</form>

		<?php if (!empty($ee_terms)) : ?>
			<nav class="ee-resource-chips" aria-label="<?php esc_attr_e('دسته‌بندی‌ها', 'evented-edu'); ?>">
				<a class="ee-chip-btn<?php echo 0 === $ee_term_id ? ' ee-on' : ''; ?>" href="<?php echo esc_url(add_query_arg(array_filter(array('resource_search' => $ee_search, 'resource_order' => 'newest' !== $ee_order ? $ee_order : null)), $ee_base_url)); ?>"><?php esc_html_e('همه', 'evented-edu'); ?></a>
				<?php foreach ($ee_terms as $term) : ?><a class="ee-chip-btn<?php echo $ee_term_id === (int) $term->term_id ? ' ee-on' : ''; ?>" href="<?php echo esc_url(add_query_arg(array_filter(array('resource_cat' => $term->term_id, 'resource_search' => $ee_search, 'resource_order' => 'newest' !== $ee_order ? $ee_order : null)), $ee_base_url)); ?>"><?php echo esc_html($term->name); ?><span class="ee-chip-count"><?php echo esc_html(number_format_i18n($term->count)); ?></span></a><?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ($ee_items instanceof WP_Query && $ee_items->have_posts()) : ?>
			<div class="ee-resource-grid">
				<?php while ($ee_items->have_posts()) : $ee_items->the_post();
					$ee_id = get_the_ID();
					$item_terms = ($ee_taxonomy && taxonomy_exists($ee_taxonomy)) ? get_the_terms($ee_id, $ee_taxonomy) : array();
					$item_terms = is_wp_error($item_terms) ? array() : (array) $item_terms;
					$item_term = !empty($item_terms) ? $item_terms[0] : null;
					?>
					<article id="post-<?php echo (int) $ee_id; ?>" <?php post_class('ee-resource-card'); ?>>
						<a class="ee-resource-card-media" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(sprintf(__('مشاهدهٔ %s', 'evented-edu'), get_the_title())); ?>">
							<?php if (has_post_thumbnail()) { the_post_thumbnail('medium_large', array('loading' => 'lazy', 'decoding' => 'async')); } else { ?><span class="ee-resource-placeholder"><svg class="ee-ic" aria-hidden="true"><use href="#i-<?php echo esc_attr($ee_icon); ?>"></use></svg></span><?php } ?>
							<?php if ('video' === $ee_modifier) : ?><span class="ee-resource-play"><svg class="ee-ic" aria-hidden="true"><use href="#i-play_arrow"></use></svg></span><?php endif; ?>
							<?php if ($item_term instanceof WP_Term) : ?><span class="ee-resource-card-cat"><?php echo esc_html($item_term->name); ?></span><?php endif; ?>
						</a>
						<div class="ee-resource-card-body">
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?></p>
							<div class="ee-resource-card-meta"><span><svg class="ee-ic" aria-hidden="true"><use href="#i-calendar_month"></use></svg><?php echo esc_html(get_the_date()); ?></span><?php if ('clip' === $ee_type) : ?><span><svg class="ee-ic" aria-hidden="true"><use href="#i-forum"></use></svg><?php echo esc_html(number_format_i18n(get_comments_number())); ?></span><?php endif; ?></div>
							<a class="ee-resource-card-more" href="<?php the_permalink(); ?>"><?php echo esc_html('مشاهده ' . $ee_label); ?><svg class="ee-ic" aria-hidden="true"><use href="#i-arrow_back"></use></svg></a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php echo function_exists('evented_pagination') ? evented_pagination($ee_items) : ''; // phpcs:ignore ?>
			<?php wp_reset_postdata(); ?>
		<?php else : ?>
			<?php
			$ee_empty_args = array('title' => $ee_empty_title, 'text' => $ee_empty_text, 'icon' => $ee_icon);
			if ('' !== $ee_search || $ee_term_id || 'newest' !== $ee_order) {
				$ee_empty_args['actions'] = array(array('label' => __('پاک کردن فیلترها', 'evented-edu'), 'url' => $ee_base_url, 'primary' => true));
			}
			echo function_exists('evented_empty_state') ? evented_empty_state($ee_empty_args) : '<p>' . esc_html($ee_empty_title) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper خروجی امن تولید می‌کند.
			?>
		<?php endif; ?>
	</div>
</main>
<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => $ee_active)); ?>
