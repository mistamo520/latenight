<?php

namespace app\admin\controller;

use app\admin\common\Tool;
use app\admin\model\Product;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\Unit;
use app\admin\model\User;
class JoinProductController extends BaseController
{
    public function index()
    {
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            return view('index_client');
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            return view('index_agent');
        } else {
            return view();
        }
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('client.name|agent.name|product.name');
        $map['product.deleted'] = '0';
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['product.user_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        }
        $map['product.type'] = 2;
        $order = 'id desc';
        $count = db('product')->join('user client', 'client.id = product.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('product')->join('user client', 'client.id = product.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('product.id,product.user_id,product.name,product.url_mobile,product.url_pc,product.image,product.image2,product.brand,product.seller_name,product.seller_url,product.price,product.price_unit,product.company_name,product.company_location,product.company_code,product.home_url_mobile,product.home_url_pc,product.list_url_mobile,product.list_url_pc,product.category,product.sub_category,product.delivery_province,product.delivery_city,product.target_province,product.target_city,product.created_time,product.created_user_id,client.name as client_name,agent.name as agent_name,unit_id')->limit($start, $length)->order($order)->select();
        $user_list = User::column('name', 'id');
        $unit_list = Unit::column('name', 'id');
        foreach ($list as $key => $item) {
            $list[$key]['unit_id'] = get_value($item['unit_id'], $unit_list);
            $list[$key]['user_id'] = get_value($item['user_id'], $user_list);
            $list[$key]['created_user_id'] = get_value($item['created_user_id'], $user_list);
        }
        return ['draw' => $this->request->param('draw'), 'recordsTotal' => $count, 'recordsFiltered' => $count, 'data' => $list];
    }
    public function _item_maintain()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        $promote_type = Constant::PROMOTE_TYPE_B2B;
        if (!empty($id)) {
            $model = Product::get($id);
            $edit_state = true;
            $unit_list = Unit::where(['user_id' => $model->user_id, 'type' => $promote_type])->column('id,name,user_id');
        } else {
            if ($this->user->type == Constant::USER_TYPE_CLIENT) {
                $unit_list = Unit::where(['user_id' => $this->userId, 'type' => $promote_type])->column('id,name,user_id');
            } else {
                $unit_list = Unit::where(['type' => $promote_type])->column('id,name,user_id');
            }
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
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        $this->assign('client_user_id', $client_user_id);
        $this->assign('unit_list', $unit_list);
        $this->assign('user_list', $user_list);
        return view();
    }
    public function save()
    {
        $data = input('post.');
        if ($this->is_exist([], $data['id'])) {
            return api_error('记录重复');
        }
        $is_new = false;
        if (empty($data['id'])) {
            $is_new = true;
            $model = new Product();
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
            $client = User::get($data['user_id']);
            if (!empty($client->product_count_limit)) {
                $count = Product::where('user_id', $data['user_id'])->count();
                if ($count >= $client->product_count_limit) {
                    return api_error('商品数量达到最大限制，请联系平台升级版本');
                }
            }
        } else {
            $model = Product::get($data['id']);
            if (empty($model)) {
                return api_error('记录不存在');
            }
            $data['updated_time'] = date('Y-m-d H:i:s');
            $data['updated_user_id'] = $this->userId;
        }
        $file = $this->request->file('picture');
        if (!empty($file)) {
            $upload_dir = '/public/upload/product';
            $info = $file->move(ROOT_PATH . $upload_dir);
            if ($info) {
                $data['image'] = str_replace('\\', '/', $upload_dir . '\\' . $info->getSaveName());
            } else {
                return json(array('status' => 0, "message" => "上传失败."));
            }
        }
        $file2 = $this->request->file('picture2');
        if (!empty($file2)) {
            $upload_dir = '\\public\\upload\\product';
            $info = $file2->move(ROOT_PATH . $upload_dir);
            if ($info) {
                $data['image2'] = str_replace('\\', '/', $upload_dir . '\\' . $info->getSaveName());
            } else {
                return json(array('status' => 0, "message" => "上传失败."));
            }
        }
        $model->data($data);
        $model->save();
        if ($is_new) {
            Tool::remind(Constant::REMIND_TYPE_PRODUCT, Constant::PROMOTE_TYPE_B2B, $data['user_id'], '提交了新商品:' . $data['name'] . '[' . $model->id . ']');
        } else {
            Tool::remind(Constant::REMIND_TYPE_PRODUCT, Constant::PROMOTE_TYPE_B2B, $data['user_id'], '更新了商品信息:' . $data['name'] . '[' . $model->id . ']');
        }
        return api_success('保存成功');
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = Product::get($id);
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
        $model = Product::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
}