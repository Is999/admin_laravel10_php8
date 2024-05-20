<?php

namespace App\Http\Controllers;

use App\Enum\Code;
use App\Enum\LogChannel;
use App\Enum\UserAction;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Services\ResponseService as Response;
use App\Services\FilesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class UploadController extends Controller
{
    /**
     * 上传图片
     * @param Request $request
     * @return JsonResponse
     */
    public function image(Request $request): JsonResponse
    {
        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');

                // 验证文件类型
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']; // 允许的图片类型

                $uploadService = new FilesService($allowedTypes);
                $path = $uploadService->upload($file);

                // 记录操作日志
                $this->addUserLog(__FUNCTION__, UserAction::UPLOAD_FILES, '上传图片', [
                    'original' => $file->getClientOriginalName(),
                    'new' => $path,
                ]);

                return Response::success(['url'=>$path], Code::S1001);
            }else{
                throw new CustomizeException(Code::E100062, ['param' => 'file']);
            }
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }


    /**
     * 批量上传图片
     * @param Request $request
     * @return JsonResponse
     */
    public function images(Request $request): JsonResponse
    {
        try {
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                // 验证文件类型
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']; // 允许的图片类型
                $uploadService = new FilesService($allowedTypes);
                $originals = [];
                $paths = [];
                foreach ($files as $file) {
                    $originals[] =$file->getClientOriginalName();
                    $paths[] = $uploadService->upload($file);
                }

                // 记录操作日志
                $this->addUserLog(__FUNCTION__, UserAction::UPLOAD_FILES, '批量上传图片', [
                    'original' => $originals,
                    'new' => $paths,
                ]);

                return Response::success(['url'=>$paths], Code::S1001);
            }else{
                throw new CustomizeException(Code::E100062, ['param' => 'file']);
            }
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 上传文件
     * @param Request $request
     * @return JsonResponse
     */
    public function file(Request $request): JsonResponse
    {
        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');

                $uploadService = new FilesService;
                $path = $uploadService->upload($file);

                // 记录操作日志
                $this->addUserLog(__FUNCTION__, UserAction::UPLOAD_FILES, '上传文件', [
                    'original' => $file->getClientOriginalName(),
                    'new' => $path,
                ]);

                return Response::success(['url'=>$path], Code::S1001);
            }else{
                throw new CustomizeException(Code::E100062, ['param' => 'file']);
            }
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 批量上传文件
     * @param Request $request
     * @return JsonResponse
     */
    public function files(Request $request): JsonResponse
    {
        try {
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $uploadService = new FilesService;
                $originals = [];
                $paths = [];
                foreach ($files as $file) {
                    $originals[] =$file->getClientOriginalName();
                    $paths[] = $uploadService->upload($file);
                }

                // 记录操作日志
                $this->addUserLog(__FUNCTION__, UserAction::UPLOAD_FILES, '批量上传文件', [
                    'original' => $originals,
                    'new' => $paths,
                ]);

                return Response::success(['url'=>$paths], Code::S1001);
            }else{
                throw new CustomizeException(Code::E100062, ['param' => 'file']);
            }
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

}