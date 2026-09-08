<?php
echo '<div style="background: #fff3cd; color: #856404; padding: 20px; margin: 20px; border: 1px solid #ffeeba; border-radius: 5px; direction: ltr; text-align: left; z-index: 9999; position: relative; font-family: monospace;">';
echo '<h3 style="margin-top:0;">🔍 WordPress Debug Info</h3>';

// ۱. نام فایلی که در حال اجراست
global $template;
echo '<strong>Current Template File:</strong> ' . basename($template) . '<hr>';

// ۲. تشخیص نوع صفحه از نگاه وردپرس
echo '<strong>WordPress Conditionals (What WP thinks this page is):</strong><br>';
if ( is_front_page() ) echo '✅ is_front_page()<br>';
if ( is_home() ) echo '✅ is_home() (Blog Index)<br>';
if ( is_page() ) echo '✅ is_page() -> Page ID: ' . get_queried_object_id() . '<br>';
if ( is_single() ) echo '✅ is_single()<br>';
if ( is_archive() ) echo '✅ is_archive()<br>';
if ( is_post_type_archive() ) echo '✅ is_post_type_archive()<br>';
if ( is_tax() ) echo '✅ is_tax() (Taxonomy)<br>';
if ( is_404() ) echo '✅ is_404() (Not Found - Permalink Issue!)<br>';
echo '<hr>';

// ۳. اطلاعات کوئری (این آبجکت شامل دیتای برگه‌، پست‌تایپ یا دسته‌بندی فعلی است)
echo '<strong>Queried Object Data:</strong>';
echo '<pre style="background: #f8f9fa; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow: auto; font-size: 13px;">';
print_r( get_queried_object() );
echo '</pre>';

// ۴. متغیرهای اصلی کوئری
global $wp_query;
echo '<strong>Query Vars:</strong>';
echo '<pre style="background: #f8f9fa; padding: 10px; border: 1px solid #ddd; max-height: 200px; overflow: auto; font-size: 13px;">';
print_r( $wp_query->query_vars );
echo '</pre>';

echo '</div>';
?>