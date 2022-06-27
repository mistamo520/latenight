<?php

namespace app\admin\controller;

use app\admin\model\Payment;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\User;
class PaymentController extends BaseController
{
    public function index()
    {
        return view();
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('agent.user_name|agent.name');
        $map['payment.deleted'] = '0';
        $map['payment.type'] = Constant::PAYMENT_TYPE_AGENT;
        $order = 'id desc';
        $count = db('payment')->join('user agent', 'agent.id = payment.user_id')->where($map)->count();
        $list = db('payment')->join('user agent', 'agent.id = payment.user_id')->where($map)->field('payment.id,payment.amount,payment.years,payment.sms_count,payment.user_id,payment.description,payment.created_time,payment.created_user_id,agent.name as user_name,agent.name as agent_name')->limit($start, $length)->order($order)->select();
        $user_list = User::column('name', 'id');
        foreach ($list as $key => $item) {
            $list[$key]['created_user_id'] = get_value($item['created_user_id'], $user_list);
        }
        return ['draw' => $this->request->param('draw'), 'recordsTotal' => $count, 'recordsFiltered' => $count, 'data' => $list];
    }
    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        if (!empty($id)) {
            $model = Payment::get($id);
            $edit_state = true;
        }
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        $user_list = User::column('name', 'id');
        $this->assign('user_list', $user_list);
        return view();
    }
    public function save()
    {
        $data = input('post.');
        $agent = User::get(['user_name' => $data['user_name'], 'type' => Constant::USER_TYPE_AGENT]);
        if (empty($agent)) {
            if (empty($model)) {
                return api_error('代理商账户不存在');
            }
        }
        if (empty($data['id'])) {
            $model = new Payment();
            $data['user_id'] = $agent->id;
            $data['deleted'] = 0;
            $data['type'] = Constant::PAYMENT_TYPE_AGENT;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
        } else {
            $model = Payment::get($data['id']);
            if (empty($model)) {
                return api_error('记录不存在');
            }
            $data['updated_time'] = date('Y-m-d H:i:s');
            $data['updated_user_id'] = $this->userId;
        }
        unset($data['user_name']);
        $model->data($data);
        $model->save();
        $agent->sms_count += $data['sms_count'];
        $agent->balance += $data['amount'];
        $agent->save();
        return api_success('保存成功');
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = Payment::get($id);
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
        $model = Payment::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
}