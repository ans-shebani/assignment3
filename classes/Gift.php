<?php
include "../conn/conn.php";

// واجهة المراقب للإشعارات
interface NotificationObserver {
    public function update(string $message): void;
}

// واجهة استراتيجية التحقق من صلاحية الهدية
interface GiftValidationStrategy {
    public function validate(int $eventID, int $receiverID): array;
}

// حالات الهدية
abstract class GiftState {
    protected Gift $gift;

    public function setContext(Gift $gift): void {
        $this->gift = $gift;
    }

    abstract public function handle(): array;
}

class PendingGiftState extends GiftState {
    public function handle(): array {
        return [
            'status' => 'pending',
            'message' => 'الهدية في انتظار الاستلام'
        ];
    }
}

class ReceivedGiftState extends GiftState {
    public function handle(): array {
        return [
            'status' => 'received',
            'message' => 'تم استلام الهدية'
        ];
    }
}

// مصنع الهدايا
class GiftFactory {
    public static function createGift(PDO $connection): Gift {
        return Gift::getInstance($connection);
    }
}

// فئة المراقب للإشعارات
class NotificationObserverImplementation implements NotificationObserver {
    private $db;
    private $userID;

    // تهيئة الكائن مع قاعدة البيانات ومعرف المستخدم
    public function __construct($db, $userID) {
        $this->db = $db;
        $this->userID = $userID;
    }

    // دالة تحديث الإشعار
    public function update(string $message): void {
        try {
            // تحقق من صحة معرف المستخدم
            if (!$this->userID) {
                throw new Exception("معرف المستخدم غير صالح");
            }
    
            // استعلام محسن مع تحديد جميع الأعمدة المطلوبة
            $query = "INSERT INTO notifications (userID, message, date) 
                      VALUES (:userID, :message, NOW())";
            
            $stmt = $this->db->prepare($query);
            $params = [
                ':userID' => $this->userID,
                ':message' => $message
            ];
            
            if (!$stmt->execute($params)) {
                throw new Exception("فشل في إدخال الإشعار: " . implode(" ", $stmt->errorInfo()));
            }
        } catch (PDOException $e) {
            error_log("خطأ في قاعدة البيانات عند إضافة الإشعار: " . $e->getMessage());
            throw new Exception("فشل في حفظ الإشعار");
        } catch (Exception $e) {
            error_log("خطأ عام عند إضافة الإشعار: " . $e->getMessage());
            throw $e;
        }
    }
    
}


class Gift {
    private static ?Gift $instance = null;
    private PDO $conn;
    private GiftState $state;
    private array $observers = [];

    public function __construct(PDO $connection) {
        $this->conn = $connection;
        $this->state = new PendingGiftState();
    }

    public static function getInstance(PDO $connection): Gift {
        if (self::$instance === null) {
            self::$instance = new self($connection);
        }
        return self::$instance;
    }

    // إضافة مراقب للإشعارات
    public function attachObserver(NotificationObserver $observer): void {
        $this->observers[] = $observer;
    }

    // تنفيذ الإشعارات
    private function notifyObservers(string $message): void {
        foreach ($this->observers as $observer) {
            $observer->update($message);
        }
    }
    public function addObserver(NotificationObserver $observer) {
        $this->observers[] = $observer;
    }
    // إضافة هدية جديدة مع التحقق من الصلاحية
    public function addGiftTicket(int $senderID, int $receiverID, int $eventID, string $ticketType): array {
        try {
            // التحقق من صلاحية الهدية
            $validationResult = $this->validateGift($eventID, $receiverID);
            if (!$validationResult['status']) {
                return $validationResult;
            }
    
            // إنشاء وإضافة المراقب قبل تنفيذ العملية
            $observer = new NotificationObserverImplementation($this->conn, $receiverID);
            $this->attachObserver($observer);
    
            $query = "INSERT INTO gifttickets (senderID, receiverID, eventID, ticketType) 
                      VALUES (:sender, :receiver, :event, :type)";
            
            $stmt = $this->conn->prepare($query);
            $params = [
                ':sender' => $senderID,
                ':receiver' => $receiverID,
                ':event' => $eventID,
                ':type' => $ticketType
            ];
    
            $this->conn->beginTransaction();
            
            if ($stmt->execute($params)) {
                $giftID = $this->conn->lastInsertId();
                
                // إرسال الإشعار داخل المعاملة
                try {
                    $this->notifyObservers("تم إرسال هدية جديدة برقم: $giftID");
                    $this->conn->commit();
                    
                    return [
                        'status' => true,
                        'message' => 'تم إرسال الهدية والإشعار بنجاح',
                        'giftID' => $giftID
                    ];
                } catch (Exception $e) {
                    $this->conn->rollBack();
                    throw $e;
                }
            }
    
            $this->conn->rollBack();
            throw new Exception('فشل في إضافة الهدية');
            
        } catch (Exception $e) {
            error_log("خطأ في إضافة الهدية: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ];
        }
    }

    // التحقق من صلاحية الهدية (Strategy Pattern)
    public function validateGift(int $eventID, int $receiverID): array {
        try {
            $query = "SELECT e.*, 
                      (SELECT COUNT(*) FROM gifttickets 
                       WHERE eventID = e.eventID AND receiverID = :receiver) as giftCount
                      FROM events e 
                      WHERE e.eventID = :event AND e.date > NOW()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':event' => $eventID, ':receiver' => $receiverID]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return [
                    'status' => false,
                    'message' => 'الحدث غير متوفر أو انتهى'
                ];
            }

            if ($result['giftCount'] > 0) {
                return [
                    'status' => false,
                    'message' => 'تم إرسال هدية لهذا الحدث مسبقاً'
                ];
            }

            return [
                'status' => true,
                'message' => 'يمكن إرسال الهدية'
            ];
        } catch (Exception $e) {
            error_log("خطأ في التحقق من الهدية: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ في التحقق: ' . $e->getMessage()
            ];
        }
    }

    // استلام الهدية وتحويلها إلى تذكرة
    public function receiveGift(int $giftID, int $userID): array {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE gifttickets 
                      SET status = 'received' 
                      WHERE giftTicketID = :gift 
                      AND receiverID = :user 
                      AND status IS NULL";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([':gift' => $giftID, ':user' => $userID]);

            if ($stmt->rowCount() > 0) {
                $this->state = new ReceivedGiftState();
                $result = $this->createTicketFromGift($giftID);
                
                if ($result) {
                    $this->conn->commit();
                    $this->notifyObservers("تم استلام الهدية رقم: $giftID");
                    return [
                        'status' => true,
                        'message' => 'تم استلام الهدية وتحويلها إلى تذكرة بنجاح'
                    ];
                }
            }

            $this->conn->rollBack();
            return [
                'status' => false,
                'message' => 'فشل في استلام الهدية'
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("خطأ في استلام الهدية: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ];
        }
    }

    // إنشاء الإشعارات بعد تحويل الهدية إلى تذكرة
    private function createNotification($userID, $message) {
        try {
            $query = "INSERT INTO notifications (userID, message) VALUES (:user_id, :message)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([ ':user_id' => $userID, ':message' => $message ]);
            return true;
        } catch (Exception $e) {
            error_log("خطأ في إنشاء الإشعار: " . $e->getMessage());
            return false;
        }
    }

    private function createTicketFromGift($giftID) {
        try {
            $query = "INSERT INTO tickets (giftTicketID, status) 
                      VALUES (:giftID, 'active')";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':giftID' => $giftID]);
            return true;
        } catch (Exception $e) {
            error_log("خطأ في createTicketFromGift: " . $e->getMessage());
            return false;
        }
    }

    // استرجاع الهدايا غير المستلمة
    public function getGiftsNotReceived(int $userID): array {
        try {
            $query = "SELECT g.*, e.name AS eventName, e.date AS eventDate, 
                             u.name AS senderName 
                      FROM gifttickets g 
                      LEFT JOIN events e ON g.eventID = e.eventID 
                      LEFT JOIN users u ON g.senderID = u.userID 
                      WHERE g.receiverID = :user AND g.status IS NULL";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([':user' => $userID]);

            return [
                'status' => true,
                'gifts' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            error_log("خطأ في استرجاع الهدايا: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ في استرجاع الهدايا: ' . $e->getMessage()
            ];
        }
    }
}
?>
