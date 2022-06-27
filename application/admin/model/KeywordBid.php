<?php

namespace app\admin\model;

use think\Model;
class KeywordBid extends Model
{
    protected $pk = 'id';
    protected $table = 'keyword_bid';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
}