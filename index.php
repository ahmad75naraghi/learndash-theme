<?php
/**
 * قالب بازگشتی (Fallback) — پوستهٔ «evented-edu»
 *
 * برای هر نمای بدون قالب اختصاصی: اگر تک‌محتوا باشد کارت محتوا و در غیر این
 * صورت بدنهٔ مشترک آرشیو (همان که archive/home/search استفاده می‌کنند).
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-index'));

if (is_singular()) {
	get_template_part('template-parts/ee', 'header', array('ee_active' => ''));
	?>

	<main class="ee-list-main">
		<div class="ee-wrap ee-list-grid">

			<?php
			while (have_posts()) :
				the_post();
				?>

				<article <?php post_class('ee-page-card'); ?>>
					<nav class="ee-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
						<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'evented-edu'); ?></a>
						<span class="material-symbols-outlined ee-ic">chevron_left</span>
						<span class="ee-crumb-current"><?php the_title(); ?></span>
					</nav>

					<h1 class="ee-page-title"><?php the_title(); ?></h1>

					<?php if (has_post_thumbnail()) : ?>
						<figure class="ee-page-hero"><?php the_post_thumbnail('large', array('decoding' => 'async')); ?></figure>
					<?php endif; ?>

					<div class="ee-page-body"><?php the_content(); ?></div>
				</article>

			<?php endwhile; ?>

			<?php get_template_part('template-parts/ee', 'sidebar'); ?>

		</div>
	</main>

	<?php
} else {
	get_template_part('template-parts/ee', 'header', array('ee_active' => 'articles'));

	get_template_part('template-parts/ee', 'archive-main', array(
		'ee_title'    => is_archive() ? (string) get_the_archive_title() : '',
		'ee_subtitle' => '',
	));
}

get_template_part('template-parts/ee', 'footer', array('ee_active' => is_singular() ? '' : 'articles'));
