<?php
/**
 * قالب نمایش دیدگاه‌ها (Comments)
 *
 * پوستهٔ جدید evented-edu؛ در single.php داخل `.ee-comments` بارگذاری می‌شود.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/* نوشتهٔ رمزدار: نه فرم، نه فهرست */
if (post_password_required()) {
	return;
}
?>
<div id="comments" class="ee-comments-wrap">

	<?php if (function_exists('evented_rating_summary_html') && function_exists('evented_rating_form_enabled') && evented_rating_form_enabled() && evented_course_rating(get_the_ID())['count'] > 0) : ?>
		<h3 class="ee-w-title ee-comments-title"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-star"></use></svg><?php esc_html_e('امتیاز خوانندگان', 'evented-edu'); ?></h3>
		<?php echo evented_rating_summary_html(get_the_ID()); // phpcs:ignore ?>
	<?php endif; ?>

	<?php if (have_comments()) : ?>
		<h3 class="ee-w-title ee-comments-title">
			<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-forum"></use></svg>
			<?php
			$ee_comment_count = (int) get_comments_number();
			/* translators: %s: تعداد دیدگاه */
			echo esc_html(sprintf(_n('دیدگاه‌ها (%s)', 'دیدگاه‌ها (%s)', $ee_comment_count, 'evented-edu'), number_format_i18n($ee_comment_count)));
			?>
		</h3>

		<ol class="ee-comment-list">
			<?php
			wp_list_comments(array(
				'style'       => 'ol',
				'short_ping'  => true,
				'avatar_size' => 44,
			));
			?>
		</ol>

		<?php
		the_comments_navigation(array(
			'before' => '<nav class="ee-comment-nav" aria-label="' . esc_attr__('صفحه‌بندی دیدگاه‌ها', 'evented-edu') . '">',
			'after'  => '</nav>',
		));
		?>
	<?php endif; ?>

	<?php if (!comments_open() && get_comments_number() && post_type_supports(get_post_type(), 'comments')) : ?>
		<p class="ee-comments-closed"><?php esc_html_e('ثبت دیدگاه برای این نوشته بسته شده است.', 'evented-edu'); ?></p>
	<?php endif; ?>

	<?php
	comment_form(array(
		'class_form'         => 'ee-comment-form',
		/* عنوان بدون HTML نگه داشته می‌شود؛ آیکن داخل wrapper (که escape نمی‌شود) است */
		'title_reply_before' => '<h3 id="reply-title" class="ee-w-title ee-reply-title"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-edit_note"></use></svg>',
		'title_reply'        => 'دیدگاه خود را بنویسید',
		'title_reply_after'  => '</h3>',
		'title_reply_to'     => 'پاسخ به %s',
		'comment_field'      => '<p class="comment-form-comment"><label class="screen-reader-text" for="comment">' . esc_html__('دیدگاه', 'evented-edu') . '</label><textarea id="comment" name="comment" rows="6" placeholder="' . esc_attr__('اینجا بنویسید...', 'evented-edu') . '" required></textarea></p>',
		'label_submit'       => 'ارسال دیدگاه',
		'submit_button'      => '<button type="submit" id="%2$s" class="ee-comment-submit">%4$s <svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-send"></use></svg></button>',
		'submit_field'       => '<div class="ee-comment-submit-row">%1$s %2$s</div>',
		'comment_notes_before' => '<p class="ee-comment-notes">' . esc_html__('نشانی ایمیل شما منتشر نخواهد شد. بخش‌های موردنیاز علامت‌گذاری شده‌اند *', 'evented-edu') . '</p>',
		'comment_notes_after'  => '',
	));
	?>

</div>
