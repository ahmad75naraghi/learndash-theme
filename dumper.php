<?php
/**
 * AI Context Collector
 * این اسکریپت تمام فایل‌های کد را در یک فایل متنی واحد جمع می‌کند.
 * S2 fix: CLI-only execution — no longer accessible from the web (to prevent code leakage).
 * Usage: php dumper.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('دسترسی مجاز نیست.');
}

// تنظیمات
$outputFile = 'all_codes_for_ai.txt'; // نام فایل خروجی
$allowedExtensions = ['php', 'js', 'css', 'html', 'htm', 'sql', 'json']; // پسوندهای مجاز
$excludedFiles = [$outputFile, basename(__FILE__), '.htaccess', '.DS_Store']; // فایل‌هایی که نباید خوانده شوند

$rootPath = realpath(__DIR__);
$fileCounter = 0;

// ایجاد یا پاکسازی فایل خروجی
file_put_contents($outputFile, "--- PROJECT STRUCTURE & CODE COLLECTION ---\n");
file_put_contents($outputFile, "Generated on: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents($outputFile, "Root Path: " . $rootPath . "\n\n", FILE_APPEND);

// استفاده از RecursiveDirectoryIterator برای پیمایش تمام پوشه‌ها
$directory = new RecursiveDirectoryIterator($rootPath);
$iterator = new RecursiveIteratorIterator($directory);

foreach ($iterator as $file) {
    // نادیده گرفتن پوشه‌ها
    if ($file->isDir()) continue;

    $filePath = $file->getRealPath();
    $fileName = $file->getFilename();
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // چک کردن پسوند و فایل‌های استثنا
    if (in_array($extension, $allowedExtensions) && !in_array($fileName, $excludedFiles)) {
        
        $relativeName = str_replace($rootPath, '', $filePath);
        
        // ساخت هدر برای هر فایل
        $content = "\n\n" . str_repeat("=", 80) . "\n";
        $content .= "START OF FILE: " . $relativeName . "\n";
        $content .= str_repeat("-", 80) . "\n";
        
        // خواندن محتوای فایل
        $fileContent = file_get_contents($filePath);
        $content .= $fileContent;
        
        // ساخت فوتر برای هر فایل
        $content .= "\n" . str_repeat("-", 80) . "\n";
        $content .= "END OF FILE: " . $relativeName . "\n";
        $content .= str_repeat("=", 80) . "\n";

        // نوشتن در فایل نهایی
        file_put_contents($outputFile, $content, FILE_APPEND);
        $fileCounter++;
    }
}

echo "✅ عملیات با موفقیت انجام شد!<br>";
echo "تعداد $fileCounter فایل جمع‌آوری شد.<br>";
echo "فایل نهایی: <a href='$outputFile' target='_blank'>$outputFile</a>";

?>