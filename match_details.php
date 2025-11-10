<?php
// yrscores/match_details.php
declare(strict_types=1);

require_once 'classes/Database.php';
require_once 'classes/AdminPanel.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    die('พารามิเตอร์ id ไม่ถูกต้อง');
}

$panel = new AdminPanel();
$match = $panel->getMatchDetails($id);

if (!$match) {
    http_response_code(404);
    die('ไม่พบแมตช์ที่คุณเลือก');
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>รายละเอียดแมตช์ — YRScores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./css/style.css">
    <style>
        :root {
            --accent: #ef4444; /* สีแดงสดแบบทีมกีฬา */
            --accent-light: #fee2e2;
            --dark-bg: #0f172a;
        }
        body {
            font-family: 'Mitr', sans-serif;
        }
        .accent {
            color: var(--accent);
        }
        .accent-bg {
            background-color: var(--accent);
        }
        .hero {
            background: linear-gradient(to right, var(--dark-bg), #1e293b);
            color: white;
            padding: 3rem 0;
            text-align: center;
        }
        .hero-title {
            font-size: 2rem;
            font-weight: 600;
        }
        .stat-box {
            background: var(--accent-light);
            color: var(--accent);
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            font-weight: 600;
            display: inline-block;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Hero Section -->
    <div class="hero shadow-md">
        <div class="max-w-6xl mx-auto">
            <div class="hero-title">
                รายละเอียดการแข่งขัน
            </div>
            <p class="mt-2 text-gray-300">ติดตามข้อมูลแมตช์แบบเรียลไทม์กับ <span class="accent font-semibold">YRScores</span></p>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-6xl mx-auto p-6">
        <div class="bg-white p-8 rounded-xl shadow-xl border border-gray-200">
            <h1 class="text-3xl font-bold mb-6 text-center accent">
                <?= htmlspecialchars($match['team1'] ?? 'ทีม 1') ?>
                <span class="text-gray-500">vs</span>
                <?= htmlspecialchars($match['team2'] ?? 'ทีม 2') ?>
            </h1>

            <!-- Score Box -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-3">
                    <span class="stat-box text-2xl"><?= htmlspecialchars((string)($match['score1'] ?? '0')) ?></span>
                    <span class="text-2xl font-bold text-gray-600">:</span>
                    <span class="stat-box text-2xl"><?= htmlspecialchars((string)($match['score2'] ?? '0')) ?></span>
                </div>
                <div class="mt-3 text-gray-500">คะแนนล่าสุด</div>
            </div>

            <!-- Grid Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-3">
                    <p><strong class="text-gray-700">กีฬา:</strong> <span class="text-gray-800"><?= htmlspecialchars($match['sport'] ?? '-') ?></span></p>
                    <p><strong class="text-gray-700">ประเภท:</strong> <span class="text-gray-800"><?= htmlspecialchars($match['category'] ?? '-') ?></span></p>
                    <p><strong class="text-gray-700">เริ่มเวลา:</strong> <span class="text-gray-800"><?= htmlspecialchars($match['starts_at'] ?? '-') ?></span></p>
                </div>
                <div class="space-y-3">
                    <p><strong class="text-gray-700">ทีม 1:</strong> <span class="text-gray-800"><?= htmlspecialchars($match['team1'] ?? '-') ?></span></p>
                    <p><strong class="text-gray-700">ทีม 2:</strong> <span class="text-gray-800"><?= htmlspecialchars($match['team2'] ?? '-') ?></span></p>
                    <p><strong class="text-gray-700">ยอดดู:</strong> <span class="text-gray-800"><?= number_format((int)($match['view_count'] ?? 0)) ?> ครั้ง</span></p>
                </div>
            </div>

            <!-- Description -->
            <div class="mt-8 border-t border-gray-200 pt-5">
                <h3 class="text-xl font-semibold mb-3 accent">ข้อมูลเพิ่มเติม</h3>
                <p class="text-gray-700 leading-relaxed"><?= htmlspecialchars($match['description'] ?? 'ไม่มีคำอธิบาย') ?></p>
            </div>
        </div>

        <!-- Back button -->
        <div class="text-center mt-10">
            <a href="index.php" class="inline-block accent-bg text-white font-semibold py-2 px-6 rounded-full hover:opacity-90 transition">
                ← กลับหน้าหลัก
            </a>
        </div>
    </div>

    <footer class="mt-12 py-4 text-center text-gray-400 text-sm">
        © <?= date('Y') ?> YRScores. By Veerawatt Kanthiphan
    </footer>
</body>
</html>
