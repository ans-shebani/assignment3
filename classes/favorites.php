<?php
class FavoritesRepository {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // إضافة فعالية إلى المفضلة
    public function addFavorite($userID, $eventID) {
        $query = "INSERT INTO Favorites (userID, eventID) VALUES (:userID, :eventID)";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':userID', $userID);
        $stmt->bindParam(':eventID', $eventID);
        return $stmt->execute();
    }

    // إزالة فعالية من المفضلة
    public function removeFavorite($userID, $eventID) {
        $query = "DELETE FROM Favorites WHERE userID = :userID AND eventID = :eventID";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':userID', $userID);
        $stmt->bindParam(':eventID', $eventID);
        return $stmt->execute();
    }

    // جلب قائمة الفعاليات المفضلة
    public function getFavorites($userID) {
        $query = "SELECT * FROM Events WHERE eventID IN (SELECT eventID FROM Favorites WHERE userID = :userID)";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':userID', $userID);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class Favorites {
    private $repository;
    private $userID;
    public $favoriteEvents = [];

    public function __construct(FavoritesRepository $repository, $userID) {
        $this->repository = $repository;
        $this->userID = $userID;
        $this->loadFavorites();
    }

    // إضافة فعالية إلى المفضلة
    public function addFavorite($eventID) {
        if ($this->repository->addFavorite($this->userID, $eventID)) {
            $this->loadFavorites(); // تحديث القائمة
            return true;
        }
        return false;
    }

    // إزالة فعالية من المفضلة
    public function removeFavorite($eventID) {
        if ($this->repository->removeFavorite($this->userID, $eventID)) {
            $this->loadFavorites(); // تحديث القائمة
            return true;
        }
        return false;
    }

    // تحميل الفعاليات المفضلة للمستخدم
    private function loadFavorites() {
        $this->favoriteEvents = $this->repository->getFavorites($this->userID);
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
