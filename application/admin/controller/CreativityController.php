<?php

namespace app\admin\controller;

use app\admin\common\Tool;
use app\admin\model\Creativity;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\Unit;
use app\admin\model\User;
class CreativityController extends BaseController
{
    public function index()
    {
        $this->assign('title', $this->promoteType == Constant::PROMOTE_TYPE_OCE ? '计划' : '单元');
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            return view('creativity/index_client');
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            return view('creativity/index_agent');
        } else {
            return view('creativity/index');
        }
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('client.name|agent.name|creativity.title');
        $map['creativity.deleted'] = '0';
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['creativity.user_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        }
        $map['creativity.type'] = $this->promoteType;
        $order = 'id desc';
        $count = db('creativity')->join('user client', 'client.id = creativity.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('creativity')->join('user client', 'client.id = creativity.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('creativity.id,creativity.user_id,creativity.title,creativity.description,creativity.url_mobile,creativity.image,creativity.created_time,creativity.created_user_id,client.name as client_name,agent.name as agent_name,unit_id,creativity.type')->limit($start, $length)->order($order)->select();
        $user_list = User::column('name', 'id');
        $unit_list = Unit::column('name', 'id');
        foreach ($list as $key => $item) {
            if ($item['type'] == Constant::PROMOTE_TYPE_OCE && !empty($item['image'])) {
                $list[$key]['image'] = explode(';', $item['image'])[0];
            }
            $list[$key]['unit_id'] = get_value($item['unit_id'], $unit_list);
            $list[$key]['created_user_id'] = get_value($item['created_user_id'], $user_list);
        }
        return ['draw' => $this->request->param('draw'), 'recordsTotal' => $count, 'recordsFiltered' => $count, 'data' => $list];
    }
    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        $image_list = [];
        $promote_type = $this->promoteType;
        if (!empty($id)) {
            $model = Creativity::get($id);
            $edit_state = true;
            $image_list = explode(';', $model->image);
            $unit_list = Unit::where(['user_id' => $model->user_id, 'type' => $promote_type])->column('id,name,user_id');
        } else {
            if ($this->user->type == Constant::USER_TYPE_CLIENT) {
                $unit_list = Unit::where(['user_id' => $this->userId, 'type' => $promote_type])->column('id,name,user_id');
            } else {
                $unit_list = Unit::where(['type' => $promote_type])->column('id,name,user_id');
            }
        }
        $client_user_id = 0;
        $map = ['type' => Constant::USER_TYPE_CLIENT];
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $client_user_id = $this->userId;
        } else {
            if ($this->user->type == Constant::USER_TYPE_AGENT) {
                $map['parent_id'] = $this->userId;
            }
        }
        $user_list = User::where($map)->column('name', 'id');
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        $this->assign('client_user_id', $client_user_id);
        $this->assign('user_list', $user_list);
        $this->assign('unit_list', $unit_list);
        $this->assign('title', $this->promoteType == Constant::PROMOTE_TYPE_OCE ? '计划' : '单元');
        $this->assign('image_list', $image_list);
        return $this->promoteType == Constant::PROMOTE_TYPE_OCE ? view('creativity/_item_maintain_oce') : view('creativity/_item_maintain');
    }
    public function save()
    {
        $data = input('post.');
        if ($this->is_exist([], $data['id'])) {
            return api_error('记录重复');
        }
        if (empty($data['id'])) {
            $model = new Creativity();
            $data['type'] = $this->promoteType;
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
            Tool::remind(Constant::REMIND_TYPE_CREATIVITY, $this->promoteType, $data['user_id'], '提交了新创意:' . $data['title']);
        } else {
            $model = Creativity::get($data['id']);
            if (empty($model)) {
                return api_error('记录不存在');
            }
            $data['updated_time'] = date('Y-m-d H:i:s');
            $data['updated_user_id'] = $this->userId;
            Tool::remind(Constant::REMIND_TYPE_CREATIVITY, $this->promoteType, $data['user_id'], '修改了创意:' . $data['title'] . '[' . $model->id . ']');
        }
        $files = $this->request->file('picture');
        if (!empty($files)) {
            if (!is_array($files)) {
                $files = [$files];
            }
            $image_list = [];
            foreach ($files as $file) {
                $upload_dir = '/public/upload/creativity';
                $info = $file->move(ROOT_PATH . $upload_dir);
                if ($info) {
                    $image_list[] = str_replace('\\', '/', $upload_dir . '\\' . $info->getSaveName());
                } else {
                    return json(array('status' => 0, "message" => "上传失败."));
                }
            }
            $data['image'] = implode(';', $image_list);
        }
        $model->data($data);
        $model->save();
        return api_success('保存成功');
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = Creativity::get($id);
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
        $model = Creativity::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
}