<?php
session_start();
require_once '../conn/conn.php';
require_once '../classes/Notification.php';

// تحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// الاتصال بقاعدة البيانات
$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

// جلب الإشعارات الخاصة بالمستخدم
$notifications = $notification->getAllNotificationsForUser($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إشعاراتي</title>
    <link rel="stylesheet" href="../public/assets/notifications_user.css">
</head>
<body class="notifications-body">
    <header class="notifications-header">
        <h1 class="notifications-title">الإشعارات</h1>
    </header>

    <nav class="notifications-nav">
        <ul class="notifications-menu">
            <li class="menu-item"><a href="../public/main.php" class="menu-link">الرئيسية</a></li>
            <li class="menu-item"><a href="../admin/admin_dashboard.php" class="menu-link">المسؤول</a></li>
            <li class="menu-item"><a href="event_user.php" class="menu-link">الفعاليات</a></li>
            <li class="menu-item"><a href="ticket_user.php" class="menu-link">التذاكر</a></li>
            <li class="menu-item"><a href="#discount-section" class="menu-link">الخصومات</a></li>
            <li class="menu-item"><a href="user_notifications.php" class="menu-link active">الإشعارات</a></li>
            <li class="menu-item"><a href="../auth/login.php" class="menu-link">تسجيل دخول</a></li>
            <li class="menu-item"><a href="../auth/logout.php" class="menu-link">تسجيل خروج</a></li>
        </ul>
    </nav>

    <main class="notifications-container">
        <ul class="notifications-list">
            <?php foreach ($notifications as $note): ?>
                <li class="notification-item">
                    <span class="notification-message"><?php echo htmlspecialchars($note['message']); ?></span>
                    <span class="notification-date"><?php echo htmlspecialchars($note['date']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>
</html>

