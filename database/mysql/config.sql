/*
 Navicat Premium Data Transfer

 Source Server         : MySQL8
 Source Server Type    : MySQL
 Source Server Version : 80034
 Source Host           : localhost:3306
 Source Schema         : admin

 Target Server Type    : MySQL
 Target Server Version : 80034
 File Encoding         : 65001

 Date: 28/02/2024 21:06:32
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
  `value` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '配置值，不能超过5000个字符',
  `example` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '配置示例',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='参数配置表';

-- ----------------------------
-- Records of config
-- ----------------------------
BEGIN;
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (1, 'CAPTCHA_DISABLE', '禁用验证码', 3, '0', '0', '禁用验证码：0启用；1禁用', '2023-06-21 15:25:40', '2023-06-29 21:41:28');
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (2, 'SECURE_DISABLE', '禁用安全码', 3, '1', '0', '禁用安全码：0启用；1禁用', '2023-06-21 09:13:31', '2023-06-29 21:41:09');
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (3, 'ADMIN_IP_WHITELIST_DISABLE', '禁用后台IP白名单', 3, '1', '0', '禁用后台IP白名单：0启用；1禁用', '2023-06-21 09:21:02', '2023-06-29 21:43:00');
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (4, 'ADMIN_IP_WHITELIST', '后台IP白名单', 4, '[\"8.8.8.8\",\"127.0.0.1\"]', '[\"8.8.8.8\",\"127.0.0.1\"]', '后台IP白名单: 多个IP以英文逗号分割', '2023-06-21 09:23:25', '2023-06-29 21:19:46');
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (5, 'CHECK_CHANGE_IP', '验证IP是否变更', 3, '1', '0', '验证IP是否变更：0 验证； 1不验证', '2023-06-21 10:18:11', '2023-06-29 21:30:55');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
