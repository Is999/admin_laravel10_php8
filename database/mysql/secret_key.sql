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

 Date: 30/06/2024 18:08:27
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for secret_key
-- ----------------------------
DROP TABLE IF EXISTS `secret_key`;
CREATE TABLE `secret_key` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uuid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT 'API KEY 唯一标识 ',
  `title` varchar(100) NOT NULL COMMENT '标题',
  `aes_key` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT 'AES KEY  长度：16, 24, 32位',
  `aes_iv` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT 'AES IV 长度：16位',
  `rsa_public_key_user` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户 RSA 公钥文件路径',
  `rsa_public_key_server` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '服务器 RSA 公钥文件路径',
  `rsa_private_key_server` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '服务器 RSA 私钥文件路径',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '1 启用， 0 禁用',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='秘钥';

-- ----------------------------
-- Records of secret_key
-- ----------------------------
BEGIN;
INSERT INTO `secret_key` (`id`, `uuid`, `title`, `aes_key`, `aes_iv`, `rsa_public_key_user`, `rsa_public_key_server`, `rsa_private_key_server`, `status`, `remark`, `created_at`, `updated_at`) VALUES (1, 'YWRtaW4wMDAx', '管理后台前端应用', 'eyJpdiI6Ikh2T3JON1ozSUhYU1lJcnVkMHlQRHc9PSIsInZhbHVlIjoiQm84c0p3cTIrdjNiR1dsOFdKbE8wUnErYU02L1Y4UWt4S0FheW5yVmFkdlV1OXBlZkwrc0J4amdKWEhIYkJsWiIsIm1hYyI6IjMyMTFjYmU2NTllZWNjMDdjOWVjNDA2ZmU1YTFiNTliYjFlOWMxNjJhYzIzYTBhMDRkNGYxODgyYWZjNGZkMDciLCJ0YWciOiIifQ==', 'eyJpdiI6IjdoS1RXd05tRWg3cTEzdXowMEFpdnc9PSIsInZhbHVlIjoiQkhtSDB4R0o4ZGhvY3krZThHRVZTcWN1UVpiZjRBbVRXekpaYjZSWGpGVT0iLCJtYWMiOiI2NjdhNjJiOTk1NjdiYTU4NGU3MGRjODMyYmViNmFjNDM2YTlkNjM4MWE2MmVmNzNiMmIyYTIyMGE0MjM4ZTE5IiwidGFnIjoiIn0=', '/app/public/admin/pem/public_user_YWRtaW4wMDAx.pem', '/app/public/admin/pem/public.pem', '/app/public/admin/pem/private.pem', 1, '管理后台前端应用', '2024-04-22 18:54:52', '2024-06-30 18:07:49');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
