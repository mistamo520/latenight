<?php

namespace app\admin\controller;

use app\admin\common\Cache;
use app\admin\common\Constant;
use app\admin\model\College;
use app\admin\model\Payment;
use app\admin\model\Permission;
use app\admin\model\ReportPlan;
use app\admin\model\User;
use app\admin\model\Role;
use http\Client;
use think\Db;
use think\Exception;
class ClientController extends BaseController
{
    public function index()
    {
        return view();
    }
    public function get_list()
    {
        $start = $this->request->param('start');
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
        $records = db('user client')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('client.id,client.user_name,client.name,agent.name as agent_name,client.role_id,client.phone,client.email,client.created_time,client.created_user_id,client.active,client.expired_date,client.sms_count,client.script_key,client.website,client.salesman,client.version,client.contact,client.rate,client.balance')->limit($start, $length)->order($order)->select();
        $ids = [];
        foreach ($records as $key => $item) {
            $ids[] = $item['id'];
        }
        $role_list = Cache::key_value('role');
        foreach ($records as $key => $item) {
            $records[$key]['version'] = get_value($item['version'], Constant::CLIENT_VERSION_LIST);
            $records[$key]['active'] = get_value($item['active'], Constant::ACTIVE_LIST);
            $records[$key]['role_id'] = get_value($item['role_id'], $role_list);
            if ($item['version'] == Constant::CLIENT_VERSION_CALCULATION) {
                $records[$key]['status'] = empty($item['balance']) || $item['balance'] < 1000 ? '余额不足' : '正常';
            } else {
                $records[$key]['status'] = empty($item['expired_date']) || time() > strtotime($item['expired_date']) ? '已过期' : '正常';
            }
        }
        return json(array('draw' => $this->request->param('draw'), "recordsTotal" => $recordCount, "recordsFiltered" => $recordCount, "data" => $records));
    }
    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        if (!empty($id)) {
            $model = User::get($id);
            $edit_state = true;
            $key = $model->script_key;
            $url = get_setting('website.script_url');
            $code = '<script type="text/javascript" language="javascript" src="' . $url . '?' . $key . '" charset="utf-8"></script>';
            $model->code = $code;
        }
        $agent_list = User::where(['type' => Constant::USER_TYPE_AGENT])->order('id')->column('concat(id,\' - \',name)', 'id');
        $this->assign('is_admin', $this->isAdmin);
        $this->assign('active_list', Constant::ACTIVE_LIST);
        $this->assign('annual_rate_list', Constant::CLIENT_ANNUAL_RATE_LIST);
        $this->assign('version_list', Constant::CLIENT_VERSION_LIST);
        $this->assign('agent_list', $agent_list);
        $this->assign('edit_state', $edit_state);
        $this->assign('model', $model);
        return view();
    }
    public function _item_payment()
    {
        $id = $this->request->param('id');
        $model = User::get($id);
        $this->assign('days_list', ['30' => '30 天', '60' => '60 天', '90' => '90 天', '180' => '180 天', '365' => '365 天']);
        $this->assign('model', $model);
        return view();
    }
    public function save_payment()
    {
        $data = input('post.');
        $client = User::get($data['id']);
        if (empty($client)) {
            return api_error('记录不存在');
        }
        if (!$this->isAdmin && $client->parent_id != $this->userId) {
            return api_error('客户不存在');
        }
        if ($client->version == Constant::CLIENT_VERSION_CALCULATION) {
            if (empty($data['amount']) || !is_numeric($data['amount'])) {
                return api_error('请正确输入充值金额');
            }
            $data['days'] = 0;
        } else {
            $data['amount'] = 0;
        }
        Db::startTrans();
        try {
            $model = new Payment();
            $model['amount'] = $data['amount'];
            $model['days'] = $data['days'];
            $model['description'] = $data['description'];
            $model['sms_count'] = 0;
            $model['deleted'] = 0;
            $model['user_id'] = $client->id;
            $model['deleted'] = 0;
            $model['type'] = Constant::PAYMENT_TYPE_CLIENT;
            $model['created_user_id'] = $this->userId;
            $model['created_time'] = date('Y-m-d H:i:s');
            $model->save();
            if ($client->version == Constant::CLIENT_VERSION_CALCULATION) {
                $client->balance += $data['amount'];
            } else {
                if (empty($client->expired_date)) {
                    $expired_date = time() + $data['days'] * 24 * 60 * 60;
                } else {
                    $expired_date = strtotime($client->expired_date) + $data['days'] * 24 * 60 * 60;
                }
                $client->expired_date = date('Y-m-d 23:59:59', $expired_date);
            }
            $client->save();
            Db::commit();
            return api_success('保存成功');
        } catch (Exception $ex) {
            Db::rollback();
            return api_exception($ex);
        }
    }
    public function _item_script()
    {
        $id = $this->request->param('id');
        $model = User::get($id);
        $agent = User::get($model->parent_id);
        $key = $model->script_key;
        $url = get_setting('website.script_url');
        if (!empty($agent->website)) {
            $arr = explode('/', $url);
            $url = $agent->website . '/' . $arr[sizeof($arr) - 1];
        }
        $code = '<script type="text/javascript" language="javascript" src="' . $url . '?' . $key . '" charset="utf-8"></script>';
        $this->assign('code', $code);
        return view();
    }
    public function _reset_password()
    {
        $id = $this->request->param('id');
        $model = User::get($id);
        $this->assign('model', $model);
        return view();
    }
    public function client_login()
    {
        $id = $this->request->param('id');
        $model = User::get($id);
        if ($this->user->type == Constant::USER_TYPE_ADMIN || $this->user->type == Constant::USER_TYPE_AGENT && $model->parent_id == $this->userId) {
            $permission_list = Permission::where(['role_id' => $model->role_id])->column('name');
            $permission['menu_permission'] = $permission_list;
            foreach ($permission_list as $key => $value) {
                $permission_list[$key] = str_replace('_', '', $value);
            }
            $permission['controller_permission'] = $permission_list;
            session(Constant::SESSION_USER_PERMISSION, $permission);
            $base_user_id = session(Constant::SESSION_BASE_USER_ID);
            if (empty($base_user_id)) {
                session(Constant::SESSION_BASE_USER_ID, $this->userId);
            }
            session(Constant::SESSION_USER_ID, $model->id);
            $this->redirect(url('index/index'));
        } else {
            $this->redirect(url('index/unauthorized'));
        }
    }
    public function save()
    {
        $data = input('post.');
        if ($this->is_exist($data['user_name'], $data['id'])) {
            return json(array('status' => 0, "message" => '用户名已存在'));
        }
        if ($this->isAdmin && empty($data['parent_id'])) {
            return api_error('请选择一个代理');
        }
        $data['expired_date'] = empty($data['expired_date']) ? null : $data['expired_date'];
        if (!$this->isAdmin) {
            unset($data['expired_date']);
        }
        if (empty($data['id'])) {
            $model = new User();
            $data['password'] = md5($data['password']);
            $data['parent_id'] = empty($data['parent_id']) ? $this->userId : $data['parent_id'];
            $data['script_key'] = md5(microtime());
            $data['active'] = 1;
            $data['type'] = Constant::USER_TYPE_CLIENT;
            $data['client_type'] = Constant::CLIENT_TYPE_NORMAL;
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
        } else {
            $model = User::get($data['id']);
            $data['updated_user_id'] = $this->userId;
            $data['updated_time'] = date('Y-m-d H:i:s');
        }
        $data['role_id'] = $data['version'] == Constant::CLIENT_VERSION_ANNUAL ? Constant::ROLE_CLIENT_ANNUAL : Constant::ROLE_CLIENT_CALCULATION;
        unset($data['confirmpassword']);
        $model->data($data)->save();
        Cache::clear('user');
        return json(array('status' => 1, "message" => "保存成功"));
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = User::get($id);
        if (empty($model)) {
            return json(array('status' => 0, "message" => "用户不存在"));
        }
        $model->deleted = 1;
        $model->deleted_user_id = $this->userId;
        $model->deleted_time = date('Y-m-d H:i:s');
        $model->save();
        return json(array('status' => 1, "message" => "删除成功"));
    }
    private function is_exist($key, $id = '')
    {
        $where['user_name'] = $key;
        $where['deleted'] = 0;
        if (!empty($id)) {
            $where['id'] = array('<>', $id);
        }
        $list = db('user')->where($where)->count();
        if ($list > 0) {
            return true;
        }
        return false;
    }
    public function reset_password()
    {
        $data = input('post.');
        if (empty($data['id'])) {
            return json(array('status' => 0, "message" => '用户不存在'));
        }
        if (empty($data['password'])) {
            return json(array('status' => 0, "message" => '请输入密码'));
        }
        $model = User::get($data['id']);
        if (empty($model)) {
            return json(array('status' => 0, "message" => "用户不存在"));
        }
        $model->password = md5($data['password']);
        $model->save();
        return json(array('status' => 1, "message" => "修改成功"));
    }
}