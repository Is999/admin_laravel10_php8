<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class Lang
{
    /**
     * 处理传入的请求。
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // 获取请求头中的语言
        $lang = $request->header('X-Language');

        // 获取请求主体中的语言
        if (empty($lang)) {
            $lang = $request->input('language', 'zh');
        }

        // 匹配语言，进行语言设置
        App::setLocale($lang);

        $response = $next($request);

        // 设置请求的语言到响应
        $response->header('X-Language', $lang);

        return $response;
    }
}
