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

 Date: 17/05/2024 23:34:19
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for permissions
-- ----------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uuid` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '唯一标识',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '权限名称',
  `module` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '权限匹配模型(路由名称 | 控制器/方法)',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `pids` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '父级ID(族谱)',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '类型: 0查看, 1新增, 2修改, 3删除, 4目录, 5菜单, 6页面, 7按钮, 8其它',
  `describe` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '描述',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1 启用；0 禁用',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uuid` (`uuid`) USING BTREE,
  KEY `title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='权限';

-- ----------------------------
-- Records of permissions
-- ----------------------------
BEGIN;
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (1, '100001', '系统管理', '4', 0, '', 4, '系统管理(目录)', 1, '2022-03-21 21:06:17', '2022-05-20 15:31:41');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (2, '100002', '角色管理', 'role.index', 1, '1', 5, '角色管理(菜单,页面)', 1, '2022-03-21 21:07:41', '2022-05-20 15:35:01');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (3, '100003', '添加', '7', 2, '1,2', 7, '添加角色(按钮,页面)', 1, '2022-03-21 21:17:07', '2022-03-22 16:55:53');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (4, '100004', '保存', 'role.add', 3, '1,2,3', 1, '添加角色(新增)', 1, '2022-03-21 21:17:14', '2022-03-22 16:56:04');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (5, '100005', '编辑', '7', 2, '1,2', 7, '编辑角色(按钮,页面)', 1, '2022-03-21 21:41:53', '2022-03-22 16:56:17');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (6, '100006', '保存', 'role.edit', 5, '1,2,5', 2, '编辑角色(修改)', 1, '2022-03-21 21:44:27', '2022-03-22 16:56:29');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (7, '100007', '删除', 'role.del', 2, '1,2', 3, '删除角色(按钮, 删除)', 1, '2022-03-22 12:13:51', '2022-03-22 16:56:49');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (8, '100008', '启用/禁用', 'role.editStatus', 2, '1,2', 2, '启用/禁用 角色(按钮,修改)', 1, '2022-03-22 12:10:36', '2022-03-22 17:21:23');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (9, '100009', '权限', 'role.permission', 2, '1,2', 6, '编辑角色权限(按钮,页面)', 1, '2022-03-22 11:22:18', '2022-03-22 16:57:21');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (10, '100010', '保存', 'role.editPermission', 9, '1,2,9', 2, '编辑角色权限(修改)', 1, '2022-03-22 11:37:44', '2022-03-23 21:58:32');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (11, '100011', '权限管理', 'permission.index', 1, '1', 5, '权限管理(菜单,页面)', 1, '2022-03-22 16:05:41', '2022-05-20 15:36:30');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (12, '100012', '添加', '7', 11, '1,11', 7, '添加权限(按钮,页面)', 1, '2022-03-22 16:09:35', '2022-03-22 16:09:39');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (13, '100013', '保存', 'permission.add', 12, '1,11,12', 1, '添加权限(新增)', 1, '2022-03-22 16:12:04', '2022-03-23 21:56:31');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (14, '100014', '编辑', '7', 11, '1,11', 7, '编辑权限(按钮,页面)', 1, '2022-03-22 16:14:05', '2022-03-22 16:14:08');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (15, '100015', '保存', 'permission.edit', 14, '1,11,14', 2, '编辑权限(修改)', 1, '2022-03-22 16:21:45', '2022-03-23 21:56:39');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (16, '100016', '删除', 'permission.del', 11, '1,11', 3, '删除权限(按钮, 删除)', 1, '2022-03-22 16:26:05', '2022-03-23 21:56:48');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (17, '100017', '菜单管理', 'menu.index', 1, '1', 5, '菜单管理(菜单,页面)', 1, '2022-03-22 17:08:54', '2024-05-17 23:23:50');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (18, '100018', '添加', '7', 17, '1,17', 7, '添加菜单(按钮,页面)', 1, '2022-03-22 17:11:43', '2022-03-22 17:12:44');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (19, '100019', '保存', 'menu.add', 18, '1,17,18', 1, '添加菜单(新增)', 1, '2022-03-22 17:13:48', '2022-03-22 17:15:47');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (20, '100020', '编辑', '7', 17, '1,17', 7, '编辑菜单(按钮,页面)', 1, '2022-03-22 16:14:05', '2022-03-22 16:14:08');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (21, '100021', '保存', 'menu.edit', 20, '1,17,20', 1, '编辑菜单(修改)', 1, '2022-03-22 17:13:48', '2022-03-22 17:15:52');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (22, '100022', '显示/隐藏', 'menu.editStatus', 17, '1,17', 2, '显示/隐藏 菜单(按钮,修改)', 1, '2022-03-22 17:19:17', '2022-03-22 17:21:09');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (23, '100023', '账号管理', 'user.index', 1, '1', 5, '账号管理(菜单,页面)', 1, '2022-03-22 17:26:44', '2022-05-20 15:37:49');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (24, '100024', '添加', '7', 23, '1,23', 7, '添加管理员(按钮,页面)', 1, '2022-03-22 17:43:11', '2022-03-22 17:45:18');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (25, '100025', '保存', 'user.add', 24, '1,23,24', 1, '添加管理员(新增)', 1, '2022-03-22 17:46:40', '2022-03-22 17:46:33');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (26, '100026', '编辑', '7', 23, '1,23', 7, '编辑管理员(按钮,页面)', 1, '2022-03-22 16:14:05', '2022-03-22 16:14:08');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (27, '100027', '保存', 'user.edit', 26, '1,23,26', 7, '编辑管理员(修改)', 1, '2022-03-22 16:14:05', '2022-03-22 17:48:58');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (28, '100028', '启用/禁用', 'user.editStatus', 23, '1,23', 2, '启用/禁用 管理员(按钮,修改)', 1, '2022-03-22 12:10:36', '2022-03-22 17:21:23');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (29, '100029', '用户角色', 'user.roleList', 23, '1,23', 6, '用户角色(弹框页面)', 1, '2022-04-08 18:48:16', '2022-04-08 18:52:44');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (30, '100030', '给用户分配角色', 'user.addRole', 29, '1,23,29', 7, '给用户分配角色(按钮,新增)', 1, '2022-04-08 18:51:41', '2022-04-08 21:42:38');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (31, '100031', '修改密码', 'user.editPassword', 23, '1,23', 2, '修改密码(按钮,修改)', 1, '2022-04-08 19:00:14', '2022-04-08 19:01:46');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (32, '100032', '解除角色与用户的关系', 'user.delRole', 29, '1,23,29', 7, '解除角色与用户的关系(按钮,删除)', 1, '2022-04-08 18:51:41', '2022-04-08 21:41:52');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (33, '100033', '重置密码', 'user.resetPassword', 23, '1,23', 7, '重置用户的密码(按钮,修改)', 1, '2022-04-08 18:51:41', '2022-04-08 21:41:52');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (34, '100034', '启用 TOTP MFA (身份验证）', 'user.editMfaStatus', 23, '1,23', 7, '启用 TOTP MFA (身份验证）(按钮,修改)', 1, '2022-04-08 18:51:41', '2024-04-29 13:37:21');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (35, '100035', '编辑账号角色', 'user.editRoles', 23, '1,23', 1, '编辑账号角色/确认 (按钮, 新增, 删除)', 1, '2022-06-01 19:07:05', '2022-06-02 11:50:04');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (36, '100036', '字典管理', 'config.index', 1, '1', 5, '字典管理(菜单,页面)', 1, '2022-07-04 11:47:39', '2022-07-04 11:47:39');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (37, '100037', '添加', '8', 36, '1,36', 7, '添加字典(按钮,页面)', 1, '2022-07-04 11:58:57', '2022-07-04 11:58:57');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (38, '100038', '保存', 'config.add', 37, '1,36,37', 1, '添加字典(新增)', 1, '2022-07-04 12:00:23', '2022-07-04 12:00:23');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (39, '100039', '编辑', '8', 36, '1,36', 7, '编辑字典(按钮,页面)', 1, '2022-07-04 12:02:02', '2022-07-04 12:02:02');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (40, '100040', '保存', 'config.edit', 39, '1,36,39', 2, '编辑字典(修改)', 1, '2022-07-04 12:04:30', '2022-07-04 12:04:30');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (41, '100041', '查看字典缓存', 'config.getCache', 36, '1,36', 7, '查看字典缓存(按钮, 查看)', 1, '2022-07-04 21:24:37', '2022-07-04 21:24:37');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (42, '100042', '刷新缓存', 'config.renew', 36, '1,36', 7, '刷新字典缓存(按钮)', 1, '2023-06-24 13:25:38', '2023-06-26 04:02:04');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (43, '100043', '缓存管理', 'cache.index', 1, '1', 5, '缓存管理(菜单,页面)', 1, '2022-07-06 16:20:14', '2023-06-26 04:01:59');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (44, '100044', '查看缓存key信息', 'cache.keyInfo', 43, '1,43', 7, '查看缓存key信息(按钮, 查看)', 1, '2022-07-06 16:24:52', '2023-06-26 04:01:54');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (45, '100045', '刷新缓存', 'cache.renew', 43, '1,43', 7, '刷新缓存(按钮, 修改)', 1, '2022-07-06 16:26:57', '2023-06-26 04:01:50');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (46, '100046', '刷新全部缓存', 'cache.renewAll', 43, '1,43', 7, '刷新全部缓存(按钮, 修改)', 1, '2022-07-06 16:26:57', '2023-06-26 04:01:43');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (47, '100047', '服务器信息', 'cache.serverInfo', 43, '1,43', 7, '服务器信息(按钮, 查看)', 1, '2022-07-06 16:26:57', '2023-06-26 04:01:38');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (48, '100048', '搜索', '8', 43, '1,43', 7, '搜索(按钮, 页面)', 1, '2023-06-26 12:22:05', '2023-06-26 04:25:51');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (49, '100049', '搜索', 'cache.searchKey', 48, '1,43,48', 7, '搜索(按钮, 查看)', 1, '2023-06-26 12:28:06', '2023-06-26 04:56:45');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (50, '100050', '查看缓存key信息', 'cache.searchKeyInfo', 48, '1,43,48', 7, '查看缓存key信息(按钮, 查看)', 1, '2023-06-26 12:56:24', '2023-06-26 04:56:51');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (51, '100051', '生成绑定安全秘钥地址', 'user.buildMfaSecretKeyUrl', 26, '1,23,26', 7, '生成绑定安全秘钥地址(按钮，查看)', 1, '2023-06-26 23:01:59', '2024-04-29 12:52:33');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (52, '100052', '后台日志', 'userlog.index', 1, '1', 5, '后台日志(菜单，页面)', 1, '2023-06-26 13:30:06', '2024-05-17 22:59:01');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (53, '100053', '个人管理', '8', 0, '', 4, '个人信息管理(目录)', 1, '2022-06-15 15:32:57', '2023-06-30 10:52:14');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (54, '100054', '个人信息', 'user.mine', 53, '53', 5, '个人信息 (菜单, 页面, 按钮)', 1, '2022-06-15 15:37:25', '2023-06-30 10:52:32');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (55, '100055', '控制台', '4', 0, '', 4, '控制台(目录)', 1, '2022-03-21 21:06:17', '2023-06-30 10:54:25');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (56, '100056', '分析页', '', 55, '55', 5, '分析页(菜单, 页面)', 1, '2022-05-20 17:32:49', '2023-06-30 10:54:31');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (57, '100057', '工作台', '', 55, '55', 5, '工作台(菜单,页面)', 1, '2022-05-20 17:36:35', '2023-06-30 10:54:39');
INSERT INTO `permissions` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`, `updated_at`) VALUES (58, '100058', '启用/禁用', 'permission.editStatus', 11, '1,11', 7, '启用/禁用 权限(按钮)', 1, '2024-05-17 23:15:19', '2024-05-17 23:16:38');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
