<?php
session_start();

require_once '../conn/conn.php';
require_once 'admin_event_user.php';

// إضافة CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db, $_SESSION['user_id']);

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location:../public/ main.php");
    exit();
}

// دالة للتحقق من صحة المدخلات
function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['add_event'])) {
            $eventData = [
                'name' => validateInput($_POST['name']),
                'date' => validateInput($_POST['date']),
                'location' => validateInput($_POST['location']),
                'type' => validateInput($_POST['type']),
                'seatsAvailable' => filter_var($_POST['seats'], FILTER_VALIDATE_INT),
                'description' => validateInput($_POST['description']),
                'organizer' => validateInput($_POST['organizer']),
                'regularTicketPrice' => filter_var($_POST['regularPrice'], FILTER_VALIDATE_FLOAT),
                'vipTicketPrice' => filter_var($_POST['vipPrice'], FILTER_VALIDATE_FLOAT)
            ];
            
            if ($eventData['seatsAvailable'] === false || $eventData['regularTicketPrice'] === false || 
                $eventData['vipTicketPrice'] === false) {
                throw new Exception('قيم غير صالحة للمقاعد أو الأسعار');
            }
            
            $admin->addEvent($eventData);
        }

        // معالجة حذف الفعالية
        if (isset($_POST['delete_event'])) {
            $eventId = filter_var($_POST['event_id'], FILTER_VALIDATE_INT);
            if ($eventId === false) {
                throw new Exception('معرف الفعالية غير صالح');
            }
            
            // Check if there are any related records in the Tickets table
            $checkTicketsQuery = "SELECT COUNT(*) FROM Tickets WHERE eventID = :eventID";
            $stmt = $db->prepare($checkTicketsQuery);
            $stmt->bindParam(':eventID', $eventId);
            $stmt->execute();
            $ticketCount = $stmt->fetchColumn();
            
            if ($ticketCount > 0) {
                $_SESSION['message'] = "لا يمكن حذف الفعالية لوجود تذاكر مرتبطة بها";
                $_SESSION['message_type'] = 'error';
            } else {
                if ($admin->deleteEvent($eventId)) {
                    $_SESSION['message'] = "تم حذف الفعالية بنجاح";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "حدث خطأ أثناء حذف الفعالية";
                    $_SESSION['message_type'] = 'error';
                }
            }
            
            // Redirect to refresh the page
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// استرجاع قائمة الفعاليات
$events = $admin->getAllEvents(); // تأكد من أن لديك هذه الدالة في كلاس Admin
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المسؤول</title>
    <link rel="stylesheet" href="../public/assets/admin_style.css">
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
            <h1>  ادارة الفعاليات</h1>
            <ul>
                <li><a href="admin_dashboard.php">لوحة التحكم </a></li>
                <li><a href="manager_event.php">إدارة الفعاليات</a></li>
                <li><a href="manager_user.php">إدارة المستخدمين</a></li>
                <li><a href="Notification.php">الإشعارات</a></li>
                <li><a href="discounts.php">الخصومات</a></li>
                <li><a href="../auth/logout.php">تسجيل خروج</a></li>
            </ul>
        </nav>

        <main>
            <!-- قسم إدارة الفعاليات -->
            <section id="events" class="admin-section">
                <h2>إضافة فعالية جديدة</h2>
                <form method="POST" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="name">اسم الفعالية:</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="date">تاريخ الفعالية:</label>
                        <input type="datetime-local" id="date" name="date" required>
                    </div>

                    <div class="form-group">
                        <label for="location">موقع الفعالية:</label>
                        <input type="text" id="location" name="location" required>
                    </div>

                    <div class="form-group">
    <label for="type">نوع الفعالية:</label>
    <select id="type" name="type">
        <option value="">اختر نوع الفعالية</option>
        <option value="مؤتمر">مؤتمر</option>
        <option value="معرض">معرض</option>
        <option value="ورشة عمل">ورشة عمل</option>
        <option value="حفل">حفل</option>
    </select>
    <label for="customType">أو أدخل نوعًا جديدًا:</label>
    <input type="text" id="customType" name="customType" placeholder="نوع آخر">
</div>


                    <div class="form-group">
                        <label for="seats">عدد المقاعد المتاحة:</label>
                        <input type="number" id="seats" name="seats" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="description">وصف الفعالية:</label>
                        <textarea id="description" name="description" rows="4" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="organizer">المنظم:</label>
                        <input type="text" id="organizer" name="organizer" required>
                    </div>

                    <div class="form-group">
                        <label for="regularPrice">سعر التذكرة العادية:</label>
                        <input type="number" id="regularPrice" name="regularPrice" min="0" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="vipPrice">سعر تذكرة VIP:</label>
                        <input type="number" id="vipPrice" name="vipPrice" min="0" step="0.01" required>
                    </div>

                    <button type="submit" name="add_event" class="admin-btn">إضافة الفعالية</button>
                </form>

                <h2>قائمة الفعاليات</h2>
                <div class="table-responsive">
                    <table class="events-table">
                        <thead>
                            <tr>
                                <th>اسم الفعالية</th>
                                <th>التاريخ</th>
                                <th>الموقع</th>
                                <th>النوع</th>
                                <th>المقاعد المتاحة</th>
                                <th>الوصف</th>
                                <th>المنظم</th>
                                <th>سعر التذكرة العادية</th>
                                <th>سعر تذكرة VIP</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($events) && is_array($events)): ?>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($event['date'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($event['location'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($event['type'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($event['seatsAvailable'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($event['description'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($event['organizer'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($event['regularTicketPrice'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($event['vipTicketPrice'] ?? ''); ?></td>
                                        <td>
                                            <form method="POST" action="update_event.php" style="display:inline;">
                                                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['eventID']); ?>">
                                                <button type="submit" class="admin-btn update-btn">تحديث</button>
                                            </form>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['eventID']); ?>">
                                                <button type="submit" name="delete_event" class="admin-btn delete-btn">حذف</button>
                                            </form>
                                        </td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </main>
    </div>
</body>
</html>