<?php

// تضمين ملف الاتصال بقاعدة البيانات
include "../classes/favorites.php";

include_once '../conn/conn.php';
include_once '../classes/review.php';

// فئة Event
class Event {
    
    private $conn;
    private $table_name = "Events";

    public $eventID;
    public $name;
    public $date;
    public $location;
    public $type;
    public $seatsAvailable;
    public $description;
    public $organizer;

    public function __construct($db) {
        $this->conn = $db;
    }

    // عرض جميع الأحداث
    public function getEvents() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // عرض تفاصيل حدث معين
    public function getEventDetails($eventID) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE eventID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$eventID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // إضافة حدث جديد
    public function addEvent($eventData) {
        $query = "INSERT INTO " . $this->table_name . " (name, date, location, type, seatsAvailable, description, organizer)
                  VALUES (:name, :date, :location, :type, :seatsAvailable, :description, :organizer)";
        $stmt = $this->conn->prepare($query);

        // ربط البيانات
        $stmt->bindParam(':name', $eventData['name']);
        $stmt->bindParam(':date', $eventData['date']);
        $stmt->bindParam(':location', $eventData['location']);
        $stmt->bindParam(':type', $eventData['type']);
        $stmt->bindParam(':seatsAvailable', $eventData['seatsAvailable']);
        $stmt->bindParam(':description', $eventData['description']);
        $stmt->bindParam(':organizer', $eventData['organizer']);

        return $stmt->execute();
    }

    // تحديث حدث
    public function updateEvent($eventID, $eventData) {
        $query = "UPDATE " . $this->table_name . " SET name = :name, date = :date, location = :location, type = :type,
                  seatsAvailable = :seatsAvailable, description = :description, organizer = :organizer WHERE eventID = :eventID";
        $stmt = $this->conn->prepare($query);

        // ربط البيانات
        $stmt->bindParam(':name', $eventData['name']);
        $stmt->bindParam(':date', $eventData['date']);
        $stmt->bindParam(':location', $eventData['location']);
        $stmt->bindParam(':type', $eventData['type']);
        $stmt->bindParam(':seatsAvailable', $eventData['seatsAvailable']);
        $stmt->bindParam(':description', $eventData['description']);
        $stmt->bindParam(':organizer', $eventData['organizer']);
        $stmt->bindParam(':eventID', $eventID);

        return $stmt->execute();
    }

    // حذف حدث
    public function deleteEvent($eventID) {
        $query = "DELETE FROM " . $this->table_name . " WHERE eventID = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$eventID]);
    }

    // التحقق من توافر المقاعد
    public function checkAvailability($eventID) {
        $query = "SELECT seatsAvailable FROM " . $this->table_name . " WHERE eventID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$eventID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['seatsAvailable'] : 0;
    }

    // إضافة هذه الدالة داخل class Event
    public function searchEvents($searchTerm) {
        $query = "SELECT * FROM " . $this->table_name . 
                 " WHERE name LIKE :search OR 
                        location LIKE :search OR 
                        type LIKE :search OR 
                        description LIKE :search OR 
                        organizer LIKE :search";
        
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%$searchTerm%";
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();
        return $stmt;
    }
}

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$db = $database->getConnection();

// إنشاء كائن Event
$event = new Event($db);
// إنشاء كائن Review
$review = new Review($db);
// جلب الفعاليات من قاعدة البيانات
$stmt = $event->getEvents();
// معالجة إضافة تقييم جديد
if (isset($_POST['addReview'])) {
    // التأكد من وجود جلسة المستخدم
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo "يجب تسجيل الدخول أولاً";
        exit;
    }

    $data = [
        'userID' => (int)$_SESSION['user_id'],  // تحويل إلى integer
        'eventID' => (int)$_POST['eventID'],
        'rating' => (int)$_POST['rating'],
        'comment' => $_POST['comment']
    ];

    if ($review->addReview($data)) {
        header("Location: event_user.php?id=" . $_POST['eventID']);
        exit;
    } else {
        echo "حدث خطأ في إضافة التقييم";
    }
}

// معالجة حذف التقييم
if (isset($_POST['deleteReview']) && isset($_POST['reviewID'])) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo "يجب تسجيل الدخول أولاً";
        exit;
    }

    $reviewID = (int)$_POST['reviewID'];
    $userID = (int)$_SESSION['user_id'];
    
    if ($review->deleteReview($reviewID, $userID)) {
        header("Location: event_user.php?id=" . $_POST['eventID']);
        exit;
    } else {
        echo "فشل حذف التقييم";
    }
}

// معالجة تعديل التقييم
if (isset($_POST['updateReview']) && isset($_POST['reviewID'])) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo "يجب تسجيل الدخول أولاً";
        exit;
    }

    $data = [
        'userID' => (int)$_SESSION['user_id'],
        'rating' => (int)$_POST['rating'],
        'comment' => $_POST['comment']
    ];

    $reviewID = (int)$_POST['reviewID'];
    
    if ($review->updateReview($reviewID, $data)) {
        header("Location: event_user.php?id=" . $_POST['eventID']);
        exit;
    } else {
        echo "فشل تعديل التقييم";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الفعاليات</title>
    <link rel="stylesheet" href="../public/assets/style.css">
    <style>
        .review {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }

        .review-actions {
            margin-top: 10px;
        }

        .btn-edit, .btn-delete, .btn-update, .btn-cancel {
            padding: 5px 10px;
            margin-right: 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .btn-edit {
            background-color: #4CAF50;
            color: white;
        }

        .btn-delete {
            background-color: #f44336;
            color: white;
        }

        .btn-update {
            background-color: #2196F3;
            color: white;
        }

        .btn-cancel {
            background-color: #607D8B;
            color: white;
        }

        .edit-form {
            margin-top: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .edit-form textarea {
            width: 100%;
            margin: 10px 0;
            padding: 5px;
        }

        .edit-form select {
            margin: 10px 0;
            padding: 5px;
        }
            .search-form {
            margin: 20px auto;
            text-align: center;
            max-width: 500px;
        }
        .search-form input[type="text"] {
            padding: 8px;
            width: 70%;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-left: 10px;
        }
        .search-form button {
            padding: 8px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .search-form button:hover {
            background-color: #0056b3;
        }
        .event {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .availability {
            font-weight: bold;
            color: green;
        }
        .btn-book {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
        }
        .btn-book:hover {
            background-color: #218838;
        }
        .review {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        .review p {
            margin: 5px 0;
        }
    </style>
</head>

<body>
    <header>
        <h1>الفعاليات المتاحة</h1>
    </header>
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

    <main>
        <!-- نموذج البحث -->
        <form class="search-form" method="GET" action="">
            <input type="text" name="search" placeholder="ابحث عن فعالية..." 
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">بحث</button>
        </form>

        <?php
session_start();
// معالجة البحث
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $stmt = $event->searchEvents($_GET['search']);
} else {
    $stmt = $event->getEvents();
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // عرض بيانات الحدث
    echo "<div class='event'>";
    echo "<h2>" . htmlspecialchars($row['name']) . "</h2>";
    echo "<p><strong>التاريخ:</strong> " . htmlspecialchars($row['date']) . "</p>";
    echo "<p><strong>المكان:</strong> " . htmlspecialchars($row['location']) . "</p>";
    echo "<p><strong>الوصف:</strong> " . htmlspecialchars($row['description']) . "</p>";
    echo "<p><strong>عدد المقاعد المتاحة:</strong> " . htmlspecialchars($row['seatsAvailable']) . "</p>";
    echo "<p><strong>المنظم:</strong> " . htmlspecialchars($row['organizer']) . "</p>";

    // التحقق من توافر المقاعد
    if ($row['seatsAvailable'] > 0) {
        echo "<p class='availability'>المقاعد متاحة للحجز!</p>";
        echo "<a href='../booking/booking.php?eventID=" . $row['eventID'] . "' class='btn-book'>احجز الآن</a>";
    } else {
        echo "<p class='availability'>عذرًا، لا توجد مقاعد متاحة.</p>";
    }
        
// التحقق من أن المستخدم قد سجل دخوله
if (isset($_SESSION['user_id'])) {
    $userID = $_SESSION['user_id'];
    $favoritesRepository = new FavoritesRepository($db);

    // إنشاء كائن من الكلاس Favorites
    $favorites = new Favorites($favoritesRepository, $userID);
    // معالجة إضافة الفعالية إلى المفضلة
    if (isset($_POST['addFavorite'])) {
        $eventID = $_POST['eventID'];
        $favorites->addFavorite($eventID);
    }

    // معالجة إزالة الفعالية من المفضلة
    if (isset($_POST['removeFavorite'])) {
        $eventID = $_POST['eventID'];
        $favorites->removeFavorite($eventID);
    }

    // التحقق إذا كان الحدث موجودًا في المفضلة
    $isFavorite = false;
    foreach ($favorites->favoriteEvents as $favoriteEvent) { // تعديل هنا
        if ($favoriteEvent['eventID'] == $row['eventID']) {
            $isFavorite = true;
            break;
        }
    }

    // عرض زر إضافة/إزالة من المفضلة
    if ($isFavorite) {
        echo "<form method='POST' action=''>
                <input type='hidden' name='eventID' value='" . $row['eventID'] . "'>
                <button type='submit' name='removeFavorite' class='btn-remove'>إزالة من المفضلة</button>
              </form>";
    } else {
        echo "<form method='POST' action=''>
                <input type='hidden' name='eventID' value='" . $row['eventID'] . "'>
                <button type='submit' name='addFavorite' class='btn-add'>إضافة إلى المفضلة</button>
              </form>";
    }
} else {
    echo "يرجى تسجيل الدخول أولاً.";
}

    $review = new Review($db);
    // جلب المراجعات الخاصة بالحدث
    $reviews = $review->getReviewStats($row['eventID']);
    if (!empty($reviews)) {
        echo "<h3>التقييمات:</h3>";
        foreach ($reviews as $review) {
            echo "<div class='review'>";
            echo "<p><strong>المستخدم:</strong> " . (isset($review['username']) ? htmlspecialchars($review['username']) : 'مستخدم غير موجود') . "</p>";
            
            // نموذج تعديل التقييم
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['userID']) {
                echo "<div id='review-" . $review['reviewID'] . "' class='review-content'>";
                echo "<p><strong>التقييم:</strong> " . htmlspecialchars($review['rating']) . " / 5</p>";
                echo "<p><strong>التعليق:</strong> " . htmlspecialchars($review['comment']) . "</p>";
                echo "<p><strong>تاريخ التقييم:</strong> " . htmlspecialchars($review['createdAt']) . "</p>";
                
                // أزرار التعديل والحذف
                echo "<div class='review-actions'>";
                // زر تظهر/إخفاء نموذج التعديل
                echo "<button onclick='toggleEditForm(" . $review['reviewID'] . ")' class='btn-edit'>تعديل</button>";
                
                // نموذج الحذف
                echo "<form method='POST' style='display:inline;' onsubmit='return confirm(\"هل أنت متأكد من حذف هذا التقييم؟\")'>";
                echo "<input type='hidden' name='reviewID' value='" . $review['reviewID'] . "'>";
                echo "<input type='hidden' name='eventID' value='" . $row['eventID'] . "'>";
                echo "<button type='submit' name='deleteReview' class='btn-delete'>حذف</button>";
                echo "</form>";
                echo "</div>";
                
                // نموذج التعديل (مخفي بشكل افتراضي)
                echo "<div id='edit-form-" . $review['reviewID'] . "' class='edit-form' style='display:none;'>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='reviewID' value='" . $review['reviewID'] . "'>";
                echo "<input type='hidden' name='eventID' value='" . $row['eventID'] . "'>";
                echo "<select name='rating' required>";
                for ($i = 1; $i <= 5; $i++) {
                    $selected = ($review['rating'] == $i) ? 'selected' : '';
                    echo "<option value='$i' $selected>$i نجوم</option>";
                }
                echo "</select>";
                echo "<textarea name='comment' required>" . htmlspecialchars($review['comment']) . "</textarea>";
                echo "<button type='submit' name='updateReview' class='btn-update'>حفظ التعديل</button>";
                echo "<button type='button' onclick='toggleEditForm(" . $review['reviewID'] . ")' class='btn-cancel'>إلغاء</button>";
                echo "</form>";
                echo "</div>";
                echo "</div>";
            } else {
                // عرض عادي للتقييم لغير صاحب التقييم
                echo "<p><strong>التقييم:</strong> " . htmlspecialchars($review['rating']) . " / 5</p>";
                echo "<p><strong>التعليق:</strong> " . htmlspecialchars($review['comment']) . "</p>";
                echo "<p><strong>تاريخ التقييم:</strong> " . htmlspecialchars($review['createdAt']) . "</p>";
            }
            echo "</div>";
        }
    } else {
        echo "<p>لا توجد تقييمات لهذا الحدث بعد.</p>";
    }

    // نموذج إضافة تقييم جديد
    if (isset($_SESSION['user_id'])) {
        echo "<form method='POST' action='' class='add-review-form'>
                <h3>أضف تقييمك:</h3>
                <input type='hidden' name='eventID' value='" . $row['eventID'] . "'>
                <textarea name='comment' placeholder='أضف تعليقك...' required></textarea>
                <select name='rating' required>
                    <option value=''>اختر التقييم</option>
                    <option value='1'>1 نجمة</option>
                    <option value='2'>2 نجوم</option>
                    <option value='3'>3 نجوم</option>
                    <option value='4'>4 نجوم</option>
                    <option value='5'>5 نجوم</option>
                </select>
                <button type='submit' name='addReview' class='btn-submit'>إرسال التقييم</button>
            </form>";
    }

    echo "</div><hr>";
}
?>


<script>
function toggleEditForm(reviewID) {
    const editForm = document.getElementById(`edit-form-${reviewID}`);
    const reviewContent = document.getElementById(`review-${reviewID}`);
    
    if (editForm.style.display === 'none') {
        editForm.style.display = 'block';
    } else {
        editForm.style.display = 'none';
    }
}
</script>
    </main>
</body>
</html>