<?php

namespace app\admin\controller;

use app\admin\common\Cache;
use app\admin\common\Constant;
use app\admin\common\Tool;
use app\admin\model\College;
use app\admin\model\Payment;
use app\admin\model\Permission;
use app\admin\model\ReportPlan;
use app\admin\model\User;
use app\admin\model\Role;
use http\Client;
use think\Db;
use think\Exception;
class ClientCostController extends BaseController
{
    public function index()
    {
        return view();
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $date_start = $this->request->param('date_start');
        $date_end = $this->request->param('date_end');
        $length = $this->request->param('length');
        $map = $this->process_query('client.user_name|client.name|client.phone|agent.name');
        $map['client.deleted'] = '0';
        $map['client.type'] = Constant::USER_TYPE_CLIENT;
        if ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['client.parent_id'] = -1;
        }
        $map['client.client_type'] = Constant::CLIENT_TYPE_NORMAL;
        $order = 'client.id desc';
        $recordCount = db('user client')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $records = db('user client')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('client.id,client.user_name,client.name,agent.name as agent_name,client.role_id,client.phone,client.email,client.created_time,client.created_user_id,client.active,client.expired_date,client.sms_count,client.script_key,client.website,client.salesman,client.version,client.contact,client.rate,client.balance,client.salesman')->limit($start, $length)->order($order)->select();
        $ids = [];
        foreach ($records as $key => $item) {
            $ids[] = $item['id'];
        }
        $map_cost = ['user_id' => ['in', $ids]];
        $cost_total = ReportPlan::where($map_cost)->group('user_id')->column('sum(amount_client)', 'user_id');
        if (!empty($date_start) && !empty($date_end)) {
            $map_cost['date'] = ['between', [$date_start, $date_end]];
        } elseif (!empty($date_start)) {
            $map_cost['date'] = ['egt', $date_start];
        } elseif (!empty($date_end)) {
            $map_cost['date'] = ['elt', $date_end];
        }
        $cost_today = ReportPlan::where($map_cost)->group('user_id')->column('sum(amount_client)', 'user_id');
        $role_list = Cache::key_value('role');
        foreach ($records as $key => $item) {
            $records[$key]['version'] = get_value($item['version'], Constant::CLIENT_VERSION_LIST);
            $records[$key]['active'] = get_value($item['active'], Constant::ACTIVE_LIST);
            $records[$key]['role_id'] = get_value($item['role_id'], $role_list);
            $records[$key]['cost_today'] = get_value($item['id'], $cost_today);
            $records[$key]['cost_total'] = get_value($item['id'], $cost_total);
            if ($item['version'] == Constant::CLIENT_VERSION_CALCULATION) {
                $records[$key]['status'] = empty($item['balance']) || $item['balance'] < 1000 ? '余额不足' : '正常';
            } else {
                $records[$key]['status'] = empty($item['expired_date']) || time() > strtotime($item['expired_date']) ? '已过期' : '正常';
            }
        }
        return json(array('draw' => $this->request->param('draw'), "recordsTotal" => $recordCount, "recordsFiltered" => $recordCount, "data" => $records));
    }
    public function export()
    {
        $date_start = $this->request->param('date_start');
        $date_end = $this->request->param('date_end');
        $map = $this->process_query('client.user_name|client.name|client.phone|agent.name');
        $map['client.deleted'] = '0';
        $map['client.type'] = Constant::USER_TYPE_CLIENT;
        if ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['client.parent_id'] = -1;
        }
        $map['client.client_type'] = Constant::CLIENT_TYPE_NORMAL;
        $order = 'client.id desc';
        $records = db('user client')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('client.user_name,client.name,agent.name as agent_name,client.salesman,client.contact,client.phone,client.expired_date,client.balance,client.id')->order($order)->select();
        $ids = [];
        foreach ($records as $key => $item) {
            $ids[] = $item['id'];
        }
        $map_cost = ['user_id' => ['in', $ids]];
        $cost_total = ReportPlan::where($map_cost)->group('user_id')->column('sum(amount_client)', 'user_id');
        if (!empty($date_start) && !empty($date_end)) {
            $map_cost['date'] = ['between', [$date_start, $date_end]];
        } elseif (!empty($date_start)) {
            $map_cost['date'] = ['egt', $date_start];
        } elseif (!empty($date_end)) {
            $map_cost['date'] = ['elt', $date_end];
        }
        $cost_today = ReportPlan::where($map_cost)->group('user_id')->column('sum(amount_client)', 'user_id');
        foreach ($records as $key => $item) {
            $records[$key]['cost_total'] = get_value($item['id'], $cost_total);
            $records[$key]['cost_today'] = get_value($item['id'], $cost_today);
            unset($records[$key]['id']);
        }
        Tool::export_excel($records, ['客户账户', '客户名称', '代理商', '业务员', '联系人', '手机号', '有效期', '账户余额', '累计消费', '消费金额'], '客户消费记录' . time());
    }
}