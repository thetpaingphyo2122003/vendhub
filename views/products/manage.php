<?php
/** @var App\Model\PageResult $pageResult */
/** @var App\Model\ProductQuery $query */
/** @var int $inStockCount */
/** @var int $lowStockCount */
/** @var int $outStockCount */

$from = $pageResult->total === 0 ? 0 : (($pageResult->page - 1) * $pageResult->perPage) + 1;
$to = min($pageResult->total, $pageResult->page * $pageResult->perPage);

$sortLink = static function (string $column) use ($query): string {
    $dir = ($query->sort === $column && $query->direction === 'asc') ? 'desc' : 'asc';

    return url('/admin/products') . '?' . http_build_query(array_filter([
        'sort' => $column,
        'dir' => $dir,
        'per_page' => $query->perPage,
        'q' => $query->search !== '' ? $query->search : null,
        'category' => $query->category !== '' ? $query->category : null,
    ]));
};

$sortIcon = static function (string $column) use ($query): string {
    if ($query->sort !== $column) {
        return '<span class="admin-sort-ico is-idle" aria-hidden="true">' . icon('chevron-down', 12) . '</span>';
    }

    $iconName = $query->direction === 'asc' ? 'chevron-up' : 'chevron-down';

    return '<span class="admin-sort-ico is-active" aria-hidden="true">' . icon($iconName, 12) . '</span>';
};

$exportHref = url('/admin/products/export') . '?' . http_build_query(array_filter([
    'sort' => $query->sort,
    'dir' => $query->direction,
    'q' => $query->search !== '' ? $query->search : null,
    'category' => $query->category !== '' ? $query->category : null,
]));

/** @var list<App\Model\Category> $categories */
$categories = $categories ?? [];
?>
<section class="admin-products">
    <div class="admin-products-stats">
        <article class="dash-stat dash-stat--mint">
            <div>
                <p>Total Products</p>
                <strong><?= e((string) $pageResult->total) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('box', 18) ?></span>
        </article>
        <article class="dash-stat dash-stat--sky">
            <div>
                <p>In Stock</p>
                <strong><?= e((string) $inStockCount) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('check', 18) ?></span>
        </article>
        <article class="dash-stat dash-stat--peach">
            <div>
                <p>Low Stock</p>
                <strong><?= e((string) $lowStockCount) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('bell', 18) ?></span>
        </article>
        <article class="dash-stat dash-stat--lilac">
            <div>
                <p>Out of Stock</p>
                <strong><?= e((string) $outStockCount) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('ban', 18) ?></span>
        </article>
    </div>

    <div class="admin-products-card">
        <div class="admin-products-toolbar">
            <form class="admin-products-filters" method="get" action="<?= e(url('/admin/products')) ?>">
                <label class="admin-products-search">
                    <span class="sr-only">Search products</span>
                    <span aria-hidden="true"><?= icon('search', 15) ?></span>
                    <input type="search" name="q" value="<?= e($query->search) ?>" placeholder="Search products...">
                </label>

                <input type="hidden" name="sort" value="<?= e($query->sort) ?>">
                <input type="hidden" name="dir" value="<?= e($query->direction) ?>">

                <label>
                    <span class="sr-only">Category</span>
                    <select name="category" data-autosubmit aria-label="Filter by category">
                        <option value="" <?= $query->category === '' ? 'selected' : '' ?>>All categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e($category->name) ?>" <?= $query->category === $category->name ? 'selected' : '' ?>>
                                <?= e($category->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span class="sr-only">Per page</span>
                    <select name="per_page" data-autosubmit>
                        <?php foreach (App\Model\ProductQuery::PER_PAGE_OPTIONS as $option): ?>
                            <option value="<?= e((string) $option) ?>" <?= $query->perPage === $option ? 'selected' : '' ?>><?= e((string) $option) ?> / page</option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>

            <div class="admin-products-actions">
                <form class="admin-import-form" method="post" action="<?= e(url('/admin/products/import')) ?>" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <input type="file" name="csv" id="product-import-csv" accept=".csv,text/csv" hidden>
                    <button type="button" class="button button-ghost button-small" data-import-trigger>
                        <?= icon('upload', 15) ?> Import
                    </button>
                </form>
                <a class="button button-ghost button-small" href="<?= e($exportHref) ?>">
                    <?= icon('download', 15) ?> Export
                </a>
                <a class="button button-small" href="<?= e(url('/admin/products/create')) ?>"><?= icon('plus', 15) ?> Add Product</a>
            </div>
        </div>

        <?php if ($pageResult->items === []): ?>
            <div class="admin-products-empty">
                <p>No products found.</p>
                <a class="button button-small" href="<?= e(url('/admin/products/create')) ?>"><?= icon('plus', 15) ?> Add Product</a>
            </div>
        <?php else: ?>
            <div class="admin-products-table-wrap">
                <table class="admin-products-table">
                    <thead>
                        <tr>
                            <th>
                                <a class="admin-sort-link <?= $query->sort === 'name' ? 'is-active' : '' ?>" href="<?= e($sortLink('name')) ?>">
                                    Product <?= $sortIcon('name') ?>
                                </a>
                            </th>
                            <th>
                                <a class="admin-sort-link <?= $query->sort === 'category' ? 'is-active' : '' ?>" href="<?= e($sortLink('category')) ?>">
                                    Category <?= $sortIcon('category') ?>
                                </a>
                            </th>
                            <th>
                                <a class="admin-sort-link <?= $query->sort === 'price' ? 'is-active' : '' ?>" href="<?= e($sortLink('price')) ?>">
                                    Price <?= $sortIcon('price') ?>
                                </a>
                            </th>
                            <th>
                                <a class="admin-sort-link <?= $query->sort === 'quantity_available' ? 'is-active' : '' ?>" href="<?= e($sortLink('quantity_available')) ?>">
                                    Stock <?= $sortIcon('quantity_available') ?>
                                </a>
                            </th>
                            <th>
                                <a class="admin-sort-link <?= $query->sort === 'status' ? 'is-active' : '' ?>" href="<?= e($sortLink('status')) ?>">
                                    Status <?= $sortIcon('status') ?>
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pageResult->items as $product): ?>
                            <?php
                            $image = product_image($product->slug);
                            $category = product_category($product);
                            $qty = (int) $product->quantityAvailable;
                            $status = $product->stockStatus();
                            $statusLabel = $status === 'out' ? 'Out of stock' : ($status === 'low' ? 'Low stock' : 'In stock');
                            ?>
                            <tr>
                                <td>
                                    <div class="admin-product-cell">
                                        <?php if ($image !== null): ?>
                                            <img src="<?= e($image) ?>" alt="">
                                        <?php else: ?>
                                            <span class="admin-product-fallback"><?= e(mb_strtoupper(mb_substr($product->name, 0, 1))) ?></span>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= e($product->name) ?></strong>
                                            <span><?= e($product->slug) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="admin-cat-pill"><?= e($category) ?></span></td>
                                <td class="admin-price">$<?= e(money($product->price)) ?></td>
                                <td><?= e((string) $qty) ?></td>
                                <td><span class="admin-stock-pill is-<?= e($status) ?>"><?= e($statusLabel) ?></span></td>
                                <td>
                                    <div class="admin-row-actions">
                                        <button
                                            type="button"
                                            class="admin-icon-btn is-restock"
                                            title="Add stock"
                                            aria-label="Add stock to <?= e($product->name) ?>"
                                            data-restock-open
                                            data-product-name="<?= e($product->name) ?>"
                                            data-product-stock="<?= e((string) $qty) ?>"
                                            data-product-image="<?= e((string) ($image ?? '')) ?>"
                                            data-restock-action="<?= e(url('/admin/products/' . $product->slug . '/restock')) ?>"
                                        >
                                            <span aria-hidden="true"><?= icon('plus', 15) ?></span>
                                        </button>
                                        <a class="admin-icon-btn" href="<?= e(url('/admin/products/' . $product->slug . '/edit')) ?>" title="Edit" aria-label="Edit <?= e($product->name) ?>">
                                            <?= icon('edit', 15) ?>
                                        </a>
                                        <form method="post" action="<?= e(url('/admin/products/' . $product->slug . '/delete')) ?>" data-confirm="Delete this product?">
                                            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                            <button type="submit" class="admin-icon-btn is-danger" title="Delete" aria-label="Delete <?= e($product->name) ?>">
                                                <?= icon('trash', 15) ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <footer class="admin-products-footer">
                <p>Showing <?= e((string) $from) ?> – <?= e((string) $to) ?> of <?= e((string) $pageResult->total) ?> products</p>
                <?php $partial('partials/pagination', ['basePath' => '/admin/products']); ?>
            </footer>
        <?php endif; ?>
    </div>
</section>

<div class="admin-restock-dialog" data-restock-dialog hidden>
    <div class="admin-restock-dialog__card" role="dialog" aria-modal="true" aria-labelledby="restock-title">
        <button type="button" class="admin-restock-dialog__close" data-restock-close aria-label="Close"><?= icon('x', 16) ?></button>

        <div class="admin-restock-dialog__hero">
            <div class="admin-restock-dialog__avatar" data-restock-avatar aria-hidden="true">+</div>
            <div>
                <p class="admin-restock-dialog__eyebrow">Restock</p>
                <h2 id="restock-title" data-restock-title>Product</h2>
            </div>
        </div>

        <div class="admin-restock-dialog__stats">
            <div>
                <span>Current</span>
                <strong data-restock-current>0</strong>
            </div>
            <div class="admin-restock-dialog__arrow" aria-hidden="true">→</div>
            <div>
                <span>After add</span>
                <strong data-restock-next>10</strong>
            </div>
        </div>

        <form method="post" action="#" data-restock-form>
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <label class="admin-restock-dialog__label" for="restock-amount">Amount to add</label>
            <div class="admin-restock-stepper">
                <button type="button" data-restock-step="-1" aria-label="Decrease">−</button>
                <input id="restock-amount" type="number" name="amount" min="1" max="10000" step="1" value="10" required inputmode="numeric">
                <button type="button" data-restock-step="1" aria-label="Increase">+</button>
            </div>
            <div class="admin-restock-dialog__actions">
                <button type="button" class="admin-restock-btn is-ghost" data-restock-close>Cancel</button>
                <button type="submit" class="admin-restock-btn is-primary"><?= icon('plus', 15) ?> Add stock</button>
            </div>
        </form>
    </div>
</div>

<script nonce="<?= e(csp_nonce()) ?>">
(() => {
    const trigger = document.querySelector('[data-import-trigger]');
    const input = document.getElementById('product-import-csv');
    const importForm = document.querySelector('.admin-import-form');
    if (trigger && input && importForm) {
        trigger.addEventListener('click', () => input.click());
        input.addEventListener('change', () => {
            if (input.files && input.files.length > 0) importForm.submit();
        });
    }

    const dialog = document.querySelector('[data-restock-dialog]');
    if (!dialog) return;

    const restockForm = dialog.querySelector('[data-restock-form]');
    const nameEl = dialog.querySelector('[data-restock-title]');
    const currentEl = dialog.querySelector('[data-restock-current]');
    const nextEl = dialog.querySelector('[data-restock-next]');
    const avatar = dialog.querySelector('[data-restock-avatar]');
    const amountInput = dialog.querySelector('#restock-amount');
    if (!restockForm || !nameEl || !currentEl || !nextEl || !avatar || !amountInput) return;

    let currentStock = 0;

    const syncPreview = () => {
        const add = Math.max(0, Number(amountInput.value || 0));
        nextEl.textContent = String(currentStock + add);
    };

    const open = (button) => {
        restockForm.action = button.getAttribute('data-restock-action') || '#';
        const name = button.getAttribute('data-product-name') || 'Product';
        currentStock = Number(button.getAttribute('data-product-stock') || '0');
        const image = button.getAttribute('data-product-image') || '';
        nameEl.textContent = name;
        currentEl.textContent = String(currentStock);
        amountInput.value = '10';
        syncPreview();

        if (image) {
            avatar.innerHTML = '<img src="' + image + '" alt="">';
            avatar.classList.add('has-image');
        } else {
            avatar.textContent = (name.trim().charAt(0) || '+').toUpperCase();
            avatar.classList.remove('has-image');
        }

        dialog.hidden = false;
        document.body.classList.add('ui-confirm-open');
        amountInput.focus();
        amountInput.select();
    };

    const close = () => {
        dialog.hidden = true;
        document.body.classList.remove('ui-confirm-open');
    };

    document.querySelectorAll('[data-restock-open]').forEach((button) => {
        button.addEventListener('click', () => open(button));
    });

    dialog.querySelectorAll('[data-restock-step]').forEach((button) => {
        button.addEventListener('click', () => {
            const step = Number(button.getAttribute('data-restock-step') || '0');
            const next = Math.min(10000, Math.max(1, Number(amountInput.value || 0) + step));
            amountInput.value = String(next);
            syncPreview();
        });
    });

    amountInput.addEventListener('input', syncPreview);

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog || event.target.closest('[data-restock-close]')) close();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !dialog.hidden) close();
    });
})();
</script>
