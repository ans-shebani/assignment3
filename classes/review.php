<?php


// فئة Review
class Review {
    private $conn;
    private $table_name = "Reviews";
    public $reviewID;
    public $eventID;
    public $userID;
    public $rating;
    public $comment;
    public $createdAt;

    // تعديل المُنشئ ليقبل كل من $db و $eventID
    public function __construct($db) {
        $this->conn = $db;
    }
    

    // دالة جلب إحصائيات المراجعات لحدث معين
    public function getReviewStats($eventID) {
        $query = "SELECT r.reviewID, r.rating, r.comment, r.createdAt, r.userID, 
                  u.name as username, u.userID as userID
                  FROM " . $this->table_name . " r
                  INNER JOIN Users u ON r.userID = u.userID
                  WHERE r.eventID = ?
                  ORDER BY r.createdAt DESC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$eventID]);
        
        $reviews = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reviews[] = array(
                'reviewID' => $row['reviewID'],
                'rating' => $row['rating'],
                'comment' => $row['comment'],
                'createdAt' => $row['createdAt'],
                'username' => $row['username'],
                'userID' => $row['userID']  // الآن سيأخذ userID من جدول Users
            );
        }    
        
        return $reviews;
    }
    // دالة إضافة مراجعة جديدة
    public function addReview($data) {
        try {
            // التحقق من وجود userID قبل الإضافة
            if (!isset($data['userID']) || empty($data['userID'])) {
                return false;
            }
    
            $query = "INSERT INTO " . $this->table_name . " 
                      (eventID, userID, rating, comment) 
                      VALUES (:eventID, :userID, :rating, :comment)";
    
            $stmt = $this->conn->prepare($query);
    
            // تحويل userID إلى integer للتأكد من أنه رقم صحيح
            $userID = (int)$data['userID'];
            $eventID = (int)$data['eventID'];
            $rating = (int)$data['rating'];
            $comment = htmlspecialchars($data['comment']);
    
            // ربط البيانات
            $stmt->bindValue(":eventID", $eventID, PDO::PARAM_INT);
            $stmt->bindValue(":userID", $userID, PDO::PARAM_INT);
            $stmt->bindValue(":rating", $rating, PDO::PARAM_INT);
            $stmt->bindValue(":comment", $comment, PDO::PARAM_STR);
    
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("خطأ في إضافة التقييم: " . $e->getMessage());
            return false;
        }
    }

// دالة حذف التقييم
public function deleteReview($reviewID, $userID) {
    try {
        // التحقق أولاً من وجود التقييم وملكية المستخدم له
        $checkQuery = "SELECT reviewID FROM " . $this->table_name . " 
                      WHERE reviewID = ? AND userID = ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->execute([$reviewID, $userID]);
        
        if ($checkStmt->rowCount() === 0) {
            return false; // التقييم غير موجود أو لا يملكه هذا المستخدم
        }

        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE reviewID = ? AND userID = ?";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([(int)$reviewID, (int)$userID]);
    } catch (PDOException $e) {
        error_log("خطأ في حذف التقييم: " . $e->getMessage());
        return false;
    }
}

// دالة تعديل التقييم
public function updateReview($reviewID, $data) {
    try {
        // التحقق من وجود التقييم وملكية المستخدم له
        $checkQuery = "SELECT reviewID FROM " . $this->table_name . " 
                      WHERE reviewID = ? AND userID = ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->execute([$reviewID, $data['userID']]);
        
        if ($checkStmt->rowCount() === 0) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                  SET rating = :rating, comment = :comment
                  WHERE reviewID = :reviewID AND userID = :userID";

        $stmt = $this->conn->prepare($query);

        // تنظيف وتحويل البيانات
        $rating = (int)$data['rating'];
        $comment = htmlspecialchars($data['comment']);
        $userID = (int)$data['userID'];
        $reviewID = (int)$reviewID;

        // ربط البيانات
        $stmt->bindValue(":rating", $rating, PDO::PARAM_INT);
        $stmt->bindValue(":comment", $comment, PDO::PARAM_STR);
        $stmt->bindValue(":reviewID", $reviewID, PDO::PARAM_INT);
        $stmt->bindValue(":userID", $userID, PDO::PARAM_INT);

        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("خطأ في تعديل التقييم: " . $e->getMessage());
        return false;
    }
}
}