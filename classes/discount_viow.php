<?php
// session_start();
include '../conn/conn.php';
require_once 'discount.php';

// if (!isset($_SESSION['admin_id'])) {
//     header('Location: login.php');
//     exit();
// }

$discount = new Discount($db, 'student', 100);

$query = "SELECT * FROM discounts ORDER BY created_at DESC";
$stmt = $db->query($query);
$discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// معالجة النموذج عند إرساله
$calculationResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate'])) {
    $price = floatval($_POST['price']);
    $userType = $_POST['userType'];
    $ticketType = $_POST['ticketType'];
    $seasonalDiscount = floatval($_POST['seasonalDiscount']);

    try {
        $discount = new Discount($db, $userType, $price, $ticketType, $seasonalDiscount);
        $calculationResult = $discount->getDiscountDetails();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// تحديث نسبة الخصم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_discount'])) {
    $userType = $_POST['edit_user_type'];
    $newRate = $_POST['new_discount_rate'];
    
    $updateQuery = "UPDATE discounts SET discountRate = ? WHERE category = ?";
    $stmt = $db->prepare($updateQuery);
    $stmt->execute([$newRate, $userType]);
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الخصومات</title>
    <style>
        /* CSS styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .dashboard {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .calculator-box {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
        }
        .btn-calculate {
            background-color: #2980b9;
        }
        .btn-edit {
            background-color: #27ae60;
        }
        .discounts-table {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        .result-box {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1>لوحة تحكم الخصومات</h1>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>إجمالي الخصومات النشطة</h3>
                <div class="value"><?php echo count($discounts); ?></div>
            </div>
            <div class="stat-card">
                <h3>متوسط نسبة الخصم</h3>
                <div class="value">
                    <?php 
                    $totalDiscounts = 0;
                    foreach ($discounts as $d) {
                        $discountObj = new Discount($db, $d['user_type'], 100);
                        $totalDiscounts += $discountObj->getDiscountRate();
                    }
                    echo number_format($totalDiscounts / count($discounts), 1) . '%';
                    ?>
                </div>
            </div>
        </div>

        <div class="calculator-box">
            <h2>حاسبة الخصم</h2>
            <form method="POST">
                <div class="form-group">
                    <label>السعر الأصلي</label>
                    <input type="number" name="price" required>
                </div>
                <div class="form-group">
                    <label>نوع المستخدم</label>
                    <select name="userType">
                        <option value="student">طالب</option>
                        <option value="military">عسكري</option>
                        <option value="teacher">معلم</option>
                        <option value="the_elderly">كبار السن</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>نوع التذكرة</label>
                    <select name="ticketType">
                        <option value="Regular">عادي</option>
                        <option value="VIP">VIP</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>خصم موسمي إضافي (%)</label>
                    <input type="number" name="seasonalDiscount" min="0" max="100" value="0">
                </div>
                <button type="submit" name="calculate" class="btn btn-calculate">حساب الخصم</button>
            </form>

            <?php if ($calculationResult): ?>
            <div class="result-box">
                <p>السعر الأصلي: <?php echo $calculationResult['original_price']; ?> دينار</p>
                <p>نسبة الخصم الأساسية: <?php echo $calculationResult['base_discount_rate']; ?>%</p>
                <p>السعر النهائي: <?php echo $calculationResult['final_price']; ?> دينار</p>
                <p>إجمالي التوفير: <?php echo $calculationResult['total_saving']; ?> دينار</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="discounts-table">
            <table>
                <thead>
                    <tr>
                        <th>نوع المستخدم</th>
                        <th>نسبة الخصم الأساسية</th>
                        <th>نوع التذكرة</th>
                        <th>مثال للسعر</th>
                        <th>السعر بعد الخصم</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $userTypes = ['student', 'military', 'teacher', 'the_elderly'];
                    foreach ($userTypes as $type): 
                        $discountObj = new Discount($db, $type, 100);
                        $details = $discountObj->getDiscountDetails();
                    ?>
                        <tr>
                            <td><?php echo ucfirst($type); ?></td>
                            <td><?php echo $discountObj->getDiscountRate() . '%'; ?></td>
                            <td>عادي</td>
                            <td>100 دينار</td>
                            <td><?php echo $details['final_price'] . ' دينار'; ?></td>
                            <td class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="edit_user_type" value="<?php echo $type; ?>">
                                    <input type="number" name="new_discount_rate" 
                                           style="width: 70px;" min="0" max="100" 
                                           value="<?php echo $discountObj->getDiscountRate(); ?>">
                                    <button type="submit" name="update_discount" class="btn btn-edit">تحديث</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>