<?php

namespace app\admin\common;

class Constant
{
    const ACTION_NO_AUTHORIZATION_REQUIRED = ['login', 'logout', 'unauthorized'];
    const SESSION_BASE_USER_ID = 'base_user_id', SESSION_USER_ID = 'user_id', SESSION_USER_PERMISSION = 'user_permission', SESSION_APP_PERMISSION = 'app_permission';
    const ROLE_ADMIN = -1, ROLE_AGENT = -2, ROLE_CLIENT_CALCULATION = -3, ROLE_CLIENT_ANNUAL = -4;
    const PAYMENT_TYPE_AGENT = 1, PAYMENT_TYPE_CLIENT = 2;
    const CLIENT_TYPE_NORMAL = 1, CLIENT_TYPE_RELATE = 2;
    const PROMOTE_TYPE_LIST = [1 => '爱采购', 2 => '搜索推广', 3 => '巨量推广', 4 => '360推广', 5 => '腾讯推广'], PROMOTE_TYPE_B2B = 1, PROMOTE_TYPE_CPC = 2, PROMOTE_TYPE_OCE = 3, PROMOTE_TYPE_360 = 4, PROMOTE_TYPE_QQ = 5;
    const PROMOTE_SETTING_HOUR_LIST = [1 => '工作时间（8点-18点）', 2 => '24小时'];
    const KEYWORD_STATUS_LIST = [1 => '待审核', 2 => '审核通过'], KEYWORD_STATUS_NEW = 0, KEYWORD_STATUS_APPROVED = 1;
    const KEYWORD_MATCH_TYPE_LIST = [1 => '精确匹配', 2 => '短语匹配', 3 => '智能匹配'], KEYWORD_MATCH_TYPE_1 = 1, KEYWORD_MATCH_TYPE_2 = 2;
    const USER_TYPE_ADMIN = 1, USER_TYPE_AGENT = 2, USER_TYPE_CLIENT = 3;
    const ROLE_DATA_LEVEL_LIST = [1 => '一级', 2 => '二级', 3 => '三级'];
    const PERMISSION_NOT_SAVE = ['system', 'system.block1', 'system.block2', 'system.block3', 'system.block4', 'system.block5', 'system.block6', 'system.block7', 'system.block8', 'system.block9', 'system.block10', 'system.block11', 'system.block12'];
    const YES_NO_LIST = [1 => '是', 0 => '否'], ACTIVE_LIST = [1 => '启用', 0 => '禁用'], GENDER_LIST = [1 => '男', 2 => '女'];
    const BANNER_TYPE_LIST = [1 => '首页', 2 => '新闻', 3 => '圈子'];
    const CLIENT_VERSION_LIST = [1 => '扣费版', 2 => '年费版'], CLIENT_VERSION_CALCULATION = 1, CLIENT_VERSION_ANNUAL = 2;
    const CLIENT_ANNUAL_RATE_LIST = [1 => '01-10', 2 => '11-20', 3 => '21-50'];
    const ORDER_STATUS_LIST = [0 => '待审核', 1 => '通过', -1 => '拒绝'];
    const REMIND_TYPE_LIST = [1 => '商品', 2 => '关键词', 3 => '创意', 4 => '出价', 5 => '否定词', 6 => '投放设置', 7 => '余额不足', 8 => '即将到期', 9 => '单元', 10 => '计划'], REMIND_TYPE_PRODUCT = 1, REMIND_TYPE_KEYWORD = 2, REMIND_TYPE_CREATIVITY = 3, REMIND_TYPE_BID = 4, REMIND_TYPE_NEGATIVE = 5, REMIND_TYPE_SETTING = 7, REMIND_TYPE_BALANCE = 7, REMIND_TYPE_EXPIRED = 8, REMIND_TYPE_UNIT = 9, REMIND_TYPE_PLAN = 10;
    const ORDER_TYPE_LIST = [1 => '试用', 2 => '季度', 3 => '半年', 4 => '全年'];
    const ORDER_TYPE_DAY_LIST = [1 => 2, 2 => 90, 3 => 180, 4 => 365];
    const BID_PERCENTAGE_LIST = ['30' => '30%', '50' => '50%', '80' => '80%'];
    const SETTING_TYPE = ['service' => '网站服务', 'oce' => '巨量推广', 'baidu' => '百度推广', '360' => '360推广', 'qq' => '腾讯推广', 'system' => '系统'];
    const SETTING_CODE = ['baidu.username' => '账户', 'baidu.password' => '密码', 'baidu.token' => 'TOKEN', 'baidu.aderid' => 'ADERID', 'oce.appid' => 'APPID', 'oce.secret' => 'SECRET', 'oce.redirect_url' => 'REDIRECT_URL', 'oce.token' => '授权（请勿手动设置）', 'oce.advertiser_id' => '商户ID', '360.username' => '账户', '360.password' => '密码', '360.appid' => 'APPID', '360.secret' => 'SECRET', '360.token' => '授权（请勿手动设置）', 'system.website_domain' => '默认域名', 'system.website_name' => '默认名称', 'qq.appid' => 'APPID', 'qq.secret' => 'SECRET', 'qq.redirect_url' => 'REDIRECT_URL', 'qq.token' => '授权（请勿手动设置）', 'qq.advertiser_id' => '商户ID'];
    const ORGANIZATION_TYPE_LIST = [1 => '集团', 2 => '公司', 3 => '部门', 4 => '团队'];
    const ORGANIZATION_TYPE_GROUP = 1, ORGANIZATION_TYPE_COMPANY = 2, ORGANIZATION_TYPE_DEPARTMENT = 2, ORGANIZATION_TYPE_TEAM = 4;
}