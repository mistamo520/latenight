<?php

namespace app\admin\controller;

use app\admin\common\OceTool;
use app\admin\common\Tool;
use app\admin\common\Tool360;
use app\admin\model\Keyword;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\KeywordBid;
use app\admin\model\UnitBid;
use app\admin\model\Unit;
use app\admin\model\User;
class KeywordController extends BaseController
{
    var $message;
    public function index()
    {
        $this->assign('title', $this->promoteType == Constant::PROMOTE_TYPE_OCE ? '计划' : '单元');
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            return view('keyword/index_client');
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            return view('keyword/index_agent');
        } else {
            return view('keyword/index');
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
        $list = db('keyword')->join('user client', 'client.id = keyword.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('keyword.id,keyword.user_id,baidu_id,keyword.name,keyword.match_type,keyword.price,keyword.status,keyword.created_time,keyword.created_user_id,client.name as client_name,agent.name as agent_name,unit_id')->limit($start, $length)->order($order)->select();
        $user_list = User::column('name', 'id');
        $unit_list = Unit::column('name', 'id');
        foreach ($list as $key => $item) {
            $list[$key]['unit_id'] = get_value($item['unit_id'], $unit_list);
            $list[$key]['match_type'] = get_value($item['match_type'], Constant::KEYWORD_MATCH_TYPE_LIST);
            $list[$key]['status'] = get_value($item['status'], Constant::KEYWORD_STATUS_LIST);
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
        $promote_type = $this->promoteType;
        if (!empty($id)) {
            $model = Keyword::get($id);
            $edit_state = true;
            $unit_list = Unit::where(['user_id' => $model->user_id, 'type' => $promote_type])->column('id,name,user_id');
        } else {
            if ($this->user->type == Constant::USER_TYPE_CLIENT) {
                $unit_list = Unit::where(['user_id' => $this->userId, 'parent_id' => ['<>', -1], 'type' => $promote_type])->column('id,name,user_id');
            } else {
                $unit_list = Unit::where(['type' => $promote_type, 'parent_id' => ['<>', -1]])->column('id,name,user_id');
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
        $this->assign('match_type_list', Constant::KEYWORD_MATCH_TYPE_LIST);
        $this->assign('status_list', Constant::KEYWORD_STATUS_LIST);
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        $this->assign('client_user_id', $client_user_id);
        $this->assign('user_list', $user_list);
        $this->assign('unit_list', $unit_list);
        $this->assign('title', $this->promoteType == Constant::PROMOTE_TYPE_OCE ? '计划' : '单元');
        return view('keyword/_item_maintain');
    }
    public function _item_batch()
    {
        $id = $this->request->param('id');
        $model = null;
        $edit_state = false;
        $promote_type = $this->promoteType;
        if (!empty($id)) {
            $model = Keyword::get($id);
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
        $this->assign('match_type_list', Constant::KEYWORD_MATCH_TYPE_LIST);
        $this->assign('status_list', Constant::KEYWORD_STATUS_LIST);
        $this->assign('model', $model);
        $this->assign('edit_state', $edit_state);
        $this->assign('client_user_id', $client_user_id);
        $this->assign('user_list', $user_list);
        $this->assign('unit_list', $unit_list);
        $this->assign('title', $this->promoteType == Constant::PROMOTE_TYPE_OCE ? '计划' : '单元');
        return view('keyword/_item_batch');
    }
    public function save_batch()
    {
        $data = input('post.');
        $keyword_list = explode(PHP_EOL, $data['name']);
        foreach ($keyword_list as $keyword) {
            if (!empty($keyword)) {
                $model = new Keyword();
                $data['type'] = $this->promoteType;
                $data['deleted'] = 0;
                $data['created_user_id'] = $this->userId;
                $data['created_time'] = date('Y-m-d H:i:s');
                $data['name'] = $keyword;
                $model->data($data);
                $model->save();
            }
        }
        Tool::remind(Constant::REMIND_TYPE_KEYWORD, $this->promoteType, $data['user_id'], '提交了新的关键词-' . implode(',', $keyword_list));
        return api_success('保存成功');
    }
    public function save()
    {
        $data = input('post.');
        if ($this->is_exist(['user_id' => $data['user_id'], 'name' => $data['name']], $data['id'])) {
            return api_error('记录重复');
        }
        $is_approved = !empty($data['status']) && $data['status'] == 2;
        if (empty($data['id'])) {
            $model = new Keyword();
            $data['type'] = $this->promoteType;
            $data['deleted'] = 0;
            $data['created_user_id'] = $this->userId;
            $data['created_time'] = date('Y-m-d H:i:s');
            Tool::remind(Constant::REMIND_TYPE_KEYWORD, $this->promoteType, $data['user_id'], '提交了新的关键词-' . $data['name']);
        } else {
            $model = Keyword::get($data['id']);
            if (empty($model)) {
                return api_error('记录不存在');
            }
            $data['updated_time'] = date('Y-m-d H:i:s');
            $data['updated_user_id'] = $this->userId;
            Tool::remind(Constant::REMIND_TYPE_KEYWORD, $this->promoteType, $data['user_id'], '修改了关键词-' . $data['name']);
        }
        $model->data($data);
        $model->save();
        $unit = Unit::get($data['unit_id']);
        if ($is_approved && !empty($unit) && !empty($unit->baidu_id)) {
            if (empty($model->baidu_id)) {
                $keyword_id = $this->add_api($unit->baidu_id, $data['name'], $data['match_type']);
                if (!empty($keyword_id)) {
                    $model->baidu_id = $keyword_id;
                    $model->save();
                    return api_success('保存成功,同步平台成功');
                } else {
                    return api_error('保存成功,同步平台失败');
                }
            } else {
                $keyword_id = $this->update_api($model->baidu_id, $data['name'], $data['match_type'], $unit->baidu_id);
                if (!empty($keyword_id)) {
                    return api_success('保存成功,同步平台成功');
                } else {
                    return api_error('保存成功,同步平台失败');
                }
            }
        } else {
            return api_success('保存成功');
        }
    }
    public function add_api($baidu_id, $keyword, $match_type)
    {
        if ($this->promoteType == Constant::PROMOTE_TYPE_OCE) {
            if ($match_type == 1) {
                $match_type = 'PRECISION';
            } else {
                if ($match_type == 2) {
                    $match_type = 'PHRASE';
                } else {
                    $match_type = 'EXTENSIVE';
                }
            }
            $advertiser_id = get_setting('oce.advertiser_id');
            $url = 'https://ad.oceanengine.com/open_api/2/keyword/create_v2/';
            $params = ['advertiser_id' => $advertiser_id, 'ad_id' => $baidu_id, 'keywords' => [['match_type' => $match_type, 'word' => $keyword]]];
            $access_token = OceTool::get_access_token();
            if (empty($access_token)) {
                return false;
            } else {
                $result = request_oce_api($url, ['Access-Token:' . $access_token, 'Content-Type:application/json'], 'POST', json_encode($params));
                if (empty($result['code'])) {
                    return $result['data']['success_list'][0]['keyword_id'];
                }
                return '';
            }
        } elseif ($this->promoteType == Constant::PROMOTE_TYPE_360) {
            if ($match_type == 1) {
                $match_type = 'exact';
            } else {
                if ($match_type == 2) {
                    $match_type = 'phrase';
                } else {
                    $match_type = 'phrase_intelligence';
                }
            }
            $keywords = [['groupId' => $baidu_id, 'word' => $keyword, 'price' => 0.3, 'matchType' => $match_type]];
            $url = 'https://api.e.360.cn/dianjing/keyword/add?keywords=' . json_encode($keywords);
            $access_token = Tool360::get_access_token();
            if (empty($access_token)) {
                $this->message = '获取access token失败';
                return false;
            } else {
                $result = request_360_api($url, ['apiKey:' . $this->settings['360.appid'], 'accessToken:' . $access_token]);
                if (empty($result['failures'])) {
                    return $result['keywordIdList'][0];
                }
                $this->message = $result['failures'][0]['message'];
                return false;
            }
        } else {
            $data = ["adgroupId" => $baidu_id, "keyword" => $keyword];
            if ($match_type == 1) {
                $data['matchType'] = 1;
                $data['phraseType'] = 1;
            } elseif ($match_type == 2) {
                $data['matchType'] = 2;
                $data['phraseType'] = 1;
            } else {
                $data['matchType'] = 2;
                $data['phraseType'] = 3;
            }
            $request_body = ["keywordTypes" => [$data]];
            $header = ['username' => $this->settings['baidu.username'], 'password' => $this->settings['baidu.password'], 'token' => $this->settings['baidu.token']];
            $data = request_baidu_api('https://api.baidu.com/json/sms/service/KeywordService/addWord', $request_body, $header);
            $id = '';
            if ($data['header']['status'] === 0 && !empty($data['body']['data'])) {
                $id = $data['body']['data'][0]['keywordId'];
            }
            return $id;
        }
    }
    public function update_api($keyword_id, $keyword, $match_type, $baidu_id)
    {
        if ($this->promoteType == Constant::PROMOTE_TYPE_OCE) {
            if ($match_type == 1) {
                $match_type = 'PRECISION';
            } else {
                if ($match_type == 2) {
                    $match_type = 'PHRASE';
                } else {
                    $match_type = 'EXTENSIVE';
                }
            }
            $advertiser_id = get_setting('oce.advertiser_id');
            $url = 'https://ad.oceanengine.com/open_api/2/keyword/update_v2/';
            $params = ['advertiser_id' => $advertiser_id, 'ad_id' => $baidu_id, 'keywords' => [['keyword_id' => $keyword_id, 'match_type' => $match_type, 'word' => $keyword]]];
            $access_token = OceTool::get_access_token();
            if (empty($access_token)) {
                return false;
            } else {
                $result = request_oce_api($url, ['Access-Token:' . $access_token, 'Content-Type:application/json'], 'POST', json_encode($params));
                if (empty($result['code'])) {
                    return true;
                }
                return false;
            }
        } elseif ($this->promoteType == Constant::PROMOTE_TYPE_360) {
            if ($match_type == 1) {
                $match_type = 'exact';
            } else {
                if ($match_type == 2) {
                    $match_type = 'phrase';
                } else {
                    $match_type = 'phrase_intelligence';
                }
            }
            $keywords = [['id' => $keyword_id, 'matchType' => $match_type]];
            $url = 'https://api.e.360.cn/dianjing/keyword/update?keywords=' . json_encode($keywords);
            $access_token = Tool360::get_access_token();
            if (empty($access_token)) {
                $this->message = '获取access token失败';
                return false;
            } else {
                $result = request_360_api($url, ['apiKey:' . $this->settings['360.appid'], 'accessToken:' . $access_token]);
                if (empty($result['failures'])) {
                    return $keyword_id;
                }
                $this->message = $result['failures'][0]['message'];
                return false;
            }
        } else {
            $data = ["keywordId" => $keyword_id, "keyword" => $keyword];
            if ($match_type == 1) {
                $data['matchType'] = 1;
                $data['phraseType'] = 1;
            } elseif ($match_type == 2) {
                $data['matchType'] = 2;
                $data['phraseType'] = 1;
            } else {
                $data['matchType'] = 2;
                $data['phraseType'] = 3;
            }
            $request_body = ["keywordTypes" => [$data]];
            $header = ['username' => $this->settings['baidu.username'], 'password' => $this->settings['baidu.password'], 'token' => $this->settings['baidu.token']];
            $data = request_baidu_api('https://api.baidu.com/json/sms/service/KeywordService/updateWord', $request_body, $header);
            $id = '';
            if ($data['header']['status'] === 0 && !empty($data['body']['data'])) {
                $id = $data['body']['data'][0]['keywordId'];
            }
            return $id;
        }
    }
    public function _item_bid()
    {
        $id = $this->request->param('id');
        $model = Keyword::get($id);
        $this->assign('model', $model);
        return view();
    }
    public function bid()
    {
        $data = input('post.');
        $model = Keyword::get($data['id']);
        if (empty($model)) {
            return api_error('记录不存在');
        }
        $model['updated_time'] = date('Y-m-d H:i:s');
        $model['updated_user_id'] = $this->userId;
        $model['price'] = $data['price'];
        $model->save();
        $bid = new KeywordBid();
        $bid->price = $data['price'];
        $bid->user_id = $model->user_id;
        $bid->keyword_id = $model->id;
        $bid->created_user_id = $this->userId;
        $bid->save();
        return api_success('保存成功');
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = Keyword::get($id);
        if (empty($model)) {
            return api_error('记录不存在');
        }
        Tool::remind(Constant::REMIND_TYPE_KEYWORD, Constant::PROMOTE_TYPE_CPC, $model->user_id, '删除了关键词-' . $model->name);
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