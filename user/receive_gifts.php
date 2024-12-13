<?php
// ملف receive_gifts.php
session_start();

require_once '../classes/Gift.php';

// الاتصال بقاعدة البيانات
$db = new Database();
$conn = $db->getConnection();
$gift = new Gift($conn);

$userID = $_SESSION['user_id'] ?? 0; // التأكد من تسجيل دخول المستخدم

// جلب الهدايا
$giftsResult = $gift->getGiftsNotReceived($userID);
$receivedGifts = ($giftsResult['status']) ? $giftsResult['gifts'] : []; // استدعاء دالة لجلب الهدايا غير المستلمة

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $giftID = $_POST['gift_id']; 
    
    // استلام الهدية
    $updateStatus = $gift->receiveGift($giftID, $userID);
    
    if ($updateStatus) {
        // رسالة نجاح
        $_SESSION['success_message'] = "تم استلام الهدية بنجاح وإضافتها إلى تذاكرك";
        header("Location: ticket_user.php");
        exit();
    } else {
        $error_message = "حدث خطأ أثناء استلام الهدية.";
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الهدايا المستلمة</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .gift-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fff;
        }
        .gift-info {
            margin-bottom: 10px;
        }
        .gift-sender {
            color: #666;
            font-size: 0.9em;
        }
        .event-name {
            font-weight: bold;
            color: #333;
        }
        .event-date {
            color: #777;
        }
        .no-gifts {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>الهدايا المستلمة</h1>
        
        <?php if (!empty($receivedGifts)): ?>
            <?php foreach ($receivedGifts as $gift): ?>
                <div class="gift-card">
                    <div class="gift-info">
                        <div class="event-name">
                            <?php echo htmlspecialchars($gift['eventName']); ?>
                        </div>
                        <div class="event-date">
                            تاريخ الحدث: <?php echo date('Y-m-d', strtotime($gift['eventDate'])); ?>
                        </div>
                        <div class="gift-sender">
                            مرسلة من: <?php echo htmlspecialchars($gift['senderName']); ?>
                        </div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="gift_id" value="<?php echo $gift['giftTicketID']; ?>">
                        <button class="btn" type="submit">استلام الهدية والانتقال للتذاكر</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-gifts">
                لا توجد هدايا مستلمة حالياً
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
