<?php

namespace app\admin\controller;

use app\admin\model\Remind;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\User;
class RemindController extends BaseController
{
    public function index()
    {
        $this->assign('type_list', Constant::REMIND_TYPE_LIST);
        $this->assign('promote_type_list', Constant::PROMOTE_TYPE_LIST);
        return view('remind/index');
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $type = $this->request->param('type');
        $promote_type = $this->request->param('promote_type');
        $map = $this->process_query('id');
        $map['remind.deleted'] = '0';
        $map['remind.status'] = '0';
        if (empty($this->isAdmin)) {
            $map['client.parent_id'] = $this->userId;
        }
        if (!empty($type)) {
            $map['remind.type'] = $type;
        }
        $promote_type = $this->promoteType;
        $map['remind.promote_type'] = $promote_type;
        $order = 'id desc';
        $count = db('remind')->join('user client', 'remind.user_id = user.id')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('remind')->join('user client', 'remind.user_id = user.id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('remind.id,remind.content,remind.user_id,remind.status,remind.created_time,remind.created_user_id,client.name as client_name,client.user_name as user_name,agent.name as agent_name,remind.type,remind.promote_type')->limit($start, $length)->order($order)->select();
        foreach ($list as $key => $item) {
            $list[$key]['type'] = get_value($item['type'], Constant::REMIND_TYPE_LIST);
            $list[$key]['promote_type'] = get_value($item['promote_type'], Constant::PROMOTE_TYPE_LIST);
        }
        return ['draw' => $this->request->param('draw'), 'recordsTotal' => $count, 'recordsFiltered' => $count, 'data' => $list];
    }
    public function mark()
    {
        $id = $this->request->param('id');
        $model = Remind::get($id);
        if (empty($model)) {
            return api_error('记录不存在');
        }
        $model->status = 1;
        $model->updated_user_id = $this->userId;
        $model->updated_time = date('Y-m-d H:i:s');
        $model->save();
        return api_success('标记成功');
    }
    public function mark_all()
    {
        $promote_type = $this->promoteType;
        Remind::where(['promote_type' => $promote_type, 'status' => 0])->update(['status' => 1, 'updated_user_id' => $this->userId, 'updated_time' => get_time()]);
        return api_success('标记成功');
    }
    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        if (!empty($id)) {
            $model = Remind::get($id);
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
            $model = new Remind();
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
        } else {
            $model = Remind::get($data['id']);
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
        $model = Remind::get($id);
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
        $model = Remind::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
}