<?php
// Gift.php
include "../conn/conn.php";

class Gift {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    // إضافة هدية جديدة
    public function addGiftTicket($senderID, $receiverID, $eventID, $ticketType) {
        $query = "INSERT INTO gifttickets (senderID, receiverID, eventID, ticketType) 
                  VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);

        if ($stmt) {
            $stmt->bindParam(1, $senderID, PDO::PARAM_INT);
            $stmt->bindParam(2, $receiverID, PDO::PARAM_INT);
            $stmt->bindParam(3, $eventID, PDO::PARAM_INT);
            $stmt->bindParam(4, $ticketType, PDO::PARAM_STR);

            if ($stmt->execute()) {
                return [
                    'status' => true,
                    'message' => 'تم إرسال الهدية بنجاح',
                    'giftID' => $this->conn->lastInsertId()
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'حدث خطأ أثناء إرسال الهدية'
                ];
            }
        }

        return [
            'status' => false,
            'message' => 'حدث خطأ في الاتصال'
        ];
    }

    // الحصول على الهدايا المرسلة لمستخدم معين
    public function getReceivedGifts($receiverID) {
        $query = "SELECT g.*, e.name AS eventName, e.date AS eventDate, u.name AS senderName 
                  FROM gifttickets g 
                  LEFT JOIN Events e ON g.eventID = e.eventID 
                  LEFT JOIN Users u ON g.senderID = u.userID 
                  WHERE g.receiverID = :receiverID";

        $stmt = $this->conn->prepare($query);

        if ($stmt) {
            $stmt->bindParam(':receiverID', $receiverID, PDO::PARAM_INT);
            $stmt->execute();
            $gifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'gifts' => $gifts
            ];
        }

        return [
            'status' => false,
            'message' => 'حدث خطأ في استرجاع الهدايا'
        ];
    }


    // التحقق من صلاحية الهدية
    public function validateGift($eventID, $receiverID) {
        // التحقق من وجود الحدث
        $query = "SELECT * FROM events WHERE eventID = :eventID AND date > NOW()";
        $stmt = $this->conn->prepare($query);

        if ($stmt) {
            $stmt->bindParam(':eventID', $eventID, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return [
                    'status' => false,
                    'message' => 'الحدث غير متوفر أو انتهى'
                ];
            }

            // التحقق من عدم تكرار الهدية
            $checkQuery = "SELECT * FROM gifttickets WHERE eventID = :eventID AND receiverID = :receiverID";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':eventID', $eventID, PDO::PARAM_INT);
            $checkStmt->bindParam(':receiverID', $receiverID, PDO::PARAM_INT);
            $checkStmt->execute();
            $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($checkResult) {
                return [
                    'status' => false,
                    'message' => 'تم إرسال هدية لهذا الحدث مسبقاً'
                ];
            }

            return [
                'status' => true,
                'message' => 'يمكن إرسال الهدية'
            ];
        }

        return [
            'status' => false,
            'message' => 'حدث خطأ في التحقق من صلاحية الهدية'
        ];
    }

public function receiveGift($giftID, $userID) {
    // تعديل الاستعلام ليتحقق من وجود الهدية أولاً
    $query = "UPDATE gifttickets 
              SET status = 'received' 
              WHERE giftTicketID = :gift_id 
              AND receiverID = :user_id 
              AND status IS NULL";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':gift_id', $giftID, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userID, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // إذا تم تحديث السجل بنجاح، نقوم بإضافة تذكرة جديدة
        if ($stmt->rowCount() > 0) {
            return $this->createTicketFromGift($giftID);
        }
    }
    return false;
}

// دالة لإنشاء تذكرة من الهدية
private function createTicketFromGift($giftID) {
    $this->conn->beginTransaction();
    
    try {
        // Get gift information including ticketType
        $query = "SELECT g.*, e.regularTicketPrice, e.vipTicketPrice, g.ticketType 
                  FROM gifttickets g 
                  JOIN events e ON g.eventID = e.eventID 
                  WHERE g.giftTicketID = :gift_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':gift_id', $giftID, PDO::PARAM_INT);
        $stmt->execute();
        $giftData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$giftData) {
            throw new Exception("الهدية غير موجودة أو تم حذفها.");
        }

        // Determine ticket price based on ticketType
        $ticketPrice = ($giftData['ticketType'] === 'VIP') ? 
            $giftData['vipTicketPrice'] : $giftData['regularTicketPrice'];

        // Check seat availability
        $query = "SELECT seatsAvailable FROM events WHERE eventID = :event_id FOR UPDATE";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':event_id', $giftData['eventID'], PDO::PARAM_INT);
        $stmt->execute();
        $eventSeats = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($eventSeats['seatsAvailable'] <= 0) {
            throw new Exception("عذراً، لا توجد مقاعد متاحة لهذا الحدث.");
        }

        // Create ticket
        $query = "INSERT INTO tickets (eventID, userID, ticketType, status, price) 
                  VALUES (:event_id, :user_id, :ticket_type, 'Confirmed', :price)";
        $stmt = $this->conn->prepare($query);
        $params = [
            ':event_id' => $giftData['eventID'],
            ':user_id' => $giftData['receiverID'],
            ':ticket_type' => $giftData['ticketType'],
            ':price' => $ticketPrice
        ];
        
        if (!$stmt->execute($params)) {
            throw new Exception("فشل في إنشاء التذكرة.");
        }

        // Update available seats
        $query = "UPDATE events 
                  SET seatsAvailable = seatsAvailable - 1 
                  WHERE eventID = :event_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':event_id', $giftData['eventID'], PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception("فشل في تحديث عدد المقاعد المتاحة.");
        }

        // Delete the gift
        $query = "DELETE FROM gifttickets WHERE giftTicketID = :gift_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':gift_id', $giftID, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception("فشل في حذف الهدية.");
        }

        $this->conn->commit();
        
        // Create notification
        $this->createNotification(
            $giftData['receiverID'], 
            "تم تحويل الهدية الخاصة بك إلى تذكرة بنجاح للحدث."
        );

        return true;

    } catch (Exception $e) {
        $this->conn->rollBack();
        error_log("خطأ في createTicketFromGift: " . $e->getMessage());
        throw new Exception("فشل في تحويل الهدية إلى تذكرة: " . $e->getMessage());
    }
}

// دالة  لإنشاء الإشعارات
private function createNotification($userID, $message) {
    try {
        $query = "INSERT INTO notifications (userID, message) VALUES (:user_id, :message)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $userID,
            ':message' => $message
        ]);
        return true;
    } catch (Exception $e) {
        error_log("خطأ في إنشاء الإشعار: " . $e->getMessage());
        return false;
    }
}



// تعديل دالة getGiftsNotReceived
public function getGiftsNotReceived($userID) {
    $query = "SELECT g.*, e.name AS eventName, e.date AS eventDate, u.name AS senderName 
              FROM gifttickets g 
              LEFT JOIN events e ON g.eventID = e.eventID 
              LEFT JOIN users u ON g.senderID = u.userID 
              WHERE g.receiverID = :user_id AND g.status IS NULL";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':user_id', $userID);
    
    if ($stmt->execute()) {
        return [
            'status' => true,
            'gifts' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }
    
    return [
        'status' => false,
        'message' => 'حدث خطأ في استرجاع الهدايا'
    ];
}

}
?>
