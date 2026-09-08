<?php

// ==========================================
// ۱. ثبت متاباکس‌ها
// ==========================================
add_action('add_meta_boxes', 'register_lms_custom_meta_boxes');
function register_lms_custom_meta_boxes()
{
    // متاباکس صفحه دوره
    add_meta_box(
        'lms_course_meta',
        'اطلاعات تکمیلی دوره',
        'render_lms_course_meta_box',
        'sfwd-courses',
        'normal',
        'high'
    );
}

// ==========================================
// ۲. رندر کردن فرم متاباکس دوره
// ==========================================
function render_lms_course_meta_box($post)
{
    // ایجاد Nonce برای امنیت
    wp_nonce_field('save_lms_course_meta', 'lms_course_meta_nonce');

    // دریافت مقادیر ذخیره شده
    $subtitle = get_post_meta($post->ID, '_course_subtitle', true);
    $duration = get_post_meta($post->ID, '_total_duration', true);
    $level    = get_post_meta($post->ID, '_course_level', true);
    $status   = get_post_meta($post->ID, '_course_status', true);
    $suffering   = get_post_meta($post->ID, '_course_suffering', true);
    $outcomes = get_post_meta($post->ID, '_course_outcomes', true) ?: array();
    $faqs     = get_post_meta($post->ID, '_course_faq', true) ?: array();


    // --- دریافت مقادیر ذخیره شده وبینار ---
    $webinar_active      = get_post_meta($post->ID, '_webinar_active', true);
    $webinar_date        = get_post_meta($post->ID, '_webinar_date', true);
    $webinar_time        = get_post_meta($post->ID, '_webinar_time', true);
    $webinar_location    = get_post_meta($post->ID, '_webinar_location', true);
    $webinar_map_iframe  = get_post_meta($post->ID, '_webinar_map_iframe', true);
    $webinar_gmap_link   = get_post_meta($post->ID, '_webinar_gmap_link', true);
    $webinar_neshan_link = get_post_meta($post->ID, '_webinar_neshan_link', true);
    // استایل‌های درون‌خطی ساده برای مرتب‌سازی پنل
    echo '<style>
        .lms-meta-row { margin-bottom: 15px; }
        .lms-meta-row label { display: block; font-weight: bold; margin-bottom: 5px; }
        .lms-meta-row input[type="text"], .lms-meta-row textarea, .lms-meta-row select { width: 100%; max-width: 600px; }
        .lms-repeater-item { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; background: #f9f9f9; }
        .remove-row { color: red; cursor: pointer; text-decoration: underline; font-size: 12px; }
        .section-title-meta { background: #f1f1f1; padding: 10px; border-right: 4px solid #0A3762; margin-top: 30px; font-weight: bold;}
    </style>';

?>
    <div class="lms-meta-row">
        <label>توضیح کوتاه دوره (Hero Section)</label>
        <textarea name="course_subtitle" rows="3"><?php echo esc_textarea($subtitle); ?></textarea>
    </div>

    <div class="lms-meta-row">
        <label>مدت زمان کل دوره</label>
        <input type="text" name="total_duration" value="<?php echo esc_attr($duration); ?>" placeholder="مثال: ۵ ساعت و ۳۰ دقیقه">
    </div>

    <div class="lms-meta-row">
        <label>سطح دوره</label>
        <select name="course_level">
            <option value="مقدماتی" <?php selected($level, 'مقدماتی'); ?>>مقدماتی</option>
            <option value="متوسط" <?php selected($level, 'متوسط'); ?>>متوسط</option>
            <option value="پیشرفته" <?php selected($level, 'پیشرفته'); ?>>پیشرفته</option>
        </select>
    </div>

    <div class="lms-meta-row">
        <label>وضعیت دوره</label>
        <select name="course_status">
            <option value="در حال برگزاری" <?php selected($status, 'در حال برگزاری'); ?>>در حال برگزاری</option>
            <option value="تمام شده" <?php selected($status, 'تمام شده'); ?>>تمام شده</option>
        </select>
    </div>

    <div class="lms-meta-row">
        <label>آپلود تکلیف</label>
        <select name="course_suffering">
            <option value="0" <?php selected($suffering, '0'); ?>>غیرفعال</option>
            <option value="1" <?php selected($suffering, '1'); ?>>فعال</option>
        </select>
    </div>


    <div class="section-title-meta">تنظیمات بخش وبینار</div>

    <div class="lms-meta-row">
        <label>وضعیت نمایش وبینار</label>
        <select name="webinar_active">
            <option value="no" <?php selected($webinar_active, 'no'); ?>>غیرفعال (عدم نمایش در صفحه)</option>
            <option value="yes" <?php selected($webinar_active, 'yes'); ?>>فعال (نمایش در صفحه)</option>
        </select>
    </div>

    <div class="lms-meta-row">
        <label>شروع وبینار (تاریخ)</label>
        <input type="text" name="webinar_date" value="<?php echo esc_attr($webinar_date); ?>" placeholder="مثال: ۲۹ اردیبهشت ۱۴۰۵">
    </div>

    <div class="lms-meta-row">
        <label>تایم وبینار (ساعت)</label>
        <input type="text" name="webinar_time" value="<?php echo esc_attr($webinar_time); ?>" placeholder="مثال: ساعت ۱۹ تا ۲۲ به مدت سه ساعت">
    </div>

    <div class="lms-meta-row">
        <label>محل برگزاری وبینار</label>
        <textarea name="webinar_location" rows="2" placeholder="آدرس دقیق را وارد کنید..."><?php echo esc_textarea($webinar_location); ?></textarea>
    </div>

    <div class="lms-meta-row">
        <label>لینک Iframe نقشه (گوگل مپ یا بلد/نشان)</label>
        <input type="text" name="webinar_map_iframe" value="<?php echo esc_attr($webinar_map_iframe); ?>" style="direction: ltr;" placeholder="فقط لینک src را قرار دهید (مثال: https://google.com/maps/embed?...)">
    </div>

    <div class="lms-meta-row">
        <label>لینک مسیریابی با گوگل مپ (برای دکمه نقشه)</label>
        <input type="text" name="webinar_gmap_link" value="<?php echo esc_attr($webinar_gmap_link); ?>" style="direction: ltr;" placeholder="https://goo.gl/maps/...">
    </div>

    <div class="lms-meta-row">
        <label>لینک مسیریابی با نشان (برای دکمه نقشه)</label>
        <input type="text" name="webinar_neshan_link" value="<?php echo esc_attr($webinar_neshan_link); ?>" style="direction: ltr;" placeholder="https://nshn.ir/...">
    </div>

    <div class="section-title-meta">سایر تنظیمات</div>

    <hr>

    <div class="lms-meta-row" id="outcomes-wrapper">
        <label>آنچه در این دوره می‌آموزید:</label>
        <div id="outcomes-container">
            <?php foreach ($outcomes as $outcome) : ?>
                <div class="lms-repeater-item">
                    <input type="text" name="course_outcomes[]" value="<?php echo esc_attr($outcome); ?>" style="width: 80%;" placeholder="عنوان دستاورد">
                    <span class="remove-row">حذف</span>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="add-outcome">افزودن دستاورد جدید</button>
    </div>

    <hr>

    <div class="lms-meta-row" id="faqs-wrapper">
        <label>سوالات متداول (FAQ):</label>
        <div id="faqs-container">
            <?php foreach ($faqs as $faq) : ?>
                <div class="lms-repeater-item">
                    <input type="text" name="course_faq[question][]" value="<?php echo esc_attr($faq['question']); ?>" style="margin-bottom:5px;" placeholder="سوال">
                    <textarea name="course_faq[answer][]" rows="2" placeholder="پاسخ"><?php echo esc_textarea($faq['answer']); ?></textarea>
                    <span class="remove-row">حذف</span>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="add-faq">افزودن سوال جدید</button>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // افزودن فیلد دستاورد
            $('#add-outcome').click(function() {
                $('#outcomes-container').append('<div class="lms-repeater-item"><input type="text" name="course_outcomes[]" style="width: 80%;" placeholder="عنوان دستاورد"> <span class="remove-row">حذف</span></div>');
            });
            // افزودن فیلد سوال متداول
            $('#add-faq').click(function() {
                $('#faqs-container').append('<div class="lms-repeater-item"><input type="text" name="course_faq[question][]" style="margin-bottom:5px;" placeholder="سوال"><textarea name="course_faq[answer][]" rows="2" placeholder="پاسخ"></textarea> <span class="remove-row">حذف</span></div>');
            });
            // حذف سطر
            $(document).on('click', '.remove-row', function() {
                $(this).parent('.lms-repeater-item').remove();
            });
        });
    </script>
<?php
}


// ==========================================
// ۴. ذخیره‌سازی امن داده‌ها در دیتابیس
// ==========================================
add_action('save_post', 'save_lms_custom_meta_data');
function save_lms_custom_meta_data($post_id)
{
    // جلوگیری از اجرای تابع در هنگام Auto-save وردپرس
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // --- ذخیره متادیتای دوره ---
    if (isset($_POST['post_type']) && 'sfwd-courses' == $_POST['post_type']) {
        if (!isset($_POST['lms_course_meta_nonce']) || !wp_verify_nonce($_POST['lms_course_meta_nonce'], 'save_lms_course_meta')) return;
        if (!current_user_can('edit_post', $post_id)) return;

        update_post_meta($post_id, '_course_subtitle', sanitize_textarea_field($_POST['course_subtitle']));
        update_post_meta($post_id, '_total_duration', sanitize_text_field($_POST['total_duration']));
        update_post_meta($post_id, '_course_level', sanitize_text_field($_POST['course_level']));
        update_post_meta($post_id, '_course_status', sanitize_text_field($_POST['course_status']));
        update_post_meta($post_id, '_course_suffering', sanitize_text_field($_POST['course_suffering']));

        // ذخیره متاهای اختصاصی وبینار
        if (isset($_POST['webinar_active'])) update_post_meta($post_id, '_webinar_active', sanitize_text_field($_POST['webinar_active']));
        if (isset($_POST['webinar_date'])) update_post_meta($post_id, '_webinar_date', sanitize_text_field($_POST['webinar_date']));
        if (isset($_POST['webinar_time'])) update_post_meta($post_id, '_webinar_time', sanitize_text_field($_POST['webinar_time']));
        if (isset($_POST['webinar_location'])) update_post_meta($post_id, '_webinar_location', sanitize_textarea_field($_POST['webinar_location']));

        // از esc_url_raw برای پاکسازی ایمن لینک‌ها استفاده می‌کنیم
        if (isset($_POST['webinar_map_iframe'])) update_post_meta($post_id, '_webinar_map_iframe', esc_url_raw($_POST['webinar_map_iframe']));
        if (isset($_POST['webinar_gmap_link'])) update_post_meta($post_id, '_webinar_gmap_link', esc_url_raw($_POST['webinar_gmap_link']));
        if (isset($_POST['webinar_neshan_link'])) update_post_meta($post_id, '_webinar_neshan_link', esc_url_raw($_POST['webinar_neshan_link']));

        // ذخیره ریپیتر دستاوردها به صورت آرایه
        if (isset($_POST['course_outcomes']) && is_array($_POST['course_outcomes'])) {
            $outcomes = array_map('sanitize_text_field', $_POST['course_outcomes']);
            update_post_meta($post_id, '_course_outcomes', array_filter($outcomes)); // array_filter برای حذف فیلدهای خالی
        } else {
            delete_post_meta($post_id, '_course_outcomes');
        }

        // ذخیره ریپیتر سوالات متداول به صورت آرایه چندبعدی
        if (isset($_POST['course_faq']) && is_array($_POST['course_faq'])) {
            $faqs = array();
            $questions = $_POST['course_faq']['question'];
            $answers = $_POST['course_faq']['answer'];

            for ($i = 0; $i < count($questions); $i++) {
                if (!empty($questions[$i]) || !empty($answers[$i])) {
                    $faqs[] = array(
                        'question' => sanitize_text_field($questions[$i]),
                        'answer'   => sanitize_textarea_field($answers[$i])
                    );
                }
            }
            update_post_meta($post_id, '_course_faq', $faqs);
        } else {
            delete_post_meta($post_id, '_course_faq');
        }
    }
}

function display_instructor_user_role_field($user)
{

    $custom_role = get_user_meta($user->ID, 'instructor_user_role', true);
?>
    <h3>تنظیمات نقش کاربری استاد</h3>
    <table class="form-table">
        <tr>
            <th><label for="instructor_user_role">نقش کاربری استاد</label></th>
            <td>
                <input type="text" name="instructor_user_role" id="instructor_user_role" value="<?php echo esc_attr($custom_role); ?>" class="regular-text" />
                <p class="description">یک عنوان یا نقش سفارشی برای این کاربر وارد کنید (مثلاً: مدرس ارشد، پشتیبان ویژه).</p>
            </td>
        </tr>
    </table>
<?php
}
add_action('show_user_profile', 'display_instructor_user_role_field');
add_action('edit_user_profile', 'display_instructor_user_role_field');


function save_instructor_user_role_field($user_id)
{

    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['instructor_user_role'])) {
        update_user_meta($user_id, 'instructor_user_role', sanitize_text_field($_POST['instructor_user_role']));
    }
}
add_action('personal_options_update', 'save_instructor_user_role_field');
add_action('edit_user_profile_update', 'save_instructor_user_role_field');

// افزودن فیلد آپلود آواتار در پروفایل کاربر
function custom_user_profile_avatar($user)
{
    wp_enqueue_media();
?>
    <h3>آپلود آواتار سفارشی</h3>
    <table class="form-table">
        <tr>
            <th><label for="custom_user_avatar">آواتار سفارشی</label></th>
            <td>
                <input type="text" name="custom_user_avatar" id="custom_user_avatar" value="<?php echo esc_attr(get_user_meta($user->ID, 'custom_user_avatar', true)); ?>" class="regular-text" />
                <input type="button" class="button button-secondary custom_media_upload" value="انتخاب تصویر" />
                <br />
                <img id="custom_user_avatar_preview" src="<?php echo esc_attr(get_user_meta($user->ID, 'custom_user_avatar', true)); ?>" style="width: 100px; height: auto; margin-top: 10px;" />
            </td>
        </tr>
    </table>
    <script>
        jQuery(document).ready(function($) {
            $('.custom_media_upload').click(function(e) {
                e.preventDefault();
                var custom_uploader = wp.media({
                    title: 'انتخاب آواتار',
                    button: {
                        text: 'انتخاب'
                    },
                    multiple: false
                }).on('select', function() {
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    $('#custom_user_avatar').val(attachment.url);
                    $('#custom_user_avatar_preview').attr('src', attachment.url);
                }).open();
            });
        });
    </script>
<?php
}
add_action('show_user_profile', 'custom_user_profile_avatar');
add_action('edit_user_profile', 'custom_user_profile_avatar');


function save_custom_user_avatar($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    update_user_meta($user_id, 'custom_user_avatar', esc_url($_POST['custom_user_avatar']));
}
add_action('personal_options_update', 'save_custom_user_avatar');
add_action('edit_user_profile_update', 'save_custom_user_avatar');


function replace_gravatar_with_custom_avatar($avatar, $id_or_email, $size, $default, $alt)
{
    if (is_numeric($id_or_email)) {
        $user_id = $id_or_email;
    } elseif (is_object($id_or_email)) {
        if (!empty($id_or_email->user_id)) {
            $user_id = $id_or_email->user_id;
        }
    } else {
        $user = get_user_by('email', $id_or_email);
        $user_id = $user ? $user->ID : null;
    }

    if (!empty($user_id)) {
        $custom_avatar = get_user_meta($user_id, 'custom_user_avatar', true);
        if ($custom_avatar) {
            return "<img src='" . esc_url($custom_avatar) . "' alt='" . esc_attr($alt) . "' width='" . esc_attr($size) . "' height='" . esc_attr($size) . "' class='avatar avatar-{$size} photo' />";
        }
    }

    return "<img src='https://edu.falnic.com/wp-content/themes/edu-falnic/assets/img/single-page/placeholder_avatar_icon.png' alt='" . esc_attr($alt) . "' width='" . esc_attr($size) . "' height='" . esc_attr($size) . "' class='avatar avatar-{$size} photo' />";
}
add_filter('get_avatar', 'replace_gravatar_with_custom_avatar', 10, 5);
function replace_gravatar_url_with_custom_avatar_url($url, $id_or_email, $args)
{
    $user_id = null;

    if (is_numeric($id_or_email)) {
        $user_id = $id_or_email;
    } elseif (is_object($id_or_email)) {
        if (!empty($id_or_email->user_id)) {
            $user_id = $id_or_email->user_id;
        }
    } else {
        $user = get_user_by('email', $id_or_email);
        $user_id = $user ? $user->ID : null;
    }

    if (!empty($user_id)) {
        $custom_avatar = get_user_meta($user_id, 'custom_user_avatar', true);
        if ($custom_avatar) {
            return esc_url($custom_avatar);
        }
    }

    // اگر عکس سفارشی نداشت، همان عکس پیش‌فرض که در کدهای قبلی گذاشتید را برگرداند
    return 'https://edu.falnic.com/wp-content/themes/edu-falnic/assets/img/single-page/placeholder_avatar_icon.png';
}
add_filter('get_avatar_url', 'replace_gravatar_url_with_custom_avatar_url', 10, 3);





// ۱. لود کردن اسکریپت‌های رسانه وردپرس در پنل ادمین
add_action('admin_enqueue_scripts', 'ld_cat_enqueue_media');
function ld_cat_enqueue_media()
{
    if (isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'ld_course_category') {
        wp_enqueue_media();
    }
}

// ۲. افزودن فیلد عکس به فرم "ساخت دسته‌بندی جدید"
// add_action( 'ld_course_category_add_form_fields', 'ld_cat_add_image_field', 10, 2 );
function ld_cat_add_image_field()
{
?>
    <div class="form-field">
        <label for="ld_cat_image_id">تصویر دسته‌بندی</label>
        <div class="ld-cat-image-preview" style="margin-bottom: 10px;"></div>
        <input type="hidden" name="ld_cat_image_id" id="ld_cat_image_id" value="">
        <button type="button" class="button ld-upload-image-btn">انتخاب تصویر از کتابخانه</button>
        <button type="button" class="button ld-remove-image-btn" style="display:none; color: red;">حذف تصویر</button>
    </div>
<?php
}
// ۳. افزودن فیلدهای عکس، توضیحات و سوالات متداول به فرم "ویرایش دسته‌بندی"
add_action('ld_course_category_edit_form_fields', 'ld_cat_edit_image_field', 10, 2);
function ld_cat_edit_image_field($term)
{
    $image_id = get_term_meta($term->term_id, 'ld_cat_image_id', true);
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';

    // دریافت خلاصه توضیحات
    $short_discription = get_term_meta($term->term_id, 'short_discription', true);
    
    // دریافت سوالات متداول
    $faqs = get_term_meta($term->term_id, 'ld_category_faqs', true);
    if (!is_array($faqs)) {
        $faqs = array(); // اگر خالی بود یک آرایه خالی در نظر بگیر
    }

?>
    <!-- فیلد خلاصه توضیحات -->
    <tr class="form-field">
        <th scope="row"><label for="short_discription">خلاصه توضیحات</label></th>
        <td>
            <?php
            $settings = array(
                'textarea_name' => 'short_discription',
                'textarea_rows' => 5,
                'media_buttons' => false,
                'wpautop'       => true,
                'teeny'         => false
            );
            wp_editor(htmlspecialchars_decode($short_discription), 'short_discription', $settings);
            ?>
        </td>
    </tr>

    <!-- فیلد تصویر دسته‌بندی -->
    <tr class="form-field">
        <th scope="row"><label for="ld_cat_image_id">تصویر دسته‌بندی</label></th>
        <td>
            <div class="ld-cat-image-preview" style="margin-bottom: 10px;">
                <?php if ($image_url) echo '<img src="' . esc_url($image_url) . '" style="max-width:300px; border-radius:8px;" />'; ?>
            </div>
            <input type="hidden" name="ld_cat_image_id" id="ld_cat_image_id" value="<?php echo esc_attr($image_id); ?>">
            <button type="button" class="button ld-upload-image-btn">انتخاب تصویر از کتابخانه</button>
            <button type="button" class="button ld-remove-image-btn" style="<?php echo $image_id ? '' : 'display:none;'; ?> color: red;">حذف تصویر</button>
        </td>
    </tr>

    <!-- فیلد سوالات متداول -->
    <tr class="form-field">
        <th scope="row"><label>سوالات متداول (FAQ)</label></th>
        <td>
            <div id="ld-faq-container">
                <?php if (!empty($faqs)) : ?>
                    <?php foreach ($faqs as $index => $faq) : ?>
                        <div class="ld-faq-item" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ccd0d4; background: #f9f9f9; border-radius: 4px;">
                            <input type="text" name="ld_faq_question[]" value="<?php echo esc_attr($faq['question']); ?>" placeholder="سوال را اینجا بنویسید..." style="width: 100%; margin-bottom: 10px; font-weight: bold;" />
                            <textarea name="ld_faq_answer[]" placeholder="پاسخ را اینجا بنویسید..." style="width: 100%;" rows="3"><?php echo esc_textarea($faq['answer']); ?></textarea>
                            <button type="button" class="button ld-remove-faq" style="color: #d63638; border-color: #d63638; margin-top: 10px;">حذف این سوال (-)</button>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <!-- یک آیتم خالی پیش‌فرض برای زمانی که هیچ سوالی وجود ندارد -->
                    <div class="ld-faq-item" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ccd0d4; background: #f9f9f9; border-radius: 4px;">
                        <input type="text" name="ld_faq_question[]" value="" placeholder="سوال را اینجا بنویسید..." style="width: 100%; margin-bottom: 10px; font-weight: bold;" />
                        <textarea name="ld_faq_answer[]" placeholder="پاسخ را اینجا بنویسید..." style="width: 100%;" rows="3"></textarea>
                        <button type="button" class="button ld-remove-faq" style="color: #d63638; border-color: #d63638; margin-top: 10px;">حذف این سوال (-)</button>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="button button-primary" id="ld-add-faq">افزودن سوال جدید (+)</button>

            <!-- کدهای جاوااسکریپت برای افزودن و حذف سوالات -->
            <script>
                jQuery(document).ready(function($) {
                    // افزودن سوال جدید
                    $('#ld-add-faq').on('click', function() {
                        var template = `
                        <div class="ld-faq-item" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ccd0d4; background: #f9f9f9; border-radius: 4px;">
                            <input type="text" name="ld_faq_question[]" value="" placeholder="سوال را اینجا بنویسید..." style="width: 100%; margin-bottom: 10px; font-weight: bold;" />
                            <textarea name="ld_faq_answer[]" placeholder="پاسخ را اینجا بنویسید..." style="width: 100%;" rows="3"></textarea>
                            <button type="button" class="button ld-remove-faq" style="color: #d63638; border-color: #d63638; margin-top: 10px;">حذف این سوال (-)</button>
                        </div>`;
                        $('#ld-faq-container').append(template);
                    });

                    // حذف سوال (چون المنت‌ها داینامیک هستند از on روی document استفاده میکنیم)
                    $(document).on('click', '.ld-remove-faq', function() {
                        if(confirm('آیا از حذف این سوال مطمئن هستید؟')) {
                            $(this).closest('.ld-faq-item').slideUp(function() {
                                $(this).remove();
                            });
                        }
                    });
                });
            </script>
        </td>
    </tr>
<?php
}

// ۴. ذخیره کردن اطلاعات در دیتابیس
add_action('edited_ld_course_category', 'ld_cat_save_meta', 10, 2);
function ld_cat_save_meta($term_id)
{
    // ذخیره عکس
    if (isset($_POST['ld_cat_image_id'])) {
        update_term_meta($term_id, 'ld_cat_image_id', sanitize_text_field($_POST['ld_cat_image_id']));
    }

    // ذخیره خلاصه توضیحات (اصلاح باگ و استفاده از wp_kses_post به جای sanitize_text_field)
    if (isset($_POST['short_discription'])) {
        update_term_meta($term_id, 'short_discription', wp_kses_post(wp_unslash($_POST['short_discription'])));
    }

    // ذخیره سوالات متداول
    $faqs = array();
    if (isset($_POST['ld_faq_question']) && isset($_POST['ld_faq_answer'])) {
        $questions = $_POST['ld_faq_question'];
        $answers   = $_POST['ld_faq_answer'];

        // حلقه برای بررسی و ذخیره جفت‌های سوال و جواب
        for ($i = 0; $i < count($questions); $i++) {
            $q = sanitize_text_field(stripslashes($questions[$i]));
            $a = sanitize_textarea_field(stripslashes($answers[$i]));

            // اگر حداقل سوال یا جواب پر شده بود، آن را به آرایه اضافه کن
            if (!empty($q) || !empty($a)) {
                $faqs[] = array(
                    'question' => $q,
                    'answer'   => $a
                );
            }
        }
    }
    // اگر همه حذف شده بودند یا آرایه خالی بود، آن را بروزرسانی کن تا در دیتابیس پاک شود
    update_term_meta($term_id, 'ld_category_faqs', $faqs);
}

// ۵. کدهای جاوااسکریپت برای باز کردن پنجره رسانه وردپرس
add_action('admin_footer', 'ld_cat_image_script');
function ld_cat_image_script()
{
    if (! isset($_GET['taxonomy']) || $_GET['taxonomy'] != 'ld_course_category') return;
?>
    <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            $('.ld-upload-image-btn').click(function(e) {
                e.preventDefault();
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                mediaUploader = wp.media.frames.file_frame = wp.media({
                    title: 'انتخاب تصویر برای دسته‌بندی',
                    button: {
                        text: 'انتخاب این تصویر'
                    },
                    multiple: false
                });
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#ld_cat_image_id').val(attachment.id);
                    $('.ld-cat-image-preview').html('<img src="' + attachment.url + '" style="max-width:300px; border-radius:8px;" />');
                    $('.ld-remove-image-btn').show();
                });
                mediaUploader.open();
            });
            $('.ld-remove-image-btn').click(function(e) {
                e.preventDefault();
                $('#ld_cat_image_id').val('');
                $('.ld-cat-image-preview').html('');
                $(this).hide();
            });
        });
    </script>
<?php
}



// ۱. نمایش فیلدهای پیشرفته مدرس در پروفایل کاربر
function custom_instructor_profile_fields($user)
{
    // دریافت مقادیر ذخیره شده قبلی
    $youtube   = get_user_meta($user->ID, 'youtube', true);
    $linkedin  = get_user_meta($user->ID, 'linkedin', true);
    $instagram = get_user_meta($user->ID, 'instagram', true);
    $about    = get_user_meta($user->ID, 'about_teacher', true);

    $jobs      = get_user_meta($user->ID, 'teacher_jobs', true) ?: array();
    $education = get_user_meta($user->ID, 'teacher_education', true) ?: array();
?>

    <hr />
    <h2>تنظیمات اختصاصی پروفایل مدرس</h2>

    <!-- بخش اول: شبکه‌های اجتماعی -->
    <h3>شبکه‌های اجتماعی مدرس</h3>
    <table class="form-table">
        <tr>
            <th><label for="youtube">لینک یوتیوب</label></th>
            <td><input type="url" name="youtube" id="youtube" value="<?php echo esc_url($youtube); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="linkedin">لینک لینکدین</label></th>
            <td><input type="url" name="linkedin" id="linkedin" value="<?php echo esc_url($linkedin); ?>" class="regular-text" /></td>
        </tr>
        <tr>
        <tr>
            <th><label for="instagram">لینک اینستاگرام</label></th>
            <td><input type="url" name="instagram" id="instagram" value="<?php echo esc_url($instagram); ?>" class="regular-text" /></td>
        </tr>
    </table>

    <!-- بخش دوم: آمار و درباره مدرس -->
    <h3>آمار و توضیحات تکمیلی</h3>
    <table class="form-table">
        <tr>
            <th><label for="about_teacher">درباره مدرس (متن طولانی)</label></th>
            <td><textarea name="about_teacher" id="about_teacher" rows="5" cols="30" class="regular-text"><?php echo esc_textarea($about); ?></textarea></td>
        </tr>
    </table>

    <!-- بخش سوم: جداول پویا (Repeater) -->
    <h3>سوابق شغلی مدرس</h3>
    <table class="widefat fixed striped" id="jobs-table" style="max-width: 800px; margin-bottom: 20px;">
        <thead>
            <tr>
                <th>محل کار مدرس</th>
                <th>سمت</th>
                <th>مدت فعالیت</th>
                <th style="width: 70px;">عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jobs as $index => $job) : ?>
                <tr>
                    <td><input type="text" name="teacher_jobs[<?php echo $index; ?>][company]" value="<?php echo esc_attr($job['company']); ?>" class="large-text" /></td>
                    <td><input type="text" name="teacher_jobs[<?php echo $index; ?>][position]" value="<?php echo esc_attr($job['position']); ?>" class="large-text" /></td>
                    <td><input type="text" name="teacher_jobs[<?php echo $index; ?>][duration]" value="<?php echo esc_attr($job['duration']); ?>" class="large-text" /></td>
                    <td><button type="button" class="button remove-row-btn" style="color:red;">حذف</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <button type="button" class="button button-primary" id="add-job-btn" style="margin-bottom: 30px;">+ افزودن سابقه شغلی جدید</button>

    <h3>سوابق تحصیلی مدرس</h3>
    <table class="widefat fixed striped" id="edu-table" style="max-width: 800px; margin-bottom: 20px;">
        <thead>
            <tr>
                <th>مقطع تحصیلی</th>
                <th>رشته و گرایش</th>
                <th>محل اخذ مدرک</th>
                <th style="width: 70px;">عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($education as $index => $edu) : ?>
                <tr>
                    <td><input type="text" name="teacher_education[<?php echo $index; ?>][degree]" value="<?php echo esc_attr($edu['degree']); ?>" class="large-text" /></td>
                    <td><input type="text" name="teacher_education[<?php echo $index; ?>][field]" value="<?php echo esc_attr($edu['field']); ?>" class="large-text" /></td>
                    <td><input type="text" name="teacher_education[<?php echo $index; ?>][university]" value="<?php echo esc_attr($edu['university']); ?>" class="large-text" /></td>
                    <td><button type="button" class="button remove-row-btn" style="color:red;">حذف</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <button type="button" class="button button-primary" id="add-edu-btn" style="margin-bottom: 30px;">+ افزودن سابقه تحصیلی جدید</button>
    <!-- جاوا اسکریپت ساده برای مدیریت حذف و اضافه کردن ردیف‌ها در پنل مدیریت -->
    <script>
        jQuery(document).ready(function($) {
            // حذف ردیف
            $(document).on('click', '.remove-row-btn', function() {
                $(this).closest('tr').remove();
            });

            // افزودن سابقه شغلی
            $('#add-job-btn').click(function() {
                var index = $('#jobs-table tbody tr').length;
                var row = '<tr>' +
                    '<td><input type="text" name="teacher_jobs[' + index + '][company]" class="large-text" /></td>' +
                    '<td><input type="text" name="teacher_jobs[' + index + '][position]" class="large-text" /></td>' +
                    '<td><input type="text" name="teacher_jobs[' + index + '][duration]" class="large-text" /></td>' +
                    '<td><button type="button" class="button remove-row-btn" style="color:red;">حذف</button></td>' +
                    '</tr>';
                $('#jobs-table tbody').append(row);
            });

            // افزودن سابقه تحصیلی
            $('#add-edu-btn').click(function() {
                var index = $('#edu-table tbody tr').length;
                var row = '<tr>' +
                    '<td><input type="text" name="teacher_education[' + index + '][degree]" class="large-text" /></td>' +
                    '<td><input type="text" name="teacher_education[' + index + '][field]" class="large-text" /></td>' +
                    '<td><input type="text" name="teacher_education[' + index + '][university]" class="large-text" /></td>' +
                    '<td><button type="button" class="button remove-row-btn" style="color:red;">حذف</button></td>' +
                    '</tr>';
                $('#edu-table tbody').append(row);
            });
        });
    </script>
<?php
}
add_action('show_user_profile', 'custom_instructor_profile_fields');
add_action('edit_user_profile', 'custom_instructor_profile_fields');


// ۲. ذخیره ایمن دیتای وارد شده هنگام به‌روزرسانی پروفایل
function save_custom_instructor_profile_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    // ذخیره فیلدهای متنی ساده
    $fields = array('youtube', 'linkedin', 'instagram', 'about_teacher');
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            if ($field === 'about_teacher') {
                update_user_meta($user_id, $field, sanitize_textarea_field($_POST[$field]));
            } elseif (in_array($field, array('youtube', 'linkedin', 'instagram'))) {
                update_user_meta($user_id, $field, esc_url_raw($_POST[$field]));
            } else {
                update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    // ذخیره و پاکسازی جدول سوابق شغلی (آرایه)
    if (isset($_POST['teacher_jobs']) && is_array($_POST['teacher_jobs'])) {
        $sanitized_jobs = array();
        foreach ($_POST['teacher_jobs'] as $job) {
            if (!empty($job['company']) || !empty($job['position'])) {
                $sanitized_jobs[] = array(
                    'company'  => sanitize_text_field($job['company']),
                    'position' => sanitize_text_field($job['position']),
                    'duration' => sanitize_text_field($job['duration']),
                );
            }
        }
        update_user_meta($user_id, 'teacher_jobs', $sanitized_jobs);
    } else {
        delete_user_meta($user_id, 'teacher_jobs');
    }

    // ذخیره و پاکسازی جدول سوابق تحصیلی (آرایه)
    if (isset($_POST['teacher_education']) && is_array($_POST['teacher_education'])) {
        $sanitized_edu = array();
        foreach ($_POST['teacher_education'] as $edu) {
            if (!empty($edu['degree']) || !empty($edu['university'])) {
                $sanitized_edu[] = array(
                    'degree'     => sanitize_text_field($edu['degree']),
                    'field'      => sanitize_text_field($edu['field']),
                    'university' => sanitize_text_field($edu['university']),
                );
            }
        }
        update_user_meta($user_id, 'teacher_education', $sanitized_edu);
    } else {
        delete_user_meta($user_id, 'teacher_education');
    }
}
add_action('personal_options_update', 'save_custom_instructor_profile_fields');
add_action('edit_user_profile_update', 'save_custom_instructor_profile_fields');
