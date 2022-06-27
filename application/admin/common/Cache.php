<?php

namespace app\admin\common;

use app\admin\model\User;
class Cache
{
    public static function clear($cache_name)
    {
        cache($cache_name, null);
    }
    public static function key_value($cache_name, $condition_key = '', $condition_value = null, $key_column = 'id', $value_column = 'name')
    {
        $list = self::get($cache_name);
        $data = [];
        foreach ($list as $item) {
            if (empty($condition_key)) {
                $data[$item[$key_column]] = $item[$value_column];
            } elseif (is_array($condition_value) && in_array($item[$condition_key], $condition_value)) {
                $data[$item[$key_column]] = $item[$value_column];
            } elseif (!is_array($condition_value) && $item[$condition_key] == $condition_value) {
                $data[$item[$key_column]] = $item[$value_column];
            }
        }
        return $data;
    }
    public static function array_value($cache_name, $condition_key = '', $condition_value = null)
    {
        $list = self::get($cache_name);
        if (empty($condition_key)) {
            return $list;
        } else {
            $data = [];
            foreach ($list as $item) {
                if (is_array($condition_value) && in_array($item[$condition_key], $condition_value)) {
                    $data[] = $item;
                } elseif (!is_array($condition_value) && $item[$condition_key] == $condition_value) {
                    $data[] = $item;
                }
            }
            return $data;
        }
    }
    public static function get_value($cache_name, $condition_value, $value_field = 'name', $condition_key = 'id')
    {
        $list = self::get($cache_name);
        foreach ($list as $item) {
            if ($item[$condition_key] == $condition_value) {
                return $item[$value_field];
            }
        }
        return '';
    }
    public static function get($cache_name)
    {
        $list = cache($cache_name);
        if (!$list) {
            $field = '';
            switch ($cache_name) {
                case 'role':
                    $field = 'id,name';
                    break;
                case 'user':
                    $field = 'id,name';
                    break;
                default:
                    break;
            }
            $list = db($cache_name)->field($field)->where(['deleted' => 0])->select();
            cache($cache_name, $list);
        }
        return $list;
    }
}