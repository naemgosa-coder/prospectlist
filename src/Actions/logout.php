<?php
session_destroy(); // ทำลาย Session ทั้งหมด
header("Location: index.php?page=login"); // เด้งกลับหน้า Login
exit;
?>