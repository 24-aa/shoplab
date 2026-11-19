<?php
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();

$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
    flash('购物车是空的，请先挑选商品', 'error');
    redirect('/cart.php');
}

$items = [];
$total = 0.0;
$ids = array_map('intval', array_keys($cart));
$in = implode(',', array_fill(0, count($ids), '?'));
$st = db()->prepare("SELECT * FROM products WHERE status = 1 AND id IN ($in) ORDER BY id");
$st->execute($ids);
foreach ($st->fetchAll() as $p) {
    $qty = (int)$cart[$p['id']];
    $p['qty'] = $qty;
    $p['subtotal'] = (float)$p['price'] * $qty;
    $items[] = $p;
    $total += $p['subtotal'];
}

if (!$items) {
    $_SESSION['cart'] = [];
    flash('购物车中的商品均已下架', 'error');
    redirect('/cart.php');
}

$errors = [];
$receiver = $user['nickname'];
$phone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver = trim((string)($_POST['receiver'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));

    if ($receiver === '' || mb_strlen($receiver) > 50) {
        $errors[] = '请填写收货人（不超过 50 字）';
    }
    if ($phone === '' || mb_strlen($phone) > 30) {
        $errors[] = '请填写联系电话';
    }
    if ($address === '' || mb_strlen($address) > 200) {
        $errors[] = '请填写收货地址（不超过 200 字）';
    }

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            $orderItems = [];
            $total = 0.0;

            foreach ($_SESSION['cart'] as $pid => $qty) {
                $pid = (int)$pid;
                $qty = (int)$qty;
                if ($qty <= 0) {
                    continue;
                }

                $q = $pdo->prepare('SELECT id, name, price, stock, status FROM products WHERE id = ? FOR UPDATE');
                $q->execute([$pid]);
                $p = $q->fetch();

                if (!$p || (int)$p['status'] !== 1) {
                    throw new RuntimeException('部分商品已下架，请返回购物车调整');
                }
                if ((int)$p['stock'] < $qty) {
                    throw new RuntimeException('“' . $p['name'] . '”库存不足，仅剩 ' . (int)$p['stock'] . ' 件');
                }

                $up = $pdo->prepare('UPDATE products SET stock = stock - ?, sales = sales + ? WHERE id = ? AND stock >= ?');
                $up->execute([$qty, $qty, $pid, $qty]);
                if ($up->rowCount() === 0) {
                    throw new RuntimeException('“' . $p['name'] . '”库存不足，请调整数量');
                }

                $orderItems[] = ['id' => $pid, 'name' => $p['name'], 'price' => (float)$p['price'], 'qty' => $qty];
                $total += (float)$p['price'] * $qty;
            }

            if (!$orderItems) {
                throw new RuntimeException('购物车是空的');
            }

            $no = order_no();
            $ins = $pdo->prepare('INSERT INTO orders (order_no, user_id, total, status, receiver, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $ins->execute([$no, (int)$user['id'], $total, '待发货', $receiver, $phone, $address]);
            $orderId = (int)$pdo->lastInsertId();

            $insi = $pdo->prepare('INSERT INTO order_items (order_id, product_id, name, price, qty) VALUES (?, ?, ?, ?, ?)');
            foreach ($orderItems as $it) {
                $insi->execute([$orderId, $it['id'], $it['name'], $it['price'], $it['qty']]);
            }

            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            flash($ex->getMessage(), 'error');
            redirect('/checkout.php');
        }

        $_SESSION['cart'] = [];
        flash('下单成功，订单号 ' . $no);
        redirect('/order.php?id=' . $orderId);
    }
}

$pageTitle = '确认订单';
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">确认订单</h1>

<div class="grid-side">
  <div class="card">
    <h2 class="card-title">收货信息</h2>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>
    <form method="post" action="/checkout.php">
      <div class="field">
        <label for="receiver">收货人</label>
        <input class="input" id="receiver" type="text" name="receiver" value="<?= e($receiver) ?>" maxlength="50" required>
      </div>
      <div class="field">
        <label for="phone">联系电话</label>
        <input class="input" id="phone" type="text" name="phone" value="<?= e($phone) ?>" maxlength="30" required>
      </div>
      <div class="field">
        <label for="address">收货地址</label>
        <textarea id="address" name="address" maxlength="200" required><?= e($address) ?></textarea>
      </div>
      <button class="btn btn-primary" type="submit">提交订单</button>
    </form>
  </div>

  <div class="card">
    <h2 class="card-title">订单明细</h2>
    <?php foreach ($items as $p): ?>
      <div class="summary-row">
        <span><?= e($p['name']) ?> × <?= (int)$p['qty'] ?></span>
        <span>¥<?= money($p['subtotal']) ?></span>
      </div>
    <?php endforeach; ?>
    <div class="summary-row summary-total">
      <span>合计</span>
      <span>¥<?= money($total) ?></span>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
