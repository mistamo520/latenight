<?php

namespace app\admin\controller;

use app\admin\common\Cache;
use app\admin\common\Constant;
use app\admin\common\Common;
use app\admin\common\NetTool;
use app\admin\common\Permission;
use app\admin\model\Order;
use app\admin\model\Remind;
use app\admin\model\Setting;
use app\admin\model\User;
class BaseController extends \think\Controller
{
    var $userId, $user, $dataPermission, $selectPermission, $isAdmin, $isGlobal, $promoteType, $settings;
    protected function _initialize()
    {

        $controller_name = strtolower($this->request->controller());
        $action_name = strtolower($this->request->action());

        if (!in_array($action_name, Constant::ACTION_NO_AUTHORIZATION_REQUIRED)) {
// echo 333;die;
            if (session(Constant::SESSION_USER_ID)) {
                $user_id = session(Constant::SESSION_USER_ID);
                $user = User::get(['id' => $user_id, 'active' => 1, 'deleted' => 0]);
                if (!empty($user)) {
                    $permission = session(Constant::SESSION_USER_PERMISSION);
                    $is_admin = $user['role_id'] == Constant::ROLE_ADMIN;
                    if ($is_admin || $controller_name == 'common' || in_array('system.' . str_replace('_', '', $controller_name), $permission['controller_permission'])) {
                        $this->isGlobal = $is_admin;
                        $this->user = $user;
                        $this->userId = $user_id;
                        $this->isAdmin = $is_admin;
                        $this->promoteType = Constant::PROMOTE_TYPE_CPC;
                        $promote_type_name = '搜索推广';
                        if (strpos($controller_name, 'b2b') !== false) {
                            $this->promoteType = Constant::PROMOTE_TYPE_B2B;
                            $promote_type_name = '爱采购';
                        } elseif (strpos($controller_name, 'oce') !== false) {
                            $this->promoteType = Constant::PROMOTE_TYPE_OCE;
                            $promote_type_name = '巨量推广';
                        } elseif (strpos($controller_name, '360') !== false) {
                            $this->promoteType = Constant::PROMOTE_TYPE_360;
                            $promote_type_name = '360推广';
                        } elseif (strpos($controller_name, 'qq') !== false) {
                            $this->promoteType = Constant::PROMOTE_TYPE_QQ;
                            $promote_type_name = 'QQ推广';
                        }
                        if (strpos($_SERVER['HTTP_HOST'], 'localhost') === false && $action_name == 'save' && !in_array($this->promoteType, session(Constant::SESSION_APP_PERMISSION))) {
                            echo json_encode(api_error('应用未授权'));
                            die;
                        }
                        $menus = Permission::get_menus($is_admin, $permission['menu_permission']);
                        $this->load_notification($permission, $is_admin);
                        $agent = User::get(['type' => Constant::USER_TYPE_AGENT, 'website' => ['like', '%' . $_SERVER['SERVER_NAME'] . '%']]);
                        $setting_list = Setting::column('value', 'code');
                        $this->settings = $setting_list;
                        $oem_name = $setting_list['system.website_name'];
                        if (!empty($agent) && !empty($agent->oem_name)) {
                            $oem_name = $agent->oem_name;
                        }
                        $this->assign('setting', $setting_list);
                        $this->assign('oem_name', $oem_name);
                        $this->assign('menu', $menus);
                        $this->assign('user', $user);
                        $this->assign('is_admin', $is_admin);
                        $this->assign('base_user_id', session(Constant::SESSION_BASE_USER_ID));
                        $this->assign('promote_type_name', $promote_type_name);
                        $this->assign('promote_type', $this->promoteType);
                    } else {
                        $this->redirect(url('index/unauthorized'));
                    }
                } else {
                    $this->redirect(url('index/login'));
                }
            } else {

                $this->redirect(url('index/login'));
            }
        }
    }
    private function load_notification($permission, $is_admin)
    {
        $data = ['remind_cpc' => 0, 'remind_b2b' => 0];
        if ($is_admin || in_array('system.remind', $permission['controller_permission'])) {
            $data['remind_cpc'] = Remind::where(['promote_type' => Constant::PROMOTE_TYPE_CPC, 'status' => 0])->count();
        }
        if ($is_admin || in_array('system.remind', $permission['controller_permission'])) {
            $data['remind_b2b'] = Remind::where(['promote_type' => Constant::PROMOTE_TYPE_B2B, 'status' => 0])->count();
        }
        if ($is_admin || in_array('system.remind', $permission['controller_permission'])) {
            $data['remind_oce'] = Remind::where(['promote_type' => Constant::PROMOTE_TYPE_OCE, 'status' => 0])->count();
        }
        $this->assign('notification', $data);
    }
    private function check_license()
    {
        $mac_address = '';
        if (!empty($mac_address)) {
            $cache_mac = \cache('mac_address');
            if (empty($cache_mac)) {
                $net_tool = new NetTool(PHP_OS);
                $cache_mac = $net_tool->mac_addr;
                if (!empty($cache_mac)) {
                    \cache('mac_address', $cache_mac, 24 * 60 * 60);
                }
            }
            if ($cache_mac != $mac_address) {
                return false;
            }
        }
        return true;
    }
    protected function process_query($key_field, $date_field = '')
    {
        $query = $this->request->param('query');
        $map = array();
        if (!empty($query)) {
            $result['key'] = array();
            $list = explode(' ', $query);
            foreach ($list as $item) {
                if (!empty($item)) {
                    array_push($result['key'], '%' . $item . '%');
                }
            }
            if (!empty($result['key'])) {
                $map[$key_field] = ['like', $result['key'], 'OR'];
            }
        }
        if (!empty($date_field)) {
            $start_date = $this->request->param('start_date');
            $end_date = $this->request->param('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $map['date'] = ['between', [$start_date, $end_date]];
            } elseif (!empty($start_date)) {
                $map['date'] = ['egt', $start_date, $end_date];
            } elseif (!empty($end_date)) {
                $map['date'] = ['elt', $end_date];
            }
        }
        return $map;
    }
    private function check_date($number)
    {
        $length = strlen($number);
        $day = substr($number, $length - 2, 2);
        $month = substr($number, $length - 4, 2);
        if ($length == 6) {
            $year = '20' . substr($number, 0, 2);
        } else {
            $year = substr($number, 0, 4);
        }
        $date = $year . '-' . $month . '-' . $day;
        $unixTime = strtotime($date);
        if (!$unixTime) {
            return false;
        }
        $fomart = "Y-m-d";
        if (date($fomart, $unixTime) == $date) {
            return $date;
        }
        return false;
    }
}