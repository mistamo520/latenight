<?php

namespace app\admin\model;

use think\Model;
class Unit extends Model
{
    protected $pk = 'id';
    protected $table = 'unit';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
    public function user()
    {
        return $this->belongsTo('User');
    }
}