<?php

namespace app\admin\model;

use think\Model;
class Product extends Model
{
    protected $pk = 'id';
    protected $table = 'product';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
    public function bid()
    {
        return $this->hasOne('ProductBid');
    }
    public function user()
    {
        return $this->belongsTo('User');
    }
}