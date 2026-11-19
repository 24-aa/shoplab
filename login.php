<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('/index.php');
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = '请输入用户名和密码';
    } else {
        $st = db()->prepare('SELECT id, password FROM users WHERE username = ?');
        $st->execute([$username]);
        $row = $st->fetch();
        if ($row && password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$row['id'];
            flash('登录成功，欢迎回来');
            redirect('/index.php');
        }
        $errors[] = '用户名或密码错误';
    }
}

$pageTitle = '登录';
include __DIR__ . '/includes/header.php';
?>
<div class="card auth">
  <h1 class="page-title">登录</h1>
  <?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
  <?php endforeach; ?>
  <form method="post" action="/login.php">
    <div class="field">
      <label>用户名</label>
      <input class="input" type="text" name="username" value="<?= e($username) ?>" autocomplete="username">
    </div>
    <div class="field">
      <label>密码</label>
      <input class="input" type="password" name="password" autocomplete="current-password">
    </div>
    <button class="btn btn-primary btn-block" type="submit">登录</button>
  </form>
  <p class="hint">还没有账号？<a href="/register.php">注册</a></p>
  <p class="hint">测试账号：管理员 / 账号1 / 账号2，密码均为 123456</p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
