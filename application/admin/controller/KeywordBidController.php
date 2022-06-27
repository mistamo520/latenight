<?php

namespace app\admin\controller;

use app\admin\common\Tool;
use app\admin\common\Tool360;
use app\admin\model\Keyword;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\KeywordBid;
use app\admin\model\UnitBid;
use app\admin\model\User;
use think\Db;
use think\Exception;
class KeywordBidController extends BaseController
{
    public function index()
    {
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            return view('keyword_bid/index_client');
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            return view('keyword_bid/index_agent');
        } else {
            return view('keyword_bid/index');
        }
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('client.name|agent.name|keyword.name');
        $map['keyword.deleted'] = '0';
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['keyword.user_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        }
        $map['keyword.type'] = $this->promoteType;
        $order = 'id desc';
        $count = db('keyword')->join('user client', 'client.id = keyword.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('keyword')->join('user client', 'client.id = keyword.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('keyword.id,keyword.user_id,keyword.name,keyword.match_type,keyword.price,keyword.status,keyword.bid_time as created_time,keyword.bid_user_id as created_user_id,client.name as client_name,agent.name as agent_name,baidu_id,baidu_price,agent_price')->limit($start, $length)->order($order)->select();
        $user_list = User::column('name', 'id');
        foreach ($list as $key => $item) {
            $list[$key]['match_type'] = get_value($item['match_type'], Constant::KEYWORD_MATCH_TYPE_LIST);
            $list[$key]['status'] = get_value($item['status'], Constant::KEYWORD_STATUS_LIST);
            $list[$key]['created_user_id'] = get_value($item['created_user_id'], $user_list);
            if ($this->user->type == Constant::USER_TYPE_CLIENT) {
                unset($list[$key]['client_name']);
                unset($list[$key]['agent_name']);
            } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
                unset($list[$key]['agent_name']);
            }
            if (empty($list[$key]['price'])) {
                $list[$key]['price'] = '未设置';
            } elseif ($this->user->type == Constant::USER_TYPE_ADMIN) {
                $list[$key]['price'] = $item['baidu_price'] . ' / ' . $item['agent_price'] . ' / ' . $item['price'];
            } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
                $list[$key]['price'] = $item['agent_price'] . ' / ' . $item['price'];
            }
        }
        return ['draw' => $this->request->param('draw'), 'recordsTotal' => $count, 'recordsFiltered' => $count, 'data' => $list];
    }
    public function _item_bid()
    {
        $id = $this->request->param('id');
        $model = Keyword::get($id);
        $this->assign('percentage_list', Constant::BID_PERCENTAGE_LIST);
        $this->assign('model', $model);
        return view('keyword_bid/_item_bid');
    }
    public function bid()
    {
        $data = input('post.');
        $model = Keyword::get($data['id']);
        if (empty($model)) {
            return api_error('记录不存在');
        }
        if ($model['price'] == $data['price']) {
            return api_error('价格没有改变');
        }
        Db::startTrans();
        try {
            $model['bid_time'] = date('Y-m-d H:i:s');
            $model['bid_user_id'] = $this->userId;
            $model['price'] = $data['price'];
            $model->agent_price = $data['price'] / $model->user->rate;
            $model->baidu_price = $data['price'] / ($model->user->rate * $model->user->agent->rate);
            $model->save();
            $bid = new KeywordBid();
            $bid->price = $data['price'];
            $bid->agent_price = $data['price'] / $model->user->rate;
            $bid->baidu_price = $data['price'] / ($model->user->rate * $model->user->agent->rate);
            $bid->user_id = $model->user_id;
            $bid->keyword_id = $model->id;
            $bid->created_user_id = $this->userId;
            $bid->save();
            Tool::remind(Constant::REMIND_TYPE_BID, Constant::PROMOTE_TYPE_CPC, $model->user_id, '修改了关键词出价-' . $model->name);
            Db::commit();
            if (!empty($model->baidu_id)) {
                $flag = $this->update_api($model->baidu_id, $bid->baidu_price);
                if ($flag) {
                    return api_success('保存成功,同步平台成功');
                } else {
                    return api_success('保存成功,同步平台失败');
                }
            } else {
                return api_success('保存成功');
            }
        } catch (Exception $ex) {
            Db::rollback();
            return api_exception($ex);
        }
    }
    public function update_api($baidu_id, $price)
    {
        if ($this->promoteType == Constant::PROMOTE_TYPE_360) {
            $keywords = [['id' => $baidu_id, 'price' => $price]];
            $url = 'https://api.e.360.cn/dianjing/keyword/update?keywords=' . json_encode($keywords);
            $access_token = Tool360::get_access_token();
            if (empty($access_token)) {
                $this->message = '获取access token失败';
                return false;
            } else {
                $result = request_360_api($url, ['apiKey:' . $this->settings['360.appid'], 'accessToken:' . $access_token]);
                if (empty($result['failures'])) {
                    return true;
                }
                $this->message = $result['failures'][0]['message'];
                return false;
            }
        } else {
            $request_body = ["keywordTypes" => [["keywordId" => $baidu_id, "price" => $price]]];
            $header = ['username' => $this->settings['baidu.username'], 'password' => $this->settings['baidu.password'], 'token' => $this->settings['baidu.token']];
            $data = request_baidu_api('https://api.baidu.com/json/sms/service/KeywordService/updateWord', $request_body, $header);
            return $data['header']['status'] === 0;
        }
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = Keyword::get($id);
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
        $model = Keyword::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
}