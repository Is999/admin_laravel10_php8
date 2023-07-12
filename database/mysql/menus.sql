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

 Date: 30/06/2023 18:43:28
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for menus
-- ----------------------------
DROP TABLE IF EXISTS `menus`;
CREATE TABLE `menus`
(
    `id`               int unsigned                                                  NOT NULL AUTO_INCREMENT COMMENT '主键',
    `permissions_uuid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '权限uuid(permissions.uuid)',
    `title`            varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '菜单名称',
    `title_lang`       varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '多语言key',
    `status`           tinyint(1)                                                    NOT NULL DEFAULT '1' COMMENT '状态: 1显示, 0隐藏',
    `pid`              int                                                           NOT NULL DEFAULT '0' COMMENT '父级ID',
    `pids`             varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '父级ID(族谱)',
    `component`        varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Vue 组件',
    `path`             varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Vue 路由',
    `type`             tinyint(1)                                                    NOT NULL DEFAULT '0' COMMENT '类型: 0目录, 1菜单',
    `icon`             varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '图标',
    `sort`             int                                                           NOT NULL DEFAULT '0' COMMENT '排序',
    `is_shortcut`      tinyint(1)                                                    NOT NULL DEFAULT '0' COMMENT '是否快捷: 1是, 0否',
    `describe`         varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '描述',
    `created_at`       timestamp                                                     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`       timestamp                                                     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `permissions_uuid` (`permissions_uuid`) USING BTREE,
    KEY `titile` (`title`) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 14
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT ='菜单';

-- ----------------------------
-- Records of menus
-- ----------------------------
BEGIN;
INSERT INTO `menus` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                     `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (1, '100001', '系统管理', 'routes.admin.system.moduleName', 1, 0, '', 'LAYOUT', '/system', 0,
        'ion:settings-outline', 1, 0, '管理员,角色,权限管理', '2022-03-22 17:53:05', '2022-03-22 17:57:09');
INSERT INTO `menus` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                     `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (2, '100002', '角色管理', 'routes.admin.system.role', 1, 1, '1', '/admin/system/role/index', 'role', 1,
        'ant-design:idcard-twotone', 2, 0, '角色管理', '2022-03-22 17:55:10', '2022-03-22 18:17:00');
INSERT INTO `menus` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                     `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (3, '100011', '权限管理', 'routes.admin.system.permission', 1, 1, '1', '/admin/system/permission/index',
        'permission', 1, 'carbon:user-role', 3, 0, '权限管理', '2022-03-22 18:18:28', '2022-03-22 18:20:04');
INSERT INTO `menus` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                     `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (4, '100017', '菜单管理', 'routes.admin.system.menu', 1, 1, '1', '/admin/system/menu/index', 'menu', 1,
        'ant-design:menu-outlined', 4, 0, '菜单管理', '2022-04-08 15:44:10', '2022-07-08 13:41:07');
INSERT INTO `menus` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                     `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (5, '100023', '账号管理', 'routes.admin.system.user', 1, 1, '1', '/admin/system/account/index', 'user', 1,
        'ant-design:contacts-twotone', 5, 0, '账号管理', '2022-04-08 15:43:00', '2022-07-08 13:46:48');
INSERT INTO `menus` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                     `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (6, '100036', '参数配置', 'routes.admin.system.config', 1, 1, '1', '/admin/system/config/index', 'config', 1,
        'ant-design:profile-outlined', 6, 0, '参数配置', '2022-07-04 12:33:17', '2022-07-13 11:15:30');
INSERT INTO `menus` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                     `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (7, '100043', '缓存管理', 'routes.admin.system.cache', 1, 1, '1', '/admin/system/cache/index', 'cache', 1,
        'ant-design:database-outlined', 7, 0, '缓存管理', '2022-07-06 16:30:47', '2023-06-26 05:32:11');
INSERT INTO `menus` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                     `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (8, '100055', '控制台', 'routes.dashboard.dashboard', 1, 0, '', 'LAYOUT', '/dashboard', 0,
        'ant-design:dashboard-outlined', 8, 0, '控制台', '2022-05-17 11:55:00', '2022-07-13 11:15:30');
INSERT INTO `menus` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                     `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (9, '100056', '分析页', 'routes.dashboard.analysis', 1, 8, '8', '/dashboard/analysis/index', 'analysis', 1,
        'ant-design:dashboard-outlined', 9, 0, '控制台/分析页', '2022-05-20 17:41:14', '2022-07-13 11:16:22');
INSERT INTO `menus` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                     `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (10, '100057', '工作台', 'routes.dashboard.workbench', 1, 8, '8', '/dashboard/workbench/index', '/workbench', 1,
        'ant-design:experiment-twotone', 10, 0, '控制台/工作台', '2022-05-20 17:47:58', '2022-07-13 11:16:33');
INSERT INTO `menus` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                     `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (11, '100053', '个人管理', 'routes.admin.personal.moduleName', 1, 0, '', 'LAYOUT', '/account', 0,
        'ant-design:audit-outlined', 11, 0, '个人管理', '2022-06-15 16:37:48', '2023-06-26 13:26:55');
INSERT INTO `menus` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                     `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (12, '100054', '个人信息', 'routes.admin.personal.info', 1, 11, '11', '/admin/account/setting/index', 'setting',
        1, 'ant-design:idcard-twotone', 12, 0, '个人管理/个人信息', '2022-06-15 16:43:27', '2022-07-13 11:16:43');
INSERT INTO `menus` (`id`, `permissions_uuid`, `title`, `title_lang`, `status`, `pid`, `pids`, `component`, `path`,
                     `type`, `icon`, `sort`, `is_shortcut`, `describe`, `created_at`, `updated_at`)
VALUES (13, '100052', '操作日志', 'routes.admin.system.userlog', 1, 1, '1', '/admin/system/userlog/index', 'userlog', 1,
        'ant-design:code-twotone', 13, 0, '操作日志', '2023-06-26 14:02:02', '2023-06-27 10:27:56');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
