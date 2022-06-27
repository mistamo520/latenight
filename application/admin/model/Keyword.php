<?php

namespace app\admin\model;

use think\Model;
class Keyword extends Model
{
    protected $pk = 'id';
    protected $table = 'keyword';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
    public function user()
    {
        return $this->belongsTo('User');
    }
}