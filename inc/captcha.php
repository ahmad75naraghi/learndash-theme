<?php
session_start();
// 1. تولید کد و ذخیره در سشن
$captcha_code = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
$_SESSION["captcha_code"] = $captcha_code;

// تبدیل اعداد به فارسی
$english_digits = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
$persian_digits = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
$display_code = str_replace($english_digits, $persian_digits, $captcha_code);

// تنظیم ابعاد
$captcha_width = 130;
$captcha_height = 40;
$target_layer = imagecreatetruecolor($captcha_width, $captcha_height);

// --- تنظیمات ترنسپرنت ---
imagealphablending($target_layer, false);
imagesavealpha($target_layer, true);
$transparent_bg = imagecolorallocatealpha($target_layer, 0, 0, 0, 127);
imagefill($target_layer, 0, 0, $transparent_bg);
// -----------------------

// رنگ متن (مشکی کامل)
$captcha_text_color = imagecolorallocate($target_layer, 27, 77, 113);

$font_path = dirname(__DIR__) . '/assets/fonts/DanaVF.ttf';
if (!file_exists($font_path)) { die('Error: Font not found '. $font_path); }

$font_size = 24; 
$text_x = 10;
$text_y = 33;

// --- تکنیک ضخیم کردن متن (Stroke Simulation) ---
// به جای یک بار، متن را در مختصات اطراف هم چاپ می‌کنیم
// این حلقه متن را در 9 نقطه (مرکز و 8 جهت اطرافش) چاپ می‌کند که باعث ضخامت زیاد می‌شود

// میزان ضخامت (1 یعنی یک پیکسل در هر جهت)
$stroke_thickness = 1; 

// نکته: چون پس زمینه ترنسپرنت است، باید blending را موقتا برای متن روشن کنیم
// تا لبه‌های متن نرم (Anti-aliased) دیده شود و پیکسلی نشود.
imagealphablending($target_layer, true); 
for ($dx = ($stroke_thickness * -1); $dx <= $stroke_thickness; $dx++) {
    for ($dy = ($stroke_thickness * -1); $dy <= $stroke_thickness; $dy++) {
        imagettftext(
            $target_layer, 
            $font_size, 
            0, 
            $text_x + $dx, // جابجایی در محور افقی
            $text_y + $dy, // جابجایی در محور عمودی
            $captcha_text_color, 
            $font_path, 
            $display_code
        );
    }
}

header("Content-type: image/png");
imagepng($target_layer);
imagedestroy($target_layer);
?>