<?php

namespace app\product\controller;

use app\admin\common\Constant;
use app\admin\common\GetMacAddr;
use app\admin\common\NetTool;
use app\admin\common\Tool;
use app\admin\model\Message;
use app\admin\model\RankKeyword;
use app\admin\model\RankReport;
use app\admin\model\ReportKey;
use app\admin\model\ReportPlan;
use app\admin\model\User;
use think\Controller;
use think\Db;
use think\Exception;
class TestController extends Controller
{
    public function index()
    {
        $app_key = 'TKkBuUdp';
        $app_secrect = 'd56b0e40c2b6838a32e678e1b9962983ee01d741';
        $timestamp = time() . '000';
        $nonce = $timestamp;
        $trans_code = 'PERSON_INFO_SELECT';
        $biz_content = ["uniqueId" => "2019000", "receivedSeq" => $timestamp];
        $original_sign = "appkey=" . $app_key . "&nonce=" . $nonce . "&timestamp=" . $timestamp . "&transCode=" . $trans_code . "&appSecrect=" . $app_secrect;
        $sign = strtoupper(md5($original_sign));
        return ['appKey' => $app_key, 'sign' => $sign, 'timestamp' => $timestamp, 'nonce' => $nonce, 'transCode' => $trans_code, 'bizContent' => $biz_content];
    }
    private function generate_sign()
    {
    }
}