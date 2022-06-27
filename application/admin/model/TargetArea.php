<?php

namespace app\admin\model;

use think\Model;
class TargetArea extends Model
{
    protected $pk = 'id';
    protected $table = 'target_area';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
    public function user()
    {
        return $this->belongsTo('User');
    }
}