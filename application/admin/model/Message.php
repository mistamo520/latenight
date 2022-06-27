<?php

namespace app\admin\model;

use think\Model;
class Message extends Model
{
    protected $pk = 'id';
    protected $table = 'message';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
}