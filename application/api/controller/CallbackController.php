<?php

namespace app\api\controller;

use app\admin\common\Constant;
use app\admin\common\GetMacAddr;
use app\admin\common\NetTool;
use app\admin\common\Tool;
use app\admin\model\Message;
use app\admin\model\RankKeyword;
use app\admin\model\RankReport;
use app\admin\model\ReportKey;
use app\admin\model\ReportPlan;
use app\admin\model\Setting;
use app\admin\model\User;
use think\Controller;
use think\Db;
use think\Exception;
class CallbackController extends Controller
{
    public function index()
    {
        $code = request()->param('auth_code');
        if (!empty($code)) {
            $settings = Setting::where(['type' => 'oce'])->column('value', 'code');
            $url = 'https://ad.oceanengine.com/open_api/oauth2/access_token/?app_id=' . $settings['oce.appid'] . '&secret=' . $settings['oce.secret'] . '&grant_type=auth_code&auth_code=' . $code;
            $result = request_oce_api($url, ['Content-Type' => 'application/json']);
            if (empty($result['code'])) {
                $token['access_token'] = $result['data']['access_token'];
                $token['expires_in'] = date('Y-m-d H:i:s', time() + $result['data']['expires_in']);
                $token['refresh_token'] = $result['data']['refresh_token'];
                $token['refresh_token_expires_in'] = date('Y-m-d H:i:s', time() + $result['data']['refresh_token_expires_in']);
                $setting = Setting::get(['code' => 'oce.token']);
                if (empty($setting)) {
                    $setting = new Setting();
                }
                $setting->type = 'oce';
                $setting->code = 'oce.token';
                $setting->value = json_encode($token);
                $setting->save();
                echo '授权成功，点击<a href="/admin/setting_oce">返回</a>';
            } else {
                echo '授权失败，消息：' . $result['message'] . '，点击<a href="/admin/setting_oce">返回</a>';
            }
        } else {
            echo '入口错误，返回<a href="/">登录</a>';
        }
    }
}