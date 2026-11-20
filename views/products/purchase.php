<?php
/** @var App\Model\Product $product */
$quantity = $old['quantity'] ?? '1';
$image = product_image($product->slug);
?>
<section class="detail-panel with-art">
    <div class="detail-art">
        <?php if ($image !== null): ?>
            <img src="<?= e($image) ?>" alt="<?= e($product->name) ?>">
        <?php else: ?>
            <span class="product-fallback large"><?= e(mb_substr($product->name, 0, 1)) ?></span>
        <?php endif; ?>
    </div>
    <div>
        <p class="eyebrow">Checkout</p>
        <h1>Buy <?= e($product->name) ?></h1>
        <dl class="details">
            <div><dt>Price</dt><dd><?= e(money($product->price)) ?> USD</dd></div>
            <div><dt>Available</dt><dd><?= e((string) $product->quantityAvailable) ?></dd></div>
        </dl>
        <?php if ($product->quantityAvailable < 1): ?>
            <p class="muted">This product is out of stock.</p>
        <?php else: ?>
            <form method="post" action="<?= e(url('/products/' . $product->slug . '/purchase')) ?>" data-validate="purchase" data-confirm="Confirm purchase of <?= e($product->name) ?>?" data-stock="<?= e((string) $product->quantityAvailable) ?>" class="stack-form" novalidate>
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <div class="field" data-field="quantity">
                    <label for="quantity">Quantity</label>
                    <input id="quantity" name="quantity" value="<?= e((string) $quantity) ?>" required inputmode="numeric" min="1" max="<?= e((string) $product->quantityAvailable) ?>">
                    <?php if (!empty($errors['quantity'])): ?><p class="field-error"><?= e($errors['quantity']) ?></p><?php endif; ?>
                </div>
                <div class="form-actions">
                    <button type="submit"><?= icon('cart', 16) ?> Confirm purchase</button>
                    <a class="text-link" href="<?= e(url('/products/' . $product->slug)) ?>">Back</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>
