<?php

namespace app\admin\controller;

use think\Cache;
class CacheController extends BaseController
{
    public function index()
    {
        return view();
    }
    public function clear()
    {
        Cache::clear();
        return json(array('status' => 1, "message" => "清除成功"));
    }
}