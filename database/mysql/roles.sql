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

 Date: 30/06/2024 18:25:20
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '角色名称',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `pids` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '父级ID(族谱)',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：1正常，0禁用',
  `permissions_id` varchar(15000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '拥有的权限id 用","隔开; (子级只能拥有父级拥有的权限)',
  `describe` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '描述',
  `is_delete` tinyint NOT NULL DEFAULT '0' COMMENT '是否删除: 1删除(关联有用户或下级角色不能删除)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `pid` (`pid`),
  KEY `title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='角色';

-- ----------------------------
-- Records of roles
-- ----------------------------
BEGIN;
INSERT INTO `roles` (`id`, `title`, `pid`, `pids`, `status`, `permissions_id`, `describe`, `is_delete`, `created_at`, `updated_at`) VALUES (1, '超级管理员', 0, '', 1, '', '超级管理员', 0, '2022-03-21 12:32:16', '2023-06-26 03:11:01');
INSERT INTO `roles` (`id`, `title`, `pid`, `pids`, `status`, `permissions_id`, `describe`, `is_delete`, `created_at`, `updated_at`) VALUES (2, '管理员', 1, '1', 1, '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57', '管理员', 0, '2022-03-21 12:34:47', '2023-06-30 11:06:15');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
