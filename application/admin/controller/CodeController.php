<?php

namespace app\admin\controller;

use app\admin\common\Constant;
use app\admin\model\Company;
use app\admin\model\Organization;
use app\admin\model\User;
use app\admin\model\Role;
use think\Config;
use think\Db;
class CodeController extends BaseController
{
    public function index()
    {
        return view();
    }
    public function generate()
    {
        $data = input('post.');
        if (empty($data['table_name'])) {
            return json(array('status' => 0, "message" => '请输入表名'));
        }
        $code_type = $data['code_type'];
        $modal_type = $data['modal_type'];
        $modal_column = $modal_type == "sm" ? 12 : ($modal_type == "md" ? 6 : 3);
        $table_name = $data['table_name'];
        $db_name = Config::get('database');
        $db_name = $db_name['database'];
        $class_name = str_replace(' ', '', ucwords(str_replace('_', ' ', $data['table_name'])));
        $result = Db::query("select * from information_schema.tables where TABLE_NAME='{$table_name}' and TABLE_SCHEMA='{$db_name}'");
        if (empty($result)) {
            return json(array('status' => 0, "message" => '没找到表'));
        }
        $table_comment = $result[0]['TABLE_COMMENT'];
        $columns = Db::query("select * from information_schema.columns where TABLE_NAME='" . $table_name . "' and TABLE_SCHEMA='" . $db_name . "' and COLUMN_NAME not in ('updated_user_id','updated_time','deleted','deleted_user_id','deleted_time')");
        if (empty($columns)) {
            return json(array('status' => 0, "message" => '没有找到字段'));
        }
        if (empty($code_type) || $code_type == 'model') {
            $this->model($table_name, $class_name);
        }
        if (empty($code_type) || $code_type == 'controller') {
            $this->controller($table_name, $class_name, $columns);
        }
        if (empty($code_type) || $code_type == 'view') {
            $this->view($table_name, $class_name, $table_comment, $modal_type, $modal_column, $columns);
        }
        return json(array('status' => 1, "message" => "生成成功"));
    }
    private function model($table_name, $class_name)
    {
        $content = "<?php \r\n\r\n";
        $content .= "namespace app\\admin\\model; \r\n\r\n";
        $content .= "use think\\Model; \r\n\r\n";
        $content .= "class {$class_name} extends Model \r\n";
        $content .= "{ \r\n";
        $content .= "    protected \$pk = 'id'; \r\n";
        $content .= "    protected \$table = '{$table_name}'; \r\n";
        $content .= "\r\n";
        $content .= "    protected function base(\$query)\r\n";
        $content .= "    {\r\n";
        $content .= "        \$query->where('deleted', 0); \r\n";
        $content .= "    }\r\n";
        $content .= "}";
        $file = fopen("application\\admin\\model\\auto_generate\\{$class_name}.php", "w") or die("Unable to open file!");
        fwrite($file, $content);
        fclose($file);
    }
    private function controller($table_name, $class_name, $columns)
    {
        $column_str = '';
        $foreign_columns = [];
        foreach ($columns as $column) {
            $column_str .= $table_name . '.' . $column['COLUMN_NAME'] . ',';
            if (strpos($column['COLUMN_NAME'], '_id') !== false && !in_array($column['COLUMN_NAME'], ['created_user_id', 'updated_user_id', 'deleted_user_id'])) {
                $foreign_columns[] = $column['COLUMN_NAME'];
            }
        }
        $column_str = substr($column_str, 0, strlen($column_str) - 1);
        $content = "<?php\r\n";
        $content .= "\r\n";
        $content .= "namespace app\\admin\\controller;\r\n";
        $content .= "\r\n";
        $content .= "use app\\admin\\model\\" . $class_name . ";\r\n";
        $content .= "use app\\admin\\common\\Constant;\r\n";
        $content .= "use app\\admin\\common\\Cache;\r\n";
        $content .= "use app\\admin\\model\\User;\r\n";
        foreach ($foreign_columns as $column) {
            $foreign_table = str_replace('_id', '', $column);
            if ($foreign_table != 'parent') {
                $content .= "use app\\admin\\model\\" . ucwords($foreign_table) . ";\r\n";
            }
        }
        $content .= "\r\n";
        $content .= "class " . $class_name . "Controller extends BaseController\r\n";
        $content .= "{\r\n";
        $content .= "   public function index()\r\n";
        $content .= "   {\r\n";
        $content .= "       return view();\r\n";
        $content .= "   }\r\n";
        $content .= "\r\n";
        $content .= "   public function get_list()\r\n";
        $content .= "   {\r\n";
        $content .= "       \$start = \$this->request->param('start');\r\n";
        $content .= "       \$length = \$this->request->param('length');\r\n";
        $content .= "       \$map = \$this->process_query('id');\r\n";
        $content .= "       \$map['" . $table_name . ".deleted'] = '0';\r\n";
        $content .= "\r\n";
        $content .= "       \$order = 'id desc';\r\n";
        $content .= "       \$count = db('" . $table_name . "')->where(\$map)->count();\r\n";
        $content .= "       \$list = db('" . $table_name . "')->where(\$map)\r\n";
        $content .= "           ->field('" . $column_str . "')\r\n";
        $content .= "           ->limit(\$start, \$length)->order(\$order)->select();\r\n";
        $content .= "\r\n";
        $content .= "        \$user_list = User::column('name', 'id');\r\n";
        foreach ($foreign_columns as $column) {
            $foreign_table = str_replace('_id', '', $column);
            if ($foreign_table == 'parent') {
                $content .= "        \$" . $foreign_table . "_list = " . $class_name . "::column('name', 'id');\r\n";
            } else {
                $content .= "        \$" . $foreign_table . "_list = " . ucwords($foreign_table) . "::column('name', 'id');\r\n";
            }
        }
        $content .= "        foreach (\$list as \$key => \$item) {\r\n";
        foreach ($foreign_columns as $column) {
            $foreign_table = str_replace('_id', '', $column);
            $content .= "            \$list[\$key]['" . $column . "'] = get_value(\$item['" . $column . "'], \$" . $foreign_table . "_list );\r\n";
        }
        $content .= "            \$list[\$key]['created_user_id'] = get_value(\$item['created_user_id'], \$user_list);";
        $content .= "        }\r\n";
        $content .= "\r\n";
        $content .= "       return [\r\n";
        $content .= "           'draw' => \$this->request->param('draw'),\r\n";
        $content .= "           'recordsTotal' => \$count,\r\n";
        $content .= "           'recordsFiltered' => \$count,\r\n";
        $content .= "           'data' => \$list\r\n";
        $content .= "       ];\r\n";
        $content .= "   }\r\n";
        $content .= "\r\n";
        $content .= "   public function _item_maintain()\r\n";
        $content .= "   {\r\n";
        $content .= "       \$id = \$this->request->param('id');\r\n";
        $content .= "       \$model = null;\r\n";
        $content .= "       \$edit_state = false;\r\n";
        $content .= "       if (!empty(\$id)) {\r\n";
        $content .= "           \$model = " . $class_name . "::get(\$id);\r\n";
        $content .= "           \$edit_state = true;\r\n";
        $content .= "       }\r\n";
        $content .= "       \$this->assign('model', \$model);\r\n";
        $content .= "       \$this->assign('edit_state', \$edit_state);\r\n";
        foreach ($foreign_columns as $column) {
            $foreign_table = str_replace('_id', '', $column);
            if ($foreign_table == 'parent') {
                $content .= "       \$" . $foreign_table . "_list = " . $class_name . "::column('name', 'id');\r\n";
            } else {
                $content .= "       \$" . $foreign_table . "_list = " . ucwords($foreign_table) . "::column('name', 'id');\r\n";
            }
            $content .= "       \$this->assign('" . $foreign_table . "_list', \$" . $foreign_table . "_list);\r\n";
        }
        $content .= "       return view();\r\n";
        $content .= "   }\r\n";
        $content .= "\r\n";
        $content .= "   public function save()\r\n";
        $content .= "   {\r\n";
        $content .= "       \$data = input('post.');\r\n";
        $content .= "       if (\$this->is_exist([], \$data['id'])) {\r\n";
        $content .= "           return api_error('记录重复');\r\n";
        $content .= "       }\r\n";
        $content .= "       if (empty(\$data['id'])) {\r\n";
        $content .= "           \$model = new " . $class_name . " ();\r\n";
        $content .= "           \$data['deleted'] = 0;\r\n";
        $content .= "           \$data['created_user_id'] = \$this->userId;\r\n";
        $content .= "           \$data['created_time'] = date('Y-m-d H:i:s');\r\n";
        $content .= "       } else {\r\n";
        $content .= "           \$model = " . $class_name . "::get(\$data['id']);\r\n";
        $content .= "           if (empty(\$model)) {\r\n";
        $content .= "               return api_error('记录不存在');\r\n";
        $content .= "           }\r\n";
        $content .= "           \$data['updated_time'] = date('Y-m-d H:i:s');\r\n";
        $content .= "           \$data['updated_user_id'] = \$this->userId;\r\n";
        $content .= "       }\r\n";
        $content .= "       \$model->data(\$data);\r\n";
        $content .= "       \$model->save();\r\n";
        $content .= "       return api_success('保存成功');\r\n";
        $content .= "   }\r\n";
        $content .= "\r\n";
        $content .= "   public function delete()\r\n";
        $content .= "   {\r\n";
        $content .= "       \$id = \$this->request->param('id');\r\n";
        $content .= "       \$model = " . $class_name . "::get(\$id);\r\n";
        $content .= "       if (empty(\$model)) {\r\n";
        $content .= "               return api_error('记录不存在');\r\n";
        $content .= "       }\r\n";
        $content .= "       \$model->deleted = 1;\r\n";
        $content .= "       \$model->deleted_user_id = \$this->userId;\r\n";
        $content .= "       \$model->deleted_time = date('Y-m-d H:i:s');\r\n";
        $content .= "       \$model->save();\r\n";
        $content .= "       return api_success('删除成功');\r\n";
        $content .= "   }\r\n";
        $content .= "\r\n";
        $content .= "   private function is_exist(\$map, \$id = '')\r\n";
        $content .= "   {\r\n";
        $content .= "       if (empty(\$map)) {\r\n";
        $content .= "           return false;\r\n";
        $content .= "       }\r\n";
        $content .= "       if (!empty(\$id)) {\r\n";
        $content .= "           \$map['id'] = array('<>', \$id);\r\n";
        $content .= "       }\r\n";
        $content .= "       \$model = " . $class_name . "::where(\$map)->find();\r\n";
        $content .= "       if (!empty(\$model)) {\r\n";
        $content .= "           return true;\r\n";
        $content .= "       }\r\n";
        $content .= "       return false;\r\n";
        $content .= "   }\r\n";
        $content .= "}\r\n";
        $file = fopen('application\\admin\\controller\\auto_generate\\' . $class_name . 'Controller.php', "w") or die("Unable to open file!");
        fwrite($file, $content);
        fclose($file);
    }
    private function view($table_name, $class_name, $table_comment, $modal_type, $modal_column, $columns)
    {
        $content = "<extend name='public/base'/>\r\n";
        $content .= "\r\n";
        $content .= "<block name='title'>" . $table_comment . "管理</block>\r\n";
        $content .= "<block name='content'>\r\n";
        $content .= "    <div class='row wrapper border-bottom white-bg page-heading'>\r\n";
        $content .= "        <div class='col-lg-10'>\r\n";
        $content .= "            <h2>" . $table_comment . "管理</h2>\r\n";
        $content .= "            <ol class='breadcrumb'>\r\n";
        $content .= "                <li>\r\n";
        $content .= "                    <a>首页</a>\r\n";
        $content .= "                </li>\r\n";
        $content .= "                <li>\r\n";
        $content .= "                    <a></a>\r\n";
        $content .= "                </li>\r\n";
        $content .= "                <li class='active'>\r\n";
        $content .= "                    <strong>" . $table_comment . "管理</strong>\r\n";
        $content .= "                </li>\r\n";
        $content .= "            </ol>\r\n";
        $content .= "        </div>\r\n";
        $content .= "        <div class='col-lg-2'>\r\n";
        $content .= "            <div class='text-right'>\r\n";
        $content .= "                <a data-toggle='modal' data-target='#modal-" . $modal_type . "' href='_item_maintain'\r\n";
        $content .= "                   class='btn btn-primary'>新增</a>\r\n";
        $content .= "            </div>\r\n";
        $content .= "        </div>\r\n";
        $content .= "    </div>\r\n";
        $content .= "    <div class='wrapper wrapper-content animated fadeInRight'>\r\n";
        $content .= "        <div class='row'>\r\n";
        $content .= "            <div class='col-lg-12'>\r\n";
        $content .= "                <div class='ibox float-e-margins'>\r\n";
        $content .= "                    <div class='ibox-title'>\r\n";
        $content .= "                        <h5>" . $table_comment . "列表</h5>\r\n";
        $content .= "                    </div>\r\n";
        $content .= "                    <div class='ibox-content'>\r\n";
        $content .= "                       <div class='row'>\r\n";
        $content .= "                              <div class='col-sm-3'>\r\n";
        $content .= "                                <div class='form-group'>\r\n";
        $content .= "                                    <div class='input-group m-b'>\r\n";
        $content .= "                                        <input type='text' class='form-control' id='search_keyword' placeholder='关键字'>\r\n";
        $content .= "                                        <span class='input-group-btn'>\r\n";
        $content .= "                                            <button type='button' id='search-button' class='btn btn-primary'>查询</button> </span>\r\n";
        $content .= "                                    </div>\r\n";
        $content .= "                                </div>\r\n";
        $content .= "                            </div>\r\n";
        $content .= "                        </div>\r\n";
        $content .= "                        <div class='table-responsive'>\r\n";
        $content .= "                            <table id='data-table' style='width:100%;'\r\n";
        $content .= "                                   class='table table-striped table-hover dataTables-example dataTable border-bottom' role='grid'>\r\n";
        $content .= "                                <thead>\r\n";
        $content .= "                                <tr role='row'>\r\n";
        foreach ($columns as $column) {
            if (in_array($column['COLUMN_NAME'], ['id', 'updated_user_id', 'updated_time', 'deleted', 'deleted_user_id', 'deleted_time'])) {
                continue;
            }
            $content .= "                                    <th>" . $column['COLUMN_COMMENT'] . "</th>\r\n";
        }
        $content .= "                                    <th width='100px'></th>\r\n";
        $content .= "                                </tr>\r\n";
        $content .= "                                </thead>\r\n";
        $content .= "                                <tbody>\r\n";
        $content .= "                                </tbody>\r\n";
        $content .= "                            </table>\r\n";
        $content .= "                        </div>\r\n";
        $content .= "                    </div>\r\n";
        $content .= "                </div>\r\n";
        $content .= "            </div>\r\n";
        $content .= "        </div>\r\n";
        $content .= "    </div>\r\n";
        $content .= "</block>\r\n";
        $content .= "<block name='script'>\r\n";
        $content .= "    <script type='text/javascript'>\r\n";
        $content .= "        \$(document).ready(function () {\r\n";
        $content .= "            var query = function (params) {\r\n";
        $content .= "                params.query = \$('#search_keyword').val();\r\n";
        $content .= "            };\r\n";
        $content .= "            var columns = [\r\n";
        foreach ($columns as $column) {
            if (in_array($column['COLUMN_NAME'], ['id', 'updated_user_id', 'updated_time', 'deleted', 'deleted_user_id', 'deleted_time'])) {
                continue;
            }
            $content .= "                {'data': '" . $column['COLUMN_NAME'] . "'},\r\n";
        }
        $content .= "                {\r\n";
        $content .= "                    'data': null,\r\n";
        $content .= "                    'render': function (data) {\r\n";
        $content .= "                        return get_table_action(data.id,'" . $modal_type . "');\r\n";
        $content .= "                    }\r\n";
        $content .= "                }\r\n";
        $content .= "            ];\r\n";
        $content .= "            load_list(query, columns);\r\n";
        $content .= "        });\r\n";
        $content .= "    </script>\r\n";
        $content .= "</block>\r\n";
        $dir = "application\\admin\\view\\auto_generate\\" . $table_name;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $file = fopen($dir . "\\index.html", "w") or die("Unable to open file!");
        fwrite($file, $content);
        fclose($file);
        $content = "<div class='modal-header'>\r\n";
        $content .= "    {\$edit_state? '修改" . $table_comment . "' : '新增" . $table_comment . "'}\r\n";
        $content .= "</div>\r\n";
        $content .= "<div class='modal-body '>\r\n";
        $content .= "    <form class='' role='form' id='form'>\r\n";
        $content .= "        <input type='hidden' name='id' value='{\$model.id|default=\"\"}' />\r\n";
        $content .= "        <div class='row'>\r\n";
        foreach ($columns as $column) {
            if (in_array($column['COLUMN_NAME'], ['id', 'created_user_id', 'created_time', 'updated_user_id', 'updated_time', 'deleted', 'deleted_user_id', 'deleted_time'])) {
                continue;
            }
            $content .= "            <div class='col-sm-{$modal_column}'>\r\n";
            $content .= "                <div class='form-group'>\r\n";
            $content .= "                    <label>" . $column['COLUMN_COMMENT'] . "</label>\r\n";
            if (strpos($column['COLUMN_NAME'], '_id') !== false && !in_array($column['COLUMN_NAME'], ['created_user_id', 'updated_user_id', 'deleted_user_id'])) {
                $foreign_table = str_replace('_id', '', $column['COLUMN_NAME']);
                $content .= "                    <p><html:select id='" . $column['COLUMN_NAME'] . "' name='" . $column["COLUMN_NAME"] . "' data='" . $foreign_table . "_list' class='form-control' selected='model[\"" . $column['COLUMN_NAME'] . "\"]" . "' first='请选择'/></p>\r\n";
            } else {
                $content .= "                    <p><input type='text' class='form-control' id='" . $column['COLUMN_NAME'] . "' name='" . $column['COLUMN_NAME'] . "' value='{\$model." . $column['COLUMN_NAME'] . "|default=\"\"}' autocomplete='off'/></p>\r\n";
            }
            $content .= "                </div>\r\n";
            $content .= "            </div>\r\n";
        }
        $content .= "        </div>\r\n";
        $content .= "    </form>\r\n";
        $content .= "</div>\r\n";
        $content .= "<div class='modal-footer'>\r\n";
        $content .= "    <button type='button' class='btn btn-default' data-dismiss='modal'>取消</button>\r\n";
        $content .= "    <button type='button' class='btn btn-primary' data-style='zoom-in' id='submit'>提交</button>\r\n";
        $content .= "</div>\r\n";
        $content .= "\r\n";
        $content .= "<script type='text/javascript'>\r\n";
        $content .= "    \$(document).ready(function () {\r\n";
        $content .= "        function validate(){\r\n";
        $content .= "            return \$('#form').validate({\r\n";
        $content .= "                rules: {\r\n";
        $content .= "                    key: {\r\n";
        $content .= "                        required: true,\r\n";
        $content .= "                    }\r\n";
        $content .= "                },\r\n";
        $content .= "            }).form();\r\n";
        $content .= "        }\r\n";
        $content .= "        \$('#submit').click(function () {\r\n";
        $content .= "            if (validate()) {\r\n";
        $content .= "                var load_btn = Ladda.create(this);\r\n";
        $content .= "                load_btn.start();\r\n";
        $content .= "                var data = \$('#form').serialize();\r\n";
        $content .= "                \$.ajax({\r\n";
        $content .= "                    type: 'POST',\r\n";
        $content .= "                    url: 'save',\r\n";
        $content .= "                    data: data,\r\n";
        $content .= "                    success: function (data) {\r\n";
        $content .= "                        if (check_status(data)){\r\n";
        $content .= "                            \$('#modal-{$modal_type}').modal('hide');\r\n";
        $content .= "                            data_table.draw();\r\n";
        $content .= "                        }\r\n";
        $content .= "                    },\r\n";
        $content .= "                    error: function () {\r\n";
        $content .= "                        toastr['error']('Error Occurred');\r\n";
        $content .= "                    }\r\n";
        $content .= "                }).always(function () {\r\n";
        $content .= "                    load_btn.stop();\r\n";
        $content .= "                });\r\n";
        $content .= "            }\r\n";
        $content .= "        });\r\n";
        $content .= "    });\r\n";
        $content .= "</script>\r\n";
        $file = fopen($dir . "\\_item_maintain.html", "w") or die("Unable to open file!");
        fwrite($file, $content);
        fclose($file);
    }
}