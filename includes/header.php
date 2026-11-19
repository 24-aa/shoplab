<?php
$__user = current_user();
$__title = isset($pageTitle) ? $pageTitle : '';
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($__title !== '' ? $__title . ' · ' . config('app')['name'] : config('app')['name']) ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="topbar">
  <div class="container topbar-inner">
    <a class="brand" href="/index.php">星海商城</a>
    <form class="search" action="/index.php" method="get">
      <input class="input search-input" type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="搜索商品">
      <button class="btn btn-primary" type="submit">搜索</button>
    </form>
    <nav class="nav">
      <a href="/cart.php">购物车</a>
      <a href="/orders.php">我的订单</a>
      <?php if ($__user): ?>
        <a href="/profile.php"><?= e($__user['nickname']) ?></a>
        <?php if ($__user['role'] === 'admin'): ?><a href="/admin/index.php">后台</a><?php endif; ?>
        <a href="/logout.php">退出</a>
      <?php else: ?>
        <a href="/login.php">登录</a>
        <a href="/register.php">注册</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container main">
<?php if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'index.php'): ?>
<div class="back-bar">
  <a class="btn btn-sm" href="/index.php" onclick="if (history.length > 1) { history.back(); return false; }">← 返回</a>
</div>
<?php endif; ?>
<?php foreach (flash_pull() as $__f): ?>
  <div class="alert alert-<?= e($__f['type']) ?>"><?= e($__f['msg']) ?></div>
<?php endforeach; ?>
