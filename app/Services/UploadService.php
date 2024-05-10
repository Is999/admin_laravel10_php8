<?php

namespace App\Services;

use App\Enum\Code;
use App\Exceptions\CustomizeException;
use App\Models\Files;
use Illuminate\Http\UploadedFile;

class UploadService extends Service
{
    public $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']; // 允许的文件类型


    /**
     * @param UploadedFile $file
     * @param string $path 存储目录
     * @param int $maxSize 文件大小 默认10MB
     * @param bool $consistency 验证扩展名和文件类型是否一致
     * @return string
     * @throws CustomizeException
     */
    public function file(UploadedFile $file, string $path = 'uploads', int $maxSize = 1024*1024*10, bool $consistency = true):string
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

        // 存储文件到指定位置
        $path = $file->storeAs('uploads', $filename);

        // 存储文件地址到数据库
        $model = new Files();
        $model->name = $filename;
        $model->path = 'uploads/';
        $model->type = $type;
        $model->size = $size;
        $model->modTime = date('Y-m-d H:i:s');
        $model->status = 0;
        $model->created_at = date('Y-m-d H:i:s');
        $model->updated_at = date('Y-m-d H:i:s');
        $model->save();

        return $path;
    }
}
