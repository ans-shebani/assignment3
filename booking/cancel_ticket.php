<?php
session_start();
include_once '../conn/conn.php';

if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit();
}

if (!isset($_POST['ticketID']) || !isset($_POST['eventID'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير كاملة']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // بدء المعاملة
    $db->beginTransaction();
    
    // تحديث حالة التذكرة
    $updateTicketQuery = "UPDATE Tickets SET status = 'Cancelled' 
                         WHERE ticketID = ? AND userID = ?";
    $stmt = $db->prepare($updateTicketQuery);
    $stmt->execute([$_POST['ticketID'], $_SESSION['userID']]);
    
    // زيادة عدد المقاعد المتاحة
    $updateSeatsQuery = "UPDATE Events SET seatsAvailable = seatsAvailable + 1 
                        WHERE eventID = ?";
    $stmt = $db->prepare($updateSeatsQuery);
    $stmt->execute([$_POST['eventID']]);
    
    // تأكيد المعاملة
    $db->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // التراجع عن المعاملة في حالة حدوث خطأ
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}