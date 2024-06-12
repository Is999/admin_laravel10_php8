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

 Date: 17/05/2024 21:01:30
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for files
-- ----------------------------
DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
                         `id`         int unsigned                                                  NOT NULL AUTO_INCREMENT COMMENT '主键',
                         `name`       varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '文件名',
                         `type`       varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci  NOT NULL DEFAULT '' COMMENT '文件类型',
                         `size`       int unsigned                                                  NOT NULL DEFAULT '0' COMMENT '文件大小',
                         `status`     tinyint                                                       NOT NULL DEFAULT '0' COMMENT '文件状态：0 未使用，1使用中，2 删除标记， 3 已删除',
                         `expiration` timestamp                                                     NULL     DEFAULT NULL COMMENT '文件过期时间，过期后删除',
                         `modTime`    timestamp                                                     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '文件最近【修改/访问】时间',
                         `created_at` timestamp                                                     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间，文件【创建/上传】时间',
                         `updated_at` timestamp                                                     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间，文件修改时间',
                         PRIMARY KEY (`id`),
                         KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='文件信息';

SET FOREIGN_KEY_CHECKS = 1;
