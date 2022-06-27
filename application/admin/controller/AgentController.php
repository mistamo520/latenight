<?php

namespace app\admin\controller;

use app\admin\common\Cache;
use app\admin\common\Constant;
use app\admin\model\College;
use app\admin\model\Payment;
use app\admin\model\Permission;
use app\admin\model\User;
use app\admin\model\Role;
use think\Db;
use think\Exception;
class AgentController extends BaseController
{
    public function index()
    {
        return view();
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $role_id = $this->request->param('role_id');
        $length = $this->request->param('length');
        $map = $this->process_query('user_name|name|phone');
        $map['deleted'] = '0';
        $map['type'] = Constant::USER_TYPE_AGENT;
        if ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['parent_id'] = $this->userId;
        }
        if (!empty($role_id)) {
            $map['role_id'] = $role_id;
        }
        $order = 'id desc';
        $recordCount = db('user')->where($map)->count();
        $records = db('user')->where($map)->field('id,user_name,name,role_id,phone,email,created_time,created_user_id,active,website,sms_count,balance,oem_name,contact,salesman,rate')->limit($start, $length)->order($order)->select();
        $role_list = Role::column('name', 'id');
        foreach ($records as $key => $item) {
            $records[$key]['active'] = get_value($item['active'], Constant::ACTIVE_LIST);
            $records[$key]['role_id'] = get_value($item['role_id'], $role_list);
            $records[$key]['client_count'] = User::where('parent_id', $item['id'])->count();
            $records[$key]['status'] = empty($item['balance']) || $item['balance'] < 1000 ? '余额不足' : '正常';
        }
        return json(array('draw' => $this->request->param('draw'), "recordsTotal" => $recordCount, "recordsFiltered" => $recordCount, "data" => $records));
    }
    public function agent_login()
    {
        $id = $this->request->param('id');
        $model = User::get($id);
        if ($this->user->type == Constant::USER_TYPE_ADMIN) {
            $permission_list = Permission::where(['role_id' => $model->role_id])->column('name');
            $permission['menu_permission'] = $permission_list;
            foreach ($permission_list as $key => $value) {
                $permission_list[$key] = str_replace('_', '', $value);
            }
            $permission['controller_permission'] = $permission_list;
            session(Constant::SESSION_USER_PERMISSION, $permission);
            session(Constant::SESSION_BASE_USER_ID, $this->userId);
            session(Constant::SESSION_USER_ID, $model->id);

            $this->redirect(url('index/index'));
        } else {
            $this->redirect(url('index/unauthorized'));
        }
    }
    public function _item_payment()
    {
        $id = $this->request->param('id');
        $model = null;
        if (!empty($id)) {
            $model = User::get($id);
        }
        $this->assign('model', $model);
        return view();
    }
    public function save_payment()
    {
        $data = input('post.');
        $agent = User::get($data['id']);
        if (empty($agent)) {
            return api_error('记录不存在');
        }
        Db::startTrans();
        try {
            $model = new Payment();
            $model['amount'] = $data['amount'];
            $model['description'] = $data['description'];
            $model['deleted'] = 0;
            $model['user_id'] = $agent->id;
            $model['deleted'] = 0;
            $model['type'] = Constant::PAYMENT_TYPE_AGENT;
            $model['created_user_id'] = $this->userId;
            $model['created_time'] = date('Y-m-d H:i:s');
            $model->save();
            if (!empty($data['sms_count'])) {
                $agent->sms_count += $data['sms_count'];
            }
            if (!empty($data['amount'])) {
                $agent->balance += $data['amount'];
            }
            $agent->save();
            Db::commit();
            return api_success('保存成功');
        } catch (Exception $ex) {
            Db::rollback();
            return api_exception($ex);
        }
    }
    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        if (!empty($id)) {
            $model = User::get($id);
            $edit_state = true;
        }
        $this->assign('is_admin', $this->isAdmin);
        $this->assign('role_list', [Constant::ROLE_AGENT => '代理']);
        $this->assign('active_list', Constant::ACTIVE_LIST);
        $this->assign('edit_state', $edit_state);
        $this->assign('model', $model);
        return view();
    }
    public function _reset_password()
    {
        $id = $this->request->param('id');
        $model = User::get($id);
        $this->assign('model', $model);
        return view();
    }
    public function save()
    {
        $data = input('post.');
        if ($this->is_exist($data['user_name'], $data['id'])) {
            return json(array('status' => 0, "message" => '用户名已存在'));
        }
        $data['role_id'] = Constant::ROLE_AGENT;
        if (empty($data['id'])) {
            $model = new User();
            $data['password'] = md5($data['password']);
            $data['type'] = Constant::USER_TYPE_AGENT;
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
            $data['role_id'] = Constant::ROLE_AGENT;
        } else {
            $model = User::get($data['id']);
            $data['updated_user_id'] = $this->userId;
            $data['updated_time'] = date('Y-m-d H:i:s');
        }
        $file = $this->request->file('picture');
        if (!empty($file)) {
            $upload_dir = '/public/upload/qr_code';
            $info = $file->move(ROOT_PATH . $upload_dir);
            if ($info) {
                $data['qr_code'] = $upload_dir . '/' . $info->getSaveName();
            } else {
                return json(array('status' => 0, "message" => "上传失败."));
            }
        }
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