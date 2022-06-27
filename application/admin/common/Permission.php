<?php

namespace app\admin\common;

class Permission
{
    public static function get_list()
    {
        return [['id' => 'system', 'text' => '系统', 'children' => [['id' => 'system.index', 'text' => '系统首页', 'menu_icon' => 'home'], ['id' => 'system.agent', 'text' => '代理管理', 'menu_icon' => 'handshake-o'], ['id' => 'system.payment', 'text' => '代理充值记录', 'menu_icon' => 'money'], ['id' => 'system.client', 'text' => '客户管理', 'menu_icon' => 'user'], ['id' => 'system.block8', 'text' => '客户消费记录', 'menu_icon' => 'user', 'children' => [['id' => 'system.client_cost', 'text' => '客户消费记录', 'menu_icon' => 'line-chart'], ['id' => 'system.client_cost_detail', 'text' => '客户消费明细', 'menu_icon' => 'bar-chart']]], ['id' => 'system.payment_client', 'text' => '客户充值记录', 'menu_icon' => 'money'], ['id' => 'system.block0', 'text' => '搜索推广设置', 'menu_icon' => 'th-list', 'children' => [['id' => 'system.unit_cpc', 'text' => '单元管理'], ['id' => 'system.creativity_cpc', 'text' => '创意管理', 'menu_icon' => 'list'], ['id' => 'system.keyword', 'text' => '关键词管理', 'menu_icon' => 'list'], ['id' => 'system.keyword_bid', 'text' => '关键词出价'], ['id' => 'system.negative_word_cpc', 'text' => '否定词设置'], ['id' => 'system.target_area_cpc', 'text' => '投放区域'], ['id' => 'system.promote_setting_cpc', 'text' => '投放设置']]], ['id' => 'system.block1', 'text' => '搜索推广报告', 'menu_icon' => 'bar-chart', 'children' => [['id' => 'system.report_key_cpc', 'text' => '关键词报告', 'menu_icon' => 'line-chart'], ['id' => 'system.report_plan_cpc', 'text' => '消费报告', 'menu_icon' => 'bar-chart']]], ['id' => 'system.remind_cpc', 'text' => '搜索推广消息', 'menu_icon' => 'info-circle'], ['id' => 'system.block2', 'text' => '爱采购设置', 'menu_icon' => 'list-ol', 'children' => [['id' => 'system.unit_b2b', 'text' => '单元管理'], ['id' => 'system.unit_bid_b2b', 'text' => '单元出价'], ['id' => 'system.product', 'text' => '商品管理', 'menu_icon' => 'list'], ['id' => 'system.join_product', 'text' => '加盟星管理', 'menu_icon' => 'list'], ['id' => 'system.negative_word_b2b', 'text' => '否定词设置'], ['id' => 'system.target_area_b2b', 'text' => '投放区域'], ['id' => 'system.promote_setting_b2b', 'text' => '投放设置']]], ['id' => 'system.block3', 'text' => '爱采购报告', 'menu_icon' => 'line-chart', 'children' => [['id' => 'system.report_key_b2b', 'text' => '搜索词报告', 'menu_icon' => 'line-chart'], ['id' => 'system.report_plan_b2b', 'text' => '消费报告', 'menu_icon' => 'bar-chart']]], ['id' => 'system.remind_b2b', 'text' => '爱采购消息', 'menu_icon' => 'info-circle'], ['id' => 'system.block4', 'text' => '巨量推广设置', 'menu_icon' => 'th-list', 'children' => [['id' => 'system.unit_oce', 'text' => '单元管理（计划）'], ['id' => 'system.unit_bid_oce', 'text' => '单元出价'], ['id' => 'system.creativity_oce', 'text' => '创意管理', 'menu_icon' => 'list'], ['id' => 'system.keyword_oce', 'text' => '关键词管理', 'menu_icon' => 'list'], ['id' => 'system.negative_word_oce', 'text' => '否定词设置'], ['id' => 'system.target_area_oce', 'text' => '投放区域'], ['id' => 'system.promote_setting_oce', 'text' => '投放设置'], ['id' => 'system.setting_oce', 'text' => '授权设置']]], ['id' => 'system.block5', 'text' => '巨量推广报告', 'menu_icon' => 'bar-chart', 'children' => [['id' => 'system.report_plan_oce', 'text' => '消费报告', 'menu_icon' => 'bar-chart']]], ['id' => 'system.remind_oce', 'text' => '巨量推广消息', 'menu_icon' => 'info-circle'], ['id' => 'system.block6', 'text' => '360推广设置', 'menu_icon' => 'th-list', 'children' => [['id' => 'system.plan_360', 'text' => '计划管理'], ['id' => 'system.unit_360', 'text' => '单元管理'], ['id' => 'system.creativity_360', 'text' => '创意管理', 'menu_icon' => 'list'], ['id' => 'system.keyword_360', 'text' => '关键词管理', 'menu_icon' => 'list'], ['id' => 'system.keyword_bid_360', 'text' => '关键词出价'], ['id' => 'system.negative_word_360', 'text' => '否定词设置'], ['id' => 'system.target_area_360', 'text' => '投放区域'], ['id' => 'system.promote_setting_360', 'text' => '投放设置']]], ['id' => 'system.block7', 'text' => '360推广报告', 'menu_icon' => 'bar-chart', 'children' => [['id' => 'system.report_key_360', 'text' => '关键词报告', 'menu_icon' => 'line-chart'], ['id' => 'system.report_plan_360', 'text' => '消费报告', 'menu_icon' => 'bar-chart']]], ['id' => 'system.remind_360', 'text' => '360推广消息', 'menu_icon' => 'info-circle'], ['id' => 'system.block9', 'text' => '腾讯推广设置', 'menu_icon' => 'th-list', 'children' => [['id' => 'system.unit_qq', 'text' => '单元管理（广告组）'], ['id' => 'system.unit_bid_qq', 'text' => '单元出价'], ['id' => 'system.creativity_qq', 'text' => '创意管理', 'menu_icon' => 'list'], ['id' => 'system.keyword_qq', 'text' => '关键词管理', 'menu_icon' => 'list'], ['id' => 'system.negative_word_qq', 'text' => '否定词设置'], ['id' => 'system.target_area_qq', 'text' => '投放区域'], ['id' => 'system.promote_setting_qq', 'text' => '投放设置'], ['id' => 'system.setting_qq', 'text' => '授权设置']]], ['id' => 'system.block10', 'text' => '腾讯推广报告', 'menu_icon' => 'bar-chart', 'children' => [['id' => 'system.report_plan_qq', 'text' => '消费报告', 'menu_icon' => 'bar-chart']]], ['id' => 'system.remind_qq', 'text' => '腾讯推广消息', 'menu_icon' => 'info-circle'], ['id' => 'system.plugin', 'text' => '插件中心', 'menu_icon' => 'list-ol'], ['id' => 'system.block8', 'text' => '系统设置', 'menu_icon' => 'gears', 'children' => [['id' => 'system.user', 'text' => '用户管理'], ['id' => 'system.role', 'text' => '角色管理'], ['id' => 'system.notice', 'text' => '公告管理'], ['id' => 'system.setting', 'text' => '配置管理']]]]]];
    }
    public static function get_menus($is_admin, $permissions)
    {
        $menus = [];
        if ($is_admin) {
        }
        $permission_list = self::get_list();
        foreach ($permission_list[0]['children'] as $permission) {
            if (!empty($permission['children'])) {
                $menu = ['', $permission['text'], $permission['menu_icon']];
                foreach ($permission['children'] as $child_permission) {
                    if ($is_admin || in_array($child_permission['id'], $permissions)) {
                        $menu['children'][] = [str_replace('system.', '', $child_permission['id']), $child_permission['text']];
                    }
                }
                if (isset($menu['children'])) {
                    array_push($menus, $menu);
                }
            } else {
                if ($is_admin || in_array($permission['id'], $permissions)) {
                    array_push($menus, [str_replace('system.', '', $permission['id']), $permission['text'], $permission['menu_icon']]);
                }
            }
        }
        return $menus;
    }
}