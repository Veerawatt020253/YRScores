<?php
// FILE: yrscores/admin/api/update_match.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Database.php';
Auth::startSession();
Auth::requireLogin();

try {
    // CSRF
    if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid_csrf'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid_id'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $score1    = max(0, (int)($_POST['score1'] ?? 0));
    $score2    = max(0, (int)($_POST['score2'] ?? 0));
    $status    = in_array($_POST['status'] ?? '', ['scheduled', 'live', 'finished'], true) ? $_POST['status'] : 'scheduled';
    $starts_at = trim((string)($_POST['starts_at'] ?? ''));
    $view      = max(0, (int)($_POST['view_count'] ?? 0));

    $pdo = Database::get();
    $sql = "UPDATE matches
            SET score1=:s1, score2=:s2, status=:st,
                starts_at = NULLIF(:starts, ''),
                view_count=:v,
                updated_at = NOW()
          WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':s1' => $score1,
        ':s2' => $score2,
        ':st' => $status,
        ':starts' => $starts_at,
        ':v' => $view,
        ':id' => $id
    ]);

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'server_error',
        'detail' => getenv('APP_DEBUG') ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
