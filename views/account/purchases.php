<?php
/** @var list<App\Model\Transaction> $transactions */

$totalPurchases = count($transactions);
$totalItems = 0;
$totalSpent = 0.0;
$monthSpent = 0.0;
$monthKey = date('Y-m');

foreach ($transactions as $transaction) {
    $totalItems += (int) $transaction->quantity;
    $totalSpent += (float) $transaction->total;
    if ($transaction->createdAt !== null && str_starts_with((string) $transaction->createdAt, $monthKey)) {
        $monthSpent += (float) $transaction->total;
    }
}

$perPage = 5;
?>
<section class="shop-page purchases-page" data-purchases-page data-per-page="<?= e((string) $perPage) ?>">
    <header class="purchases-hero">
        <div class="purchases-hero-icon" aria-hidden="true"><?= icon('cart', 22) ?></div>
        <div>
            <h1>My Purchases</h1>
            <p>View your vending machine purchase history</p>
        </div>
    </header>

    <div class="purchase-stats">
        <article class="purchase-stat">
            <div class="purchase-stat-icon"><?= icon('cart', 18) ?></div>
            <div>
                <p>Total Purchases</p>
                <strong data-stat-purchases><?= e((string) $totalPurchases) ?></strong>
            </div>
        </article>
        <article class="purchase-stat">
            <div class="purchase-stat-icon"><?= icon('box', 18) ?></div>
            <div>
                <p>Total Items</p>
                <strong data-stat-items><?= e((string) $totalItems) ?></strong>
            </div>
        </article>
        <article class="purchase-stat">
            <div class="purchase-stat-icon"><?= icon('coins', 18) ?></div>
            <div>
                <p>Total Spent</p>
                <strong data-stat-spent>$<?= e(money($totalSpent)) ?></strong>
            </div>
        </article>
        <article class="purchase-stat">
            <div class="purchase-stat-icon"><?= icon('calendar', 18) ?></div>
            <div>
                <p>This Month</p>
                <strong data-stat-month>$<?= e(money($monthSpent)) ?></strong>
            </div>
        </article>
    </div>

    <?php if ($transactions === []): ?>
        <p class="empty">You have not purchased anything yet.</p>
    <?php else: ?>
        <div class="purchases-toolbar">
            <label class="purchases-search">
                <span class="sr-only">Search purchases</span>
                <span class="search-ico" aria-hidden="true"><?= icon('search', 15) ?></span>
                <input type="search" data-purchase-search placeholder="Search by product name or transaction ID...">
            </label>
            <label class="purchases-filter">
                <span class="sr-only">Date range</span>
                <select data-purchase-range>
                    <option value="all">All Time</option>
                    <option value="month">This Month</option>
                    <option value="week">This Week</option>
                    <option value="today">Today</option>
                </select>
            </label>
            <label class="purchases-filter">
                <span class="sr-only">Per page</span>
                <select data-purchase-per-page>
                    <option value="1" <?= $perPage === 1 ? 'selected' : '' ?>>1 per page</option>
                    <option value="2" <?= $perPage === 2 ? 'selected' : '' ?>>2 per page</option>
                    <option value="5" <?= $perPage === 5 ? 'selected' : '' ?>>5 per page</option>
                    <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10 per page</option>
                    <option value="20" <?= $perPage === 20 ? 'selected' : '' ?>>20 per page</option>
                </select>
            </label>
            <button type="button" class="button button-ghost button-small purchases-export" data-purchase-export>
                <?= icon('download', 15) ?> Export
            </button>
        </div>

        <div class="purchases-table-wrap">
            <div class="purchases-table-scroll">
            <table class="purchases-table" data-purchases-table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Transaction ID</th>
                        <th>Products</th>
                        <th>Quantity</th>
                        <th>Total Price</th>
                        <th>Purchased At</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $index => $transaction): ?>
                        <?php
                        $id = (int) $transaction->id;
                        $txCode = '#PUR-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
                        $slug = (string) ($transaction->productSlug ?? '');
                        $image = $slug !== '' ? product_image($slug) : null;
                        $created = (string) ($transaction->createdAt ?? '');
                        $stamp = $created !== '' ? strtotime($created) : false;
                        $dayKey = $stamp ? date('Y-m-d', $stamp) : '';
                        $weekKey = $stamp ? date('o-W', $stamp) : '';
                        $monthStamp = $stamp ? date('Y-m', $stamp) : '';
                        ?>
                        <tr
                            data-purchase-row
                            data-id="<?= e((string) $id) ?>"
                            data-code="<?= e($txCode) ?>"
                            data-product="<?= e((string) $transaction->productName) ?>"
                            data-slug="<?= e($slug) ?>"
                            data-day="<?= e($dayKey) ?>"
                            data-week="<?= e($weekKey) ?>"
                            data-month="<?= e($monthStamp) ?>"
                            data-total="<?= e(number_format((float) $transaction->total, 3, '.', '')) ?>"
                            data-qty="<?= e((string) $transaction->quantity) ?>"
                            data-when="<?= e($created) ?>"
                        >
                            <td data-col-index><?= e((string) ($index + 1)) ?></td>
                            <td><span class="tx-id"><?= e($txCode) ?></span></td>
                            <td>
                                <div class="tx-product">
                                    <?php if ($image !== null): ?>
                                        <img src="<?= e($image) ?>" alt="" width="36" height="36" loading="lazy">
                                    <?php else: ?>
                                        <span class="tx-fallback"><?= e(mb_strtoupper(mb_substr((string) $transaction->productName, 0, 1))) ?></span>
                                    <?php endif; ?>
                                    <span><?= e((string) $transaction->productName) ?></span>
                                </div>
                            </td>
                            <td><?= e((string) $transaction->quantity) ?></td>
                            <td class="tx-price">$<?= e(money($transaction->total)) ?></td>
                            <td><?= e($created !== '' ? date('Y-m-d H:i', $stamp ?: time()) : '—') ?></td>
                            <td><span class="status-pill"><?= icon('check', 12) ?> Completed</span></td>
                            <td class="tx-actions">
                                <div class="tx-menu">
                                    <button type="button" class="tx-more" data-tx-menu aria-label="More actions" aria-expanded="false">
                                        <?= icon('more', 16) ?>
                                    </button>
                                    <div class="tx-menu-list" hidden>
                                        <?php if ($slug !== ''): ?>
                                            <a href="<?= e(url('/products/' . $slug)) ?>">View product</a>
                                            <a href="<?= e(url('/products/' . $slug)) ?>">Buy again</a>
                                        <?php endif; ?>
                                        <button type="button" data-copy-code="<?= e($txCode) ?>">Copy ID</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <footer class="purchases-footer">
            <p data-purchase-summary>Showing 0 – 0 of 0 purchases</p>
            <nav class="pager purchases-pager" data-purchase-pager aria-label="Purchases pagination"></nav>
        </footer>
    <?php endif; ?>
</section>
