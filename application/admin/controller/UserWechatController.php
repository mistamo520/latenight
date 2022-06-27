<?php

namespace app\admin\controller;

use app\admin\model\UserWechat;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\User;
class UserWechatController extends BaseController
{
    public function index()
    {
        return view();
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('id');
        $map['user_wechat.deleted'] = '0';
        $order = 'id desc';
        $count = db('user_wechat')->where($map)->count();
        $list = db('user_wechat')->where($map)->field('user_wechat.id,user_wechat.user_id,user_wechat.name,user_wechat.created_time,user_wechat.created_user_id')->limit($start, $length)->order($order)->select();
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
            $model = UserWechat::get($id);
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
        if ($this->is_exist([], $data['id'])) {
            return api_error('记录重复');
        }
        if (empty($data['id'])) {
            $model = new UserWechat();
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
        } else {
            $model = UserWechat::get($data['id']);
            if (empty($model)) {
                return api_error('记录不存在');
            }
            $data['updated_time'] = date('Y-m-d H:i:s');
            $data['updated_user_id'] = $this->userId;
        }
        $model->data($data);
        $model->save();
        return api_success('保存成功');
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = UserWechat::get($id);
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
        $model = UserWechat::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
}