<?php

namespace app\admin\model;

use app\admin\common\Constant;
use app\admin\common\Common;
use think\Model;
class User extends Model
{
    protected $pk = 'id';
    protected $table = 'user';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
    public function role()
    {
        return $this->belongsTo('Role', 'role_id');
    }
    public function agent()
    {
        return $this->belongsTo('User', 'parent_id');
    }
}