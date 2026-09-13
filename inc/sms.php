<?php
/*
// sms.php file
*/

/**
 * ارسال پیامک الگویی (payamak-panel).
 *
 * @param string       $mobile      شمارهٔ گیرنده.
 * @param string|array $code        مقدار(های) متغیر الگو.
 * @param int          $template_id شناسهٔ الگو؛ ۰ = الگوی OTP از تنظیمات.
 */
function send_pattern_sms($mobile, $code, $template_id = 0): bool
{
    $username    = (string) (function_exists('evented_opt') ? evented_opt('sms_username', '') : '');
    $password    = (string) (function_exists('evented_opt') ? evented_opt('sms_password', '') : '');
    $template_id = (int) $template_id ?: intval(function_exists('evented_opt') ? evented_opt('sms_body_id', 0) : 0);

    if ('' === $username || '' === $password || !$template_id) {
        error_log('evented-edu: SMS credentials are not configured (Appearance → Theme Settings → SMS).');
        return false;
    }
    if (!class_exists('SoapClient')) {
        error_log('evented-edu: PHP SOAP extension is missing.');
        return false;
    }

    try {
        // timeout کوتاه تا در صورت down بودن سرویس پیامک، صفحهٔ ورود قفل نشود
        $prev_timeout = ini_get('default_socket_timeout');
        @ini_set('default_socket_timeout', '8');
        $client = new SoapClient("https://api.payamak-panel.com/post/Send.asmx?wsdl", [
            'encoding'           => 'UTF-8',
            'connection_timeout' => 8,
            'exceptions'         => true,
            'cache_wsdl'         => defined('WSDL_CACHE_DISK') ? WSDL_CACHE_DISK : 1,
            'stream_context'     => stream_context_create(array('http' => array('timeout' => 8))),
        ]);

        $params = [
            'username' => $username,
            'password' => $password,
            'to' => $mobile,
            'bodyId' => $template_id,
            'text' =>  is_array($code) ? $code : [$code]
        ];

        $response = $client->SendByBaseNumber($params);
        if ($prev_timeout !== false) {
            @ini_set('default_socket_timeout', (string) $prev_timeout);
        }
        // بررسی پاسخ API - استفاده از SendByBaseNumberResult
        if (isset($response->SendByBaseNumberResult) && $response->SendByBaseNumberResult > 0) {
            return true;
        }
        error_log('evented-edu: SMS API returned ' . (isset($response->SendByBaseNumberResult) ? $response->SendByBaseNumberResult : 'no result'));
        return false;
    } catch (Throwable $e) {
        if (isset($prev_timeout) && $prev_timeout !== false) {
            @ini_set('default_socket_timeout', (string) $prev_timeout);
        }
        error_log('evented-edu: SMS send failed — ' . $e->getMessage());
        return false;
    }
}
