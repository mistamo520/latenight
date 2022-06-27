<?php

function api_success($message = '', $data = null)
{
    return ['status' => 1, 'message' => $message, 'data' => $data];
}
function api_error($message, $status = 0)
{
    return ['status' => $status, 'message' => $message, 'data' => null];
}
function api_exception($exception)
{
    return ['status' => 0, 'message' => '系统错误:' . $exception->getMessage(), 'data' => $exception->getTraceAsString()];
}
function format_date($datetime, $format = '')
{
    if (empty($format)) {
        return date('Y-m-d', strtotime($datetime));
    } else {
        return date($format, strtotime($datetime));
    }
}
function get_date()
{
    return date('Y-m-d');
}
function get_time()
{
    return date('Y-m-d H:i:s');
}
function get_time_diff($date)
{
    $days = floor((time() - strtotime($date)) / 86400);
    if ($days >= 1) {
        return $date;
    } elseif ($days == 1) {
        return '1天前';
    }
    $hour = floor((time() - strtotime($date)) % 86400 / 3600);
    if ($hour > 0) {
        return $hour . '小时前';
    }
    $minute = floor((time() - strtotime($date)) % 86400 / 60);
    if ($minute > 0) {
        return $minute . '分钟前';
    }
    return '刚刚';
}
function get_month_start($date = '')
{
    return empty($date) ? date('Y-m-01') : date('Y-m-01', strtotime($date));
}
function get_month_end($date = '')
{
    return empty($date) ? date('Y-m-t 23:59:59') : date('Y-m-t 23:59:59', strtotime($date));
}
function get_quarter_start($date = '')
{
    $date = empty($date) ? time() : strtotime($date);
    $month = date('m', $date);
    if ($month <= 3) {
        $start_month = 1;
    } elseif ($month <= 6) {
        $start_month = 4;
    } elseif ($month <= 9) {
        $start_month = 7;
    } else {
        $start_month = 10;
    }
    return date("Y", $date) . '-' . $start_month . '-01';
}
function get_quarter_end($date = '')
{
    $date = empty($date) ? time() : strtotime($date);
    $month = date('m', $date);
    if ($month <= 3) {
        $end_month = 3;
    } elseif ($month <= 6) {
        $end_month = 6;
    } elseif ($month <= 9) {
        $end_month = 9;
    } else {
        $end_month = 12;
    }
    $date = date("Y", $date) . '-' . $end_month . '-01';
    return date('Y-m-t 23:59:59', strtotime($date));
}
function get_distance($longitude1, $latitude1, $longitude2, $latitude2, $decimal = 2)
{
    $EARTH_RADIUS = 6370.996;
    $PI = 3.1415926;
    $radLat1 = $latitude1 * $PI / 180.0;
    $radLat2 = $latitude2 * $PI / 180.0;
    $radLng1 = $longitude1 * $PI / 180.0;
    $radLng2 = $longitude2 * $PI / 180.0;
    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;
    $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $distance = $distance * $EARTH_RADIUS * 1000;
    return round($distance, $decimal);
}
function get_ip()
{
    global $ip;
    if (getenv("HTTP_CLIENT_IP")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } else {
            if (getenv("REMOTE_ADDR")) {
                $ip = getenv("REMOTE_ADDR");
            } else {
                $ip = "Unknown";
            }
        }
    }
    return $ip;
}
function get_setting($code)
{
    $setting = \app\admin\model\Setting::get(['code' => $code]);
    return empty($setting) ? '' : $setting['value'];
}
function get_multiple_value($key, $list)
{
    $value = [];
    foreach (explode(',', $key) as $item) {
        if (array_key_exists($item, $list)) {
            $value[] = $list[$item];
        }
    }
    return implode(',', $value);
}
function get_value($key, $list)
{
    return array_key_exists($key, $list) ? $list[$key] : '';
}
function get_key_value($list)
{
    $key_value = [];
    foreach ($list as $key => $value) {
        $key_value[] = ['key' => $key, 'value' => $value];
    }
    return $key_value;
}
function filter_value($array, $filter_key, $filter_value)
{
    $return_array = [];
    foreach ($array as $item) {
        if (is_array($filter_value) && in_array($item[$filter_key], $filter_value)) {
            $return_array[] = $item;
        } elseif (!is_array($filter_value) && $item[$filter_key] == $filter_value) {
            $return_array[] = $item;
        }
    }
    return $return_array;
}
function get_substring($title, $length)
{
    if (mb_strlen($title, 'UTF8') > $length) {
        return mb_substr($title, 0, $length - 1) . '...';
    }
    return $title;
}
function request_url($url, $params)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
    $data = curl_exec($curl);
    $error_number = curl_errno($curl);
    if ($error_number) {
        return ['status' => 0, 'message' => '接口异常：' . $error_number];
    }
    curl_close($curl);
    return json_decode($data, true);
}
function request_baidu_api($url, $body, $header)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $post_data = json_encode(['header' => $header, 'body' => $body]);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    $data = curl_exec($curl);
    $error_number = curl_errno($curl);
    if ($error_number) {
        return false;
    }
    curl_close($curl);
    return json_decode($data, true);
}
function request_oce_api($url, $header = [], $method = 'POST', $param = [])
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    if (!empty($header)) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    }
    if (!empty($param)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
    }
    $data = curl_exec($curl);
    $error_number = curl_errno($curl);
    if ($error_number) {
        return false;
    }
    curl_close($curl);
    return json_decode($data, true, 512, JSON_BIGINT_AS_STRING);
}
function request_qq_api($url, $method = 'POST', $param = [])
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    if (!empty($header)) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    }
    if (!empty($param)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
    }
    $data = curl_exec($curl);
    $error_number = curl_errno($curl);
    if ($error_number) {
        return false;
    }
    curl_close($curl);
    return json_decode($data, true, 512, JSON_BIGINT_AS_STRING);
}
function request_360_api($url, $header = [], $method = 'POST', $param = [])
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    if (!empty($header)) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    }
    if (!empty($param)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
    }
    $data = curl_exec($curl);
    $error_number = curl_errno($curl);
    if ($error_number) {
        return false;
    }
    curl_close($curl);
    return json_decode($data, true, 512, JSON_BIGINT_AS_STRING);
}