<?php

declare(strict_types=1);

namespace app\services;

use Yii;
use yii\caching\CacheInterface;

class CacheService
{
    public function __construct(
        private readonly CacheInterface $cache
    ) {
    }

    public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }

    public function set(string $key, mixed $value, int $duration = 0): bool
    {
        return $this->cache->set($key, $value, $duration);
    }

    public function productKey(int $productId): string
    {
        return 'product:' . $productId;
    }

    public function categoryProductsKey(int $categoryId): string
    {
        return 'category:' . $categoryId . ':products';
    }

    public function invalidateEntity(
        string $entityType,
        int $entityId,
        array $context = []
    ): void {
        switch ($entityType) {
            case 'product':
                $this->cache->delete($this->productKey($entityId));

                foreach ($context['categoryIds'] ?? [] as $categoryId) {
                    $this->cache->delete(
                        $this->categoryProductsKey((int) $categoryId)
                    );
                }

                break;

            default:
                throw new \InvalidArgumentException(
                    'Unsupported cache entity: ' . $entityType
                );
        }
    }
}
