<?php

declare(strict_types=1);

namespace app\tests;

use Yii;
use app\models\Product;
use app\services\CacheService;
use PHPUnit\Framework\TestCase;

class CacheBugTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure cache is empty before test
        Yii::$app->cache->flush();
    }

    public function testUpdatingProductInvalidatesCategoryCache(): void
    {
        $cacheService = new CacheService(Yii::$app->cache);
        
        // 1. Simulate CategoryController fetching the list and caching it
        $categoryKey = $cacheService->categoryProductsKey(1);
        $initialCategoryProducts = [
            ['id' => 1, 'name' => 'Airbus H125', 'category_id' => 1, 'price' => 45000]
        ];
        $cacheService->set($categoryKey, $initialCategoryProducts);

        // Assert the cache holds the old data
        $this->assertEquals(45000, $cacheService->get($categoryKey)[0]['price']);

        // 2. Simulate ProductController updating the product
        // In a real functional test, we would hit the controller endpoint. 
        // Here we test the CacheService abstraction directly.
        $newPrice = 47000;
        
        // Product is saved to DB (mocked here), then invalidateEntity is called
        $cacheService->invalidateEntity('product', 1, [
            'categoryIds' => [1] // product remains in category 1
        ]);

        // 3. Assert the category cache has been cleared
        $this->assertFalse(
            $cacheService->get($categoryKey),
            'The category cache should have been invalidated and return false on cache miss.'
        );
    }
}
