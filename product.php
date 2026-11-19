<?php
require __DIR__ . '/includes/bootstrap.php';

$id = get_int('id');
$st = db()->prepare('SELECT * FROM products WHERE id = ? AND status = 1');
$st->execute([$id]);
$product = $st->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = '商品不存在';
    include __DIR__ . '/includes/header.php';
    echo '<div class="card empty">商品不存在或已下架，<a href="/index.php">返回商品列表</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$stock = (int)$product['stock'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = (int)($_POST['qty'] ?? 1);
    $qty = max(1, $qty);
    $inCart = (int)($_SESSION['cart'][$id] ?? 0);

    if ($stock <= 0) {
        flash('该商品暂时缺货', 'error');
    } elseif ($inCart + $qty > $stock) {
        flash('库存不足，该商品最多可购买 ' . $stock . ' 件', 'error');
    } else {
        $_SESSION['cart'][$id] = $inCart + $qty;
        flash('已加入购物车');
    }
}

$pageTitle = $product['name'];
include __DIR__ . '/includes/header.php';
?>
<div class="detail">
  <div class="detail-thumb"><?= e($product['icon'] !== '' ? $product['icon'] : mb_substr($product['name'], 0, 1)) ?></div>
  <div class="detail-info">
    <h1><?= e($product['name']) ?></h1>
    <p><?= e($product['description']) ?></p>
    <div class="price"><small>¥</small><?= money($product['price']) ?></div>
    <p>库存 <?= $stock ?> 件 · 已售 <?= (int)$product['sales'] ?> 件</p>

    <?php if ($stock > 0): ?>
      <form method="post" action="/product.php?id=<?= (int)$product['id'] ?>" class="row">
        <div class="field" style="margin:0">
          <label for="qty">数量</label>
          <input class="input qty-input" id="qty" type="number" name="qty" value="1" min="1" max="<?= $stock ?>">
        </div>
        <input type="hidden" name="direct" value="1">
        <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
        <button class="btn btn-primary" type="submit" style="margin-top:20px">加入购物车</button>
        <button class="btn" type="submit" formmethod="get" formaction="/checkout.php"
                style="margin-top:20px;margin-left:8px;color:#fff;background:#e8912d;border-color:#e8912d">立即购买</button>
      </form>
    <?php else: ?>
      <button class="btn" type="button" disabled>暂时缺货</button>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
