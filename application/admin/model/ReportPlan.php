<?php

namespace app\admin\model;

use think\Model;
class ReportPlan extends Model
{
    protected $pk = 'id';
    protected $table = 'report_plan';
    protected function base($query)
    {
        $query->where('deleted', 0);
    }
}