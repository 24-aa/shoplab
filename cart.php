<?php
require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $pid = (int)($_POST['id'] ?? 0);

    if ($action === 'update') {
        $qty = (int)($_POST['qty'] ?? 0);
        $st = db()->prepare('SELECT stock, status FROM products WHERE id = ?');
        $st->execute([$pid]);
        $p = $st->fetch();

        if (!$p || (int)$p['status'] !== 1) {
            unset($_SESSION['cart'][$pid]);
            flash('商品已下架，已从购物车移除', 'error');
        } elseif ($qty <= 0) {
            unset($_SESSION['cart'][$pid]);
            flash('已移除该商品');
        } elseif ($qty > (int)$p['stock']) {
            $_SESSION['cart'][$pid] = (int)$p['stock'];
            flash('库存不足，数量已调整为 ' . (int)$p['stock'] . ' 件', 'error');
        } else {
            $_SESSION['cart'][$pid] = $qty;
            flash('数量已更新');
        }
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$pid]);
        flash('已移除该商品');
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        flash('购物车已清空');
    }

    redirect('/cart.php');
}

$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0.0;

if ($cart) {
    $ids = array_map('intval', array_keys($cart));
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("SELECT * FROM products WHERE status = 1 AND id IN ($in) ORDER BY id");
    $st->execute($ids);
    $rows = $st->fetchAll();

    foreach ($rows as $p) {
        $qty = (int)$cart[$p['id']];
        $subtotal = (float)$p['price'] * $qty;
        $p['qty'] = $qty;
        $p['subtotal'] = $subtotal;
        $items[] = $p;
        $total += $subtotal;
    }

    // 清理已下架或不存在的商品
    $valid = array_column($rows, 'id');
    foreach ($ids as $sid) {
        if (!in_array($sid, array_map('intval', $valid), true)) {
            unset($_SESSION['cart'][$sid]);
        }
    }
}

$pageTitle = '购物车';
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">购物车</h1>

<?php if (!$items): ?>
  <div class="card empty">
    购物车是空的，<a href="/index.php">去逛逛</a>
  </div>
<?php else: ?>
  <div class="grid-side">
    <div class="card" style="padding:0">
      <table class="table">
        <thead>
          <tr>
            <th>商品</th>
            <th class="num">单价</th>
            <th>数量</th>
            <th class="num">小计</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $p): ?>
            <tr>
              <td><a href="/product.php?id=<?= (int)$p['id'] ?>"><?= e($p['name']) ?></a></td>
              <td class="num">¥<?= money($p['price']) ?></td>
              <td>
                <form class="qty-form" method="post" action="/cart.php">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <input class="input qty-input" type="number" name="qty" value="<?= (int)$p['qty'] ?>" min="1" max="<?= (int)$p['stock'] ?>">
                  <button class="btn btn-sm" type="submit">更新</button>
                </form>
              </td>
              <td class="num">¥<?= money($p['subtotal']) ?></td>
              <td>
                <form method="post" action="/cart.php">
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="btn btn-sm btn-danger" type="submit">删除</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2 class="card-title">结算</h2>
      <div class="summary-row">
        <span>商品件数</span>
        <span><?= (int)array_sum(array_column($items, 'qty')) ?> 件</span>
      </div>
      <div class="summary-row">
        <span>商品金额</span>
        <span>¥<?= money($total) ?></span>
      </div>
      <div class="summary-row summary-total">
        <span>合计</span>
        <span>¥<?= money($total) ?></span>
      </div>
      <a class="btn btn-primary btn-block" href="/checkout.php" style="margin-top:12px">去结算</a>
      <form method="post" action="/cart.php" style="margin-top:10px">
        <input type="hidden" name="action" value="clear">
        <button class="btn btn-block" type="submit">清空购物车</button>
      </form>
    </div>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
