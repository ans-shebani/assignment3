<?php
session_start();
include_once '../conn/conn.php'; // تأكد من تضمين ملف قاعدة البيانات
include_once '../user/user.php'; // تأكد من تضمين ملف المستخدم

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $userType = $_POST['userType'];

    if ($user->register($name, $email, $password, $phone, $address, $userType)) {
        header("Location: ../booking/booking.php");
        exit();
    } else {
        $error = "حدث خطأ في التسجيل. قد يكون البريد الإلكتروني مسجل مسبقاً.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حساب جديد</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .register-container {
            max-width: 400px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>تسجيل حساب جديد</h2>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>الاسم</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>رقم الهاتف</label>
                <input type="tel" name="phone" required>
            </div>
            
            <div class="form-group">
                <label>العنوان</label>
                <input type="text" name="address" required>
            </div>
            
            <div class="form-group">
                <label>نوع المستخدم</label>
                <select name="userType" required>
                    <option value="student">طالب</option>
                    <option value="military">عسكري</option>
                    <option value="teacher">معلم</option>
                    <option value="the_elderly">كبار السن</option>
                </select>
            </div>
            
            <button type="submit">تسجيل</button>
        </form>
    </div>
</body>
</html>
