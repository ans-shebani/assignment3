<?php
session_start();
require_once '../conn/conn.php';
require_once '../classes/Notification.php';

// تحقق من تسجيل الدخول كمسؤول
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: main.php");
    exit();
}

// الاتصال بقاعدة البيانات
$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

// إضافة إشعار جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_notification'])) {
    $message = $_POST['message'];
    $userID = !empty($_POST['user_id']) ? $_POST['user_id'] : null; // تحديد المستخدم أو إرسال للجميع
    $notification->sendNotification($message, $userID);
}

// تحديث إشعار
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notification'])) {
    $notificationID = $_POST['notification_id'];
    $message = $_POST['message'];
    $notification->updateNotification($notificationID, $message);
}

// حذف إشعار
if (isset($_POST['delete_notification'])) {
    $notificationID = $_POST['notification_id'];
    $notification->deleteNotification($notificationID);
}

// جلب جميع الإشعارات
$notifications = $notification->getAllNotifications();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الإشعارات</title>
    <link rel="stylesheet" href="../public/assets/notifications_admin.css">
</head>
<body class="notifications-body">
    <header class="notifications-header">
        <h1 class="notifications-title">إدارة الإشعارات</h1>
    </header>

    <nav class="notifications-nav">
        <ul class="notifications-menu">
            <li class="menu-item"><a href="admin_dashboard.php" class="menu-link">لوحة التحكم</a></li>
            <li class="menu-item"><a href="manager_event.php" class="menu-link">إدارة الفعاليات</a></li>
            <li class="menu-item"><a href="#users" class="menu-link">إدارة المستخدمين</a></li>
            <li class="menu-item"><a href="admin_notifications.php" class="menu-link active">الإشعارات</a></li>
            <li class="menu-item"><a href="discounts.php" class="menu-link">الخصومات</a></li>
            <li class="menu-item"><a href="../auth/logout.php" class="menu-link">تسجيل خروج</a></li>
           
        </ul>
    </nav>

    <main class="notifications-container">
        <!-- نموذج لإضافة أو تعديل إشعار -->
        <form method="POST" class="notification-form">
            <textarea name="message" class="form-textarea" placeholder="نص الإشعار" required></textarea>
            <label for="user" class="form-label">إرسال إلى:</label>
            <select name="user_id" class="form-select">
                <option value="">جميع المستخدمين</option>
                <?php
                // جلب قائمة المستخدمين
                $stmt = $db->prepare("SELECT userID, name FROM Users");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($users as $user) {
                    echo "<option value='{$user['userID']}'>{$user['name']}</option>";
                }
                ?>
            </select>
            <button type="submit" name="add_notification" class="form-button">إضافة إشعار</button>
        </form>

        <h2 class="notifications-section-title">قائمة الإشعارات</h2>
        <ul class="notifications-list">
            <?php foreach ($notifications as $note): ?>
                <li class="notification-item">
                    <?php
                    // إذا كان الإشعار موجهًا لمستخدم معين، أضف اسمه
                    $user = "جميع المستخدمين";
                    if ($note['userID']) {
                        $stmt = $db->prepare("SELECT name FROM Users WHERE userID = :userID");
                        $stmt->bindParam(':userID', $note['userID'], PDO::PARAM_INT);
                        $stmt->execute();
                        $user = $stmt->fetchColumn();
                    }
                    ?>
                    <form method="POST" class="notification-update-form">
                        <input type="hidden" name="notification_id" value="<?php echo $note['notificationID']; ?>">
                        <textarea name="message" class="form-textarea"><?php echo htmlspecialchars($note['message']); ?></textarea>
                        <span class="notification-user"><strong>إلى:</strong> <?php echo htmlspecialchars($user); ?></span>
                        <button type="submit" name="update_notification" class="form-button">تحديث</button>
                        <button type="submit" name="delete_notification" class="form-button delete">حذف</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>
</html>
