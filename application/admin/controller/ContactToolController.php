<?php

namespace app\admin\controller;

use app\admin\common\Cache;
use app\admin\common\Constant;
use app\admin\model\College;
use app\admin\model\Remind;
use app\admin\model\User;
use app\admin\model\Role;
use app\admin\model\UserWechat;
use think\Db;
use think\Exception;
class ContactToolController extends BaseController
{
    public function index()
    {
        $model = User::get($this->userId);
        $agent = User::get($this->user->parent_id);
        $qr_code = '';
        if (!empty($agent->contact_tool_setting)) {
            $contact_tool_setting = json_decode($agent->contact_tool_setting, true);
            if (!empty($contact_tool_setting['qr_code'])) {
                $qr_code = $contact_tool_setting['qr_code'];
            }
        }
        $contact_tool_setting = json_decode($model->contact_tool_setting, true);
        if (empty($contact_tool_setting['script_key'])) {
            $contact_tool_setting['script_key'] = md5(microtime());
        }
        if (!empty($contact_tool_setting['copy_code'])) {
            $contact_tool_setting['copy_code'] = explode(PHP_EOL, $contact_tool_setting['copy_code']);
        } else {
            $contact_tool_setting['copy_code'] = [];
        }
        $this->assign('qr_code', $qr_code);
        $this->assign('model', $model);
        $this->assign('contact_tool_setting', $contact_tool_setting);
        $this->assign('enable_list', ['1' => '启用', '0' => '禁用']);
        $this->assign('position_list', ['center' => '中心', 'left' => '左下', 'right' => '右下']);
        $this->assign('float_position_list', ['center_right' => '右中', 'bottom_right' => '右下', 'center_left' => '左中', 'bottom_left' => '左下']);
        return view();
    }
    public function save()
    {
        $model = User::get($this->userId);
        $data = input('post.');
        $data['contact_tool_setting']['copy_code'] = '';
        for ($i = 1; $i <= 4; $i++) {
            $data['contact_tool_setting']['copy_code'] .= $data['copy_code' . $i] . PHP_EOL;
            unset($data['copy_code' . $i]);
        }
        if (isset($data['contact_tool_setting']['wechat_name'])) {
            $wechat_name = $data['contact_tool_setting']['wechat_name'];
            $wechat_list = explode(PHP_EOL, $wechat_name);
            $exist_list = explode(PHP_EOL, $model->wechat_name);
            $add_list = [];
            $remove_list = [];
            foreach ($wechat_list as $name) {
                if (!empty($name) && !in_array($name, $exist_list)) {
                    $add_list[] = '[' . $name . ']';
                }
            }
            foreach ($exist_list as $name) {
                if (!empty($name) && !in_array($name, $wechat_list)) {
                    $remove_list[] = '[' . $name . ']';
                }
            }
            $message = [];
            if (!empty($add_list) || !empty($remove_list)) {
                $message[] = '客户:' . $model->name;
                if (!empty($add_list)) {
                    $message[] = '增加微信直聊客服：' . implode('', $add_list);
                }
                if (!empty($remove_list)) {
                    $message[] = '减少微信直聊客服：' . implode('', $remove_list);
                }
                $remind = new Remind();
                $remind->user_id = $this->userId;
                $remind->content = implode(',', $message) . '。';
                $remind->created_time = get_time();
            }
        }
        $data['updated_user_id'] = $this->userId;
        $data['updated_time'] = date('Y-m-d H:i:s');
        $data['contact_tool_setting']['contact_enable_pc_float'] = empty($data['contact_tool_setting']['contact_enable_pc_float']) ? 0 : 1;
        $data['contact_tool_setting']['contact_enable_mobile_float'] = empty($data['contact_tool_setting']['contact_enable_mobile_float']) ? 0 : 1;
        $data['contact_tool_setting']['contact_enable_pc'] = empty($data['contact_tool_setting']['contact_enable_pc']) ? 0 : 1;
        $data['contact_tool_setting']['contact_open_message'] = empty($data['contact_tool_setting']['contact_open_message']) ? 0 : 1;
        $data['contact_tool_setting']['contact_enable_mobile'] = empty($data['contact_tool_setting']['contact_enable_mobile']) ? 0 : 1;
        $data['contact_tool_setting']['message_enable_pc'] = empty($data['contact_tool_setting']['message_enable_pc']) ? 0 : 1;
        $data['contact_tool_setting']['message_enable_mobile'] = empty($data['contact_tool_setting']['message_enable_mobile']) ? 0 : 1;
        $data['contact_tool_setting']['message_call_enable'] = empty($data['contact_tool_setting']['message_call_enable']) ? 0 : 1;
        $data['contact_tool_setting'] = json_encode($data['contact_tool_setting']);
        $model->data($data)->save();
        return json(array('status' => 1, "message" => "保存成功"));
    }
}