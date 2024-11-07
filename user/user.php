<?php
class User {
    private $conn;
    private $table_name = "Users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($name, $email, $password, $phone, $address, $userType) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO " . $this->table_name . " (name, email, password, phone, address, userType) 
                  VALUES (:name, :email, :password, :phone, :address, :userType)";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':phone' => $phone,
                ':address' => $address,
                ':userType' => $userType
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function login($email, $password) {
        $query = "SELECT u.*, a.adminID 
                  FROM " . $this->table_name . " u 
                  LEFT JOIN Admins a ON u.userID = a.userID 
                  WHERE u.email = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($user && password_verify($password, $user['password'])) {
            // التحقق من كونه مديرا
            $isAdmin = !is_null($user['adminID']);
            return ['user' => $user, 'is_admin' => $isAdmin];
        }
        return false;
    }
    
}    
?>
