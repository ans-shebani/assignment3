<?php
session_start();

require_once '../conn/conn.php'; // ملف الاتصال بقاعدة البيانات
require_once 'admin_event_user.php'; // ملف الفئات اللازمة

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db, $_SESSION['user_id']);

// التحقق من كون المستخدم إداري
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: main.php");
    exit();
}

// استرجاع معلومات المستخدمين والفعاليات
$users = $admin->getAllUsers();
$events = $admin->getAllEvents();

// حساب عدد المستخدمين وعدد الإداريين
$totalUsers = count($users);
$totalAdmins = count(array_filter($users, function($user) {
    return isset($user['userType']) && $user['userType'] === 'admin'; // تحقق من نوع المستخدم
}));
$totalEvents = count($events);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المسؤول</title>
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

        .admin-nav h1 {
            margin-bottom: 1rem;
            text-align: center;
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
        .admin-panel {
            max-width: 900px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        /* الإحصائيات */
        .stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .stats div {
            background-color: #007BFF;
            color: white;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            flex: 1;
            margin: 0 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            padding: 12px;
            text-align: right;
            border: 1px solid #dddddd;
        }

        th {
            background-color: #007BFF;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        /* التنبيهات */
        .alert {
            padding: 15px;
            margin: 20px;
            border-radius: 4px;
            text-align: right;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        }

        .admin-btn:hover {
            background-color: #34495e;
        }

        /* التنسيق المتجاوب */
        @media (max-width: 768px) {
            .admin-nav ul {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-panel">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo htmlspecialchars($_SESSION['error'] ?? '');
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo htmlspecialchars($_SESSION['message'] ?? ''); 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <nav class="admin-nav">
            <h1>لوحة تحكم المسؤول</h1>
            <ul>
                <li><a href="admin_dashboard.php">لوحة التحكم</a></li>
                <li><a href="manager_event.php">إدارة الفعاليات</a></li>
                <li><a href="#users">إدارة المستخدمين</a></li>
                <li><a href="admin_notifications.php">الإشعارات</a></li>
                <li><a href="discounts.php">الخصومات</a></li>
                <li><a href="../auth/logout.php">تسجيل خروج</a></li>
            </ul>
        </nav>

        <div class="stats">
            <div>إجمالي المستخدمين: <?php echo $totalUsers; ?></div>
            <div>إجمالي الفعاليات: <?php echo $totalEvents; ?></div>
        </div>

        <h2>قائمة المستخدمين</h2>
        <table>
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الهاتف</th>
                    <th>العنوان</th>
                    <th>نوع المستخدم</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($user['phone'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($user['address'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($user['userType'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>قائمة الفعاليات</h2>
        <table>
            <thead>
                <tr>
                    <th>اسم الفعالية</th>
                    <th>التاريخ</th>
                    <th>المكان</th>
                    <th>النوع</th>
                    <th>عدد المقاعد المتاحة</th>
                    <th>سعر التذكرة العادية</th>
                    <th>سعر تذكرة VIP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($event['date'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($event['location'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($event['type'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($event['seatsAvailable'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($event['regularTicketPrice'] ?? ''); ?> ر.س</td>
                        <td><?php echo htmlspecialchars($event['vipTicketPrice'] ?? ''); ?> ر.س</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
