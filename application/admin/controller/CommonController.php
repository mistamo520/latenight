<?php

namespace app\admin\controller;

use app\admin\common\Cache;
use app\admin\model\Article;
use app\admin\model\User;
class CommonController extends BaseController
{
    public function _change_avatar()
    {
        return view();
    }
    public function _change_password()
    {
        return view();
    }
    public function change_avatar()
    {
        $data = input('post.');
        $photo = $data['photo'];
        if (preg_match('/^(data:\\s*image\\/(\\w+);base64,)/', $photo, $result)) {
            $type = $result[2];
            $path = ROOT_PATH . "/public/user/avatar/";
            if (!file_exists($path)) {
                mkdir($path, 0700);
            }
            $filename = time() . ".{$type}";
            if (file_put_contents($path . $filename, base64_decode(str_replace($result[1], '', $photo)))) {
                $user = User::get($this->userId);
                $user->photo = $filename;
                $user->save();
                return json(array('status' => 1));
            } else {
                return json(array('status' => 0, "message" => "上传失败"));
            }
        } else {
            return json(array('status' => 0, "message" => "上传文件不正確"));
        }
    }
    public function change_password()
    {
        $data = input('post.');
        $model = User::get($this->userId);
        if (empty($model)) {
            return json(array('status' => 0, "message" => "用户不存在"));
        }
        if (md5($data['oldpassword']) != $model['password']) {
            return json(array('status' => 0, "message" => "原密码不正确."));
        }
        $model->password = md5($data['password']);
        $model->save();
        return json(array('status' => 1, "message" => "修改密码成功"));
    }
    public function upload_image()
    {
        $data = input('post.');
        $img = $data['img'];
        if (preg_match('/^(data:\\s*image\\/(\\w+);base64,)/', $img, $result)) {
            $type = $result[2];
            $path = ROOT_PATH . "/public/upload/img/";
            if (!file_exists($path)) {
                mkdir($path, 0700);
            }
            $filename = time() . ".{$type}";
            if (file_put_contents($path . $filename, base64_decode(str_replace($result[1], '', $img)))) {
                return json(array('status' => 1, "message" => "上传成功", 'data' => "/public/upload/img/" . $filename));
            } else {
                return json(array('status' => 0, "message" => "上传失败"));
            }
        } else {
            return json(array('status' => 0, "message" => "上传文件不正確"));
        }
    }
}