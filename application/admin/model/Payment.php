<?php

namespace app\admin\model;

use think\Model;
class Payment extends Model
{
    protected $pk = 'id';
    protected $table = 'payment';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
}