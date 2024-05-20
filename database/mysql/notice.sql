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

 Date: 20/05/2024 09:25:57
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for notice
-- ----------------------------
DROP TABLE IF EXISTS `notice`;
CREATE TABLE `notice` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` tinytext NOT NULL COMMENT '内容',
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '数据(以json格式保存读取数据)',
  `type` tinyint NOT NULL COMMENT '类型',
  `receiver_id` int unsigned NOT NULL DEFAULT '0' COMMENT '接收者id',
  `sender_id` int unsigned NOT NULL DEFAULT '0' COMMENT '发送者id',
  `sender_notice_id` int NOT NULL COMMENT '发送方回复id(notice.id)',
  `receiver_notice_id` int unsigned NOT NULL DEFAULT '0' COMMENT '接收方回复id(notice.id)',
  `read_time` timestamp NULL DEFAULT NULL COMMENT '阅读时间',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
