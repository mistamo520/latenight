<?php

namespace app\admin\controller;

use app\admin\common\Constant;
use app\admin\model\Setting;
use think\Url;
class SettingController extends BaseController
{
    public function index()
    {
        return view();
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('code');
        $order = 'type,code';
        $recordCount = db('setting')->where($map)->count();
        $records = db('setting')->where($map)->field('id,type,code,value')->limit($start, $length)->order($order)->select();
        foreach ($records as $key => $item) {
            $records[$key]['type'] = Constant::SETTING_TYPE[$item['type']];
            $records[$key]['code'] = Constant::SETTING_CODE[$item['code']];
        }
        return json(array('draw' => $this->request->param('draw'), "recordsTotal" => $recordCount, "recordsFiltered" => $recordCount, "data" => $records));
    }
    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        if (!empty($id)) {
            $model = Setting::get($id);
            $edit_state = true;
            $model['type'] = Constant::SETTING_TYPE[$model['type']];
            $model['code'] = Constant::SETTING_CODE[$model['code']];
            $model['value'] = str_replace('<br>', PHP_EOL, $model['value']);
        }
        $this->assign('type_list', Constant::SETTING_TYPE);
        $this->assign('code_list', Constant::SETTING_CODE);
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        return view();
    }
    public function save()
    {
        $data = input('post.');
        $data['value'] = str_replace(PHP_EOL, '<br>', $data['value']);
        if (empty($data['id'])) {
            if ($this->is_exist($data['type'], $data['code'])) {
                return json(array('status' => 0, "message" => '字典已存在'));
            }
            $model = new Setting();
            $model->data($data);
            $model->save();
        } else {
            if (!isset($data['value'])) {
                return json(array('status' => 0, "message" => '请输入配置'));
            }
            $model = Setting::get($data['id']);
            if (empty($model)) {
                return json(array('status' => 0, "message" => "记录不存在"));
            }
            $model->data($data);
            $model->save();
        }
        return json(array('status' => 1, "message" => "保存成功"));
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = Setting::get($id);
        if (empty($model)) {
            return json(array('status' => 0, "message" => "记录不存在"));
        }
        $model->delete();
        return json(array('status' => 1, "message" => "删除成功"));
    }
    private function is_exist($type, $code)
    {
        $where['type'] = $type;
        $where['code'] = $code;
        $list = db('setting')->where($where)->count();
        if ($list > 0) {
            return true;
        }
        return false;
    }
}