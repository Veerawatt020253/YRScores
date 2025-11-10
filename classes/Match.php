<?php
// yrscores/classes/Match.php
declare(strict_types=1);

final class MatchService {
  private PDO $pdo;
  private ?string $startsCol = null; // 'start_time' | 'starts_at' | 'created_at'

  public function __construct() { $this->pdo = Database::get(); }

  /* ===== helper: resolve starts column ===== */
  private function startsColumn(): string {
    if ($this->startsCol !== null) return $this->startsCol;
    $cols = $this->pdo->query("SHOW COLUMNS FROM matches")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('start_time', $cols, true)) { $this->startsCol = 'start_time'; return 'start_time'; }
    if (in_array('starts_at',  $cols, true)) { $this->startsCol = 'starts_at';  return 'starts_at'; }
    $this->startsCol = 'created_at';
    return 'created_at';
  }

  /* ===== snapshots ===== */
  public function getPublicSnapshot(): array {
    $live     = $this->fetchMatches('live');
    $finished = $this->fetchMatches('finished', 10);
    $top      = $this->fetchTopViewed(5);
    $total    = $this->getTotalViews();
    return ['live'=>$live, 'finished'=>$finished, 'top'=>$top, 'total_views'=>$total];
  }

  // เผื่อโค้ดเดิมเรียกชื่อ getSnapshot()
  public function getSnapshot(): array { return $this->getPublicSnapshot(); }

  /* ===== queries ===== */
  public function fetchMatches(string $status, int $limit = 50): array {
    $col = $this->startsColumn();
    $sql = "
      SELECT m.id, m.sport_id, m.category_id, m.team1_id, m.team2_id,
             m.score1, m.score2, m.status, m.{$col} AS starts_at, m.view_count,
             t1.name AS team1, t1.color_hex AS color1,
             t2.name AS team2, t2.color_hex AS color2,
             s.name AS sport, c.name AS category
      FROM matches m
      JOIN teams t1 ON t1.id = m.team1_id
      JOIN teams t2 ON t2.id = m.team2_id
      JOIN sports s ON s.id = m.sport_id
      JOIN categories c ON c.id = m.category_id
      WHERE m.status = ?
      ORDER BY (m.status='live') DESC, m.{$col} DESC
      LIMIT ?
    ";
    $st = $this->pdo->prepare($sql);
    $st->bindValue(1, $status, PDO::PARAM_STR);
    $st->bindValue(2, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public function fetchTopViewed(int $limit = 5): array {
    $col = $this->startsColumn();
    $sql = "
      SELECT m.id, t1.name AS team1, t2.name AS team2, m.view_count
      FROM matches m
      JOIN teams t1 ON t1.id = m.team1_id
      JOIN teams t2 ON t2.id = m.team2_id
      ORDER BY m.view_count DESC, m.{$col} DESC
      LIMIT ?
    ";
    $st = $this->pdo->prepare($sql);
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public function getTotalViews(): int {
    return (int)$this->pdo->query("SELECT COALESCE(SUM(view_count),0) v FROM matches")->fetch()['v'];
  }

  public function incrementView(int $matchId): void {
    $st = $this->pdo->prepare("UPDATE matches SET view_count = view_count + 1 WHERE id = ?");
    $st->execute([$matchId]);
  }

  public function updateScore(int $matchId, int $delta1, int $delta2): void {
    $st = $this->pdo->prepare("UPDATE matches
      SET score1 = GREATEST(score1 + ?, 0), score2 = GREATEST(score2 + ?, 0)
      WHERE id = ? AND status = 'live'");
    $st->execute([$delta1, $delta2, $matchId]);
  }

  public function endMatch(int $matchId): void {
    $st = $this->pdo->prepare("UPDATE matches SET status='finished' WHERE id=?");
    $st->execute([$matchId]);
  }

  public function createMatch(
    int $sportId, int $categoryId, int $team1Id, int $team2Id,
    string $startsAt, string $status = 'live'
  ): int {
    if ($team1Id === $team2Id) throw new InvalidArgumentException('ทีมต้องไม่ซ้ำกัน');
    if (!in_array($status, ['live','finished'], true)) throw new InvalidArgumentException('สถานะไม่ถูกต้อง');

    // ตรวจความสัมพันธ์
    $chk = $this->pdo->prepare("
      SELECT
        (SELECT COUNT(*) FROM sports WHERE id=?) ok_sport,
        (SELECT COUNT(*) FROM categories WHERE id=? AND sport_id=?) ok_cat,
        (SELECT COUNT(*) FROM teams WHERE id=? AND sport_id=?) ok_t1,
        (SELECT COUNT(*) FROM teams WHERE id=? AND sport_id=?) ok_t2
    ");
    $chk->execute([$sportId, $categoryId, $sportId, $team1Id, $sportId, $team2Id, $sportId]);
    $row = $chk->fetch();
    if (!$row || !$row['ok_sport'] || !$row['ok_cat'] || !$row['ok_t1'] || !$row['ok_t2']) {
      throw new RuntimeException('ข้อมูลอ้างอิงไม่ถูกต้องกับกีฬา');
    }

    $timeCol = $this->startsColumn(); // 'start_time' | 'starts_at' | 'created_at'
    if ($timeCol === 'created_at') {
      $sql = "INSERT INTO matches (sport_id, category_id, team1_id, team2_id, score1, score2, status)
              VALUES (?,?,?,?,0,0,?)";
      $st = $this->pdo->prepare($sql);
      $st->execute([$sportId, $categoryId, $team1Id, $team2Id, $status]);
    } else {
      $sql = "INSERT INTO matches (sport_id, category_id, team1_id, team2_id, score1, score2, status, {$timeCol})
              VALUES (?,?,?,?,0,0,?,?)";
      $st = $this->pdo->prepare($sql);
      $st->execute([$sportId, $categoryId, $team1Id, $team2Id, $status, $startsAt]);
    }
    return (int)$this->pdo->lastInsertId();
  }
}
