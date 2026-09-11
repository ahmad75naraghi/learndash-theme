<?php
/**
 * صفحهٔ ۴۰۴ (یافت نشد) — پوستهٔ «evented-edu»
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-404-page'));
get_template_part('template-parts/ee', 'header', array('ee_active' => ''));

$ee_404_courses = function_exists('evented_latest_courses') ? evented_latest_courses(4) : array();
?>

<main class="ee-list-main">
	<div class="ee-wrap">

		<section class="ee-404">
			<span class="ee-404-code">۴۰۴</span>
			<h1 class="ee-404-title"><?php esc_html_e('صفحه‌ای که دنبالش بودید پیدا نشد', 'evented-edu'); ?></h1>
			<p class="ee-404-text"><?php esc_html_e('ممکن است نشانی تغییر کرده یا محتوا حذف شده باشد. می‌توانید جستجو کنید یا از مسیرهای زیر ادامه دهید.', 'evented-edu'); ?></p>

			<form class="ee-search-inline ee-404-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
				<label class="screen-reader-text" for="ee-404-s"><?php esc_html_e('جستجو', 'evented-edu'); ?></label>
				<input id="ee-404-s" type="search" name="s" placeholder="<?php esc_attr_e('جستجو در دوره‌ها و مقالات...', 'evented-edu'); ?>">
				<button type="submit"><span class="material-symbols-outlined">search</span></button>
			</form>

			<div class="ee-404-links">
				<a class="ee-btn ee-btn-primary" href="<?php echo esc_url(home_url('/')); ?>">
					<span class="material-symbols-outlined ee-ic">home</span> <?php esc_html_e('صفحهٔ اصلی', 'evented-edu'); ?>
				</a>
				<?php
				$ee_courses_page = get_page_by_path('courses');
				$ee_courses_url  = $ee_courses_page instanceof WP_Post
					? (string) get_permalink($ee_courses_page)
					: (string) get_post_type_archive_link('sfwd-courses');
				if ($ee_courses_url) :
					?>
					<a class="ee-btn ee-btn-ghost" href="<?php echo esc_url($ee_courses_url); ?>">
						<span class="material-symbols-outlined ee-ic">school</span> <?php esc_html_e('همهٔ دوره‌ها', 'evented-edu'); ?>
					</a>
				<?php endif; ?>
			</div>
		</section>

		<?php if (!empty($ee_404_courses)) : ?>
			<section class="ee-404-more">
				<h2 class="ee-w-title"><span class="material-symbols-outlined ee-ic">new_releases</span> <?php esc_html_e('شاید این دوره‌ها برایتان مفید باشد', 'evented-edu'); ?></h2>
				<?php
				get_template_part('template-parts/lms/course', 'grid', array('ee_posts' => $ee_404_courses));
				?>
			</section>
		<?php endif; ?>

	</div>
</main>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => '')); ?>
