<?php

namespace app\admin\model;

use think\Model;
class UserWechat extends Model
{
    protected $pk = 'id';
    protected $table = 'user_wechat';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
}