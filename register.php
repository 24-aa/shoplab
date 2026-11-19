<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('/index.php');
}

$errors = [];
$username = '';
$nickname = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $nickname = trim((string)($_POST['nickname'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $errors[] = '用户名需为 3-20 位字母、数字或下划线';
    }
    if (mb_strlen($nickname) < 1 || mb_strlen($nickname) > 20) {
        $errors[] = '昵称长度需为 1-20 个字符';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '邮箱格式不正确';
    }
    if (strlen($password) < 6) {
        $errors[] = '密码至少 6 位';
    }
    if ($password !== $confirm) {
        $errors[] = '两次输入的密码不一致';
    }

    if (!$errors) {
        $st = db()->prepare('SELECT id FROM users WHERE username = ?');
        $st->execute([$username]);
        if ($st->fetch()) {
            $errors[] = '该用户名已被注册';
        }
    }

    if (!$errors) {
        $st = db()->prepare('INSERT INTO users (username, password, nickname, email, role) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$username, password_hash($password, PASSWORD_DEFAULT), $nickname, $email, 'user']);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)db()->lastInsertId();
        flash('注册成功，已自动登录');
        redirect('/index.php');
    }
}

$pageTitle = '注册';
include __DIR__ . '/includes/header.php';
?>
<div class="card auth">
  <h1 class="page-title">注册</h1>
  <?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
  <?php endforeach; ?>
  <form method="post" action="/register.php">
    <div class="field">
      <label>用户名</label>
      <input class="input" type="text" name="username" value="<?= e($username) ?>">
      <div class="hint">3-20 位字母、数字或下划线</div>
    </div>
    <div class="field">
      <label>昵称</label>
      <input class="input" type="text" name="nickname" value="<?= e($nickname) ?>">
    </div>
    <div class="field">
      <label>邮箱（选填）</label>
      <input class="input" type="email" name="email" value="<?= e($email) ?>">
    </div>
    <div class="field">
      <label>密码</label>
      <input class="input" type="password" name="password">
    </div>
    <div class="field">
      <label>确认密码</label>
      <input class="input" type="password" name="confirm">
    </div>
    <button class="btn btn-primary btn-block" type="submit">注册</button>
  </form>
  <p class="hint">已有账号？<a href="/login.php">登录</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
