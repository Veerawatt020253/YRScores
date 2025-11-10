<?php

declare(strict_types=1);
$cfg = require __DIR__ . '/config.php';
$brand = htmlspecialchars($cfg['app']['brand'], ENT_QUOTES);
$accent = $cfg['app']['accent'];
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= $brand ?> — Live Scores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Mitr:wght@400;500;600;700&display=swap">
    <style>
        :root {
            --accent: <?= $accent ?>;
            --accent-light: rgba(255, 80, 80, 0.2);
            --accent-glow: rgba(255, 80, 80, 0.4);
            --primary-font: 'Mitr', sans-serif;
        }

        body {
            font-family: var(--primary-font);
            background: #ffffff;
            color: #333;
            min-height: 100vh;
        }

        html {
            scroll-behavior: smooth;
        }

        /* Navigation */
        nav {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Hero Slideshow */
        .hero-section {
            position: relative;
            height: 500px;
            overflow: hidden;
            margin-bottom: 3rem;
        }

        .slideshow-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            transition: transform 1s ease-in-out;
        }

        .slide {
            min-width: 100%;
            height: 100%;
            transition: opacity 1s ease-in-out;
        }

        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.7);
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            /* เพิ่มความทึบเล็กน้อย */
            display: flex;
            justify-content: center;
            /* จัดกลางแนวนอน */
            align-items: center;
            /* จัดกลางแนวตั้ง */
        }

        .hero-content {
            text-align: center;
            z-index: 10;
            animation: fadeInUp 1s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 60px rgba(255, 255, 255, 0.5);
        }

        .hero-subtitle {
            font-size: 1.25rem;
            opacity: 0.9;
            font-weight: 300;
        }

        /* Sections */
        section {
            margin-bottom: 3rem;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 40px;
            background: linear-gradient(180deg, var(--accent) 0%, transparent 100%);
            border-radius: 2px;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        th {
            background-color: var(--accent);
            color: white;
        }

        td {
            background-color: #f9f9f9;
        }

        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            background: var(--accent);
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: 0 0 20px var(--accent-glow);
            animation: livePulse 2s ease-in-out infinite;
        }

        @keyframes livePulse {

            0%,
            100% {
                box-shadow: 0 0 20px var(--accent-glow);
            }

            50% {
                box-shadow: 0 0 30px var(--accent-glow), 0 0 40px var(--accent-glow);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }

            table {
                font-size: 0.875rem;
            }

            th, td {
                padding: 0.5rem;
            }
        }
    </style>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="bg-white text-gray-900">
    <!-- NAV -->
    <nav class="bg-white/90 sticky top-0 backdrop-blur z-10 border-b">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="font-extrabold text-xl text-gradient bg-clip-text accent bg-gradient-to-r from-accent to-red-400">
                <?= $brand ?>
            </div>
            <div class="text-sm">Live Scores</div>
        </div>
    </nav>

    <header>
        <!-- Banner Slideshow -->
        <div class="hero-section relative">
            <div class="slideshow-container">
                <div class="slide"><img src="./images/1.png" alt="Banner 1"></div>
                <div class="slide"><img src="./images/2.png" alt="Banner 2"></div>
                <div class="slide"><img src="./images/3.png" alt="Banner 3"></div>
            </div>

            <!-- Text Overlay -->
            <div class="hero-overlay absolute inset-0 flex flex-col items-center justify-center text-center bg-black/40">
                <div class="hero-content text-white">
                    <div class="hero-title text-6xl sm:text-6xl font-bold mb-2">
                        Welcome to <span class="accent"><?= $brand ?></span> Live Scores
                    </div>
                    <p class="text-lg sm:text-xl text-gray-200">
                        ระบบติดตามผลการแข่งขันกีฬาสีแบบเรียลไทม์ ประจำปี 2568
                    </p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6">
        <section>
            <h2 class="section-title mb-3">กำลังแข่ง (Live)</h2>
            <table id="live" class="rounded-lg">
                <thead>
                    <tr>
                        <th>กีฬา · หมวดหมู่</th>
                        <th>ทีม 1</th>
                        <th>คะแนน</th>
                        <th>ทีม 2</th>
                        <th>คะแนน</th>
                        <th>ดูรายละเอียด</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </section>

        <section class="mt-8">
            <h2 class="section-title mb-3">จบแล้ว (Finished)</h2>
            <table id="finished">
                <thead>
                    <tr>
                        <th>กีฬา · หมวดหมู่</th>
                        <th>ทีม 1</th>
                        <th>คะแนน</th>
                        <th>ทีม 2</th>
                        <th>คะแนน</th>
                        <th>ดูรายละเอียด</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </section>

        <section class="mt-8">
            <h2 class="section-title mb-3">สถิติการรับชม</h2>
            <div class="flex flex-wrap gap-3 items-center">
                <div id="total_views" class="chip">รวมผู้ชมทั้งหมด: 0</div>
                <div class="chip">อันดับยอดดู</div>
            </div>
            <div id="top" class="mt-3 grid gap-2"></div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="mt-12 py-4 text-center text-gray-400 text-sm">
        © <?= date('Y') ?> YRScores. By Veerawatt Kanthiphan
    </footer>

    <script>
        const ACCENT = getComputedStyle(document.documentElement).getPropertyValue('--accent').trim();

        // Slideshow functionality
        let currentIndex = 0;
        const slides = document.querySelectorAll('.slide');
        const totalSlides = slides.length;

        function changeSlide() {
            currentIndex = (currentIndex + 1) % totalSlides;
            document.querySelector('.slideshow-container').style.transform = `translateX(-${currentIndex * 100}%)`;
        }

        setInterval(changeSlide, 5000); // Change slide every 5 seconds


        async function fetchScores() {
            const res = await fetch('/yrscores/api/get_scores.php', {
                cache: 'no-store'
            });
            if (!res.ok) {
                console.error('get_scores failed', res.status);
                document.getElementById('live').innerHTML =
                    `<div class="text-sm text-red-600">โหลดข้อมูลไม่ได้ (${res.status})</div>`;
                return;
            }

            const data = await res.json();
            renderLive(data.live);
            renderFinished(data.finished);
            renderTop(data.top);
            document.getElementById('total_views').textContent = `รวมผู้ชมทั้งหมด: ${Number(data.total_views||0).toLocaleString()}`;
        }

        function matchRow(m) {
            const statusBadge = m.status === 'live' ?
                `<span class="chip accent" style="border-color:${ACCENT}">LIVE</span>` :
                `<span class="chip">Finished</span>`;
            return `
        <tr>
            <td>${m.sport} · ${m.category}</td>
            <td>${m.team1}</td>
            <td>${m.score1}</td>
            <td>${m.team2}</td>
            <td>${m.score2}</td>
            <td><a href="match_details.php?id=${m.id}" class="view-btn">ดูแมตช์นี้ · ${Number(m.view_count).toLocaleString()}</a></td>
        </tr>`;
        }

        function renderLive(rows) {
            const el = document.getElementById('live').getElementsByTagName('tbody')[0];
            el.innerHTML = rows.length ? rows.map(matchRow).join('') : `<tr><td colspan="6" class="text-sm text-gray-500">— ไม่มีแมตช์กำลังแข่ง —</td></tr>`;
        }

        function renderFinished(rows) {
            const el = document.getElementById('finished').getElementsByTagName('tbody')[0];
            el.innerHTML = rows.length ? rows.map(matchRow).join('') : `<tr><td colspan="6" class="text-sm text-gray-500">— ยังไม่มีประวัติ —</td></tr>`;
        }

        function renderTop(rows) {
            const el = document.getElementById('top');
            el.innerHTML = rows.map(r => `
        <div class="card p-3 flex justify-between text-sm">
          <div>${r.team1} vs ${r.team2}</div>
          <div class="text-gray-500">${Number(r.view_count).toLocaleString()} views</div>
        </div>`).join('');
        }

        fetchScores();
        setInterval(fetchScores, 7000); // Poll ทุก 7 วินาที
    </script>
</body>

</html>
