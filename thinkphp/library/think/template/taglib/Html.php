<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace think\template\taglib;

use think\template\TagLib;

/**
 * Html标签库驱动
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Taglib
 * @author    liu21st <liu21st@gmail.com>
 */
class Html extends TagLib
{
    // 标签定义
    protected $tags = array(
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'select' => array('attr' => 'name,data,data-value,data-key,data-parent,class,multiple,id,size,first,selected,selected-parent', 'close' => 0),
        'checkbox' => array('attr' => 'name,data,data-value,data-key,class,id,checked', 'close' => 0),
        'radio' => array('attr' => 'name,data,data-value,data-key,class,id,checked', 'close' => 0),
        'pager' => array('attr' => 'total,index', 'close' => 0)
    );

    public function tagselect($tag, $content)
    {
        $name = isset($tag['name']) ? $tag['name'] : '';
        $id = isset($tag['id']) ? $tag['id'] : '';
        $data = isset($tag['data']) ? $tag['data'] : '';
        $data_key = isset($tag['data-key']) ? $tag['data-key'] : '';
        $data_value = isset($tag['data-value']) ? $tag['data-value'] : '';
        $data_parent = isset($tag['data-parent']) ? $tag['data-parent'] : '';
        $multiple = isset($tag['multiple']) ? $tag['multiple'] : '';
        $size = isset($tag['size']) ? $tag['size'] : '';
        $first = isset($tag['first']) ? $tag['first'] : '';
        $selected = isset($tag['selected']) ? $tag['selected'] : '';
        $selected_parent = isset($tag['selected_parent']) ? $tag['selected_parent'] : '';
        $class = isset($tag['class']) ? $tag['class'] : '';
        '';
        if (!empty($multiple)) {
            $parseStr = '<select id="' . $id . '" name="' . $name . '" multiple="multiple" class="' . $class . '" size="' . $size . '" >';
        } else {
            $parseStr = '<select id="' . $id . '" name="' . $name . '" class="' . $class . '" >';
        }
        $parent_str = '';
        if (!empty($data_parent)) {
            $hidden_option = empty($selected_parent) ? 'hidden' : '<?php if(empty($' . $selected_parent . ') || $' . $selected_parent . ' != $val["' . $data_parent . '"]){echo "hidden";} ?>';
            $parent_str = ' class="' . $hidden_option . '" data-parent="<?php echo $val["' . $data_parent . '"] ?>" ';
        }
        if (!empty($first)) {
            $parseStr .= '<option value="" >' . $first . '</option>';
        }
        if (!empty($data)) {
            if (!empty($data_key)) {
                $parseStr .= '<?php  foreach($' . $data . ' as $val) { ?>';
                if (!empty($selected)) {
                    $parseStr .= '<?php if(!empty($' . $selected . ') && ($' . $selected . ' == $val["' . $data_value . '"] || in_array($val["' . $data_value . '"],explode(",",$' . $selected . ')))) { ?>';
                    $parseStr .= '<option selected="selected" ' . $parent_str . '  value="<?php echo $val["' . $data_value . '"] ?>"><?php echo $val["' . $data_key . '"] ?></option>';
                    $parseStr .= '<?php }else { ?><option ' . $parent_str . ' value="<?php echo $val["' . $data_value . '"] ?>"><?php echo $val["' . $data_key . '"] ?></option>';
                    $parseStr .= '<?php } ?>';
                } else {
                    $parseStr .= '<option ' . $parent_str . '  value="<?php echo $val["' . $data_value . '"] ?>"><?php echo $val["' . $data_key . '"] ?></option>';
                }
                $parseStr .= '<?php } ?>';
            } else {
                $parseStr .= '<?php  foreach($' . $data . ' as $key=>$val) { ?>';
                if (!empty($selected)) {
                    $parseStr .= '<?php if(!empty($' . $selected . ') && ($' . $selected . ' == $key || in_array($key,explode(",",$' . $selected . ')))) { ?>';
                    $parseStr .= '<option selected="selected" value="<?php echo $key ?>"><?php echo $val ?></option>';
                    $parseStr .= '<?php }else { ?><option value="<?php echo $key ?>"><?php echo $val ?></option>';
                    $parseStr .= '<?php } ?>';
                } else {
                    $parseStr .= '<option value="<?php echo $key ?>"><?php echo $val ?></option>';
                }
                $parseStr .= '<?php } ?>';

            }
        }
        $parseStr .= '</select>';
        return $parseStr;
    }

    public function tagpager($tag, $content)
    {
        $total = isset($tag['total']) ? $tag['total'] : 1;
        $index = isset($tag['index']) ? $tag['index'] : 1;

        $parse_str = '<div class="pager">
                    <div class="pager-container">
                        <?php
                          $page_total = ' . $total . ';
                          $page_index = ' . $index . ';
                          $page_count = $page_total>7 ? 7 : $page_total;
                          if($page_total == 0){
                          ?>
                          <span style="font-size: 20px;color:#bbb;">未找到相关记录</span>
                          <?php
                          }
                          else
                          {
                          ?>
                        <a href="?page=<?php echo $page_index-1==0?1:$page_index-1?>"><</a>
                        <?php
                          for($i=1;$i<=$page_count;$i++){
                            $page_active= $page_index==$i?"active":"";
                            if($page_total <= 7){?><a href="?page=<?php echo $i?>" class="<?php echo $page_active?>"><?php echo $i?></a><?php }
                            else if( $page_index <= 5){
                                if($i==7){?><a href="?page=<?php echo $page_total?>" class=""><?php echo $page_total?></a><?php }
                                elseif ($i==6){?><a href="javascript:;" class="disabled">...</a><?php }
                                else{?><a href="?page=<?php echo $i?>" class="<?php echo $page_active?>"><?php echo $i?></a><?php }
                            }
                            else if( $page_index >= ($page_total - 4)){
                                $page_active= $page_index==($page_total - (7 - $i))?"active":"";
                                if($i==1){?><a href="?page=1" class="">1</a><?php }
                                elseif ($i==2){?><a href="javascript:;" class="disabled">...</a><?php }
                                else{?><a href="?page=<?php echo $page_total - (7 - $i)?>" class="<?php echo $page_active?>"><?php echo $page_total - (7 - $i)?></a><?php }
                             }
                            else {
                                ?><a href="?page=1" class="">1</a><a href="javascript:;" class="disabled">...</a><a href="?page=<?php echo $page_index-1?>" class=""><?php echo $page_index-1?></a><a href="?page=<?php echo $page_index?>" class="active"><?php echo $page_index?></a><a href="?page=<?php echo $page_index+1?>" class=""><?php echo $page_index+1?></a><a href="javascript:;" class="disabled">...</a><a href="?page=<?php echo $page_total?>" class=""><?php echo $page_total?></a><?php
                                break;
                             }
                          }?>

                        <a href="?page=<?php echo $page_index+1>$page_total?$page_total:$page_index+1?>">></a>
                        <div class="clear"></div>
                        <?php }?>
                    </div>
                </div>';

        return $parse_str;
    }

    public function tagcheckbox($tag, $content)
    {
        $name = $tag['name'];
        $data = isset($tag['data']) ? $tag['data'] : '';
        $data_key = isset($tag['data-key']) ? $tag['data-key'] : '';
        $data_value = isset($tag['data-value']) ? $tag['data-value'] : '';
        $checked = isset($tag['checked']) ? $tag['checked'] : '';
        $class = isset($tag['class']) ? $tag['class'] : '';


        $parseStr = '';
        if (!empty($data)) {
            if (!empty($data_key)) {
                $parseStr .= '<?php  foreach($' . $data . ' as $val) { ?>';
                if (!empty($checked)) {
                    $parseStr .= '<?php if(!empty($' . $checked . ') && ($' . $checked . ' == $val["' . $data_value . '"] || in_array($val["' . $data_value . '"],explode(",",$' . $checked . ')))) { ?>';
                    $parseStr .= '<label class="' . $class . '"><input checked="checked" type="checkbox" name="' . $name . '" value="<?php echo $val["' . $data_value . '"] ?>"> <?php echo $val["' . $data_key . '"] ?></label>';
                    $parseStr .= '<?php }else { ?><label class="' . $class . '"><input type="checkbox" name="' . $name . '" value="<?php echo $val["' . $data_value . '"] ?>"> <?php echo $val["' . $data_key . '"] ?></label>';
                    $parseStr .= '<?php } ?>';
                } else {
                    $parseStr .= '<label class="' . $class . '"><input type="checkbox" name="' . $name . '" value="<?php echo $val["' . $data_value . '"] ?>"> <?php echo $val["' . $data_key . '"] ?></label>';
                }
                $parseStr .= '<?php } ?>';
            } else {
                $parseStr .= '<?php  foreach($' . $data . ' as $key=>$val) { ?>';
                if (!empty($checked)) {
                    $parseStr .= '<?php if(!empty($' . $checked . ') && ($' . $checked . ' == $key || in_array($key,explode(",",$' . $checked . ')))) { ?>';
                    $parseStr .= '<label class="' . $class . '"><input checked="checked" type="checkbox" name="' . $name . '" value="<?php echo $key ?>"> <?php echo $val ?></label>';
                    $parseStr .= '<?php }else { ?><label class="' . $class . '"><input type="checkbox" name="' . $name . '" value="<?php echo $key ?>"> <?php echo $val ?></label>';
                    $parseStr .= '<?php } ?>';
                } else {
                    $parseStr .= '<label class="' . $class . '"><input type="checkbox" name="' . $name . '" value="<?php echo $key ?>"> <?php echo $val ?></label>';
                }
                $parseStr .= '<?php } ?>';

            }
        }
        return $parseStr;
    }

    public function tagradio($tag, $content)
    {
        $name = $tag['name'];
        $data = isset($tag['data']) ? $tag['data'] : '';
        $data_key = isset($tag['data-key']) ? $tag['data-key'] : '';
        $data_value = isset($tag['data-value']) ? $tag['data-value'] : '';
        $checked = isset($tag['checked']) ? $tag['checked'] : '';
        $class = isset($tag['class']) ? $tag['class'] : '';

        $parseStr = '';
        if (!empty($data)) {
            if (!empty($data_key)) {
                $parseStr .= '<?php $default=false; foreach($' . $data . ' as $val) { ?>';
                if (!empty($checked)) {
                    $parseStr .= '<?php if(empty($' . $checked . ') && $' . $checked . '!==0 &&!$default) { ?>';
                    $parseStr .= '<label class="' . $class . '"><input type="radio" checked="checked" name="' . $name . '" value="<?php echo $val["' . $data_value . '"] ?>"> <?php echo $val["' . $data_key . '"] ?></label>';
                    $parseStr .= '<?php $default=true;}elseif((!empty($' . $checked . ') ||  $' . $checked . '===0 )&& ($' . $checked . ' == $val["' . $data_value . '"] || in_array($val["' . $data_value . '"],explode(",",$' . $checked . ')))) { ?>';
                    $parseStr .= '<label class="' . $class . '"><input checked="checked" type="radio" name="' . $name . '" value="<?php echo $val["' . $data_value . '"] ?>"> <?php echo $val["' . $data_key . '"] ?></label>';
                    $parseStr .= '<?php }else { ?><label class="' . $class . '"><input type="radio" name="' . $name . '" value="<?php echo $val["' . $data_value . '"] ?>"> <?php echo $val["' . $data_key . '"] ?></label>';
                    $parseStr .= '<?php } ?>';
                } else {
                    $parseStr .= '<?php if(!$default) { ?>';
                    $parseStr .= '<label class="' . $class . '"><input type="radio" checked="checked" name="' . $name . '" value="<?php echo $val["' . $data_value . '"] ?>"> <?php echo $val["' . $data_key . '"] ?></label>';
                    $parseStr .= '<?php $default=true;}else { ?><label class="' . $class . '"><input type="radio" name="' . $name . '" value="<?php echo $val["' . $data_value . '"] ?>"> <?php echo $val["' . $data_key . '"] ?></label>';
                    $parseStr .= '<?php } ?>';
                }
                $parseStr .= '<?php } ?>';
            } else {
                $parseStr .= '<?php $default=false; foreach($' . $data . ' as $key=>$val) { ?>';
                if (!empty($checked)) {
                    $parseStr .= '<?php if(empty($' . $checked . ') && $' . $checked . '!==0 &&!$default) { ?>';
                    $parseStr .= '<label class="' . $class . '"><input type="radio" checked="checked" name="' . $name . '" value="<?php echo $key ?>"> <?php echo $val ?></label>';
                    $parseStr .= '<?php $default=true;}elseif((!empty($' . $checked . ')||  $' . $checked . '===0 ) && ($' . $checked . ' == $key || in_array($key,explode(",",$' . $checked . ')))) { ?>';
                    $parseStr .= '<label class="' . $class . '"><input checked="checked" type="radio" name="' . $name . '" value="<?php echo $key ?>"> <?php echo $val ?></label>';
                    $parseStr .= '<?php }else { ?><label class="' . $class . '"><input type="radio" name="' . $name . '" value="<?php echo $key ?>"> <?php echo $val ?></label>';
                    $parseStr .= '<?php } ?>';
                } else {
                    $parseStr .= '<?php if(!$default) { ?>';
                    $parseStr .= '<label class="' . $class . '"><input type="radio" checked="checked" name="' . $name . '" value="<?php echo $key ?>"> <?php echo $val ?></label>';
                    $parseStr .= '<?php $default=true;}else { ?><label class="' . $class . '"><input type="radio" name="' . $name . '" value="<?php echo $key ?>"> <?php echo $val ?></label>';
                    $parseStr .= '<?php } ?>';
                }
                $parseStr .= '<?php } ?>';

            }
        }
        return $parseStr;
    }

    public function _editor($attr, $content)
    {
        $tag = $this->parseXmlAttr($attr, 'editor');
        $id = !empty($tag['id']) ? $tag['id'] : '_editor';
        $name = $tag['name'];
        $style = !empty($tag['style']) ? $tag['style'] : '';
        $width = !empty($tag['width']) ? $tag['width'] : '100%';
        $height = !empty($tag['height']) ? $tag['height'] : '320px';
        //   $content    =   $tag['content'];
        $type = $tag['type'];
        switch (strtoupper($type)) {
            case 'FCKEDITOR':
                $parseStr = '<!-- 编辑器调用开始 --><script type="text/javascript" src="__ROOT__/Public/Js/FCKeditor/fckeditor.js"></script><textarea id="' . $id . '" name="' . $name . '">' . $content . '</textarea><script type="text/javascript"> var oFCKeditor = new FCKeditor( "' . $id . '","' . $width . '","' . $height . '" ) ; oFCKeditor.BasePath = "__ROOT__/Public/Js/FCKeditor/" ; oFCKeditor.ReplaceTextarea() ;function resetEditor(){setContents("' . $id . '",document.getElementById("' . $id . '").value)}; function saveEditor(){document.getElementById("' . $id . '").value = getContents("' . $id . '");} function InsertHTML(html){ var oEditor = FCKeditorAPI.GetInstance("' . $id . '") ;if (oEditor.EditMode == FCK_EDITMODE_WYSIWYG ){oEditor.InsertHtml(html) ;}else	alert( "FCK必须处于WYSIWYG模式!" ) ;}</script> <!-- 编辑器调用结束 -->';
                break;
            case 'FCKMINI':
                $parseStr = '<!-- 编辑器调用开始 --><script type="text/javascript" src="__ROOT__/Public/Js/FCKMini/fckeditor.js"></script><textarea id="' . $id . '" name="' . $name . '">' . $content . '</textarea><script type="text/javascript"> var oFCKeditor = new FCKeditor( "' . $id . '","' . $width . '","' . $height . '" ) ; oFCKeditor.BasePath = "__ROOT__/Public/Js/FCKMini/" ; oFCKeditor.ReplaceTextarea() ;function resetEditor(){setContents("' . $id . '",document.getElementById("' . $id . '").value)}; function saveEditor(){document.getElementById("' . $id . '").value = getContents("' . $id . '");} function InsertHTML(html){ var oEditor = FCKeditorAPI.GetInstance("' . $id . '") ;if (oEditor.EditMode == FCK_EDITMODE_WYSIWYG ){oEditor.InsertHtml(html) ;}else	alert( "FCK必须处于WYSIWYG模式!" ) ;}</script> <!-- 编辑器调用结束 -->';
                break;
            case 'EWEBEDITOR':
                $parseStr = "<!-- 编辑器调用开始 --><script type='text/javascript' src='__ROOT__/Public/Js/eWebEditor/js/edit.js'></script><input type='hidden'  id='{$id}' name='{$name}'  value='{$conent}'><iframe src='__ROOT__/Public/Js/eWebEditor/ewebeditor.htm?id={$name}' frameborder=0 scrolling=no width='{$width}' height='{$height}'></iframe><script type='text/javascript'>function saveEditor(){document.getElementById('{$id}').value = getHTML();} </script><!-- 编辑器调用结束 -->";
                break;
            case 'NETEASE':
                $parseStr = '<!-- 编辑器调用开始 --><textarea id="' . $id . '" name="' . $name . '" style="display:none">' . $content . '</textarea><iframe ID="Editor" name="Editor" src="__ROOT__/Public/Js/HtmlEditor/index.html?ID=' . $name . '" frameBorder="0" marginHeight="0" marginWidth="0" scrolling="No" style="height:' . $height . ';width:' . $width . '"></iframe><!-- 编辑器调用结束 -->';
                break;
            case 'UBB':
                $parseStr = '<script type="text/javascript" src="__ROOT__/Public/Js/UbbEditor.js"></script><div style="padding:1px;width:' . $width . ';border:1px solid silver;float:left;"><script LANGUAGE="JavaScript"> showTool(); </script></div><div><TEXTAREA id="UBBEditor" name="' . $name . '"  style="clear:both;float:none;width:' . $width . ';height:' . $height . '" >' . $content . '</TEXTAREA></div><div style="padding:1px;width:' . $width . ';border:1px solid silver;float:left;"><script LANGUAGE="JavaScript">showEmot();  </script></div>';
                break;
            case 'KINDEDITOR':
                $parseStr = '<script type="text/javascript" src="__ROOT__/Public/Js/KindEditor/kindeditor.js"></script><script type="text/javascript"> KE.show({ id : \'' . $id . '\'  ,urlType : "absolute"});</script><textarea id="' . $id . '" style="' . $style . '" name="' . $name . '" >' . $content . '</textarea>';
                break;
            default :
                $parseStr = '<textarea id="' . $id . '" style="' . $style . '" name="' . $name . '" >' . $content . '</textarea>';
        }

        return $parseStr;
    }

}