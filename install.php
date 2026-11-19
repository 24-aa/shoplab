<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
$db = $config['db'];
$log = [];
$error = null;

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=%s', $db['host'], $db['port'], $db['charset']),
        $db['user'],
        $db['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db['name']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db['name']}`");
    $log[] = '数据库 shoplab 已就绪';

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        nickname VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL DEFAULT '',
        avatar VARCHAR(255) NOT NULL DEFAULT '',
        role ENUM('user','admin') NOT NULL DEFAULT 'user',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        category_id INT UNSIGNED NOT NULL DEFAULT 0,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        stock INT NOT NULL DEFAULT 0,
        description VARCHAR(255) NOT NULL DEFAULT '',
        icon VARCHAR(20) NOT NULL DEFAULT '',
        sales INT NOT NULL DEFAULT 0,
        status TINYINT NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_category (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        order_no VARCHAR(32) NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status VARCHAR(20) NOT NULL DEFAULT '待发货',
        receiver VARCHAR(50) NOT NULL,
        phone VARCHAR(30) NOT NULL,
        address VARCHAR(200) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_user (user_id),
        UNIQUE KEY uk_order_no (order_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        qty INT NOT NULL DEFAULT 1,
        PRIMARY KEY (id),
        KEY idx_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = '5 张数据表已创建';

    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
        $hash = password_hash('123456', PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO users (username, password, nickname, email, role) VALUES (?, ?, ?, ?, ?)');
        $st->execute(['admin', $hash, '管理员', 'admin@shoplab.local', 'admin']);
        $st->execute(['alice', $hash, '爱丽丝', 'alice@shoplab.local', 'user']);
        $st->execute(['bob', $hash, '鲍勃', 'bob@shoplab.local', 'user']);
        $log[] = '种子账号已写入：admin / alice / bob（密码 123456）';

        $pdo->exec("INSERT INTO categories (name) VALUES ('数码影音'), ('家居生活'), ('办公外设')");

        $items = [
            [1, '星海无线降噪耳机', 699.00, 20, '主动降噪，续航 30 小时', '耳', 120],
            [3, '星海机械键盘', 399.00, 35, '87 键青轴，RGB 背光', '键', 86],
            [1, '桌面蓝牙音箱', 259.00, 40, '双单元立体声，支持 TF 卡', '音', 64],
            [2, '护眼台灯', 129.00, 50, '无频闪，三档色温', '灯', 152],
            [1, '便携充电宝', 199.00, 60, '20000mAh，双向快充', '电', 210],
            [3, '人体工学鼠标', 159.00, 45, '垂直握持，静音按键', '鼠', 97],
            [2, '香薰加湿器', 189.00, 30, '静音雾化，七彩夜灯', '湿', 41],
            [3, '双肩电脑包', 229.00, 25, '15.6 英寸，防泼水面料', '包', 73],
        ];
        $st = $pdo->prepare('INSERT INTO products (category_id, name, price, stock, description, icon, sales) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $it) {
            $st->execute($it);
        }
        $log[] = '种子商品已写入：8 件';
    } else {
        $log[] = '检测到已有数据，跳过种子写入';
    }

    $labsFile = __DIR__ . '/data/labs.json';
    if (!is_dir(dirname($labsFile))) {
        mkdir(dirname($labsFile), 0777, true);
    }
    if (!is_file($labsFile)) {
        file_put_contents($labsFile, json_encode($config['lab_defaults'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    $log[] = '漏洞开关文件 data/labs.json 已就绪';

    $uploadDir = __DIR__ . '/' . $config['upload_dir'];
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $log[] = '头像目录 ' . $config['upload_dir'] . ' 已就绪';
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<title>安装 · 星海商城 ShopLab</title>
<style>
body{font:14px/1.7 "Microsoft YaHei",Arial,sans-serif;background:#f7f7f8;color:#171717;padding:48px 20px}
.box{max-width:640px;margin:0 auto;background:#fff;border:1px solid #e3e4e8;border-radius:12px;padding:28px}
h1{font-size:20px;margin:0 0 16px}
li{margin:4px 0}
.err{color:#d93a2f}
a{color:#0b6b3d}
</style>
</head>
<body>
<div class="box">
  <h1>星海商城 ShopLab 安装</h1>
  <?php if ($error !== null): ?>
    <p class="err">安装失败：<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <p>请检查 config.php 中的数据库账号与密码，确认 MySQL 已启动。</p>
  <?php else: ?>
    <ul>
      <?php foreach ($log as $line): ?>
        <li><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
    <p>安装完成。<a href="/index.php">进入首页</a> · <a href="/login.php">登录</a></p>
    <p style="color:#5b5b63">种子账号：admin / alice / bob，密码均为 123456。</p>
  <?php endif; ?>
</div>
</body>
</html>
