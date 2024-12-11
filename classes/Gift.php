<?php
// Gift.php
include "../conn/conn.php";

class Gift {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    // إضافة هدية جديدة
    public function addGiftTicket($senderID, $receiverID, $eventID) {
        $query = "INSERT INTO gifttickets (senderID, receiverID, eventID) VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($query);

        if ($stmt) {
            $stmt->bindParam(1, $senderID, PDO::PARAM_INT);
            $stmt->bindParam(2, $receiverID, PDO::PARAM_INT);
            $stmt->bindParam(3, $eventID, PDO::PARAM_INT);

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

    // الحصول على الهدايا المرسلة من مستخدم معين
    public function getSentGifts($senderID) {
        $query = "SELECT g.*, e.name AS eventName, e.date AS eventDate, u.name AS receiverName 
                  FROM gifttickets g 
                  LEFT JOIN events e ON g.eventID = e.eventID 
                  LEFT JOIN users u ON g.receiverID = u.userID 
                  WHERE g.senderID = :senderID";

        $stmt = $this->conn->prepare($query);

        if ($stmt) {
            $stmt->bindParam(':senderID', $senderID, PDO::PARAM_INT);
            $stmt->execute();
            $gifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'gifts' => $gifts
            ];
        }

        return [
            'status' => false,
            'message' => 'حدث خطأ في استرجاع الهدايا المرسلة'
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
    try {
        // جلب معلومات الهدية
        $query = "SELECT * FROM gifttickets WHERE giftTicketID = :gift_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':gift_id', $giftID, PDO::PARAM_INT);
        $stmt->execute();
        $gift = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($gift) {
            // جلب معلومات الحدث لمعرفة السعر
            $query = "SELECT * FROM events WHERE eventID = :event_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':event_id', $gift['eventID'], PDO::PARAM_INT);
            $stmt->execute();
            $event = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($event) {
                // تحديد السعر بناءً على نوع التذكرة
                $ticketPrice = ($gift['ticketType'] == 'VIP') ? $event['vipTicketPrice'] : $event['regularTicketPrice'];

                // إنشاء التذكرة الجديدة
                $query = "INSERT INTO tickets (eventID, userID, ticketType, status, price) 
                          VALUES (:event_id, :user_id, :ticket_type, 'Confirmed', :price)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':event_id', $gift['eventID'], PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $gift['receiverID'], PDO::PARAM_INT);
                $stmt->bindParam(':ticket_type', $gift['ticketType'], PDO::PARAM_STR);
                $stmt->bindParam(':price', $ticketPrice, PDO::PARAM_STR);  // استخدام السعر المناسب
                
                if ($stmt->execute()) {
                    return true; // تم إضافة التذكرة بنجاح
                } else {
                    throw new Exception("لم يتم إضافة التذكرة.");
                }
            } else {
                throw new Exception("الحدث غير موجود.");
            }
        } else {
            throw new Exception("الهدية غير موجودة.");
        }
    } catch (Exception $e) {
        // طباعة الخطأ للتصحيح
        echo "حدث خطأ: " . $e->getMessage();
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
