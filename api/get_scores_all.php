<?php

declare(strict_types=1);

$cfg = require __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// ห้ามให้ PHP พ่น HTML error ออกจอ
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// เก็บ output ที่อาจหลุดมาจากไฟล์ที่ include
ob_start();

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Match.php';

try {
    $svc  = new MatchService();

    // ถ้าอยาก “เอาทั้งหมด” ให้รับพารามฯ (ไม่บังคับ)
    $range = $_GET['range'] ?? 'today'; // today|all
    $from  = $_GET['from']  ?? null;
    $to    = $_GET['to']    ?? null;

    // ถ้า getPublicSnapshot รองรับตัวเลือก ก็ส่งลงไป
    if ((new ReflectionMethod(MatchService::class, 'getPublicSnapshot'))->getNumberOfParameters() >= 1) {
        $snap = $svc->getPublicSnapshot(['range' => $range, 'from' => $from, 'to' => $to]);
    } else {
        $snap = $svc->getPublicSnapshot();
    }

    $noise = ob_get_clean();
    if ($noise !== '') {
        // เก็บลง log แทน ไม่ส่งให้ client
        error_log("get_scores.php stray output: " . substr($noise, 0, 500));
    }

    $json = json_encode($snap, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('json_encode_failed: ' . json_last_error_msg());
    }
    echo $json;
    // *** ห้ามปิดด้วย "" เพื่อลดโอกาสช่องว่างหลุดออก
    return;
} catch (Throwable $e) {
    ob_end_clean(); // ทิ้ง HTML ที่เผลอพ่น
    http_response_code(500);
    $payload = ['error' => true, 'message' => 'server_error'];
    if (!empty($cfg['app']['debug'])) {
        $payload['exception'] = [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    return;
}
