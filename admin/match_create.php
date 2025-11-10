<?php

declare(strict_types=1);
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/AdminPanel.php';
require_once __DIR__ . '/../classes/Match.php';

Auth::startSession();
Auth::requireLogin();
$panel = new AdminPanel();
$svc   = new MatchService();

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$CSRF = $_SESSION['csrf'];

$err = '';
$ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $err = 'CSRF invalid';
    } else {
        try {
            $sportId    = (int)($_POST['sport_id'] ?? 0);
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $team1Id    = (int)($_POST['team1_id'] ?? 0);
            $team2Id    = (int)($_POST['team2_id'] ?? 0);
            $status     = ($_POST['status'] ?? 'live') === 'finished' ? 'finished' : 'live';

            // รับจาก datetime-local => 'YYYY-MM-DDTHH:MM' → ให้แปลงเป็น 'YYYY-MM-DD HH:MM:SS'
            $dtLocal = trim($_POST['starts_at'] ?? '');
            if ($dtLocal === '') throw new RuntimeException('กรุณาเลือกเวลาเริ่ม');
            $startsAt = str_replace('T', ' ', $dtLocal) . ':00';

            $newId = $svc->createMatch($sportId, $categoryId, $team1Id, $team2Id, $startsAt, $status);
            $ok = 'สร้างแมตช์เรียบร้อย (#' . $newId . ')';
        } catch (Throwable $e) {
            $err = 'ผิดพลาด: ' . $e->getMessage();
        }
    }
}

$sports = $panel->listSports(); // ใช้แสดงตัวเลือกกีฬาชุดแรก
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Match — YRScores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body class="bg-white text-gray-900">
    <div class="max-w-3xl mx-auto p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">สร้างแมตช์ใหม่</h1>
            <div class="flex gap-3 text-sm">
                <a class="underline" href="/yrscores/admin/matches.php">ควบคุมแมตช์สด</a>
                <a class="underline" href="/yrscores/admin/dashboard.php">แดชบอร์ด</a>
            </div>
        </div>

        <?php if ($err): ?><div class="mb-4 text-sm text-red-600"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <?php if ($ok):  ?><div class="mb-4 text-sm text-green-700"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

        <form method="post" class="border rounded-2xl p-5 grid gap-4">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">

            <!-- Sport -->
            <label class="grid gap-1">
                <span class="text-sm">กีฬา</span>
                <select id="sport" name="sport_id" class="border rounded p-2" required>
                    <option value="">— เลือกกีฬา —</option>
                    <?php foreach ($sports as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <!-- Category (depends on sport) -->
            <label class="grid gap-1">
                <span class="text-sm">ลีก/หมวด</span>
                <select id="category" name="category_id" class="border rounded p-2" required>
                    <option value="">— เลือกลีก —</option>
                </select>
            </label>

            <div class="grid sm:grid-cols-2 gap-4">
                <!-- Team 1 -->
                <label class="grid gap-1">
                    <span class="text-sm">ทีม 1</span>
                    <select id="team1" name="team1_id" class="border rounded p-2" required>
                        <option value="">— เลือกทีม —</option>
                    </select>
                </label>

                <!-- Team 2 -->
                <label class="grid gap-1">
                    <span class="text-sm">ทีม 2</span>
                    <select id="team2" name="team2_id" class="border rounded p-2" required>
                        <option value="">— เลือกทีม —</option>
                    </select>
                </label>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <!-- Starts at -->
                <label class="grid gap-1">
                    <span class="text-sm">เวลาเริ่ม</span>
                    <input type="datetime-local" name="starts_at" class="border rounded p-2" required>
                </label>

                <!-- Status -->
                <label class="grid gap-1">
                    <span class="text-sm">สถานะ</span>
                    <select name="status" class="border rounded p-2">
                        <option value="live">Live</option>
                        <option value="finished">Finished</option>
                    </select>
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button class="px-4 py-2 rounded text-white" style="background:#FF1493">สร้างแมตช์</button>
                <button type="reset" class="px-4 py-2 rounded border">ล้างฟอร์ม</button>
            </div>
        </form>
    </div>

    <script>
        const sportEl = document.getElementById('sport');
        const catEl = document.getElementById('category');
        const t1El = document.getElementById('team1');
        const t2El = document.getElementById('team2');

        async function loadOptions() {
            const sportId = sportEl.value;
            if (!sportId) {
                catEl.innerHTML = '<option value="">— เลือกลีก —</option>';
                t1El.innerHTML = '<option value="">— เลือกทีม —</option>';
                t2El.innerHTML = '<option value="">— เลือกทีม —</option>';
                return;
            }
            // โหลด categories
            const cRes = await fetch('/yrscores/api/get_options.php?type=categories&sport_id=' + encodeURIComponent(sportId), {
                cache: 'no-store'
            });
            const cats = cRes.ok ? await cRes.json() : [];
            catEl.innerHTML = '<option value="">— เลือกลีก —</option>' + cats.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

            // โหลด teams
            const tRes = await fetch('/yrscores/api/get_options.php?type=teams&sport_id=' + encodeURIComponent(sportId), {
                cache: 'no-store'
            });
            const teams = tRes.ok ? await tRes.json() : [];
            const opts = '<option value="">— เลือกทีม —</option>' + teams.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
            t1El.innerHTML = opts;
            t2El.innerHTML = opts;
        }

        sportEl.addEventListener('change', loadOptions);

        // กันเลือกทีมซ้ำกัน
        function ensureDifferent() {
            if (t1El.value && t1El.value === t2El.value) {
                // ถ้าซ้ำ ให้รีทีม 2
                t2El.value = '';
            }
        }
        t1El.addEventListener('change', ensureDifferent);
        t2El.addEventListener('change', ensureDifferent);
    </script>
</body>

</html>