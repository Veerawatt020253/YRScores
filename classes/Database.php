<?php
// yrscores/classes/Database.php
declare(strict_types=1);

final class Database {
  private static ?PDO $pdo = null;

  public static function get(): PDO {
    if (self::$pdo === null) {
      $cfg = require __DIR__ . '/../config.php';
      $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['db']['host'], $cfg['db']['port'], $cfg['db']['name'], $cfg['db']['charset']);
      $opt = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ];
      self::$pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], $opt);
    }
    return self::$pdo;
  }

  private function __construct() {}
  private function __clone() {}
}
