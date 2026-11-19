<?php
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();

$st = db()->prepare(
    'SELECT o.*,
            (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS kinds,
            (SELECT COALESCE(SUM(i.qty), 0) FROM order_items i WHERE i.order_id = o.id) AS pieces
     FROM orders o
     WHERE o.user_id = ?
     ORDER BY o.id DESC'
);
$st->execute([$user['id']]);
$orders = $st->fetchAll();

$pageTitle = '我的订单';
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">我的订单</h1>

<?php if (!$orders): ?>
  <div class="card empty">还没有订单，<a href="/index.php">去逛逛</a></div>
<?php else: ?>
  <div class="card" style="padding:0">
    <table class="table">
      <thead>
        <tr>
          <th>订单号</th>
          <th>下单时间</th>
          <th class="num">件数</th>
          <th class="num">金额</th>
          <th>状态</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><?= e($o['order_no']) ?></td>
            <td><?= e($o['created_at']) ?></td>
            <td class="num"><?= (int)$o['pieces'] ?></td>
            <td class="num">¥<?= money($o['total']) ?></td>
            <td><span class="badge"><?= e($o['status']) ?></span></td>
            <td><a href="/order.php?id=<?= (int)$o['id'] ?>">查看</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
