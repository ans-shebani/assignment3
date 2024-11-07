<?php
session_start();
include_once '../conn/conn.php';
include_once '../user/user.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $userData = $user->login($email, $password);
    
    if ($userData) {
        $_SESSION['user_id'] = $userData['user']['userID'];
        $_SESSION['user_name'] = $userData['user']['name'];
        $_SESSION['user_type'] = $userData['user']['userType'];
        
        if ($userData['is_admin']) {
            $_SESSION['is_admin'] = true;
            header("Location:../admin/admin_dashboard.php");  // توجيه الإداري إلى صفحة المدير
        } else {
            header("Location:../public/main.php");  // توجيه المستخدم العادي إلى الصفحة الرئيسية
        }
        exit();
    } else {
        $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة";
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffe6e6;
            border-radius: 4px;
        }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #4CAF50;
            text-decoration: none;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 style="text-align: center; margin-bottom: 30px;">تسجيل الدخول</h2>
        
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        <?php if (isset($_SESSION['success'])) {
            echo "<div style='color: green; margin-bottom: 15px;'>" . $_SESSION['success'] . "</div>";
            unset($_SESSION['success']);
        } ?>
        
        <form method="POST">
            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit">تسجيل الدخول</button>
            
            <div class="links">
                <p>ليس لديك حساب؟ <a href="rigster.php">إنشاء حساب جديد</a></p>
                <p><a href="forgot-password.php">نسيت كلمة المرور؟</a></p>
            </div>
        </form>
    </div>
</body>
</html>