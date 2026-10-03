<?php
// ==========================================
// پردازش فرم ثبت نظر دوره با ایجکس
// ==========================================
add_action('wp_ajax_submit_course_review', 'handle_submit_course_review');
add_action('wp_ajax_nopriv_submit_course_review', 'handle_submit_course_review');

function handle_submit_course_review() {
    // بررسی امنیت (Nonce)
    check_ajax_referer('course_review_nonce', 'security');

    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    $rating    = isset($_POST['rating']) ? absint($_POST['rating']) : 0;
    $content   = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';

    // بررسی خالی نبودن مقادیر
    if (!$course_id || !$rating || empty($content)) {
        wp_send_json_error('لطفا تمام فیلدها و امتیاز را وارد کنید.');
    }
    if ($rating < 1 || $rating > 5 || 'sfwd-courses' !== get_post_type($course_id)) {
        wp_send_json_error('امتیاز یا دوره نامعتبر است.');
    }

    $user = wp_get_current_user();
    if (!$user->exists()) {
        wp_send_json_error('برای ثبت نظر ابتدا باید وارد سایت شوید.');
    }

    // ساخت داده‌های کامنت
    $comment_data = array(
        'comment_post_ID'      => $course_id,
        'comment_author'       => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_content'      => $content,
        'user_id'              => $user->ID,
        'comment_approved'     => 0, // مقدار 0 یعنی نیاز به تایید مدیر دارد
    );

    /** امکان وتو (مثلاً جلوگیری از امتیاز تکراری در inc/reviews.php). */
    $comment_data = apply_filters('evented_review_before_insert', $comment_data, $course_id, $rating);
    if (is_wp_error($comment_data)) {
        wp_send_json_error($comment_data->get_error_message());
    }

    // ذخیره کامنت در دیتابیس وردپرس
    $comment_id = wp_insert_comment($comment_data);

    if ($comment_id) {
        // ذخیره امتیاز ستاره‌ها به عنوان متای کامنت
        add_comment_meta($comment_id, 'review_rating', $rating);
        wp_send_json_success('نظر شما با موفقیت ثبت شد و پس از تایید مدیریت نمایش داده می‌شود.');
    } else {
        wp_send_json_error('متاسفانه خطایی در ثبت نظر رخ داد.');
    }
}

// ==========================================
// پردازش تکمیل اتوماتیک درس با ایجکس و برگشت درصد پیشرفت
// ==========================================
add_action('wp_ajax_custom_mark_lesson_complete', 'handle_custom_mark_lesson_complete');
function handle_custom_mark_lesson_complete() {
    $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    
    if ( ! check_ajax_referer('mark_complete_nonce_' . $lesson_id, 'security', false) ) {
         wp_send_json_error('خطای امنیتی');
    }
    
    $user_id = get_current_user_id();

    if (!$lesson_id || !$course_id || !$user_id) {
        wp_send_json_error('درخواست نامعتبر است.');
    }
    if ('sfwd-courses' !== get_post_type($course_id) || !in_array(get_post_type($lesson_id), array('sfwd-lessons', 'sfwd-topic'), true)) {
        wp_send_json_error('دوره یا درس نامعتبر است.');
    }

    $actual_course_id = function_exists('learndash_get_course_id') ? (int) learndash_get_course_id($lesson_id) : 0;
    if ($actual_course_id !== $course_id) {
        wp_send_json_error('این درس متعلق به دورهٔ انتخاب‌شده نیست.');
    }
    if (!function_exists('sfwd_lms_has_access') || !sfwd_lms_has_access($course_id, $user_id)) {
        wp_send_json_error('برای تکمیل این درس باید در دوره ثبت‌نام کرده باشید.');
    }
    if (!function_exists('learndash_process_mark_complete')) {
        wp_send_json_error('سرویس پیشرفت LearnDash در دسترس نیست.');
    }

    // فقط API عمومی LearnDash؛ قفل ویدیو/تایمر یا متای پیشرفت مستقیماً دستکاری نمی‌شود.
    learndash_process_mark_complete($user_id, $lesson_id, false, $course_id);

    if (function_exists('learndash_course_progress')) {
        $progress_data = learndash_course_progress(array(
            'user_id'   => $user_id,
            'course_id' => $course_id,
            'array'     => true,
        ));
        wp_send_json_success($progress_data);
    }

    wp_send_json_error('امکان دریافت پیشرفت به‌روز وجود ندارد.');
}




// ==========================================
// پردازش دکمه علاقه‌مندی‌ها (Wishlist) با ایجکس
// ==========================================
add_action('wp_ajax_toggle_course_wishlist', 'handle_toggle_course_wishlist');
function handle_toggle_course_wishlist() {
    // بررسی امنیت ریکوئست
    check_ajax_referer('wishlist_nonce', 'security');

    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    $user_id = get_current_user_id();

    if (!$course_id || !$user_id || 'sfwd-courses' !== get_post_type($course_id)) {
        wp_send_json_error('درخواست نامعتبر');
    }

    // دریافت لیست فعلی از متای کاربر (فقط شناسه‌های عددی معتبر)
    $fav_courses_str = (string) get_user_meta($user_id, 'fav_courses', true);
    $fav_courses = $fav_courses_str ? array_map('intval', explode(',', $fav_courses_str)) : array();

    $status = '';
    
    // اگر دوره در لیست بود، آن را حذف کن، در غیر این صورت اضافه کن
    if (($key = array_search($course_id, $fav_courses)) !== false) {
        unset($fav_courses[$key]);
        $status = 'removed';
    } else {
        $fav_courses[] = $course_id;
        $status = 'added';
    }

    // تمیز کردن آرایه و تبدیل مجدد به رشته با کاما (سقف ۲۰۰ مورد)
    $fav_courses = array_slice(array_values(array_unique(array_filter($fav_courses))), -200);
    update_user_meta($user_id, 'fav_courses', implode(',', $fav_courses));

    // ارسال موفقیت‌آمیز وضعیت جدید به فرانت‌اند
    wp_send_json_success(array('status' => $status));
}

// ذخیره اطلاعات پروفایل
add_action('wp_ajax_save_user_profile', 'handle_save_user_profile');
function handle_save_user_profile() {
    check_ajax_referer('profile_nonce_action', 'security');

    $user_id = get_current_user_id();
    if (!$user_id) wp_send_json_error('کاربر لاگین نیست');

    // لیست فیلدها و کلیدهای متا
    $only_fa = function ($v) { return trim(preg_replace('/[^\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\s\x{200C}]/u', '', sanitize_text_field(wp_unslash((string) $v)))); };
    $only_en = function ($v) { return trim(preg_replace("/[^A-Za-z\s.\-']/", '', sanitize_text_field(wp_unslash((string) $v)))); };
    $birth_date = sanitize_text_field(wp_unslash((string) ($_POST['birth_date'] ?? '')));
    $birth_date = function_exists('evented_normalize_digits') ? evented_normalize_digits($birth_date) : $birth_date;
    if ('' !== $birth_date) {
        if (!preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $birth_date, $birth_parts)
            || (int) $birth_parts[2] < 1 || (int) $birth_parts[2] > 12
            || (int) $birth_parts[3] < 1 || (int) $birth_parts[3] > 31) {
            wp_send_json_error('تاریخ تولد معتبر نیست؛ نمونهٔ صحیح: ۱۳۷۰/۰۱/۰۱');
        }
    }
    $fields = [
        'first_name_fa' => $only_fa($_POST['first_name_fa'] ?? ''),
        'last_name_fa'  => $only_fa($_POST['last_name_fa'] ?? ''),
        'first_name_en' => $only_en($_POST['first_name_en'] ?? ''),
        'last_name_en'  => $only_en($_POST['last_name_en'] ?? ''),
        'gender'        => in_array($_POST['gender'] ?? '', array('male', 'female', 'other', ''), true) ? (string) $_POST['gender'] : '',
        'birth_date'    => $birth_date,
    ];
    // فیلدهای بالا تنها متاهای قابل‌نوشتن از سمت کاربر هستند (whitelist).

    foreach ($fields as $key => $value) {
        update_user_meta($user_id, $key, $value);
    }

    wp_send_json_success('اطلاعات با موفقیت ذخیره شد.');
}

// پردازش تغییرات تنظیمات حساب کاربری (ایمیل، موبایل، رمز عبور)
add_action('wp_ajax_save_account_settings', 'handle_save_account_settings');
function handle_save_account_settings() {
    check_ajax_referer('settings_nonce_action', 'security');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('شما دسترسی ندارید.');
    }

    $userdata = array(
        'ID' => $user_id
    );

    // بررسی و تغییر ایمیل
    if (!empty($_POST['user_email'])) {
        $new_email = sanitize_email($_POST['user_email']);
        if (is_email($new_email) && !email_exists($new_email)) {
            $userdata['user_email'] = $new_email;
        } elseif (email_exists($new_email) && $new_email !== wp_get_current_user()->user_email) {
            wp_send_json_error('این ایمیل قبلاً توسط شخص دیگری ثبت شده است.');
        }
    }

    // بررسی و تغییر رمز عبور — رمز فعلی برای هر تغییر الزامی است
    if (!empty($_POST['user_password']) && $_POST['user_password'] !== '..........') {
        $current_user_obj = wp_get_current_user();
        $current_password = isset($_POST['current_password']) ? (string) wp_unslash($_POST['current_password']) : '';
        if ('' === $current_password || !wp_check_password($current_password, $current_user_obj->user_pass, $user_id)) {
            wp_send_json_error('رمز عبور فعلی صحیح نیست.');
        }
        $pass  = (string) wp_unslash($_POST['user_password']);
        $pass2 = isset($_POST['user_password2']) ? (string) wp_unslash($_POST['user_password2']) : $pass;
        if ($pass !== $pass2) {
            wp_send_json_error('تکرار رمز عبور با رمز جدید یکسان نیست.');
        }
        $password_error = function_exists('evented_auth_password_error')
            ? evented_auth_password_error($pass)
            : (strlen($pass) < 8 ? 'رمز عبور باید حداقل ۸ کاراکتر باشد.' : '');
        if ('' !== $password_error) {
            wp_send_json_error($password_error);
        }
        $userdata['user_pass'] = $pass;
    }

    // آپدیت ایمیل و رمز عبور (در صورت تغییر)
    if (isset($userdata['user_email']) || isset($userdata['user_pass'])) {
        $result = wp_update_user($userdata);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        if (isset($userdata['user_pass'])) {
            // کاربر پس از تغییر رمز از حساب خارج نشود
            wp_set_auth_cookie($user_id, true);
        }
    }

    // شماره موبایل شناسهٔ ورود است و از این فرم قابل تغییر نیست.

    wp_send_json_success('تغییرات با موفقیت ذخیره شد.');
}