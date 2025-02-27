<?php
declare(strict_types=1);

namespace app\common;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Config;


class JwtAuth{

    public static function createToken($userId)
    {
        $key = Config::get('jwt.secret');
        $expire = Config::get('jwt.expire');

        $payload = [
            'iss' => 'thinkphp',
            'iat' => time(),
            'exp' => time() + $expire,
            'uid' => $userId,
        ];

        return JWT::encode($payload, $key, 'HS256');
    }

    public static function verifyToken($token)
    {
        try{
            $key = Config::get('jwt.secret');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return (array) $decoded;
        }catch (\Exception $e){
            return false; //无效Token
        }
    }
}