<?php
session_start();

require_once '../conn/conn.php';
require_once '../admin/admin_event_user.php';




// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db, $_SESSION['user_id']);

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: main.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المسؤول</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="admin-panel">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo htmlspecialchars($_SESSION['message']); 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <nav class="admin-nav">
            <h1>لوحة تحكم المسؤول</h1>
            <ul>
                <li><a href="admin_dashboard.php">لوحة التحكم </a></li>
                <li><a href="manager_event.php">إدارة الفعاليات</a></li>
                <li><a href="#users">إدارة المستخدمين</a></li>
                <li><a href="Notification.php">الإشعارات</a></li>
                <li><a href="discounts.php">الخصومات</a></li>
                <li><a href="logout.php">تسجيل خروج</a></li>
            </ul>
        </nav>

        <main>
            <!-- قسم الإشعارات -->
            <section id="notifications" class="admin-section">
                <h2>إرسال إشعارات</h2>
                <form method="POST" class="admin-form">
                    <input type="hidden" name="send_notification">
                    <div class="form-group">
                        <label>اختر المستخدم:</label>
                        <select name="userID" required>
                            <?php if (isset($users) && is_array($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo htmlspecialchars($user['userID']); ?>">
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>الرسالة:</label>
                        <textarea name="message" required></textarea>
                    </div>
                    <button type="submit" class="admin-btn">إرسال الإشعار</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>