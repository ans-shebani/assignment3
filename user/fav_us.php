<?php
include "../classes/favorites.php";
 include "../conn/conn.php";
session_start();

// التأكد من وجود المستخدم في الجلسة
if (!isset($_SESSION['user_id'])) {
    echo "يرجى تسجيل الدخول أولاً.";
    exit;
}

// الحصول على ID المستخدم من الجلسة
$userID = $_SESSION['user_id'];

// إنشاء كائن من الكلاس Favorites
$conn = new Database();
$pdo = $conn->getConnection();
$favorites = new Favorites($pdo, $userID);

// التعامل مع الحذف إذا تم إرسال بيانات عبر POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['removeFavorite'])) {
    $eventID = $_POST['eventID'];
    if ($favorites->removeFavorite($eventID)) {
        echo "<p class='success-message'>تمت إزالة الفعالية من المفضلة.</p>";
        // إعادة تحميل الفعاليات المفضلة بعد الحذف
        $favorites->displayFavorites();
    } else {
        echo "<p class='error-message'>حدث خطأ أثناء إزالة الفعالية.</p>";
    }
} else {
    // عرض الفعاليات المفضلة إذا لم يكن هناك حذف
    $favorites->displayFavorites();
}
?>

<!-- إضافة تنسيقات CSS مباشرة داخل الصفحة -->
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f9;
        margin: 0;
        padding: 0;
    }

    .container {
        width: 80%;
        margin: 0 auto;
        padding: 20px;
        background-color: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
    }

    h2 {
        text-align: center;
        color: #333;
    }

    .event {
        background-color: #fafafa;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
    }

    .event h3 {
        color: #5b5b5b;
        font-size: 20px;
    }

    .event p {
        color: #777;
        margin: 10px 0;
    }

    .remove-button {
        background-color: #ff6f61;
        color: white;
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
    }

    .remove-button:hover {
        background-color: #e05a4b;
    }

    .message {
        text-align: center;
        margin: 20px;
    }

    .success-message {
        color: green;
    }

    .error-message {
        color: red;
    }

    .no-favorites {
        text-align: center;
        color: #aaa;
    }
</style>
