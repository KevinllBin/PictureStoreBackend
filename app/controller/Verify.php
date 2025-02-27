<?php
declare(strict_types=1);

namespace app\controller;

use think\captcha\facade\Captcha;
use think\facade\Request;
use think\Response;

class Verify
{
    /**
     * 生成验证码
     * 
     * @return \think\Response
     */
    public function generate()
    {
        // 直接使用ThinkPHP的验证码生成功能
        return Captcha::create();
    }
    
    /**
     * 静态检查方法 - 直接调用ThinkPHP的验证码校验
     * @param string $code 验证码
     * @return bool
     */
    public static function check($code)
    {
        return Captcha::check($code);
    }
}