<?php
// yrscores/classes/Auth.php
declare(strict_types=1);

final class Auth {
  public static function startSession(): void {
    $cfg = require __DIR__ . '/../config.php';
    session_name($cfg['session']['name']);
    session_set_cookie_params([
      'secure' => $cfg['session']['secure'],
      'httponly' => $cfg['session']['http_only'],
      'samesite' => $cfg['session']['same_site'],
      'path' => '/'
    ]);
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  }

  public static function login(string $username, string $password): bool {
    $pdo = Database::get();
    $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if (!$row) return false;
    if (!password_verify($password, $row['password_hash'])) return false;

    $_SESSION['admin_id'] = (int)$row['id'];
    $_SESSION['admin_name'] = $username;
    return true;
  }

  public static function check(): bool {
    return isset($_SESSION['admin_id']);
  }

  public static function requireLogin(): void {
    if (!self::check()) {
      header('Location: /yrscores/admin/login.php');
      exit;
    }
  }

  public static function logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $params['path']);
    }
    session_destroy();
  }
}
