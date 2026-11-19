<?php
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();

// CSRF 令牌：与订单列表页共用同一会话令牌
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// 取消订单只接受 POST + 令牌校验
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/orders.php');
}

$orderId = (int)($_POST['order_id'] ?? 0);
$token = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '';
$back  = (string)($_POST['back'] ?? '/orders.php');
// 仅允许站内相对路径回跳，避免开放重定向
if ($back === '' || $back[0] !== '/' || str_starts_with($back, '//') || str_contains($back, '\\')) {
    $back = '/orders.php';
}

if ($orderId <= 0 || !hash_equals($csrfToken, $token)) {
    flash('取消请求无效，请重试', 'error');
    redirect($back);
}

if (lab_enabled('idor')) {
    // V2 漏洞分支：只按订单 id 查询，忽略归属 → 可取消他人订单（水平越权）
    $st = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $st->execute([$orderId]);
} else {
    // 安全分支：订单必须属于当前用户
    $st = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
    $st->execute([$orderId, $user['id']]);
}
$order = $st->fetch();

if (!$order) {
    flash('订单不存在或无权操作', 'error');
    redirect($back);
}
if ($order['status'] !== '待发货') {
    flash('该订单状态不允许取消', 'error');
    redirect($back);
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    // 回滚库存与销量：把该订单商品数量加回
    $items = $pdo->prepare('SELECT product_id, qty FROM order_items WHERE order_id = ?');
    $items->execute([$orderId]);
    foreach ($items->fetchAll() as $it) {
        $pdo->prepare('UPDATE products SET stock = stock + ?, sales = sales - ? WHERE id = ?')
            ->execute([(int)$it['qty'], (int)$it['qty'], (int)$it['product_id']]);
    }

    // 更新订单状态为已取消（安全分支再次带上归属条件）
    if (lab_enabled('idor')) {
        $pdo->prepare("UPDATE orders SET status = '已取消' WHERE id = ?")
            ->execute([$orderId]);
    } else {
        $pdo->prepare("UPDATE orders SET status = '已取消' WHERE id = ? AND user_id = ?")
            ->execute([$orderId, $user['id']]);
    }

    $pdo->commit();
    flash('订单 ' . $order['order_no'] . ' 已取消', 'success');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash('取消失败，请稍后重试', 'error');
}

redirect($back);
