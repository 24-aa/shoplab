<?php
// 全局配置
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'shoplab',
        'user'    => 'root',
        'pass'    => 'root',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name'  => '星海商城',
        'debug' => false,
    ],
    // 头像上传目录，相对站点根
    'upload_dir' => 'uploads/avatars',
    // 漏洞开关默认值，install.php 写入 data/labs.json
    'lab_defaults' => [
        'upload' => true,
        'idor'   => true,
    ],
];
