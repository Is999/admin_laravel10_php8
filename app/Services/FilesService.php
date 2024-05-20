<?php

namespace App\Services;

use App\Enum\Code;
use App\Exceptions\CustomizeException;
use App\Models\Files;
use Illuminate\Http\UploadedFile;

class FilesService extends Service
{
    public $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']; // 允许的文件类型

    public function __construct(array $mimes=[])
    {
        if(!empty($mimes)){
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
    public function upload(UploadedFile $file, string $path = 'uploads/', int $maxSize = 1024*1024*10, bool $consistency = true):string
    {
        // 验证文件类型
        $type = $file->getMimeType();
        if (!in_array($type, $this->allowedTypes)) {
            throw new CustomizeException(code::F10001, ['type' =>$type]);
        }

        // 验证扩展名和文件类型是否一致
        $ext = $file->getClientOriginalExtension();
        if ($consistency && stripos($type, $ext) === false) {
            throw new CustomizeException(code::F10002, ['type' =>$type, 'ext'=>$ext]);
        }

        // 验证文件大小
        $size = $file->getSize();
        if ($size > $maxSize) {
            throw new CustomizeException(code::F10003, ['size' =>$size, 'maxSize'=>$maxSize]);
        }

        // 生成新的唯一文件名
        $filename = time() . '_' . uniqid() . '.' . $ext;

        $path = str_ends_with($path, '/') ? $path : $path . '/';

        // 存储文件到指定位置
        $newPath = $file->storeAs($path, $filename);

        // 存储文件地址到数据库
        $model = new Files();
        $model->name = $filename;
        $model->path = $path;
        $model->type = $type;
        $model->size = $size;
        $model->status = 0; // '文件状态：0 未使用，1使用中，2 删除标记， 3 已删除';
        $model->expiration = null;
        $model->modTime = date('Y-m-d H:i:s');
        $model->created_at = date('Y-m-d H:i:s');
        $model->updated_at = date('Y-m-d H:i:s');
        $model->save();

        return $newPath;
    }
}
