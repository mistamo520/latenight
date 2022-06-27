<?php

namespace app\admin\model;

use think\Model;
class NegativeWord extends Model
{
    protected $pk = 'id';
    protected $table = 'negative_word';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
    public function user()
    {
        return $this->belongsTo('User');
    }
}