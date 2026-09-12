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
        $client = new SoapClient("https://api.payamak-panel.com/post/Send.asmx?wsdl", [
            'encoding' => 'UTF-8'
        ]);

        $params = [
            'username' => $username,
            'password' => $password,
            'to' => $mobile,
            'bodyId' => $template_id,
            'text' =>  is_array($code) ? $code : [$code]
        ];

        $response = $client->SendByBaseNumber($params);
        // بررسی پاسخ API - استفاده از SendByBaseNumberResult
        if (isset($response->SendByBaseNumberResult) && $response->SendByBaseNumberResult > 0) {
            return true;
        }

        return false;
    } catch (Exception $e) {
        return false;
    }
}
