<?php

namespace app\admin\model;

use think\Model;
class Remind extends Model
{
    protected $pk = 'id';
    protected $table = 'remind';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
}