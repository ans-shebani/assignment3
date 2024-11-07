<?php
session_start();
include'../conn/conn.php'; // تأكد من أن لديك ملف اتصال بقاعدة البيانات

// التحقق مما إذا كان المدير قد سجل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// إضافة مستخدم جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $userType = $_POST['user_type'];

    $sql = "INSERT INTO Users (name, email, password, userType) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $password, $userType);
    $stmt->execute();
}

// عرض جميع المستخدمين
$sql = "SELECT * FROM Users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحكم الإدارة</title>
    <link rel="stylesheet" href="../public/assets/admin_style.css"> <!-- رابط ملف CSS -->
</head>
<body>
    <h1>تحكم الإدارة</h1>

    <h2>إضافة مستخدم جديد</h2>
    <form method="POST">
        <input type="text" name="name" placeholder="الاسم" required>
        <input type="email" name="email" placeholder="البريد الإلكتروني" required>
        <input type="password" name="password" placeholder="كلمة المرور" required>
        <select name="user_type" required>
            <option value="student">طالب</option>
            <option value="military">عسكري</option>
            <option value="teacher">معلم</option>
            <option value="the_elderly">كبير في السن</option>
        </select>
        <button type="submit" name="add_user">إضافة مستخدم</button>
    </form>

    <h2>قائمة المستخدمين</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>الاسم</th>
            <th>البريد الإلكتروني</th>
            <th>نوع المستخدم</th>
            <th>إجراءات</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['userID']; ?></td>
            <td><?php echo $row['name']; ?></td>
            <td><?php echo $row['email']; ?></td>
            <td><?php echo $row['userType']; ?></td>
            <td>
                <form method="POST" action="delete_user.php">
                    <input type="hidden" name="user_id" value="<?php echo $row['userID']; ?>">
                    <button type="submit">حذف</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <a href="logout.php">تسجيل الخروج</a>
</body>
</html>
