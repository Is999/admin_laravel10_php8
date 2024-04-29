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

 Date: 28/02/2024 21:11:57
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户账号',
  `real_name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '密码hash',
  `email` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `phone` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '电话',
  `secure_key` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT 'MFA密码秘钥：如Google Authenticator、Microsoft Authenticator等基于时间的一次性密码(TOTP)',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '账户状态: 1正常, 0禁用',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '头像',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '简介备注',
  `last_login_time` timestamp NOT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '最后登录ip',
  `last_login_ipaddr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '最后登录ip区域',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='管理员';

-- ----------------------------
-- Records of users
-- ----------------------------
BEGIN;
INSERT INTO `users` (`id`, `name`, `real_name`, `password`, `email`, `phone`, `secure_key`, `status`, `avatar`, `remark`, `last_login_time`, `last_login_ip`, `last_login_ipaddr`, `created_at`, `updated_at`) VALUES (1, 'super999', 'super999', '$2y$10$qOjSQkQ43o4RIy36ZP.FLef3nPtJDJWYZDKdCvvyYjYqyBgl30axu', '', '', 'eyJpdiI6Ikp1NG1yazZZbW9ObVRLdmRDKzlCS2c9PSIsInZhbHVlIjoiT3lJYTdwblhQYnUzbFhYMzl4SjhNTGFpbmdtV3VDNUdHV0hsb2VVWEovcz0iLCJtYWMiOiI3M2FiZmFiMWFjOGY3YzIzMmNjZmQ2NjhjMTg2OGM2OTk5NzNjYTRjNzgxYzg4MDExYjBlNmZmY2ZiNjgxYjhhIiwidGFnIjoiIn0', 1, '', '', '2023-06-30 15:18:38', '172.18.0.1', '广东省深圳市', '2022-03-21 21:54:26', '2023-06-30 15:18:38');
INSERT INTO `users` (`id`, `name`, `real_name`, `password`, `email`, `phone`, `secure_key`, `status`, `avatar`, `remark`, `last_login_time`, `last_login_ip`, `last_login_ipaddr`, `created_at`, `updated_at`) VALUES (2, 'admin999', 'admin999', '$2y$10$ltUtx9xHJLhuPsjNS2EmZ.5ccYAyYfFznyrYmS0cNrdWRQRR2lhhq', '', '', 'eyJpdiI6Ikp1NG1yazZZbW9ObVRLdmRDKzlCS2c9PSIsInZhbHVlIjoiT3lJYTdwblhQYnUzbFhYMzl4SjhNTGFpbmdtV3VDNUdHV0hsb2VVWEovcz0iLCJtYWMiOiI3M2FiZmFiMWFjOGY3YzIzMmNjZmQ2NjhjMTg2OGM2OTk5NzNjYTRjNzgxYzg4MDExYjBlNmZmY2ZiNjgxYjhhIiwidGFnIjoiIn0=', 1, '', '', '2023-07-18 10:51:07', '172.18.0.1', '广东省深圳市', '2022-03-21 21:54:26', '2023-07-18 10:51:07');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
