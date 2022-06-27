<?php

namespace app\admin\controller;

use app\admin\model\Device;
use app\admin\model\Organization;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\User;
use think\Db;
class OrganizationController extends BaseController
{
    public function index()
    {
        return view();
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $organization_id = $this->request->param('organization_id');
        $query = $this->request->param('query');
        if (!empty($query)) {
            $map['organization.name|organization.code'] = ['like', '%' . $query . '%'];
        }
        $map['organization.deleted'] = '0';
        if (!empty($organization_id)) {
            $organization_code = Organization::where('id', $organization_id)->value('code');
            $map['organization.code'] = ['like', $organization_code . '%'];
            $map['organization.id'] = ['neq', $organization_id];
        }
        $organizationId = $this->organizationId();
        if ($organizationId) {
            $map['organization.id'] = ['in', $organizationId];
        }
        $order = 'id desc';
        $count = db('organization')->where($map)->count();
        $list = db('organization')->where($map)->field('organization.id,organization.name,organization.code,organization.parent_id,organization.type,organization.icon,organization.longitude,organization.latitude,organization.address,organization.contact,organization.phone,organization.remark,organization.created_time,organization.created_user_id')->limit($start, $length)->order($order)->select();
        $user_list = User::column('name', 'id');
        $parent_list = Organization::column('name', 'id');
        foreach ($list as $key => $item) {
            $list[$key]['parent_id'] = get_value($item['parent_id'], $parent_list);
            $list[$key]['type'] = get_value($item['type'], Constant::ORGANIZATION_TYPE_LIST);
            $list[$key]['created_user_id'] = get_value($item['created_user_id'], $user_list);
        }
        return ['draw' => $this->request->param('draw'), 'recordsTotal' => $count, 'recordsFiltered' => $count, 'data' => $list];
    }
    public function get_tree()
    {
        $typ = $this->request->param('typ', '');
        $tree = ['id' => 0, 'text' => '所有机构', 'state' => ['opened' => true], 'children' => []];
        if (!$this->isAdmin) {
            $user_organization_id = User::where('id', $this->userId)->value('organization_id');
        } else {
            $user_organization_id = 0;
        }
        if ($user_organization_id == 0) {
            $this->get_tree_list('parent_id', $user_organization_id, $typ, $tree['children']);
        } else {
            $this->get_tree_list('id', $user_organization_id, $typ, $tree['children']);
        }
        return $tree;
    }
    private function get_tree_list($key, $parent_id, $typ, &$list)
    {
        $map['deleted'] = 0;
        $map[$key] = $parent_id;
        if ($typ == 'edit') {
            $map['type'] = ['<>', Constant::ORGANIZATION_TYPE_TEAM];
        }
        $organization_list = Organization::all($map);
        foreach ($organization_list as $item) {
            $icon = $item->type == Constant::ORGANIZATION_TYPE_TEAM ? 'fa fa-cube' : 'fa fa-building-o';
            $node = ['id' => $item->id, 'text' => $item->name, 'parent' => 0, 'icon' => $icon, 'children' => []];
            $this->get_tree_list('parent_id', $item->id, $typ, $node['children']);
            $list[] = $node;
        }
    }
    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        if (!empty($id)) {
            $model = Organization::get($id);
            $edit_state = true;
            $model->parent_name = empty($model->parent_id) ? '' : Organization::where('id', $model->parent_id)->value('name');
        }
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        $parent_list = Organization::all(['type' => ['<>', Constant::ORGANIZATION_TYPE_TEAM, 'deleted' => 0]]);
        $this->assign('parent_list', $parent_list);
        $this->assign('type_list', Constant::ORGANIZATION_TYPE_LIST);
        return view();
    }
    public function save()
    {
        $data = input('post.');
        $file = $this->request->file('file');
        $pic_file = $this->request->file('pic_file');
        if ($this->is_exist(['name' => $data['name']], $data['id'])) {
            return api_error('记录重复');
        }
        if (empty($data['id'])) {
            if (empty($file)) {
                $data['icon'] = '\\public\\upload\\organization\\20190628\\540c19cef73f151579796a97c61dcb81.png';
            }
            $model = new Organization();
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
            $data['code'] = $this->get_code($data['parent_id']);
        } else {
            $model = Organization::get($data['id']);
            if (empty($model)) {
                return api_error('记录不存在');
            }
            $data['updated_time'] = date('Y-m-d H:i:s');
            $data['updated_user_id'] = $this->userId;
            if ($model->parent_id != $data['parent_id']) {
                if (!empty($model->children)) {
                    return api_error('存在下级机构，不可修改所属机构');
                }
                $data['code'] = $this->get_code($data['parent_id']);
            }
        }
        unset($data['parent_name']);
        if (!empty($file)) {
            $upload_dir = '/public/upload/organization';
            $info = $file->move(ROOT_PATH . $upload_dir);
            if ($info) {
                $file_name = $upload_dir . '\\' . $info->getSaveName();
                $data['icon'] = $file_name;
            } else {
                return api_error('上传失败');
            }
        }
        if (!empty($pic_file)) {
            $upload_dir = '\\public\\upload\\organization';
            $pic_info = $pic_file->move(ROOT_PATH . $upload_dir);
            if ($pic_info) {
                $pic_file_name = $upload_dir . '\\' . $pic_info->getSaveName();
                $data['picture_url'] = $pic_file_name;
            } else {
                return api_error('上传失败');
            }
        }
        $model->data($data);
        $model->save();
        return api_success('保存成功');
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = Organization::get($id);
        $user_organization = User::where('organization_id', $id)->count();
        $device_organization = Device::where('organization_id', $id)->count();
        if (empty($model)) {
            return api_error('记录不存在');
        }
        if (!empty($model->children)) {
            return api_error('存在下级机构，不可删除');
        }
        if ($user_organization > 0) {
            return api_error('存在关联用户，不可删除');
        }
        if ($device_organization > 0) {
            return api_error('存在关联设备，不可删除');
        }
        $model->deleted = 1;
        $model->deleted_user_id = $this->userId;
        $model->deleted_time = date('Y-m-d H:i:s');
        $model->save();
        return api_success('删除成功');
    }
    private function get_code($parent_id)
    {
        $parent_code = '';
        $parent = Organization::get($parent_id);
        if (!empty($parent)) {
            $parent_code = $parent->code;
        }
        $last = Organization::where('parent_id', $parent_id)->order('cast(`code` as signed) desc')->find();
        if (empty($last)) {
            $code = $parent_code . '001';
        } else {
            $code = $parent_code . str_pad(substr($last['code'], -3) + 1, 3, '0', STR_PAD_LEFT);
        }
        return $code;
    }
    private function is_exist($map, $id = '')
    {
        if (empty($map)) {
            return false;
        }
        if (!empty($id)) {
            $map['id'] = array('<>', $id);
        }
        $model = Organization::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
    private function organizationId()
    {
        $map = [];
        if (!$this->isAdmin) {
            $user_organization_id = User::where('id', $this->userId)->value('organization_id');
            if (!empty($user_organization_id)) {
                $user_organization_code = Organization::where('id', $user_organization_id)->value('code');
                $organization_ids = Organization::where(['code' => ['like', $user_organization_code . '%']])->column('id');
                $map = $organization_ids;
            }
        }
        return $map;
    }
}