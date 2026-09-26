Assignment 1 Decisions

1. Reproducing the Bug

I started by reproducing the cache invalidation bug exactly as described. 
Here's how to see it in action:

1. Start the app: `php yii serve --docroot=web`
2. Hit the category endpoint first to populate the cache: 
   `GET http://localhost:8080/categories/1/products`
   (This caches the product with the old price of 45000.00)
3. Update the product directly via the product endpoint:
   `curl -X PUT http://localhost:8080/products/1 -H "Content-Type: application/json" -d '{"name": "Airbus H125 Updated", "price": 47000}'`
4. If you hit `GET http://localhost:8080/products/1`, you'll see the new price (47000). 
5. But if you hit the category endpoint again (`GET http://localhost:8080/categories/1/products`), it still returns 45000.00.

The root issue was that updating a product correctly cleared the `product:{id}` cache, but left the `category:1:products` collection completely untouched.

2. My Architecture Fix

I wanted to fix this without cluttering the controllers with caching logic. 

I created a `CacheService` with an `invalidateEntity` method. Now, `ProductController` and `CategoryController` don't care about how cache keys are built or deleted. They just tell the service "hey, a product was updated."

One important edge case I made sure to handle: if someone changes a product's category from Category 1 to Category 2, we actually need to invalidate *both* the old and the new category caches. You'll see in my `ProductController` that I track `$oldCategoryId` before the save, and pass both IDs to the invalidation service.

3. Why I rejected the 24-hour TTL idea

The PM suggested just setting a 24-hour expiry on everything. I decided against this because it doesn't actually solve the stale data problem—it just limits how long the data stays broken. 

If we rely on TTL, a customer could book a helicopter based on a price that was updated 23 hours ago. Since we know exactly when a product changes (during the PUT request), event-driven invalidation makes much more sense. We get the performance of the cache, but with 100% data accuracy.

4. What I Deliberately Skipped

Redis/Memcached: The instructions said stick to Yii FileCache, so I kept it simple.

A massive event bus: I could have built an async queue to clear caches in the background, but for a small CRUD app, running synchronous deletes on save is way faster to build and perfectly fine for performance.

Heavy unit testing: Given the strict timebox, I prioritized fixing the core correctness issue and manual testing over scaffolding PHPUnit.
