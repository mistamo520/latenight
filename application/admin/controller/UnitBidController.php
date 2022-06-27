<?php

namespace app\admin\controller;

use app\admin\common\OceTool;
use app\admin\common\Tool;
use app\admin\model\UnitBid;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\User;
use app\admin\model\Unit;
class UnitBidController extends BaseController
{
    public function index()
    {
        $this->assign('title', $this->promoteType == Constant::PROMOTE_TYPE_OCE ? '计划' : '单元');
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            return view('unit_bid/index_client');
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            return view('unit_bid/index_agent');
        } else {
            return view('unit_bid/index');
        }
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('client.name|agent.name|unit.name');
        $map['unit.deleted'] = '0';
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['unit.user_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        }
        $map['unit.type'] = $this->promoteType;
        $order = 'id desc';
        $count = db('unit')->join('user client', 'client.id = unit.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('unit')->join('user client', 'client.id = unit.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('unit.id,unit.name as unit_name,unit.bid_price,client.name as client_name,agent.name as agent_name,unit.bid_time as created_time,unit.bid_user_id as created_user_id,baidu_id,baidu_price,agent_price')->limit($start, $length)->order($order)->select();
        $user_list = User::column('name', 'id');
        foreach ($list as $key => $item) {
            $list[$key]['created_user_id'] = get_value($item['created_user_id'], $user_list);
            if (empty($list[$key]['bid_price'])) {
                $list[$key]['bid_price'] = '未设置';
            } elseif ($this->user->type == Constant::USER_TYPE_ADMIN) {
                $list[$key]['bid_price'] = $item['baidu_price'] . ' / ' . $item['agent_price'] . ' / ' . $item['bid_price'];
            } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
                $list[$key]['bid_price'] = $item['agent_price'] . ' / ' . $item['bid_price'];
            }
        }
        return ['draw' => $this->request->param('draw'), 'recordsTotal' => $count, 'recordsFiltered' => $count, 'data' => $list];
    }
    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        if (!empty($id)) {
            $model = Unit::get($id);
            $edit_state = true;
        }
        $this->assign('percentage_list', Constant::BID_PERCENTAGE_LIST);
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        return view('unit_bid/_item_maintain');
    }
    public function update_api($baidu_id, $price)
    {
        if ($this->promoteType == Constant::PROMOTE_TYPE_OCE) {
            $advertiser_id = get_setting('oce.advertiser_id');
            $url = 'https://ad.oceanengine.com/open_api/2/ad/update/bid/';
            $params = ['advertiser_id' => $advertiser_id, 'data' => [['ad_id' => $baidu_id, 'bid' => $price]]];
            $access_token = OceTool::get_access_token();
            if (empty($access_token)) {
                return false;
            } else {
                $result = request_oce_api($url, ['Access-Token:' . $access_token, 'Content-Type:application/json'], 'POST', json_encode($params));
                if (empty($result['code'])) {
                    return true;
                }
                return false;
            }
        } elseif ($this->promoteType == Constant::PROMOTE_TYPE_QQ) {
            $access_token = json_decode(get_setting('qq.token'), true)['access_token'];
            if (empty($access_token)) {
                return false;
            }
            $advertiser_id = get_setting('qq.advertiser_id');
            $url = 'https://api.e.qq.com/v1.3/adgroups/update?account_id=' . $advertiser_id . '&timestamp=' . time() . '&nonce=' . microtime() . '&access_token=' . $access_token;
            $result = request_qq_api($url, 'POST', ['bid_amount' => $price * 100, 'account_id' => $advertiser_id, 'adgroup_id' => $baidu_id]);
            if (empty($result['code'])) {
                return true;
            } else {
                return false;
            }
        } else {
            $request_body = ["adgroupTypes" => [["adgroupId" => $baidu_id, "maxPrice" => $price]]];
            $header = ['username' => $this->settings['baidu.username'], 'password' => $this->settings['baidu.password'], 'token' => $this->settings['baidu.token']];
            $data = request_baidu_api('https://api.baidu.com/json/sms/service/AdgroupService/updateAdgroup', $request_body, $header);
            return $data['header']['status'] === 0;
        }
    }
    public function save()
    {
        $data = input('post.');
        $unit = Unit::get($data['id']);
        $unit->bid_price = $data['price'];
        $unit->bid_user_id = $this->userId;
        $unit->bid_time = get_time();
        $unit->agent_price = $data['price'] / $unit->user->rate;
        $unit->baidu_price = $data['price'] / ($unit->user->rate * $unit->user->agent->rate);
        $unit->save();
        $model = new UnitBid();
        $model->unit_id = $data['id'];
        $model->agent_price = $data['price'] / $unit->user->rate;
        $model->baidu_price = round($data['price'] / ($unit->user->rate * $unit->user->agent->rate), 2);
        $model->price = $data['price'];
        $model->user_id = $unit->user_id;
        $model->created_user_id = $this->userId;
        $model->save();
        Tool::remind(Constant::REMIND_TYPE_BID, $this->promoteType, $unit->user_id, '修改了' . ($this->promoteType == Constant::PROMOTE_TYPE_OCE ? '计划' : '单元') . ' [' . $unit->name . '] 的出价:' . $model->price);
        if (!empty($unit->baidu_id)) {
            $flag = $this->update_api($unit->baidu_id, $model->baidu_price);
            if ($flag) {
                return api_success('保存成功,同步平台成功');
            } else {
                return api_success('保存成功,同步平台失败');
            }
        } else {
            return api_success('保存成功');
        }
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = UnitBid::get($id);
        if (empty($model)) {
            return api_error('记录不存在');
        }
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
        $model = UnitBid::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
}