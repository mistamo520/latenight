<?php

namespace app\admin\controller;

use app\admin\common\Tool;
use app\admin\model\ReportPlan;
use app\admin\common\Constant;
use app\admin\common\Cache;
use app\admin\model\User;
use think\Db;
class ReportPlanController extends BaseController
{
    public function index()
    {
        $view = 'report_plan/';
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $view .= 'index_client';
            $this->assign('is_annual', $this->user->version == Constant::CLIENT_VERSION_ANNUAL);
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $view .= 'index_agent';
        } else {
            $view .= 'index';
        }
        return view($view);
    }
    public function get_list()
    {
        $start = $this->request->param('start');
        $length = $this->request->param('length');
        $map = $this->process_query('client.user_name|client.name|agent.name', 'date');
        $map['report_plan.deleted'] = '0';
        if ($this->user->type == Constant::USER_TYPE_CLIENT) {
            $map['report_plan.user_id'] = $this->userId;
        } elseif ($this->user->type == Constant::USER_TYPE_AGENT) {
            $map['client.parent_id'] = $this->userId;
        }
        $promote_type = $this->promoteType;
        $map['report_plan.type'] = $promote_type;
        $order = 'date desc';
        $count = db('report_plan')->join('user client', 'client.id = report_plan.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->count();
        $list = db('report_plan')->join('user client', 'client.id = report_plan.user_id')->join('user agent', 'client.parent_id = agent.id')->where($map)->field('report_plan.id,report_plan.user_id,report_plan.name,report_plan.date,report_plan.display_count,report_plan.click_count,report_plan.click_rate,report_plan.amount,report_plan.amount_client,report_plan.amount_agent,report_plan.created_time,report_plan.created_user_id,client.user_name as client_name,agent.name as agent_name,report_plan.updated_time,report_plan.updated_user_id,report_plan.charged,display_count_client,click_count_client,client.version as client_version')->limit($start, $length)->order($order)->select();
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
                    $list[$key]['amount_avg'] = empty($item['click_count']) ? '0.00' : round($item['amount_client'] / (empty($item['click_count']) ? 1 : $item['click_count']), 2);
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
    public function _item_import()
    {
        return view('report_plan/_item_import');
    }
    public function import_save()
    {
        $file = $this->request->file('import_file');
        $promote_type = strtolower($this->request->controller()) == 'reportplanb2b' ? Constant::PROMOTE_TYPE_B2B : Constant::PROMOTE_TYPE_CPC;
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
            $template_column_list = ['日期', '计划名称', '展现', '点击', '消费', '点击率（%）', '平均点击价格'];
            if ($template_column_list != $column_list) {
                return api_error("导入的文件不正确，请检查列名是否和模板一致");
            }
            $message_list = [];
            $data_list = [];
            $report_list = [];
            $client_list = User::where('type', Constant::USER_TYPE_CLIENT)->column('id,parent_id,rate,version', 'user_name');
            $agent_list = User::where('type', Constant::USER_TYPE_AGENT)->column('name,rate', 'id');
            foreach ($import_list as $row_index => $row_data) {
                if ($row_index >= 1) {
                    $row_message = [];
                    for ($i = 0; $i < count($row_data); $i++) {
                        if (trim($row_data[$i]) === '') {
                            $row_message[] = $template_column_list[$i] . '不能为空';
                        }
                    }
                    $data_item['date'] = str_replace('/', '-', $row_data[0]);
                    $data_item['name'] = $row_data[1];
                    $data_item['display_count'] = $row_data[2];
                    $data_item['click_count'] = $row_data[3];
                    $data_item['amount'] = $row_data[4];
                    $data_item['click_rate'] = $row_data[5];
                    $data_item['created_user_id'] = $this->userId;
                    $data_item['created_time'] = get_time();
                    $data_item['type'] = $promote_type;
                    if (strtotime($data_item['date']) >= strtotime(date('Y-m-d'))) {
                        $row_message[] = '不可上传当天以后的报告';
                    }
                    if (!key_exists($data_item['name'], $client_list)) {
                        $row_message[] = '未找到客户' . $data_item['name'];
                    } elseif (empty($client_list[$data_item['name']]['rate'])) {
                        $row_message[] = '未找到客户' . $data_item['name'] . '的扣费比';
                    } elseif (!key_exists($client_list[$data_item['name']]['parent_id'], $agent_list)) {
                        $row_message[] = '未找到客户' . $data_item['name'] . '的代理信息';
                    } elseif (empty($agent_list[$client_list[$data_item['name']]['parent_id']]['rate'])) {
                        $row_message[] = '未找到客户' . $data_item['name'] . '的代理计费系数';
                    } else {
                        $user_id = $client_list[$data_item['name']]['id'];
                        if (in_array($data_item['date'] . '_' . $user_id, $report_list)) {
                            $row_message[] = '客户' . $data_item['name'] . '日期' . $data_item['date'] . '存在重复数据';
                        } else {
                            $report_list[] = $data_item['date'] . '_' . $user_id;
                            $exist = ReportPlan::where(['date' => $data_item['date'], 'user_id' => $user_id])->find();
                            if (!empty($exist) && !empty($exist->charged)) {
                                $row_message[] = '客户' . $data_item['name'] . '日期' . $data_item['date'] . '已扣费';
                            } else {
                                if (!empty($exist)) {
                                    $data_item['id'] = $exist->id;
                                }
                                $data_item['user_id'] = $user_id;
                                $data_item['amount_agent'] = $data_item['amount'] * $agent_list[$client_list[$data_item['name']]['parent_id']]['rate'];
                                $data_item['amount_client'] = $client_list[$data_item['name']]['version'] == Constant::CLIENT_VERSION_ANNUAL ? 0 : $data_item['amount_agent'] * $client_list[$data_item['name']]['rate'];
                                $data_item['display_count_client'] = $client_list[$data_item['name']]['version'] == Constant::CLIENT_VERSION_ANNUAL ? $data_item['display_count'] * $client_list[$data_item['name']]['rate'] : $data_item['display_count'];
                                $data_item['click_count_client'] = $client_list[$data_item['name']]['version'] == Constant::CLIENT_VERSION_ANNUAL ? $data_item['click_count'] * $client_list[$data_item['name']]['rate'] : $data_item['click_count'];
                            }
                        }
                    }
                    if (empty($row_message)) {
                        $report_plan = ReportPlan::get(['user_id' => $client_list[$data_item['name']]['id'], 'type' => $promote_type, 'date' => $data_item['date']]);
                        if (empty($report_plan)) {
                            $data_list[] = $data_item;
                        } else {
                            $data_item['id'] = $report_plan['id'];
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
                    $model = new ReportPlan();
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
    public function charge()
    {
        $promote_type = $this->promoteType;
        $list = ReportPlan::where(['type' => $promote_type, 'charged' => 0, 'date' => ['lt', date('Y-m-d')]])->select();
        Db::startTrans();
        try {
            foreach ($list as $item) {
                $client = User::get($item->user_id);
                if (!empty($item->amount_client)) {
                    $client->balance -= $item->amount_client;
                    $client->save();
                    if ($client->balance < 1000) {
                        Tool::remind(Constant::REMIND_TYPE_BALANCE, $this->promoteType, $client->id, '账户余额不足1000，请联系客户充值');
                    }
                }
                if (!empty($item->amount_agent)) {
                    $agent = User::get($client->parent_id);
                    $agent->balance -= $item->amount_agent;
                    $agent->save();
                    if ($agent->balance < 1000) {
                        Tool::remind(Constant::REMIND_TYPE_BALANCE, $this->promoteType, $agent->id, '账户余额不足1000，请联系代理商充值');
                    }
                }
                $item->charged = 1;
                $item->updated_user_id = $this->userId;
                $item->updated_time = get_time();
                $item->save();
            }
            $client_list = User::where(['type' => Constant::USER_TYPE_CLIENT, 'version' => Constant::CLIENT_VERSION_ANNUAL, 'expired_date' => ['gt', date('Y-m-d'), strtotime('+60 days')]])->column('id');
            foreach ($client_list as $user_id) {
                Tool::remind(Constant::REMIND_TYPE_EXPIRED, $this->promoteType, $user_id, '账户即将到期，请联系客户续费');
            }
            Db::commit();
            return api_success('扣费成功');
        } catch (\Exception $e) {
            Db::rollback();
            return api_error("扣费错误：" . $e->getMessage());
        }
    }
    public function recharge()
    {
        $date = input('param.')['date'];
        $promote_type = $this->promoteType;
        $list = ReportPlan::where(['type' => $promote_type, 'charged' => 1, 'date' => $date])->select();
        Db::startTrans();
        try {
            foreach ($list as $item) {
                $client = User::get($item->user_id);
                if (!empty($item->amount_client)) {
                    $client->balance += $item->amount_client;
                    $client->save();
                }
                if (!empty($item->amount_agent)) {
                    $agent = User::get($client->parent_id);
                    $agent->balance += $item->amount_agent;
                    $agent->save();
                }
                $item->charged = 0;
                $item->updated_user_id = null;
                $item->updated_time = null;
                $item->save();
            }
            Db::commit();
            return api_success('取消扣费成功');
        } catch (\Exception $e) {
            Db::rollback();
            return api_error("取消扣费错误：" . $e->getMessage());
        }
    }
    public function sync()
    {
        $start = input('param.')['start'];
        $end = input('param.')['end'];
        $request_body = ["realTimeRequestType" => ["reportType" => 10, "device" => 0, "levelOfDetails" => 3, "statRange" => 2, "platform" => 0, "unitOfTime" => 5, "number" => "1000", "pageIndex" => 1, "performanceData" => ["impression", "click", "cost", "cpc", "ctr"], "startDate" => $start, "endDate" => $end, "statIds" => [111, 222, 333], "attributes" => null, "order" => true]];
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
                        $report_plan = ReportPlan::get(['user_id' => $client->id, 'type' => $promote_type, 'date' => $item['date']]);
                        if (empty($report_plan) || empty($report_plan->charged) || $item['date'] == date('Y-m-d')) {
                            $data_item = [];
                            $data_item['date'] = $item['date'];
                            $data_item['name'] = $item['name'][1];
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
                            if (empty($report_plan)) {
                                $record_list[] = $data_item;
                            } else {
                                $data_item['id'] = $report_plan['id'];
                                $record_list[] = $data_item;
                            }
                        }
                    }
                }
                $model = new ReportPlan();
                $model->saveAll($record_list);
            }
            return api_success('同步成功');
        } else {
            return api_error('同步失败');
        }
    }
    public function delete()
    {
        $id = $this->request->param('id');
        $model = ReportPlan::get($id);
        if (empty($model)) {
            return api_error('记录不存在');
        }
        $model->deleted = 1;
        $model->deleted_user_id = $this->userId;
        $model->deleted_time = date('Y-m-d H:i:s');
        $model->save();
        return api_success('删除成功');
    }
}