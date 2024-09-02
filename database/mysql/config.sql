/*
 Navicat Premium Dump SQL

 Source Server         : Mysql8
 Source Server Type    : MySQL
 Source Server Version : 80034 (8.0.34)
 Source Host           : localhost:3306
 Source Schema         : admin

 Target Server Type    : MySQL
 Target Server Version : 80034 (8.0.34)
 File Encoding         : 65001

 Date: 29/08/2024 17:11:53
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for config
-- ----------------------------
DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uuid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '配置唯一标识',
  `title` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '配置标题',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '类型：0 String; 1 Integer; 2 Float; 3 Boolean；4 JsonArr; 5 仅做分组标题（配置归类）',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '配置值',
  `example` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '配置示例',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='参数配置表';

-- ----------------------------
-- Records of config
-- ----------------------------
BEGIN;
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (1, 'ADMIN_IP_WHITELIST_DISABLE', '禁用后台IP白名单', 3, '1', '0', '[生产建议配置：0 启用] 禁用后台IP白名单：0 启用；1 禁用', '2023-06-21 09:21:02', '2024-06-30 18:06:03');
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (2, 'ADMIN_IP_WHITELIST', '后台IP白名单', 4, '[\"8.8.8.8\",\"127.0.0.1\"]', '[\"8.8.8.8\",\"127.0.0.1\"]', '后台IP白名单: 多个IP以英文逗号分割', '2023-06-21 09:23:25', '2024-06-30 01:10:15');
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (3, 'ADMIN_CHECK_CHANGE_IP', '验证IP是否变更', 3, '1', '0', '[生产建议配置：0 验证] 验证IP是否变更：0 验证； 1 不验证', '2023-06-21 10:18:11', '2024-06-30 18:06:14');
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (4, 'ADMIN_SIGNATURE_DISABLE', '禁用签名', 3, '1', '0', '[生产建议配置：0 启用] 禁用签名：0 启用；1 禁用', '2024-04-22 12:04:47', '2024-06-30 18:06:23');
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (5, 'MFA_CHECK_ENABLE', '强校验MFA设备验证码', 3, '0', '1', '[生产建议配置：1 强启用] 强启用MFA设备（身份验证器）登录校验：1 强启用校验（用户设置MFA状态失效）；0 非强启用（默认使用用户设置的MFA状态）', '2024-08-25 12:47:13', '2024-08-29 16:45:13');
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (6, 'MFA_CHECK_FREQUENCY', 'MFA设备校验频率', 1, '1800', '0', 'MFA设备校验频率（单位秒），建议配置5分钟(300秒)以上: 0 需要校验的地方每次都校验，大于0 秒在该时间内只不再重复校验（x秒时间内只校验一次）', '2024-08-29 16:59:12', '2024-08-29 16:59:12');
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (7, 'CHECK_MFA_SCENARIOS', '校验MFA设备应用场景', 4, '{\"1\":\"修改秘密\",\"2\":\"修改MFA状态（关闭）\",\"3\":\"修改MFA秘钥\",\"4\":\"修改用户状态\",\"5\":\"添加用户\",\"6\":\"修改用户\"}', '{\"1\":\"修改秘密\",\"2\":\"修改MFA状态（关闭）\",\"3\":\"修改MFA秘钥\"}', '校验MFA设备应用场景，请到菜单【系统管理/MFA管理】配置相关选项', '2024-09-02 21:12:48', '2024-09-02 21:12:48');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
