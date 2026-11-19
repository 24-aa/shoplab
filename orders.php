<?php
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();

// CSRF 令牌：删除使用 POST + 令牌校验，避免被跨站诱导删除
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// 处理删除订单
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)($_POST['delete_id'] ?? 0);
    $token = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '';
    if ($deleteId <= 0 || !hash_equals($csrfToken, $token)) {
        flash('删除请求无效，请重试', 'error');
        redirect('/orders.php');
    }
    // 安全写法：先校验订单归属，杜绝越权删除他人订单
    $st = db()->prepare('SELECT id FROM orders WHERE id = ? AND user_id = ?');
    $st->execute([$deleteId, $user['id']]);
    if ($st->fetch()) {
        try {
            db()->beginTransaction();
            db()->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$deleteId]);
            db()->prepare('DELETE FROM orders WHERE id = ? AND user_id = ?')->execute([$deleteId, $user['id']]);
            db()->commit();
            flash('订单已删除', 'success');
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            flash('删除失败，请稍后重试', 'error');
        }
    } else {
        flash('订单不存在或无权删除', 'error');
    }
    redirect('/orders.php');
}

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
            <td>
              <a href="/order.php?id=<?= (int)$o['id'] ?>">查看</a>
              <?php if ($o['status'] === '待发货'): ?>
                <form method="post" action="/cancel_order.php" style="display:inline"
                      onsubmit="return confirm('确定取消订单 <?= e($o['order_no']) ?> 吗？');">
                  <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                  <input type="hidden" name="csrf" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="back" value="/orders.php">
                  <button type="submit" class="btn btn-sm" style="margin-left:8px">取消</button>
                </form>
              <?php endif; ?>
              <form method="post" action="/orders.php" style="display:inline"
                    onsubmit="return confirm('确定删除订单 <?= e($o['order_no']) ?> 吗？此操作不可恢复。');">
                <input type="hidden" name="delete_id" value="<?= (int)$o['id'] ?>">
                <input type="hidden" name="csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="btn btn-sm btn-danger" style="margin-left:8px">删除</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
