<?php
session_start();
include_once '../conn/conn.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    header("Location: ../auth/login.php");
    exit();
}

// التحقق من وجود معرف الحدث
if (!isset($_GET['eventID'])) {
    header("Location: ../user/event_user.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// جلب معلومات الحدث
$eventID = $_GET['eventID'];
$query = "SELECT * FROM Events WHERE eventID = ?";
$stmt = $db->prepare($query);
$stmt->execute([$eventID]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

// جلب معلومات المستخدم
$userID = $_SESSION['user_id'];
$query = "SELECT * FROM Users WHERE userID = ?";
$stmt = $db->prepare($query);
$stmt->execute([$userID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// معالجة الحجز
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ticketType = $_POST['ticketType'];
    $paymentMethod = $_POST['paymentMethod']; // إضافة متغير طريقة الدفع
    $price = ($ticketType == 'VIP') ? $event['vipTicketPrice'] : $event['regularTicketPrice'];
    //الخصم من غير استخدام كلاس الخصم التي سيتم تنفيدها مستقبلا
    // تطبيق الخصم حسب نوع المستخدم
    $discount = 0;
    switch($user['userType']) {
        case 'student':
            $discount = 0.20;
            break;
        case 'military':
            $discount = 0.30;
            break;
        case 'teacher':
            $discount = 0.15;
            break;
        case 'the_elderly':
            $discount = 0.25;
            break;
    }
    
    $finalPrice = $price * (1 - $discount);
    
    try {
        // إدخال معلومات الدفع
        $paymentQuery = "INSERT INTO Payments (amount, paymentMethod) VALUES (:amount, :paymentMethod)";
        $paymentStmt = $db->prepare($paymentQuery);
        $paymentStmt->execute([
            ':amount' => $finalPrice,
            ':paymentMethod' => $paymentMethod
        ]);
        $paymentID = $db->lastInsertId();
        
        // إضافة الحجز مع رقم الدفع
        $query = "INSERT INTO Tickets (price, userID, eventID, ticketType, paymentID, status) 
                  VALUES (:price, :userID, :eventID, :ticketType, :paymentID, 'Confirmed')";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':price' => $finalPrice,
            ':userID' => $userID,
            ':eventID' => $eventID,
            ':ticketType' => $ticketType,
            ':paymentID' => $paymentID
        ]);
        
        // تحديث عدد المقاعد
        $query = "UPDATE Events SET seatsAvailable = seatsAvailable - 1 WHERE eventID = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$eventID]);
        
        $success = "تم الحجز بنجاح!";
    } catch(PDOException $e) {
        $error = "حدث خطأ في عملية الحجز";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجز تذكرة</title>
    <link rel="stylesheet" href="../public/assets/style.css">
</head>
<body>
<nav>
<ul>
            <li><a href="../public/main.php">الرئيسية</a></li>
            <li><a href="../admin/admin_dashboard.php">المسؤول</a></li>
            <li><a href="../user/event_user.php">الفعاليات</a></li>
            <li><a href="../user/ticket_user.php">التذاكر</a></li>
            <li><a href="#discount-section">الخصومات</a></li>
            <li><a href="../auth/login.php">تسجيل دخول</a></li>
            <li><a href="..//auth/logout.php">تسجيل خروج</a></li>
        </ul>
    </nav>
    <div class="booking-container">
        <h2>حجز تذكرة</h2>
        <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        
        <div class="event-details">
            <h3>تفاصيل الحدث</h3>
            <p><strong>اسم الحدث:</strong> <?php echo htmlspecialchars($event['name']); ?></p>
            <p><strong>التاريخ:</strong> <?php echo htmlspecialchars($event['date']); ?></p>
            <p><strong>المكان:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
            <p><strong>المقاعد المتاحة:</strong> <?php echo htmlspecialchars($event['seatsAvailable']); ?></p>
        </div>
        
        <div class="user-details">
            <h3>معلومات المستخدم</h3>
            <p><strong>الاسم:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
            <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>نوع المستخدم:</strong> <?php echo htmlspecialchars($user['userType']); ?></p>
        </div>
        
        <?php if ($event['seatsAvailable'] > 0): ?>
            <form method="POST">
                <div class="form-group">
                    <label>نوع التذكرة</label>
                    <select name="ticketType" required>
                        <option value="Regular">عادية - <?php echo $event['regularTicketPrice']; ?> دينار</option>
                        <option value="VIP">VIP - <?php echo $event['vipTicketPrice']; ?> دينار</option>
                    </select>
                </div>
                
                <div class="payment-methods">
                    <h3>اختر طريقة الدفع</h3>
                    <div class="payment-method">
                        <input type="radio" id="LocalCard" name="paymentMethod" value="LocalCard" required>
                        <label for="LocalCard">بطاقة مصرفية</label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" id="Tadawul" name="paymentMethod" value="Tadawul">
                        <label for="Tadawul">تداول</label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" id="PayLi" name="paymentMethod" value="PayLi">
                        <label for="PayLi">ادفعلي</label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" id="Sadaad" name="paymentMethod" value="Sadaad">
                        <label for="Sadaad">سداد</label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" id="MobiCash" name="paymentMethod" value="MobiCash">
                        <label for="MobiCash">موبي كاش</label>
                    </div>
                </div>
                
                <div class="price-info">
                    <p><strong>ملاحظة:</strong> يتم تطبيق الخصومات التالية:</p>
                    <ul>
                        <li>طالب: 20%</li>
                        <li>عسكري: 30%</li>
                        <li>معلم: 15%</li>
                        <li>كبار السن: 25%</li>
                    </ul>
                </div>
                
                <button type="submit">تأكيد الحجز</button>
            </form>
        <?php else: ?>
            <p class="error">عذراً، لا توجد مقاعد متاحة لهذا الحدث.</p>
        <?php endif; ?>
    </div>
</body>
</html>