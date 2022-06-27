<?php

namespace app\admin\model;

use think\Model;
class UnitBid extends Model
{
    protected $pk = 'id';
    protected $table = 'unit_bid';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
}