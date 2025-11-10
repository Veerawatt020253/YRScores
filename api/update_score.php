<?php
declare(strict_types=1);
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Match.php';

Auth::startSession();
if (!Auth::check()) { http_response_code(403); exit('forbidden'); }

$matchId = (int)($_POST['match_id'] ?? 0);
$delta1  = (int)($_POST['delta1'] ?? 0); // +1 / -1
$delta2  = (int)($_POST['delta2'] ?? 0);

header('Content-Type: application/json; charset=utf-8');
try {
  (new MatchService())->updateScore($matchId, $delta1, $delta2);
  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'server_error']);
}
