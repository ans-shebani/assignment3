<?php
include '../conn/conn.php';
class Discount {
    private $db;
    private $userType;
    private $price;
    private $ticketType; // إضافة نوع التذكرة (عادي/VIP)
    private $seasonalDiscount; // خصم موسمي إضافي
    
    // تحديث دالة البناء لتشمل المتغيرات الجديدة
    public function __construct($db, $userType, $price, $ticketType = 'Regular', $seasonalDiscount = 0) {
        $this->db = $db;
        $this->userType = $userType;
        $this->price = $price;
        $this->ticketType = $ticketType;
        $this->seasonalDiscount = $seasonalDiscount;
    }
    
    // دالة للتحقق من صلاحية نوع المستخدم
    private function isValidUserType($userType) {
        $validTypes = ['student', 'military', 'teacher', 'the_elderly'];
        return in_array($userType, $validTypes);
    }
    
    // دالة لتطبيق خصم إضافي للتذاكر VIP
    private function applyVIPDiscount($price) {
        if ($this->ticketType === 'VIP') {
            return $price * 0.95; // خصم إضافي 5% على تذاكر VIP
        }
        return $price;
    }
    
    // دالة محدثة لحساب السعر النهائي مع جميع الخصومات
    public function calculateFinalPrice() {
        if (!$this->isValidUserType($this->userType)) {
            throw new Exception("نوع المستخدم غير صالح");
        }
        
        $baseDiscount = $this->calculateDiscount();
        $priceAfterBaseDiscount = $this->price * (1 - $baseDiscount);
        $priceAfterVIP = $this->applyVIPDiscount($priceAfterBaseDiscount);
        $finalPrice = $priceAfterVIP * (1 - ($this->seasonalDiscount / 100));
        
        return round($finalPrice, 2); // تقريب السعر لأقرب رقمين عشريين
    }
    
    // دالة لعرض تفاصيل الخصم
    public function getDiscountDetails() {
        $baseDiscount = $this->getDiscountRate();
        $finalPrice = $this->calculateFinalPrice();
        $totalSaving = $this->price - $finalPrice;
        
        return [
            'original_price' => $this->price,
            'user_type' => $this->userType,
            'base_discount_rate' => $baseDiscount,
            'ticket_type' => $this->ticketType,
            'seasonal_discount' => $this->seasonalDiscount,
            'final_price' => $finalPrice,
            'total_saving' => round($totalSaving, 2)
        ];
    }
    
    // تحديث دالة حساب الخصم الأساسي
    private function calculateDiscount() {
        $discountRate = $this->getDiscountRate() / 100;
        return $discountRate;
    }
    
    // باقي الدوال تبقى كما هي
    public function getDiscountRate() {
        $query = "SELECT discountRate FROM Discounts WHERE category = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->userType]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['discountRate'];
        }
        
        $discountRates = [
            'student' => 20,
            'military' => 30,
            'teacher' => 15,
            'the_elderly' => 25
        ];
        
        return isset($discountRates[$this->userType]) ? $discountRates[$this->userType] : 0;
    }
}

?>