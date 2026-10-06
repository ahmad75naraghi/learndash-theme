<?php
/** آرشیو حرفه‌ای دانلودهای WPDM در مسیر /download/. @package evented-edu */
defined('ABSPATH') || exit;
$page = get_queried_object();
$page_id = $page instanceof WP_Post ? (int) $page->ID : 0;
$base_url = $page_id ? (string) get_permalink($page_id) : home_url('/download/');
$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
$search = isset($_GET['download_search']) ? sanitize_text_field(wp_unslash($_GET['download_search'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$order = isset($_GET['download_order']) ? sanitize_key(wp_unslash($_GET['download_order'])) : 'newest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$orders = array('newest' => 'جدیدترین', 'oldest' => 'قدیمی‌ترین', 'title' => 'عنوان');
if (!isset($orders[$order])) { $order = 'newest'; }
$args = array(
	'post_type' => 'wpdmpro', 'post_status' => 'publish', 'posts_per_page' => 12,
	'paged' => $paged, 'ignore_sticky_posts' => true, 'no_found_rows' => false,
);
if ($search) { $args['s'] = $search; }
if ('oldest' === $order) { $args['order'] = 'ASC'; }
if ('title' === $order) { $args['orderby'] = 'title'; $args['order'] = 'ASC'; }
$downloads = post_type_exists('wpdmpro') ? new WP_Query($args) : null;
if (!wp_style_is('archive-post', 'enqueued')) {
	wp_enqueue_style('archive-post', PATH_DIR_URL . '/assets/css/archive-post.css', array('ee-shell'), '1.9.1');
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
			<label class="ee-resource-select"><span>مرتب‌سازی</span><select name="download_order"><?php foreach ($orders as $value => $label) : ?><option value="<?php echo esc_attr($value); ?>"<?php selected($order, $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
			<button class="ee-btn ee-btn-primary" type="submit">اعمال فیلتر</button>
			<?php if ($search || 'newest' !== $order) : ?><a class="ee-resource-reset" href="<?php echo esc_url($base_url); ?>">پاک کردن</a><?php endif; ?>
		</form>
		<?php if ($downloads instanceof WP_Query && $downloads->have_posts()) : ?>
			<div class="ee-resource-grid ee-download-grid">
			<?php while ($downloads->have_posts()) : $downloads->the_post();
				$data = evented_download_data(get_the_ID());
				$extensions = array_values(array_unique(array_filter(wp_list_pluck($data['files'], 'extension'))));
				$type_label = 1 === count($extensions) ? strtoupper($extensions[0]) : (count($extensions) > 1 ? 'چندفرمتی' : 'فایل');
				$excerpt = trim((string) get_the_excerpt());
				?>
				<article <?php post_class('ee-resource-card ee-download-card'); ?>>
					<div class="ee-download-card-top">
						<a class="ee-download-card-icon" href="<?php the_permalink(); ?>" aria-label="مشاهدهٔ جزئیات <?php echo esc_attr(get_the_title()); ?>"><svg class="ee-ic" aria-hidden="true"><use href="#i-download_for_offline"></use></svg></a>
						<div class="ee-download-card-heading"><span class="ee-download-file-kind"><?php echo esc_html($type_label); ?></span><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2></div>
						<span class="ee-download-file-count"><?php echo esc_html(number_format_i18n($data['file_count'])); ?> فایل</span>
					</div>
					<div class="ee-resource-card-body">
						<?php if ($excerpt) : ?><p><?php echo esc_html(wp_trim_words($excerpt, 22)); ?></p><?php endif; ?>
						<div class="ee-download-card-stats">
							<?php if ($data['size']) : ?><span><svg class="ee-ic" aria-hidden="true"><use href="#i-download"></use></svg><?php echo esc_html($data['size']); ?></span><?php endif; ?>
							<span><svg class="ee-ic" aria-hidden="true"><use href="#i-calendar_month"></use></svg><?php echo esc_html(get_the_date()); ?></span>
							<span><svg class="ee-ic" aria-hidden="true"><use href="#i-download_for_offline"></use></svg><?php echo esc_html(number_format_i18n($data['downloads'])); ?> دانلود</span>
						</div>
						<div class="ee-download-card-actions"><a class="ee-resource-card-more" href="<?php the_permalink(); ?>">مشاهدهٔ جزئیات<svg class="ee-ic" aria-hidden="true"><use href="#i-arrow_back"></use></svg></a><?php if (1 === $data['file_count']) : ?><a class="ee-btn ee-btn-primary ee-download-direct" href="<?php echo esc_url($data['files'][0]['url']); ?>"><svg class="ee-ic" aria-hidden="true"><use href="#i-download"></use></svg>دانلود مستقیم</a><?php endif; ?></div>
					</div>
				</article>
			<?php endwhile; ?>
			</div><?php echo function_exists('evented_pagination') ? evented_pagination($downloads) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php wp_reset_postdata(); ?>
		<?php else : echo function_exists('evented_empty_state') ? evented_empty_state(array('title' => 'فایلی پیدا نشد', 'text' => 'عبارت یا دستهٔ دیگری را امتحان کنید.', 'icon' => 'download_for_offline')) : '<p>فایلی پیدا نشد.</p>'; endif; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</main>
<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'downloads')); ?>
