<?php

namespace app\admin\controller;

use app\admin\common\Constant;
use app\admin\model\Banner;
class BannerController extends BaseController
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
        $map['deleted'] = '0';
        $order = 'display_order desc';
        $recordCount = db('banner')->where($map)->count();
        $records = db('banner')->where($map)->field('id,title,redirect_url,picture_url,description,display_order,created_time,updated_time')->limit($start, $length)->order($order)->select();
        return json(array('draw' => $this->request->param('draw'), 'recordsTotal' => $recordCount, 'recordsFiltered' => $recordCount, 'data' => $records));
    }
    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        if (!empty($id)) {
            $model = Banner::get($id);
            $edit_state = true;
        }
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        return view();
    }
    public function save()
    {
        $data = input('post.');
        if ($this->is_exist($data['id'], $data['id'])) {
            return json(array('status' => 0, 'message' => '记录重复'));
        }
        if (empty($data['id'])) {
            $model = new Banner();
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
        } else {
            $model = Banner::get($data['id']);
            if (empty($model)) {
                return json(array('status' => 0, 'message' => '记录不存在'));
            }
            $data['updated_time'] = date('Y-m-d H:i:s');
            $data['updated_user_id'] = $this->userId;
        }
        $file = $this->request->file('picture');
        if (!empty($file)) {
            $upload_dir = '/public/upload/banner';
            $info = $file->move(ROOT_PATH . $upload_dir);
            if ($info) {
                $data['picture_url'] = $upload_dir . '\\' . $info->getSaveName();
            } else {
                return json(array('status' => 0, "message" => "上传失败."));
            }
        }
        $model->data($data);
        $model->save();
        return json(array('status' => 1, 'message' => '保存成功'));
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = Banner::get($id);
        if (empty($model)) {
            return json(array('status' => 0, 'message' => '记录不存在'));
        }
        $model->deleted = 1;
        $model->deleted_user_id = $this->userId;
        $model->deleted_time = date('Y-m-d H:i:s');
        $model->save();
        return json(array('status' => 1, 'message' => '删除成功'));
    }
    private function is_exist($key, $id = '')
    {
        return false;
        $where['key'] = $key;
        $where['deleted'] = 0;
        if (!empty($id)) {
            $where['id'] = array('<>', $id);
        }
        $list = db('banner')->where($where)->count();
        if ($list > 0) {
            return true;
        }
        return false;
    }
}