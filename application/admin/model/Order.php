<?php

namespace app\admin\model;

use app\admin\common\Constant;
use think\Model;
class Order extends Model
{
    protected $pk = 'id';
    protected $table = 'order';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
    public function client()
    {
        return $this->belongsTo('User', 'user_id');
    }
    public function getTypeTextAttr($value, $data)
    {
        return get_value($data['type'], Constant::ORDER_TYPE_LIST);
    }
    public function getVersionTextAttr($value, $data)
    {
        return get_value($data['version'], Constant::CLIENT_VERSION_LIST);
    }
    public function agent()
    {
        return $this->belongsTo('User', 'created_user_id');
    }
}