<?php

namespace App\Enum;

/**
 *  permission.type 字段状态
 */
enum PermissionType: int
{
    // 注意title值的顺序要和case定义一致
    private const title = ['查看', '新增', '修改', '删除', '目录', '菜单', '页面', '按钮', '其它'];

    case READ = 0; // 查看
    case ADD = 1; // 新增
    case UPDATE = 2; // 修改
    case DELETE = 3; // 删除
    case MENU_CATALOG = 4; // 目录
    case MENU_NAV = 5; // 菜单
    case VIEW_PAGE = 6; // 页面
    case BUTTON = 7; // 按钮
    case OTHERS = 8; // 其它


//    /**
//     *  通过反射获取所有定义的const和case参数
//     * @return array
//     */
//    public static function toArray()
//    {
//        $reflection = new \ReflectionClass(self::class);
//        return $reflection->getConstants();
//    }


    /**
     * 获取所有cases 值
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * 映射title
     * @return array
     */
    public static function titleMap(): array
    {
        return array_combine(self::values(), self::title);
    }

    /**
     * 下拉选择框
     * @return array
     */
    public static function forSelect(): array
    {
        $arr = [];
        foreach (self::titleMap() as $k => $v) {
            $arr[] = array('value' => $k, 'title' => $v);
        }
        return $arr;
    }
}