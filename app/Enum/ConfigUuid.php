<?php

namespace App\Enum;

/**
 *  config.uuid 字段
 */
enum ConfigUuid: string
{
    // 安全配置
    const ADMIN_IP_WHITELIST_DISABLE = 'ADMIN_IP_WHITELIST_DISABLE'; // 禁用IP白名单
    const ADMIN_IP_WHITELIST = 'ADMIN_IP_WHITELIST'; // 后台ip白名单
    const ADMIN_CHECK_CHANGE_IP = 'ADMIN_CHECK_CHANGE_IP'; // 验证后台IP是否变更
    const ADMIN_SIGNATURE_DISABLE = 'ADMIN_SIGNATURE_DISABLE'; // 后台禁用签名
    const MFA_CHECK_ENABLE = 'MFA_CHECK_ENABLE'; // 强校验MFA设备验证码

}
