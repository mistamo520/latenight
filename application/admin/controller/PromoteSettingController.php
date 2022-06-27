<?php

namespace app\admin\controller;

use app\admin\common\Tool;
use app\admin\model\Location;
use app\admin\model\Product;
use app\admin\model\PromoteSetting;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\User;

class PromoteSettingController extends BaseController
{
    public function index()
    {
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $promote_type = $this->promoteType;
            $weekday_list = [['name' => '周一'], ['name' => '周二'], ['name' => '周三'], ['name' => '周四'], ['name' => '周五'], ['name' => '周六'], ['name' => '周日']];

            $model = PromoteSetting::where(['user_id' => $this->userId, 'type' => $promote_type])->find();
            $selected_weekday_list = empty($model) ? [] : explode(';', $model->weekdays);

            foreach ($weekday_list as $key => $weekday) {
                $weekday_list[$key]['checked'] = in_array($weekday['name'], $selected_weekday_list);

            }
            $min_budget = 100;
            if ($this->promoteType == Constant::PROMOTE_TYPE_B2B) {
                $min_budget = 200;
            } else if ($this->promoteType == Constant::PROMOTE_TYPE_CPC) {
                $min_budget = 200;
            } else if ($this->promoteType == Constant::PROMOTE_TYPE_360) {
                $min_budget = 100;
            } else if ($this->promoteType == Constant::PROMOTE_TYPE_OCE) {
                $min_budget = 300;
            } else if ($this->promoteType == Constant::PROMOTE_TYPE_QQ) {
                $min_budget = 200;
            }


            $this->assign('hours_list', Constant::PROMOTE_SETTING_HOUR_LIST);
            $this->assign('daily_budget', empty($model) ? '0.00' : $model->daily_budget);
            $this->assign('min_budget', $min_budget);
            $this->assign('model', $model);
            $this->assign('weekday_list', $weekday_list);
            return view('promote_setting/index_client');
        } else {
            return view('promote_setting/index');
        }
    }

    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('client.name|agent.name');
        $map['promote_setting.deleted'] = '0';
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['promote_setting.user_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        }
        $promote_type = $this->promoteType;
        $map['promote_setting.type'] = $promote_type;
        $order = 'id desc';
        $count = db('promote_setting')
            ->join('user client', 'client.id = promote_setting.user_id')
            ->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('promote_setting')
            ->join('user client', 'client.id = promote_setting.user_id')
            ->join('user agent', 'client.parent_id = agent.id')->where($map)
            ->field('promote_setting.id,promote_setting.user_id, promote_setting.weekdays,daily_budget,promote_setting.created_time,promote_setting.created_user_id,client.name as client_name,agent.name as agent_name,client.rate as client_rate,agent.rate as agent_rate,hours')
            ->limit($start, $length)->order($order)->select();

        $user_list = User::column('name', 'id');
        foreach ($list as $key => $item) {
            $list[$key]['hours'] = get_value($item['hours'], Constant::PROMOTE_SETTING_HOUR_LIST);
            $list[$key]['user_id'] = get_value($item['user_id'], $user_list);
            $list[$key]['created_user_id'] = get_value($item['created_user_id'], $user_list);
            if ($this->user->type == Constant::USER_TYPE_AGENT) {
                $list[$key]['daily_budget'] = round($item['daily_budget'] / $item['agent_rate'], 2);
            } else {
                $list[$key]['daily_budget'] = round($item['daily_budget'] / ($item['client_rate'] * $item['agent_rate']), 2);
            }
        }

        return [
            'draw' => $this->request->param('draw'),
            'recordsTotal' => $count,
            'recordsFiltered' => $count,
            'data' => $list
        ];
    }

    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        if (!empty($id)) {
            $model = PromoteSetting::get($id);
            $edit_state = true;
        }
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        $map = ['type' => Constant::USER_TYPE_CLIENT];
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['id'] = $this->userId;
        } else if ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['parent_id'] = $this->userId;
        }
        $user_list = User::where($map)->column('name', 'id');
        $this->assign('min_budget', $this->promoteType == Constant::PROMOTE_TYPE_OCE || $this->promoteType == Constant::PROMOTE_TYPE_QQ ? 200 : 0);
        $this->assign('user_list', $user_list);
        $this->assign('hours_list', Constant::PROMOTE_SETTING_HOUR_LIST);
        return view('promote_setting/_item_maintain');
    }

    public function save()
    {
        $data = input('post.');
        $promote_type = $this->promoteType;
        $data['type'] = $promote_type;
        if ($this->is_exist(['user_id' => $data['user_id'], 'type' => $data['type']], $data['id'])) {
            return api_error('该用户已设置投放设置');
        }
        if (empty($data['id'])) {
            $changed = true;

            $model = new PromoteSetting ();
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
        } else {
            $model = PromoteSetting::get($data['id']);
            if (empty($model)) {
                return api_error('记录不存在');
            }
            $changed = $data['weekdays'] != $model->weekdays || $data['daily_budget'] != $model->daily_budget;
            $data['updated_time'] = date('Y-m-d H:i:s');
            $data['updated_user_id'] = $this->userId;
        }
        $model->data($data);
        $model->save();
        if ($changed) {
            Tool::remind(Constant::REMIND_TYPE_SETTING, $promote_type, $data['user_id'], '修改了投放设置');
        }
        return api_success('保存成功');
    }

    public function save_client()
    {
        $data = input('post.');
        $promote_type = $this->promoteType;
        $data['type'] = $promote_type;
        $model = PromoteSetting::where(['user_id' => $this->userId, 'type' => $data['type']])->find();
        if (empty($model)) {
            $changed = true;
            $model = new PromoteSetting ();
            $data['user_id'] = $this->userId;
            $data['created_user_id'] = $this->userId;
        } else {
            $changed = $data['weekdays'] != $model->weekdays || $data['daily_budget'] != $model->daily_budget;
            $data['updated_time'] = date('Y-m-d H:i:s');
            $data['updated_user_id'] = $this->userId;
        }
        $model->data($data);
        $model->save();
        if ($changed) {
            Tool::remind(Constant::REMIND_TYPE_SETTING, $promote_type, $this->userId, '修改了投放设置');
        }
        return api_success('保存成功');
    }

    public function delete()
    {
        $id = $this->request->param('id');
        $model = PromoteSetting::get($id);
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
        $model = PromoteSetting::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
}
