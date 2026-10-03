<?php
/** نمای تکی مستقل دانلودهای WPDM. @package evented-edu */
defined('ABSPATH') || exit;
get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-single ee-download-single'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'downloads'));
?>
<main id="ee-main" class="ee-single-main">
<?php while (have_posts()) : the_post(); $id = get_the_ID(); $data = evented_download_data($id); $shares = evented_share_links(get_permalink(), get_the_title()); ?>
	<div class="ee-wrap ee-download-single-wrap">
		<article <?php post_class('ee-post ee-download-post'); ?>>
			<nav class="ee-crumb" aria-label="مسیر صفحه"><a href="<?php echo esc_url(home_url('/')); ?>">خانه</a><svg class="ee-ic" aria-hidden="true"><use href="#i-chevron_left"></use></svg><a href="<?php echo esc_url(home_url('/download/')); ?>">دانلودها</a><svg class="ee-ic" aria-hidden="true"><use href="#i-chevron_left"></use></svg><span><?php the_title(); ?></span></nav>
			<header class="ee-resource-head"><span class="ee-resource-type"><svg class="ee-ic" aria-hidden="true"><use href="#i-download_for_offline"></use></svg>دانلود</span><h1 class="ee-post-title"><?php the_title(); ?></h1><div class="ee-post-meta"><span class="ee-meta-item"><svg class="ee-ic" aria-hidden="true"><use href="#i-calendar_month"></use></svg><?php echo esc_html(get_the_date()); ?></span><?php if ($data['version']) : ?><span class="ee-meta-item">نسخه <?php echo esc_html($data['version']); ?></span><?php endif; ?><span class="ee-meta-item"><?php echo esc_html(number_format_i18n($data['downloads'])); ?> دانلود</span></div></header>
			<div class="ee-download-single-grid">
				<?php if ($data['preview']) : ?><figure class="ee-download-cover"><img src="<?php echo esc_url($data['preview']); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="eager" fetchpriority="high"></figure><?php endif; ?>
				<section class="ee-download-box" aria-labelledby="eeDownloadFiles"><header><span><svg class="ee-ic" aria-hidden="true"><use href="#i-download"></use></svg></span><div><h2 id="eeDownloadFiles">فایل‌های قابل دانلود</h2><p><?php echo esc_html(sprintf('%s فایل آمادهٔ دریافت', number_format_i18n($data['file_count']))); ?></p></div></header>
					<?php if ($data['files']) : ?><div class="ee-download-files"><?php foreach ($data['files'] as $index => $file) : ?><div class="ee-download-file"><span class="ee-download-file-type"><?php echo esc_html($file['extension'] ?: 'FILE'); ?></span><span class="ee-download-file-copy"><strong><?php echo esc_html($file['label']); ?></strong><small><?php echo esc_html($file['size'] ? evented_download_size_label($file['size']) : ($data['size'] ?: 'فایل دانلودی')); ?></small></span><a class="ee-btn ee-btn-primary" href="<?php echo esc_url($file['url']); ?>"><svg class="ee-ic" aria-hidden="true"><use href="#i-download"></use></svg>دانلود</a></div><?php endforeach; ?></div><?php else : ?><p class="ee-empty-inline">فایلی برای این مورد پیدا نشد. متای اصلی نوشته حفظ شده و می‌توانید مسیر فایل را در مدیریت بررسی کنید.</p><?php endif; ?>
				</section>
			</div>
			<?php if (trim((string) get_the_content())) : ?><div class="ee-post-content"><?php the_content(); ?></div><?php endif; ?>
			<?php if ($shares) : ?><div class="ee-share-block"><span class="ee-share-label">اشتراک‌گذاری این دانلود</span><div class="ee-share-row"><?php foreach ($shares as $share) : ?><a class="ee-share-btn" style="background:<?php echo esc_attr($share['color']); ?>" href="<?php echo esc_url($share['url']); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr($share['label']); ?>"><?php echo $share['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a><?php endforeach; ?></div></div><?php endif; ?>
		</article>
	</div>
<?php endwhile; ?>
</main>
<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'downloads')); ?>
