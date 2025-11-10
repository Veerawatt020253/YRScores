<?php
// yrscores/admin/dashboard.php
declare(strict_types=1);
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/AdminPanel.php';

Auth::startSession();
Auth::requireLogin();

$panel = new AdminPanel();
$stats = $panel->getDashboardStats();
$recent = $panel->listRecentMatches(10);

const MATCH_DURATION_MIN = 120; // ความยาวแมตช์โดยประมาณ (นาที)

function badgeStatus(array $m): string
{
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
    <title>Dashboard — YRScores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --accent: #FF1493;
            /* เปลี่ยนสีหลักเป็น #FF1493 */
            --accent-2: #FF77FF;
            /* สีม่วงอ่อนใช้ผสม */
            --ink: #0f172a;
            /* slate-900 */
        }

        .accent {
            color: var(--accent)
        }

        .accent-bg {
            background: var(--accent)
        }

        /* พื้นหลังนวล ๆ ไล่สีบานเย็น */
        body {
            background:
                radial-gradient(20rem 20rem at 8% -8%, rgba(255, 0, 168, .10), transparent 60%),
                radial-gradient(24rem 24rem at 110% 12%, rgba(162, 28, 175, .10), transparent 55%),
                #fff;
            color: var(--ink);
        }

        /* CARD กลาสซี่ + เส้นกรอบไล่สีบานเย็น */
        .card {
            position: relative;
            border-radius: 1rem;
            overflow: hidden;
            background: linear-gradient(180deg, rgba(255, 255, 255, .92), rgba(255, 255, 255, .88));
            backdrop-filter: blur(6px);
            border: 1px solid rgba(15, 23, 42, .06);
        }

        .card::before {
            content: "";
            position: absolute;
            inset: -1px;
            border-radius: 1.1rem;
            pointer-events: none;
            background: linear-gradient(135deg, rgba(255, 0, 168, .40), rgba(162, 28, 175, .35));
            mask: linear-gradient(#000, #000) content-box, linear-gradient(#000, #000);
            -webkit-mask: linear-gradient(#000, #000) content-box, linear-gradient(#000, #000);
            padding: 1px;
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: .38;
        }

        .chip {
            border: 1px solid rgba(15, 23, 42, .12);
            padding: .3rem .6rem;
            border-radius: 999px;
            font-size: .75rem;
        }

        .icon-bubble {
            width: 44px;
            height: 44px;
            border-radius: 9999px;
            display: grid;
            place-items: center;
            color: #fff;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            box-shadow: 0 8px 24px rgba(255, 0, 168, .25);
        }

        .icon-bubble i {
            font-size: 18px;
        }

        /* หัวตารางโทนบานเย็นอ่อน */
        .thead-soft {
            background: linear-gradient(0deg, rgba(255, 0, 168, .06), rgba(255, 0, 168, .06));
        }

        /* Topbar กราเดียนต์บานเย็น */
        .topbar {
            background: linear-gradient(90deg, rgba(255, 0, 168, .10), rgba(162, 28, 175, .10));
            border-bottom: 1px solid rgba(15, 23, 42, .08);
        }

        /* ปุ่มหลัก */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .55rem 1rem;
            border-radius: 999px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            box-shadow: 0 10px 24px rgba(255, 0, 168, .28);
        }

        .btn:hover {
            filter: brightness(.98)
        }

        .btn i {
            font-size: 14px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
        try {
            // เรียกใช้ Database
            $pdo = Database::get();

            // ลบข้อมูลทั้งหมดจากตาราง matches
            $stmt = $pdo->prepare("DELETE FROM matches");
            $stmt->execute();

            // เก็บสถานะการรีเซ็ทไว้ใน session
            $_SESSION['reset_success'] = true;

            // รีเฟรชหน้าเพื่อแสดงผล
            header('Location: dashboard.php');
            exit; // ทำให้การทำงานหยุดที่นี่
        } catch (PDOException $e) {
            echo "<p>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
        }
    }

    // ตรวจสอบสถานะการรีเซ็ทจาก session
    if (isset($_SESSION['reset_success']) && $_SESSION['reset_success']) {
        echo "
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'ข้อมูลการแข่งขันทั้งหมดถูกรีเซ็ทแล้ว',
                    showConfirmButton: false,
                    timer: 3000 // ปิดอัตโนมัติหลังจาก 3 วินาที
                }).then(() => {
                    location.reload(); // รีเฟรชหน้า
                });
            </script>
        ";

        // ลบ session หลังแสดงผลเสร็จ
        unset($_SESSION['reset_success']);
    }
    ?>

    <!-- Topbar -->
    <div class="topbar">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="icon-bubble"><i class="fa-solid fa-gauge-high"></i></div>
                <h1 class="text-xl sm:text-2xl font-bold">แผงควบคุม</h1>
                <span class="chip hidden sm:inline text-slate-700 bg-white/70">YRScores Admin</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="/yrscores" class="text-sm text-slate-700 hover:text-slate-900 underline"><i class="fa-solid fa-up-right-from-square"></i> ดูหน้าเว็บไซต์</a>
                <a href="/yrscores/admin/logout.php" class="text-sm underline"><i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ</a>
            </div>
        </div>
    </div>

    <main class="max-w-6xl mx-auto px-4 py-6 space-y-8">
        <!-- Stats -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-600">แมตช์ทั้งหมด</div>
                        <div class="mt-1 text-3xl font-bold"><?= (int)($stats['total_all'] ?? 0) ?></div>
                    </div>
                    <div class="icon-bubble"><i class="fa-solid fa-chart-line"></i></div>
                </div>
            </div>
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-600">แข่งวันนี้</div>
                        <div class="mt-1 text-3xl font-bold"><?= (int)($stats['total_today'] ?? 0) ?></div>
                    </div>
                    <div class="icon-bubble"><i class="fa-regular fa-calendar-days"></i></div>
                </div>
            </div>
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-600">กำลังถ่ายทอดสด</div>
                        <div class="mt-1 text-3xl font-bold accent"><?= (int)($stats['total_live'] ?? 0) ?></div>
                    </div>
                    <div class="icon-bubble"><i class="fa-solid fa-tower-broadcast"></i></div>
                </div>
            </div>
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-600">จบแล้ว</div>
                        <div class="mt-1 text-3xl font-bold text-emerald-600"><?= (int)($stats['total_finished'] ?? 0) ?></div>
                    </div>
                    <div class="icon-bubble"><i class="fa-regular fa-circle-check"></i></div>
                </div>
            </div>
        </section>

        <form id="resetForm" action="dashboard.php" method="POST" style="display:none;">
            <input type="hidden" name="reset" value="true">
        </form>

        <!-- Quick Actions -->
        <section class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base sm:text-lg font-semibold">เมนูการจัดการ</h2>
                
                    <button type="button" class="btn text-sm" id="resetButton">รีเซ็ทข้อมูลการแข่งขัน</button>
                    <script>
                        document.getElementById('resetButton').addEventListener('click', function() {
                            Swal.fire({
                                title: 'คุณแน่ใจหรือไม่?',
                                text: "การรีเซ็ทจะลบข้อมูลการแข่งขันทั้งหมด!",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'ใช่, รีเซ็ท!',
                                cancelButtonText: 'ยกเลิก'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // ถ้าผู้ใช้กดยืนยัน รีเซ็ทข้อมูล
                                    document.getElementById('resetForm').submit(); // ส่งคำขอเพื่อรีเซ็ทข้อมูล
                                }
                            });
                        });
                    </script>
               
                <a href="/yrscores/admin/match_create.php" class="btn text-sm"><i class="fa-solid fa-plus"></i> สร้างแมตช์ใหม่</a>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a class="group card p-5 hover:shadow-lg transition" href="/yrscores/admin/matches.php">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold"><i class="fa-solid fa-stopwatch mr-2"></i> ควบคุมแมตช์ (Live)</div>
                        <div class="text-slate-300 group-hover:text-slate-400"><i class="fa-solid fa-angles-right"></i></div>
                    </div>
                    <p class="text-sm text-slate-600 mt-1">อัปเดตสกอร์/สถานะแบบเรียลไทม์</p>
                </a>

                <a class="group card p-5 hover:shadow-lg transition" href="/yrscores/admin/teams.php">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold"><i class="fa-solid fa-flag mr-2"></i> ทีม (Teams)</div>
                        <div class="text-slate-300 group-hover:text-slate-400"><i class="fa-solid fa-angles-right"></i></div>
                    </div>
                    <p class="text-sm text-slate-600 mt-1">จัดการทีม สี และโลโก้</p>
                </a>

                <a class="group card p-5 hover:shadow-lg transition" href="/yrscores/admin/categories.php">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold"><i class="fa-solid fa-layer-group mr-2"></i> หมวด/ลีก (Categories)</div>
                        <div class="text-slate-300 group-hover:text-slate-400"><i class="fa-solid fa-angles-right"></i></div>
                    </div>
                    <p class="text-sm text-slate-600 mt-1">กลุ่มการแข่งขัน แยกตามกีฬา/ลีก</p>
                </a>

                <a class="group card p-5 hover:shadow-lg transition" href="/yrscores/admin/sports.php">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold"><i class="fa-solid fa-trophy mr-2"></i> กีฬา (Sports)</div>
                        <div class="text-slate-300 group-hover:text-slate-400"><i class="fa-solid fa-angles-right"></i></div>
                    </div>
                    <p class="text-sm text-slate-600 mt-1">ประเภทกีฬาในระบบ</p>
                </a>

                <a class="group card p-5 hover:shadow-lg transition" href="/yrscores/admin/admins.php">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold"><i class="fa-solid fa-user-shield mr-2"></i> ผู้ดูแลระบบ (Admins)</div>
                        <div class="text-slate-300 group-hover:text-slate-400"><i class="fa-solid fa-angles-right"></i></div>
                    </div>
                    <p class="text-sm text-slate-600 mt-1">เพิ่ม/แก้ไขบัญชีผู้ดูแล</p>
                </a>

                <a class="group card p-5 hover:shadow-lg transition" href="/yrscores/admin/match_create.php">
                    <div class="flex items-center justify-between">

                        <div class="font-semibold"><i class="fa-solid fa-circle-plus mr-2"></i> + สร้างแมตช์ใหม่</div>
                        <div class="text-slate-300 group-hover:text-slate-400"><i class="fa-solid fa-angles-right"></i></div>
                    </div>
                    <p class="text-sm text-slate-600 mt-1">เพิ่มรายการแข่งขันใหม่ในระบบ</p>
                </a>
            </div>
        </section>

        <!-- Recent Matches -->
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between px-5 pt-5">
                <h2 class="text-base sm:text-lg font-semibold">แมตช์ล่าสุด</h2>
                <a href="/yrscores/admin/matches.php" class="text-sm text-slate-700 hover:text-slate-900 underline"><i class="fa-solid fa-list-ul"></i> ดูทั้งหมด</a>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="min-w-full text-sm">
                    <thead class="thead-soft text-slate-700">
                        <tr>
                            <th class="text-left p-3">คู่แข่ง</th>
                            <th class="text-left p-3 hidden sm:table-cell">กีฬา/ลีก</th>
                            <th class="text-left p-3">เวลา</th>
                            <th class="text-left p-3">สกอร์</th>
                            <th class="text-left p-3">สถานะ</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white/70">
                        <?php foreach ($recent as $m): ?>
                            <tr class="border-t hover:bg-fuchsia-50/40 transition">
                                <td class="p-3 font-medium">
                                    <i class="fa-solid fa-shield-halved text-slate-400 mr-2"></i>
                                    <?= htmlspecialchars($m['team1'] ?? 'ทีม 1') ?>
                                    <span class="text-slate-400">vs</span>
                                    <?= htmlspecialchars($m['team2'] ?? 'ทีม 2') ?>
                                </td>
                                <td class="p-3 text-slate-600 hidden sm:table-cell">
                                    <i class="fa-regular fa-futbol text-slate-400 mr-1"></i>
                                    <?= htmlspecialchars($m['sport'] ?? '-') ?> / <?= htmlspecialchars($m['category'] ?? '-') ?>
                                </td>
                                <td class="p-3 text-slate-600">
                                    <i class="fa-regular fa-clock text-slate-400 mr-1"></i>
                                    <?= htmlspecialchars($m['starts_at'] ?? '-') ?>
                                </td>
                                <td class="p-3">
                                    <i class="fa-solid fa-scoreboard text-slate-400 mr-1"></i>
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
                        <?php if (empty($recent)): ?>
                            <tr>
                                <td colspan="6" class="p-6 text-center text-slate-500">ยังไม่มีข้อมูล</td>
                            </tr>
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