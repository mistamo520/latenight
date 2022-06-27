<?php

namespace app\admin\model;

use think\Model;
class Role extends Model
{
    protected $pk = 'id';
    protected $table = 'role';
    public function permissions()
    {
        return $this->hasMany('Permission', 'role_id')->field('name');
    }
}