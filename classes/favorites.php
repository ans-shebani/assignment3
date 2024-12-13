<?php
//include "../conn/conn.php";
class Favorites {
    private $pdo;
    private $userID;
    public $favoriteEvents = []; 

    public function __construct($pdo, $userID) {
        $this->pdo = $pdo;
        $this->userID = $userID;
        $this->loadFavorites();
    }

    // إضافة فعالية إلى المفضلة
    public function addFavorite($eventID) {
        $query = "INSERT INTO Favorites (userID, eventID) VALUES (:userID, :eventID)";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':userID', $this->userID);
        $stmt->bindParam(':eventID', $eventID);
        if ($stmt->execute()) {
            $this->loadFavorites(); 
            return true;
        }
        return false;
    }

    // إزالة فعالية من المفضلة
    public function removeFavorite($eventID) {
        $query = "DELETE FROM Favorites WHERE userID = :userID AND eventID = :eventID";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':userID', $this->userID);
        $stmt->bindParam(':eventID', $eventID);
        return $stmt->execute();
    }

    // تحميل الفعاليات المفضلة للمستخدم من قاعدة البيانات
    private function loadFavorites() {
        $query = "SELECT * FROM Events WHERE eventID IN (SELECT eventID FROM Favorites WHERE userID = :userID)";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':userID', $this->userID);
        $stmt->execute();
        $this->favoriteEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // عرض الفعاليات المفضلة للمستخدم
    public function displayFavorites() {
        echo "<div class='container'>";
        if (count($this->favoriteEvents) > 0) {
            foreach ($this->favoriteEvents as $event) {
                echo "<div class='event'>";
                echo "<h3>" . htmlspecialchars($event['name']) . "</h3>";
                echo "<p>الموقع: " . htmlspecialchars($event['location']) . "</p>";
                echo "<p>التاريخ: " . htmlspecialchars($event['date']) . "</p>";
                echo "<form method='POST' action=''>
                        <input type='hidden' name='eventID' value='" . $event['eventID'] . "'>
                        <button type='submit' name='removeFavorite' class='remove-button'>إزالة من المفضلة</button>
                      </form>";
                echo "</div>";
            }
        } else {
            echo "<p class='no-favorites'>لا توجد فعاليات مفضلة لديك.</p>";
        }
        echo "</div>";
    }
}
