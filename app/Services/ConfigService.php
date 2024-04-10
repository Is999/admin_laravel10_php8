<?php

namespace App\Services;

use App\Enum\Code;
use App\Enum\ConfigType;
use App\Enum\OrderBy;
use App\Enum\RedisKeys;
use App\Exceptions\CustomizeException;
use App\Models\Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class ConfigService extends Service
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

        $query = Config::when(Arr::get($input, 'uuid'), function (Builder $query, $val) {
            return $query->where('uuid', $val);
        })->when(Arr::get($input, 'title'), function (Builder $query, $val) {
            return $query->where('title', $val);
        });

        // 总数
        $total = $query->count();
        $items = [];
        if ($total) {
            // 排序,分页
            $items = $query->select([
                'id', 'uuid', 'title', 'type', 'value', 'example', 'remark', 'created_at', 'updated_at'
            ])->orderBy($orderByField, $orderByType)
                ->offset($pageSize * ($page - 1))
                ->limit($pageSize)
                ->get();
        }
        return ['total' => $total, 'items' => $items];
    }

    /**
     * @param Request $request
     * @param array $input
     * @return bool
     * @throws CustomizeException
     */
    public function add(Request $request, array $input): bool
    {
        $model = new Config;
        // 验证uuid 是否已经存在
        if ($model->where('uuid', Arr::get($input, 'uuid'))->exists()) {
            throw new CustomizeException(Code::E100054, $input);
        }

        $model->title = Arr::get($input, 'title', '');
        $model->uuid = Arr::get($input, 'uuid', '');
        $model->title = Arr::get($input, 'title', '');
        $model->type = Arr::get($input, 'type', ConfigType::STRING_TYPE);
        $model->value = Arr::get($input, 'value', '');
        $model->example = Arr::get($input, 'example', '');
        $model->remark = Arr::get($input, 'remark', '');
        $model->created_at = date('Y-m-d H:i:s');
        $model->updated_at = date('Y-m-d H:i:s');

        $res = $model->save();
        if ($res) {
            // 刷新 参数配置 Hash
            RedisService::initTable(RedisKeys::CONFIG_UUID, [$model->uuid]);
        }
        return $res;
    }

    /**
     * @param Request $request
     * @param int $id
     * @param array $input
     * @return bool
     * @throws CustomizeException
     */
    public function edit(Request $request, int $id, array $input): bool
    {
        // 查找配置
        $model = Config::find($id);
        if (!$model) {
            throw new CustomizeException(Code::E100055);
        }

        $model->value = Arr::get($input, 'value', $model->value);
        $model->remark = Arr::get($input, 'remark', $model->remark);
        $model->updated_at = date('Y-m-d H:i:s');

        $res = $model->save();
        if ($res) {
            // 刷新 参数配置 Hash
            RedisService::initTable(RedisKeys::CONFIG_UUID, [$model->uuid]);
        }
        return $res;
    }

    /**
     * 查看缓存数据
     * @param string $uuid
     * @return mixed
     */
    public static function getCache(string $uuid): mixed
    {
        $val = null;
        $config = RedisService::hGetAllTable(RedisKeys::CONFIG_UUID . $uuid, true);
        if ($config) {
            switch ($config['type']) {
                case ConfigType::STRING_TYPE->value:
                    $val = (string)$config['value'];
                    break;
                case ConfigType::INT_TYPE->value:
                    $val = (int)$config['value'];
                    break;
                case ConfigType::FLOAT_TYPE->value:
                    $val = (float)$config['value'];
                    break;
                case ConfigType::BOOL_TYPE->value:
                    $val = (bool)$config['value'];
                    break;
                case ConfigType::JSON_ARR_TYPE->value:
                    $val = json_decode($config['value'], true);
                    break;
            }
        }
        return $val;
    }

    /**
     * 刷新配置
     * @param string $uuid
     * @return void
     */
    public static function renew(string $uuid): void
    {
        RedisService::initTable(RedisKeys::CONFIG_UUID . $uuid);
    }

    /**
     * @param $type
     * @param $value
     * @return bool|float|int|string
     * @throws CustomizeException
     */
    public function checkAndReformValue($type, $value): float|bool|int|string
    {
        switch ($type) {
            case ConfigType::INT_TYPE->value:
                if (!preg_match("/^[+-]?(0|[1-9]\d*)$/", $value)) {
                    throw new CustomizeException(Code::FAIL, '数据格式错误：不是Integer类型');
                }
                return (int)$value;
            case ConfigType::FLOAT_TYPE->value:
                if (!preg_match("/^[+-]?(0|[1-9]\d*)(\.\d{1,8})?$/", $value)) {
                    throw new CustomizeException(Code::FAIL, '数据格式错误：不是Float类型');
                }
                return (float)$value;
            case ConfigType::BOOL_TYPE->value:
                if (!preg_match("/^(0|1|TRUE|FALSE)$/", strtoupper($value))) {
                    throw new CustomizeException(Code::FAIL, '数据格式错误：不是Boolean类型');
                }
                return true == preg_match("/^(1|TRUE)$/", strtoupper($value));
            case ConfigType::JSON_ARR_TYPE->value:
                try {
                    if (!preg_match("/^(\{[\s\S]*\}|\[[\s\S]*\])$/", $value)) {
                        throw new CustomizeException(Code::FAIL);
                    }
                    $value = json_decode($value, true);
                    if ($value === null) {
                        throw new CustomizeException(Code::FAIL);
                    }
                    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (Throwable) {
                    throw new CustomizeException(Code::FAIL, '数据格式错误：不是JsonArr类型');
                }
            default:
                return $value;
        }
    }
}
