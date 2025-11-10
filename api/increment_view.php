<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Match.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false, 'message'=>'method_not_allowed']);
    exit;
  }
  $matchId = (int)($_POST['match_id'] ?? 0);
  if ($matchId <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'message'=>'bad_match_id']);
    exit;
  }
  $svc = new MatchService();
  $svc->incrementView($matchId);
  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'message'=>'server_error']);
}
