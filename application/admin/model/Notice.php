<?php

namespace app\admin\model;

use think\Model;
class Notice extends Model
{
    protected $pk = 'id';
    protected $table = 'notice';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
}