<?php

namespace app\admin\model;

use think\Model;
class Test extends Model
{
    protected $pk = 'id';
    protected $table = 'test';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
}