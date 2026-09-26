<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\Product;
use app\services\CacheService;

/**
 * Products API.
 */
class ProductController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionView(int $id): array
    {
        $cache = new CacheService(Yii::$app->cache);
        $key = $cache->productKey($id);

        $data = $cache->get($key);
        if ($data !== false) {
            return $data;
        }

        $product = Product::findOne($id);
        if ($product === null) {
            throw new NotFoundHttpException('Product not found.');
        }

        $data = $product->toArray();
        $cache->set($key, $data);

        return $data;
    }

    public function actionUpdate(int $id): array
    {
        $product = Product::findOne($id);
        if ($product === null) {
            throw new NotFoundHttpException('Product not found.');
        }

        $oldCategoryId = (int) $product->category_id;

        $product->load(Yii::$app->request->getBodyParams(), '');

        if (!$product->save()) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $product->getErrors()];
        }

        $newCategoryId = (int) $product->category_id;

        $cacheService = new CacheService(Yii::$app->cache);
        $cacheService->invalidateEntity(
            'product',
            $id,
            [
                'categoryIds' => array_values(
                    array_unique([
                        $oldCategoryId,
                        $newCategoryId,
                    ])
                ),
            ]
        );

        return $product->toArray();
    }
}
