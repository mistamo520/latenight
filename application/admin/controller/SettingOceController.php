<?php

namespace app\admin\controller;

use app\admin\common\Tool;
use app\admin\model\Location;
use app\admin\model\Product;
use app\admin\model\PromoteSetting;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\User;
class SettingOceController extends BaseController
{
    public function index()
    {
        $setting['appid'] = get_setting('oce.appid');
        $setting['redirect_url'] = urlencode(get_setting('oce.redirect_url'));
        $setting['token'] = get_setting('oce.token');
        $this->assign('setting', $setting);
        return view();
    }
}