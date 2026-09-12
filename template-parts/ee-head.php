<?php
/**
 * سرآغاز سند HTML برای قالب‌های پوستهٔ «evented-edu»
 *
 * از `<!DOCTYPE html>` تا باز شدن `<body>` و فراخوانی `wp_head()`.
 * بستن سند در template-parts/ee-footer.php انجام می‌شود.
 *
 * آرگومان‌های اختیاری (get_template_part):
 *   ee_body_class — کلاس(های) اضافی برای تگ body.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_body_class = isset($args['ee_body_class']) ? (string) $args['ee_body_class'] : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(trim('ee-home ee-body-pad ' . $ee_body_class)); ?>>
<?php wp_body_open(); ?>
