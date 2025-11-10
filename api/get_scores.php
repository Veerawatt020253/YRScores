<?php
declare(strict_types=1);

$cfg = require __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!empty($cfg['app']['debug'])) {
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
}

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Match.php';

try {
  $svc  = new MatchService();
  $snap = $svc->getPublicSnapshot(); // เรียกตรง ๆ
  echo json_encode($snap, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  $payload = ['error'=>true,'message'=>'server_error'];
  if (!empty($cfg['app']['debug'])) {
    $payload['exception'] = [
      'type'=>get_class($e), 'message'=>$e->getMessage(),
      'file'=>$e->getFile(), 'line'=>$e->getLine()
    ];
  }
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
}
