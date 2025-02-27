<?php
// 中间件配置

return [
    // 全局中间件
    'global' => [
        \app\middleware\AllowCrossDomain::class,
        \think\middleware\SessionInit::class,
    ],
    
    // 别名或分组
    'alias' => [
        'cors' => \app\middleware\AllowCrossDomain::class,
    ],
    
    // 优先级设置
    'priority' => [
        \app\middleware\AllowCrossDomain::class,
        \think\middleware\SessionInit::class,
    ],
];