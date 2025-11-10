<?php
declare(strict_types=1);
require_once __DIR__ . '/../classes/Auth.php';

// เริ่มต้น session
Auth::startSession();

// ทำลาย session เพื่อออกจากระบบ
session_destroy();

// ส่งผู้ใช้กลับไปยังหน้า login
header('Location: /yrscores/admin/login.php');
exit;
?>
