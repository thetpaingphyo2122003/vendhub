<?php
/** @var App\Model\PageResult $pageResult */
/** @var App\Model\ProductQuery $query */
/** @var int $availableCount */
/** @var list<App\Model\Category> $categories */

$from = $pageResult->total === 0 ? 0 : (($pageResult->page - 1) * $pageResult->perPage) + 1;
$to = min($pageResult->total, $pageResult->page * $pageResult->perPage);
$availableCount = (int) ($availableCount ?? 0);
$categories = $categories ?? [];
?>
<section class="shop-page">
    <div class="shop-sticky">
        <header class="shop-heading">
            <div>
                <h1>Products</h1>
                <p><?= e((string) $availableCount) ?> product<?= $availableCount === 1 ? '' : 's' ?> available</p>
            </div>
        </header>

        <form class="shop-toolbar" method="get" action="<?= e(url('/products')) ?>">
            <label class="shop-search">
                <span class="sr-only">Search products</span>
                <span class="search-ico" aria-hidden="true"><?= icon('search', 16) ?></span>
                <input type="search" name="q" value="<?= e($query->search) ?>" placeholder="Search products...">
            </label>
            <div class="shop-filters">
                <label class="shop-filter">
                    <span class="shop-filter__label">Category</span>
                    <select name="category" data-autosubmit aria-label="Filter by category">
                        <option value="" <?= $query->category === '' ? 'selected' : '' ?>>All</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e($category->name) ?>" <?= $query->category === $category->name ? 'selected' : '' ?>>
                                <?= e($category->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="shop-filter">
                    <span class="shop-filter__label">Sort</span>
                    <select name="sort" data-autosubmit aria-label="Sort by">
                        <option value="name" <?= $query->sort === 'name' ? 'selected' : '' ?>>Name</option>
                        <option value="price" <?= $query->sort === 'price' ? 'selected' : '' ?>>Price</option>
                        <option value="quantity_available" <?= $query->sort === 'quantity_available' ? 'selected' : '' ?>>Stock</option>
                    </select>
                </label>
                <input type="hidden" name="dir" value="<?= e($query->direction) ?>">
                <label class="shop-filter">
                    <span class="shop-filter__label">Show</span>
                    <select name="per_page" data-autosubmit aria-label="Show per page">
                        <?php foreach (App\Model\ProductQuery::PER_PAGE_OPTIONS as $option): ?>
                            <option value="<?= e((string) $option) ?>" <?= $query->perPage === $option ? 'selected' : '' ?>><?= e((string) $option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </form>
    </div>

    <?php if ($pageResult->items === []): ?>
        <p class="empty">No products matched your search.</p>
    <?php else: ?>
        <div class="shop-grid">
            <?php foreach ($pageResult->items as $product): ?>
                <?php
                $image = product_image($product->slug);
                $category = product_category($product);
                $inStock = $product->quantityAvailable > 0;
                ?>
                <article class="shop-card">
                    <div class="shop-card-media">
                        <?php if ($image !== null): ?>
                            <img src="<?= e($image) ?>" alt="<?= e($product->name) ?>" loading="lazy">
                        <?php else: ?>
                            <span class="product-fallback"><?= e(mb_substr($product->name, 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="shop-card-body">
                        <div class="shop-card-title">
                            <h2><a href="<?= e(url('/products/' . $product->slug)) ?>"><?= e($product->name) ?></a></h2>
                            <span class="category-pill"><?= e($category) ?></span>
                        </div>
                        <p class="shop-price">$<?= e(money($product->price)) ?></p>
                        <p class="shop-stock <?= $inStock ? 'is-in' : 'is-out' ?>">
                            <?= $inStock ? 'In Stock (' . e((string) $product->quantityAvailable) . ')' : 'Out of Stock' ?>
                        </p>

                        <?php if ($inStock && $authUser !== null): ?>
                            <form method="post" action="<?= e(url('/products/' . $product->slug . '/purchase')) ?>" class="shop-buy" data-validate="purchase" data-confirm="Confirm purchase of <?= e($product->name) ?>?" data-stock="<?= e((string) $product->quantityAvailable) ?>" novalidate>
                                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                <div class="qty-stepper" data-field="quantity">
                                    <button type="button" class="qty-btn" data-qty="-1" aria-label="Decrease quantity">−</button>
                                    <input name="quantity" type="number" min="1" max="<?= e((string) $product->quantityAvailable) ?>" value="1" inputmode="numeric">
                                    <button type="button" class="qty-btn" data-qty="1" aria-label="Increase quantity">+</button>
                                </div>
                                <button type="submit" class="button purchase-btn"><?= icon('cart', 15) ?> Purchase</button>
                            </form>
                        <?php elseif ($inStock): ?>
                            <a class="button purchase-btn" href="<?= e(url('/login')) ?>"><?= icon('cart', 15) ?> Purchase</a>
                        <?php else: ?>
                            <button type="button" class="button purchase-btn" disabled><?= icon('ban', 15) ?> Unavailable</button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <footer class="shop-footer">
            <p>Showing <?= e((string) $from) ?> – <?= e((string) $to) ?> of <?= e((string) $pageResult->total) ?> products</p>
            <?php $partial('partials/pagination', ['basePath' => '/products']); ?>
        </footer>
    <?php endif; ?>
</section>
