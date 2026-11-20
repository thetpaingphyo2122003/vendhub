<?php
/** @var list<App\Model\Transaction> $transactions */
/** @var App\Model\TransactionQuery $query */
/** @var int $txCount */
/** @var int $itemCount */
/** @var string $revenueTotal */

$count = count($transactions);

$sortLink = static function (string $column) use ($query): string {
    $dir = ($query->sort === $column && $query->direction === 'asc') ? 'desc' : 'asc';

    return url('/admin/transactions') . '?' . http_build_query(array_filter([
        'sort' => $column,
        'dir' => $dir,
        'start' => $query->startForInput() !== '' ? $query->startForInput() : null,
        'end' => $query->endForInput() !== '' ? $query->endForInput() : null,
    ]));
};

$sortIcon = static function (string $column) use ($query): string {
    if ($query->sort !== $column) {
        return '<span class="admin-sort-ico is-idle" aria-hidden="true">' . icon('chevron-down', 12) . '</span>';
    }

    $iconName = $query->direction === 'asc' ? 'chevron-up' : 'chevron-down';

    return '<span class="admin-sort-ico is-active" aria-hidden="true">' . icon($iconName, 12) . '</span>';
};
?>
<section class="admin-tx">
    <div class="admin-tx-stats">
        <article class="dash-stat dash-stat--mint">
            <div>
                <p>Transactions</p>
                <strong><?= e((string) $txCount) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('list', 18) ?></span>
        </article>
        <article class="dash-stat dash-stat--sky">
            <div>
                <p>Items Sold</p>
                <strong><?= e((string) $itemCount) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('box', 18) ?></span>
        </article>
        <article class="dash-stat dash-stat--peach">
            <div>
                <p>Revenue</p>
                <strong>$<?= e(money($revenueTotal)) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('coins', 18) ?></span>
        </article>
    </div>

    <div class="admin-tx-card">
        <form class="admin-tx-filters" method="get" action="<?= e(url('/admin/transactions')) ?>">
            <input type="hidden" name="sort" value="<?= e($query->sort) ?>">
            <input type="hidden" name="dir" value="<?= e($query->direction) ?>">

            <label class="admin-tx-date">
                <span>Start time</span>
                <input type="datetime-local" name="start" value="<?= e($query->startForInput()) ?>">
            </label>

            <label class="admin-tx-date">
                <span>End time</span>
                <input type="datetime-local" name="end" value="<?= e($query->endForInput()) ?>">
            </label>

            <div class="admin-tx-filter-actions">
                <button type="submit" class="button button-small"><?= icon('search', 14) ?> Filter</button>
                <a class="button button-ghost button-small" href="<?= e(url('/admin/transactions')) ?>">Clear</a>
            </div>
        </form>

        <?php if ($transactions === []): ?>
            <div class="admin-tx-empty">
                <p>No transactions found for this filter.</p>
            </div>
        <?php else: ?>
            <div class="admin-tx-table-wrap">
                <table class="admin-tx-table">
                    <thead>
                        <tr>
                            <th>
                                <a class="admin-sort-link <?= $query->sort === 'when' ? 'is-active' : '' ?>" href="<?= e($sortLink('when')) ?>">
                                    When <?= $sortIcon('when') ?>
                                </a>
                            </th>
                            <th>
                                <a class="admin-sort-link <?= $query->sort === 'customer' ? 'is-active' : '' ?>" href="<?= e($sortLink('customer')) ?>">
                                    Customer <?= $sortIcon('customer') ?>
                                </a>
                            </th>
                            <th>
                                <a class="admin-sort-link <?= $query->sort === 'product' ? 'is-active' : '' ?>" href="<?= e($sortLink('product')) ?>">
                                    Product <?= $sortIcon('product') ?>
                                </a>
                            </th>
                            <th class="is-num">
                                <a class="admin-sort-link <?= $query->sort === 'qty' ? 'is-active' : '' ?>" href="<?= e($sortLink('qty')) ?>">
                                    Qty <?= $sortIcon('qty') ?>
                                </a>
                            </th>
                            <th class="is-num">
                                <a class="admin-sort-link <?= $query->sort === 'unit_price' ? 'is-active' : '' ?>" href="<?= e($sortLink('unit_price')) ?>">
                                    Unit price <?= $sortIcon('unit_price') ?>
                                </a>
                            </th>
                            <th class="is-num">
                                <a class="admin-sort-link <?= $query->sort === 'total' ? 'is-active' : '' ?>" href="<?= e($sortLink('total')) ?>">
                                    Total <?= $sortIcon('total') ?>
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <?php
                            $slug = (string) ($transaction->productSlug ?? '');
                            $image = $slug !== '' ? product_image($slug) : null;
                            $productName = (string) ($transaction->productName ?? '—');
                            $when = (string) ($transaction->createdAt ?? '—');
                            if ($when !== '—' && strtotime($when) !== false) {
                                $when = date('M j, Y · g:i A', strtotime($when));
                            }
                            ?>
                            <tr>
                                <td class="admin-tx-when"><?= e($when) ?></td>
                                <td>
                                    <span class="admin-tx-customer"><?= e((string) ($transaction->userName ?? '—')) ?></span>
                                </td>
                                <td>
                                    <div class="admin-tx-product">
                                        <?php if ($image !== null): ?>
                                            <img src="<?= e($image) ?>" alt="">
                                        <?php else: ?>
                                            <span class="admin-tx-fallback"><?= e(mb_strtoupper(mb_substr($productName, 0, 1))) ?></span>
                                        <?php endif; ?>
                                        <span><?= e($productName) ?></span>
                                    </div>
                                </td>
                                <td class="is-num"><?= e((string) $transaction->quantity) ?></td>
                                <td class="is-num">$<?= e(money($transaction->unitPrice)) ?></td>
                                <td class="is-num admin-tx-total">$<?= e(money($transaction->total)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <footer class="admin-tx-footer">
                <p>Showing <?= e((string) $count) ?> transaction<?= $count === 1 ? '' : 's' ?></p>
            </footer>
        <?php endif; ?>
    </div>
</section>
