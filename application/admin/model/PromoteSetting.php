<?php

namespace app\admin\model;

use think\Model;
class PromoteSetting extends Model
{
    protected $pk = 'id';
    protected $table = 'promote_setting';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
    public function user()
    {
        return $this->belongsTo('User');
    }
}