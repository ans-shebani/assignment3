<?php
class Notification {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // إرسال إشعار جديد
    public function sendNotification($message, $userID = null) {
        try {
            $stmt = $this->db->prepare("INSERT INTO Notifications (message, userID) VALUES (:message, :userID)");
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (Exception $e) {
            echo "Error sending notification: " . $e->getMessage();
            return false;
        }
    }

    // استرجاع جميع الإشعارات
    public function getAllNotifications() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM Notifications ORDER BY date DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Error retrieving notifications: " . $e->getMessage();
            return [];
        }
    }
    
    // استرجاع الإشعارات الخاصة بمستخدم معين
    public function getAllNotificationsForUser($userID) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM Notifications WHERE userID = :userID OR userID IS NULL ORDER BY date DESC");
            $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Error retrieving notifications: " . $e->getMessage();
            return [];
        }
    }

    // حذف إشعار معين
    public function deleteNotification($notificationID) {
        try {
            $stmt = $this->db->prepare("DELETE FROM Notifications WHERE notificationID = :notificationID");
            $stmt->bindParam(':notificationID', $notificationID, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            echo "Error deleting notification: " . $e->getMessage();
            return false;
        }
    }

    // تعديل إشعار معين
    public function updateNotification($notificationID, $message) {
        try {
            $stmt = $this->db->prepare("UPDATE Notifications SET message = :message WHERE notificationID = :notificationID");
            $stmt->bindParam(':notificationID', $notificationID, PDO::PARAM_INT);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (Exception $e) {
            echo "Error updating notification: " . $e->getMessage();
            return false;
        }
    }
}
?>
