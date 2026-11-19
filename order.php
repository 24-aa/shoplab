<?php
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$id = get_int('id');

if (lab_enabled('idor')) {
    // V2 漏洞分支：只按订单 id 查询，忽略归属
    $st = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $st->execute([$id]);
} else {
    // 安全分支：订单必须属于当前用户
    $st = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
    $st->execute([$id, $user['id']]);
}
$order = $st->fetch();
$pwned = $order && lab_enabled('idor') && (int)$order['user_id'] !== (int)$user['id'];

if (!$order) {
    http_response_code(404);
    $pageTitle = '订单不存在';
    include __DIR__ . '/includes/header.php';
    echo '<div class="card empty">订单不存在，<a href="/orders.php">返回订单列表</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$st = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
$st->execute([$id]);
$items = $st->fetchAll();

$pageTitle = '订单详情';
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">订单详情</h1>

<?php if ($pwned): ?>
  <div class="alert alert-success">攻破成功！V2 水平越权：你查看了不属于自己的订单。</div>
<?php endif; ?>

<div class="grid-side">
  <div>
    <div class="card">
      <h2 class="card-title">商品清单</h2>
      <table class="table">
        <thead>
          <tr>
            <th>商品</th>
            <th class="num">单价</th>
            <th class="num">数量</th>
            <th class="num">小计</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= e($it['name']) ?></td>
              <td class="num">¥<?= money($it['price']) ?></td>
              <td class="num"><?= (int)$it['qty'] ?></td>
              <td class="num">¥<?= money((float)$it['price'] * (int)$it['qty']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="summary-row summary-total">
        <span>合计</span>
        <span>¥<?= money($order['total']) ?></span>
      </div>
    </div>

    <div class="card">
      <h2 class="card-title">收货信息</h2>
      <div class="summary-row"><span>收货人</span><span><?= e($order['receiver']) ?></span></div>
      <div class="summary-row"><span>联系电话</span><span><?= e($order['phone']) ?></span></div>
      <div class="summary-row"><span>收货地址</span><span><?= e($order['address']) ?></span></div>
    </div>
  </div>

  <div class="card">
    <h2 class="card-title">订单信息</h2>
    <div class="summary-row"><span>订单号</span><span><?= e($order['order_no']) ?></span></div>
    <div class="summary-row"><span>下单时间</span><span><?= e($order['created_at']) ?></span></div>
    <div class="summary-row"><span>订单状态</span><span class="badge"><?= e($order['status']) ?></span></div>
    <div class="summary-row"><span>订单金额</span><span>¥<?= money($order['total']) ?></span></div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
