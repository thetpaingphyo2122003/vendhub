<?php
/** @var App\Model\Product $product */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
$image = product_image($product->slug);
$category = product_category($product);
$inStock = $product->quantityAvailable > 0;
$quantityValue = (string) ($old['quantity'] ?? '1');
/** @var array<string, mixed>|null $purchaseSuccess */
$success = is_array($purchaseSuccess ?? null) ? $purchaseSuccess : null;
$successImage = is_string($success['image'] ?? null) ? $success['image'] : $image;
$successQty = (int) ($success['quantity'] ?? 0);
$successTotal = (float) ($success['total'] ?? 0);
?>
<section class="shop-page product-detail-page">
    <a class="product-back" href="<?= e(url('/products')) ?>">
        <?= icon('arrow-left', 15) ?>
        Back to products
    </a>

    <article class="product-detail">
        <div class="product-detail-media">
            <?php if ($image !== null): ?>
                <img src="<?= e($image) ?>" alt="<?= e($product->name) ?>">
            <?php else: ?>
                <span class="product-fallback large"><?= e(mb_substr($product->name, 0, 1)) ?></span>
            <?php endif; ?>
        </div>

        <div class="product-detail-body">
            <header class="product-detail-head">
                <span class="category-pill"><?= e($category) ?></span>
                <h1><?= e($product->name) ?></h1>
                <p class="product-detail-price">$<?= e(money($product->price)) ?></p>
            </header>

            <ul class="product-detail-meta">
                <li>
                    <span><?= icon('box', 15) ?> Availability</span>
                    <strong class="shop-stock <?= $inStock ? 'is-in' : 'is-out' ?>">
                        <?= $inStock ? 'In Stock (' . e((string) $product->quantityAvailable) . ')' : 'Out of Stock' ?>
                    </strong>
                </li>
                <li>
                    <span><?= icon('tag', 15) ?> Category</span>
                    <strong><?= e($category) ?></strong>
                </li>
                <li>
                    <span><?= icon('coins', 15) ?> Unit price</span>
                    <strong>$<?= e(money($product->price)) ?></strong>
                </li>
            </ul>

            <div class="product-detail-actions">
                <?php if ($inStock && $authUser !== null): ?>
                    <form method="post" action="<?= e(url('/products/' . $product->slug . '/purchase')) ?>" class="product-detail-buy" data-validate="purchase" data-confirm="Confirm purchase of <?= e($product->name) ?>?" data-stock="<?= e((string) $product->quantityAvailable) ?>" novalidate>
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                        <label class="product-qty-label" for="detail-quantity">Quantity</label>
                        <div class="product-detail-buy-row">
                            <div class="qty-stepper" data-field="quantity">
                                <button type="button" class="qty-btn" data-qty="-1" aria-label="Decrease quantity">−</button>
                                <input id="detail-quantity" name="quantity" type="number" min="1" max="<?= e((string) $product->quantityAvailable) ?>" value="<?= e($quantityValue) ?>" inputmode="numeric">
                                <button type="button" class="qty-btn" data-qty="1" aria-label="Increase quantity">+</button>
                            </div>
                            <button type="submit" class="button button-small purchase-btn"><?= icon('cart', 15) ?> Purchase</button>
                        </div>
                        <?php if (!empty($errors['quantity'])): ?><p class="field-error"><?= e($errors['quantity']) ?></p><?php endif; ?>
                    </form>
                <?php elseif ($inStock): ?>
                    <a class="button button-small purchase-btn" href="<?= e(url('/login')) ?>"><?= icon('cart', 15) ?> Purchase</a>
                <?php else: ?>
                    <button type="button" class="button button-small purchase-btn" disabled><?= icon('ban', 15) ?> Unavailable</button>
                <?php endif; ?>
            </div>
        </div>
    </article>

    <?php if ($success !== null): ?>
        <?php
        $unitPrice = (float) ($success['unit_price'] ?? $product->price);
        $successName = (string) ($success['name'] ?? $product->name);
        ?>
        <div class="purchase-success" data-purchase-success role="dialog" aria-modal="true" aria-labelledby="purchase-success-title">
            <div class="purchase-success__backdrop" data-close-purchase-success></div>
            <div class="purchase-success__panel">
                <button type="button" class="purchase-success__close" data-close-purchase-success aria-label="Close"><?= icon('x', 14) ?></button>

                <div class="purchase-success__brand">
                    <img src="<?= e(brand_mark()) ?>" alt="VendHub">
                </div>

                <div class="purchase-success__hero" aria-hidden="true">
                    <span class="purchase-success__ray purchase-success__ray--1"></span>
                    <span class="purchase-success__ray purchase-success__ray--2"></span>
                    <span class="purchase-success__ray purchase-success__ray--3"></span>
                    <span class="purchase-success__ray purchase-success__ray--4"></span>
                    <span class="purchase-success__ray purchase-success__ray--5"></span>
                    <span class="purchase-success__ray purchase-success__ray--6"></span>
                    <div class="purchase-success__badge"><?= icon('check', 28) ?></div>
                </div>

                <h2 id="purchase-success-title">Purchase Successful!</h2>
                <p class="purchase-success__sub">Your purchase has been completed successfully.</p>

                <div class="purchase-success__card">
                    <div class="purchase-success__row">
                        <div class="purchase-success__media">
                            <?php if ($successImage !== null): ?>
                                <img src="<?= e((string) $successImage) ?>" alt="<?= e($successName) ?>">
                            <?php else: ?>
                                <span class="product-fallback"><?= e(mb_substr($successName, 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="purchase-success__info">
                            <strong><?= e($successName) ?></strong>
                            <span>$<?= e(money($unitPrice)) ?> × <?= e((string) $successQty) ?></span>
                        </div>
                        <strong class="purchase-success__line">$<?= e(money($successTotal)) ?></strong>
                    </div>

                    <div class="purchase-success__paid">
                        <span>Total Paid</span>
                        <strong>$<?= e(money($successTotal)) ?></strong>
                    </div>
                </div>

                <p class="purchase-success__note"><?= icon('check', 14) ?> Your transaction has been recorded.</p>

                <div class="purchase-success__actions">
                    <a class="button button-ghost button-small" href="<?= e(url('/purchases')) ?>"><?= icon('list', 15) ?> View My Purchases</a>
                    <a class="button button-small" href="<?= e(url('/products')) ?>"><?= icon('home', 15) ?> Back to Products</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
