/*
 Navicat Premium Data Transfer

 Source Server         : localhost8_3306
 Source Server Type    : MySQL
 Source Server Version : 80032
 Source Host           : localhost:3306
 Source Schema         : admin_laravel

 Target Server Type    : MySQL
 Target Server Version : 80032
 File Encoding         : 65001

 Date: 30/06/2023 18:44:40
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`
(
    `id`                int                                                           NOT NULL AUTO_INCREMENT COMMENT '主键',
    `name`              varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL DEFAULT '' COMMENT '用户账号',
    `real_name`         varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL DEFAULT '' COMMENT '用户名',
    `password`          varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码hash',
    `email`             varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
    `phone`             varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL DEFAULT '' COMMENT '电话',
    `secure_key`        varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '安全秘钥：如google验证器秘钥',
    `status`            int unsigned                                                  NOT NULL DEFAULT '1' COMMENT '账户状态: 1正常, 0禁用',
    `avatar`            varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '头像',
    `remark`            varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '简介备注',
    `last_login_time`   timestamp                                                     NOT NULL COMMENT '最后登录时间',
    `last_login_ip`     varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL DEFAULT '' COMMENT '最后登录ip',
    `last_login_ipaddr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '最后登录ip区域',
    `created_at`        timestamp                                                     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
    `updated_at`        timestamp                                                     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 3
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT ='管理员';

-- ----------------------------
-- Records of users
-- ----------------------------
BEGIN;
INSERT INTO `users` (`id`, `name`, `real_name`, `password`, `email`, `phone`, `secure_key`, `status`, `avatar`,
                     `remark`, `last_login_time`, `last_login_ip`, `last_login_ipaddr`, `created_at`, `updated_at`)
VALUES (1, 'super999', 'super999', '$2y$10$qOjSQkQ43o4RIy36ZP.FLef3nPtJDJWYZDKdCvvyYjYqyBgl30axu', 'super@gmail.com',
        '18899990000',
        'eyJpdiI6Ikp1NG1yazZZbW9ObVRLdmRDKzlCS2c9PSIsInZhbHVlIjoiT3lJYTdwblhQYnUzbFhYMzl4SjhNTGFpbmdtV3VDNUdHV0hsb2VVWEovcz0iLCJtYWMiOiI3M2FiZmFiMWFjOGY3YzIzMmNjZmQ2NjhjMTg2OGM2OTk5NzNjYTRjNzgxYzg4MDExYjBlNmZmY2ZiNjgxYjhhIiwidGFnIjoiIn0',
        1, '', '', '2023-06-30 15:18:38', '172.18.0.1', '广东省深圳市', '2022-03-21 21:54:26', '2023-06-30 15:18:38');
INSERT INTO `users` (`id`, `name`, `real_name`, `password`, `email`, `phone`, `secure_key`, `status`, `avatar`,
                     `remark`, `last_login_time`, `last_login_ip`, `last_login_ipaddr`, `created_at`, `updated_at`)
VALUES (2, 'admin999', 'admin999', '$2y$10$eOWZP39.YIM5p83sJTnq.O3MwtHDj6akYNANoCWRj6pMwoHTlB/Mi', 'admin999@gmail.com',
        '18899990099',
        'eyJpdiI6Ikp1NG1yazZZbW9ObVRLdmRDKzlCS2c9PSIsInZhbHVlIjoiT3lJYTdwblhQYnUzbFhYMzl4SjhNTGFpbmdtV3VDNUdHV0hsb2VVWEovcz0iLCJtYWMiOiI3M2FiZmFiMWFjOGY3YzIzMmNjZmQ2NjhjMTg2OGM2OTk5NzNjYTRjNzgxYzg4MDExYjBlNmZmY2ZiNjgxYjhhIiwidGFnIjoiIn0=',
        1, '', '', '2023-06-29 13:02:32', '172.18.0.1', '广东省深圳市', '2022-03-21 21:54:26', '2023-06-29 13:02:32');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
