<?php

namespace app\admin\controller;

use app\admin\common\Cache;
use app\admin\common\Constant;
use app\admin\model\College;
use app\admin\model\User;
use app\admin\model\Role;
use think\Db;
use think\Exception;
class ScriptController extends BaseController
{
    public function index()
    {
        $key = User::where('id', $this->userId)->value('script_key');
        $url = get_setting('website.script_url');
        $code = '<script type="text/javascript" language="javascript" src="' . $url . '?' . $key . '" charset="utf-8"></script>';
        $this->assign('code', $code);
        return view();
    }
}