<?php

namespace app\admin\common;

use app\admin\model\Setting;
class Tool360
{
    public static function get_access_token()
    {
        $settings = Setting::where(['type' => '360'])->column('value', 'code');
        $token = json_decode($settings['360.token'], true);
        if (strtotime($token['expires_in']) > time()) {
            return $token['access_token'];
        } else {
            $url = 'https://api.e.360.cn/uc/account/clientLogin?username=' . $settings['360.username'] . '&passwd=' . $settings['360.password'];
            $result = request_oce_api($url, ['Content-Type:application/x-www-form-urlencoded', 'apiKey:' . $settings['360.appid']]);
            if (!empty($result['accessToken'])) {
                $token = [];
                $token['access_token'] = $result['accessToken'];
                $token['expires_in'] = date('Y-m-d H:i:s', time() + 9 * 60 * 60);
                $setting = Setting::get(['code' => '360.token']);
                $setting->type = '360';
                $setting->code = '360.token';
                $setting->value = json_encode($token);
                $setting->save();
                return $token['access_token'];
            } else {
                return false;
            }
        }
    }
}