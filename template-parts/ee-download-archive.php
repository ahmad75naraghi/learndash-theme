<?php
/** آرشیو حرفه‌ای دانلودهای WPDM در مسیر /download/. @package evented-edu */
defined('ABSPATH') || exit;
$page = get_queried_object();
$page_id = $page instanceof WP_Post ? (int) $page->ID : 0;
$base_url = $page_id ? (string) get_permalink($page_id) : home_url('/download/');
$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
$search = isset($_GET['download_search']) ? sanitize_text_field(wp_unslash($_GET['download_search'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$term_id = isset($_GET['download_cat']) ? absint($_GET['download_cat']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$order = isset($_GET['download_order']) ? sanitize_key(wp_unslash($_GET['download_order'])) : 'newest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$orders = array('newest' => 'جدیدترین', 'oldest' => 'قدیمی‌ترین', 'title' => 'عنوان');
if (!isset($orders[$order])) { $order = 'newest'; }
$args = array(
	'post_type' => 'wpdmpro', 'post_status' => 'publish', 'posts_per_page' => 12,
	'paged' => $paged, 'ignore_sticky_posts' => true, 'no_found_rows' => false,
);
if ($search) { $args['s'] = $search; }
if ($term_id && taxonomy_exists('wpdmcategory')) {
	$args['tax_query'] = array(array('taxonomy' => 'wpdmcategory', 'field' => 'term_id', 'terms' => $term_id));
}
if ('oldest' === $order) { $args['order'] = 'ASC'; }
if ('title' === $order) { $args['orderby'] = 'title'; $args['order'] = 'ASC'; }
$downloads = post_type_exists('wpdmpro') ? new WP_Query($args) : null;
$terms = taxonomy_exists('wpdmcategory') ? get_terms(array('taxonomy' => 'wpdmcategory', 'hide_empty' => true, 'number' => 30, 'orderby' => 'count', 'order' => 'DESC')) : array();
$terms = is_wp_error($terms) ? array() : (array) $terms;
if (!wp_style_is('archive-post', 'enqueued')) {
	wp_enqueue_style('archive-post', PATH_DIR_URL . '/assets/css/archive-post.css', array('ee-shell'), '1.7.0');
}
get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-archive ee-download-archive'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'downloads'));
?>
<main id="ee-main" class="ee-resource-page-main ee-download-page">
	<section class="ee-resource-hero"><div class="ee-wrap ee-resource-hero-in">
		<div class="ee-resource-hero-copy"><span class="ee-resource-eyebrow"><svg class="ee-ic" aria-hidden="true"><use href="#i-download_for_offline"></use></svg>مرکز دانلود</span><h1>دانلودها</h1><p>فایل‌ها و منابع قابل دریافت شمیم؛ مستقل از افزونه و با حفظ اطلاعات قبلی</p></div>
		<div class="ee-resource-hero-stat"><strong><?php echo esc_html($downloads instanceof WP_Query ? number_format_i18n($downloads->found_posts) : 0); ?></strong><span>فایل منتشرشده</span></div>
	</div></section>
	<div class="ee-wrap ee-resource-page-wrap">
		<form class="ee-resource-tools" method="get" action="<?php echo esc_url($base_url); ?>">
			<label class="ee-resource-search"><svg class="ee-ic" aria-hidden="true"><use href="#i-search"></use></svg><span class="screen-reader-text">جستجو در دانلودها</span><input type="search" name="download_search" value="<?php echo esc_attr($search); ?>" placeholder="جستجو در دانلودها…"></label>
			<?php if ($terms) : ?><label class="ee-resource-select"><span>دسته‌بندی</span><select name="download_cat"><option value="0">همهٔ دسته‌ها</option><?php foreach ($terms as $term) : ?><option value="<?php echo (int) $term->term_id; ?>"<?php selected($term_id, (int) $term->term_id); ?>><?php echo esc_html($term->name); ?></option><?php endforeach; ?></select></label><?php endif; ?>
			<label class="ee-resource-select"><span>مرتب‌سازی</span><select name="download_order"><?php foreach ($orders as $value => $label) : ?><option value="<?php echo esc_attr($value); ?>"<?php selected($order, $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
			<button class="ee-btn ee-btn-primary" type="submit">اعمال فیلتر</button>
			<?php if ($search || $term_id || 'newest' !== $order) : ?><a class="ee-resource-reset" href="<?php echo esc_url($base_url); ?>">پاک کردن</a><?php endif; ?>
		</form>
		<?php if ($terms) : ?><nav class="ee-resource-chips" aria-label="دسته‌های دانلود"><a class="ee-chip-btn<?php echo !$term_id ? ' ee-on' : ''; ?>" href="<?php echo esc_url($base_url); ?>">همه</a><?php foreach ($terms as $term) : ?><a class="ee-chip-btn<?php echo $term_id === (int) $term->term_id ? ' ee-on' : ''; ?>" href="<?php echo esc_url(add_query_arg('download_cat', $term->term_id, $base_url)); ?>"><?php echo esc_html($term->name); ?><span class="ee-chip-count"><?php echo esc_html(number_format_i18n($term->count)); ?></span></a><?php endforeach; ?></nav><?php endif; ?>
		<?php if ($downloads instanceof WP_Query && $downloads->have_posts()) : ?>
			<div class="ee-resource-grid ee-download-grid">
			<?php while ($downloads->have_posts()) : $downloads->the_post(); $data = evented_download_data(get_the_ID()); $cats = get_the_terms(get_the_ID(), 'wpdmcategory'); $cat = !is_wp_error($cats) && $cats ? reset($cats) : null; ?>
				<article <?php post_class('ee-resource-card ee-download-card'); ?>>
					<a class="ee-resource-card-media" href="<?php the_permalink(); ?>" aria-label="مشاهدهٔ <?php echo esc_attr(get_the_title()); ?>"><?php if ($data['preview']) { ?><img src="<?php echo esc_url($data['preview']); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy" decoding="async"><?php } else { ?><span class="ee-resource-placeholder"><svg class="ee-ic" aria-hidden="true"><use href="#i-download_for_offline"></use></svg></span><?php } ?><?php if ($cat instanceof WP_Term) : ?><span class="ee-resource-card-cat"><?php echo esc_html($cat->name); ?></span><?php endif; ?></a>
					<div class="ee-resource-card-body"><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
						<div class="ee-download-card-stats"><?php if ($data['size']) : ?><span><svg class="ee-ic" aria-hidden="true"><use href="#i-download"></use></svg><?php echo esc_html($data['size']); ?></span><?php endif; ?><span><?php echo esc_html(number_format_i18n($data['file_count'])); ?> فایل</span><span><?php echo esc_html(number_format_i18n($data['downloads'])); ?> دانلود</span></div>
						<div class="ee-download-card-actions"><a class="ee-resource-card-more" href="<?php the_permalink(); ?>">جزئیات<svg class="ee-ic" aria-hidden="true"><use href="#i-arrow_back"></use></svg></a><?php if (1 === $data['file_count']) : ?><a class="ee-btn ee-btn-primary ee-download-direct" href="<?php echo esc_url($data['files'][0]['url']); ?>">دانلود</a><?php endif; ?></div>
					</div>
				</article>
			<?php endwhile; ?>
			</div><?php echo function_exists('evented_pagination') ? evented_pagination($downloads) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php wp_reset_postdata(); ?>
		<?php else : echo function_exists('evented_empty_state') ? evented_empty_state(array('title' => 'فایلی پیدا نشد', 'text' => 'عبارت یا دستهٔ دیگری را امتحان کنید.', 'icon' => 'download_for_offline')) : '<p>فایلی پیدا نشد.</p>'; endif; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</main>
<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'downloads')); ?>
