<?php
session_start();
require_once '../conn/conn.php';
require_once 'admin_event_user.php';

$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db);

// تحقق من وجود event_id
if (isset($_POST['event_id'])) {
    $eventId = filter_var($_POST['event_id'], FILTER_VALIDATE_INT);
    if ($eventId === false) {
        die("معرف الفعالية غير صالح");
    }

    // استرجاع بيانات الفعالية
    $eventData = $admin->getEventById($eventId);
    if (!$eventData) {
        die("الفعالية غير موجودة");
    }
} else {
    die("لم يتم تقديم معرف الفعالية");
}

// تحديث الفعالية
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_event'])) {
    $updatedData = [
        'name' => $_POST['name'],
        'date' => $_POST['date'],
        'location' => $_POST['location'],
        'type' => $_POST['type'],
        'seatsAvailable' => $_POST['seats'],
        'description' => $_POST['description'],
        'organizer' => $_POST['organizer'],
        'regularTicketPrice' => $_POST['regularPrice'],
        'vipTicketPrice' => $_POST['vipPrice']
    ];

    if ($admin->updateEvent($eventId, $updatedData)) {
        $_SESSION['message'] = "تم تحديث الفعالية بنجاح";
        $_SESSION['message_type'] = 'success';
        header("Location: admin_dashboard.php"); // يعيدك إلى صفحة الإدارة
        exit();
    } else {
        $_SESSION['message'] = "حدث خطأ أثناء تحديث الفعالية";
        $_SESSION['message_type'] = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تحديث الفعالية</title>
    <style>
        /* التنسيق الأساسي */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
        }

        /* شريط التنقل */
        .admin-nav {
            background-color: #2c3e50;
            color: white;
            padding: 1rem;
        }

        .admin-nav h2 {
            text-align: center;
            margin-bottom: 1rem;
        }

        .admin-nav ul {
            list-style: none;
            display: flex;
            justify-content: center;
            gap: 2rem;
        }

        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .admin-nav a:hover {
            background-color: #34495e;
        }

        /* القسم الرئيسي */
        .admin-section {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            margin: 2rem auto;
            max-width: 600px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .admin-section h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        /* النماذج */
        .admin-form {
            display: grid;
            gap: 1rem;
        }

        .form-group {
            display: grid;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: bold;
            color: #2c3e50;
        }

        .form-group input,
        .form-group textarea {
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        /* الأزرار */
        .admin-btn {
            background-color: #2c3e50;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
            margin-top: 1rem;
        }

        .admin-btn:hover {
            background-color: #34495e;
        }

        /* التنسيق المتجاوب */
        @media (max-width: 768px) {
            .admin-nav ul {
                flex-direction: column;
                text-align: center;
            }

            .admin-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
  
    <nav class="admin-nav">
        <h2>تحديث الفعالية</h2>
        <ul>
                <li><a href="admin_dashboard.php">لوحة التحكم </a></li>
                <li><a href="manager_event.php">إدارة الفعاليات</a></li>
                <li><a href="manager_user.php">إدارة المستخدمين</a></li>
                <li><a href="Notification.php">الإشعارات</a></li>
                <li><a href="discounts.php">الخصومات</a></li>
                <li><a href="../auth/logout.php">تسجيل خروج</a></li>
            </ul>
        </nav>
    
    <div class="admin-section">
        <form method="POST" class="admin-form">
            <input type="hidden" name="update_event" value="1">
            <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">

            <div class="form-group">
                <label>اسم الفعالية:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($eventData['name']); ?>" required>
            </div>

            <div class="form-group">
                <label>التاريخ:</label>
                <input type="datetime-local" name="date" value="<?php echo date('Y-m-d\TH:i', strtotime($eventData['date'])); ?>" required>
            </div>

            <div class="form-group">
                <label>الموقع:</label>
                <input type="text" name="location" value="<?php echo htmlspecialchars($eventData['location']); ?>" required>
            </div>

            <div class="form-group">
                <label>النوع:</label>
                <input type="text" name="type" value="<?php echo htmlspecialchars($eventData['type']); ?>" required>
            </div>

            <div class="form-group">
                <label>عدد المقاعد المتاحة:</label>
                <input type="number" name="seats" value="<?php echo htmlspecialchars($eventData['seatsAvailable']); ?>" required>
            </div>

            <div class="form-group">
                <label>الوصف:</label>
                <textarea name="description" required><?php echo htmlspecialchars($eventData['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label>المنظم:</label>
                <input type="text" name="organizer" value="<?php echo htmlspecialchars($eventData['organizer']); ?>" required>
            </div>

            <div class="form-group">
                <label>سعر التذكرة العادية:</label>
                <input type="number" name="regularPrice" value="<?php echo htmlspecialchars($eventData['regularTicketPrice']); ?>" required>
            </div>

            <div class="form-group">
                <label>سعر تذكرة VIP:</label>
                <input type="number" name="vipPrice" value="<?php echo htmlspecialchars($eventData['vipTicketPrice']); ?>" required>
            </div>

            <button type="submit" class="admin-btn">تحديث</button>
        </form>
    </div>
</body>
</html>
