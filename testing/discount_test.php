<?php
// كود للاختبار
function testDiscount() {
    try {
        // محاكاة اتصال قاعدة البيانات (يجب استبداله باتصال حقيقي)
        $mockDB = new PDO("mysql:host=localhost;dbname=btes", "username", "password");
        
        // اختبار حالات مختلفة
        $tests = [
            [
                'userType' => 'student',
                'price' => 100,
                'ticketType' => 'Regular',
                'seasonalDiscount' => 10
            ],
            [
                'userType' => 'military',
                'price' => 200,
                'ticketType' => 'VIP',
                'seasonalDiscount' => 5
            ]
        ];
        
        foreach ($tests as $test) {
            $discount = new Discount(
                $mockDB,
                $test['userType'],
                $test['price'],
                $test['ticketType'],
                $test['seasonalDiscount']
            );
            
            $details = $discount->getDiscountDetails();
            echo "=== نتائج الاختبار ===\n";
            echo "نوع المستخدم: " . $details['user_type'] . "\n";
            echo "السعر الأصلي: " . $details['original_price'] . " ريال\n";
            echo "نسبة الخصم الأساسي: " . $details['base_discount_rate'] . "%\n";
            echo "نوع التذكرة: " . $details['ticket_type'] . "\n";
            echo "الخصم الموسمي: " . $details['seasonal_discount'] . "%\n";
            echo "السعر النهائي: " . $details['final_price'] . " ريال\n";
            echo "إجمالي التوفير: " . $details['total_saving'] . " ريال\n";
            echo "========================\n";
        }
        
    } catch (Exception $e) {
        echo "خطأ: " . $e->getMessage() . "\n";
    }
}

// تشغيل الاختبار
// testDiscount();
?>