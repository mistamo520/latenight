<?php

namespace app\admin\model;

use app\admin\common\Constant;
use think\Model;
class Banner extends Model
{
    protected $pk = 'id';
    protected $table = 'banner';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
    public function getPictureUrlAttr($value)
    {
        return str_replace('\\', '/', $value);
    }
}