<?php

namespace app\api\controller;

use app\admin\common\Constant;
use app\admin\common\GetMacAddr;
use app\admin\common\NetTool;
use app\admin\common\Tool;
use app\admin\model\Message;
use app\admin\model\User;
use think\Controller;
class CommonController extends Controller
{
    public function load()
    {
        header('Access-Control-Allow-Origin:*');
        if (true || $_SERVER['SERVER_NAME'] == 'info.91supai.com') {
            $data = input('param.');
            if (empty($data['key']) || empty($data['domain']) || empty($data['pc'])) {
                return null;
            }
            $is_pc = $data['pc'] == 'pc';
            $is_mobile = !$is_pc;
            $user = User::get(['script_key' => $data['key'], 'type' => Constant::USER_TYPE_CLIENT]);
            if (empty($user) || strpos($data['domain'], $user->website) === false) {
                return null;
            } else {
                $contact_enable = true;
                $contact_float_enable = true;
                $message_enable = true;
                $contact_open_message = false;
                $message_title = 'ċĉşçè¨';
                $theme_color = '#3e8847';
                $message_position = 'center';
                $contact_float_position = $is_pc ? 'center_right' : 'bottom_right';
                $message_open_seconds = 0;
                $contact_open_seconds = 0;
                $contact_code = $user['contact_code'];
                $copy_code = $user['copy_code'];
                $agent = User::get($user->parent_id);
                $system_name = empty($agent->oem_name) ? config('default_system_name') : $agent->oem_name;
                $expired = empty($user->expired_date) || strtotime($user->expired_date) < time();
                if ($expired) {
                    $contact_code = '';
                    $copy_code = '';
                    $contact_enable = false;
                    $message_enable = false;
                } else {
                    if (empty($user['setting'])) {
                        if (empty($contact_code)) {
                            $contact_enable = false;
                        }
                    } else {
                        $setting = json_decode($user['setting'], true);
                        if (!empty($setting['theme_color'])) {
                            $theme_color = $setting['theme_color'];
                        }
                        if (!empty($setting['message_title'])) {
                            $message_title = $setting['message_title'];
                        }
                        if ($is_pc && !empty($setting['message_position'])) {
                            $message_position = $setting['message_position'];
                        }
                        if ($is_pc && !empty($setting['contact_float_position'])) {
                            $contact_float_position = $setting['contact_float_position'];
                        }
                        if ($is_pc && !empty($setting['contact_open_message'])) {
                            $contact_open_message = $setting['contact_open_message'];
                        }
                        if ($is_mobile && !empty($setting['contact_float_position_mobile'])) {
                            $contact_float_position = $setting['contact_float_position_mobile'];
                        }
                        if (!empty($setting['contact_open_seconds'])) {
                            $contact_open_seconds = $setting['contact_open_seconds'];
                        }
                        if (!empty($setting['message_open_seconds'])) {
                            $message_open_seconds = $setting['message_open_seconds'];
                        }
                        if (empty($contact_code)) {
                            $contact_enable = false;
                            $contact_float_enable = false;
                        } elseif (!empty($setting['contact_start_time']) && !empty($setting['contact_end_time']) && (time() < strtotime(date('Y-m-d ' . $setting['contact_start_time'])) || time() > strtotime(date('Y-m-d ' . $setting['contact_end_time'])))) {
                            $contact_enable = false;
                            $contact_float_enable = false;
                        } else {
                            if ($is_pc && empty($setting['contact_enable_pc']) || $is_mobile && empty($setting['contact_enable_mobile'])) {
                                $contact_enable = false;
                            }
                            if ($is_pc && empty($setting['contact_enable_pc_float']) || $is_mobile && empty($setting['contact_enable_mobile_float'])) {
                                $contact_float_enable = false;
                            }
                        }
                        if ($is_pc && empty($setting['message_enable_pc']) || $is_mobile && empty($setting['message_enable_mobile'])) {
                            $message_enable = false;
                        } elseif (!empty($setting['message_start_time']) && !empty($setting['message_end_time']) && (time() < strtotime(date('Y-m-d ' . $setting['message_start_time'])) || time() > strtotime(date('Y-m-d ' . $setting['message_end_time'])))) {
                            $message_enable = false;
                        }
                    }
                    if ($user->version == Constant::CLIENT_VERSION_CALCULATION) {
                        $contact_code = '';
                        $contact_enable = false;
                        $contact_open_message = false;
                        $contact_float_position = '';
                        $contact_float_enable = '';
                        $message_open_seconds = '';
                        $contact_open_seconds = '';
                    }
                }
                return api_success('', ['expired' => $expired, 'contact_code' => $contact_code, 'copy_code' => $copy_code, 'theme_color' => $theme_color, 'message_title' => $message_title, 'message_enable' => $message_enable, 'message_position' => $message_position, 'contact_enable' => $contact_enable, 'contact_open_message' => $contact_open_message, 'contact_float_position' => $contact_float_position, 'contact_float_enable' => $contact_float_enable, 'message_open_seconds' => $message_open_seconds, 'contact_open_seconds' => $contact_open_seconds, 'system_name' => $system_name]);
            }
        }
    }
    public function message()
    {
        header('Access-Control-Allow-Origin:*');
        $data = input('param.');
        if (empty($data['key']) || empty($data['domain']) || empty($data['phone']) || empty($data['name']) || empty($data['content'])) {
            return null;
        } else {
            $user = User::get(['script_key' => $data['key'], 'type' => Constant::USER_TYPE_CLIENT]);
            if (empty($user) || strpos($data['domain'], $user->website) === false || empty($user->expired_date) || strtotime($user->expired_date) < time()) {
                return null;
            } else {
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $message_count = Message::where(['ip_address' => $ip_address, 'created_time' => ['between', [date('Y-m-d H:i:s', strtotime('-1hour')), get_time()]]])->count();
                $count_limit = get_setting('service.sms_count_limit');
                if (empty($count_limit) || !is_numeric($count_limit)) {
                    $count_limit = 10;
                }
                if ($message_count > $count_limit) {
                    return api_error('èŻ·ĉħèżċ¤ïĵèŻ·ç¨ċéèŻ');
                }
                $message = new Message();
                $message->user_id = $user->id;
                $message->name = $data['name'];
                $message->phone = $data['phone'];
                $message->created_time = get_time();
                $message->content = $data['content'];
                $message->ip_address = $_SERVER['REMOTE_ADDR'];
                if ($user->sms_count > 0) {
                    $user->sms_count -= 1;
                    $user->save();
                    $message->sms_sent = 1;
                    $agent = User::get($user->parent_id);
                    $oem_name = config('default_system_name');
                    if (!empty($agent) && !empty($agent->oem_name)) {
                        $oem_name = $agent->oem_name;
                    }
                    $content = 'ĉéĉ¨ - ċ?˘ĉ·ïĵ' . $data['name'] . 'ïĵçµèŻïĵ' . $data['phone'] . 'ïĵċ¨èŻ˘ïĵ' . substr($data['content'], 0, 30);
                    $message->sms_status = $this->send_sms($content, $user->phone, $oem_name);
                }
                $message->save();
                return api_success('ĉè°˘ĉ¨çĉŻĉ!', ['phone' => $user->phone]);
            }
        }
    }
    public function load_global()
    {
        header('Access-Control-Allow-Origin:*');
        if (true) {
            $data = input('param.');
            if (empty($data['uid']) || empty($data['pc'])) {
                return null;
            }
            $is_pc = $data['pc'] == 'pc';
            $is_mobile = !$is_pc;
            $user = User::get(['relate_id' => $data['uid'], 'type' => Constant::USER_TYPE_CLIENT]);
            if (empty($user)) {
                return null;
            } else {
                $contact_enable = true;
                $contact_float_enable = true;
                $message_enable = true;
                $message_title = 'ċĉşçè¨';
                $message_position = 'center';
                $contact_float_position = $is_pc ? 'center_right' : 'bottom_right';
                $message_open_seconds = 30;
                $contact_open_seconds = 30;
                $contact_code = $user['contact_code'];
                $copy_code = $user['copy_code'];
                $agent = User::get($user->parent_id);
                $system_name = empty($agent->oem_name) ? config('default_system_name') : $agent->oem_name;
                $expired = empty($user->expired_date) || strtotime($user->expired_date) < time();
                if ($expired) {
                    $contact_code = '';
                    $copy_code = '';
                    $contact_enable = false;
                    $message_enable = false;
                } else {
                    if (empty($user['setting'])) {
                        if (empty($contact_code)) {
                            $contact_enable = false;
                        }
                    } else {
                        $setting = json_decode($user['setting'], true);
                        if (!empty($setting['message_title'])) {
                            $message_title = $setting['message_title'];
                        }
                        if ($is_pc && !empty($setting['message_position'])) {
                            $message_position = $setting['message_position'];
                        }
                        if ($is_pc && !empty($setting['contact_float_position'])) {
                            $contact_float_position = $setting['contact_float_position'];
                        }
                        if (!empty($setting['contact_open_seconds'])) {
                            $contact_open_seconds = $setting['contact_open_seconds'];
                        }
                        if (!empty($setting['message_open_seconds'])) {
                            $message_open_seconds = $setting['message_open_seconds'];
                        }
                        if (empty($contact_code)) {
                            $contact_enable = false;
                            $contact_float_enable = false;
                        } elseif (!empty($setting['contact_start_time']) && !empty($setting['contact_end_time']) && (time() < strtotime(date('Y-m-d ' . $setting['contact_start_time'])) || time() > strtotime(date('Y-m-d ' . $setting['contact_end_time'])))) {
                            $contact_enable = false;
                            $contact_float_enable = false;
                        } else {
                            if ($is_pc && empty($setting['contact_enable_pc']) || $is_mobile && empty($setting['contact_enable_mobile'])) {
                                $contact_enable = false;
                            }
                            if ($is_pc && empty($setting['contact_enable_pc_float']) || $is_mobile && empty($setting['contact_enable_mobile_float'])) {
                                $contact_float_enable = false;
                            }
                        }
                        if ($is_pc && empty($setting['message_enable_pc']) || $is_mobile && empty($setting['message_enable_mobile'])) {
                            $message_enable = false;
                        } elseif (!empty($setting['message_start_time']) && !empty($setting['message_end_time']) && (time() < strtotime(date('Y-m-d ' . $setting['message_start_time'])) || time() > strtotime(date('Y-m-d ' . $setting['message_end_time'])))) {
                            $message_enable = false;
                        }
                    }
                }
                return api_success('', ['expired' => $expired, 'contact_code' => $contact_code, 'copy_code' => $copy_code, 'message_title' => $message_title, 'message_enable' => $message_enable, 'message_position' => $message_position, 'contact_enable' => $contact_enable, 'contact_float_position' => $contact_float_position, 'contact_float_enable' => $contact_float_enable, 'message_open_seconds' => $message_open_seconds, 'contact_open_seconds' => $contact_open_seconds, 'system_name' => $system_name]);
            }
        }
    }
    public function message_global()
    {
        header('Access-Control-Allow-Origin:*');
        $data = input('param.');
        if (empty($data['uid']) || empty($data['phone']) || empty($data['name']) || empty($data['content'])) {
            return null;
        } else {
            $user = User::get(['relate_id' => $data['uid'], 'type' => Constant::USER_TYPE_CLIENT]);
            if (empty($user)) {
                return null;
            } else {
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $message_count = Message::where(['ip_address' => $ip_address, 'created_time' => ['between', [date('Y-m-d H:i:s', strtotime('-1hour')), get_time()]]])->count();
                $count_limit = get_setting('service.sms_count_limit');
                if (empty($count_limit) || !is_numeric($count_limit)) {
                    $count_limit = 2;
                }
                if ($message_count > $count_limit) {
                    return api_error('èŻ·ĉħèżċ¤ïĵèŻ·ç¨ċéèŻ');
                }
                $message = new Message();
                $message->type = 2;
                $message->user_id = $user->id;
                $message->name = $data['name'];
                $message->phone = $data['phone'];
                $message->created_time = get_time();
                $message->content = $data['content'];
                $message->ip_address = $_SERVER['REMOTE_ADDR'];
                if ($user->sms_count > 0) {
                    $user->sms_count -= 1;
                    $user->save();
                    $message->sms_sent = 1;
                    $agent = User::get($user->parent_id);
                    $sms_signature = get_setting('service.sms_signature');
                    if (!empty($agent) && !empty($agent->oem_name)) {
                        $sms_signature = $agent->oem_name;
                    }
                    $content = 'ĉéĉ¨ - ċ?˘ĉ·ïĵ' . $data['name'] . 'ïĵçµèŻïĵ' . $data['phone'] . 'ïĵċ¨èŻ˘ïĵ' . substr($data['content'], 0, 30);
                    $message->sms_status = $this->send_sms($content, $user->phone, $sms_signature);
                }
                $message->save();
                return api_success('ĉè°˘ĉ¨çĉŻĉ!', ['phone' => $user->phone]);
            }
        }
    }
    public function count_list()
    {
        header('Access-Control-Allow-Origin:*');
        $data = input('param.');
        if (!empty($data['ids'])) {
            $ids = explode(',', $data['ids']);
            $list = User::where(['user_name' => ['in', $ids]])->column('sms_count', 'user_name');
            $count_list = [];
            foreach ($ids as $key) {
                if (array_key_exists($key, $list)) {
                    $count_list[] = $list[$key];
                } else {
                    $count_list[] = 0;
                }
            }
            return $count_list;
        }
    }
    public function test()
    {
        $this->send_sms('?91éĉ?ĉéĉ¨ïĵĉäşşĉ­£ċ¨ċ¨èŻ˘èŻ·ċĉĥĉ?ç', '15376191779');
    }
    function request_url($url, $post_data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($post_data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        }
        $data = curl_exec($curl);
        $error_number = curl_errno($curl);
        if ($error_number) {
            return array('status' => 0, 'message' => 'ĉ?ċ£ċĵċ¸¸ïĵ' . $error_number);
        }
        curl_close($curl);
        return json_decode($data, true);
    }
    private function send_sms_old($message, $phone)
    {
        $ch = curl_init();
        $post_data = array("account" => "", "password" => "", "destmobile" => $phone, "msgText" => $message . "?ċŻéĞç§ĉ?", "sendDateTime" => "");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $post_data = http_build_query($post_data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_URL, 'http://www.jianzhou.sh.cn/JianzhouSMSWSServer/http/sendBatchMessage');
        $info = curl_exec($ch);
        curl_close($ch);
        return $info;
    }
    private function send_sms($message, $phone, $sms_signature)
    {
        $statusStr = array("0" => "ç­äżĦċéĉċ", "-1" => "ċĉ°ä¸ċ¨", "-2" => "ĉċĦċ¨çİşé´ä¸ĉŻĉ,èŻ·çĦ?è?¤ĉŻĉcurlĉèfsocketïĵèç³ğĉ¨ççİşé´ċè§£ċ³ĉèĉ´ĉ˘çİşé´ïĵ", "30" => "ċŻç éèŻŻ", "40" => "è´Ĥċ·ä¸ċ­ċ¨", "41" => "ä½é˘ä¸èĥ³", "42" => "ċ¸ĉ·ċ·²èżĉ", "43" => "IPċ°ċéċĥ", "50" => "ċċ?ıċĞĉĉĉèŻ");
        $smsapi = "http://api.smsbao.com/";
        $user = config('sms')['account'];
        $pass = md5(config('sms')['password']);
        $content = '?' . $sms_signature . '?' . $message;
        $phone = $phone;
        $sendurl = $smsapi . "sms?u=" . $user . "&p=" . $pass . "&m=" . $phone . "&c=" . urlencode($content);
        $result = file_get_contents($sendurl);
        return $result;
    }
    public function testmac()
    {
        var_dump($_SERVER);
        $mac = new NetTool(PHP_OS);
        echo $mac->mac_addr;
    }
}