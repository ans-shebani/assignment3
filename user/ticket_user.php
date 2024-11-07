<?php
session_start();
include_once '../conn/conn.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$userID = $_SESSION['user_id'];

// معالجة طلب إلغاء التذكرة
if (isset($_POST['cancel_ticket']) && isset($_POST['ticketID']) && isset($_POST['eventID'])) {
    try {
        // بدء المعاملة
        $db->beginTransaction();
        
        // تحديث حالة التذكرة
        $updateTicketQuery = "UPDATE Tickets SET status = 'Cancelled' 
                            WHERE ticketID = ? AND userID = ?";
        $stmt = $db->prepare($updateTicketQuery);
        $stmt->execute([$_POST['ticketID'], $userID]);
        
        // زيادة عدد المقاعد المتاحة
        $updateSeatsQuery = "UPDATE Events SET seatsAvailable = seatsAvailable + 1 
                            WHERE eventID = ?";
        $stmt = $db->prepare($updateSeatsQuery);
        $stmt->execute([$_POST['eventID']]);
        
        // تأكيد المعاملة
        $db->commit();
        
        // إعادة توجيه بعد نجاح العملية
        header("Location: ticket_user.php?message=success");
        exit();
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة حدوث خطأ
        $db->rollBack();
        header("Location: ticket_user.php?message=error");
        exit();
    }
}

// جلب جميع تذاكر المستخدم مع معلومات الأحداث والمدفوعات
$query = "SELECT t.*, e.name as eventName, e.date as eventDate, e.location, 
                 p.paymentMethod, p.amount, u.name as userName, u.email 
          FROM Tickets t 
          JOIN Events e ON t.eventID = e.eventID 
          JOIN Payments p ON t.paymentID = p.paymentID 
          JOIN Users u ON t.userID = u.userID 
          WHERE t.userID = ? 
          ORDER BY e.date DESC";

$stmt = $db->prepare($query);
$stmt->execute([$userID]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تذاكري</title>
    <link rel="stylesheet" href="../public/assets/style_ticket.css">
</head>
<body>
    <nav>
    <ul>
            <li><a href="../public/main.php">الرئيسية</a></li>
            <li><a href="../admin/admin_dashboard.php">المسؤول</a></li>
            <li><a href="event_user.php">الفعاليات</a></li>
            <li><a href="ticket_user.php">التذاكر</a></li>
            <li><a href="#discount-section">الخصومات</a></li>
            <li><a href="../auth/login.php">تسجيل دخول</a></li>
            <li><a href="../auth/logout.php">تسجيل خروج</a></li>
        </ul>
    </nav>

    <h1>تذاكري</h1>

    <?php if (isset($_GET['message'])): ?>
        <?php if ($_GET['message'] === 'success'): ?>
            <div class="alert success">تم إلغاء التذكرة بنجاح</div>
        <?php elseif ($_GET['message'] === 'error'): ?>
            <div class="alert error">حدث خطأ أثناء إلغاء التذكرة</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="tickets-container">
        <?php if (count($tickets) > 0): ?>
            <?php foreach ($tickets as $ticket): ?>
                <div class="ticket-card">
                    <div class="ticket-header">
                        <h3 class="ticket-title"><?php echo htmlspecialchars($ticket['eventName']); ?></h3>
                        <span class="ticket-status status-<?php echo strtolower($ticket['status']); ?>">
                            <?php 
                            $statusArabic = [
                                'Confirmed' => 'مؤكد',
                                'Pending' => 'قيد الانتظار',
                                'Cancelled' => 'ملغي'
                            ];
                            echo $statusArabic[$ticket['status']];
                            ?>
                        </span>
                    </div>
                    
                    <div class="ticket-details">
                        <p class="ticket-detail">
                            <strong>التاريخ:</strong> 
                            <?php echo date('Y-m-d H:i', strtotime($ticket['eventDate'])); ?>
                        </p>
                        <p class="ticket-detail">
                            <strong>المكان:</strong> 
                            <?php echo htmlspecialchars($ticket['location']); ?>
                        </p>
                        <p class="ticket-detail">
                            <strong>نوع التذكرة:</strong>
                            <span class="ticket-type type-<?php echo strtolower($ticket['ticketType']); ?>">
                                <?php echo $ticket['ticketType'] == 'Regular' ? 'عادية' : 'VIP'; ?>
                            </span>
                        </p>
                        <p class="ticket-detail">
                            <strong>طريقة الدفع:</strong>
                            <?php echo htmlspecialchars($ticket['paymentMethod']); ?>
                        </p>
                        <p class="ticket-price">
                            <strong>السعر:</strong>
                            <?php echo number_format($ticket['amount'], 2); ?> دينار
                        </p>
                        
                        <?php if ($ticket['status'] !== 'Cancelled'): ?>
                            <div class="ticket-actions">
                                <form method="POST" onsubmit="return confirm('هل أنت متأكد من رغبتك في إلغاء هذه التذكرة؟');">
                                    <input type="hidden" name="ticketID" value="<?php echo $ticket['ticketID']; ?>">
                                    <input type="hidden" name="eventID" value="<?php echo $ticket['eventID']; ?>">
                                    <button type="submit" name="cancel_ticket" class="cancel-btn">إلغاء التذكرة</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-tickets">
                <p>لا توجد تذاكر محجوزة حالياً</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>