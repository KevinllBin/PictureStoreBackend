<?php
use think\facade\Route;
use app\controller\User;

Route::get('think', function () {
    return 'hello,ThinkPHP8!';
});

Route::get('hello/:name', 'index/hello');
Route::get('captcha', 'Verify/generate');
Route::post('user/register', [User::class, 'register']);
Route::post('user/login', [User::class, 'login']);