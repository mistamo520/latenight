<?php

namespace app\admin\model;

use think\Model;
class ReportKey extends Model
{
    protected $pk = 'id';
    protected $table = 'report_key';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
}