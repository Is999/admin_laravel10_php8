<?php

namespace App\Services;

use App\Enum\OrderBy;
use App\Models\UserLog;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class UserLogService extends Service
{

    /**
     * 列表
     * @param Request $request
     * @param array $input
     * @return array
     */
    public function list(Request $request, array $input): array
    {
        // 分页, 排序
        $orderByField = Arr::get($input, 'field', 'id'); // 排序字段
        $orderByType = OrderBy::getLabel(Arr::get($input, 'order')); // 排序方式
        $page = Arr::get($input, 'page', 1); // 页码
        $pageSize = Arr::get($input, 'pageSize', 10); // 每页条数

        // 查询
        $query = UserLog::when(Arr::get($input, 'user_name'), function (Builder $query, $val) {
            return $query->where('user_name', $val);
        })->when(Arr::get($input, 'action'), function (Builder $query, $val) {
            return $query->where('action', $val);
        });

        // 总数
        $total = $query->count();

        // 排序,分页
        $items = $query->select([
            'id', 'user_id', 'user_name', 'action', 'route', 'method', 'describe', 'data', 'ip', 'ipaddr', 'created_at'
        ])->orderBy($orderByField, $orderByType)
            ->offset($pageSize * ($page - 1))->limit($pageSize)->get();

        return ['total' => $total, 'items' => $items];
    }

    /**
     * 添加日志
     * @param array $input
     * @param $user
     * @return bool
     */
    public static function add(array $input, $user): bool
    {
        $model = new UserLog();
        $model->user_id = Arr::get($user, 'id', 0);
        $model->user_name = Arr::get($user, 'name', '');
        $model->ip = Arr::get($user, 'last_login_ip', '');
        $model->ipaddr = Arr::get($user, 'last_login_ipaddr', '');

        $model->action = Arr::get($input, 'action', '');
        $model->route = Arr::get($input, 'route', '');
        $model->method = Arr::get($input, 'method', '');
        $model->describe = Arr::get($input, 'describe', '');
        $model->data = Arr::get($input, 'data', '');

        $model->created_at = date('Y-m-d H:i:s');

        return $model->save();
    }
}
