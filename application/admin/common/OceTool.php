<?php

namespace app\admin\common;

use app\admin\model\Setting;
class OceTool
{
    public static function get_access_token()
    {
        $settings = Setting::where(['type' => 'oce'])->column('value', 'code');
        $token = json_decode($settings['oce.token'], true);
        if (strtotime($token['expires_in']) > time()) {
            return $token['access_token'];
        } else {
            if (strtotime($token['refresh_token_expires_in']) > time()) {
                $url = 'https://ad.oceanengine.com/open_api/oauth2/refresh_token/?app_id=' . $settings['oce.appid'] . '&secret=' . $settings['oce.secret'] . '&grant_type=refresh_token&refresh_token=' . $token['refresh_token'];
                $result = request_oce_api($url, ['Content-Type' => 'application/json']);
                if (empty($result['code'])) {
                    $token['access_token'] = $result['data']['access_token'];
                    $token['expires_in'] = date('Y-m-d H:i:s', time() + $result['data']['expires_in']);
                    $token['refresh_token'] = $result['data']['refresh_token'];
                    $token['refresh_token_expires_in'] = date('Y-m-d H:i:s', time() + $result['data']['refresh_token_expires_in']);
                    $setting = Setting::get(['code' => 'oce.token']);
                    $setting->type = 'oce';
                    $setting->code = 'oce.token';
                    $setting->value = json_encode($token);
                    $setting->save();
                    return $token['access_token'];
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }
}