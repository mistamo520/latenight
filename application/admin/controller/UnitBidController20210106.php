<?php

namespace app\admin\controller;

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
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            return view('index_client');
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            return view('index_agent');
        } else {
            return view();
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
        $map['unit.type'] = Constant::PROMOTE_TYPE_B2B;
        $order = 'id desc';
        $count = db('unit')->join('user client', 'client.id = unit.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('unit')->join('user client', 'client.id = unit.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('unit.id,unit.name as unit_name,unit.bid_price,client.name as client_name,agent.name as agent_name,unit.bid_time as created_time,unit.bid_user_id as created_user_id,baidu_id')->limit($start, $length)->order($order)->select();
        $user_list = User::column('name', 'id');
        foreach ($list as $key => $item) {
            $list[$key]['created_user_id'] = get_value($item['created_user_id'], $user_list);
            if (empty($list[$key]['price'])) {
                $list[$key]['price'] = '未设置';
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
        return view();
    }
    public function update_baidu($baidu_id, $price)
    {
        $request_body = ["adgroupTypes" => [["adgroupId" => $baidu_id, "maxPrice" => $price]]];
        $data = request_baidu_api('https://api.baidu.com/json/sms/service/AdgroupService/updateAdgroup', $request_body);
        return $data['header']['status'] === 0;
    }
    public function save()
    {
        $data = input('post.');
        $unit = Unit::get($data['id']);
        $unit->bid_price = $data['price'];
        $unit->bid_user_id = $this->userId;
        $unit->bid_time = get_time();
        $unit->save();
        $client_rate = $unit->user->rate;
        $agent_rate = $unit->user->agent->rate;
        $model = new UnitBid();
        $model->unit_id = $data['id'];
        $model->price = $data['price'];
        $model->baidu_price = $data['price'] / ($agent_rate * $client_rate);
        $model->agent_price = $data['price'] / $agent_rate;
        $model->user_id = $unit->user_id;
        $model->created_user_id = $this->userId;
        $model->save();
        Tool::remind(Constant::REMIND_TYPE_BID, Constant::PROMOTE_TYPE_B2B, $unit->user_id, '修改了单元 [' . $unit->name . '] 的出价:' . $model->price);
        if (!empty($unit->baidu_id)) {
            $flag = $this->update_baidu($unit->baidu_id, $model->baidu_price);
            if ($flag) {
                return api_success('保存成功,同步百度后台成功');
            } else {
                return api_success('保存成功,同步百度后台失败');
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