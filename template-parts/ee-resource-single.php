<?php
/**
 * نمای مشترک کتاب، ویدئو و گالری.
 *
 * @package evented-edu
 */
defined('ABSPATH') || exit;

$ee_type       = isset($args['post_type']) ? sanitize_key($args['post_type']) : get_post_type();
$ee_active     = isset($args['active']) ? sanitize_key($args['active']) : '';
$ee_label      = isset($args['label']) ? (string) $args['label'] : 'محتوا';
$ee_plural     = isset($args['plural']) ? (string) $args['plural'] : $ee_label;
$ee_taxonomy   = isset($args['taxonomy']) ? sanitize_key($args['taxonomy']) : '';
$ee_icon       = isset($args['icon']) ? sanitize_key($args['icon']) : 'article';
$ee_comments   = !empty($args['comments']);
$ee_archive    = function_exists('evented_nav_url') ? evented_nav_url($ee_active) : get_post_type_archive_link($ee_type);
$ee_archive    = $ee_archive ?: home_url('/');

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-single ee-resource-single ee-single-' . $ee_type));
get_template_part('template-parts/ee', 'header', array('ee_active' => $ee_active));
?>
<main id="ee-main" class="ee-single-main">
	<?php while (have_posts()) : the_post();
		$ee_id       = get_the_ID();
		$ee_terms    = $ee_taxonomy && taxonomy_exists($ee_taxonomy) ? get_the_terms($ee_id, $ee_taxonomy) : array();
		$ee_terms    = is_wp_error($ee_terms) ? array() : (array) $ee_terms;
		$ee_term     = !empty($ee_terms) ? $ee_terms[0] : null;
		$ee_date     = function_exists('evented_post_date') ? evented_post_date($ee_id) : get_the_date();
		$ee_shares   = function_exists('evented_share_links') ? evented_share_links((string) get_permalink(), (string) get_the_title()) : array();
		$ee_related_args = array(
			'post_type' => $ee_type, 'post_status' => 'publish', 'posts_per_page' => 4, 'post__not_in' => array($ee_id),
			'no_found_rows' => true, 'ignore_sticky_posts' => true,
		);
		if ($ee_term instanceof WP_Term) {
			$ee_related_args['tax_query'] = array(array('taxonomy' => $ee_taxonomy, 'field' => 'term_id', 'terms' => $ee_term->term_id));
		}
		$ee_related = new WP_Query($ee_related_args);
		?>
		<div class="ee-wrap ee-single-grid">
			<article id="post-<?php echo esc_attr($ee_id); ?>" <?php post_class('ee-post ee-resource-post'); ?>>
				<nav class="ee-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
					<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'evented-edu'); ?></a>
					<svg class="ee-ic" aria-hidden="true"><use href="#i-chevron_left"></use></svg>
					<a href="<?php echo esc_url($ee_archive); ?>"><?php echo esc_html($ee_plural); ?></a>
					<?php if ($ee_term instanceof WP_Term) : ?>
						<svg class="ee-ic" aria-hidden="true"><use href="#i-chevron_left"></use></svg>
						<a href="<?php echo esc_url(get_term_link($ee_term)); ?>"><?php echo esc_html($ee_term->name); ?></a>
					<?php endif; ?>
					<svg class="ee-ic" aria-hidden="true"><use href="#i-chevron_left"></use></svg>
					<span class="ee-crumb-current"><?php the_title(); ?></span>
				</nav>

				<header class="ee-resource-head">
					<span class="ee-resource-type"><svg class="ee-ic" aria-hidden="true"><use href="#i-<?php echo esc_attr($ee_icon); ?>"></use></svg><?php echo esc_html($ee_label); ?></span>
					<h1 class="ee-post-title"><?php the_title(); ?></h1>
					<div class="ee-post-meta">
						<?php if ($ee_term instanceof WP_Term) : ?><a class="ee-meta-cat" href="<?php echo esc_url(get_term_link($ee_term)); ?>"><?php echo esc_html($ee_term->name); ?></a><?php endif; ?>
						<span class="ee-meta-item"><svg class="ee-ic" aria-hidden="true"><use href="#i-calendar_month"></use></svg><?php echo esc_html($ee_date); ?></span>
						<span class="ee-meta-item"><svg class="ee-ic" aria-hidden="true"><use href="#i-person"></use></svg><?php echo esc_html(get_the_author()); ?></span>
					</div>
				</header>

				<?php if (has_post_thumbnail()) : ?>
					<figure class="ee-post-hero"><?php the_post_thumbnail('large', array('loading' => 'eager', 'fetchpriority' => 'high')); ?></figure>
				<?php endif; ?>

				<div class="ee-post-content"><?php the_content(); ?></div>
				<?php wp_link_pages(array('before' => '<nav class="ee-page-links">', 'after' => '</nav>')); ?>

				<?php if (!empty($ee_shares)) : ?>
					<div class="ee-share-block">
						<span class="ee-share-label"><?php esc_html_e('اشتراک‌گذاری این محتوا', 'evented-edu'); ?></span>
						<div class="ee-share-row">
							<?php foreach ($ee_shares as $ee_share) : ?><a class="ee-share-btn" style="background:<?php echo esc_attr($ee_share['color']); ?>" href="<?php echo esc_url($ee_share['url']); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr($ee_share['label']); ?>"><?php echo $ee_share['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a><?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<nav class="ee-post-nav" aria-label="<?php esc_attr_e('محتوای قبلی و بعدی', 'evented-edu'); ?>">
					<span><?php previous_post_link('%link', '→ %title', false, '', $ee_taxonomy); ?></span>
					<span><?php next_post_link('%link', '%title ←', false, '', $ee_taxonomy); ?></span>
				</nav>

				<?php if ($ee_comments && (comments_open() || get_comments_number())) { comments_template(); } ?>
			</article>

			<aside class="ee-side" aria-label="<?php echo esc_attr('مطالب مرتبط ' . $ee_plural); ?>">
				<section class="ee-widget">
					<h2 class="ee-w-title"><svg class="ee-ic" aria-hidden="true"><use href="#i-<?php echo esc_attr($ee_icon); ?>"></use></svg><?php echo esc_html($ee_plural . ' مرتبط'); ?></h2>
					<div class="ee-mini-list">
						<?php if ($ee_related->have_posts()) : while ($ee_related->have_posts()) : $ee_related->the_post(); ?>
							<a class="ee-mini" href="<?php the_permalink(); ?>">
								<span class="ee-mini-txt"><strong><?php the_title(); ?></strong><span class="ee-mini-date"><?php echo esc_html(get_the_date()); ?></span></span>
								<?php if (has_post_thumbnail()) { the_post_thumbnail('thumbnail', array('class' => 'ee-mini-th', 'loading' => 'lazy')); } ?>
							</a>
						<?php endwhile; else : ?><p class="ee-empty-inline"><?php esc_html_e('مورد مرتبط دیگری پیدا نشد.', 'evented-edu'); ?></p><?php endif; wp_reset_postdata(); ?>
					</div>
				</section>
				<a class="ee-btn ee-btn-primary" href="<?php echo esc_url($ee_archive); ?>"><?php echo esc_html('مشاهده همهٔ ' . $ee_plural); ?></a>
			</aside>
		</div>
	<?php endwhile; ?>
</main>
<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => $ee_active)); ?>
