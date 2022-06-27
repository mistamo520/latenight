<?php

namespace app\admin\controller;

use app\admin\common\Tool;
use app\admin\model\Keyword;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\KeywordBid;
use app\admin\model\RankKeyword;
use app\admin\model\RankReport;
use app\admin\model\UnitBid;
use app\admin\model\Unit;
use app\admin\model\User;
use think\Db;
use think\Exception;
class RankKeywordController extends BaseController
{
    public function index()
    {
        return view();
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('client.name|agent.name|rank_keyword.name');
        $map['rank_keyword.deleted'] = '0';
        $order = $this->request->param('order');
        if (empty($order)) {
            $order = 'id desc';
        }
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['rank_keyword.user_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        }
        $count = db('rank_keyword')->join('user client', 'client.id = rank_keyword.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('rank_keyword')->join('user client', 'client.id = rank_keyword.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('rank_keyword.id,rank_keyword.user_id,domain,rank_keyword.keyword,rank_keyword.price_client,rank_keyword.price_agent,rank_keyword.rank_create,rank_current,rank_last,rank_keyword.updated_time,rank_keyword.created_time,rank_keyword.created_user_id,client.name as client_name,agent.name as agent_name')->limit($start, $length)->order($order)->select();
        $user_list = User::column('name', 'id');
        foreach ($list as $key => $item) {
            $list[$key]['created_user_id'] = get_value($item['created_user_id'], $user_list);
        }
        return ['draw' => $this->request->param('draw'), 'recordsTotal' => $count, 'recordsFiltered' => $count, 'data' => $list];
    }
    public function sync()
    {
        $list = RankKeyword::all();
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
                    Db::rollback();
                }
            }
        }
    }
    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        if (!empty($id)) {
            $model = RankKeyword::get($id);
            $edit_state = true;
        }
        $client_user_id = 0;
        $map = ['type' => Constant::USER_TYPE_CLIENT];
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $client_user_id = $this->userId;
        } else {
            if ($this->user->type == Constant::USER_TYPE_AGENT) {
                $map['parent_id'] = $this->userId;
            }
        }
        $user_list = User::where($map)->column('name', 'id');
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        $this->assign('client_user_id', $client_user_id);
        $this->assign('user_list', $user_list);
        return view();
    }
    public function save()
    {
        $data = input('post.');
        if ($this->is_exist(['user_id' => $data['user_id'], 'keyword' => $data['keyword'], 'domain' => $data['domain']], $data['id'])) {
            return api_error('记录重复');
        }
        if (empty($data['id'])) {
            $model = new RankKeyword();
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
            $data['updated_time'] = '2020-1-1';
        } else {
            $model = RankKeyword::get($data['id']);
            if (empty($model)) {
                return api_error('记录不存在');
            }
            $data['updated_user_id'] = $this->userId;
        }
        $model->data($data);
        $model->save();
        return api_success('保存成功');
    }
    public function add_baidu($baidu_id, $keyword)
    {
        $request_body = ["keywordTypes" => [["adgroupId" => $baidu_id, "keyword" => $keyword]]];
        $header = ['username' => $this->settings['baidu.username'], 'password' => $this->settings['baidu.password'], 'token' => $this->settings['baidu.token']];
        $data = request_baidu_api('https://api.baidu.com/json/sms/service/KeywordService/addWord', $request_body, $header);
        $id = '';
        if ($data['header']['status'] === 0 && !empty($data['body']['data'])) {
            $id = $data['body']['data'][0]['keywordId'];
        }
        return $id;
    }
    public function _item_bid()
    {
        $id = $this->request->param('id');
        $model = Keyword::get($id);
        $this->assign('model', $model);
        return view();
    }
    public function bid()
    {
        $data = input('post.');
        $model = Keyword::get($data['id']);
        if (empty($model)) {
            return api_error('记录不存在');
        }
        $model['updated_time'] = date('Y-m-d H:i:s');
        $model['updated_user_id'] = $this->userId;
        $model['price'] = $data['price'];
        $model->save();
        $bid = new KeywordBid();
        $bid->price = $data['price'];
        $bid->user_id = $model->user_id;
        $bid->keyword_id = $model->id;
        $bid->created_user_id = $this->userId;
        $bid->save();
        return api_success('保存成功');
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = Keyword::get($id);
        if (empty($model)) {
            return api_error('记录不存在');
        }
        Tool::remind(Constant::REMIND_TYPE_KEYWORD, Constant::PROMOTE_TYPE_CPC, $model->user_id, '删除了关键词-' . $model->name);
        $model->deleted = 1;
        $model->deleted_user_id = $this->userId;
        $model->deleted_time = date('Y-m-d H:i:s');
        $model->save();
        return api_success('删除成功');
    }
    private function is_exist($map, $id = '')
    {
        if (empty($map)) {
            return false;
        }
        if (!empty($id)) {
            $map['id'] = array('<>', $id);
        }
        $model = RankKeyword::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
}