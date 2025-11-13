<?php
// FILE: yrscores/admin/scores_manage_api.php
declare(strict_types=1);
require_once __DIR__ . '/../classes/Auth.php';
Auth::startSession();
Auth::requireLogin();

// CSRF (ง่าย ๆ)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>จัดการคะแนนทั้งหมด — ใช้ API</title>
  <link rel="stylesheet" href="../css/style.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    :root{ --accent:#FF1493; --accent2:#FF77FF }
    body{
      background:
        radial-gradient(20rem 20rem at 8% -8%, rgba(255,0,168,.10), transparent 60%),
        radial-gradient(24rem 24rem at 110% 12%, rgba(162,28,175,.10), transparent 55%), #fff;
      color:#0f172a;
    }
    .btn{display:inline-flex;align-items:center;gap:.5rem;padding:.55rem 1rem;border-radius:.75rem;
      color:#fff;background:linear-gradient(135deg,var(--accent),var(--accent2));box-shadow:0 10px 24px rgba(255,0,168,.25)}
    .btn:hover{filter:brightness(.98)}
    .card{background:#fff;border:1px solid rgba(2,6,23,.08);border-radius:1rem}
    .thead-soft{background:linear-gradient(0deg, rgba(255,0,168,.06), rgba(255,0,168,.06))}
    .chip{border:1px solid rgba(2,6,23,.12);padding:.25rem .5rem;border-radius:999px;font-size:.75rem}
    .topbar{background:linear-gradient(90deg, rgba(255,0,168,.10), rgba(162,28,175,.10));border-bottom:1px solid rgba(2,6,23,.08)}
    .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;align-items:center;justify-content:center;z-index:50}
  </style>
</head>
<body>
<div class="topbar">
  <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <span class="inline-flex justify-center items-center w-10 h-10 rounded-full text-white"
            style="background:linear-gradient(135deg,var(--accent),var(--accent2))"><i class="fa-solid fa-table"></i></span>
      <h1 class="text-xl sm:text-2xl font-bold">จัดการคะแนนทั้งหมด (API)</h1>
      <span class="chip hidden sm:inline">YRScores Admin</span>
    </div>
    <div class="flex items-center gap-3">
      <a href="/yrscores/admin/dashboard.php" class="underline text-sm">แดชบอร์ด</a>
      <a href="/yrscores" class="underline text-sm" target="_blank">ดูหน้าเว็บ</a>
    </div>
  </div>
</div>

<main class="max-w-6xl mx-auto px-4 py-6 space-y-6">
  <!-- Filters -->
  <section class="card p-4">
    <div class="grid md:grid-cols-5 gap-3">
      <div class="md:col-span-2">
        <label class="block text-sm text-slate-600 mb-1">ค้นหา (ทีม/กีฬา/ลีก)</label>
        <input id="q" type="text" class="w-full border rounded-lg px-3 py-2" placeholder="เช่น ฟ้า หรือ Premier">
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">กีฬา</label>
        <select id="sport" class="w-full border rounded-lg px-3 py-2"><option value="">— ทั้งหมด —</option></select>
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">ลีก/หมวด</label>
        <select id="category" class="w-full border rounded-lg px-3 py-2"><option value="">— ทั้งหมด —</option></select>
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">สถานะ</label>
        <select id="status" class="w-full border rounded-lg px-3 py-2">
          <option value="">— ทั้งหมด —</option>
          <option value="scheduled">กำหนดการ</option>
          <option value="live">ถ่ายทอดสด</option>
          <option value="finished">จบแล้ว</option>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">ตั้งแต่วันที่</label>
        <input id="from" type="date" class="w-full border rounded-lg px-3 py-2">
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">ถึงวันที่</label>
        <input id="to" type="date" class="w-full border rounded-lg px-3 py-2">
      </div>
      <div class="md:col-span-2 flex items-end gap-2">
        <button id="btnSearch" class="btn"><i class="fa-solid fa-magnifying-glass"></i> ค้นหา</button>
        <button id="btnReset" class="px-4 py-2 rounded-lg border">ล้าง</button>
        <span id="metaCnt" class="text-sm text-slate-600 ml-auto"></span>
      </div>
    </div>
  </section>

  <!-- Table -->
  <section class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm" id="tbl">
        <thead class="thead-soft">
          <tr>
            <th class="text-left p-3">ID</th>
            <th class="text-left p-3">ทีม</th>
            <th class="text-left p-3 hidden md:table-cell">กีฬา/ลีก</th>
            <th class="text-left p-3">เวลา</th>
            <th class="text-left p-3">สกอร์</th>
            <th class="text-left p-3">สถานะ</th>
            <th class="text-left p-3 hidden lg:table-cell">วิว</th>
            <th class="p-3"></th>
          </tr>
        </thead>
        <tbody class="bg-white/70"></tbody>
      </table>
    </div>
  </section>
</main>

<!-- Modal Edit -->
<div id="modal" class="modal-backdrop">
  <div class="bg-white rounded-xl w-[95vw] max-w-xl p-4">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-bold">แก้ไขคะแนน/สถานะ</h3>
      <button id="closeModal" class="text-slate-500 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form id="editForm" class="mt-4 grid grid-cols-2 gap-3">
      <input type="hidden" name="csrf" value="<?=htmlspecialchars($CSRF, ENT_QUOTES)?>">
      <input type="hidden" name="id" id="f_id">

      <div class="col-span-2">
        <label class="text-sm text-slate-600">คู่แข่ง</label>
        <div class="mt-1 p-2 border rounded-md bg-slate-50">
          <span id="f_team1"></span> <span class="text-slate-400">vs</span> <span id="f_team2"></span>
        </div>
      </div>

      <div>
        <label class="text-sm text-slate-600">สกอร์ทีม 1</label>
        <input type="number" min="0" name="score1" id="f_score1" class="w-full border rounded-md px-3 py-2">
      </div>
      <div>
        <label class="text-sm text-slate-600">สกอร์ทีม 2</label>
        <input type="number" min="0" name="score2" id="f_score2" class="w-full border rounded-md px-3 py-2">
      </div>
      <div>
        <label class="text-sm text-slate-600">สถานะ</label>
        <select name="status" id="f_status" class="w-full border rounded-md px-3 py-2">
          <option value="scheduled">scheduled</option>
          <option value="live">live</option>
          <option value="finished">finished</option>
        </select>
      </div>
      <div>
        <label class="text-sm text-slate-600">เวลาเริ่ม (YYYY-MM-DD HH:MM:SS)</label>
        <input type="text" name="starts_at" id="f_starts_at" class="w-full border rounded-md px-3 py-2" placeholder="2025-11-13 14:00:00">
      </div>
      <div class="col-span-2">
        <label class="text-sm text-slate-600">วิว</label>
        <input type="number" min="0" name="view_count" id="f_view" class="w-full border rounded-md px-3 py-2">
      </div>

      <div class="col-span-2 flex items-center justify-end gap-2 mt-2">
        <button type="button" id="cancelBtn" class="px-4 py-2 rounded-md border">ยกเลิก</button>
        <button type="submit" class="btn"><i class="fa-regular fa-floppy-disk"></i> บันทึก</button>
      </div>
    </form>
  </div>
</div>

<script>
const API_ENDPOINT = '../api/get_scores.php';
const UPDATE_ENDPOINT = '../api/update_match.php';
const CSRF = <?= json_encode($CSRF) ?>;

const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);

const state = { raw: [], filtered: [] };

function normalizeRows(snapshot){
  // รวมทุกสถานะเข้าตารางเดียว
  const pack = (arr, status) => (arr||[]).map(m=>({...m, status}));
  const rows = [
    ...pack(snapshot.live, 'live'),
    ...pack(snapshot.finished, 'finished'),
    ...pack(snapshot.scheduled, 'scheduled')
  ];
  return rows;
}

function uniqueSorted(arr){
  return [...new Set(arr.filter(Boolean))].sort((a,b)=>(''+a).localeCompare(b,undefined,{numeric:true,sensitivity:'base'}));
}

function fillOptions(rows){
  const sports = uniqueSorted(rows.map(r=>r.sport));
  const cats   = uniqueSorted(rows.map(r=>r.category));
  const selSport = $('#sport'), selCat = $('#category');
  sports.forEach(s=>{ const o=document.createElement('option'); o.value=o.textContent=s; selSport.appendChild(o); });
  cats.forEach(c=>{ const o=document.createElement('option'); o.value=o.textContent=c; selCat.appendChild(o); });
}

function withinDateRange(starts_at, from, to){
  if(!starts_at) return true;
  const t = new Date(starts_at.replace(' ','T'));
  if(from){ const f=new Date(from+'T00:00:00'); if(t < f) return false; }
  if(to){ const e=new Date(to+'T23:59:59'); if(t > e) return false; }
  return true;
}

function applyFilter(){
  const kw = ($('#q').value||'').trim().toLowerCase();
  const sp = $('#sport').value||'';
  const ct = $('#category').value||'';
  const st = $('#status').value||'';
  const fd = $('#from').value||'';
  const td = $('#to').value||'';

  state.filtered = state.raw.filter(m=>{
    if(sp && m.sport !== sp) return false;
    if(ct && m.category !== ct) return false;
    if(st && m.status !== st) return false;
    if(!withinDateRange(m.starts_at||m.start_time||m.updated_at, fd, td)) return false;
    if(kw){
      const blob = [
        m.team1, m.team2, m.sport, m.category, m.status
      ].map(x=> (x||'').toString().toLowerCase()).join(' ');
      if(!blob.includes(kw)) return false;
    }
    return true;
  });

  renderTable(state.filtered);
  $('#metaCnt').textContent = `ผลลัพธ์ ${state.filtered.length} รายการ`;
}

function badge(status){
  const map = {
    scheduled:'bg-gray-100 text-gray-700 border-gray-200',
    live:'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
    finished:'bg-emerald-50 text-emerald-700 border-emerald-200'
  };
  const cls = map[status] || 'bg-slate-100 text-slate-700 border-slate-200';
  return `<span class="px-2 py-1 rounded-full text-xs border ${cls}">${status||'-'}</span>`;
}

function renderTable(rows){
  const tbody = $('#tbl tbody');
  if(!rows.length){
    tbody.innerHTML = `<tr><td colspan="8" class="p-6 text-center text-slate-500">ไม่พบรายการ</td></tr>`;
    return;
  }
  tbody.innerHTML = rows.map(r=>{
    const id = r.id ?? '';
    const starts = r.starts_at || r.start_time || '';
    const s1 = Number(r.score1||0), s2 = Number(r.score2||0);
    const view = Number(r.view_count||0);
    const editBtn = id
      ? `<button class="px-3 py-1 rounded-md border hover:bg-slate-50" data-edit
            data-id="${id}"
            data-team1="${(r.team1||'').replace(/"/g,'&quot;')}"
            data-team2="${(r.team2||'').replace(/"/g,'&quot;')}"
            data-score1="${s1}" data-score2="${s2}"
            data-status="${r.status||''}"
            data-starts_at="${starts}"
            data-view="${view}"
            hidden
          ><i class="fa-regular fa-pen-to-square"></i> แก้ไข</button>`
      : `<span class="text-slate-400 text-xs">— ไม่มี id แก้ไขไม่ได้ —</span>`;
    return `<tr class="border-t hover:bg-fuchsia-50/40 transition">
      <td class="p-3">${id||'-'}</td>
      <td class="p-3 font-medium">${r.team1||'-'} <span class="text-slate-400">vs</span> ${r.team2||'-'}</td>
      <td class="p-3 text-slate-600 hidden md:table-cell">${r.sport||'-'} / ${r.category||'-'}</td>
      <td class="p-3">${starts||'-'}</td>
      <td class="p-3"><span class="font-semibold">${s1}</span><span class="text-slate-400">:</span><span class="font-semibold">${s2}</span></td>
      <td class="p-3">${badge(r.status)}</td>
      <td class="p-3 hidden lg:table-cell">${view}</td>
      <td class="p-3">${editBtn}</td>
    </tr>`;
  }).join('');

  // bind edit buttons
  $$('#tbl [data-edit]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      openEdit({
        id: btn.dataset.id,
        team1: btn.dataset.team1,
        team2: btn.dataset.team2,
        score1: btn.dataset.score1,
        score2: btn.dataset.score2,
        status: btn.dataset.status,
        starts_at: btn.dataset.starts_at,
        view: btn.dataset.view
      });
    });
  });
}

// modal handlers
const modal = document.getElementById('modal');
const closeModal = ()=> modal.style.display='none';
document.getElementById('closeModal').addEventListener('click', closeModal);
document.getElementById('cancelBtn').addEventListener('click', closeModal);

function openEdit(m){
  modal.style.display='flex';
  document.getElementById('f_id').value = m.id||'';
  document.getElementById('f_team1').textContent = m.team1||'-';
  document.getElementById('f_team2').textContent = m.team2||'-';
  document.getElementById('f_score1').value = m.score1||0;
  document.getElementById('f_score2').value = m.score2||0;
  document.getElementById('f_status').value = m.status||'scheduled';
  document.getElementById('f_starts_at').value = m.starts_at||'';
  document.getElementById('f_view').value = m.view||0;
}

document.getElementById('editForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action','update'); // ให้ endpoint แยกได้
  try {
    const res = await fetch(UPDATE_ENDPOINT, { method:'POST', body: fd });
    const data = await res.json();
    if(!data.ok) throw new Error(data.error||'อัปเดตไม่สำเร็จ');
    Swal.fire({icon:'success',title:'บันทึกแล้ว',timer:1200,showConfirmButton:false});
    closeModal();
    // reload data from GET API (ให้ข้อมูลใหม่สะท้อน)
    await boot();
  } catch(err){
    Swal.fire({icon:'error',title:'มีข้อผิดพลาด', text:String(err.message||err)});
  }
});

// filters
document.getElementById('btnSearch').addEventListener('click', applyFilter);
document.getElementById('btnReset').addEventListener('click', ()=>{
  ['q','sport','category','status','from','to'].forEach(id=>{ document.getElementById(id).value=''; });
  applyFilter();
});

async function boot(){
  // ดึงจาก API เดียว
  const res = await fetch(API_ENDPOINT, {cache:'no-store'});
  if(!res.ok){
    Swal.fire({icon:'error', title:'โหลดข้อมูลล้มเหลว', text:`${res.status} ${res.statusText}`});
    return;
  }
  const snap = await res.json();
  const rows = normalizeRows(snap);
  state.raw = rows;
  // เติมตัวเลือก dropdown (ครั้งแรก)
  if(!$('#sport').dataset.filled){
    fillOptions(rows);
    $('#sport').dataset.filled = '1';
  }
  applyFilter();
}

boot();
</script>
</body>
</html>
