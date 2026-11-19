<?php
// 配置读取
function config(?string $key = null)
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config.php';
    }
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? null;
}

// 输出转义，全站唯一出口
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// 站点内跳转
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

// 一次性提示
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['msg' => $message, 'type' => $type];
}

function flash_pull(): array
{
    $list = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $list;
}

// 当前登录用户
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $uid = (int)($_SESSION['uid'] ?? 0);
    if ($uid <= 0) {
        return $user = null;
    }
    $st = db()->prepare('SELECT id, username, nickname, email, avatar, role, created_at FROM users WHERE id = ?');
    $st->execute([$uid]);
    $row = $st->fetch();
    return $user = ($row ?: null);
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

function require_login(): array
{
    $u = current_user();
    if ($u === null) {
        flash('请先登录', 'error');
        redirect('/login.php');
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        echo '<!doctype html><meta charset="utf-8"><title>403</title>'
            . '<body style="font-family:sans-serif;padding:60px;text-align:center">'
            . '<h1>403</h1><p>没有访问权限</p><p><a href="/index.php">返回首页</a></p></body>';
        exit;
    }
    return $u;
}

// 漏洞开关
function labs(): array
{
    static $labs = null;
    if ($labs === null) {
        $file = dirname(__DIR__) . '/data/labs.json';
        $labs = is_file($file) ? (json_decode((string)file_get_contents($file), true) ?: []) : [];
    }
    return $labs;
}

function lab_enabled(string $name): bool
{
    return (bool)(labs()[$name] ?? false);
}

function lab_set(string $name, bool $on): void
{
    $file = dirname(__DIR__) . '/data/labs.json';
    $labs = labs();
    $labs[$name] = $on;
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0777, true);
    }
    file_put_contents($file, json_encode($labs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// 金额格式化
function money($value): string
{
    return number_format((float)$value, 2);
}

// 生成订单号
function order_no(): string
{
    return date('YmdHis') . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

// 接受 GET 整数参数
function get_int(string $key, int $default = 0): int
{
    $v = $_GET[$key] ?? null;
    return ($v === null || $v === '') ? $default : (int)$v;
}
