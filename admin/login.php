<?php
declare(strict_types=1);
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::startSession();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username'] ?? '');
  $p = $_POST['password'] ?? '';

  // ถ้ายังไม่มีแอดมินเลย สร้างผู้ใช้แรกอัตโนมัติ
  $pdo = Database::get();
  $exists = (int)$pdo->query("SELECT COUNT(*) AS c FROM admins")->fetch()['c'];
  if ($exists === 0 && $u !== '' && $p !== '') {
    $stmt = $pdo->prepare("INSERT INTO admins(username, password_hash) VALUES(?, ?)");
    $stmt->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
  }

  if (Auth::login($u, $p)) {
    header('Location: /yrscores/admin/dashboard.php'); exit;
  } else {
    $err = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
  }
}
?>
<!DOCTYPE html>
<html lang="th"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login — YRScores</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-white">
  <div class="min-h-screen flex items-center justify-center">
    <form method="post" class="border rounded-2xl p-6 w-full max-w-sm">
      <h1 class="text-2xl font-bold mb-4"><span class="text-[<?= htmlspecialchars((require __DIR__.'/../config.php')['app']['accent']) ?>]">YRScores</span> Admin</h1>
      <?php if ($err): ?><div class="text-red-600 text-sm mb-3"><?= htmlspecialchars($err) ?></div><?php endif; ?>
      <label class="block text-sm">Username</label>
      <input name="username" class="border rounded w-full p-2 mb-3" required>
      <label class="block text-sm">Password</label>
      <input name="password" type="password" class="border rounded w-full p-2 mb-4" required>
      <button class="w-full py-2 rounded text-white" style="background:#FF1493">เข้าสู่ระบบ</button>
    </form>
  </div>
</body></html>
