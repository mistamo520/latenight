<?php

namespace app\admin\controller;

use app\admin\common\Cache;
use app\admin\model\Article;
use app\admin\model\User;
use think\Controller;
class TestController extends Controller
{
    public function test()
    {
        header('Access-Control-Allow-Origin:*');
        return $this->request->domain();
    }
}