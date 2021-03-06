<?php

namespace app\admin\controller;

use app\admin\model\ReportKey;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\User;
use think\Db;
class RankReportController extends BaseController
{
    public function index()
    {
        return view();
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $view = $view . 'index_client';
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $view = $view . 'index_agent';
        } else {
            $view = $view . 'index';
        }
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('client.user_name|client.name|agent.name|rank_report.name', 'date');
        $map['rank_report.deleted'] = '0';
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['rank_report.user_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        }
        $order = 'date desc';
        $count = db('rank_report')->join('user client', 'client.id = rank_report.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('rank_report')->join('user client', 'client.id = rank_report.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('rank_report.id,rank_report.user_id,rank_report.keyword,rank_report.date,rank_report.rank,rank_report.domain,rank_report.amount_client,rank_report.amount_agent,rank_report.created_time,rank_report.created_user_id,client.name as client_name,agent.name as agent_name,rank_report.updated_time,rank_report.updated_user_id')->limit($start, $length)->order($order)->select();
        foreach ($list as $key => $item) {
            if ($this->user->type == Constant::USER_TYPE_CLIENT) {
                unset($list[$key]['amount_agent']);
            } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
                $map['client.parent_id'] = $this->userId;
            }
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
                    $promote_type = strpos($item['name'][1], '?????????') !== false ? Constant::PROMOTE_TYPE_B2B : Constant::PROMOTE_TYPE_CPC;
                    $client_name = str_replace('?????????', '', str_replace('CPC', '', $item['name'][1]));
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
            return api_success('????????????');
        } else {
            return api_error('????????????');
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
                return api_error("?????????CSV??????");
            }
            $import_folder = '\\public\\upload\\import';
            $info = $file->move(ROOT_PATH . $import_folder);
            if (!$info) {
                return api_error("????????????");
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
                return api_error("???????????????");
            }
            $column_list = $import_list[0];
            $template_column_list = ['?????????', '??????', '????????????', '????????????', '??????', '??????', '??????', '????????????%???', '??????????????????'];
            if ($template_column_list != $column_list) {
                return api_error("???????????????????????????????????????????????????????????????");
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
                            $row_message[] = $template_column_list[$i] . '????????????';
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
                        $row_message[] = '?????????????????????????????????';
                    }
                    if (!key_exists($data_item['plan'], $client_list)) {
                        $row_message[] = '???????????????' . $data_item['plan'];
                    } elseif (empty($client_list[$data_item['plan']]['rate'])) {
                        $row_message[] = '???????????????' . $data_item['plan'] . '????????????';
                    } elseif (!key_exists($client_list[$data_item['plan']]['parent_id'], $agent_list)) {
                        $row_message[] = '???????????????' . $data_item['plan'] . '???????????????';
                    } elseif (empty($agent_list[$client_list[$data_item['plan']]['parent_id']]['rate'])) {
                        $row_message[] = '???????????????' . $data_item['plan'] . '?????????????????????';
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
                        $message_list[] = '???' . $row_index . '??????' . implode('/', $row_message) . ";\n";
                    }
                }
            }
            if (empty($message_list)) {
                Db::startTrans();
                try {
                    $model = new ReportKey();
                    $model->saveAll($data_list);
                    Db::commit();
                    return api_success('????????????');
                } catch (\Exception $e) {
                    Db::rollback();
                    return api_error("???????????????" . $e->getMessage());
                }
            } else {
                return api_error("????????????\n" . implode('', $message_list));
            }
        }
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = ReportKey::get($id);
        if (empty($model)) {
            return api_error('???????????????');
        }
        $model->deleted = 1;
        $model->deleted_user_id = $this->userId;
        $model->deleted_time = date('Y-m-d H:i:s');
        $model->save();
        return api_success('????????????');
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