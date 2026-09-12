<?php
/**
 * برگهٔ عمومی (page) — پوستهٔ «evented-edu»
 *
 * کارت سفید برگه (عنوان، تصویر شاخص، محتوا) + سایدبار ویجت‌ها.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-page'));
get_template_part('template-parts/ee', 'header', array('ee_active' => ''));

while (have_posts()) :
	the_post();
	?>

	<main class="ee-list-main">
		<div class="ee-wrap ee-list-grid">

			<article <?php post_class('ee-page-card'); ?>>

				<nav class="ee-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
					<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'evented-edu'); ?></a>
					<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-chevron_left"></use></svg>
					<span class="ee-crumb-current"><?php the_title(); ?></span>
				</nav>

				<h1 class="ee-page-title"><?php the_title(); ?></h1>

				<?php if (has_post_thumbnail()) : ?>
					<figure class="ee-page-hero">
						<?php the_post_thumbnail('large', array('loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async')); ?>
					</figure>
				<?php endif; ?>

				<div class="ee-page-body">
					<?php
					the_content();

					wp_link_pages(array(
						'before' => '<div class="ee-page-links">',
						'after'  => '</div>',
					));
					?>
				</div>

				<?php if (comments_open() || get_comments_number()) : ?>
					<div class="ee-page-comments">
						<?php comments_template(); ?>
					</div>
				<?php endif; ?>

			</article>

			<?php get_template_part('template-parts/ee', 'sidebar'); ?>

		</div>
	</main>

<?php endwhile; ?>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => '')); ?>
