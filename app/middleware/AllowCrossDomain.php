<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

class AllowCrossDomain
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        // 获取请求的源头域
        $origin = $request->header('Origin');
        
        // 记录请求信息，帮助调试
        trace("收到请求: 源头={$origin}, 方法={$request->method()}", 'info');
        
        // 支持跨域请求
        $header = [
            'Access-Control-Allow-Origin'   => $origin ?: '*',
            'Access-Control-Allow-Methods'  => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers'  => 'Authorization, Content-Type, Accept, Origin, X-Requested-With',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'        => '86400',
        ];

        // 如果是OPTIONS请求，直接返回正确响应
        if ($request->method(true) == 'OPTIONS') {
            return Response::create()->code(204)->header($header);
        }

        // 正常请求，添加跨域头并继续
        $response = $next($request);
        
        // 添加响应头
        return $response->header($header);
    }
}