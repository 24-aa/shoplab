<?php
require __DIR__ . '/../includes/bootstrap.php';

$user = require_admin();

$labs = [
    'upload' => ['name' => 'V1 任意文件上传', 'code' => 'profile.php 头像上传'],
    'idor'   => ['name' => 'V2 水平越权',     'code' => 'order.php 详情 / cancel_order.php 取消'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab = (string)($_POST['lab'] ?? '');
    $on = (string)($_POST['state'] ?? '') === 'on';

    if (!isset($labs[$lab])) {
        flash('未知的漏洞开关', 'error');
    } else {
        lab_set($lab, $on);
        flash($labs[$lab]['name'] . '已' . ($on ? '开启' : '关闭'));
    }
    redirect('/admin/labs.php');
}

$pageTitle = '漏洞开关';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">管理后台</h1>

<div class="admin-layout">
  <aside class="side">
    <a href="/admin/index.php">控制台</a>
    <a class="active" href="/admin/labs.php">漏洞开关</a>
    <a href="/index.php">返回前台</a>
  </aside>

  <div class="card">
    <h2 class="card-title">漏洞开关</h2>
    <p class="hint">开启后按不安全写法运行，关闭后按安全写法运行。</p>

    <?php foreach ($labs as $key => $meta): $on = lab_enabled($key); ?>
      <div class="lab-row">
        <div>
          <div class="lab-name"><?= e($meta['name']) ?></div>
          <div class="lab-code"><?= e($meta['code']) ?></div>
        </div>
        <form class="switch-form" method="post" action="/admin/labs.php">
          <input type="hidden" name="lab" value="<?= e($key) ?>">
          <input type="hidden" name="state" value="<?= $on ? 'off' : 'on' ?>">
          <span class="switch-state <?= $on ? '' : 'off' ?>"><?= $on ? '已开启' : '已关闭' ?></span>
          <button class="btn btn-sm <?= $on ? 'btn-danger' : 'btn-primary' ?>" type="submit">
            <?= $on ? '关闭漏洞' : '开启漏洞' ?>
          </button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
