<?php
require __DIR__ . '/includes/bootstrap.php';

$q   = trim((string)($_GET['q'] ?? ''));
$cat = get_int('cat');
$sort = (string)($_GET['sort'] ?? 'new');

$sortMap = [
    'new'        => 'id DESC',
    'price_asc'  => 'price ASC',
    'price_desc' => 'price DESC',
    'sales'      => 'sales DESC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['new'];
$sort = isset($sortMap[$sort]) ? $sort : 'new';

$sql = 'SELECT p.*, c.name AS category_name FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.status = 1';
$params = [];
if ($q !== '') {
    $sql .= ' AND p.name LIKE ?';
    $params[] = '%' . $q . '%';
}
if ($cat > 0) {
    $sql .= ' AND p.category_id = ?';
    $params[] = $cat;
}
$sql .= ' ORDER BY p.' . $orderBy;

$st = db()->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();

$categories = db()->query('SELECT * FROM categories ORDER BY id')->fetchAll();

$pageTitle = '商品列表';
include __DIR__ . '/includes/header.php';
?>
<div class="row-between" style="margin-bottom:16px">
  <h1 class="page-title" style="margin:0">全部商品</h1>
  <span class="hint">共 <?= count($products) ?> 件</span>
</div>

<div class="filters">
  <a class="chip <?= $cat === 0 ? 'active' : '' ?>" href="/index.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>">全部</a>
  <?php foreach ($categories as $c): ?>
    <a class="chip <?= $cat === (int)$c['id'] ? 'active' : '' ?>"
       href="/index.php?cat=<?= (int)$c['id'] ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
</div>

<div class="sort-bar">
  <span>排序：</span>
  <?php
  $sorts = ['new' => '最新', 'price_asc' => '价格从低到高', 'price_desc' => '价格从高到低', 'sales' => '销量'];
  foreach ($sorts as $key => $label):
      $url = '/index.php?sort=' . $key . ($cat > 0 ? '&cat=' . $cat : '') . ($q !== '' ? '&q=' . urlencode($q) : '');
  ?>
    <a class="<?= $sort === $key ? 'active' : '' ?>" href="<?= e($url) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$products): ?>
  <div class="card empty">没有找到符合条件的商品</div>
<?php else: ?>
  <div class="products">
    <?php foreach ($products as $p): ?>
      <a class="product" href="/product.php?id=<?= (int)$p['id'] ?>">
        <div class="thumb"><?= e($p['icon'] !== '' ? $p['icon'] : mb_substr($p['name'], 0, 1)) ?></div>
        <div class="product-body">
          <div class="product-name"><?= e($p['name']) ?></div>
          <div class="product-desc"><?= e($p['description']) ?></div>
          <div class="product-sub">
            <span class="badge badge-muted"><?= e($p['category_name'] ?: '未分类') ?></span>
            <span class="stock">已售 <?= (int)$p['sales'] ?></span>
          </div>
          <div class="product-meta">
            <span class="price"><small>¥</small><?= money($p['price']) ?></span>
            <span class="stock">库存 <?= (int)$p['stock'] ?></span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
