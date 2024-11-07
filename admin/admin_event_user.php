<?php
include_once '../conn/conn.php';

class Admin {
    private $conn;
    private $table_name = "events";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getEvents() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function addEvent($eventData) {
        $query = "INSERT INTO " . $this->table_name . " (name, date, location, type, seatsAvailable, description, organizer, regularTicketPrice, vipTicketPrice) VALUES 
                  (:name, :date, :location, :type, :seatsAvailable, :description, :organizer, :regularTicketPrice, :vipTicketPrice)";
        $stmt = $this->conn->prepare($query);
    
        if (!$stmt->execute($eventData)) {
            print_r($stmt->errorInfo()); // طباعة معلومات الخطأ
            return false;
        }
        return true;
    }
    
    public function updateEvent($eventID, $eventData) {
        $query = "UPDATE " . $this->table_name . " SET name = :name, date = :date, location = :location, type = :type, 
                  seatsAvailable = :seatsAvailable, description = :description, organizer = :organizer, 
                  regularTicketPrice = :regularTicketPrice, vipTicketPrice = :vipTicketPrice WHERE eventID = :eventID";
        $stmt = $this->conn->prepare($query);
        $eventData['eventID'] = $eventID; // إضافة ID الفعالية إلى البيانات
        return $stmt->execute($eventData);
    }

    public function deleteEvent($eventId) {
        $query = "DELETE FROM Events WHERE eventID = :eventID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':eventID', $eventId);
        return $stmt->execute();
    }
    
    public function getAllEvents() {
        $query = "SELECT * FROM events";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getEventById($eventId) {
        $query = "SELECT * FROM events WHERE eventID = :eventID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':eventID', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getAllUsers() {
        $query = "SELECT userID, name, email, phone, address, userType FROM Users";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
}


$database = new Database();
$db = $database->getConnection();
