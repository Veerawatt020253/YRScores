<?php
// yrscores/admin/summary.php
declare(strict_types=1);
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/AdminPanel.php';

Auth::startSession();
Auth::requireLogin();

$panel = new AdminPanel();
$matches = $panel->listAllMatches(); // ดึงข้อมูลทั้งหมดจาก matches

const MATCH_DURATION_MIN = 120;

function badgeStatus(array $m): string {
    $now = new DateTimeImmutable('now');
    $starts = !empty($m['starts_at']) ? new DateTimeImmutable($m['starts_at']) : null;

    if (!$starts) {
        return '<span class="px-2 py-1 rounded-full text-xs border bg-slate-100 text-slate-700 border-slate-200">ไม่ทราบ</span>';
    }

    $endsGuess = $starts->modify('+' . MATCH_DURATION_MIN . ' minutes');

    if ($now < $starts) {
        return '<span class="px-2 py-1 rounded-full text-xs border bg-gray-100 text-gray-700 border-gray-200">กำหนดการ</span>';
    }
    if ($now >= $starts && $now < $endsGuess) {
        return '<span class="px-2 py-1 rounded-full text-xs border bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200">ถ่ายทอดสด</span>';
    }
    return '<span class="px-2 py-1 rounded-full text-xs border bg-emerald-50 text-emerald-700 border-emerald-200">จบแล้ว</span>';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>สรุปผลการแข่งขัน — YRScores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root{
            --accent:#FF00A8;      /* สีบานเย็นหลัก */
            --accent-2:#A21CAF;    /* ม่วงเข้มไว้ผสม */
            --ink:#0f172a;         /* slate-900 */
        }

        .accent{color:var(--accent)}
        .accent-bg{background:var(--accent)}

        /* พื้นหลังนวล ๆ ไล่สีบานเย็น */
        body{
            color: var(--ink);
        }

        /* CARD กลาสซี่ + เส้นกรอบไล่สีบานเย็น */
        .card{
            position:relative; border-radius:1rem; overflow:hidden;
            background:linear-gradient(180deg,rgba(255,255,255,.92),rgba(255,255,255,.88));
            backdrop-filter: blur(6px);
            border:1px solid rgba(15,23,42,.06);
        }
        .card::before{
            content:""; position:absolute; inset:-1px; border-radius:1.1rem; pointer-events:none;
            background:linear-gradient(135deg, rgba(255,0,168,.40), rgba(162,28,175,.35));
            mask:linear-gradient(#000,#000) content-box,linear-gradient(#000,#000);
            -webkit-mask:linear-gradient(#000,#000) content-box,linear-gradient(#000,#000);
            padding:1px; -webkit-mask-composite: xor; mask-composite: exclude; opacity:.38;
        }

        .chip{border:1px solid rgba(15,23,42,.12); padding:.3rem .6rem; border-radius:999px; font-size:.75rem;}
        .icon-bubble{
            width:44px;height:44px;border-radius:9999px;display:grid;place-items:center;color:#fff;
            background:linear-gradient(135deg,var(--accent),var(--accent-2));
            box-shadow:0 8px 24px rgba(255,0,168,.25);
        }
        .icon-bubble i { font-size: 18px; }

        /* หัวตารางโทนบานเย็นอ่อน */
        .thead-soft{background:linear-gradient(0deg, rgba(255,0,168,.06), rgba(255,0,168,.06));}

        /* Topbar กราเดียนต์บานเย็น */
        .topbar{
            background:linear-gradient(90deg, rgba(255,0,168,.10), rgba(162,28,175,.10));
            border-bottom:1px solid rgba(15,23,42,.08);
        }

        /* ปุ่มหลัก */
        .btn{
            display:inline-flex;align-items:center;gap:.5rem;
            padding:.55rem 1rem;border-radius:999px;font-weight:700;color:#fff;
            background:linear-gradient(135deg,var(--accent),var(--accent-2));
            box-shadow:0 10px 24px rgba(255,0,168,.28);
        }
        .btn:hover{filter:brightness(.98)}
        .btn i { font-size: 14px; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Topbar -->
    <div class="topbar">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="icon-bubble"><i class="fa-solid fa-trophy"></i></div>
                <h1 class="text-xl sm:text-2xl font-bold">สรุปผลการแข่งขัน</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="/yrscores/admin/dashboard.php" class="text-sm text-slate-700 hover:text-slate-900 underline"><i class="fa-solid fa-arrow-left"></i> กลับสู่แดชบอร์ด</a>
            </div>
        </div>
    </div>

    <main class="max-w-6xl mx-auto px-4 py-6 space-y-8">
        <!-- Recent Matches Table -->
        <section class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">แมตช์ล่าสุดทั้งหมด</h2>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="min-w-full text-sm">
                    <thead class="thead-soft text-slate-700">
                        <tr>
                            <th class="text-left p-3">คู่แข่ง</th>
                            <th class="text-left p-3">กีฬา/ลีก</th>
                            <th class="text-left p-3">เวลา</th>
                            <th class="text-left p-3">สกอร์</th>
                            <th class="text-left p-3">สถานะ</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white/70">
                        <?php foreach ($matches as $m): ?>
                            <tr class="border-t hover:bg-fuchsia-50/40 transition">
                                <td class="p-3 font-medium">
                                    <?= htmlspecialchars($m['team1'] ?? 'ทีม 1') ?>
                                    <span class="text-slate-400">vs</span>
                                    <?= htmlspecialchars($m['team2'] ?? 'ทีม 2') ?>
                                </td>
                                <td class="p-3 text-slate-600">
                                    <?= htmlspecialchars($m['sport'] ?? '-') ?> / <?= htmlspecialchars($m['category'] ?? '-') ?>
                                </td>
                                <td class="p-3 text-slate-600">
                                    <?= htmlspecialchars($m['starts_at'] ?? '-') ?>
                                </td>
                                <td class="p-3">
                                    <span class="font-semibold"><?= (int)($m['score1'] ?? 0) ?></span>
                                    <span class="text-slate-400">:</span>
                                    <span class="font-semibold"><?= (int)($m['score2'] ?? 0) ?></span>
                                </td>
                                <td class="p-3">
                                    <?= badgeStatus($m) ?>
                                </td>
                                <td class="p-3">
                                    <a class="text-sm accent hover:underline"
                                       href="/yrscores/match_details.php?id=<?= (int)$m['id'] ?>">
                                        <i class="fa-regular fa-eye mr-1"></i> รายละเอียด
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($matches)): ?>
                            <tr><td colspan="6" class="p-6 text-center text-slate-500">ยังไม่มีข้อมูล</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="py-8 text-center text-xs text-slate-400">
        © <?= date('Y') ?> YRScores — Admin Console
    </footer>
</body>
</html>
