<?php
// yrscores/classes/AdminPanel.php
declare(strict_types=1);

final class AdminPanel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::get();
        // แนะนำให้ตั้ง fetch mode เป็น ASSOC เป็นค่าเริ่มต้น
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /* ========== Admins ========== */
    public function listAdmins(): array
    {
        return $this->pdo->query("SELECT id, username, created_at FROM admins ORDER BY id DESC")->fetchAll();
    }
    public function createAdmin(string $username, string $password): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO admins(username, password_hash) VALUES(?, ?)");
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
    }
    public function updateAdmin(int $id, ?string $username, ?string $password): void
    {
        if ($username !== null) {
            $stmt = $this->pdo->prepare("UPDATE admins SET username=? WHERE id=?");
            $stmt->execute([$username, $id]);
        }
        if ($password !== null) {
            $stmt = $this->pdo->prepare("UPDATE admins SET password_hash=? WHERE id=?");
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
        }
    }
    public function deleteAdmin(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM admins WHERE id=?");
        $stmt->execute([$id]);
    }

    /* ========== Sports ========== */
    public function listSports(): array
    {
        return $this->pdo->query("SELECT * FROM sports ORDER BY id DESC")->fetchAll();
    }
    public function createSport(string $name): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO sports(name) VALUES(?)");
        $stmt->execute([$name]);
    }
    public function updateSport(int $id, string $name): void
    {
        $stmt = $this->pdo->prepare("UPDATE sports SET name=? WHERE id=?");
        $stmt->execute([$name, $id]);
    }
    public function deleteSport(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM sports WHERE id=?");
        $stmt->execute([$id]);
    }

    /* ========== Categories ========== */
    public function listCategories(): array
    {
        $sql = "SELECT c.*, s.name AS sport FROM categories c JOIN sports s ON s.id=c.sport_id ORDER BY c.id DESC";
        return $this->pdo->query($sql)->fetchAll();
    }
    public function createCategory(int $sportId, string $name): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO categories(sport_id, name) VALUES(?,?)");
        $stmt->execute([$sportId, $name]);
    }
    public function updateCategory(int $id, int $sportId, string $name): void
    {
        $stmt = $this->pdo->prepare("UPDATE categories SET sport_id=?, name=? WHERE id=?");
        $stmt->execute([$sportId, $name, $id]);
    }
    public function deleteCategory(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM categories WHERE id=?");
        $stmt->execute([$id]);
    }

    /* ========== Teams ========== */
    public function listTeams(): array
    {
        $sql = "SELECT t.*, s.name AS sport FROM teams t JOIN sports s ON s.id=t.sport_id ORDER BY t.id DESC";
        return $this->pdo->query($sql)->fetchAll();
    }
    public function createTeam(int $sportId, string $name, string $hex): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO teams(sport_id, name, color_hex) VALUES(?,?,?)");
        $stmt->execute([$sportId, $name, $hex]);
    }
    public function updateTeam(int $id, int $sportId, string $name, string $hex): void
    {
        $stmt = $this->pdo->prepare("UPDATE teams SET sport_id=?, name=?, color_hex=? WHERE id=?");
        $stmt->execute([$sportId, $name, $hex, $id]);
    }
    public function deleteTeam(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM teams WHERE id=?");
        $stmt->execute([$id]);
    }

    /* ========== Matches ========== */
    // เวอร์ชันแนะนำ: join เอาชื่อทีม/กีฬา/หมวด เพื่อพร้อมแสดงผล
    public function getMatchDetails(int $id): ?array
    {
        $sql = "
            SELECT
                m.*,
                t1.name AS team1,
                t2.name AS team2,
                c.name  AS category,
                s.name  AS sport
            FROM matches m
            LEFT JOIN teams t1      ON t1.id = m.team1_id
            LEFT JOIN teams t2      ON t2.id = m.team2_id
            LEFT JOIN categories c  ON c.id  = m.category_id
            LEFT JOIN sports s      ON s.id  = c.sport_id
            WHERE m.id = :id
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(); // ด้วย fetch mode ASSOC ที่ตั้งไว้
        return $row ?: null;
    }

    public function getDashboardStats(): array
    {
        // ใช้ status ถ้ามี; ถ้าไม่มี ให้อนุมานจากเวลา
        // เคส A: ตารางมีคอลัมน์ status (scheduled|live|finished)
        $hasStatus = false;
        try {
            $this->pdo->query("SELECT status FROM matches LIMIT 0");
            $hasStatus = true;
        } catch (\Throwable $e) {
            $hasStatus = false;
        }

        if ($hasStatus) {
            $sql = "
            SELECT
              (SELECT COUNT(*) FROM matches) AS total_all,
              (SELECT COUNT(*) FROM matches WHERE DATE(starts_at) = CURDATE()) AS total_today,
              (SELECT COUNT(*) FROM matches WHERE status = 'live') AS total_live,
              (SELECT COUNT(*) FROM matches WHERE status = 'finished') AS total_finished
        ";
            return $this->pdo->query($sql)->fetch();
        }

        // เคส B: ไม่มี status → อนุมาน:
        // live: NOW() >= starts_at AND (ends_at IS NULL OR NOW() < ends_at)
        // finished: ends_at IS NOT NULL AND NOW() >= ends_at
        $sql = "
        SELECT
          (SELECT COUNT(*) FROM matches) AS total_all,
          (SELECT COUNT(*) FROM matches WHERE DATE(starts_at) = CURDATE()) AS total_today,
          (SELECT COUNT(*) FROM matches
             WHERE starts_at IS NOT NULL
               AND NOW() >= starts_at
               AND (ends_at IS NULL OR NOW() < ends_at)
          ) AS total_live,
          (SELECT COUNT(*) FROM matches
             WHERE ends_at IS NOT NULL AND NOW() >= ends_at
          ) AS total_finished
    ";
        return $this->pdo->query($sql)->fetch();
    }

    public function listRecentMatches(int $limit = 10): array
    {
        $sql = "
        SELECT
            m.id, m.starts_at,
            m.score1, m.score2,
            t1.name AS team1, t2.name AS team2,
            c.name  AS category, s.name AS sport
        FROM matches m
        LEFT JOIN teams t1     ON t1.id = m.team1_id
        LEFT JOIN teams t2     ON t2.id = m.team2_id
        LEFT JOIN categories c ON c.id  = m.category_id
        LEFT JOIN sports s     ON s.id  = c.sport_id
        ORDER BY COALESCE(m.starts_at, '1970-01-01') DESC, m.id DESC
        LIMIT :lim
    ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listAllMatches(): array
    {
        // SQL query ที่ใช้ดึงข้อมูลทั้งหมดจากตาราง matches
        $stmt = $this->pdo->query("SELECT m.*, s.name AS sport, c.name AS category 
                               FROM matches m 
                               LEFT JOIN sports s ON m.sport_id = s.id
                               LEFT JOIN categories c ON m.category_id = c.id
                               ORDER BY m.starts_at DESC");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);  // คืนค่าผลลัพธ์ในรูปแบบ array
    }
}
