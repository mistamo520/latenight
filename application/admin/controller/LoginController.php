<?php

namespace app\admin\controller;

use app\admin\common\Constant;
use app\admin\model\Permission;
use app\admin\model\User;
use think\Controller;
class LoginController extends Controller
{
    public function index()
    {
        $data = input('param.');
        if (empty($data['user_name'])) {
            echo '请输入用户名';
            return;
        }
        if (empty($data['password'])) {
            echo '请输入密码';
            return;
        }
        $model = User::get(['user_name' => $data['user_name']]);
        if (empty($model) || $model['password'] != $data['password']) {
            echo '授权失败';
            return;
        }
        $permission_list = Permission::where(['role_id' => $model->role_id])->column('name');
        if ($model['role_id'] != Constant::ROLE_ADMIN && empty($permission_list)) {
            echo '禁止登录';
            return;
        } else {
            $permission['menu_permission'] = $permission_list;
            foreach ($permission_list as $key => $value) {
                $permission_list[$key] = str_replace('_', '', $value);
            }
            $permission['controller_permission'] = $permission_list;
            session(Constant::SESSION_USER_PERMISSION, $permission);
            session(Constant::SESSION_USER_ID, $model['id']);
            $url = $model['role_id'] != Constant::ROLE_ADMIN ? str_replace('system.', '', $permission_list[0]) : 'index';
            $this->redirect(url($url . '/index'));
        }
    }
}