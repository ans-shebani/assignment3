<?php
include_once '../conn/conn.php';
//include_once 'event_user.php';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام حجز التذاكر</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        header {
            background-color: #1a237e;
            color: white;
            padding: 1rem;
            text-align: center;
        }
        nav {
            background-color: #283593;
            padding: 1rem;
        }
        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }
        nav ul li {
            margin: 0 1rem;
        }
        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        nav ul li a:hover {
            background-color: #3949ab;
        }
        .filters {
            background-color: white;
            padding: 1rem;
            margin: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            padding: 1rem;
        }
        .event-card {
            background-color: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .event-card h3 {
            color: #1a237e;
            margin-top: 0;
        }
        .price-tag {
            color: #4caf50;
            font-weight: bold;
        }
        .filter-group {
            margin-bottom: 1rem;
        }
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .filter-group select, .filter-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #388e3c;
        }
    </style>
</head>
<body>
    <header>
        <h1>نظام حجز التذاكر</h1>
    </header>

    <nav>
        <ul>
            <li><a href="main.php">الرئيسية</a></li>
            <li><a href="../admin/admin_dashboard.php">المسؤول</a></li>
            <li><a href="../user/event_user.php">الفعاليات</a></li>
            <li><a href="../user/ticket_user.php">التذاكر</a></li>
            <li><a href="#discount-section">الخصومات</a></li>
            <li><a href="../user/user_notifications.php">الاشعارات</a></li>
            <li><a href="../auth/login.php">تسجيل دخول</a></li>
            <li><a href="../auth/logout.php">تسجيل خروج</a></li>
        </ul>
    </nav>

    <div class="filters">
        <form action="filter_events.php" method="GET">
            <div class="filter-group">
                <label for="eventType">نوع الفعالية:</label>
                <select id="eventType" name="eventType">
                    <option value="">الكل</option>
                    <option value="ترفيهي">ترفيهي</option>
                    <option value="ثقافي">ثقافي</option>
                    <option value="رياضي">رياضي</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="dateFilter">التاريخ:</label>
                <input type="date" id="dateFilter" name="dateFilter">
            </div>
            <div class="filter-group">
                <label for="priceRange">السعر الأقصى:</label>
                <input type="range" id="priceRange" name="priceRange" min="0" max="500" step="50" value="500">
                <span>500 دينار</span>
            </div>
            <button type="submit">تطبيق الفلتر</button>
        </form>
    </div>

    <div class="events-grid">
        <!-- مثال على بطاقة فعالية -->
        <div class="event-card">
            <h3>اسم الفعالية</h3>
            <p>وصف الفعالية</p>
            <p>المكان: الرياض</p>
            <p>التاريخ: ٢٠٢٤/١١/٠٤</p>
            <p class="price-tag">السعر: ١٠٠ دينار</p>
            <form action="book_ticket.php" method="POST">
                <input type="hidden" name="eventId" value="1">
                <button type="submit">حجز تذكرة</button>
            </form>
        </div>
    </div>
</body>
</html>