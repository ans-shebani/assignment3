<?php
require_once __DIR__ . '/../conn/conn.php'; 
require_once  __DIR__ .'/../classes/Notification.php'; 
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    private $pdo;
    private $notification;

    protected function setUp(): void
    {
        // استخدام كائن Database للحصول على الاتصال
        $database = new Database();
        $this->pdo = $database->getConnection();

        if (!$this->pdo) {
            throw new Exception("Failed to establish a database connection.");
        }

        // تهيئة جدول Notifications
        $this->pdo->exec("TRUNCATE TABLE Notifications");

        // إنشاء كائن Notification
        $this->notification = new Notification($this->pdo);
    }

    public function testSendNotificationWithUserID()
    {
        $result = $this->notification->sendNotification("Test message", 1);
        $this->assertTrue($result);

        // التحقق من وجود الإشعار في قاعدة البيانات
        $stmt = $this->pdo->query("SELECT * FROM Notifications WHERE message = 'Test message'");
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($notification);
        $this->assertEquals("Test message", $notification['message']);
        $this->assertEquals(1, $notification['userID']);
    }

    public function testSendNotificationWithoutUserID()
    {
        $result = $this->notification->sendNotification("Test message without user ID");
        $this->assertTrue($result);

        // التحقق من وجود الإشعار في قاعدة البيانات
        $stmt = $this->pdo->query("SELECT * FROM Notifications WHERE message = 'Test message without user ID'");
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($notification);
        $this->assertNull($notification['userID']);
    }

    public function testGetAllNotifications()
    {
        // إدخال بيانات
        $this->notification->sendNotification("Message 1", 1);
        $this->notification->sendNotification("Message 2", 2);

        $notifications = $this->notification->getAllNotifications();
        $this->assertCount(2, $notifications);
    }

    public function testGetAllNotificationsForUser()
    {
        // إدخال بيانات
        $this->notification->sendNotification("Message 1", 1);
        $this->notification->sendNotification("Message 2", 2);
        $this->notification->sendNotification("Message for all users");

        $userNotifications = $this->notification->getAllNotificationsForUser(1);
        $this->assertCount(2, $userNotifications);

        foreach ($userNotifications as $notification) {
            $this->assertTrue($notification['userID'] === null || $notification['userID'] == 1);
        }
    }

    public function testDeleteNotification()
    {
        // إدخال إشعار
        $this->notification->sendNotification("Message to delete", 1);
        $stmt = $this->pdo->query("SELECT notificationID FROM Notifications WHERE message = 'Message to delete'");
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);

        // حذف الإشعار
        $result = $this->notification->deleteNotification($notification['notificationID']);
        $this->assertTrue($result);

        // التحقق من الحذف
        $stmt = $this->pdo->query("SELECT * FROM Notifications WHERE notificationID = {$notification['notificationID']}");
        $this->assertFalse($stmt->fetch());
    }

    public function testUpdateNotification()
    {
        // إدخال إشعار
        $this->notification->sendNotification("Old message", 1);
        $stmt = $this->pdo->query("SELECT notificationID FROM Notifications WHERE message = 'Old message'");
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);

        // تحديث الرسالة
        $result = $this->notification->updateNotification($notification['notificationID'], "Updated message");
        $this->assertTrue($result);
        // التحقق من التحديث
        $stmt = $this->pdo->query("SELECT * FROM Notifications WHERE notificationID = {$notification['notificationID']}");
        $updatedNotification = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals("Updated message", $updatedNotification['message']);
    }
}
?>