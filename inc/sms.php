<?php
/*
// sms.php file
*/

function send_pattern_sms($mobile, $code): bool
{
    $username = 'iranhp';
    $password = '@Fal#nic1415@';
    $template_id = intval('315445');

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
