<?php

namespace app\admin\controller;

use app\admin\common\Tool;
use app\admin\model\NegativeWord;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\Unit;
use app\admin\model\User;
use http\Client;
class NegativeWordController extends BaseController
{
    public function index()
    {
        $this->assign('title', $this->promoteType == Constant::PROMOTE_TYPE_OCE ? '计划' : '单元');
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            return view('negative_word/index_client');
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            return view('negative_word/index_agent');
        } else {
            return view('negative_word/index');
        }
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('client.name|agent.name|word');
        $map['negative_word.deleted'] = '0';
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['negative_word.user_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        }
        $map['negative_word.type'] = $this->promoteType;
        $order = 'id desc';
        $count = db('negative_word')->join('user client', 'client.id = negative_word.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('negative_word')->join('user client', 'client.id = negative_word.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('negative_word.id,negative_word.user_id,negative_word.word,negative_word.created_time,negative_word.created_user_id,client.name as client_name,agent.name as agent_name,unit_id')->limit($start, $length)->order($order)->select();
        $user_list = User::column('name', 'id');
        $unit_list = Unit::column('name', 'id');
        foreach ($list as $key => $item) {
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
        $promote_type = $this->promoteType;
        if (!empty($id)) {
            $model = NegativeWord::get($id);
            $edit_state = true;
            $unit_list = Unit::where(['user_id' => $model->user_id, 'type' => $promote_type])->column('id,name,user_id');
        } else {
            if ($this->user->type == Constant::USER_TYPE_CLIENT) {
                $unit_list = Unit::where(['user_id' => $this->userId, 'parent_id' => ['<>', -1], 'type' => $promote_type])->column('id,name,user_id');
            } else {
                $unit_list = Unit::where(['type' => $promote_type, 'parent_id' => ['<>', -1]])->column('id,name,user_id');
            }
        }
        $client_user_id = 0;
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        $map = ['type' => Constant::USER_TYPE_CLIENT];
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $client_user_id = $this->userId;
        } else {
            if ($this->user->type == Constant::USER_TYPE_AGENT) {
                $map['parent_id'] = $this->userId;
            }
        }
        $user_list = User::where($map)->column('name', 'id');
        $this->assign('user_list', $user_list);
        $this->assign('unit_list', $unit_list);
        $this->assign('client_user_id', $client_user_id);
        $this->assign('title', $this->promoteType == Constant::PROMOTE_TYPE_OCE ? '计划' : '单元');
        return view('negative_word/_item_maintain');
    }
    public function save()
    {
        $data = input('post.');
        $promote_type = $this->promoteType;
        $data['type'] = $promote_type;
        if ($this->is_exist(['user_id' => $data['user_id'], 'unit_id' => $data['unit_id'], 'type' => $data['type']], $data['id'])) {
            return api_error('该用户已设置该单元的否定词');
        }
        if (empty($data['id'])) {
            $changed = true;
            $model = new NegativeWord();
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
        } else {
            $model = NegativeWord::get($data['id']);
            if (empty($model)) {
                return api_error('记录不存在');
            }
            $changed = $data['word'] != $model->word;
            $data['updated_time'] = date('Y-m-d H:i:s');
            $data['updated_user_id'] = $this->userId;
        }
        $model->data($data);
        $model->save();
        if ($changed) {
            Tool::remind(Constant::REMIND_TYPE_NEGATIVE, $promote_type, $data['user_id'], '修改了否定词:' . str_replace(PHP_EOL, ';', $data['word']));
        }
        return api_success('保存成功');
    }
    public function save_client()
    {
        $data = input('post.');
        $promote_type = $this->promoteType;
        $data['type'] = $promote_type;
        $model = NegativeWord::where('user_id', $this->userId)->find();
        if (empty($model)) {
            $changed = true;
            $model = new NegativeWord();
            $data['user_id'] = $this->userId;
            $data['created_user_id'] = $this->userId;
        } else {
            $changed = $data['word'] != $model->word;
            $data['updated_time'] = date('Y-m-d H:i:s');
            $data['updated_user_id'] = $this->userId;
        }
        $model->data($data);
        $model->save();
        if ($changed) {
            Tool::remind(Constant::REMIND_TYPE_NEGATIVE, $promote_type, $this->userId, '修改了否定词:' . str_replace(PHP_EOL, ';', $data['word']));
        }
        return api_success('保存成功');
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = NegativeWord::get($id);
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
        $model = NegativeWord::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
}