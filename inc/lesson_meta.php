<?php
/**
 * متاباکس «رسانه و پیوست‌های درس» برای sfwd-lessons و sfwd-topic.
 *
 * کلیدهای متا (همان‌هایی که evented_lesson_media() می‌خواند):
 *   _lesson_audio_url   string  آدرس فایل صوتی درس (mp3/ogg …)
 *   _lesson_attachments int[]   شناسهٔ پیوست‌های رسانه (فایل‌های دانلودی)
 *
 * @package evented-edu
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('add_meta_boxes', function () {
	foreach (array('sfwd-lessons', 'sfwd-topic') as $pt) {
		add_meta_box('evented_lesson_media', 'رسانه و پیوست‌های درس (پوسته)', 'evented_lesson_media_metabox', $pt, 'normal', 'default');
	}
});

/**
 * @param WP_Post $post
 */
function evented_lesson_media_metabox($post)
{
	wp_nonce_field('evented_lesson_media_save', 'evented_lesson_media_nonce');
	$audio = (string) get_post_meta($post->ID, '_lesson_audio_url', true);
	$files = get_post_meta($post->ID, '_lesson_attachments', true);
	$files = is_array($files) ? array_filter(array_map('intval', $files)) : array();
	?>
	<p>
		<label for="ee-lesson-audio"><strong>آدرس فایل صوتی درس</strong></label><br>
		<input type="url" id="ee-lesson-audio" name="ee_lesson_audio_url" class="large-text" dir="ltr" value="<?php echo esc_url($audio); ?>" placeholder="https://…/lesson.mp3">
		<button type="button" class="button ee-pick-audio">انتخاب از رسانه</button>
	</p>
	<p><strong>پیوست‌های قابل دانلود</strong></p>
	<ul id="ee-lesson-files" class="ee-lesson-files">
		<?php foreach ($files as $fid) : ?>
			<li>
				<input type="hidden" name="ee_lesson_attachments[]" value="<?php echo (int) $fid; ?>">
				<span class="dashicons dashicons-media-default"></span>
				<?php echo esc_html(get_the_title($fid)); ?>
				<a href="#" class="ee-remove-file" style="color:#b32d2e;margin-inline-start:.5rem">حذف</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<button type="button" class="button ee-add-files">افزودن فایل</button>
	<script>
	(function($){
		$('.ee-pick-audio').on('click',function(e){e.preventDefault();var f=wp.media({title:'انتخاب فایل صوتی',library:{type:'audio'},multiple:false});f.on('select',function(){$('#ee-lesson-audio').val(f.state().get('selection').first().toJSON().url)});f.open()});
		$('.ee-add-files').on('click',function(e){e.preventDefault();var f=wp.media({title:'انتخاب پیوست',multiple:true});f.on('select',function(){f.state().get('selection').each(function(a){a=a.toJSON();$('#ee-lesson-files').append('<li><input type="hidden" name="ee_lesson_attachments[]" value="'+a.id+'"><span class="dashicons dashicons-media-default"></span> '+$('<i>').text(a.title).html()+' <a href="#" class="ee-remove-file" style="color:#b32d2e;margin-inline-start:.5rem">حذف</a></li>')})});f.open()});
		$('#ee-lesson-files').on('click','.ee-remove-file',function(e){e.preventDefault();$(this).closest('li').remove()});
	})(jQuery);
	</script>
	<?php
}

add_action('admin_enqueue_scripts', function ($hook) {
	if (in_array($hook, array('post.php', 'post-new.php'), true)) {
		$screen = get_current_screen();
		if ($screen && in_array($screen->post_type, array('sfwd-lessons', 'sfwd-topic'), true)) {
			wp_enqueue_media();
		}
	}
});

add_action('save_post', function ($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (!isset($_POST['evented_lesson_media_nonce']) || !wp_verify_nonce($_POST['evented_lesson_media_nonce'], 'evented_lesson_media_save')) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}
	$audio = isset($_POST['ee_lesson_audio_url']) ? esc_url_raw(wp_unslash($_POST['ee_lesson_audio_url'])) : '';
	if ('' !== $audio) {
		update_post_meta($post_id, '_lesson_audio_url', $audio);
	} else {
		delete_post_meta($post_id, '_lesson_audio_url');
	}
	$files = isset($_POST['ee_lesson_attachments']) && is_array($_POST['ee_lesson_attachments'])
		? array_values(array_filter(array_map('intval', $_POST['ee_lesson_attachments'])))
		: array();
	$files = array_values(array_filter($files, function ($id) {
		return 'attachment' === get_post_type($id);
	}));
	if ($files) {
		update_post_meta($post_id, '_lesson_attachments', $files);
	} else {
		delete_post_meta($post_id, '_lesson_attachments');
	}
});
