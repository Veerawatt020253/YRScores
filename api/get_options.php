<?php
declare(strict_types=1);
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? '';
$sportId = (int)($_GET['sport_id'] ?? 0);

try {
  $pdo = Database::get();

  if ($type === 'categories') {
    if ($sportId <= 0) { echo json_encode([]); exit; }
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE sport_id = ? ORDER BY name ASC");
    $stmt->execute([$sportId]);
    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($type === 'teams') {
    if ($sportId <= 0) { echo json_encode([]); exit; }
    $stmt = $pdo->prepare("SELECT id, name FROM teams WHERE sport_id = ? ORDER BY name ASC");
    $stmt->execute([$sportId]);
    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
    exit;
  }

  http_response_code(400);
  echo json_encode(['error'=>'bad_request']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'server_error']);
}
