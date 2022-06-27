<?php

namespace app\admin\common;

use app\admin\model\Remind;
use PHPExcel_IOFactory;
use PHPExcel;
class Tool
{
    public static function remind($type, $promote_type, $user_id, $message)
    {
        $remind = new Remind();
        $remind->type = $type;
        $remind->promote_type = $promote_type;
        $remind->content = $message;
        $remind->user_id = $user_id;
        $remind->created_user_id = $user_id;
        $remind->save();
    }
    public static function export_excel($data, $header, $filename)
    {
        $array = array();
        array_push($array, $header);
        foreach ($data as $item) {
            $result = [];
            array_walk_recursive($item, function ($value) use(&$result) {
                array_push($result, $value);
            });
            array_push($array, $result);
        }
        vendor("PHPExcel.PHPExcel");
        $PHPExcel = new PHPExcel();
        $PHPSheet = $PHPExcel->getActiveSheet();
        $PHPSheet->setTitle('Sheet1');
        $PHPSheet->fromArray($array);
        $PHPWriter = PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel2007');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        $PHPWriter->save("php://output");
    }
}