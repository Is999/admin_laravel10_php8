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

 Date: 13/07/2024 00:40:44
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user`
(
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户账号',
  `real_name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '密码hash',
  `email` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `phone` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '电话',
  `mfa_secure_key` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '基于时间的动态密码 (TOTP) 多重身份验证 (MFA) 秘钥：如Google Authenticator、Microsoft Authenticator',
  `mfa_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '启用 TOTP MFA (两步验证 2FA)：0 不启用，1 启用',
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
-- Records of user
-- ----------------------------
BEGIN;
INSERT INTO `user` (`id`, `name`, `real_name`, `password`, `email`, `phone`, `mfa_secure_key`, `mfa_status`, `status`,
                    `avatar`, `remark`, `last_login_time`, `last_login_ip`, `last_login_ipaddr`, `created_at`,
                    `updated_at`)
VALUES (1, 'super999', 'super999', '$2y$10$oR/DGfcwUKcqk0S0byUIRuHJZEPpLb1itVlLrtKv2rYQ8rP3rd5Xq', 'super999@gmail.com',
        '18888888999',
        'eyJpdiI6IkU5OTVBSjdac2thcENOS252T2ZSNUE9PSIsInZhbHVlIjoickFQMml4VDRmT2FiekdET3NQVlROK3hUTkdXOEowbkdyS0dzL2xzUjU0WT0iLCJtYWMiOiIyMmRlMjMzZTM2YWI1MTk0Zjc2ZmVhNjRhZjJmOWFiZmEyMzYwYzM1YTNmMTllN2I3M2I4NzIwMjYwMjA3Y2ExIiwidGFnIjoiIn0=',
        0, 1, '/uploads/1719733806_66810e2e0d002.jpg', '超级管理员', '2024-06-30 16:57:38', '192.168.65.1',
        '未知IP归属地[未获取到]', '2022-03-21 21:54:26', '2024-06-30 18:16:45');
INSERT INTO `user` (`id`, `name`, `real_name`, `password`, `email`, `phone`, `mfa_secure_key`, `mfa_status`, `status`,
                    `avatar`, `remark`, `last_login_time`, `last_login_ip`, `last_login_ipaddr`, `created_at`,
                    `updated_at`)
VALUES (2, 'admin999', 'admin999', '$2y$10$uc32v2od4s.5yAKc9k8FFOOnlLfKz4/dJTy1bUvrZaKjI.ki1NQqi', 'admin999@gamil.com',
        '18888888999',
        'eyJpdiI6IngwaVVJQXlMdVBMM0ViQ3d1b1pYSEE9PSIsInZhbHVlIjoibXNPSzFFRmhTZU9ROTlKeXIwR2pqSm5uTTNzTW4waDZyVWtwN1Z6aCt0MD0iLCJtYWMiOiI0NDdkNjNkZDcyYzhmMmVmYThiNzk0MmM1ZGFiZjdkNDIxYTJiODFlNzE3YjE5OTY2MWJhYzMwNzE1YjhiYTUzIiwidGFnIjoiIn0=',
        0, 1, '/uploads/1719736719_6681198f507c4.jpg', '管理员', '2024-06-30 16:42:29', '192.168.65.1',
        '未知IP归属地[未获取到]', '2022-03-21 21:54:26', '2024-06-30 18:17:03');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
