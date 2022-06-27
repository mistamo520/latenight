<?php

namespace app\admin\model;

use think\Model;
class RankKeyword extends Model
{
    protected $pk = 'id';
    protected $table = 'rank_keyword';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
}