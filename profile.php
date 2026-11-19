<?php
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();

$errors = [];
$pwned = false;
$nickname = $user['nickname'];
$email = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim((string)($_POST['nickname'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));

    if ($nickname === '' || mb_strlen($nickname) > 50) {
        $errors[] = '昵称不能为空且不超过 50 字';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '邮箱格式不正确';
    }

    $avatarRel = $user['avatar'];

    if (isset($_FILES['avatar']) && (int)$_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['avatar'];

        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = '文件上传失败，错误码 ' . (int)$file['error'];
        } else {
            $dir = APP_ROOT . '/' . config('upload_dir');
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $base = basename($file['name']);

            if (lab_enabled('upload')) {
                // V1 漏洞分支：只信客户端 Content-Type 与原始文件名
                $allow = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file['type'], $allow, true)) {
                    $errors[] = '只允许上传图片';
                } elseif (move_uploaded_file($file['tmp_name'], $dir . '/' . $base)) {
                    $avatarRel = config('upload_dir') . '/' . $base;
                    $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
                    $pwned = $ext === 'php';
                } else {
                    $errors[] = '保存文件失败';
                }
            } else {
                // 安全分支：后缀白名单 + 真实图片校验 + 随机文件名 + 2MB 限制
                $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
                $allow = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($ext, $allow, true)) {
                    $errors[] = '仅支持 jpg / jpeg / png / gif 格式';
                } elseif ((int)$file['size'] > 2 * 1024 * 1024) {
                    $errors[] = '图片不能超过 2MB';
                } elseif (getimagesize($file['tmp_name']) === false) {
                    $errors[] = '文件不是有效图片';
                } else {
                    $name = bin2hex(random_bytes(8)) . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
                        $avatarRel = config('upload_dir') . '/' . $name;
                    } else {
                        $errors[] = '保存文件失败';
                    }
                }
            }
        }
    }

    if (!$errors) {
        $st = db()->prepare('UPDATE users SET nickname = ?, email = ?, avatar = ? WHERE id = ?');
        $st->execute([$nickname, $email, $avatarRel, $user['id']]);
        flash($pwned ? '攻破成功！V1 任意文件上传：危险文件已保存到 /' . $avatarRel : '资料已更新');
        redirect('/profile.php');
    }
}

$pageTitle = '个人中心';
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">个人中心</h1>

<div class="grid-side">
  <div class="card">
    <h2 class="card-title">基本资料</h2>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>
    <form method="post" action="/profile.php" enctype="multipart/form-data">
      <div class="field">
        <label for="nickname">昵称</label>
        <input class="input" id="nickname" type="text" name="nickname" value="<?= e($nickname) ?>" maxlength="50" required>
      </div>
      <div class="field">
        <label for="email">邮箱</label>
        <input class="input" id="email" type="text" name="email" value="<?= e($email) ?>" maxlength="100">
      </div>
      <div class="field">
        <label for="avatar">头像</label>
        <input class="input" id="avatar" type="file" name="avatar" accept="image/*">
      </div>
      <button class="btn btn-primary" type="submit">保存资料</button>
    </form>
  </div>

  <div class="card">
    <h2 class="card-title">当前头像</h2>
    <div class="avatar-preview">
      <?php if ($user['avatar'] !== ''): ?>
        <img src="/<?= e($user['avatar']) ?>" alt="头像">
      <?php else: ?>
        <?= e(mb_substr($user['nickname'], 0, 1)) ?>
      <?php endif; ?>
    </div>
    <p class="hint">用户名：<?= e($user['username']) ?> · 角色：<?= $user['role'] === 'admin' ? '管理员' : '普通用户' ?></p>
    <p class="hint">注册时间：<?= e($user['created_at'] ?? '') ?></p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
