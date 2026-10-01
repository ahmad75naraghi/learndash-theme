<?php
/** Runtime contract for the Persian certificate font and mPDF integration. */

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
function get_template_directory(): string { return dirname(__DIR__); }
function add_action(): void {}

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (! is_file($autoload)) {
    fwrite(STDERR, "Composer dependencies are missing.\n");
    exit(1);
}
require_once $autoload;
require_once dirname(__DIR__) . '/inc/certificates.php';

$temp = sys_get_temp_dir() . '/evented-cert-font-' . getmypid();
mkdir($temp, 0700, true);

try {
    [$config, $font] = shamiim_cert_mpdf_config($temp);
    if ('eventedcert' !== $font || empty($config['fontdata']['eventedcert'])) {
        throw new RuntimeException('The bundled Persian certificate font was not selected.');
    }

    $mpdf = new \Mpdf\Mpdf($config);
    $mpdf->SetDirectionality('rtl');
    $mpdf->WriteHTML('<div dir="rtl" lang="fa" style="font-family:eventedcert;font-weight:bold">گواهینامه برای کسری؛ پ ژ چ گ ی ک</div>');
    $pdf = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    if (0 !== strpos($pdf, '%PDF-') || strlen($pdf) < 10000) {
        throw new RuntimeException('mPDF did not create a valid font-embedded PDF.');
    }
} finally {
    foreach ((array) glob($temp . '/*') as $file) {
        if (is_file($file)) { unlink($file); }
    }
    @rmdir($temp);
}

echo "Certificate Persian font test passed.\n";
