<?php

namespace App\Enum;

/**
 *  user.is_enabled 字段状态
 */
enum ConfigUuid: string
{
    // 安全配置
    const ADMIN_IP_WHITELIST_DISABLE = 'ADMIN_IP_WHITELIST_DISABLE'; // 禁用IP白名单
    const ADMIN_IP_WHITELIST = 'ADMIN_IP_WHITELIST'; // 后台ip白名单
    const ADMIN_CHECK_CHANGE_IP = 'ADMIN_CHECK_CHANGE_IP'; // 验证IP是否变更

}