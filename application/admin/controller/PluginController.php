<?php

namespace app\admin\controller;

use app\admin\common\Cache;
use app\admin\common\Constant;
use app\admin\model\College;
use app\admin\model\User;
use app\admin\model\Role;
use think\Db;
use think\Exception;
class PluginController extends BaseController
{
    public function index()
    {
        return view();
    }
}