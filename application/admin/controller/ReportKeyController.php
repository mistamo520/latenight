<?php

namespace app\admin\controller;

use app\admin\model\ReportKey;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\User;
use think\Db;
class ReportKeyController extends BaseController
{
    public function index()
    {
        $this->assign('title', $this->promoteType == Constant::PROMOTE_TYPE_OCE ? '计划' : '单元');
        $view = 'report_key/';
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $view = $view . 'index_client';
            $this->assign('is_annual', $this->user->version == Constant::CLIENT_VERSION_ANNUAL);
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $view = $view . 'index_agent';
        } else {
            $view = $view . 'index';
        }
        $promote_type = $this->promoteType;
        $this->assign('promote_type', $promote_type);
        return view($view);
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('client.user_name|client.name|agent.name|report_key.name', 'date');
        $map['report_key.deleted'] = '0';
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['report_key.user_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        }
        $promote_type = $this->promoteType;
        $map['report_key.type'] = $promote_type;
        $order = 'date desc';
        $count = db('report_key')->join('user client', 'client.id = report_key.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('report_key')->join('user client', 'client.id = report_key.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('report_key.id,report_key.user_id,report_key.name,report_key.date,report_key.plan,report_key.unit,report_key.display_count,report_key.click_count,report_key.click_rate,report_key.amount,report_key.amount_client,report_key.amount_agent,report_key.created_time,report_key.created_user_id,client.name as client_name,agent.name as agent_name,report_key.updated_time,report_key.updated_user_id,display_count_client,click_count_client,client.version as client_version')->limit($start, $length)->order($order)->select();
        $user_list = User::column('name', 'id');
        foreach ($list as $key => $item) {
            $list[$key]['created_user_id'] = get_value($item['created_user_id'], $user_list);
            $list[$key]['updated_user_id'] = get_value($item['updated_user_id'], $user_list);
            if ($this->user->type == Constant::USER_TYPE_CLIENT) {
                unset($list[$key]['amount']);
                unset($list[$key]['amount_agent']);
                if ($item['client_version'] == Constant::CLIENT_VERSION_ANNUAL) {
                    $list[$key]['display_count'] = $item['display_count_client'];
                    $list[$key]['click_count'] = $item['click_count_client'];
                    $list[$key]['amount_client'] = '-';
                    $list[$key]['amount_avg'] = '-';
                } else {
                    $list[$key]['amount_avg'] = round($item['amount_client'] / (empty($item['click_count']) ? 1 : $item['click_count']), 2);
                }
            } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
                $map['client.parent_id'] = $this->userId;
                unset($list[$key]['amount']);
            }
            unset($list[$key]['display_count_client']);
            unset($list[$key]['click_count_client']);
            unset($list[$key]['client_version']);
        }
        return ['draw' => $this->request->param('draw'), 'recordsTotal' => $count, 'recordsFiltered' => $count, 'data' => $list];
    }
    public function sync()
    {
        $start = input('param.')['start'];
        $end = input('param.')['end'];
        $request_body = ["realTimeRequestType" => ["reportType" => 14, "device" => 0, "levelOfDetails" => 11, "statRange" => 2, "platform" => 0, "unitOfTime" => 5, "number" => "1000", "pageIndex" => 1, "performanceData" => ["impression", "click", "cost", "cpc", "ctr"], "startDate" => $start, "endDate" => $end, "statIds" => null, "attributes" => null, "order" => true]];
        $header = ['username' => $this->settings['baidu.username'], 'password' => $this->settings['baidu.password'], 'token' => $this->settings['baidu.token']];
        $data = request_baidu_api('https://api.baidu.com/json/sms/service/ReportService/getRealTimeData', $request_body, $header);
        if ($data['header']['status'] == 0) {
            $data = $data['body']['data'];
            if (!empty($data)) {
                $record_list = [];
                foreach ($data as $item) {
                    $promote_type = strpos($item['name'][1], '爱采购') !== false ? Constant::PROMOTE_TYPE_B2B : Constant::PROMOTE_TYPE_CPC;
                    $client_name = str_replace('爱采购', '', str_replace('CPC', '', $item['name'][1]));
                    $client = User::get(['type' => Constant::USER_TYPE_CLIENT, 'user_name' => $client_name]);
                    if (!empty($client)) {
                        $report_key = ReportKey::get(['user_id' => $client->id, 'type' => $promote_type, 'date' => $item['date'], 'plan' => $item['name'][1], 'name' => $item['name'][3], 'unit' => $item['name'][2]]);
                        $data_item = [];
                        $data_item['date'] = $item['date'];
                        $data_item['name'] = $item['name'][3];
                        $data_item['unit'] = $item['name'][2];
                        $data_item['plan'] = $item['name'][1];
                        $data_item['display_count'] = $item['kpis'][0];
                        $data_item['click_count'] = $item['kpis'][1];
                        $data_item['amount'] = $item['kpis'][2];
                        $data_item['click_rate'] = $item['kpis'][4] * 100;
                        $data_item['created_user_id'] = $this->userId;
                        $data_item['created_time'] = get_time();
                        $data_item['type'] = $promote_type;
                        $data_item['user_id'] = $client->id;
                        $data_item['amount_agent'] = $data_item['amount'] * $client->agent->rate;
                        $data_item['amount_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? 0 : $data_item['amount_agent'] * $client->rate;
                        $data_item['display_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['display_count'] * $client->rate : $data_item['display_count'];
                        $data_item['click_count_client'] = $client->version == Constant::CLIENT_VERSION_ANNUAL ? $data_item['click_count'] * $client->rate : $data_item['click_count'];
                        if (empty($report_key)) {
                            $record_list[] = $data_item;
                        } else {
                            $data_item['id'] = $report_key['id'];
                            $record_list[] = $data_item;
                        }
                    }
                }
                $model = new ReportKey();
                $model->saveAll($record_list);
            }
            return api_success('同步成功');
        } else {
            return api_error('同步失败');
        }
    }
    public function _item_import()
    {
        return view('report_key/_item_import');
    }
    public function import_save()
    {
        $file = $this->request->file('import_file');
        if (!empty($file)) {
            $file_types = explode(".", $_FILES['import_file']['name']);
            $file_type = strtolower($file_types[count($file_types) - 1]);
            if ($file_type != "csv") {
                return api_error("请上传CSV文件");
            }
            $import_folder = '\\public\\upload\\import';
            $info = $file->move(ROOT_PATH . $import_folder);
            if (!$info) {
                return api_error("上传失败");
            }
            $file_path = ROOT_PATH . str_replace("\\", "\\\\", $import_folder . '\\' . $info->getSaveName());
            $import_list = [];
            if (($handle = fopen($file_path, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $data = eval('return ' . iconv('gbk', 'utf-8', var_export($data, true)) . ';');
                    $count = count($data);
                    $item = [];
                    for ($i = 0; $i < $count; $i++) {
                        $item[] = $data[$i];
                    }
                    $import_list[] = $item;
                    unset($item);
                }
                fclose($handle);
            }
            if (count($import_list) <= 1) {
                return api_error("文档无数据");
            }
            $column_list = $import_list[0];
            $template_column_list = ['搜索词', '日期', '推广计划', '推广单元', '展现', '点击', '消费', '点击率（%）', '平均点击价格'];
            if ($template_column_list != $column_list) {
                return api_error("导入的文件不正确，请检查列名是否和模板一致");
            }
            $promote_type = strtolower($this->request->controller()) == 'reportkeyb2b' ? Constant::PROMOTE_TYPE_B2B : Constant::PROMOTE_TYPE_CPC;
            $message_list = [];
            $data_list = [];
            $client_list = User::where('type', Constant::USER_TYPE_CLIENT)->column('id,parent_id,rate,version', 'user_name');
            $agent_list = User::where('type', Constant::USER_TYPE_AGENT)->column('name,rate', 'id');
            foreach ($import_list as $row_index => $row_data) {
                if ($row_index > 1) {
                    $row_message = [];
                    for ($i = 0; $i < count($row_data); $i++) {
                        if (empty($row_data)) {
                            $row_message[] = $template_column_list[$i] . '不能为空';
                        }
                    }
                    $data_item['name'] = $row_data[0];
                    $data_item['date'] = str_replace('/', '-', $row_data[1]);
                    $data_item['plan'] = $row_data[2];
                    $data_item['unit'] = $row_data[3];
                    $data_item['display_count'] = $row_data[4];
                    $data_item['click_count'] = $row_data[5];
                    $data_item['amount'] = $row_data[6];
                    $data_item['click_rate'] = $row_data[7];
                    $data_item['created_user_id'] = $this->userId;
                    $data_item['created_time'] = get_time();
                    $data_item['type'] = $promote_type;
                    if (strtotime($data_item['date']) >= strtotime(date('Y-m-d'))) {
                        $row_message[] = '不可上传当天以后的报告';
                    }
                    if (!key_exists($data_item['plan'], $client_list)) {
                        $row_message[] = '未找到客户' . $data_item['plan'];
                    } elseif (empty($client_list[$data_item['plan']]['rate'])) {
                        $row_message[] = '未找到客户' . $data_item['plan'] . '的扣费比';
                    } elseif (!key_exists($client_list[$data_item['plan']]['parent_id'], $agent_list)) {
                        $row_message[] = '未找到客户' . $data_item['plan'] . '的代理信息';
                    } elseif (empty($agent_list[$client_list[$data_item['plan']]['parent_id']]['rate'])) {
                        $row_message[] = '未找到客户' . $data_item['plan'] . '的代理计费系数';
                    } else {
                        $data_item['user_id'] = $client_list[$data_item['plan']]['id'];
                        $data_item['amount_agent'] = $data_item['amount'] * $agent_list[$client_list[$data_item['plan']]['parent_id']]['rate'];
                        $data_item['amount_client'] = $data_item['amount_agent'] * $client_list[$data_item['plan']]['rate'];
                        $data_item['display_count_client'] = $client_list[$data_item['plan']]['version'] == Constant::CLIENT_VERSION_ANNUAL ? $data_item['display_count'] * $client_list[$data_item['plan']]['rate'] : $data_item['display_count'];
                        $data_item['click_count_client'] = $client_list[$data_item['plan']]['version'] == Constant::CLIENT_VERSION_ANNUAL ? $data_item['click_count'] * $client_list[$data_item['plan']]['rate'] : $data_item['click_count'];
                    }
                    if (empty($row_message)) {
                        $report_key = ReportKey::get(['user_id' => $client_list[$data_item['plan']]['id'], 'type' => $promote_type, 'date' => $data_item['date'], 'plan' => $data_item['plan'], 'name' => $data_item['name'], 'unit' => $data_item['unit']]);
                        if (empty($report_key)) {
                            $data_list[] = $data_item;
                        } else {
                            $data_item['id'] = $report_key['id'];
                            $data_list[] = $data_item;
                        }
                    } else {
                        $message_list[] = '第' . $row_index . '行：' . implode('/', $row_message) . ";\n";
                    }
                }
            }
            if (empty($message_list)) {
                Db::startTrans();
                try {
                    $model = new ReportKey();
                    $model->saveAll($data_list);
                    Db::commit();
                    return api_success('导入成功');
                } catch (\Exception $e) {
                    Db::rollback();
                    return api_error("导入错误：" . $e->getMessage());
                }
            } else {
                return api_error("导入失败\n" . implode('', $message_list));
            }
        }
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = ReportKey::get($id);
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
        $model = ReportKey::where($map)->find();
        if (!empty($model)) {
            return true;
        }
        return false;
    }
}