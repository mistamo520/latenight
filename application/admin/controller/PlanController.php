<?php

namespace app\admin\controller;

use app\admin\common\Tool;
use app\admin\common\OceTool;
use app\admin\common\Tool360;
use app\admin\model\Setting;
use app\admin\model\Unit;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\UnitBid;
use app\admin\model\User;
class PlanController extends BaseController
{
    var $message;
    public function check_api($baidu_id, $name)
    {
        $this->message = '';
        if ($this->promoteType == Constant::PROMOTE_TYPE_360) {
            $url = 'https://api.e.360.cn/dianjing/campaign/add?name=' . $name;
            if (!empty($baidu_id)) {
                $url = 'https://api.e.360.cn/dianjing/campaign/update?id=' . $baidu_id . '&name=' . $name;
            }
            $access_token = Tool360::get_access_token();
            if (empty($access_token)) {
                $this->message = '获取access token失败';
                return false;
            } else {
                $result = request_360_api($url, ['apiKey:' . $this->settings['360.appid'], 'accessToken:' . $access_token]);
                if (empty($result['failures'])) {
                    return $result['id'];
                }
                $this->message = $result['failures'][0]['message'];
                return false;
            }
        }
    }
    public function index()
    {
        $this->assign('title', '计划');
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            return view('plan/index_client');
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            return view('plan/index_agent');
        } else {
            return view('plan/index');
        }
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('client.name|agent.name|unit.name');
        $map['unit.deleted'] = '0';
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['unit.user_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        }
        $map['unit.parent_id'] = -1;
        $map['unit.type'] = $this->promoteType;
        $order = 'id desc';
        $count = db('unit')->join('user client', 'client.id = unit.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('unit')->join('user client', 'client.id = unit.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('unit.id,unit.user_id,unit.name,unit.created_time,unit.created_user_id,client.name as client_name,agent.name as agent_name,baidu_id')->limit($start, $length)->order($order)->select();
        $user_list = User::column('name', 'id');
        foreach ($list as $key => $item) {
            $list[$key]['created_user_id'] = get_value($item['created_user_id'], $user_list);
            if ($this->user->type == Constant::USER_TYPE_CLIENT) {
                unset($list[$key]['client_name']);
                unset($list[$key]['agent_name']);
            } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
                unset($list[$key]['agent_name']);
            }
        }
        return ['draw' => $this->request->param('draw'), 'recordsTotal' => $count, 'recordsFiltered' => $count, 'data' => $list];
    }
    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        if (!empty($id)) {
            $model = Unit::get($id);
            $edit_state = true;
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
        $this->assign('title', '计划');
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        $this->assign('client_user_id', $client_user_id);
        $this->assign('user_list', $user_list);
        return view('plan/_item_maintain');
    }
    public function save()
    {
        $data = input('post.');
        if ($this->is_exist(['user_id' => $data['user_id'], 'parent_id' => -1, 'name' => $data['name']], $data['id'])) {
            return api_error('记录重复');
        }
        $count = Unit::where(['user_id' => $data['user_id'], 'parent_id' => -1])->count();
        if (empty($data['id']) && $count > 0) {
            return api_error('每个客户仅允许创建一个计划');
        }
        $baidu_id = $this->check_api($data['baidu_id'], $data['name']);
        if (empty($baidu_id)) {
            return api_error($this->message);
        }
        $data['baidu_id'] = $baidu_id;
        $data['type'] = $this->promoteType;
        if (empty($data['id'])) {
            $model = new Unit();
            $data['parent_id'] = -1;
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
            Tool::remind(Constant::REMIND_TYPE_PLAN, $this->promoteType, $data['user_id'], '提交了新的计划 - ' . $data['name']);
        } else {
            $model = Unit::get($data['id']);
            if (empty($model)) {
                return api_error('记录不存在');
            }
            $data['updated_time'] = date('Y - m - d H:i:s');
            $data['updated_user_id'] = $this->userId;
        }
        $model->data($data);
        $model->save();
        return api_success('保存成功');
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = Unit::get($id);
        if (empty($model)) {
            return api_error('记录不存在');
        }
        $model->deleted = 1;
        $model->deleted_user_id = $this->userId;
        $model->deleted_time = date('Y - m - d H:i:s');
        $model->save();
        return api_success('删除成功');
    }
    private function is_exist($map, $id = '')
    {
        if (empty($map)) {
            return false;
        }
        if (!empty($id)) {
            $map['id'] = ['<>', $id];
        }
        $model = Unit::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
}