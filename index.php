<?php
/**
 * قالب بازگشتی (Fallback) استاندارد.
 *
 * این فایل برای هر صفحه‌ای استفاده می‌شود که قالب اختصاصی ندارد
 * (مثل بایگانی پست‌ها، تکی درس/آزمون لرن‌دش و…).
 * توجه: اگر صفحهٔ اصلی از front-page.php استفاده می‌کند، این قالب اجرا نمی‌شود.
 */

defined('ABSPATH') || exit;

get_header();
?>

<main class="page-main container" style="padding: 40px 0;">
	<?php if (have_posts()) : ?>

		<?php if (is_home() && !is_front_page()) : ?>
			<header class="page-header">
				<h1 class="page-title"><?php single_post_title(); ?></h1>
			</header>
		<?php elseif (is_archive()) : ?>
			<header class="page-header">
				<h1 class="page-title"><?php the_archive_title(); ?></h1>
				<?php the_archive_description('<div class="archive-description">', '</div>'); ?>
			</header>
		<?php elseif (is_search()) : ?>
			<header class="page-header">
				<h1 class="page-title">
					<?php
					/* translators: %s: search query */
					printf(esc_html__('نتایج جستجو برای: %s', 'evented-edu'), '<span>' . get_search_query() . '</span>');
					?>
				</h1>
			</header>
		<?php endif; ?>

		<?php
		// حلقهٔ اصلی
		while (have_posts()) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class('entry'); ?>>
				<h2 class="entry-title">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h2>

				<div class="entry-meta" style="color:#666; font-size:14px; margin:8px 0;">
					<?php
					printf(
						esc_html__('انتشار: %s', 'evented-edu'),
						esc_html(get_the_date())
					);
					?>
				</div>

				<div class="entry-content">
					<?php the_excerpt(); ?>
				</div>

				<p>
					<a class="btn-primary" href="<?php the_permalink(); ?>">
						<?php esc_html_e('ادامه مطلب', 'evented-edu'); ?>
					</a>
				</p>
			</article>
			<hr style="border:none; border-top:1px solid #eee; margin:24px 0;">
		<?php endwhile; ?>

		<nav class="pagination" aria-label="<?php esc_attr_e('صفحه‌بندی', 'evented-edu'); ?>">
			<?php
			echo paginate_links(array(
				'prev_text' => '→',
				'next_text' => '←',
			));
			?>
		</nav>

	<?php else : ?>
		<section class="no-results">
			<h2><?php esc_html_e('موردی یافت نشد', 'evented-edu'); ?></h2>
			<p><?php esc_html_e('لطفاً عبارت دیگری را جستجو کنید.', 'evented-edu'); ?></p>
			<?php get_search_form(); ?>
		</section>
	<?php endif; ?>
</main>

<?php
get_footer();
