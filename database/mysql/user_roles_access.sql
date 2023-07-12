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

 Date: 30/06/2023 18:44:28
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for user_roles_access
-- ----------------------------
DROP TABLE IF EXISTS `user_roles_access`;
CREATE TABLE `user_roles_access`
(
    `id`         int unsigned NOT NULL AUTO_INCREMENT COMMENT '索引',
    `user_id`    int unsigned NOT NULL COMMENT '用户id',
    `role_id`    int unsigned NOT NULL COMMENT '角色id',
    `created_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `user_id_role_id` (`user_id`, `role_id`) USING BTREE,
    KEY `role_id` (`role_id`) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 3
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT ='用户角色';

-- ----------------------------
-- Records of user_roles_access
-- ----------------------------
BEGIN;
INSERT INTO `user_roles_access` (`id`, `user_id`, `role_id`, `created_at`)
VALUES (1, 1, 1, '2022-04-05 16:30:56');
INSERT INTO `user_roles_access` (`id`, `user_id`, `role_id`, `created_at`)
VALUES (2, 2, 2, '2023-06-16 00:24:01');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
