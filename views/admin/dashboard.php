<?php
/** @var int $productTotal */
/** @var int $userTotal */
/** @var int $outOfStockTotal */
/** @var int $salesTotal */
/** @var list<App\Model\Transaction> $recentTransactions */
/** @var list<string> $salesLabels */
/** @var list<int> $salesCounts */
/** @var list<array{label: string, value: int, color: string}> $topProducts */
$salesLabelsJson = json_for_script($salesLabels);
$salesCountsJson = json_for_script($salesCounts);
$topLabels = array_column($topProducts, 'label');
$topValues = array_column($topProducts, 'value');
$topColors = array_column($topProducts, 'color');
$topLabelsJson = json_for_script($topLabels);
$topValuesJson = json_for_script($topValues);
$topColorsJson = json_for_script($topColors);
$topSalesSum = array_sum($topValues);
?>
<section class="dash">
    <div class="dash-stats">
        <article class="dash-stat dash-stat--mint">
            <div>
                <p>Products</p>
                <strong><?= e((string) $productTotal) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('box', 18) ?></span>
        </article>
        <article class="dash-stat dash-stat--sky">
            <div>
                <p>Users</p>
                <strong><?= e((string) $userTotal) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('user', 18) ?></span>
        </article>
        <article class="dash-stat dash-stat--lilac">
            <div>
                <p>Out of Stock</p>
                <strong><?= e((string) $outOfStockTotal) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('ban', 18) ?></span>
        </article>
        <article class="dash-stat dash-stat--peach">
            <div>
                <p>Total Sales</p>
                <strong><?= e((string) $salesTotal) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('coins', 18) ?></span>
        </article>
    </div>

    <div class="dash-mid">
        <article class="dash-card">
            <div class="dash-card-head">
                <h2>Sales Overview</h2>
                <span>Last 7 days</span>
            </div>
            <canvas id="sales-chart" height="140" aria-label="Sales overview chart"></canvas>
        </article>

        <article class="dash-card dash-card--donut">
            <div class="dash-card-head">
                <h2>Top Products</h2>
            </div>
            <div class="dash-donut-wrap">
                <canvas id="products-chart" width="160" height="160" aria-label="Top products chart"></canvas>
                <div class="dash-donut-center">
                    <strong><?= e((string) $topSalesSum) ?></strong>
                    <span>Sales</span>
                </div>
            </div>
            <ul class="dash-legend">
                <?php foreach ($topProducts as $item): ?>
                    <li>
                        <span class="dash-legend-dot" style="background: <?= e($item['color']) ?>"></span>
                        <span><?= e($item['label']) ?></span>
                        <strong><?= e($topSalesSum > 0 ? number_format(($item['value'] / $topSalesSum) * 100, 1) . '%' : '0%') ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </article>

    </div>

    <div class="dash-bottom">
        <section class="dash-card dash-table-card">
            <div class="dash-card-head">
                <h2>Latest Transactions</h2>
                <a href="<?= e(url('/admin/transactions')) ?>">See all</a>
            </div>
            <?php if ($recentTransactions === []): ?>
                <p class="muted">No transactions yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Total</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $tx): ?>
                                <?php
                                $slug = (string) ($tx->productSlug ?? '');
                                $image = $slug !== '' ? product_image($slug) : null;
                                ?>
                                <tr>
                                    <td>#<?= e((string) ($tx->id ?? '—')) ?></td>
                                    <td><?= e((string) ($tx->userName ?? '—')) ?></td>
                                    <td>
                                        <div class="dash-product-cell">
                                            <?php if ($image !== null): ?>
                                                <img src="<?= e($image) ?>" alt="">
                                            <?php else: ?>
                                                <span class="dash-product-fallback"><?= e(mb_strtoupper(mb_substr((string) ($tx->productName ?? 'P'), 0, 1))) ?></span>
                                            <?php endif; ?>
                                            <span><?= e((string) ($tx->productName ?? '—')) ?></span>
                                        </div>
                                    </td>
                                    <td><?= e((string) $tx->quantity) ?></td>
                                    <td class="dash-total">$<?= e(money($tx->total)) ?></td>
                                    <td><?= e((string) ($tx->createdAt ?? '—')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <aside class="dash-card dash-actions">
            <h2>Quick Actions</h2>
            <a class="button" href="<?= e(url('/admin/products/create')) ?>"><?= icon('plus', 15) ?> Add Product</a>
            <a class="button button-ghost" href="<?= e(url('/admin/users')) ?>"><?= icon('user-plus', 15) ?> Manage Users</a>
            <a class="button button-ghost" href="<?= e(url('/admin/transactions')) ?>"><?= icon('list', 15) ?> View Transactions</a>
            <a class="button button-ghost" href="<?= e(url('/products')) ?>"><?= icon('home', 15) ?> Switch to Shop View</a>
        </aside>
    </div>
</section>

<script nonce="<?= e(csp_nonce()) ?>">
document.addEventListener("DOMContentLoaded", () => {
    if (typeof Chart === "undefined") return;

    const salesLabels = <?= $salesLabelsJson ?>;
    const salesCounts = <?= $salesCountsJson ?>;
    const topLabels = <?= $topLabelsJson ?>;
    const topValues = <?= $topValuesJson ?>;
    const topColors = <?= $topColorsJson ?>;

    const salesCanvas = document.getElementById("sales-chart");
    if (salesCanvas) {
        new Chart(salesCanvas, {
            type: "line",
            data: {
                labels: salesLabels,
                datasets: [{
                    data: salesCounts,
                    borderColor: "#22c55e",
                    backgroundColor: "rgba(34, 197, 94, 0.12)",
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointBackgroundColor: "#16a34a",
                    borderWidth: 2.5,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: "#6b7280", font: { size: 11 } } },
                    y: { beginAtZero: true, ticks: { precision: 0, color: "#6b7280", font: { size: 11 } }, grid: { color: "#f1f5f9" } },
                },
            },
        });
    }

    const productsCanvas = document.getElementById("products-chart");
    if (productsCanvas) {
        new Chart(productsCanvas, {
            type: "doughnut",
            data: {
                labels: topLabels,
                datasets: [{
                    data: topValues.length ? topValues : [1],
                    backgroundColor: topValues.length ? topColors : ["#e5e7eb"],
                    borderWidth: 0,
                    hoverOffset: 4,
                }],
            },
            options: {
                cutout: "72%",
                plugins: { legend: { display: false } },
            },
        });
    }
});
</script>
