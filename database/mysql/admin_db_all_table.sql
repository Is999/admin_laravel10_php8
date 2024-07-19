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

 Date: 13/07/2024 00:42:10
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
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '配置值',
  `example` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '配置示例',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='参数配置表';

-- ----------------------------
-- Records of config
-- ----------------------------
BEGIN;
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (1, 'ADMIN_IP_WHITELIST_DISABLE', '禁用后台IP白名单', 3, '1', '0', '[生产建议配置：0 启用] 禁用后台IP白名单：0 启用；1 禁用', '2023-06-21 09:21:02', '2024-06-30 18:06:03');
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (2, 'ADMIN_IP_WHITELIST', '后台IP白名单', 4, '[\"8.8.8.8\",\"127.0.0.1\"]', '[\"8.8.8.8\",\"127.0.0.1\"]', '后台IP白名单: 多个IP以英文逗号分割', '2023-06-21 09:23:25', '2024-06-30 01:10:15');
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (3, 'ADMIN_CHECK_CHANGE_IP', '验证IP是否变更', 3, '1', '0', '[生产建议配置：0 验证] 验证IP是否变更：0 验证； 1 不验证', '2023-06-21 10:18:11', '2024-06-30 18:06:14');
INSERT INTO `config` (`id`, `uuid`, `title`, `type`, `value`, `example`, `remark`, `created_at`, `updated_at`) VALUES (4, 'ADMIN_SIGNATURE_DISABLE', '禁用签名', 3, '1', '0', '[生产建议配置：0 启用] 禁用签名：0 启用；1 禁用', '2024-04-22 12:04:47', '2024-06-30 18:06:23');
COMMIT;

-- ----------------------------
-- Table structure for file
-- ----------------------------
DROP TABLE IF EXISTS `file`;
CREATE TABLE `file`
(
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '文件名',
  `type` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '文件类型',
  `size` int unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '文件状态：0 未使用，1使用中，2 删除标记， 3 已删除',
  `expiration` timestamp NULL DEFAULT NULL COMMENT '文件过期时间，过期后删除',
  `modTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '文件最近【修改/访问】时间',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间，文件【创建/上传】时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间，文件修改时间',
  PRIMARY KEY (`id`),
  KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='文件信息';

-- ----------------------------
-- Records of file
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for menu
-- ----------------------------
DROP TABLE IF EXISTS `menu`;
CREATE TABLE `menu`
(
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `permissions_uuid` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '权限uuid(permissions.uuid)',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '菜单名称',
  `title_lang` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '多语言key',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态: 1显示, 0隐藏',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `pids` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '父级ID(族谱)',
  `component` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT 'Vue 组件',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT 'Vue 路由',
  `type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '类型: 0目录, 1菜单',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '图标',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `is_shortcut` tinyint NOT NULL DEFAULT '0' COMMENT '是否快捷: 1是, 0否',
  `describe` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '描述',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `permissions_uuid` (`permissions_uuid`) USING BTREE,
  KEY `titile` (`title`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='菜单';

-- ----------------------------
-- Records of menu
-- ----------------------------
BEGIN;
INSERT INTO `menu` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                    `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (1, '100001', '系统管理', 'routes.admin.system.moduleName', 1, 0, '', 'LAYOUT', '/system', 0,
        'ion:settings-outline', 1, 0, '管理员,角色,权限管理', '2022-03-22 17:53:05', '2022-03-22 17:57:09');
INSERT INTO `menu` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                    `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (2, '100002', '角色管理', 'routes.admin.system.role', 1, 1, '1', '/admin/system/role/index', 'role', 1,
        'ant-design:idcard-twotone', 2, 0, '角色管理', '2022-03-22 17:55:10', '2022-03-22 18:17:00');
INSERT INTO `menu` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                    `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (3, '100011', '权限管理', 'routes.admin.system.permission', 1, 1, '1', '/admin/system/permission/index',
        'permission', 1, 'carbon:user-role', 3, 0, '权限管理', '2022-03-22 18:18:28', '2022-03-22 18:20:04');
INSERT INTO `menu` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                    `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (4, '100017', '菜单管理', 'routes.admin.system.menu', 1, 1, '1', '/admin/system/menu/index', 'menu', 1,
        'ant-design:menu-outlined', 4, 0, '菜单管理', '2022-04-08 15:44:10', '2022-07-08 13:41:07');
INSERT INTO `menu` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                    `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (5, '100023', '账号管理', 'routes.admin.system.user', 1, 1, '1', '/admin/system/account/index', 'user', 1,
        'ant-design:contacts-twotone', 5, 0, '账号管理', '2022-04-08 15:43:00', '2022-07-08 13:46:48');
INSERT INTO `menu` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                    `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (6, '100036', '字典管理', 'routes.admin.system.config', 1, 1, '1', '/admin/system/config/index', 'config', 1,
        'ant-design:profile-outlined', 6, 0, '字典管理', '2022-07-04 12:33:17', '2022-07-13 11:15:30');
INSERT INTO `menu` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                    `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (7, '100043', '缓存管理', 'routes.admin.system.cache', 1, 1, '1', '/admin/system/cache/index', 'cache', 1,
        'ant-design:database-outlined', 7, 0, '缓存管理', '2022-07-06 16:30:47', '2023-06-26 05:32:11');
INSERT INTO `menu` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                    `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (8, '100055', '控制台', 'routes.dashboard.dashboard', 1, 0, '', 'LAYOUT', '/dashboard', 0,
        'ant-design:dashboard-outlined', 8, 0, '控制台', '2022-05-17 11:55:00', '2022-07-13 11:15:30');
INSERT INTO `menu` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                    `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (9, '100056', '分析页', 'routes.dashboard.analysis', 1, 8, '8', '/dashboard/analysis/index', 'analysis', 1,
        'ant-design:dashboard-outlined', 9, 0, '分析页', '2022-05-20 17:41:14', '2022-07-13 11:16:22');
INSERT INTO `menu` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                    `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (10, '100057', '工作台', 'routes.dashboard.workbench', 1, 8, '8', '/dashboard/workbench/index', '/workbench', 1,
        'ant-design:experiment-twotone', 10, 0, '工作台', '2022-05-20 17:47:58', '2022-07-13 11:16:33');
INSERT INTO `menu` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                    `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (11, '100053', '个人管理', 'routes.admin.personal.moduleName', 1, 0, '', 'LAYOUT', '/account', 0,
        'ant-design:audit-outlined', 11, 0, '个人管理', '2022-06-15 16:37:48', '2023-06-26 13:26:55');
INSERT INTO `menu` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                    `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (12, '100054', '个人信息', 'routes.admin.personal.info', 1, 11, '11', '/admin/account/setting/index', 'setting',
        1, 'ant-design:idcard-twotone', 12, 0, '个人信息', '2022-06-15 16:43:27', '2022-07-13 11:16:43');
INSERT INTO `menu` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                    `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (13, '100052', '后台日志', 'routes.admin.system.userlog', 1, 1, '1', '/admin/system/userlog/index', 'userlog', 1,
        'ant-design:code-twotone', 13, 0, '后台日志', '2023-06-26 14:02:02', '2023-06-27 10:27:56');
COMMIT;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='信息通知';

-- ----------------------------
-- Records of notice
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for permission
-- ----------------------------
DROP TABLE IF EXISTS `permission`;
CREATE TABLE `permission`
(
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
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='权限';

-- ----------------------------
-- Records of permission
-- ----------------------------
BEGIN;
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (1, '100001', '系统管理', '4', 0, '', 4, '系统管理(目录)', 1, '2022-03-21 21:06:17', '2022-05-20 15:31:41');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (2, '100002', '角色管理', 'role.index', 1, '1', 5, '角色管理(菜单,页面)', 1, '2022-03-21 21:07:41',
        '2022-05-20 15:35:01');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (3, '100003', '添加', '7', 2, '1,2', 7, '添加角色(按钮,页面)', 1, '2022-03-21 21:17:07', '2022-03-22 16:55:53');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (4, '100004', '保存', 'role.add', 3, '1,2,3', 1, '添加角色(新增)', 1, '2022-03-21 21:17:14',
        '2022-03-22 16:56:04');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (5, '100005', '编辑', '7', 2, '1,2', 7, '编辑角色(按钮,页面)', 1, '2022-03-21 21:41:53', '2022-03-22 16:56:17');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (6, '100006', '保存', 'role.edit', 5, '1,2,5', 2, '编辑角色(修改)', 1, '2022-03-21 21:44:27',
        '2022-03-22 16:56:29');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (7, '100007', '删除', 'role.del', 2, '1,2', 3, '删除角色(按钮, 删除)', 1, '2022-03-22 12:13:51',
        '2022-03-22 16:56:49');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (8, '100008', '启用/禁用', 'role.editStatus', 2, '1,2', 2, '启用/禁用 角色(按钮,修改)', 1, '2022-03-22 12:10:36',
        '2022-03-22 17:21:23');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (9, '100009', '权限', 'role.permission', 2, '1,2', 0, '编辑角色权限(按钮,页面，查看)', 1, '2022-03-22 11:22:18',
        '2024-06-30 17:37:50');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (10, '100010', '保存', 'role.editPermission', 9, '1,2,9', 2, '编辑角色权限(修改)', 1, '2022-03-22 11:37:44',
        '2022-03-23 21:58:32');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (11, '100011', '权限管理', 'permission.index', 1, '1', 5, '权限管理(菜单,页面)', 1, '2022-03-22 16:05:41',
        '2022-05-20 15:36:30');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (12, '100012', '添加', '7', 11, '1,11', 7, '添加权限(按钮,页面)', 1, '2022-03-22 16:09:35',
        '2022-03-22 16:09:39');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (13, '100013', '保存', 'permission.add', 12, '1,11,12', 1, '添加权限(新增)', 1, '2022-03-22 16:12:04',
        '2022-03-23 21:56:31');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (14, '100014', '编辑', '7', 11, '1,11', 7, '编辑权限(按钮,页面)', 1, '2022-03-22 16:14:05',
        '2022-03-22 16:14:08');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (15, '100015', '保存', 'permission.edit', 14, '1,11,14', 2, '编辑权限(修改)', 1, '2022-03-22 16:21:45',
        '2022-03-23 21:56:39');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (16, '100016', '删除', 'permission.del', 11, '1,11', 3, '删除权限(按钮, 删除)', 1, '2022-03-22 16:26:05',
        '2022-03-23 21:56:48');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (17, '100017', '菜单管理', 'menu.index', 1, '1', 5, '菜单管理(菜单,页面)', 1, '2022-03-22 17:08:54',
        '2024-05-17 23:23:50');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (18, '100018', '添加', '7', 17, '1,17', 7, '添加菜单(按钮,页面)', 1, '2022-03-22 17:11:43',
        '2022-03-22 17:12:44');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (19, '100019', '保存', 'menu.add', 18, '1,17,18', 1, '添加菜单(新增)', 1, '2022-03-22 17:13:48',
        '2022-03-22 17:15:47');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (20, '100020', '编辑', '7', 17, '1,17', 7, '编辑菜单(按钮,页面)', 1, '2022-03-22 16:14:05',
        '2022-03-22 16:14:08');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (21, '100021', '保存', 'menu.edit', 20, '1,17,20', 1, '编辑菜单(修改)', 1, '2022-03-22 17:13:48',
        '2022-03-22 17:15:52');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (22, '100022', '显示/隐藏', 'menu.editStatus', 17, '1,17', 2, '显示/隐藏 菜单(按钮,修改)', 1,
        '2022-03-22 17:19:17', '2022-03-22 17:21:09');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (23, '100023', '账号管理', 'user.index', 1, '1', 5, '账号管理(菜单,页面)', 1, '2022-03-22 17:26:44',
        '2022-05-20 15:37:49');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (24, '100024', '添加', '7', 23, '1,23', 7, '添加管理员(按钮,页面)', 1, '2022-03-22 17:43:11',
        '2022-03-22 17:45:18');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (25, '100025', '保存', 'user.add', 24, '1,23,24', 1, '添加管理员(新增)', 1, '2022-03-22 17:46:40',
        '2022-03-22 17:46:33');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (26, '100026', '编辑', '7', 23, '1,23', 7, '编辑管理员(按钮,页面)', 1, '2022-03-22 16:14:05',
        '2022-03-22 16:14:08');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (27, '100027', '保存', 'user.edit', 26, '1,23,26', 2, '编辑管理员(修改)', 1, '2022-03-22 16:14:05',
        '2024-06-30 17:49:19');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (28, '100028', '启用/禁用', 'user.editStatus', 23, '1,23', 2, '启用/禁用 管理员(按钮,修改)', 1,
        '2022-03-22 12:10:36', '2022-03-22 17:21:23');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (29, '100029', '用户角色', 'user.roleList', 23, '1,23', 0, '用户角色(弹框页面)', 1, '2022-04-08 18:48:16',
        '2024-06-30 17:50:34');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (30, '100030', '给用户分配角色', 'user.addRole', 29, '1,23,29', 1, '给用户分配角色(按钮,新增)', 1,
        '2022-04-08 18:51:41', '2024-06-30 17:51:29');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (31, '100031', '修改密码', 'user.editPassword', 23, '1,23', 2, '修改密码(按钮,修改)', 1, '2022-04-08 19:00:14',
        '2022-04-08 19:01:46');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (32, '100032', '解除角色与用户的关系', 'user.delRole', 29, '1,23,29', 3, '解除角色与用户的关系(按钮,删除)', 1,
        '2022-04-08 18:51:41', '2024-06-30 17:52:05');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (33, '100033', '重置密码', 'user.resetPassword', 23, '1,23', 2, '重置用户的密码(按钮,修改)', 1,
        '2022-04-08 18:51:41', '2024-06-30 17:52:41');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (34, '100034', '启用 TOTP MFA (身份验证）', 'user.editMfaStatus', 23, '1,23', 2,
        '启用 TOTP MFA (身份验证）(按钮,修改)', 1, '2022-04-08 18:51:41', '2024-06-30 17:54:49');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (35, '100035', '编辑账号角色', 'user.editRole', 23, '1,23', 2, '编辑账号角色/确认 (按钮, 新增, 删除, 修改)', 1,
        '2022-06-01 19:07:05', '2024-06-30 17:56:29');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (36, '100036', '字典管理', 'config.index', 1, '1', 5, '字典管理(菜单,页面)', 1, '2022-07-04 11:47:39',
        '2022-07-04 11:47:39');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (37, '100037', '添加', '8', 36, '1,36', 7, '添加字典(按钮,页面)', 1, '2022-07-04 11:58:57',
        '2022-07-04 11:58:57');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (38, '100038', '保存', 'config.add', 37, '1,36,37', 1, '添加字典(新增)', 1, '2022-07-04 12:00:23',
        '2022-07-04 12:00:23');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (39, '100039', '编辑', '8', 36, '1,36', 7, '编辑字典(按钮,页面)', 1, '2022-07-04 12:02:02',
        '2022-07-04 12:02:02');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (40, '100040', '保存', 'config.edit', 39, '1,36,39', 2, '编辑字典(修改)', 1, '2022-07-04 12:04:30',
        '2022-07-04 12:04:30');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (41, '100041', '查看字典缓存', 'config.getCache', 36, '1,36', 0, '查看字典缓存(按钮, 查看)', 1,
        '2022-07-04 21:24:37', '2024-06-30 17:58:36');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (42, '100042', '刷新缓存', 'config.renew', 36, '1,36', 2, '刷新字典缓存(按钮)', 1, '2023-06-24 13:25:38',
        '2024-06-30 17:57:24');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (43, '100043', '缓存管理', 'cache.index', 1, '1', 5, '缓存管理(菜单,页面)', 1, '2022-07-06 16:20:14',
        '2023-06-26 04:01:59');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (44, '100044', '查看缓存key信息', 'cache.keyInfo', 43, '1,43', 7, '查看缓存key信息(按钮, 查看)', 1,
        '2022-07-06 16:24:52', '2023-06-26 04:01:54');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (45, '100045', '刷新缓存', 'cache.renew', 43, '1,43', 7, '刷新缓存(按钮, 修改)', 1, '2022-07-06 16:26:57',
        '2023-06-26 04:01:50');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (46, '100046', '刷新全部缓存', 'cache.renewAll', 43, '1,43', 7, '刷新全部缓存(按钮, 修改)', 1,
        '2022-07-06 16:26:57', '2023-06-26 04:01:43');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (47, '100047', '服务器信息', 'cache.serverInfo', 43, '1,43', 7, '服务器信息(按钮, 查看)', 1,
        '2022-07-06 16:26:57', '2023-06-26 04:01:38');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (48, '100048', '搜索', '8', 43, '1,43', 7, '搜索(按钮, 页面)', 1, '2023-06-26 12:22:05', '2023-06-26 04:25:51');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (49, '100049', '搜索', 'cache.searchKey', 48, '1,43,48', 0, '搜索(按钮, 查看)', 1, '2023-06-26 12:28:06',
        '2024-06-30 18:02:09');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (50, '100050', '查看缓存key信息', 'cache.searchKeyInfo', 48, '1,43,48', 0, '查看缓存key信息(按钮, 查看)', 1,
        '2023-06-26 12:56:24', '2024-06-30 18:01:31');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (51, '100051', '生成绑定安全秘钥地址', 'user.buildMfaSecretKeyUrl', 26, '1,23,26', 7,
        '生成绑定安全秘钥地址(按钮，查看)', 1, '2023-06-26 23:01:59', '2024-04-29 12:52:33');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (52, '100052', '后台日志', 'userlog.index', 1, '1', 5, '后台日志(菜单，页面)', 1, '2023-06-26 13:30:06',
        '2024-05-17 22:59:01');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (53, '100053', '个人管理', '8', 0, '', 4, '个人信息管理(目录)', 1, '2022-06-15 15:32:57', '2023-06-30 10:52:14');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (54, '100054', '个人信息', 'user.mine', 53, '53', 5, '个人信息 (菜单, 页面, 按钮)', 1, '2022-06-15 15:37:25',
        '2023-06-30 10:52:32');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (55, '100055', '控制台', '4', 0, '', 4, '控制台(目录)', 1, '2022-03-21 21:06:17', '2023-06-30 10:54:25');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (56, '100056', '分析页', '', 55, '55', 5, '分析页(菜单, 页面)', 1, '2022-05-20 17:32:49', '2023-06-30 10:54:31');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (57, '100057', '工作台', '', 55, '55', 5, '工作台(菜单,页面)', 1, '2022-05-20 17:36:35', '2023-06-30 10:54:39');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (58, '100058', '启用/禁用', 'permission.editStatus', 11, '1,11', 2, '启用/禁用 权限(按钮, 修改)', 1,
        '2024-05-17 23:15:19', '2024-06-30 17:46:30');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (59, '100059', '秘钥管理', 'secretKey.index', 1, '1', 5, '秘钥管理列表（菜单，页面）', 1, '2024-06-30 16:54:55',
        '2024-06-30 16:54:55');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (60, '100060', '添加', '8', 59, '1,59', 7, '添加秘钥(按钮,页面)', 1, '2024-06-30 17:19:56',
        '2024-06-30 17:19:56');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (61, '100061', '保存', 'secretKey.add', 60, '1,59,60', 1, '添加秘钥(新增)', 1, '2024-06-30 17:21:32',
        '2024-06-30 17:21:32');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (62, '100062', '编辑', '8', 59, '1,59', 7, '编辑秘钥(按钮,页面)', 1, '2024-06-30 17:22:22',
        '2024-06-30 17:22:22');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (63, '100063', '保存', 'secretKey.edit', 62, '1,59,62', 2, '编辑秘钥(修改)', 1, '2024-06-30 17:23:20',
        '2024-06-30 17:23:20');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (64, '100064', '启用/禁用', 'secretKey.editStatus', 59, '1,59', 2, '启用/禁用秘钥(按钮,修改）', 1,
        '2024-06-30 17:25:41', '2024-06-30 18:00:28');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (65, '100065', '文件管理', 'files.index', 1, '1', 5, '文件管理列表(菜单，页面)', 1, '2024-06-30 17:27:20',
        '2024-06-30 17:32:57');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (66, '100066', '设置文件状态', 'files.editStatus', 65, '1,65', 2, '设置文件状态(按钮,修改）', 1,
        '2024-06-30 17:29:26', '2024-06-30 17:33:13');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (67, '100067', '设置文件过期', 'files.editExpire', 65, '1,65', 2, '设置文件过期(按钮,修改)', 1,
        '2024-06-30 17:32:37', '2024-06-30 17:32:37');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (68, '100068', '删除', 'files.del', 65, '1,65', 3, '删除文件(按钮，删除)', 1, '2024-06-30 17:34:08',
        '2024-06-30 17:34:08');
INSERT INTO `permission` (`id`, `uuid`, `title`, `module`, `pid`, `pids`, `type`, `describe`, `status`, `created_at`,
                          `updated_at`)
VALUES (69, '100069', '消息管理', 'notice.index', 1, '1', 5, '消息列表(菜单，页面)', 1, '2024-06-30 17:35:47',
        '2024-06-30 17:35:47');
COMMIT;

-- ----------------------------
-- Table structure for role
-- ----------------------------
DROP TABLE IF EXISTS `role`;
CREATE TABLE `role`
(
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
-- Records of role
-- ----------------------------
BEGIN;
INSERT INTO `role` (`id`, `title`, `pid`, `pids`, `status`, `permissions_id`, `describe`, `is_delete`, `created_at`,
                    `updated_at`)
VALUES (1, '超级管理员', 0, '', 1, '', '超级管理员', 0, '2022-03-21 12:32:16', '2023-06-26 03:11:01');
INSERT INTO `role` (`id`, `title`, `pid`, `pids`, `status`, `permissions_id`, `describe`, `is_delete`, `created_at`,
                    `updated_at`)
VALUES (2, '管理员', 1, '1', 1,
        '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57',
        '管理员', 0, '2022-03-21 12:34:47', '2023-06-30 11:06:15');
COMMIT;

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
INSERT INTO `secret_key` (`id`, `uuid`, `title`, `aes_key`, `aes_iv`, `rsa_public_key_user`, `rsa_public_key_server`,
                          `rsa_private_key_server`, `status`, `remark`, `created_at`, `updated_at`)
VALUES (1, 'YWRtaW4wMDAx', '管理后台前端应用',
        'eyJpdiI6Ikh2T3JON1ozSUhYU1lJcnVkMHlQRHc9PSIsInZhbHVlIjoiQm84c0p3cTIrdjNiR1dsOFdKbE8wUnErYU02L1Y4UWt4S0FheW5yVmFkdlV1OXBlZkwrc0J4amdKWEhIYkJsWiIsIm1hYyI6IjMyMTFjYmU2NTllZWNjMDdjOWVjNDA2ZmU1YTFiNTliYjFlOWMxNjJhYzIzYTBhMDRkNGYxODgyYWZjNGZkMDciLCJ0YWciOiIifQ==',
        'eyJpdiI6IjdoS1RXd05tRWg3cTEzdXowMEFpdnc9PSIsInZhbHVlIjoiQkhtSDB4R0o4ZGhvY3krZThHRVZTcWN1UVpiZjRBbVRXekpaYjZSWGpGVT0iLCJtYWMiOiI2NjdhNjJiOTk1NjdiYTU4NGU3MGRjODMyYmViNmFjNDM2YTlkNjM4MWE2MmVmNzNiMmIyYTIyMGE0MjM4ZTE5IiwidGFnIjoiIn0=',
        '/app/pem/public_user_YWRtaW4wMDAx.pem', '/app/pem/public.pem', '/app/pem/private.pem', 1, '管理后台前端应用',
        '2024-04-22 18:54:52', '2024-06-30 18:07:49');
COMMIT;

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user`
(
    `id`                int                                                           NOT NULL AUTO_INCREMENT COMMENT '主键',
    `name`              varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci  NOT NULL DEFAULT '' COMMENT '用户账号',
    `real_name`         varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci  NOT NULL DEFAULT '' COMMENT '用户名',
    `password`          varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '密码hash',
    `email`             varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '邮箱',
    `phone`             varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '电话',
    `mfa_secure_key`    varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '基于时间的动态密码 (TOTP) 多重身份验证 (MFA) 秘钥：如Google Authenticator、Microsoft Authenticator',
    `mfa_status`        tinyint unsigned                                              NOT NULL DEFAULT '0' COMMENT '启用 TOTP MFA (两步验证 2FA)：0 不启用，1 启用',
    `status`            tinyint                                                       NOT NULL DEFAULT '1' COMMENT '账户状态: 1正常, 0禁用',
    `avatar`            varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '头像',
    `remark`            varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '简介备注',
    `last_login_time`   timestamp                                                     NOT NULL COMMENT '最后登录时间',
    `last_login_ip`     varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci  NOT NULL DEFAULT '' COMMENT '最后登录ip',
    `last_login_ipaddr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '最后登录ip区域',
    `created_at`        timestamp                                                     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
    `updated_at`        timestamp                                                     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 3
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci COMMENT ='管理员';

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

-- ----------------------------
-- Table structure for user_log
-- ----------------------------
DROP TABLE IF EXISTS `user_log`;
CREATE TABLE `user_log` (
                            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户账户',
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '0' COMMENT '动作名称',
  `route` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '路由名称',
  `method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '模块/类/方法',
  `describe` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '描述',
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT '操作数据',
  `ip` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `ipaddr` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT 'ip地区信息',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `created_at` (`created_at`) USING BTREE,
  KEY `action` (`action`),
  KEY `user_name` (`user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='管理员操作日志';

-- ----------------------------
-- Records of user_log
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for user_role_access
-- ----------------------------
DROP TABLE IF EXISTS `user_role_access`;
CREATE TABLE `user_role_access`
(
    `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int unsigned NOT NULL COMMENT '用户id',
  `role_id` int unsigned NOT NULL COMMENT '角色id',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `user_id_role_id` (`user_id`,`role_id`) USING BTREE,
  KEY `role_id` (`role_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户角色';

-- ----------------------------
-- Records of user_role_access
-- ----------------------------
BEGIN;
INSERT INTO `user_role_access` (`id`, `user_id`, `role_id`, `created_at`)
VALUES (1, 1, 1, '2022-04-05 16:30:56');
INSERT INTO `user_role_access` (`id`, `user_id`, `role_id`, `created_at`)
VALUES (2, 2, 2, '2023-06-16 00:24:01');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
