<?php

namespace app\admin\controller;

use app\admin\common\Cache;
use app\admin\common\Common;
use app\admin\common\Constant;
use app\admin\model\Role;
use app\admin\model\Permission;
use think\Url;
class RoleController extends BaseController
{
    public function index()
    {
        return view();
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('name');
        $map['deleted'] = '0';
        $order = 'id desc';
        $recordCount = db('role')->where($map)->count();
        $records = db('role')->where($map)->field('id,name,data_level')->limit($start, $length)->order($order)->select();
        foreach ($records as $key => $item) {
            $records[$key]['data_level'] = get_value($item['data_level'], Constant::ROLE_DATA_LEVEL_LIST);
        }
        return json(array('draw' => $this->request->param('draw'), "recordsTotal" => $recordCount, "recordsFiltered" => $recordCount, "data" => $records));
    }
    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        if (!empty($id)) {
            $model = Role::get($id);
            $edit_state = true;
        }
        $this->assign('data_level_list', Constant::ROLE_DATA_LEVEL_LIST);
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        return view();
    }
    public function save()
    {
        $data = input('post.');
        if ($this->is_exist($data['name'], $data['id'])) {
            return json(array('status' => 0, "message" => '名称已存在'));
        }
        if (empty($data['id'])) {
            $model = new Role();
            $model->name = $data['name'];
            $model->deleted = 0;
            $model->created_user_id = $this->userId;
            $model->created_time = date('Y-m-d H:i:s');
            $model->save();
        } else {
            $model = Role::get($data['id']);
            if (empty($model)) {
                return json(array('status' => 0, "message" => "记录不存在"));
            }
            $model->name = $data['name'];
            $model->updated_time = date('Y-m-d H:i:s');
            $model->updated_user_id = $this->userId;
            $model->save();
        }
        Cache::clear('role');
        return json(array('status' => 1, "message" => "保存成功"));
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = Role::get($id);
        if (empty($model)) {
            return json(array('status' => 0, "message" => "记录不存在"));
        }
        $model->deleted = 1;
        $model->deleted_user_id = $this->userId;
        $model->deleted_time = date('Y-m-d H:i:s');
        $model->save();
        Cache::clear('role');
        return json(array('status' => 1, "message" => "删除成功"));
    }
    private function is_exist($name, $id = '')
    {
        $where['name'] = $name;
        $where['deleted'] = 0;
        if (!empty($id)) {
            $where['id'] = array('<>', $id);
        }
        $list = db('role')->where($where)->count();
        if ($list > 0) {
            return true;
        }
        return false;
    }
    public function _item_permission()
    {
        $id = $this->request->param('id');
        $model = null;
        $system_permissions = null;
        if (!empty($id)) {
            $model = Role::get($id);
            $system_permissions = Permission::where(['role_id' => $id])->column('name');
            $system_permissions = implode(',', $system_permissions);
        }
        $this->assign('model', $model);
        $this->assign('system_permissions', $system_permissions);
        return view();
    }
    public function get_system_permissions()
    {
        $permission = \app\admin\common\Permission::get_list();
        return json($permission);
    }
    public function get_app_permissions()
    {
        $permission = \app\admin\common\Permission::get_list();
        return json($permission);
    }
    public function save_permission()
    {
        $role_id = $this->request->param('role_id');
        Permission::destroy(['role_id' => $role_id]);
        $system_permission = $this->request->param('system_permission');
        $system_permission = explode(",", $system_permission);
        $now_date = date('Y-m-d H:i:s');
        $permission_list = [];
        foreach ($system_permission as $item) {
            if (!empty($item) && !in_array($item, Constant::PERMISSION_NOT_SAVE)) {
                $permission["role_id"] = $role_id;
                $permission["name"] = $item;
                $permission["created_time"] = $now_date;
                $permission_list[] = $permission;
            }
        }
        $model = new Permission();
        $model->saveAll($permission_list);
        return json(array('status' => 1, "message" => "修改成功"));
    }
}