<?php

namespace app\admin\model;

use app\admin\common\Constant;
use think\Model;
class Location extends Model
{
    protected $pk = 'id';
    protected $table = 'location';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
}