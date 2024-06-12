<?php

namespace App\Services;

use App\Enum\Code;
use App\Enum\FileStatus;
use App\Exceptions\CustomizeException;
use App\Models\Files;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FilesService extends Service
{
    public array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']; // 允许的文件类型

    public function __construct(array $mimes = [])
    {
        if (!empty($mimes)) {
            $this->allowedTypes = $mimes;
        }
    }

    /**
     * @param UploadedFile $file
     * @param string $path 存储目录
     * @param int $maxSize 文件大小 默认10MB
     * @param bool $consistency 验证扩展名和文件类型是否一致
     * @return string
     * @throws CustomizeException
     */
    public function upload(UploadedFile $file, string $path = 'uploads', int $maxSize = 1024 * 1024 * 10, bool $consistency = true): string
    {
        // 验证文件类型
        $type = $file->getMimeType();
        if (!in_array($type, $this->allowedTypes)) {
            throw new CustomizeException(code::F10001, ['type' => $type]);
        }

        // 验证扩展名和文件类型是否一致
        $ext = $file->getClientOriginalExtension();
        if ($consistency && (!$ext || stripos($type, $ext) === false)) {
            throw new CustomizeException(code::F10002, ['type' => $type, 'ext' => $ext]);
        }

        // 处理空扩展名
        if (!$ext) {
            $mimeArr = explode('/', $type);
            $ext = $mimeArr[count($mimeArr) - 1];
        }

        // 验证文件大小
        $size = $file->getSize();
        if ($size > $maxSize) {
            throw new CustomizeException(code::F10003, ['size' => $size, 'maxSize' => $maxSize]);
        }

        // 生成新的唯一文件名
        $filename = time() . '_' . uniqid() . '.' . $ext;

        // 路径处理
        $path = str_ends_with($path, '/') ? rtrim($path, '/') : $path;
        $path = str_starts_with($path, '/') ? $path : '/' . $path;

        // 存储文件到指定位置
        $newPath = $file->storeAs($path, $filename);
        $newPath = str_starts_with($newPath, '/') ? $newPath : '/' . $newPath;

        // 存储文件地址到数据库
        $model = new Files();
        $model->name = $newPath;
        $model->type = $type;
        $model->size = $size;
        $model->status = FileStatus::UNUSED->value; // '文件状态：0 未使用，1使用中，2 删除标记， 3 已删除';
        $model->expiration = null;
        $model->modTime = date('Y-m-d H:i:s');
        $model->created_at = date('Y-m-d H:i:s');
        $model->updated_at = date('Y-m-d H:i:s');
        $model->save();

        return $model->name;
    }

    /**
     * 修改文件状态
     * @param string $name 文件名
     * @param FileStatus $status 状态
     * @param int $expiration 过期时间，默认7天后过期
     * @return bool
     * @throws CustomizeException
     */
    public function updateStatus(string $name, FileStatus $status, int $expiration = 7 * 24 * 60 * 60): bool
    {
        if ($status->value < FileStatus::DELETED->value && !Storage::exists($name)) {
            throw new CustomizeException(Code::F5000, ['flag' => $name]);
        }

        $model = Files::where('name', $name)->first();
        if (!$model) {
            throw new CustomizeException(Code::E100069, ['file' => $name]);
        }

        $model->status = $status->value;
        $model->updated_at = date('Y-m-d H:i:s');
        if (FileStatus::TOBEDELETED->value == $status->value) {
            $model->expiration = date('Y-m-d H:i:s', time() + $expiration);
        }
        return $model->save();
    }
}
