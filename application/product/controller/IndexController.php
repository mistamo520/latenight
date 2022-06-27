<?php

namespace app\product\controller;

use app\admin\common\Constant;
use app\admin\common\GetMacAddr;
use app\admin\common\NetTool;
use app\admin\common\Tool;
use app\admin\model\Message;
use app\admin\model\Product;
use app\admin\model\RankKeyword;
use app\admin\model\RankReport;
use app\admin\model\ReportKey;
use app\admin\model\ReportPlan;
use app\admin\model\User;
use think\Controller;
use think\Db;
use think\Exception;
class IndexController extends Controller
{
    public function index()
    {
        $id = input('param.')['id'];
        $product = Product::get($id);
        $product->description = str_replace(PHP_EOL, '<br>', $product->description);
        $product_names = Product::where('user_id', $product->user_id)->column('name');
        $product->names = implode(',', $product_names);
        $list = Product::order('rand()')->limit(5)->select();
        $product_list = Product::where('user_id', $product->user_id)->limit(10)->select();
        $this->assign('model', $product);
        $this->assign('product_list', $product_list);
        $this->assign('list', $list);
        return view();
    }
}