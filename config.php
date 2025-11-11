<?php
// yrscores/config.php
declare(strict_types=1);

return [
  'db' => [
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'yrscores',
    'user' => 'root',
    'pass' => 'root',          // ใส่รหัสจริง
    'charset' => 'utf8mb4'
  ],
  'session' => [
    'name' => 'YRSID',
    'secure' => false,     // เปิด true บน HTTPS
    'http_only' => true,
    'same_site' => 'Lax'
  ],
  'app' => [
    'brand' => 'YRScores',
    'accent' => '#FF1493',
    'debug'  => false,
  ]
];
