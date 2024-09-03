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
    const MFA_CHECK_FREQUENCY = 'MFA_CHECK_FREQUENCY'; // MFA设备校验频率
    const CHECK_MFA_SCENARIOS_DISABLE = 'CHECK_MFA_SCENARIOS_DISABLE'; // MFA设备校验频率

    /**
     * 仅超级管理员可编辑的配置
     * @return string[]
     */
    public static function superOnlyUuids(): array
    {
        return array(
            self::ADMIN_IP_WHITELIST_DISABLE,
            self::ADMIN_CHECK_CHANGE_IP,
            self::ADMIN_SIGNATURE_DISABLE,
            self::MFA_CHECK_ENABLE,
            self::MFA_CHECK_FREQUENCY,
            self::CHECK_MFA_SCENARIOS_DISABLE,
        );
    }

}
