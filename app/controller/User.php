<?php
declare(strict_types=1);

namespace app\controller;

use app\common\JwtAuth;
use think\facade\Db;
use think\facade\Request;
use think\facade\Validate;
use think\facade\Cache;
use think\facade\Session;
use think\captcha\facade\Captcha;

class User{
    // 限制次数和时间窗口
    protected $maxAttempts = 5; // 最大尝试次数
    protected $decayMinutes = 1; // 时间窗口（分钟）

    /**
     * 检查请求频率限制
     * @param string $action 操作类型（login/register）
     * @return array|bool 如果超出限制返回错误信息数组，否则返回true
     */
    protected function checkRateLimit($action)
    {
        $ip = Request::ip();
        $key = "rate_limit:{$action}:{$ip}";
        
        // 获取当前请求次数
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $this->maxAttempts) {
            // 超出限制
            $retryAfter = 60; // 1分钟后重试
            return [
                'code' => 0,
                'msg' => "操作过于频繁，请{$retryAfter}秒后重试"
            ];
        }
        
        // 增加请求次数并设置过期时间
        Cache::set($key, $attempts + 1, $this->decayMinutes * 60);
        
        return true;
    }

    /**
     * 验证码校验
     * @param string $captcha 验证码
     * @return bool 验证成功返回true，失败返回false
     */
    protected function validateCaptcha($captcha)
    {
        if (empty($captcha)) {
            return false;
        }
        // 修改为使用ThinkPHP内置的验证码校验功能
        return Captcha::check($captcha);
    }
    
    public function Register()
    {
    
        // 检查频率限制
        $rateCheck = $this->checkRateLimit('register');
        if ($rateCheck !== true) {
            return json($rateCheck);
        }
        
        $data = Request::post();
        
        // 添加日志，查看接收到的数据
        trace("收到注册请求数据: " . json_encode($data), 'info');





        //验证规则
        $validate = Validate::rule([
            'username' => 'require|min:3|max:20|unique:users',
            'password' => 'require|min:6',
            'email' => 'require|email|unique:users',
            'captcha' => 'require|captcha'
        ]);

        //验证数据
        if(!$validate->check($data)){
            $error = $validate->getError();
            trace("验证失败: " . $error, 'error');
            return json(['code'=>0,'msg'=>$error]);
        }

        //密码加密
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        unset($data['captcha']);

        //写入数据库前记录日志
        trace("准备插入数据: " . json_encode(array_merge($data, ['password' => '[PROTECTED]'])), 'info');
        
        try {
            $userId = Db::name('users')->insertGetId($data);
            trace("插入成功，用户ID: " . $userId, 'info');
            return json(['code'=> 1,'msg'=>'注册成功','user_id'=>$userId]);
        } catch (\Exception $e) {
            trace("数据库插入失败: " . $e->getMessage(), 'error');
            return json(['code'=>0,'msg'=>'注册失败: ' . $e->getMessage()]);
        }
    }
    
    public function Login(){
        // 检查频率限制
        $rateCheck = $this->checkRateLimit('login');
        if ($rateCheck !== true) {
            return json($rateCheck);
        }
        
        $data = Request::post();
        if (!is_array($data)){
            $data = [];
        }
        
        // 添加日志，记录登录请求（不包含密码）
        if(isset($data['password'])) {
            $logData = array_merge($data, ['password' => '[PROTECTED]']);
            trace("收到登录请求数据: " . json_encode($logData), 'info');
        }

        //验证规则
        $validate = Validate::rule([
            'username' => 'require',
            'password' => 'require',
            'captcha' => 'require|captcha'
        ]);

        //验证数据
        if(!$validate->check($data)){
            trace("登录验证失败: " . $validate->getError(), 'error');
            return json(['code'=>0, 'msg'=>$validate->getError()]);
        }
        
        //查询用户
        $user = Db::name('users')->where('username',$data['username'])->find();
        if(!$user){
            trace("登录失败：用户不存在 - " . $data['username'], 'info');
            return json(['code'=>0,'msg'=>'用户不存在']);
        }

        //验证密码
        if (!password_verify($data['password'], $user['password'])) {
            trace("登录失败：密码错误 - 用户: " . $data['username'], 'info');
            return json(['code'=>0,'msg'=>'密码错误']);
        }

        //生成JWT
        $token = JwtAuth::createToken($user['id']);
        trace("登录成功：用户 " . $data['username'] . ", ID: " . $user['id'], 'info');

        return json(['code' => 1, 'msg' => '登录成功', 'token' => $token]);
    }
}