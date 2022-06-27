<?php

namespace app\admin\controller;

use app\admin\common\Common;
use app\admin\common\Constant;
use app\admin\model\Message;
use app\admin\model\Notice;
use app\admin\model\Permission;
use app\admin\model\User;
class IndexController extends BaseController
{
    public function unauthorized()
    {
        return view();
    }
    public function index()
    {
        $user = User::get($this->userId);
        $data['type'] = $user['type'];
        $data['sms_count'] = $user['sms_count'];
        $data['balance'] = $user['balance'];
        $data['expired_date'] = date('Y-m-d', strtotime($user['expired_date']));
        $data['agent_count'] = User::where('type', Constant::USER_TYPE_AGENT)->count();
        $map_client_count['type'] = Constant::USER_TYPE_CLIENT;
        $map_message = [];
        if ($user['type'] == Constant::USER_TYPE_AGENT) {
            $map_client_count['parent_id'] = $this->userId;
            $client_ids = User::where($map_client_count)->column('id');
            $map_message['user_id'] = ['in', $client_ids];
            $data['client_count'] = sizeof($client_ids);
        } else {
            if ($user['type'] == Constant::USER_TYPE_CLIENT) {
                $map_message['user_id'] = $this->userId;
            }
            $data['client_count'] = User::where($map_client_count)->count();
        }
        $data['show_expired'] = $user->type == Constant::USER_TYPE_CLIENT && $user->version == Constant::CLIENT_VERSION_ANNUAL;
        $data['message_count'] = Message::where($map_message)->count();
        $map_message['created_time'] = ['between', [date('Y-m-d', time()), date('Y-m-d 23:59:59', time())]];
        $data['message_count_today'] = Message::where($map_message)->count();
        $data['sms_count'] = $user['sms_count'];
        $data['sms_count'] = $user['sms_count'];
        $notice_list = Notice::order('id desc')->limit(10)->select();
        $this->assign('notice', str_replace(' ', '&nbsp', str_replace(PHP_EOL, '<br/>', get_setting('website.notice'))));
        $this->assign('data', $data);
        $this->assign('notice_list', $notice_list);
        return view();
    }
    public function login()
    {

        if ($_POST) {
            $data = input('post.');
            if (strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {

                $auth_result = request_url('http://bpms_auth.ideasinsoft.com/auth', ['domain' => $_SERVER['HTTP_HOST']]);
                if (empty($auth_result['status'])) {
                    return api_error('授权失败：' . $auth_result['message']);
                }
            } else {
                $auth_result = ['data' => '1,2,3'];
            }
            if (empty($data['user_name'])) {
                return json(array('status' => 0, "message" => '请输入用户名'));
            }
            if (empty($data['password'])) {
                return json(array('status' => 0, "message" => '请输入密码'));
            }
            $model = User::get(['user_name' => $data['user_name']]);
            if (empty($model)) {
                return json(array('status' => 0, "message" => '用户不存在'));
            }
            if ($model['password'] != md5($data['password'])) {
                return json(array('status' => 0, "message" => '密码不正确'));
            }
            $permission_list = Permission::where(['role_id' => $model->role_id])->column('name');
            if ($model['role_id'] != Constant::ROLE_ADMIN && empty($permission_list)) {
                return json(array('status' => 0, "message" => "没有授权"));
            } else {
                $permission['menu_permission'] = $permission_list;
                foreach ($permission_list as $key => $value) {
                    $permission_list[$key] = str_replace('_', '', $value);
                }
                $permission['controller_permission'] = $permission_list;
                session(Constant::SESSION_USER_PERMISSION, $permission);
                session(Constant::SESSION_USER_ID, $model['id']);
                session(Constant::SESSION_APP_PERMISSION, explode(',', $auth_result['data']));
                $url = $model['role_id'] != Constant::ROLE_ADMIN ? str_replace('system.', '', $permission_list[0]) : 'index';
                return json(array('status' => 1, "message" => "登录成功", 'url' => $url));
            }
        }

        $agent = User::get(['type' => Constant::USER_TYPE_AGENT, 'website' => ['like', '%' . $_SERVER['SERVER_NAME'] . '%']]);

        $oem_name = get_setting('system.website_name');
        if (!empty($agent) && !empty($agent->oem_name)) {
            $oem_name = $agent->oem_name;
        }
        $this->assign('oem_name', $oem_name);
        return view('login');
    }
    public function _item_notice()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        if (!empty($id)) {
            $model = Notice::get($id);
            $edit_state = true;
        }
        $model->content = str_replace(' ', '&nbsp', str_replace(PHP_EOL, '<br/>', $model->content));
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        return view();
    }
    public function back_to_base()
    {
        $base_user_id = session(Constant::SESSION_BASE_USER_ID);
        if (empty($base_user_id)) {
            $this->redirect(url('index/unauthorized'));
        } else {
            $model = User::get($base_user_id);
            $permission_list = Permission::where(['role_id' => $model->role_id])->column('name');
            $permission['menu_permission'] = $permission_list;
            foreach ($permission_list as $key => $value) {
                $permission_list[$key] = str_replace('_', '', $value);
            }
            $permission['controller_permission'] = $permission_list;
            session(Constant::SESSION_USER_PERMISSION, $permission);
            session(Constant::SESSION_USER_ID, $base_user_id);
            session(Constant::SESSION_BASE_USER_ID, null);
            $this->redirect(url('index/index'));
        }
    }
    public function logout()
    {
        session('user_id', null);
        session('base_user_id', null);
        $this->redirect(url('index/login'));
    }
}