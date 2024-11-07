<?php
// تضمين ملف الاتصال بقاعدة البيانات
include_once '../conn/conn.php';

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

    // جلب المراجعات للحدث
    public function getReviews($eventID) {
        $query = "SELECT * FROM Reviews WHERE eventID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$eventID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // إضافة مراجعة للحدث
    public function addReview($userID, $eventID, $rating, $comment) {
        $query = "INSERT INTO Reviews (userID, eventID, rating, comment) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$userID, $eventID, $rating, $comment]);
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

// جلب الفعاليات من قاعدة البيانات
$stmt = $event->getEvents();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الفعاليات</title>
    <link rel="stylesheet" href="../public/assets/style.css">
    <style>
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
                    echo "<a href='../booking/booking.php?eventID=" . $row['eventID'] . "' class='btn-book'>احجز الآن</a>"; // زر الحجز
                } else {
                    echo "<p class='availability'>عذرًا، لا توجد مقاعد متاحة.</p>";
                }

                // جلب المراجعات الخاصة بالحدث
                $reviews = $event->getReviews($row['eventID']);
                if (!empty($reviews)) {
                    echo "<h3>التقييمات:</h3>";
                    foreach ($reviews as $review) {
                        echo "<div class='review'>";
                        echo "<p><strong>التقييم:</strong> " . htmlspecialchars($review['rating']) . " / 5</p>";
                        echo "<p><strong>تعليق:</strong> " . htmlspecialchars($review['comment']) . "</p>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>لا توجد تقييمات لهذا الحدث بعد.</p>";
                }

                echo "</div><hr>";
            }
            ?>
        </section>
    </main>
</body>
</html>
