<?php

declare(strict_types=1);
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/AdminPanel.php';

Auth::startSession();
Auth::requireLogin();
$panel = new AdminPanel();

/* --- CSRF --- */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$CSRF = $_SESSION['csrf'];

/* --- Handle POST --- */
$err = '';
$ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $err = 'CSRF invalid';
    } else {
        $act = $_POST['action'] ?? '';
        try {
            if ($act === 'create') {
                $u = trim($_POST['username'] ?? '');
                $p = $_POST['password'] ?? '';
                if ($u === '' || $p === '') throw new RuntimeException('กรอกให้ครบ');
                $panel->createAdmin($u, $p);
                $ok = 'สร้างแล้ว';
            } elseif ($act === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $u  = trim($_POST['username'] ?? '');
                $p  = $_POST['password'] ?? null;
                if ($id <= 0) throw new RuntimeException('bad id');
                $panel->updateAdmin($id, $u !== '' ? $u : null, ($p !== '' ? $p : null));
                $ok = 'อัปเดตแล้ว';
            } elseif ($act === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new RuntimeException('bad id');
                $panel->deleteAdmin($id);
                $ok = 'ลบแล้ว';
            }
        } catch (Throwable $e) {
            $err = 'ผิดพลาด: ' . $e->getMessage();
        }
    }
}

$rows = $panel->listAdmins();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admins — YRScores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body class="bg-white text-gray-900">
    <div class="max-w-5xl mx-auto p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">ผู้ดูแลระบบ (Admins)</h1>
            <a href="/yrscores/admin/dashboard.php" class="text-sm underline">กลับ</a>
        </div>

        <?php if ($err): ?><div class="text-red-600 text-sm mb-3"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <?php if ($ok):  ?><div class="text-green-700 text-sm mb-3"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

        <!-- Create -->
        <form method="post" class="border rounded-2xl p-4 mb-6">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
            <input type="hidden" name="action" value="create">
            <h2 class="font-semibold mb-3">เพิ่มผู้ดูแล</h2>
            <div class="grid sm:grid-cols-3 gap-3">
                <input name="username" placeholder="username" class="border rounded p-2" required>
                <input name="password" type="password" placeholder="password" class="border rounded p-2" required>
                <button class="rounded text-white px-4" style="background:#FF1493">เพิ่ม</button>
            </div>
        </form>

        <!-- List -->
        <div class="border rounded-2xl overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left p-2">ID</th>
                        <th class="text-left p-2">Username</th>
                        <th class="text-left p-2">Created</th>
                        <th class="p-2">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr class="border-t">
                            <td class="p-2"><?= (int)$r['id'] ?></td>
                            <td class="p-2"><?= htmlspecialchars($r['username']) ?></td>
                            <td class="p-2"><?= htmlspecialchars($r['created_at']) ?></td>
                            <td class="p-2">
                                <form method="post" class="flex flex-wrap gap-2 items-center">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input type="text" name="username" placeholder="เปลี่ยนชื่อ (ไม่บังคับ)" class="border rounded p-1">
                                    <input type="password" name="password" placeholder="ตั้งรหัสใหม่ (ไม่บังคับ)" class="border rounded p-1">
                                    <button name="action" value="update" class="px-3 py-1 rounded text-white" style="background:#FF1493">อัปเดต</button>
                                    <button name="action" value="delete" class="px-3 py-1 rounded border border-red-500 text-red-600" onclick="return confirm('ลบผู้ใช้นี้?')">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (!$rows): ?>
                        <tr>
                            <td colspan="4" class="p-3 text-gray-500">ยังไม่มีผู้ดูแล</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>