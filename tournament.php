<?php
// FILE: tournament.php
// ดูได้หลายกีฬา • ไม่แตะฐานข้อมูล • ผู้ชนะอ้างอิง "ผลจริง" เท่านั้น
declare(strict_types=1);

$cfg = require __DIR__ . '/config.php';
$brand  = htmlspecialchars($cfg['app']['brand'], ENT_QUOTES);
$accent = $cfg['app']['accent'];
$qsSport = isset($_GET['sport']) ? htmlspecialchars((string)$_GET['sport'], ENT_QUOTES) : '';
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?= $brand ?> — Tournament Bracket (Multi-Sport)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Mitr:wght@400;500;600;700&display=swap">
    <style>
        :root {
            --accent: <?= $accent ?>;
            --line: #e5e7eb;
            --accent-glow: rgba(255, 80, 80, .35);
            --primary-font: 'Mitr', sans-serif;
        }

        body {
            font-family: var(--primary-font);
            background: #fff;
            color: #111;
            min-height: 100vh;
        }

        nav {
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, .85);
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .4rem .9rem;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            font-size: .85rem;
            background: #fff;
        }

        .btn {
            padding: .6rem 1rem;
            border-radius: .8rem;
            color: #fff;
            background: var(--accent);
            box-shadow: 0 0 18px var(--accent-glow);
        }

        .btn:hover {
            filter: brightness(.96);
        }

        .match {
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 12px 14px;
            background: #fff;
            box-shadow: 0 1px 2px rgb(0 0 0 / 6%);
            min-width: 320px;
        }

        .pair-title {
            font-size: 1.10rem;
            font-weight: 900;
            letter-spacing: .2px;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .pair-title .vs {
            font-weight: 800;
            opacity: .6;
        }

        .team {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            border: 1px dashed #eee;
            border-radius: 10px;
            padding: 8px 10px;
            background: #fafafa;
        }

        .team .name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .team.win {
            background: rgba(255, 80, 80, .08);
            border-color: var(--accent);
        }

        .team.win .name,
        .team.win .tick {
            font-weight: 900;
        }

        .seed {
            font-size: .8rem;
            color: #9ca3af;
            min-width: 2.5rem;
        }

        .time {
            font-size: .82rem;
            color: #6b7280;
        }

        .col {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .bracket {
            display: grid;
            gap: 26px;
            align-items: center;
        }

        @media(min-width:768px) {
            .bracket {
                grid-auto-flow: column;
                grid-auto-columns: 1fr;
            }
        }

        .winner-banner {
            margin-top: 24px;
            padding: 14px 16px;
            border-radius: 16px;
            background: linear-gradient(135deg, #fff3d4, #ffeab6);
            border: 1px solid #f5d66f;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .06);
            display: none;
        }

        .winner-name {
            font-size: 1.6rem;
            font-weight: 900;
            color: #b45309;
        }

        .flag-real {
            font-size: .72rem;
            font-weight: 700;
            color: #065f46;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 999px;
            padding: .15rem .5rem;
        }

        .flag-tbd {
            font-size: .72rem;
            font-weight: 700;
            color: #92400e;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 999px;
            padding: .15rem .5rem;
        }

        .sport-tabs {
            display: flex;
            gap: .5rem;
            overflow-x: auto;
            padding-bottom: .25rem;
        }

        .sport-tab {
            white-space: nowrap;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            padding: .45rem .9rem;
            cursor: pointer;
        }

        .sport-tab.active {
            background: var(--accent);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 0 16px var(--accent-glow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: .65rem .75rem;
            border-bottom: 1px solid #f1f5f9;
            text-align: left;
        }

        th {
            background: #fafafa;
            font-weight: 700;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 800;
            margin: 1.25rem 0 .75rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
    </style>
</head>

<body>
    <!-- NAV -->
    <nav>
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="font-extrabold text-xl"><?= $brand ?></div>
            <div class="text-sm">By Veerawatt Kanthiphan</div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-6">
        <header class="mb-5">
            <h1 class="text-2xl md:text-3xl font-bold mb-1">Tournament Bracket</h1>

            <div id="sportTabs" class="sport-tabs"></div>
            <div class="mt-2 flex gap-2 flex-wrap items-center">
                <span id="chosenFilters" class="chip">กำลังโหลดข้อมูล…</span>
                <button id="reload" class="btn" title="รีโหลดผลล่าสุด">รีโหลดผลล่าสุด</button>
            </div>
        </header>

        <section class="hidden">
            <div id="meta" class="text-sm text-gray-600 mb-3"></div>
            <div id="notice" class="text-sm text-red-600 mb-3"></div>
            <div id="bracket" class="bracket overflow-x-auto"></div>
            <div id="winner" class="winner-banner">
                <div class="text-sm text-amber-700 font-semibold mb-1">🏆 แชมป์ (ผลจริง)</div>
                <div class="winner-name" id="winnerName"></div>
                <div class="text-sm text-amber-800" id="winnerSeed"></div>
            </div>
        </section>

        <section class="mt-8">
            <div class="section-title">ตารางคะแนน (Standings) — <span id="standingsSport">—</span></div>
            <div class="overflow-x-auto">
                <table id="tblStandings">
                    <thead>
                        <tr>
                            <th>อันดับ</th>
                            <th>ทีม</th>
                            <th>แข่ง</th>
                            <th>ชนะ</th>
                            <th>เสมอ</th>
                            <th>แพ้</th>
                            <th>ได้</th>
                            <th>เสีย</th>
                            <th>ต่าง</th>
                            <th>คะแนน</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>

        <section class="mt-10 hidden">
            <div class="section-title">ผลการแข่งขันทั้งหมดของกีฬา <span id="sportLabel">—</span></div>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-semibold text-green-700 mb-1">กำลังแข่ง (Live)</h3>
                    <table id="tblLive">
                        <thead>
                            <tr>
                                <th>กีฬา·หมวด</th>
                                <th>ทีม 1</th>
                                <th>สกอร์</th>
                                <th>ทีม 2</th>
                                <th>สกอร์</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-800 mb-1">จบแล้ว (Finished)</h3>
                    <table id="tblFinished">
                        <thead>
                            <tr>
                                <th>กีฬา·หมวด</th>
                                <th>ทีม 1</th>
                                <th>สกอร์</th>
                                <th>ทีม 2</th>
                                <th>สกอร์</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <script>
        // ====== ค่านิ่ง ======
        const SCORES_ENDPOINT = '/yrscores/api/get_scores_all.php';
        const LIMIT_TEAMS = 5;
        const MINS_PER_MATCH = 45;
        const QS_SPORT = "<?= $qsSport ?>";

        // ====== Utils ======
        function nextPow2(n) {
            let p = 1;
            while (p < n) p <<= 1;
            return p;
        }

        function fmtTime(d) {
            const dd = new Date(d);
            return dd.toLocaleString(undefined, {
                dateStyle: 'medium',
                timeStyle: 'short'
            });
        }

        function bracketOrder(n) {
            if (n === 1) return [1];
            const prev = bracketOrder(n / 2),
                res = [];
            for (let i = 0; i < prev.length; i++) {
                res.push(prev[i]);
                res.push(n + 1 - prev[i]);
            }
            return res;
        }

        function rowHtml(m) {
            return `<tr><td>${m.sport||'-'} · ${m.category||'-'}</td><td>${m.team1}</td><td>${Number(m.score1||0)}</td><td>${m.team2}</td><td>${Number(m.score2||0)}</td></tr>`;
        }

        async function loadScores() {
            const res = await fetch(SCORES_ENDPOINT, {
                cache: 'no-store'
            });
            if (!res.ok) {
                document.getElementById('notice').textContent = `โหลดข้อมูลไม่ได้ (${res.status}) จาก ${SCORES_ENDPOINT}`;
                return null;
            }
            return await res.json();
        }

        function sportsFromData(data) {
            const all = [...(data.live || []), ...(data.finished || [])];
            const cnt = new Map();
            for (const m of all) {
                const s = m.sport || '';
                if (!s) continue;
                cnt.set(s, (cnt.get(s) || 0) + 1);
            }
            return Array.from(cnt.entries()).sort((a, b) => b[1] - a[1]).map(([k]) => k);
        }

        function dominantCategoryForSport(data, sport) {
            const all = [...(data.live || []), ...(data.finished || [])];
            const cnt = new Map();
            let best = null,
                bestN = -1;
            for (const m of all) {
                if (m.sport !== sport) continue;
                const c = m.category || '';
                cnt.set(c, (cnt.get(c) || 0) + 1);
                if (cnt.get(c) > bestN) {
                    best = c;
                    bestN = cnt.get(c);
                }
            }
            return best || '';
        }

        function computeTeamsPower(matches, sport) {
            const stat = new Map();
            for (const m of matches) {
                if (m.sport !== sport) continue;
                const t1 = m.team1,
                    t2 = m.team2;
                const s1 = Number(m.score1 || 0),
                    s2 = Number(m.score2 || 0);
                const v = Number(m.view_count || 0);
                if (!stat.has(t1)) stat.set(t1, {
                    games: 0,
                    goalDiffSum: 0,
                    views: 0
                });
                if (!stat.has(t2)) stat.set(t2, {
                    games: 0,
                    goalDiffSum: 0,
                    views: 0
                });
                stat.get(t1).games++;
                stat.get(t2).games++;
                stat.get(t1).goalDiffSum += (s1 - s2);
                stat.get(t2).goalDiffSum += (s2 - s1);
                stat.get(t1).views += v;
                stat.get(t2).views += v;
            }
            const teams = [];
            for (const [name, s] of stat.entries()) {
                const avgDiff = s.games ? (s.goalDiffSum / s.games) : 0;
                const power = (s.games ? (avgDiff * 10) : 0) + (Math.log10(s.views + 1) * 15);
                teams.push({
                    name,
                    sport,
                    power: s.games ? Math.max(60, Math.round(power + 80)) : 80
                });
            }
            if (teams.length === 0) {
                const names = new Set(matches.filter(m => m.sport === sport).flatMap(m => [m.team1, m.team2]));
                names.forEach(n => teams.push({
                    name: n,
                    sport,
                    power: 80
                }));
            }
            return teams.filter(t => t && t.name).sort((a, b) => (b.power || 0) - (a.power || 0)).map((t, i) => ({
                ...t,
                seed: i + 1
            }));
        }

        // ① วางทีมตาม seeding แล้วเติม BYE
        function seedWithByes(sortedTeams, limit) {
            const slice = sortedTeams.slice(0, limit);
            const N = slice.length,
                target = nextPow2(N),
                order = bracketOrder(target);
            const slots = new Array(target);
            for (let j = 0; j < order.length; j++) {
                const wantedSeed = order[j];
                slots[j] = wantedSeed <= N ? slice[wantedSeed - 1] : {
                    name: 'BYE',
                    power: 0,
                    seed: null,
                    bye: true
                };
            }
            return slots;
        }

        function headToHeadPoints(finished, sport, A, B) {
            let ptsA = 0,
                ptsB = 0;
            for (const m of (finished || [])) {
                if (m.sport !== sport || m.status !== 'finished') continue;
                const ab = (m.team1 === A && m.team2 === B);
                const ba = (m.team1 === B && m.team2 === A);
                if (!ab && !ba) continue;
                const s1 = Number(m.score1 || 0),
                    s2 = Number(m.score2 || 0);
                if (s1 > s2) {
                    if (ab) ptsA += 3;
                    else ptsB += 3;
                } else if (s1 < s2) {
                    if (ab) ptsB += 3;
                    else ptsA += 3;
                } else {
                    ptsA += 1;
                    ptsB += 1;
                }
            }
            return {
                ptsA,
                ptsB
            };
        }


        // ——— Standings (จัดอันดับตาม ชนะ→แพ้ ก่อน) ———
        function computeStandings(finished, sport) {
            const table = new Map(); // name -> stats
            function ensure(t) {
                if (!table.has(t)) table.set(t, {
                    team: t,
                    P: 0,
                    W: 0,
                    D: 0,
                    L: 0,
                    GF: 0,
                    GA: 0,
                    GD: 0,
                    PTS: 0
                });
                return table.get(t);
            }

            for (const m of (finished || [])) {
                if (m.sport !== sport || m.status !== 'finished') continue;
                const a = ensure(m.team1),
                    b = ensure(m.team2);
                const s1 = Number(m.score1 || 0),
                    s2 = Number(m.score2 || 0);
                a.P++;
                b.P++;
                a.GF += s1;
                a.GA += s2;
                b.GF += s2;
                b.GA += s1;
                if (s1 > s2) {
                    a.W++;
                    b.L++;
                    a.PTS += 3;
                } else if (s1 < s2) {
                    b.W++;
                    a.L++;
                    b.PTS += 3;
                } else {
                    a.D++;
                    b.D++;
                    a.PTS += 1;
                    b.PTS += 1;
                }
            }
            for (const st of table.values()) st.GD = st.GF - st.GA;

            const arr = Array.from(table.values());

            // ตัวช่วยนับแต้ม H2H สำหรับใช้ตัดสินตอนที่ W และ L เท่ากัน
            function h2hPts(A, B) {
                let a = 0,
                    b = 0;
                for (const m of (finished || [])) {
                    if (m.sport !== sport || m.status !== 'finished') continue;
                    const ab = (m.team1 === A && m.team2 === B);
                    const ba = (m.team1 === B && m.team2 === A);
                    if (!ab && !ba) continue;
                    const s1 = Number(m.score1 || 0),
                        s2 = Number(m.score2 || 0);
                    if (s1 > s2) {
                        if (ab) a += 3;
                        else b += 3;
                    } else if (s1 < s2) {
                        if (ab) b += 3;
                        else a += 3;
                    } else {
                        a += 1;
                        b += 1;
                    }
                }
                return a - b; // >0 แปลว่า A เหนือกว่า
            }

            // เรียงหลัก: W (desc) → L (asc) → H2H → GD (desc) → GF (desc) → ชื่อทีม (asc)
            arr.sort((x, y) => {
                if (y.W !== x.W) return y.W - x.W;
                if (x.L !== y.L) return x.L - y.L;
                const hv = h2hPts(x.team, y.team);
                if (hv !== 0) return -hv; // ถ้า A ได้มากกว่า → x ขึ้นก่อน
                if (y.GD !== x.GD) return y.GD - x.GD;
                if (y.GF !== x.GF) return y.GF - x.GF;
                return ('' + x.team).localeCompare(y.team, undefined, {
                    numeric: true,
                    sensitivity: 'base'
                });
            });

            return arr.map((s, i) => ({
                ...s,
                rank: i + 1
            }));
        }


        function medalForRank(rank) {
            if (rank === 1) return '🥇 ทอง';
            if (rank === 2) return '🥈 เงิน';
            if (rank === 3) return '🥉 ทองแดง';
            return '—';
        }


        function renderStandings(data, sport) {
            document.getElementById('standingsSport').textContent = sport || '—';
            const tbl = document.getElementById('tblStandings');
            const thead = tbl.querySelector('thead');
            const tbody = tbl.querySelector('tbody');

            // หัวตาราง
            thead.innerHTML = `
    <tr>
      <th>อันดับ</th>
      <th>เหรียญ</th>
      <th>ทีม</th>
    </tr>
  `;

            const ordered = computeStandings(data.finished || [], sport);
            const rows = ordered.map((s, i) => {
                // ถ้าเกินอันดับ 3 ให้เป็น “3” ทั้งหมด
                const rank = i < 2 ? i + 1 : 3;
                const medal = rank === 1 ? '🥇 ทอง' :
                    rank === 2 ? '🥈 เงิน' :
                    '🥉 ทองแดง';
                return `<tr>
      <td>${rank}</td>
      <td>${medal}</td>
      <td>${s.team}</td>
    </tr>`;
            }).join('');

            tbody.innerHTML = rows || `<tr><td colspan="3" class="text-sm text-gray-500">— ยังไม่มีข้อมูลพอคำนวณอันดับ —</td></tr>`;
        }



        // ✅ ใช้ผลจริงทั้งหมดของกีฬา (ไม่กรองหมวด)
        function headToHeadWinner(a, b, finished, sport) {
            const h2h = (finished || [])
                .filter(m => {
                    if (m.sport !== sport) return false;
                    const ab = (m.team1 === a.name && m.team2 === b.name);
                    const ba = (m.team1 === b.name && m.team2 === a.name);
                    return (m.status === 'finished') && (ab || ba);
                })
                .sort((m1, m2) => new Date(m2.start_time || m2.updated_at || 0) - new Date(m1.start_time || m1.updated_at || 0));
            if (!h2h.length) return {
                winner: null,
                source: null
            };
            const last = h2h[0],
                s1 = Number(last.score1 || 0),
                s2 = Number(last.score2 || 0);
            let w = null;
            if (last.team1 === a.name && s1 > s2) w = a;
            else if (last.team2 === a.name && s2 > s1) w = a;
            else if (last.team1 === b.name && s1 > s2) w = b;
            else if (last.team2 === b.name && s2 > s1) w = b;
            return {
                winner: w,
                source: last
            };
        }

        // ② สร้างรอบจากผลจริง
        function buildRoundsReal(slots, baseStart, minsPerMatch, finished, sport) {
            const rounds = [];
            let current = slots.map(t => ({
                team: t
            }));
            let base = new Date(baseStart);
            const gap = minsPerMatch;
            let idx = 1;

            while (true) {
                const alive = current.filter(x => x.team && !x.team.bye && !x.team.tbd);
                if (alive.length <= 1) break;

                const pairs = [];
                for (let i = 0; i < current.length; i += 2) {
                    const a = current[i]?.team,
                        b = current[i + 1]?.team;
                    if (!a && !b) continue;
                    if ((a?.bye) && (b?.bye)) continue;

                    const time = new Date(base.getTime() + (pairs.length * gap) * 60000);
                    let winner = null,
                        src = null,
                        flag = '';
                    if (a?.bye && !b?.bye) {
                        winner = b;
                        flag = 'BYE';
                    } else if (!a?.bye && b?.bye) {
                        winner = a;
                        flag = 'BYE';
                    } else if (!a?.bye && !b?.bye) {
                        const h2h = headToHeadWinner(a, b, finished, sport);
                        winner = h2h.winner;
                        src = h2h.source;
                        flag = winner ? 'REAL' : 'TBD';
                    }

                    pairs.push({
                        round: idx,
                        a: a || {
                            name: 'BYE',
                            bye: true
                        },
                        b: b || {
                            name: 'BYE',
                            bye: true
                        },
                        time,
                        winner,
                        flag,
                        source: src
                    });
                }
                rounds.push(pairs);

                const next = [];
                for (const p of pairs) {
                    if (p.winner && !p.winner.bye) {
                        next.push({
                            team: p.winner
                        });
                    } else {
                        next.push({
                            team: {
                                name: 'TBD',
                                tbd: true,
                                seed: null
                            }
                        });
                    }
                }
                current = next;
                base = new Date(base.getTime() + (pairs.length * gap) * 60000);
                idx++;
            }
            return rounds;
        }

        function renderBracket(rounds, meta) {
            const wrap = document.getElementById('bracket');
            wrap.innerHTML = '';
            wrap.style.gridTemplateColumns = `repeat(${rounds.length}, minmax(340px,1fr))`;
            rounds.forEach(pairs => {
                const col = document.createElement('div');
                col.className = 'col';
                pairs.forEach(p => {
                    const aWin = p.winner && (p.winner.name === p.a.name),
                        bWin = p.winner && (p.winner.name === p.b.name);
                    const flagLabel = p.flag === 'REAL' ? `<span class="flag-real">ผลจริง</span>` :
                        p.flag === 'BYE' ? `<span class="chip">BYE</span>` :
                        `<span class="flag-tbd">TBD</span>`;
                    const srcInfo = p.source ? `<div class="text-xs text-gray-500 mt-1">อ้างอิง: ${p.source.team1} ${p.source.score1}–${p.source.score2} ${p.source.team2}</div>` : '';
                    const box = document.createElement('div');
                    box.className = 'match';
                    box.innerHTML = `
                        <div class="flex items-center justify-between mb-2">
                          <div class="time">${fmtTime(p.time)}</div>
                          <div class="text-xs text-gray-500">รอบ ${p.round}</div>
                        </div>
                        <div class="pair-title mb-2">
                          <span>${p.a.name}</span><span class="vs">vs</span><span>${p.b.name}</span>
                          <span class="ml-auto">${flagLabel}</span>
                        </div>
                        <div class="team ${aWin?'win':''}">
                          <span class="seed">${p.a.seed ? '#'+p.a.seed : ''}</span>
                          <span class="name">${p.a.name}</span>
                          <span class="tick">${aWin?'✓':''}</span>
                        </div>
                        <div class="team ${bWin?'win':''}" style="margin-top:8px;">
                          <span class="seed">${p.b.seed ? '#'+p.b.seed : ''}</span>
                          <span class="name">${p.b.name}</span>
                          <span class="tick">${bWin?'✓':''}</span>
                        </div>
                        ${srcInfo}
                    `;
                    col.appendChild(box);
                });
                wrap.appendChild(col);
            });

            const last = rounds[rounds.length - 1]?.[0];
            const banner = document.getElementById('winner');
            if (last && last.winner && !last.winner.tbd && !last.winner.bye) {
                document.getElementById('winnerName').textContent = last.winner.name;
                document.getElementById('winnerSeed').textContent = `seed ${last.winner.seed ?? '-'}`;
                banner.style.display = 'block';
            } else {
                banner.style.display = 'none';
            }

            document.getElementById('meta').textContent =
                `กีฬา: ${meta.sport} • ใช้ทีม Top ${meta.limit} จากทั้งหมด ${meta.teamCount} ทีม • เริ่ม ${fmtTime(meta.baseStart)} • ${meta.minsPerMatch} นาที/คู่`;
        }

        function renderMatchesTables(data, sport) {
            document.getElementById('sportLabel').textContent = sport || '—';
            const liveBody = document.querySelector('#tblLive tbody');
            const finBody = document.querySelector('#tblFinished tbody');
            const liveRows = (data.live || []).filter(m => m.sport === sport).map(rowHtml).join('');
            const finRows = (data.finished || []).filter(m => m.sport === sport).map(rowHtml).join('');
            liveBody.innerHTML = liveRows || `<tr><td colspan="5" class="text-sm text-gray-500">— ไม่มีแมตช์กำลังแข่ง —</td></tr>`;
            finBody.innerHTML = finRows || `<tr><td colspan="5" class="text-sm text-gray-500">— ยังไม่มีผลจบ —</td></tr>`;
        }

        function renderSportTabs(sports, current) {
            const wrap = document.getElementById('sportTabs');
            wrap.innerHTML = '';
            sports.forEach(s => {
                const el = document.createElement('div');
                el.className = 'sport-tab' + (s === current ? ' active' : '');
                el.textContent = s;
                el.addEventListener('click', () => selectSport(s));
                wrap.appendChild(el);
            });
        }

        // ====== โฟลว์หลัก ======
        let CACHE = null,
            CURRENT_SPORT = '',
            CURRENT_CATEGORY = '',
            TEAMS = [];

        async function selectSport(sport) {
            if (!CACHE) return;
            CURRENT_SPORT = sport;
            CURRENT_CATEGORY = dominantCategoryForSport(CACHE, sport); // เพื่อแสดงผล
            document.getElementById('chosenFilters').textContent = `กีฬา: ${CURRENT_SPORT} · หมวด: ${CURRENT_CATEGORY||'-'}`;

            // ตารางคะแนน + ตารางแมตช์
            renderStandings(CACHE, CURRENT_SPORT);
            renderMatchesTables(CACHE, CURRENT_SPORT);

            // Bracket
            const all = [...(CACHE.live || []), ...(CACHE.finished || [])];
            TEAMS = computeTeamsPower(all, CURRENT_SPORT);
            if (TEAMS.length < 2) {
                document.getElementById('notice').textContent = 'ข้อมูลทีมไม่พอจะจัดสาย';
                document.getElementById('bracket').innerHTML = '';
                document.getElementById('winner').style.display = 'none';
                return;
            }
            const slots = seedWithByes(TEAMS, LIMIT_TEAMS);
            const base = new Date();
            const rounds = buildRoundsReal(slots, base, MINS_PER_MATCH, (CACHE.finished || []), CURRENT_SPORT);
            renderBracket(rounds, {
                sport: CURRENT_SPORT,
                teamCount: TEAMS.length,
                limit: LIMIT_TEAMS,
                baseStart: base,
                minsPerMatch: MINS_PER_MATCH
            });

            renderSportTabs(sportsFromData(CACHE), CURRENT_SPORT);
        }

        async function boot() {
            document.getElementById('notice').textContent = '';
            CACHE = await loadScores();
            if (!CACHE) return;

            const sports = sportsFromData(CACHE);
            if (!sports.length) {
                document.getElementById('notice').textContent = 'ไม่พบข้อมูลกีฬาในระบบ';
                return;
            }
            const initial = QS_SPORT && sports.includes(QS_SPORT) ? QS_SPORT : sports[0];
            renderSportTabs(sports, initial);
            await selectSport(initial);
        }

        document.getElementById('reload').addEventListener('click', boot);
        (async () => {
            await boot();
        })();
    </script>
</body>

</html>