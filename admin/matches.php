<?php
declare(strict_types=1);
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Match.php';
Auth::startSession(); Auth::requireLogin();

$pdo = Database::get();
// ดึง live ทั้งหมดมาให้ควบคุม (จริง ๆ ควรมีหน้า Create/Set live แยก)
// ตัวอย่าง: เลือก last 20 live/newest
$rows = (new MatchService())->fetchMatches('live', 20);
?>
<!DOCTYPE html>
<html lang="th"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ควบคุมแมตช์สด — YRScores</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    :root {
        --accent: #FF1493; /* สีหลักที่ใช้เป็น #FF1493 */
        --accent-2: #FF77FF; /* สีม่วงอ่อนใช้ผสม */
        --ink: #0f172a; /* slate-900 */
    }

    body {
        background: #fff; /* พื้นหลังสีขาว */
        color: var(--ink);
    }

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
        background: linear-gradient(135deg, rgba(255, 20, 147, .40), rgba(255, 119, 255, .35));
        mask: linear-gradient(#000, #000) content-box, linear-gradient(#000, #000);
        -webkit-mask: linear-gradient(#000, #000) content-box, linear-gradient(#000, #000);
        padding: 1px;
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        opacity: .38;
    }

    .icon-bubble {
        width: 44px;
        height: 44px;
        border-radius: 9999px;
        display: grid;
        place-items: center;
        color: #fff;
        background: linear-gradient(135deg, var(--accent), var(--accent-2));
        box-shadow: 0 8px 24px rgba(255, 20, 147, .25);
    }

    .icon-bubble i {
        font-size: 18px;
    }

    .topbar {
        background: linear-gradient(90deg, rgba(255, 20, 147, .10), rgba(255, 119, 255, .10));
        border-bottom: 1px solid rgba(15, 23, 42, .08);
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .55rem 1rem;
        border-radius: 999px;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, var(--accent), var(--accent-2));
        box-shadow: 0 10px 24px rgba(255, 20, 147, .28);
    }

    .btn:hover {
        filter: brightness(.98)
    }

    .btn i {
        font-size: 14px;
    }

    .border-rounded {
        border-radius: 9999px;
    }

    .hover\:bg-gray-50:hover {
        background-color: rgba(249, 250, 251, 1);
    }
  </style>
</head>
<body>
  <div class="max-w-5xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">ควบคุมแมตช์สด</h1>
      <a href="/yrscores/admin/dashboard.php" class="text-sm underline">กลับ</a>
    </div>
    <div class="grid gap-4">
      <?php foreach ($rows as $m): ?>
        <div class="card p-4">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500"><?= htmlspecialchars($m['sport']) ?> · <?= htmlspecialchars($m['category']) ?> · เริ่ม <?= htmlspecialchars($m['starts_at']) ?></div>
            <div class="text-sm text-gray-500">views: <?= (int)$m['view_count'] ?></div>
          </div>
          <div class="mt-3 flex items-center gap-8">
            <div class="flex items-center gap-2">
              <span style="color:<?= htmlspecialchars($m['color1']) ?>"><?= htmlspecialchars($m['team1']) ?></span>
              <button data-d1="1" data-id="<?= $m['id'] ?>" class="px-3 py-1 border rounded hover:bg-gray-50">+1</button>
              <button data-d1="-1" data-id="<?= $m['id'] ?>" class="px-3 py-1 border rounded hover:bg-gray-50">-1</button>
              <span class="font-bold ml-2"><?= (int)$m['score1'] ?></span>
            </div>
            <div class="flex items-center gap-2">
              <span style="color:<?= htmlspecialchars($m['color2']) ?>"><?= htmlspecialchars($m['team2']) ?></span>
              <button data-d2="1" data-id="<?= $m['id'] ?>" class="px-3 py-1 border rounded hover:bg-gray-50">+1</button>
              <button data-d2="-1" data-id="<?= $m['id'] ?>" class="px-3 py-1 border rounded hover:bg-gray-50">-1</button>
              <span class="font-bold ml-2"><?= (int)$m['score2'] ?></span>
            </div>
            <button data-end="<?= $m['id'] ?>" class="ml-auto px-4 py-2 rounded text-white" style="background:#FF1493">จบการแข่งขัน</button>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <div class="text-sm text-gray-500">— ยังไม่มีแมตช์ Live —</div>
      <?php endif; ?>
    </div>
  </div>

  <script>
  async function callUpdate(id, d1, d2) {
    const fd = new FormData();
    fd.append('match_id', id);
    fd.append('delta1', d1);
    fd.append('delta2', d2);
    await fetch('/yrscores/api/update_score.php', { method: 'POST', body: fd });
    location.reload();
  }
  document.querySelectorAll('button[data-d1]').forEach(b=>{
    b.onclick = ()=> callUpdate(b.dataset.id, b.dataset.d1, 0);
  });
  document.querySelectorAll('button[data-d2]').forEach(b=>{
    b.onclick = ()=> callUpdate(b.dataset.id, 0, b.dataset.d2);
  });
  document.querySelectorAll('button[data-end]').forEach(b=>{
    b.onclick = async ()=>{
      const fd = new FormData(); fd.append('match_id', b.dataset.end);
      await fetch('/yrscores/api/end_match.php', { method:'POST', body: fd });
      location.reload();
    };
  });
  </script>
</body>
</html>
