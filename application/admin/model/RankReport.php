<?php

namespace app\admin\model;

use think\Model;
class RankReport extends Model
{
    protected $pk = 'id';
    protected $table = 'rank_report';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
}