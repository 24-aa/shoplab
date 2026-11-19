<?php
require __DIR__ . '/../includes/bootstrap.php';

$user = require_admin();

$userCount = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$productCount = (int)db()->query('SELECT COUNT(*) FROM products')->fetchColumn();
$orderCount = (int)db()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$orderTotal = (float)db()->query('SELECT COALESCE(SUM(total), 0) FROM orders')->fetchColumn();

$pageTitle = '管理后台';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">管理后台</h1>

<div class="admin-layout">
  <aside class="side">
    <a class="active" href="/admin/index.php">控制台</a>
    <a href="/admin/labs.php">漏洞开关</a>
    <a href="/index.php">返回前台</a>
  </aside>

  <div>
    <div class="grid-2">
      <div class="card">
        <div class="hint">用户总数</div>
        <div class="summary-total"><?= $userCount ?></div>
      </div>
      <div class="card">
        <div class="hint">商品总数</div>
        <div class="summary-total"><?= $productCount ?></div>
      </div>
      <div class="card">
        <div class="hint">订单总数</div>
        <div class="summary-total"><?= $orderCount ?></div>
      </div>
      <div class="card">
        <div class="hint">订单总额</div>
        <div class="summary-total">¥<?= money($orderTotal) ?></div>
      </div>
    </div>

    <div class="card">
      <h2 class="card-title">靶场开关</h2>
      <p class="hint">V1 任意文件上传、V2 水平越权可在此开启或关闭，用于对比漏洞复现与修复效果。</p>
      <a class="btn btn-primary" href="/admin/labs.php">前往开关面板</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
