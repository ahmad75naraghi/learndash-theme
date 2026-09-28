<?php

define('PATH_DIR_URL', get_template_directory_uri());
define('PATH_DIR', get_template_directory());

require_once get_stylesheet_directory() . '/assets/assets_functions.php';
require_once get_stylesheet_directory() . '/inc/includes.php';

/*
 * پوستهٔ «evented-edu» (طراحی pastel با کلاس‌های ee-*)
 *
 * صفحات تحت پوشش (evented_is_ee_view):
 *   - صفحهٔ اصلی (front-page.php)          → ee-shell.css + evented-home.css
 *   - تک‌نوشته (single.php)                 → ee-shell.css + single-post.css
 *   - آرشیو/برگهٔ نوشته‌ها/جستجو            → ee-shell.css + archive-post.css
 *   - تک‌دوره/تک‌درس/تک‌آزمون (single-sfwd-*.php) → ee-shell.css + newhome/ee-lms.css
 *   - بایگانی/دستهٔ دوره، دوره‌ها، اساتید،
 *     پروفایل مدرس، برگه و ۴۰۴             → ee-shell.css + newhome/ee-courses.css
 */
add_action('wp_enqueue_scripts', function () {
    if (!function_exists('evented_is_ee_view') || !evented_is_ee_view()) {
        return;
    }

    // فونت‌ها و آیکن‌ها کاملاً محلی هستند (بدون هیچ درخواست خارجی): @font-face در ee-fonts.css و آیکن‌ها در اسپرایت SVG
    wp_enqueue_style('ee-fonts', PATH_DIR_URL . '/assets/css/newhome/ee-fonts.css', array(), '1.0.0');

    // پوستهٔ مشترک: توکن‌ها، هدر، فوتر، نوار موبایل و ویجت‌های سایدبار
    wp_enqueue_style('ee-shell', PATH_DIR_URL . '/assets/css/newhome/ee-shell.css', array('ee-fonts'), '1.5.2');

    // رفتارها: منوی موبایل، اسلایدر هیرو، کپی لینک اشتراک‌گذاری
    wp_enqueue_script('ee-home-js', PATH_DIR_URL . '/assets/js/newhome/evented-home.js', array(), '1.3.0', true);

    if (is_front_page()) {
        wp_enqueue_style('ee-home', PATH_DIR_URL . '/assets/css/newhome/evented-home.css', array('ee-shell'), '1.1.0');
    } elseif (is_singular('post')) {
        wp_enqueue_style('single-post', PATH_DIR_URL . '/assets/css/single-post.css', array('ee-shell'), '1.0.0');
    } elseif (is_singular(array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'))) {
        // دوره، درس و آزمون: استایل + رفتارها (آکاردئون، دیدگاه، تکمیل درس، علاقه‌مندی)
        wp_enqueue_style('ee-lms', PATH_DIR_URL . '/assets/css/newhome/ee-lms.css', array('ee-shell'), '1.3.0');
        wp_enqueue_script('ee-lms', PATH_DIR_URL . '/assets/js/newhome/ee-lms.js', array(), '1.2.0', true);
        wp_localize_script('ee-lms', 'eeLms', array('ajax_url' => admin_url('admin-ajax.php')));

        if (is_singular('sfwd-quiz')) {
            // سایدبار آزمون کارت دوره و گرید دوره‌ها را نشان می‌دهد.
            wp_enqueue_style('ee-courses', PATH_DIR_URL . '/assets/css/newhome/ee-courses.css', array('ee-shell'), '1.2.0');
        }
    } elseif (
        is_post_type_archive('sfwd-courses')
        || is_tax('ld_course_category')
        || is_author()
        || is_404()
        || (is_page() && !is_front_page())
    ) {
        // فهرست‌ها: بایگانی/دستهٔ دوره، برگهٔ دوره‌ها، اساتید، پروفایل مدرس،
        // برگهٔ عمومی و صفحهٔ ۴۰۴.
        wp_enqueue_style('ee-courses', PATH_DIR_URL . '/assets/css/newhome/ee-courses.css', array('ee-shell'), '1.2.0');
        // سایدبار فیلتر دوره‌ها و نوار مرتب‌سازی (باز/بسته در موبایل، ارسال خودکار)
        wp_enqueue_script('ee-lms', PATH_DIR_URL . '/assets/js/newhome/ee-lms.js', array(), '1.2.0', true);
    } elseif (is_home() || is_archive() || is_search()) {
        wp_enqueue_style('archive-post', PATH_DIR_URL . '/assets/css/archive-post.css', array('ee-shell'), '1.1.0');
    }
}, 20);































// اضافه کردن به functions.php

// if (file_exists(get_template_directory() . '/vendor/autoload.php')) {
//     require_once get_template_directory() . '/vendor/autoload.php';
// }

// add_action('admin_post_shamiim_download_cert', 'shamiim_handle_certificate_pdf_generation');

// function shamiim_handle_certificate_pdf_generation()
// {
//     if (! is_user_logged_in()) {
//         wp_die('دسترسی غیرمجاز است.', 'خطای دسترسی', ['response' => 403]);
//     }

//     $current_user_id = get_current_user_id();
//     $quiz_id         = isset($_GET['quiz_id']) ? (int) $_GET['quiz_id'] : 0;
//     $time            = isset($_GET['time']) ? (int) $_GET['time'] : 0;

//     // اعتبارسنجی Nonce
//     if (! isset($_GET['_wpnonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'cert_download_' . $quiz_id . '_' . $time)) {
//         wp_die('اعتبار توکن منقضی شده است.', 'خطای امنیتی', ['response' => 403]);
//     }

//     $quiz_attempts = get_user_meta($current_user_id, '_sfwd-quizzes', true);
//     $passed        = false;
//     $attempt_data  = [];

//     if (is_array($quiz_attempts)) {
//         foreach ($quiz_attempts as $attempt) {
//             if ((int) ($attempt['quiz'] ?? 0) === $quiz_id && (int) ($attempt['time'] ?? 0) === $time && ! empty($attempt['pass'])) {
//                 $passed       = true;
//                 $attempt_data = $attempt;
//                 break;
//             }
//         }
//     }

//     if (! $passed) {
//         wp_die('گواهینامه‌ای یافت نشد.', 'خطا', ['response' => 404]);
//     }

//     $user_info     = get_userdata($current_user_id);
//     $student_name  = get_user_meta($current_user_id, 'first_last_name', true) ?: ($user_info->display_name ?: $user_info->first_name . ' ' . $user_info->last_name);
//     $course_id     = isset($attempt_data['course']) ? (int) $attempt_data['course'] : (function_exists('learndash_get_course_id') ? (int) learndash_get_course_id($quiz_id) : 0);
//     $course_title  = $course_id ? get_the_title($course_id) : get_the_title($quiz_id);
//     $national_code = get_user_meta($current_user_id, 'national_code', true) ?: '---';
//     $issue_date    = function_exists('evented_format_jalali') ? evented_format_jalali($time, 'Y/m/d') : date_i18n('Y/m/d', $time);
//     // تابع تبدیل ارقام انگلیسی به فارسی
//     $to_persian_digits = function ($string) {
//         $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
//         $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
//         return str_replace($english, $persian, $string);
//     };

//     $issue_date    = $to_persian_digits(function_exists('evented_format_jalali') ? evented_format_jalali($time, 'Y/m/d') : date_i18n('Y/m/d', $time));
//     $national_code = $to_persian_digits(get_user_meta($current_user_id, 'national_code', true) ?: '---');
//     $student_name  = get_user_meta($current_user_id, 'first_last_name', true) ?: ($user_info->display_name ?: $user_info->first_name . ' ' . $user_info->last_name);
//     $course_title  = $course_id ? get_the_title($course_id) : get_the_title($quiz_id);

//     $temp_dir        = WP_CONTENT_DIR . '/uploads/mpdf-tmp';
//     if (! file_exists($temp_dir)) {
//         wp_mkdir_p($temp_dir);
//     }

//     $fonts_directory = get_template_directory() . '/assets/fonts';
//     $bg_image_path   = 'https://shamiim.ir/wp-content/uploads/2022/05/lic2.jpg';

//     $mpdf = new \Mpdf\Mpdf([
//         'mode'              => 'utf-8',
//         'format'            => 'A4-L',
//         'tempDir'           => $temp_dir,
//         'margin_left'       => 0,
//         'margin_right'      => 0,
//         'margin_top'        => 0,
//         'margin_bottom'     => 0,
//         'fontDir'           => [$fonts_directory],
//         'fontdata'          => [
//             'vazir' => [
//                 'R'          => 'Vazir-Regular.ttf',
//                 'useOTL'     => 0xFF,
//                 'useKashida' => 75,
//             ],
//         ],
//         'default_font'      => 'vazir',
//         'autoScriptToLang'  => true,
//         'autoLangToFont'    => true,
//     ]);

//     $mpdf->SetDirectionality('rtl');

//     $html = '
//     <!DOCTYPE html>
//     <html dir="rtl" lang="fa">
//     <head>
//         <meta charset="utf-8">
       
//                 <style>
//             body {
//                 margin: 0;
//                 padding: 0;
//                 font-family: "vazir", sans-serif;
//                 background-image: url("' . esc_url($bg_image_path) . '");
//                 background-image-resize: 6;
//                 width: 297mm;
//                 height: 210mm;
//             }
//             /* جایگاه تاریخ صدور (مثلا بالا سمت راست یا چپ) */
//             .issue-date {
//                 position: absolute;
//                 top: 35mm;  
//                 left: 40mm;
//                 font-size: 14px;
//                 color: #333333;
//                 font-feature-settings: "ss02";
//                 -webkit-font-feature-settings: "ss02";
//             }
//             /* جایگاه نام دانشجو (دقیقاً وسط صفحه یا نقطه دلخواه) */
//             .student-name {
//                 position: absolute;
//                 top: 86mm;
//                 left: -140mm;
//                 right: 0;
//                 text-align: center;
//                 font-size: 28px;
//                 font-weight: bold;
//                 color: #097621;
//             }
//             /* جایگاه کد ملی */
//             .national-code {
//                 position: absolute;
//                 top: 113mm;
//                 right: 65mm;
//                 font-size: 17px;
//                 color: #097621;
//                 font-weight: bold;
//             }
//             /* جایگاه نام دوره */
//             .course-title {
//                 position: absolute;
//                 top: 113mm;
//                 right: 160mm;
//                 font-size: 14px;
//                 color: #097621;
//                 font-weight: bold;
//             }
//         </style>
//     </head>
//     <body>
//         <div class="issue-date">تاریخ صدور: ' . esc_html($issue_date) . '</div>
//         <div class="student-name">' . esc_html($student_name) . '</div>
//         <div class="national-code">' . esc_html($national_code) . '</div>
//         <div class="course-title">' . esc_html($course_title) . '</div>
//     </body>
//     </html>';

//     $mpdf->WriteHTML($html);
//     $mpdf->Output('Certificate.pdf', \Mpdf\Output\Destination::INLINE);
//     exit;
// }


    /**
 * Shamiim – گواهینامه PDF (mPDF) روی لینک‌های فرانت‌اند LearnDash
 *
 * اجرا:  /certificates/xxx/?quiz=ID&user=ID&time=TS   (همان لینک‌های فعلی کاربران)
 * کالیبره (فقط مدیر):  ...&debug=grid
 * تغییر زنده مختصات (فقط مدیر): ...&debug=grid&pos[name][y]=92&pos[nid][x]=170
 */


if (file_exists(get_template_directory() . '/vendor/autoload.php')) {
    require_once get_template_directory() . '/vendor/autoload.php';
}

/* -------------------------------------------------------------------------
 * تنظیمات جای متن‌ها  (همه‌چیز به میلی‌متر، از گوشه بالا-چپ صفحه A4 افقی 297×210)
 *  x,y  = گوشه بالا-چپ کادر
 *  w,h  = عرض و ارتفاع کادر
 *  align= left | center | right   (فارسی معمولاً right: لبه راست کادر، تکیه‌گاه متن است)
 *  size = اندازه فونت به pt
 *  overflow = auto (اگر متن بلند بود فونت کوچک می‌شود تا در کادر جا شود) | visible
 * ---------------------------------------------------------------------- */
function shamiim_cert_fields()
{
    $fields = [
        'date'   => ['x' => 40,   'y' => 35,  'w' => 90,  'h' => 10, 'align' => 'left',   'size' => 10.5, 'color' => '#333333', 'bold' => false, 'overflow' => 'visible'],
        'name'   => ['x' => -60, 'y' => 85,  'w' => 150, 'h' => 16, 'align' => 'right', 'size' => 17,   'color' => '#097621', 'bold' => true,  'overflow' => 'auto'],
        'nid'    => ['x' => 172,  'y' => 112, 'w' => 60,  'h' => 11, 'align' => 'right',  'size' => 13,   'color' => '#097621', 'bold' => true,  'overflow' => 'visible'],
        'course' => ['x' => 47,   'y' => 112, 'w' => 90,  'h' => 11, 'align' => 'right',  'size' => 12, 'color' => '#097621', 'bold' => true,  'overflow' => 'auto'],
    ];

    return apply_filters('shamiim_cert_fields', $fields);
}

/* -------------------------------------------------------------------------
 * ابزارها
 * ---------------------------------------------------------------------- */
function shamiim_to_persian_digits($string)
{
    return str_replace(
        ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
        (string) $string
    );
}

function shamiim_cert_find_attempt($user_id, $quiz_id, $time)
{
    $attempts = get_user_meta($user_id, '_sfwd-quizzes', true);
    if (! is_array($attempts)) {
        return null;
    }

    foreach ($attempts as $attempt) {
        if (
            (int) ($attempt['quiz'] ?? 0) === $quiz_id
            && (int) ($attempt['time'] ?? 0) === $time
            && ! empty($attempt['pass'])
        ) {
            return $attempt;
        }
    }

    return null;
}

/**
 * مسیر فایل بک‌گراند: اول تصویر شاخص پست گواهینامه، بعد فایل پیش‌فرض
 */
function shamiim_cert_background_path($cert_post_id)
{
    if ($cert_post_id) {
        $thumb_id = get_post_thumbnail_id($cert_post_id);
        if ($thumb_id) {
            $file = get_attached_file($thumb_id);
            if ($file && file_exists($file)) {
                return $file;
            }
        }
    }

    $upload = wp_get_upload_dir();
    $file   = trailingslashit($upload['basedir']) . '2022/05/lic2.jpg';

    return file_exists($file) ? $file : '';
}

/**
 * قرار دادن یک متن در کادر دقیق (میلی‌متر)
 */
function shamiim_cert_place($mpdf, $text, array $f, $debug = false)
{
    $style = sprintf(
        'direction:rtl; font-family:vazir; text-align:%s; font-size:%spt; color:%s; font-weight:%s;%s',
        $f['align'],
        $f['size'],
        $f['color'],
        ! empty($f['bold']) ? 'bold' : 'normal',
        $debug ? ' border:0.2mm dashed #00aa00;' : ''
    );

    $html = '<div style="' . $style . '">' . esc_html($text) . '</div>';

    $mpdf->WriteFixedPosHTML(
        $html,
        (float) $f['x'],
        (float) $f['y'],
        (float) $f['w'],
        (float) $f['h'],
        $f['overflow'] ?? 'visible'
    );
}

/**
 * شبکه میلی‌متری برای کالیبره (با Line خود mPDF)
 */
function shamiim_cert_draw_grid($mpdf)
{
    $mpdf->SetLineWidth(0.15);

    for ($x = 0; $x <= 297; $x += 10) {
        $mpdf->SetDrawColor(255, 0, 0);
        $mpdf->Line($x, 0, $x, 210);
        $mpdf->WriteFixedPosHTML('<div style="font-size:6pt; color:#ff0000;">' . $x . '</div>', $x + 0.5, 0.5, 10, 4, 'visible');
    }

    for ($y = 0; $y <= 210; $y += 10) {
        $mpdf->SetDrawColor(0, 0, 255);
        $mpdf->Line(0, $y, 297, $y);
        $mpdf->WriteFixedPosHTML('<div style="font-size:6pt; color:#0000ff;">' . $y . '</div>', 0.5, $y + 0.5, 10, 4, 'visible');
    }
}

/* -------------------------------------------------------------------------
 * تولید و ارسال PDF
 * ---------------------------------------------------------------------- */
function shamiim_cert_stream_pdf($user_id, $quiz_id, $time, $cert_post_id = 0, $debug = false)
{
    if (! class_exists('\Mpdf\Mpdf')) {
        wp_die('کتابخانه mPDF بارگذاری نشده است.', 'خطا', ['response' => 500]);
    }

    $attempt = shamiim_cert_find_attempt($user_id, $quiz_id, $time);
    if (! $attempt) {
        wp_die('گواهینامه‌ای یافت نشد.', 'خطا', ['response' => 404]);
    }

    /* --- داده‌ها --- */
    $user_info = get_userdata($user_id);
    if (! $user_info) {
        wp_die('کاربر یافت نشد.', 'خطا', ['response' => 404]);
    }

    $student_name = get_user_meta($user_id, 'first_last_name', true);
    if (! $student_name) {
        $student_name = $user_info->display_name ?: trim($user_info->first_name . ' ' . $user_info->last_name);
    }

    $course_id = isset($attempt['course']) ? (int) $attempt['course'] : 0;
    if (! $course_id && function_exists('learndash_get_course_id')) {
        $course_id = (int) learndash_get_course_id($quiz_id);
    }
    $course_title = get_the_title($course_id ?: $quiz_id);

    $national_code = get_user_meta($user_id, 'national_code', true) ?: '---';
    $issue_date    = function_exists('evented_format_jalali')
        ? evented_format_jalali($time, 'Y/m/d')
        : date_i18n('Y/m/d', $time);

    $texts = [
        'date'   => 'تاریخ صدور: ' . shamiim_to_persian_digits($issue_date),
        'name'   => $student_name,
        'nid'    => shamiim_to_persian_digits($national_code),
        'course' => $course_title,
    ];

    /* --- پوشه موقت محافظت‌شده --- */
    $temp_dir = WP_CONTENT_DIR . '/uploads/mpdf-tmp';
    if (! file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
        file_put_contents($temp_dir . '/.htaccess', "Require all denied\nDeny from all\n");
        file_put_contents($temp_dir . '/index.html', '');
    }

    /* --- فونت‌ها --- */
    $fonts_dir = get_template_directory() . '/assets/fonts';
    $bold_file = file_exists($fonts_dir . '/Vazir-Bold.ttf') ? 'Vazir-Bold.ttf' : 'Vazir-Regular.ttf';

    $mpdf = new \Mpdf\Mpdf([
        'mode'             => 'utf-8',
        'format'           => 'A4-L',
        'tempDir'          => $temp_dir,
        'margin_left'      => 0,
        'margin_right'     => 0,
        'margin_top'       => 0,
        'margin_bottom'    => 0,
        'margin_header'    => 0,
        'margin_footer'    => 0,
        'fontDir'          => array_merge(
            (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'],
            [$fonts_dir]
        ),
        'fontdata'         => (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + [
            'vazir' => [
                'R'          => 'Vazir-Regular.ttf',
                'B'          => $bold_file,
                'useOTL'     => 0xFF,
                'useKashida' => 75,
            ],
        ],
        'default_font'     => 'vazir',
        // عمداً خاموش: روشن بودنشان فونت وزیر را با فونت دیگری جایگزین می‌کند
        'autoScriptToLang' => false,
        'autoLangToFont'   => false,
    ]);

    $mpdf->SetTitle('گواهینامه - ' . $student_name);
    $mpdf->SetAuthor(get_bloginfo('name'));
    $mpdf->SetDirectionality('rtl');
    $mpdf->SetAutoPageBreak(false);
    $mpdf->AddPage();

    /* --- بک‌گراند تمام‌صفحه (297×210) --- */
    $bg_path = shamiim_cert_background_path($cert_post_id);
    if ($bg_path) {
        $mpdf->Image($bg_path, 0, 0, 297, 210, '', '', true, false);
    }

    /* --- مختصات (با امکان override زنده در حالت debug) --- */
    $fields = shamiim_cert_fields();

    if ($debug && isset($_GET['pos']) && is_array($_GET['pos'])) {
        foreach ($_GET['pos'] as $key => $vals) {
            if (! isset($fields[$key]) || ! is_array($vals)) {
                continue;
            }
            foreach (['x', 'y', 'w', 'h', 'size'] as $k) {
                if (isset($vals[$k]) && is_numeric($vals[$k])) {
                    $fields[$key][$k] = (float) $vals[$k];
                }
            }
            if (isset($vals['align']) && in_array($vals['align'], ['left', 'center', 'right'], true)) {
                $fields[$key]['align'] = $vals['align'];
            }
        }
    }

    foreach ($fields as $key => $f) {
        if (isset($texts[$key])) {
            shamiim_cert_place($mpdf, $texts[$key], $f, $debug);
        }
    }

    if ($debug) {
        shamiim_cert_draw_grid($mpdf);
    }

    /* --- خروجی تمیز --- */
    while (ob_get_level()) {
        ob_end_clean();
    }
    nocache_headers();

    $mpdf->Output('Certificate-' . $quiz_id . '.pdf', \Mpdf\Output\Destination::INLINE);
    exit;
}

/* -------------------------------------------------------------------------
 * هوک روی لینک‌های فعلی گواهینامه LearnDash (بدون wp-admin)
 * ---------------------------------------------------------------------- */
add_action('wp', function () {
    if (! is_singular('sfwd-certificates')) {
        return;
    }

    if (! isset($_GET['quiz'], $_GET['time'])) {
        return;
    }

    if (! is_user_logged_in()) {
        auth_redirect();
    }

    $current_user_id = get_current_user_id();
    $quiz_id         = absint($_GET['quiz']);
    $time            = absint($_GET['time']);
    $req_user_id     = isset($_GET['user']) ? absint($_GET['user']) : $current_user_id;

    if ($req_user_id !== $current_user_id && ! current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز است.', 'خطای دسترسی', ['response' => 403]);
    }

    $debug = isset($_GET['debug']) && 'grid' === $_GET['debug'] && current_user_can('manage_options');

    shamiim_cert_stream_pdf(
        $req_user_id,
        $quiz_id,
        $time,
        (int) get_queried_object_id(),
        $debug
    );
}, 1);