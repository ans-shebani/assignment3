<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ .'/../classes/Review.php';
require_once __DIR__ .'/../conn/conn.php';

class ReviewTest extends TestCase
{
    private $review;
    private $dbMock;

    protected function setUp(): void
    {
        // إنشاء mock لقاعدة البيانات
        $this->dbMock = $this->createMock(PDO::class);
        $this->review = new Review($this->dbMock);
    }

    // اختبارات دالة getReviewStats

    public function testGetReviewStatsReturnsCorrectData()
    {
        // تجهيز البيانات المتوقعة
        $expectedReviews = [
            [
                'reviewID' => 1,
                'rating' => 5,
                'comment' => 'تعليق رائع',
                'createdAt' => '2024-01-01',
                'username' => 'أحمد',
                'userID' => 1
            ]
        ];

        // إنشاء mock للـ PDOStatement
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')
            ->willReturnOnConsecutiveCalls($expectedReviews[0], false);
        
        // تكوين mock لقاعدة البيانات
        $this->dbMock->method('prepare')
            ->willReturn($stmtMock);
        
        // تنفيذ الاختبار
        $result = $this->review->getReviewStats(1);
        
        $this->assertEquals($expectedReviews, $result);
    }

    // اختبارات دالة addReview

    public function testAddReviewSuccessful()
    {
        $reviewData = [
            'eventID' => 1,
            'userID' => 1,
            'rating' => 5,
            'comment' => 'تعليق جيد'
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')
            ->willReturn(true);

        $this->dbMock->method('prepare')
            ->willReturn($stmtMock);

        $result = $this->review->addReview($reviewData);
        $this->assertTrue($result);
    }

    public function testAddReviewWithMissingUserID()
    {
        $reviewData = [
            'eventID' => 1,
            'rating' => 5,
            'comment' => 'تعليق'
        ];

        $result = $this->review->addReview($reviewData);
        $this->assertFalse($result);
    }

    // اختبارات دالة deleteReview

    public function testDeleteReviewSuccessful()
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('rowCount')
            ->willReturn(1);
        $stmtMock->method('execute')
            ->willReturn(true);

        $this->dbMock->method('prepare')
            ->willReturn($stmtMock);

        $result = $this->review->deleteReview(1, 1);
        $this->assertTrue($result);
    }

    public function testDeleteNonExistentReview()
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('rowCount')
            ->willReturn(0);

        $this->dbMock->method('prepare')
            ->willReturn($stmtMock);

        $result = $this->review->deleteReview(999, 1);
        $this->assertFalse($result);
    }

    // اختبارات دالة updateReview

    public function testUpdateReviewSuccessful()
    {
        $reviewData = [
            'userID' => 1,
            'rating' => 4,
            'comment' => 'تعليق محدث'
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('rowCount')
            ->willReturn(1);
        $stmtMock->method('execute')
            ->willReturn(true);

        $this->dbMock->method('prepare')
            ->willReturn($stmtMock);

        $result = $this->review->updateReview(1, $reviewData);
        $this->assertTrue($result);
    }

    public function testUpdateNonExistentReview()
    {
        $reviewData = [
            'userID' => 1,
            'rating' => 4,
            'comment' => 'تعليق محدث'
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('rowCount')
            ->willReturn(0);

        $this->dbMock->method('prepare')
            ->willReturn($stmtMock);

        $result = $this->review->updateReview(999, $reviewData);
        $this->assertFalse($result);
    }

    public function testUpdateReviewWithDatabaseError()
    {
        $reviewData = [
            'userID' => 1,
            'rating' => 4,
            'comment' => 'تعليق محدث'
        ];

        $this->dbMock->method('prepare')
            ->willThrowException(new PDOException('خطأ في قاعدة البيانات'));

        $result = $this->review->updateReview(1, $reviewData);
        $this->assertFalse($result);
    }
}