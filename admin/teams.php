<?php

declare(strict_types=1);
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/AdminPanel.php';

Auth::startSession();
Auth::requireLogin();
$panel = new AdminPanel();

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$CSRF = $_SESSION['csrf'];

$err = '';
$ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $err = 'CSRF invalid';
    } else {
        $act = $_POST['action'] ?? '';
        try {
            if ($act === 'create') {
                $sportId = (int)($_POST['sport_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $hex  = strtoupper(trim($_POST['color_hex'] ?? ''));
                if ($sportId <= 0 || $name === '' || !preg_match('/^#[0-9A-F]{6}$/i', $hex)) throw new RuntimeException('ข้อมูลไม่ครบ/สีไม่ถูกต้อง');
                $panel->createTeam($sportId, $name, $hex);
                $ok = 'สร้างแล้ว';
            } elseif ($act === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $sportId = (int)($_POST['sport_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $hex  = strtoupper(trim($_POST['color_hex'] ?? ''));
                if ($id <= 0 || $sportId <= 0 || $name === '' || ($hex !== '' && !preg_match('/^#[0-9A-F]{6}$/i', $hex))) throw new RuntimeException('ข้อมูลไม่ครบ/สีไม่ถูกต้อง');
                $panel->updateTeam($id, $sportId, $name, $hex !== '' ? $hex : '#000000');
                $ok = 'อัปเดตแล้ว';
            } elseif ($act === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new RuntimeException('bad id');
                $panel->deleteTeam($id);
                $ok = 'ลบแล้ว';
            }
        } catch (Throwable $e) {
            $err = 'ผิดพลาด: ' . $e->getMessage();
        }
    }
}

$sports = $panel->listSports();
$rows   = $panel->listTeams();
function sportOptions(array $sports, ?int $sel = null): string
{
    $out = '';
    foreach ($sports as $s) {
        $out .= '<option value="' . (int)$s['id'] . '"' . ($sel === (int)$s['id'] ? ' selected' : '') . '>' . htmlspecialchars($s['name']) . '</option>';
    }
    return $out ?: '<option value="">-- ยังไม่มีกีฬา --</option>';
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Teams — YRScores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body class="bg-white">
    <div class="max-w-5xl mx-auto p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">ทีม (Teams)</h1>
            <a href="/yrscores/admin/dashboard.php" class="text-sm underline">กลับ</a>
        </div>

        <?php if ($err): ?><div class="text-red-600 text-sm mb-3"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <?php if ($ok):  ?><div class="text-green-700 text-sm mb-3"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

        <!-- Create -->
        <form method="post" class="border rounded-2xl p-4 mb-6">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
            <input type="hidden" name="action" value="create">
            <div class="grid sm:grid-cols-4 gap-3 items-center">
                <select name="sport_id" class="border rounded p-2" required>
                    <?= sportOptions($sports) ?>
                </select>
                <input name="name" placeholder="ชื่อทีม" class="border rounded p-2" required>
                <!-- ใช้ color picker -->
                <input type="color" name="color_hex" value="#000000" class="border rounded h-10 w-full p-0" required>
                <button class="rounded text-white px-4" style="background:#FF1493">เพิ่มทีม</button>
            </div>
        </form>

        <!-- List -->
        <div class="border rounded-2xl overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left p-2">ID</th>
                        <th class="text-left p-2">กีฬา</th>
                        <th class="text-left p-2">ชื่อทีม</th>
                        <th class="text-left p-2">สี</th>
                        <th class="text-left p-2">Created</th>
                        <th class="p-2">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr class="border-t">
                            <td class="p-2"><?= (int)$r['id'] ?></td>
                            <td class="p-2"><?= htmlspecialchars($r['sport']) ?></td>
                            <td class="p-2"><?= htmlspecialchars($r['name']) ?></td>
                            <td class="p-2">
                                <span class="inline-block w-4 h-4 rounded align-middle" style="background:<?= htmlspecialchars($r['color_hex']) ?>"></span>
                                <?= htmlspecialchars($r['color_hex']) ?>
                            </td>
                            <td class="p-2"><?= htmlspecialchars($r['created_at']) ?></td>
                            <td class="p-2">
                                <form method="post" class="flex flex-wrap gap-2 items-center">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <select name="sport_id" class="border rounded p-1"><?= sportOptions($sports) ?></select>
                                    <input type="text" name="name" placeholder="ชื่อใหม่" class="border rounded p-1">
                                    <!-- color picker ตอนอัปเดต -->
                                    <input type="color" name="color_hex" value="<?= htmlspecialchars($r['color_hex']) ?>" class="border rounded h-9 w-24 p-0">
                                    <button name="action" value="update" class="px-3 py-1 rounded text-white" style="background:#FF1493">อัปเดต</button>
                                    <button name="action" value="delete" class="px-3 py-1 rounded border border-red-500 text-red-600" onclick="return confirm('ลบทีมนี้?')">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (!$rows): ?>
                        <tr>
                            <td colspan="6" class="p-3 text-gray-500">ยังไม่มีรายการ</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>