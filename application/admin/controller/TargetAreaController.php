<?php

namespace app\admin\controller;

use app\admin\common\Tool;
use app\admin\model\Location;
use app\admin\model\Product;
use app\admin\model\TargetArea;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\User;
class TargetAreaController extends BaseController
{
    public function index()
    {
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $promote_type = $this->promoteType;
            $location_list = \cache('location_list');
            if (empty($location_list)) {
                $province_list = Location::where('type', 1)->field('name,code')->select();
                $location_list = [];
                foreach ($province_list as $province) {
                    $location_list[] = ['name' => $province['name'], 'city_list' => Location::where('parent_code', $province['code'])->field('name')->select()];
                }
                \cache('location_list', $location_list);
            }
            $model = TargetArea::where(['user_id' => $this->userId, 'type' => $promote_type])->find();
            $province_list = empty($model) ? [] : explode(';', $model->province);
            $city_list = empty($model) ? [] : explode(';', $model->city);
            foreach ($location_list as $key => $province) {
                $location_list[$key]['checked'] = in_array($province['name'], $province_list);
                foreach ($location_list[$key]['city_list'] as $ckey => $city) {
                    $location_list[$key]['city_list'][$ckey]['checked'] = in_array($city['name'], $city_list);
                }
            }
            $this->assign('promote_type', $promote_type);
            $this->assign('location_list', $location_list);
            return view('target_area/index_client');
        } else {
            return view('target_area/index');
        }
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('client.name|agent.name');
        $map['target_area.deleted'] = '0';
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['target_area.user_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        }
        $promote_type = $this->promoteType;
        $map['target_area.type'] = $promote_type;
        $order = 'id desc';
        $count = db('target_area')->join('user client', 'client.id = target_area.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('target_area')->join('user client', 'client.id = target_area.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('target_area.id,target_area.user_id, target_area.province,left(target_area.city,10) as city,target_area.created_time,target_area.created_user_id,client.name as client_name,agent.name as agent_name')->limit($start, $length)->order($order)->select();
        $user_list = User::column('name', 'id');
        foreach ($list as $key => $item) {
            $list[$key]['user_id'] = get_value($item['user_id'], $user_list);
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
            $model = TargetArea::get($id);
            $edit_state = true;
        }
        $promote_type = $this->promoteType;
        $location_list = \cache('location_list');
        if (empty($location_list)) {
            $province_list = Location::where('type', 1)->field('name,code')->select();
            $location_list = [];
            foreach ($province_list as $province) {
                $location_list[] = ['name' => $province['name'], 'city_list' => Location::where('parent_code', $province['code'])->field('name')->select()];
            }
            \cache('location_list', $location_list);
        }
        $province_list = empty($model) ? [] : explode(';', $model->province);
        $city_list = empty($model) ? [] : explode(';', $model->city);
        foreach ($location_list as $key => $province) {
            $location_list[$key]['checked'] = in_array($province['name'], $province_list);
            foreach ($location_list[$key]['city_list'] as $ckey => $city) {
                $location_list[$key]['city_list'][$ckey]['checked'] = in_array($city['name'], $city_list);
            }
        }
        $this->assign('promote_type', $promote_type);
        $this->assign('location_list', $location_list);
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        $map = ['type' => Constant::USER_TYPE_CLIENT];
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['id'] = $this->userId;
        } else {
            if ($this->user->type == Constant::USER_TYPE_AGENT) {
                $map['parent_id'] = $this->userId;
            }
        }
        $user_list = User::where($map)->column('name', 'id');
        $this->assign('user_list', $user_list);
        return view('target_area/_item_maintain');
    }
    public function save()
    {
        $data = input('post.');
        $promote_type = $this->promoteType;
        $data['type'] = $promote_type;
        if ($this->is_exist(['user_id' => $data['user_id'], 'type' => $data['type']], $data['id'])) {
            return api_error('该用户已设置投放区域');
        }
        if (empty($data['id'])) {
            $changed = true;
            $model = new TargetArea();
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
        } else {
            $model = TargetArea::get($data['id']);
            if (empty($model)) {
                return api_error('记录不存在');
            }
            $changed = $data['province'] != $model->province || $data['city'] != $model->city;
            $data['updated_time'] = date('Y-m-d H:i:s');
            $data['updated_user_id'] = $this->userId;
        }
        $model->data($data);
        $model->save();
        if ($changed) {
            if ($promote_type == Constant::PROMOTE_TYPE_B2B) {
                Product::where('user_id', $data['user_id'])->update(['target_province' => $data['province'], 'target_city' => $data['city']]);
            }
            Tool::remind(Constant::REMIND_TYPE_PRODUCT, $promote_type, $data['user_id'], '修改了投放区域');
        }
        return api_success('保存成功');
    }
    public function save_client()
    {
        $data = input('post.');
        $promote_type = $this->promoteType;
        $data['type'] = $promote_type;
        $model = TargetArea::where(['user_id' => $this->userId, 'type' => $data['type']])->find();
        if (empty($model)) {
            $changed = true;
            $model = new TargetArea();
            $data['user_id'] = $this->userId;
            $data['created_user_id'] = $this->userId;
        } else {
            $changed = $data['province'] != $model->province || $data['city'] != $model->city;
            $data['updated_time'] = date('Y-m-d H:i:s');
            $data['updated_user_id'] = $this->userId;
        }
        $model->data($data);
        $model->save();
        if ($changed) {
            if ($promote_type == Constant::PROMOTE_TYPE_B2B) {
                Product::where('user_id', $this->userId)->update(['target_province' => $data['province'], 'target_city' => $data['city']]);
            }
            Tool::remind(Constant::REMIND_TYPE_PRODUCT, $promote_type, $this->userId, '修改了投放区域');
        }
        return api_success('保存成功');
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = TargetArea::get($id);
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
        $model = TargetArea::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
}