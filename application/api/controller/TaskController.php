<?php

namespace app\api\controller;

use app\admin\common\Constant;
use app\admin\common\GetMacAddr;
use app\admin\common\NetTool;
use app\admin\common\OceTool;
use app\admin\common\Tool;
use app\admin\common\Tool360;
use app\admin\model\Message;
use app\admin\model\Product;
use app\admin\model\PromoteSetting;
use app\admin\model\RankKeyword;
use app\admin\model\RankReport;
use app\admin\model\ReportKey;
use app\admin\model\ReportPlan;
use app\admin\model\Setting;
use app\admin\model\Unit;
use app\admin\model\User;
use http\Client;
use think\Controller;
use think\Db;
use think\Exception;

class TaskController extends Controller
{
    var $settings;

    public function __construct()
    {
        //$this->sync();
    }
  public function sync_360()
    {
        $date = date('Y-m-d', strtotime('-1 day'));//date('Y-m-d');
        if (!empty(input('param.')['date'])) {
            $date = input('param.')['date'];
        }
        $setting_list = Setting::column('value', 'code');
        $this->settings = $setting_list;

        $this->sync_360_query($date);
        
    } 
    public function pause1()
    {
        $setting_list = Setting::column('value', 'code');
        $this->settings = $setting_list;
        $user_id = input('param.')['id'];

        $this->pause($user_id);
    }
    public function test1()
    { $pre_date = date('Y-m-d', strtotime('-1 day'));
        $setting_list = Setting::column('value', 'code');
        $this->settings = $setting_list;


        $this->sync_360_query($pre_date);
        ///$this->sync_360_plan($pre_date);
        
        return;
        $date = date('Y-m-d', strtotime('-1 day'));//date('Y-m-d');
        if (!empty(input('param.')['date'])) {
            $date = input('param.')['date'];
        }
        $setting_list = Setting::column('value', 'code');
        $this->settings = $setting_list;

        $this->sync_qq_plan($date);
        return;
        $this->sync_360_query($date);
        //$this->sync_baidu_query($date);
        return;
        $date = date('Y-m-d', strtotime('-1 day'));
        $setting_list = Setting::column('value', 'code');
        $this->settings = $setting_list;

        $this->sync_360_query($date);
        $this->sync_360_plan($date);
    }


    public function test()
    { $pre_date = date('Y-m-d', strtotime('-1 day'));
        $setting_list = Setting::column('value', 'code');
        $this->settings = $setting_list;


        $this->sync_360_query($pre_date);
        $this->sync_360_plan($pre_date);
        
        return;
        $date = date('Y-m-d', strtotime('-1 day'));//date('Y-m-d');
        if (!empty(input('param.')['date'])) {
            $date = input('param.')['date'];
        }
        $setting_list = Setting::column('value', 'code');
        $this->settings = $setting_list;

        $this->sync_qq_plan($date);
        return;
        $this->sync_360_query($date);
        //$this->sync_baidu_query($date);
        return;
        $date = date('Y-m-d', strtotime('-1 day'));
        $setting_list = Setting::column('value', 'code');
        $this->settings = $setting_list;

        $this->sync_360_query($date);
        $this->sync_360_plan($date);
    }

    public function sync()
    {
        $date = date('Y-m-d');
        echo $date;die;

        if (!empty(input('param.')['date'])) {
            $date = input('param.')['date'];
        }
        $setting_list = Setting::column('value', 'code');
        $this->settings = $setting_list;
        //$this->sync_rank();
        $this->sync_baidu_query($date);
        $this->sync_baidu_plan($date);
        $this->sync_oce_plan($date);
        $this->sync_qq_plan($date);
        if (!empty(input('param.')['date'])) {
        //
        $this->sync_360_query($date);
        $this->sync_360_plan($date);
        }
    }

    public function charge()
    {
        $pre_date = date('Y-m-d', strtotime('-1 day'));
        $setting_list = Setting::column('value', 'code');
        $this->settings = $setting_list;


        $this->sync_360_query($pre_date);
        $this->sync_360_plan($pre_date);
        $this->sync_baidu_plan($pre_date);
        $this->sync_oce_plan($pre_date);
        $this->sync_qq_plan($pre_date);
        $this->charge_plan();
    }

    public function sync_rank()
    {
        //请求方式：GET；请求URL：http://apidata.chinaz.com/CallAPI/BaiduPcRanking； 请求参数：key=52bd4243487347f09d99f8ed75a8a0c4&domainName=www.woocandy.com&keyword=2021年汽车摇号政策；
        //{"StateCode":1,"Reason":"成功","Result":{"CollectCount":"3580000","CrawlTime":"2020-12-08 15:33:15","Ranks":[{"RankStr":"1-5","Title":"2021年汽车指标摇号政策_北京汽车指标查询_WOCAN车务_汽车...","Url":"http://www.woocandy.com/","XiongzhangID":""}]}}
        //{"StateCode":1,"Reason":"成功","Result":{"CollectCount":"100000000","CrawlTime":"2020-12-08 15:59:04","Ranks":null}}
        $this->log('开始同步快排关键词');
        $list = RankKeyword::where(['updated_time' => ['lt', date('Y-m-d')]])->limit(10)->select();
        foreach ($list as $item) {
            if (!empty($item->domain) && !empty($item->keyword)) {
                Db::startTrans();
                try {
                    $data = file_get_contents("http://apidata.chinaz.com/CallAPI/BaiduPcRanking?key=52bd4243487347f09d99f8ed75a8a0c4&domainName={$item->domain}&keyword={$item->keyword}");
                    $data = json_decode($data, true);
                    $rank = 100;
                    if (!empty($data['Result']['Ranks'])) {
                        list($page, $rank) = explode('-', $data['Result']['Ranks'][0]['RankStr']);
                        $rank = ($page - 1) * 10 + $rank;
                    }
                    if ($rank <= 10) {
                        $report = new RankReport();
                        $report->user_id = $item->user_id;
                        $report->domain = $item->domain;
                        $report->keyword = $item->keyword;
                        $report->date = get_date();
                        $report->rank = $rank;
                        $report->amount_client = $item->price_client;
                        $report->amount_agent = $item->price_agent;
                        $report->save();
                        $client = User::get($item->user_id);
                        if (!empty($item->price_client)) {
                            $client->balance -= $item->price_client;
                            $client->save();
                            if ($client->balance < 1000) {
                                Tool::remind(Constant::REMIND_TYPE_BALANCE, Constant::PROMOTE_TYPE_CPC, $client->id, '账户余额不足1000，请联系客户充值');
                            }
                        }
                        if (!empty($item->price_agent)) {
                            $agent = User::get($client->parent_id);
                            $agent->balance -= $item->price_agent;
                            $agent->save();
                            if ($agent->balance < 1000) {
                                Tool::remind(Constant::REMIND_TYPE_BALANCE, Constant::PROMOTE_TYPE_CPC, $agent->id, '账户余额不足1000，请联系代理商充值');
                            }
                        }
                    }
                    $item->rank_last = empty($item->rank_current) ? $rank : $item->rank_current;
                    $item->rank_current = $rank;
                    if (empty($item->rank_create)) {
                        $item->rank_create = $rank;
                    }
                    $item->updated_time = get_time();
                    $item->save();
                    Db::commit();
                } catch (Exception $ex) {
                    $this->log('同步快排关键词异常：' . json_encode($item, JSON_UNESCAPED_UNICODE) . $ex->getMessage() . $ex->getTrace());
                    Db::rollback();
                }
            }
        }
        $this->log('结束同步快排关键词');
    }

    private function charge_plan()
    {
        $this->log('开始扣费');
        $list = ReportPlan::where(['charged' => 0, 'date' => ['lt', date('Y-m-d')]])->select();

        $this->log('获取扣费数据：' . json_encode($list, JSON_UNESCAPED_UNICODE));
        Db::startTrans();
        try {
            foreach ($list as $item) {
                $client = User::get($item->user_id);
                    $agent = User::get($client->parent_id);
                    if (!empty($client) && !empty($agent)) {
                   //账户余额不足消息提醒处理
                if (!empty($item->amount_client)) {
                    $client->balance -= $item->amount_client;
                    $client->save();

                    if ($client->balance < 200) {
                        Tool::remind(Constant::REMIND_TYPE_BALANCE, Constant::PROMOTE_TYPE_CPC, $client->id, '账户余额不足200元，已暂停相关业务，请联系客户充值');
                        $this->pause($client->id);
                    } else if ($client->balance < 1000) {
                        Tool::remind(Constant::REMIND_TYPE_BALANCE, Constant::PROMOTE_TYPE_CPC, $client->id, '账户余额不足1000，请联系客户充值');
                    }
                }
                if (!empty($item->amount_agent)) {
                    //扣费
                    $agent->balance -= $item->amount_agent;
                    $agent->save();
                    //扣费后余额不足1000 提醒消息
                    if ($agent->balance < 1000) {
                        Tool::remind(Constant::REMIND_TYPE_BALANCE, Constant::PROMOTE_TYPE_CPC, $agent->id, '账户余额不足1000，请联系代理商充值');
                    }
                }
                $item->charged = 1;
                $item->updated_user_id = 0;
                $item->updated_time = get_time();
                $item->save();
                    }
            }
            $client_list = User::where(['type' => Constant::USER_TYPE_CLIENT, 'version' => Constant::CLIENT_VERSION_ANNUAL, 'expired_date' => ['gt', date('Y-m-d'), strtotime('+60 days')]])->column('id');
            foreach ($client_list as $user_id) {
                Tool::remind(Constant::REMIND_TYPE_EXPIRED, Constant::PROMOTE_TYPE_CPC, $user_id, '账户即将到期，请联系客户续费');
            }
            Db::commit();


            $this->log('扣费成功');
        } catch (\Exception $e) {
            Db::rollback();
            echo  $e->getMessage();
            $this->log('扣费错误' . $e->getMessage());
        }
        $this->log('结束扣费');
    }


    public function sync_key($date)
    {
        $start = $date;// date('Y-m-d');
        $end = $date;//date('Y-m-d');
        $request_body = ["realTimeRequestType" =>
            ["reportType" => 14,
                "device" => 0,
                "levelOfDetails" => 11,
                "statRange" => 2,
                "platform" => 0,
                "unitOfTime" => 5,
                "number" => "1000",
                "pageIndex" => 1,
                "performanceData" => ["impression", "click", "cost", "cpc", "ctr"],
                "startDate" => $start,
                "endDate" => $end,
                "statIds" => null,
                "attributes" => null,
                "order" => true]
        ];

        $header = ['username' => $this->settings['baidu.username'], 'password' => $this->settings['baidu.password'], 'token' => $this->settings['baidu.token']];
        $data = request_baidu_api('https://api.baidu.com/json/sms/service/ReportService/getRealTimeData', $request_body, $header);
        if ($data['header']['status'] == 0) {
            $data = $data['body']['data'];

            $this->log('获取同步数据：' . json_encode($data, JSON_UNESCAPED_UNICODE));
            if (!empty($data)) {
                $record_list = [];
                foreach ($data as $item) {
                    $promote_type = strpos($item['name'][1], '爱采购') !== false ? Constant::PROMOTE_TYPE_B2B : Constant::PROMOTE_TYPE_CPC;
                    $client_name = str_replace('爱采购', '', str_replace('CPC', '', $item['name'][1]));
                    $client = User::get(['type' => Constant::USER_TYPE_CLIENT, 'user_name' => $client_name]);
                    if (!empty($client) && !empty($client->agent)) {
                        $report_key = ReportKey::get(['user_id' => $client->id, 'type' => $promote_type, 'date' => $item['date'], 'plan' => $item['name'][1], 'name' => $item['name'][3], 'unit' => $item['name'][2]]);
                        //if (empty($report_key) || $report_key-> || $item['date'] == date('Y-m-d')) {
                        $data_item = [];
                        $data_item['date'] = $item['date'];
                        $data_item['name'] = $item['name'][3];
                        $data_item['unit'] = $item['name'][2];
                        $data_item['plan'] = $item['name'][1];
                        $data_item['display_count'] = $item['kpis'][0];
                        $data_item['click_count'] = $item['kpis'][1];
                        $data_item['amount'] = $item['kpis'][2];;
                        $data_item['click_rate'] = $item['kpis'][4] * 100;
                        $data_item['created_user_id'] = 0;
                        $data_item['created_time'] = get_time();
                        $data_item['type'] = $promote_type;
                        $data_item['user_id'] = $client->id;
                        $data_item['amount_agent'] = $data_item['amount'] * $client->agent->rate;
                        $data_item['amount_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? 0 : $data_item['amount_agent'] * $client->rate;
                        $data_item['display_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['display_count'] * $client->rate : $data_item['display_count'];
                        $data_item['click_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['click_count'] * $client->rate : $data_item['click_count'];
                        if (empty($report_key)) {
                            $record_list[] = $data_item;
                        } else {
                            $data_item['id'] = $report_key['id'];
                            $record_list[] = $data_item;
                        }
                        //}
                    }
                }

                $model = new ReportKey();
                $model->saveAll($record_list);
            }

            $this->log('同步成功');
        } else {
            $this->log('同步失败');
        }
    }

    public function sync_baidu_query($date)
    {
        $this->log('开始同步搜索词报表' . $date);
        $start = $date;// date('Y-m-d');
        $end = $date;//date('Y-m-d');
        $request_body = ["realTimeQueryRequestType" =>
            ["reportType" => 6,
                "levelOfDetails" => 12,
                "performanceData" => ["impression", "click", "cost", "ctr"],
                "startDate" => $start,
                "endDate" => $end]
        ];

        $header = ['username' => $this->settings['baidu.username'], 'password' => $this->settings['baidu.password'], 'token' => $this->settings['baidu.token']];
        $data = request_baidu_api('https://api.baidu.com/json/sms/service/ReportService/getRealTimeQueryData', $request_body, $header);

        if ($data['header']['status'] == 0) {
            $data = $data['body']['data'];
            $this->log('获取同步数据：' . json_encode($data, JSON_UNESCAPED_UNICODE));
            if (!empty($data)) {
                $record_list = [];
                foreach ($data as $item) {
                    //判断类型时爱采购 还是搜索推广  1 => '爱采购', 2 => '搜索推广', 3 => '巨量推广', 4 => '360推广', 5 => '腾讯推广'
                    $promote_type = strpos($item['queryInfo'][1], '爱采购') !== false ? Constant::PROMOTE_TYPE_B2B : Constant::PROMOTE_TYPE_CPC;
                    $client_name = str_replace('爱采购', '', str_replace('CPC', '', $item['queryInfo'][1]));
                    $client = User::get(['type' => Constant::USER_TYPE_CLIENT, 'user_name' => $client_name]);
                    if (!empty($client) && !empty($client->agent)) {
                        ReportKey::where(['user_id' => $client->id, 'type' => $promote_type, 'date' => $item['date']])->delete();

                        //$report_key = ReportKey::get(['user_id' => $client->id, 'type' => $promote_type, 'date' => $item['date'], 'plan' => $item['queryInfo'][1], 'name' => $item['query'], 'unit' => $item['queryInfo'][2]]);
                        //if (empty($report_key) || $report_key-> || $item['date'] == date('Y-m-d')) {
                        $data_item = [];
                        $data_item['date'] = $item['date'];
                        $data_item['name'] = $item['query'];
                        $data_item['unit'] = $item['queryInfo'][2];
                        $data_item['plan'] = $item['queryInfo'][1];
                        $data_item['display_count'] = $item['kpis'][0];
                        $data_item['click_count'] = $item['kpis'][1];
                        $data_item['amount'] = $item['kpis'][2];;
                        $data_item['click_rate'] = $item['kpis'][3] * 100;
                        $data_item['created_user_id'] = 0;
                        $data_item['created_time'] = get_time();
                        $data_item['type'] = $promote_type;
                        $data_item['user_id'] = $client->id;
                        $data_item['amount_agent'] = $data_item['amount'] * $client->agent->rate;
                        $data_item['amount_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? 0 : $data_item['amount_agent'] * $client->rate;
                        $data_item['display_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['display_count'] * $client->rate : $data_item['display_count'];
                        $data_item['click_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['click_count'] * $client->rate : $data_item['click_count'];
                        //if (empty($report_key)) {
                        $record_list[] = $data_item;
                        //} else {
                        //    $data_item['id'] = $report_key['id'];
                        //    $record_list[] = $data_item;
                        //}
                        //}
                    }
                }
                $model = new ReportKey();
                $model->saveAll($record_list);
            }

            $this->log('同步成功');
        } else {
            $this->log('同步失败');
        }
        $this->log('结束同步搜索词报表');
    }

    private function sync_baidu_plan($date)
    {
        $this->log('开始同步计划报表' . $date);
        $start = $date;//date('Y-m-d');
        $end = $date;// date('Y-m-d');
        $request_body = ["realTimeRequestType" =>
            ["reportType" => 10,
                "device" => 0,
                "levelOfDetails" => 3,
                "statRange" => 2,
                "platform" => 0,
                "unitOfTime" => 5,
                "number" => "1000",
                "pageIndex" => 1,
                "performanceData" => ["impression", "click", "cost", "cpc", "ctr"],
                "startDate" => $start,
                "endDate" => $end,
                "statIds" => null,// [111, 222, 333],
                "attributes" => null,
                "order" => true]
        ];

        $header = ['username' => $this->settings['baidu.username'], 'password' => $this->settings['baidu.password'], 'token' => $this->settings['baidu.token']];
        $data = request_baidu_api('https://api.baidu.com/json/sms/service/ReportService/getRealTimeData', $request_body, $header);
        if ($data['header']['status'] == 0) {
            $data = $data['body']['data'];
            //var_dump($data);
            $this->log('获取同步数据：' . json_encode($data, JSON_UNESCAPED_UNICODE));
            if (!empty($data)) {
                $record_list = [];
                foreach ($data as $item) {
                    $promote_type = strpos($item['name'][1], '爱采购') !== false ? Constant::PROMOTE_TYPE_B2B : Constant::PROMOTE_TYPE_CPC;
                    $client_name = str_replace('爱采购', '', str_replace('CPC', '', $item['name'][1]));
                    $client = User::get(['type' => Constant::USER_TYPE_CLIENT, 'user_name' => $client_name]);

                    if (!empty($client) && !empty($client->agent)) {
                        $report_plan = ReportPlan::get(['user_id' => $client->id, 'type' => $promote_type, 'date' => $item['date']]);
                        if (empty($report_plan) || empty($report_plan->charged) || $item['date'] == date('Y-m-d')) {
                            $data_item = [];
                            $data_item['date'] = $item['date'];
                            $data_item['name'] = $item['name'][1];
                            $data_item['display_count'] = $item['kpis'][0];
                            $data_item['click_count'] = $item['kpis'][1];
                            $data_item['amount'] = $item['kpis'][2];;
                            $data_item['click_rate'] = $item['kpis'][4] * 100;
                            $data_item['created_user_id'] = 0;
                            $data_item['created_time'] = get_time();
                            $data_item['type'] = $promote_type;
                            $data_item['user_id'] = $client->id;
                            $data_item['amount_agent'] = $data_item['amount'] * $client->agent->rate;
                            $data_item['amount_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? 0 : $data_item['amount_agent'] * $client->rate;
                            $data_item['display_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['display_count'] * $client->rate : $data_item['display_count'];
                            $data_item['click_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['click_count'] * $client->rate : $data_item['click_count'];
                            if (empty($report_plan)) {
                                $record_list[] = $data_item;
                            } else {
                                //查询设置每日预算金额 当前消费金额大于每日预算 停止
                                $daily_budget = PromoteSetting::get(['user_id' => $client->id]);

                                if (!empty($daily_budget) && !empty($daily_budget->daily_budget)) {
                                    if ($data_item['amount'] >$daily_budget->daily_budget) {
                                        Tool::remind(Constant::REMIND_TYPE_BALANCE, Constant::PROMOTE_TYPE_CPC, $client->id, '当日消费金额超过预算金额，已暂停相关业务');
                                        $this->pause($client->id);
                                    }
                                }

                                //当前账户金额（账户余额-当日实时消费）小于200 停止
                                if (bcsub($client->balance,$data_item['amount'],2) < 200 ) {
                                    Tool::remind(Constant::REMIND_TYPE_BALANCE, Constant::PROMOTE_TYPE_CPC, $client->id, '账户余额不足200元，已暂停相关业务，请联系客户充值');
                                    $this->pause($client->id);
                                }
                                $data_item['id'] = $report_plan['id'];
                                $record_list[] = $data_item;
                            }
                        }
                    }
                }

                $model = new ReportPlan();
                $model->saveAll($record_list);
            }

            $this->log('同步成功');
        } else {
            $this->log('同步失败');
        }
        $this->log('结束同步计划报表');
    }

    private function sync_oce_plan($date)
    {
        $this->log('开始同步巨量计划报表' . $date);
        $advertiser_id = get_setting('oce.advertiser_id');
        $param = '{"page_size":"1000","group_by":["STAT_GROUP_BY_FIELD_ID"],"page":1,"time_granularity":"STAT_TIME_GRANULARITY_DAILY","order_type":"DESC","end_date":"' . $date . '","start_date":"' . $date . '","advertiser_id":"' . $advertiser_id . '","order_field":"cost"}';
        $access_token = OceTool::get_access_token();
        if (empty($access_token)) {
            $this->log('同步失败,获取access token 失败');
        } else {
            $url = 'https://open.oceanengine.com/open_api/2/report/ad/get/';
            $result = request_oce_api($url, ['Access-Token:' . $access_token, 'Content-Type:application/json'], 'GET', $param);
            if (empty($result['code']) && !empty($result['data']['list'])) {
                $record_list = [];
                $plan_list = Unit::where('type', Constant::PROMOTE_TYPE_OCE)->column('user_id', 'name');

                foreach ($result['data']['list'] as $item) {
                    $client_id = get_value($item['ad_name'], $plan_list);

                    if (!empty($client_id)) {
                        $client = User::get($client_id);

                        if(!empty($client) && !empty($client->agent)){
                        $report_plan = ReportPlan::get(['user_id' => $client->id, 'name' => $item['ad_name'], 'type' => Constant::PROMOTE_TYPE_OCE, 'date' => $date]);

                        if (empty($report_plan) || empty($report_plan->charged) || $date == date('Y-m-d')) {

                            $data_item = [];
                            $data_item['date'] = $date;
                            $data_item['name'] = $item['ad_name'];
                            $data_item['display_count'] = $item['show'];
                            $data_item['click_count'] = $item['click'];
                            $data_item['amount'] = $item['cost'];;
                            $data_item['click_rate'] = $item['ctr'];
                            $data_item['created_user_id'] = 0;
                            $data_item['created_time'] = get_time();
                            $data_item['type'] = Constant::PROMOTE_TYPE_OCE;
                            $data_item['user_id'] = $client->id;
                            $data_item['amount_agent'] = $data_item['amount'] * $client->agent->rate;
                            $data_item['amount_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? 0 : $data_item['amount_agent'] * $client->rate;
                            $data_item['display_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['display_count'] * $client->rate : $data_item['display_count'];
                            $data_item['click_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['click_count'] * $client->rate : $data_item['click_count'];
                            if (empty($report_plan)) {
                                $record_list[] = $data_item;
                            } else {
                                //查询设置每日预算金额 当前消费金额大于每日预算 停止
                                $daily_budget = PromoteSetting::get(['user_id' => $client->id]);

                                if (!empty($daily_budget) && !empty($daily_budget->daily_budget)) {

                                    if ($data_item['amount_client'] >$daily_budget->daily_budget) {

                                        Tool::remind(Constant::REMIND_TYPE_BALANCE, Constant::PROMOTE_TYPE_CPC, $client->id, '当日消费金额达到预算金额，已暂停相关业务');
                                        $this->pause($client->id);
                                    }
                                }
                                //当前账户金额（账户余额-当日实时消费）小于200 停止
                                if (bcsub($client->balance,$data_item['amount_client'],2) < 200 ) {

                                    Tool::remind(Constant::REMIND_TYPE_BALANCE, Constant::PROMOTE_TYPE_CPC, $client->id, '账户余额不足200元，已暂停相关业务，请联系客户充值');
                                    $this->pause($client->id);
                                }

                                $data_item['id'] = $report_plan['id'];
                                $record_list[] = $data_item;
                            }
                        }

                        }
                    }
                }

                $model = new ReportPlan();
                $model->saveAll($record_list);
                $this->log('同步成功');
            } else {
                $this->log('同步失败，CODE:' . $result['code'] . ',MESSAGE:' . $result['message']);
            }
        }
        $this->log('结束同步计划报表');
    }

    private function sync_qq_plan($date)
    {
        $this->log('开始同步腾讯计划报表' . $date);
        $advertiser_id = get_setting('qq.advertiser_id');
        $param = '{"page_size":"1000","group_by":["STAT_GROUP_BY_FIELD_ID"],"page":1,"time_granularity":"STAT_TIME_GRANULARITY_DAILY","order_type":"DESC","end_date":"' . $date . '","start_date":"' . $date . '","advertiser_id":"' . $advertiser_id . '","order_field":"cost"}';
        $access_token = json_decode(get_setting('qq.token'), true)['access_token'];
        if (empty($access_token)) {
            $this->log('同步失败,获取access token 失败');
        } else {
            $url = 'https://api.e.qq.com/v1.3/daily_reports/get?account_id=' . $advertiser_id . '&timestamp=' . time() . '&nonce=' . microtime() . '&access_token=' . $access_token . '&level=REPORT_LEVEL_ADGROUP&date_range={"start_date":"' . $date . '","end_date":"' . $date . '"}&fields=["adgroup_name","adgroup_id","click","impression","cpc","cost","ctr"]&group_by=["adgroup_id"]';
            $result = request_qq_api($url, 'GET');
            if (empty($result['code']) && !empty($result['data']['list'])) {
                $record_list = [];
                $plan_list = Unit::where('type', Constant::PROMOTE_TYPE_QQ)->column('user_id', 'name');
                foreach ($result['data']['list'] as $item) {
                    $client_id = get_value($item['adgroup_name'], $plan_list);
                    if (!empty($client_id)) {
                        $client = User::get($client_id);
                        $report_plan = ReportPlan::get(['user_id' => $client->id, 'name' => $item['adgroup_name'], 'type' => Constant::PROMOTE_TYPE_QQ, 'date' => $date]);
                        if (empty($report_plan) || empty($report_plan->charged) || $date == date('Y-m-d')) {
                            $data_item = [];
                            $data_item['date'] = $date;
                            $data_item['name'] = $item['adgroup_name'];
                            $data_item['display_count'] = $item['impression'];
                            $data_item['click_count'] = $item['click'];
                            $data_item['amount'] = $item['cost'] / 100;;
                            $data_item['click_rate'] = $item['ctr'] * 100;
                            $data_item['created_user_id'] = 0;
                            $data_item['created_time'] = get_time();
                            $data_item['type'] = Constant::PROMOTE_TYPE_QQ;
                            $data_item['user_id'] = $client->id;
                            $data_item['amount_agent'] = $data_item['amount'] * $client->agent->rate;
                            $data_item['amount_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? 0 : $data_item['amount_agent'] * $client->rate;
                            $data_item['display_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['display_count'] * $client->rate : $data_item['display_count'];
                            $data_item['click_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['click_count'] * $client->rate : $data_item['click_count'];
                            if (empty($report_plan)) {
                                $record_list[] = $data_item;
                            } else {
                                //查询设置每日预算金额 当前消费金额大于每日预算 停止
                                $daily_budget = PromoteSetting::get(['user_id' => $client->id]);

                                if (!empty($daily_budget) && !empty($daily_budget->daily_budget)) {
                                    if ($data_item['amount'] >$daily_budget->daily_budget) {
                                        Tool::remind(Constant::REMIND_TYPE_BALANCE, Constant::PROMOTE_TYPE_CPC, $client->id, '当日消费金额达到预算金额，已暂停相关业务');
                                        $this->pause($client->id);
                                    }
                                }
                                //当前账户金额（账户余额-当日实时消费）小于200 停止
                                if (bcsub($client->balance,$data_item['amount'],2) < 200 ) {
                                    Tool::remind(Constant::REMIND_TYPE_BALANCE, Constant::PROMOTE_TYPE_CPC, $client->id, '账户余额不足200元，已暂停相关业务，请联系客户充值');
                                    $this->pause($client->id);
                                }

                                $data_item['id'] = $report_plan['id'];
                                $record_list[] = $data_item;
                            }
                        }
                    }
                }

                $model = new ReportPlan();
                $model->saveAll($record_list);
                $this->log('同步成功');
            } else {
                $this->log('同步失败，CODE:' . $result['code'] . ',MESSAGE:' . $result['message']);
            }
        }
        $this->log('结束同步计划报表');
    }

   
    public function sync_360_query($date)
    {
        $this->log('开始同步360搜索词报表' . $date);
        $start = $date;// date('Y-m-d');
        $end = $date;//date('Y-m-d');
        $access_token = Tool360::get_access_token();
        if (empty($access_token)) {
            $this->log('结束同步计划报表获取access token失败');
        } else {
            ReportKey::where(['type' => Constant::PROMOTE_TYPE_360, 'date' => $date])->delete();
            $model = new ReportKey();
            $record_list = [];
            $client_list = [];
            $unit_list = Unit::where(['type' => Constant::PROMOTE_TYPE_360])->column('user_id', 'baidu_id');
            $url = 'https://api.e.360.cn/dianjing/report/queryword?startDate=' . $end . '&endDate=' . $end;
            $result = request_360_api($url, ['apiKey:' . $this->settings['360.appid'], 'accessToken:' . $access_token]);
            $this->log('获取同步数据：' . json_encode($result, JSON_UNESCAPED_UNICODE));
            if (empty($result['failures'])) {
                foreach ($result['querywordList'] as $key => $item) {
                    $promote_type = Constant::PROMOTE_TYPE_360;
                    //$unit = Unit::get(['baidu_id' => $item['campaignId'], 'type' => Constant::PROMOTE_TYPE_360]);
                    $user_id = get_value($item['campaignId'], $unit_list);
                    if (!empty($user_id)) {
                        if (key_exists($user_id, $client_list)) {
                            $client = $client_list[$user_id];
                        } else {
                            $client = User::get($user_id);
                            $client_list[$user_id] = $client;
                        }
                        
                        if (!empty($client) && !empty($client->agent)) {
                        
                        
                        $data_item = [];
                        $data_item['date'] = $item['date'];
                        $data_item['name'] = $item['queryword'];
                        $data_item['unit'] = $item['groupName'];
                        $data_item['plan'] = $item['campaignName'];
                        $data_item['display_count'] = $item['views'];
                        $data_item['click_count'] = $item['clicks'];
                        $data_item['amount'] = $item['totalCost'];
                        $data_item['click_rate'] = empty($item['views']) ? 0 : $item['clicks'] * 100 / $item['views'];
                        $data_item['created_user_id'] = 0;
                        $data_item['created_time'] = get_time();
                        $data_item['type'] = $promote_type;
                        $data_item['user_id'] = $client->id;
                        $data_item['amount_agent'] = $data_item['amount'] * $client->agent->rate;
                        $data_item['amount_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? 0 : $data_item['amount_agent'] * $client->rate;
                        $data_item['display_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['display_count'] * $client->rate : $data_item['display_count'];
                        $data_item['click_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['click_count'] * $client->rate : $data_item['click_count'];

                        $record_list[] = $data_item;
                        if (sizeof($record_list) > 100) {
                            $model->saveAll($record_list);
                            $record_list = [];
                        }
                        }
                    }
                }
            } else {
                $this->log('同步失败');
            }
            $url = 'https://api.e.360.cn/dianjing/report/queryword?type=mobile&startDate=' . $end . '&endDate=' . $end;
            $result = request_360_api($url, ['apiKey:' . $this->settings['360.appid'], 'accessToken:' . $access_token]);
            $this->log('获取同步数据：' . json_encode($result, JSON_UNESCAPED_UNICODE));
            if (empty($result['failures'])) {
                foreach ($result['querywordList'] as $key => $item) {
                    $promote_type = Constant::PROMOTE_TYPE_360;
                    //$unit = Unit::get(['baidu_id' => $item['campaignId'], 'type' => Constant::PROMOTE_TYPE_360]);
                    $user_id = get_value($item['campaignId'], $unit_list);
                    if (!empty($user_id)) {
                        if (key_exists($user_id, $client_list)) {
                            $client = $client_list[$user_id];
                        } else {
                            $client = User::get($user_id);
                            $client_list[$user_id] = $client;
                        }
                        if (!empty($client) && !empty($client->agent)) {
                        
                        $data_item = [];
                        $data_item['date'] = $item['date'];
                        $data_item['name'] = $item['queryword'];
                        $data_item['unit'] = $item['groupName'];
                        $data_item['plan'] = $item['campaignName'];
                        $data_item['display_count'] = $item['views'];
                        $data_item['click_count'] = $item['clicks'];
                        $data_item['amount'] = $item['totalCost'];
                        $data_item['click_rate'] = empty($item['views']) ? 0 : $item['clicks'] * 100 / $item['views'];
                        $data_item['created_user_id'] = 0;
                        $data_item['created_time'] = get_time();
                        $data_item['type'] = $promote_type;
                        $data_item['user_id'] = $client->id;
                        $data_item['amount_agent'] = $data_item['amount'] * $client->agent->rate;
                        $data_item['amount_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? 0 : $data_item['amount_agent'] * $client->rate;
                        $data_item['display_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['display_count'] * $client->rate : $data_item['display_count'];
                        $data_item['click_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['click_count'] * $client->rate : $data_item['click_count'];

                        $record_list[] = $data_item;
                        if (sizeof($record_list) > 100) {
                            $model->saveAll($record_list);
                            $record_list = [];
                        }
                        }
                    }
                }
            } else {
                $this->log('同步失败');
            }
            if (!empty($record_list)) {
                $model->saveAll($record_list);
            }
            $this->log('同步成功');
        }
    }

    private function sync_360_plan($date)
    {
        $this->log('开始同步360计划报表' . $date);
        $start = $date;//date('Y-m-d');
        $end = $date;// date('Y-m-d');

        $url = 'https://api.e.360.cn/dianjing/report/campaign?startDate=' . $end . '&endDate=' . $end;
        $access_token = Tool360::get_access_token();
        if (empty($access_token)) {
            $this->log('结束同步计划报表获取access token失败');

        } else {
            $result = request_360_api($url, ['apiKey:' . $this->settings['360.appid'], 'accessToken:' . $access_token]);
            $this->log('获取同步数据：' . json_encode($result, JSON_UNESCAPED_UNICODE));
            if (empty($result['failures'])) {
                $record_list = [];
                foreach ($result['campaignList'] as $item) {
                    $promote_type = Constant::PROMOTE_TYPE_360;
                    $unit = Unit::get(['baidu_id' => $item['campaignId'], 'type' => Constant::PROMOTE_TYPE_360]);

                    if (!empty($unit)) {
                        $report_plan = ReportPlan::get(['user_id' => $unit->user_id, 'type' => $promote_type, 'date' => $item['date']]);
                        if (empty($report_plan) || empty($report_plan->charged) || $item['date'] == date('Y-m-d')) {
                            $client = User::get($unit->user_id);
                        if (!empty($client) && !empty($client->agent)) {
                        
                            $data_item = [];
                            $data_item['date'] = $item['date'];
                            $data_item['name'] = $item['campaignName'];
                            $data_item['display_count'] = $item['views'];
                            $data_item['click_count'] = $item['clicks'];
                            $data_item['amount'] = $item['totalCost'];
                            $data_item['click_rate'] = empty($item['views']) ? 0 : $item['clicks'] * 100 / $item['views'];
                            $data_item['created_user_id'] = 0;
                            $data_item['created_time'] = get_time();
                            $data_item['type'] = $promote_type;
                            $data_item['user_id'] = $unit->user_id;
                            $data_item['amount_agent'] = $data_item['amount'] * $client->agent->rate;
                            $data_item['amount_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? 0 : $data_item['amount_agent'] * $client->rate;
                            $data_item['display_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['display_count'] * $client->rate : $data_item['display_count'];
                            $data_item['click_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['click_count'] * $client->rate : $data_item['click_count'];
                            if (empty($report_plan)) {
                                $record_list[] = $data_item;
                            } else {
                                //查询设置每日预算金额 当前消费金额大于每日预算 停止
                                $daily_budget = PromoteSetting::get(['user_id' => $client->id]);

                                if (!empty($daily_budget) && !empty($daily_budget->daily_budget)) {
                                    if ($data_item['amount'] >$daily_budget->daily_budget) {
                                        Tool::remind(Constant::REMIND_TYPE_BALANCE, Constant::PROMOTE_TYPE_CPC, $client->id, '当日消费金额超过预算金额，已暂停相关业务');
                                        $this->pause($client->id);
                                    }
                                }
                                //当前账户金额（账户余额-当日实时消费）小于200 停止
                                if (bcsub($client->balance,$data_item['amount'],2) < 200 ) {
                                    Tool::remind(Constant::REMIND_TYPE_BALANCE, Constant::PROMOTE_TYPE_CPC, $client->id, '账户余额不足200元，已暂停相关业务，请联系客户充值');
                                    $this->pause($client->id);
                                }
                                $data_item['id'] = $report_plan['id'];
                                $record_list[] = $data_item;
                            }
                        }}
                    }
                }

                $model = new ReportPlan();
                $model->saveAll($record_list);
                $this->log('同步成功');
            } else {

                $this->log('同步失败');
            }
        }

    }

    private function pause($user_id)
    {
        try {

            $unit_list = Unit::where('user_id', $user_id)->field('type,baidu_id,parent_id')->select();
            $baidu_list = [];
            $oce_list = [];
            $plan_360_list = [];
            $unit_360_list = [];
            $qq_list = [];
            foreach ($unit_list as $item) {
                if (!empty($item['baidu_id'])) {
                    $api_id = $item['baidu_id'];
                    if ($item['type'] == Constant::PROMOTE_TYPE_OCE) {
                        $oce_list[] = $api_id;
                    } elseif ($item['type'] == Constant::PROMOTE_TYPE_CPC || $item['type'] == Constant::PROMOTE_TYPE_B2B) {
                        $baidu_list[] = ['adgroupId' => $api_id, 'pause' => true];
                    } else if ($item['type'] == Constant::PROMOTE_TYPE_360) {
                        if ($item['parent_id'] == -1) {
                            $plan_360_list[] = $api_id;
                        } else {
                            $unit_360_list[] = $api_id;
                        }
                    } else if ($item['type'] == Constant::PROMOTE_TYPE_QQ) {
                        $qq_list[] = $api_id;
                    }
                }
            }

            if (!empty($baidu_list)) {
                $this->log('开始暂停百度单元');
                $request_body = ['adgroupTypes' => $baidu_list];
                $header = ['username' => $this->settings['baidu.username'], 'password' => $this->settings['baidu.password'], 'token' => $this->settings['baidu.token']];
                $result = request_baidu_api('https://api.baidu.com/json/sms/service/AdgroupService/updateAdgroup', $request_body, $header);

                $this->log('完成暂停百度单元 - ' . json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            if (!empty($oce_list)) {
                $this->log('开始暂停巨量单元');
                //$oce_list = urlencode(implode(',', $oce_list));

                $advertiser_id = get_setting('oce.advertiser_id');
                $url = 'https://open.oceanengine.com/open_api/2/ad/update/status/';
                $params = ['advertiser_id' => $advertiser_id, 'opt_status' => 'disable', 'ad_ids' => $oce_list];
                //{"advertiser_id":"123","opt_status":"disable","ad_ids":["1","2","3","4"]}
                $access_token = OceTool::get_access_token();
                if (empty($access_token)) {
                    $this->log('暂停巨量单元失败 授权无效');
                } else {
                    $result = request_oce_api($url, ['Access-Token:' . $access_token, 'Content-Type:application/json'], 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));

                    $this->log('完成暂停巨量单元 - ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                }
            }
            if (!empty($unit_360_list)) {

                $this->log('开始暂停360单元');
                $access_token = Tool360::get_access_token();
                if (empty($access_token)) {
                    $this->log('获取access token失败');
                } else {
                    foreach ($unit_360_list as $baidu_id) {
                        if (!empty($baidu_id)) {
                            $url = 'https://api.e.360.cn/dianjing/group/update?id=' . $baidu_id . '&status=pause';
                            $result = request_360_api($url, ['apiKey:' . $this->settings['360.appid'], 'accessToken:' . $access_token]);
                            if (!empty($result['failures'])) {
                                $this->log('暂停360单元失败' . $baidu_id . ':' . $result['failures'][0]['message']);
                            } else {
                                $this->log('暂停360单元成功' . $baidu_id);
                            }
                        }
                    }

                }
            }
            if (!empty($plan_360_list)) {

                $this->log('开始暂停360计划');
                $access_token = Tool360::get_access_token();
                if (empty($access_token)) {
                    $this->log('获取access token失败');
                } else {
                    foreach ($plan_360_list as $baidu_id) {
                        if (!empty($baidu_id)) {
                            $url = 'https://api.e.360.cn/dianjing/campaign/update?id=' . $baidu_id . '&status=pause';
                            $result = request_360_api($url, ['apiKey:' . $this->settings['360.appid'], 'accessToken:' . $access_token]);
                            if (!empty($result['failures'])) {
                                $this->log('暂停360计划失败' . $baidu_id . ':' . $result['failures'][0]['message']);
                            } else {
                                $this->log('暂停360计划成功' . $baidu_id);
                            }
                        }
                    }

                }
            }
            if (!empty($qq_list)) {
                $this->log('开始暂停QQ单元');
                $access_token = json_decode($this->settings['qq.token'], true)['access_token'];
                if (empty($access_token)) {
                    $this->log('获取access token失败');
                }
                foreach ($qq_list as $baidu_id) {
                    $advertiser_id = get_setting('qq.advertiser_id');
                    $url = 'https://api.e.qq.com/v1.3/adgroups/update?timestamp=' . time() . '&nonce=' . microtime() . '&access_token=' . $access_token . '';//AD_STATUS_NORMAL AD_STATUS_SUSPEND


                    $result = request_qq_api($url, 'POST', ['account_id' => $advertiser_id, 'adgroup_id' => $baidu_id, 'configured_status' => 'AD_STATUS_SUSPEND']);
                    if (!empty($result['code'])) {
                        $this->log('暂停QQ单元失败' . $baidu_id);
                    } else {
                        $this->log('暂停QQ单元成功' . $baidu_id);
                    }
                }
            }

        } catch (Exception $ex) {
            $this->log('暂停时发生错误 - ' . $ex->getMessage() . $ex->getTraceAsString());
        }

    }

    private function log($content)
    {
        if (!is_dir(ROOT_PATH . '/task_log/')) {



            mkdir(ROOT_PATH . '/task_log/');
        }
        $file = ROOT_PATH . '/task_log/' . date('Y-m-d') . ' log.txt';

        file_put_contents($file, date('H:i:s - ') . $content . PHP_EOL, FILE_APPEND);
    }

    public function product()
    {

        $temp_path = ROOT_PATH . '/public/product/temp.csv';
        if (!file_exists($temp_path)) {
            return '模板文件未找到';
        }
        $list = Product::all();
        $content = '';
        foreach ($list as $item) {
            $content .= ',';
            $content .= $item['id'] . ',';
            $content .= $item['name'] . ',';
            $content .= (empty($item['url_mobile']) ? $item['url_pc'] : $item['url_mobile']) . ',';
            $content .= get_setting('system.website_domain') . $item['image'] . ',';
            $content .= $item['brand'] . ',';
            $content .= $item['seller_name'] . ',';
            $content .= $item['seller_url'] . ',';
            $content .= $item['price'] . ',';
            $content .= 'RMB,';
            $content .= $item['company_name'] . ',';
            $content .= get_setting('system.website_domain') . $item['image2'] . ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= $item['url_pc'] . ',';
            $content .= ',';
            $content .= ',';
            $content .= $item['home_url_mobile'] . ',';
            $content .= $item['home_url_pc'] . ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= $item['category'] . ',';
            $content .= ',';
            $content .= ',';
            $content .= $item['sub_category'] . ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= ',';
            $content .= $item['company_code'] . ',';
            $content .= $item['delivery_province'] . ',';
            $content .= $item['delivery_city'] . ',';
            $content .= PHP_EOL;
        }

        $file_path = ROOT_PATH . '/public/product/list.csv';
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        copy($temp_path, $file_path);

        file_put_contents($file_path, $content, FILE_APPEND);
    }

}
