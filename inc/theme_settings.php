<?php
/**
 * تنظیمات قالب (evented-edu)
 *
 * صفحهٔ «تنظیمات قالب» در پیشخوان (ظاهر ← تنظیمات قالب):
 * مدیریت اسلایدرهای صفحهٔ اصلی — هر اسلاید شامل:
 *   تصویر (از کتابخانهٔ رسانه وردپرس) + عنوان + توضیحات + لینک
 *
 * داده‌ها در آپشن `evented_home_slides` ذخیره می‌شوند و front-page.php
 * آن‌ها را در بخش هیرو رندر می‌کند (در نبود اسلاید، بنر پیش‌فرض قبلی نمایش داده می‌شود).
 */

defined('ABSPATH') || exit;

if (!defined('EVENTED_OPT_SLIDES')) {
    define('EVENTED_OPT_SLIDES', 'evented_home_slides');
}

/* ---------- منوی پیشخوان ---------- */
add_action('admin_menu', function () {
    add_theme_page(
        'تنظیمات قالب',
        'تنظیمات قالب',
        'manage_options',
        'evented-theme-settings',
        'evented_render_theme_settings_page'
    );
});

/* ---------- بارگذاری رسانه + استایل اختصاصی فقط در همین صفحه ---------- */
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'appearance_page_evented-theme-settings') {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style('evented-theme-settings', PATH_DIR_URL . '/assets/css/admin/theme-settings.css', array(), '1.0.0');
    wp_enqueue_script('evented-theme-settings-js', PATH_DIR_URL . '/assets/js/admin/theme-settings.js', array('jquery'), '1.0.0', true);
});

/* ---------- خواندن اسلایدهای ذخیره‌شده (مصرف front-end و صفحهٔ تنظیمات) ---------- */
function evented_get_home_slides()
{
    $slides = get_option(EVENTED_OPT_SLIDES, array());
    if (!is_array($slides)) {
        return array();
    }
    // نرمال‌سازی و حذف ردیف‌های بی‌تصویر
    $out = array();
    foreach ($slides as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = isset($row['id']) ? absint($row['id']) : 0;
        $img = isset($row['image']) ? esc_url_raw($row['image']) : '';
        if (!$id || !$img) {
            continue;
        }
        $out[] = array(
            'id'    => $id,
            'image' => $img,
            'title' => isset($row['title']) ? (string) $row['title'] : '',
            'desc'  => isset($row['desc']) ? (string) $row['desc'] : '',
            'badge' => isset($row['badge']) ? (string) $row['badge'] : '',
            'link'  => isset($row['link']) ? (string) $row['link'] : '',
        );
    }
    return $out;
}

/* ---------- ذخیره‌سازی ---------- */
function evented_render_theme_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $updated = false;

    if (isset($_POST['evented_theme_options_nonce'])) {
        check_admin_referer('save_evented_theme_options', 'evented_theme_options_nonce');

        $raw  = (isset($_POST['slides']) && is_array($_POST['slides'])) ? $_POST['slides'] : array();
        $saved = array();

        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id    = isset($row['image_id']) ? absint($row['image_id']) : 0;
            $title = isset($row['title']) ? sanitize_text_field(wp_unslash($row['title'])) : '';
            $desc  = isset($row['desc']) ? sanitize_textarea_field(wp_unslash($row['desc'])) : '';
            $badge = isset($row['badge']) ? sanitize_text_field(wp_unslash($row['badge'])) : '';
            $link  = isset($row['link']) ? esc_url_raw(wp_unslash($row['link'])) : '';

            if (!$id) {
                continue; // اسلاید بدون تصویر ذخیره نمی‌شود
            }

            $img = wp_get_attachment_image_url($id, 'full');
            if (!$img) {
                continue; // پیوست حذف/نامعتبر شده باشد
            }

            $saved[] = array(
                'id'    => $id,
                'image' => $img,
                'title' => $title,
                'desc'  => $desc,
                'badge' => $badge,
                'link'  => $link,
            );
        }

        update_option(EVENTED_OPT_SLIDES, $saved, false);
        $updated = true;
    }

    $slides = evented_get_home_slides();
    ?>
    <div class="wrap evented-settings-wrap">
        <h1>تنظیمات قالب evented-edu</h1>
        <p class="description">
            اسلایدهای <strong>بنر بالای صفحهٔ اصلی (هیرو)</strong> را مدیریت کنید.
            برای هر اسلاید یک تصویر از کتابخانهٔ رسانه انتخاب کنید و در صورت تمایل عنوان، توضیح کوتاه، برچسب و لینک بدهید.
            اگر اسلایدی تنظیم نشود، صفحهٔ اصلی همان بنر پیش‌فرض (آخرین دورهٔ ویژه) را نشان می‌دهد.
        </p>

        <?php if ($updated) : ?>
            <div class="notice notice-success is-dismissible"><p>✅ تغییرات با موفقیت ذخیره شد.</p></div>
        <?php endif; ?>

        <form method="post" action="" class="evented-slides-form">
            <?php wp_nonce_field('save_evented_theme_options', 'evented_theme_options_nonce'); ?>

            <h2 class="title">اسلایدر صفحهٔ اصلی</h2>

            <div id="evented-slides-list">
                <?php
                if (empty($slides)) {
                    echo '<p class="description" id="evented-slides-empty">هنوز اسلایدی اضافه نکرده‌اید. از دکمهٔ «افزودن اسلایدر» استفاده کنید.</p>';
                } else {
                    foreach ($slides as $i => $slide) {
                        echo evented_slide_row_html($i, $slide); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- خروجی داخل تابع escape شده است
                    }
                }
                ?>
            </div>

            <p>
                <button type="button" class="button button-primary" id="evented-add-slide">+ افزودن اسلایدر</button>
            </p>

            <?php submit_button('ذخیرهٔ اسلایدرها'); ?>
        </form>

        <!-- قالب یک ردیف اسلاید برای کپی توسط JS -->
        <?php
        echo evented_slide_row_html('__UID__', array());
        ?>
    </div>

    <?php
}

/**
 * HTML یک ردیف اسلاید (برای نمایش اولیه و قالبِ کپیِ JS)
 *
 * @param string        $uid   شناسهٔ یکتای ردیف (در قالب JS برابر __UID__ است)
 * @param array         $slide داده‌های اسلاید (می‌تواند خالی باشد)
 */
function evented_slide_row_html($uid, $slide = array())
{
    $image_url = !empty($slide['image']) ? $slide['image'] : '';
    $title     = isset($slide['title']) ? $slide['title'] : '';
    $desc      = isset($slide['desc']) ? $slide['desc'] : '';
    $badge     = isset($slide['badge']) ? $slide['badge'] : '';
    $link      = isset($slide['link']) ? $slide['link'] : '';

    $img_attrs = $image_url
        ? 'src="' . esc_url($image_url) . '" class="evented-slide-image has-image"'
        : 'class="evented-slide-image"';
    $box_class = $image_url ? '' : ' empty';
    $is_proto  = ($uid === '__UID__');
    $proto_attr = $is_proto ? ' id="evented-slide-proto" style="display:none"' : '';

    ob_start();
    ?>
    <div class="evented-slide-row"<?php echo $proto_attr; ?>>
        <div class="evented-slide-main">
            <div class="evented-slide-imgbox<?php echo esc_attr($box_class); ?>">
                <img <?php echo $img_attrs; ?> alt="">
                <span class="evented-img-hint">تصویر اسلایدر</span>
                <input type="hidden" class="evented-slide-image-id" name="slides[<?php echo esc_attr($uid); ?>][image_id]" value="<?php echo esc_attr(absint($slide['id'] ?? 0)); ?>">
                <button type="button" class="button evented-pick-image">انتخاب تصویر</button>
            </div>

            <div class="evented-slide-fields">
                <p>
                    <label>برچسب (اختیاری — روی تصویر نمایش داده می‌شود)</label>
                    <input type="text" class="widefat evented-slide-badge" name="slides[<?php echo esc_attr($uid); ?>][badge]" value="<?php echo esc_attr($badge); ?>" placeholder="مثلاً: دورهٔ ویژه">
                </p>
                <p>
                    <label>عنوان</label>
                    <input type="text" class="widefat evented-slide-title" name="slides[<?php echo esc_attr($uid); ?>][title]" value="<?php echo esc_attr($title); ?>" placeholder="عنوان بزرگ اسلایدر">
                </p>
                <p>
                    <label>توضیحات (اختیاری)</label>
                    <textarea class="widefat evented-slide-desc" name="slides[<?php echo esc_attr($uid); ?>][desc]" rows="2" placeholder="یک توضیح کوتاه زیر عنوان..."><?php echo esc_textarea($desc); ?></textarea>
                </p>
                <p>
                    <label>لینک دکمهٔ اسلایدر (اختیاری)</label>
                    <input type="text" dir="ltr" class="widefat evented-slide-link" name="slides[<?php echo esc_attr($uid); ?>][link]" value="<?php echo esc_attr($link); ?>" placeholder="https://…">
                </p>
            </div>
        </div>
        <div class="evented-slide-actions">
            <a href="#" class="evented-remove-slide">🗑 حذف این اسلایدر</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
