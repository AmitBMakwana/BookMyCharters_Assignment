<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\Category;
use app\models\Product;
use app\services\CacheService;

/**
 * Categories API.
 */
class CategoryController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionProducts(int $id): array
    {
        $category = Category::findOne($id);
        if ($category === null) {
            throw new NotFoundHttpException('Category not found.');
        }

        $cacheService = new CacheService(Yii::$app->cache);
        $key = $cacheService->categoryProductsKey($id);

        $data = $cacheService->get($key);
        if ($data !== false) {
            return $data;
        }

        $products = Product::find()
            ->where(['category_id' => $id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $data = array_map(static fn(Product $p): array => $p->toArray(), $products);
        $cacheService->set($key, $data);

        return $data;
    }
}
