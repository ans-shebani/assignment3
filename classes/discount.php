<?php
// تعريف فئة الخصم
class Discount {
    private $db;         // متغير لتخزين كائن قاعدة البيانات
    private $userType;   // نوع المستخدم (مثل طالب، عسكري، معلم، كبار السن)
    private $price;      // السعر الأساسي قبل تطبيق الخصم
    
    // دالة البناء: تهيئة المتغيرات عند إنشاء كائن جديد من الفئة
    public function __construct($db, $userType, $price) {
        $this->db = $db;
        $this->userType = $userType;
        $this->price = $price;
    }
    
    // دالة لحساب السعر بعد تطبيق الخصم
    public function calculateDiscount() {
        // استعلام للحصول على معدل الخصم من قاعدة البيانات بناءً على نوع المستخدم
        $query = "SELECT discountRate FROM Discounts WHERE category = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->userType]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // تحويل معدل الخصم من نسبة مئوية إلى عدد عشري
            $discountRate = $result['discountRate'] / 100; 
        } else {
            // معدلات خصم افتراضية إذا لم يتم العثور على النوع في قاعدة البيانات
            $discountRates = [
                'student' => 0.20,       // 20% خصم للطلاب
                'military' => 0.30,      // 30% خصم للعسكريين
                'teacher' => 0.15,       // 15% خصم للمعلمين
                'the_elderly' => 0.25    // 25% خصم لكبار السن
            ];
            // التحقق من وجود معدل خصم افتراضي لنوع المستخدم المحدد
            $discountRate = isset($discountRates[$this->userType]) ? $discountRates[$this->userType] : 0;
        }
        
        // حساب السعر بعد تطبيق الخصم
        return $this->price * (1 - $discountRate);
    }
    
    // دالة لاسترجاع معدل الخصم فقط
    public function getDiscountRate() {
        // استعلام لاسترجاع معدل الخصم من قاعدة البيانات
        $query = "SELECT discountRate FROM Discounts WHERE category = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->userType]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['discountRate'];  // إرجاع معدل الخصم من قاعدة البيانات
        }
        
        // معدلات خصم افتراضية إذا لم يتم العثور على النوع في قاعدة البيانات
        $discountRates = [
            'student' => 20,       // 20% للطلاب
            'military' => 30,      // 30% للعسكريين
            'teacher' => 15,       // 15% للمعلمين
            'the_elderly' => 25    // 25% لكبار السن
        ];
        
        // إرجاع معدل الخصم الافتراضي إذا لم يتم العثور عليه في قاعدة البيانات
        return isset($discountRates[$this->userType]) ? $discountRates[$this->userType] : 0;
    }
}
?>