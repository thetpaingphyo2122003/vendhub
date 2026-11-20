<?php
$nav = (string) ($activeNav ?? '');
$adminName = (string) ($authUser?->name ?? 'Admin');
$adminInitial = mb_strtoupper(mb_substr($adminName !== '' ? $adminName : 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · VendHub Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/app.css')) ?>">
</head>
<body class="admin admin-sidebar-open">
    <div class="admin-shell">
        <div class="admin-sidebar-backdrop" data-admin-sidebar-backdrop hidden></div>

        <aside class="admin-sidebar" id="admin-sidebar" data-admin-sidebar>
            <div class="admin-sidebar-head">
                <a class="admin-sidebar-brand" href="<?= e(url('/admin')) ?>">
                    <img src="<?= e(brand_logo()) ?>" alt="VendHub">
                </a>
                <button type="button" class="admin-sidebar-close" data-admin-menu-close aria-label="Close menu">
                    <?= icon('x', 18) ?>
                </button>
            </div>

            <nav class="admin-sidebar-nav" aria-label="Admin">
                <a href="<?= e(url('/admin')) ?>" class="<?= $nav === 'dashboard' ? 'is-active' : '' ?>">
                    <span class="admin-nav-ico" aria-hidden="true"><?= icon('layout', 16) ?></span>
                    <span class="admin-nav-label">Dashboard</span>
                </a>
                <a href="<?= e(url('/admin/products')) ?>" class="<?= $nav === 'products' ? 'is-active' : '' ?>">
                    <span class="admin-nav-ico" aria-hidden="true"><?= icon('box', 16) ?></span>
                    <span class="admin-nav-label">Products</span>
                </a>
                <a href="<?= e(url('/admin/categories')) ?>" class="<?= $nav === 'categories' ? 'is-active' : '' ?>">
                    <span class="admin-nav-ico" aria-hidden="true"><?= icon('tag', 16) ?></span>
                    <span class="admin-nav-label">Categories</span>
                </a>
                <a href="<?= e(url('/admin/transactions')) ?>" class="<?= $nav === 'transactions' ? 'is-active' : '' ?>">
                    <span class="admin-nav-ico" aria-hidden="true"><?= icon('list', 16) ?></span>
                    <span class="admin-nav-label">Transactions</span>
                </a>
                <a href="<?= e(url('/admin/users')) ?>" class="<?= $nav === 'users' ? 'is-active' : '' ?>">
                    <span class="admin-nav-ico" aria-hidden="true"><?= icon('user', 16) ?></span>
                    <span class="admin-nav-label">Users</span>
                </a>
                <a href="<?= e(url('/products')) ?>" class="admin-sidebar-switch">
                    <span class="admin-nav-ico" aria-hidden="true"><?= icon('home', 16) ?></span>
                    <span class="admin-nav-label">Switch to Shop View</span>
                </a>
            </nav>

            <form method="post" action="<?= e(url('/logout')) ?>" class="admin-sidebar-logout" data-confirm="Log out of your account?">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="next" value="/admin/login">
                <button type="submit">Log out</button>
            </form>
        </aside>

        <div class="admin-workspace">
            <header class="admin-topbar">
                <div class="admin-topbar-lead">
                    <button
                        type="button"
                        class="admin-menu-toggle"
                        data-admin-menu-toggle
                        aria-label="Open menu"
                        aria-controls="admin-sidebar"
                        aria-expanded="false"
                    >
                        <span class="admin-menu-toggle__open" aria-hidden="true"><?= icon('panel-right', 18) ?></span>
                        <span class="admin-menu-toggle__close" aria-hidden="true"><?= icon('panel-left', 18) ?></span>
                    </button>
                    <div class="admin-topbar-title">
                        <h1><?= e((string) ($title ?? 'Admin')) ?></h1>
                        <?php if ($nav === 'dashboard'): ?>
                            <p>Welcome back, <?= e($adminName) ?>.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="admin-topbar-actions">
                    <div class="admin-notify" data-admin-notify data-notify-url="<?= e(url('/admin/notifications')) ?>">
                        <button type="button" class="admin-bell" data-notify-toggle aria-label="Notifications" aria-expanded="false">
                            <?= icon('bell', 16) ?>
                            <span class="admin-bell-badge" data-notify-badge hidden>0</span>
                        </button>
                        <div class="admin-notify-panel" data-notify-panel hidden>
                            <div class="admin-notify-head">
                                <strong>Notifications</strong>
                                <span data-notify-status>Live</span>
                            </div>
                            <div class="admin-notify-list" data-notify-list>
                                <p class="admin-notify-empty">No alerts right now.</p>
                            </div>
                        </div>
                    </div>
                    <div class="admin-userchip">
                        <span class="admin-userchip-avatar"><?= e($adminInitial) ?></span>
                        <span class="admin-userchip-copy">
                            <strong><?= e($adminName) ?></strong>
                            <small>Admin</small>
                        </span>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                <?php if (!empty($flash)): ?>
                    <p class="flash flash-<?= e($flash['type']) ?>" data-flash-toast role="status"><?= e($flash['message']) ?></p>
                <?php endif; ?>
                <?= $content ?>
            </main>
        </div>
    </div>
    <script nonce="<?= e(csp_nonce()) ?>" src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <script nonce="<?= e(csp_nonce()) ?>" src="<?= e(asset_url('assets/app.js')) ?>"></script>
</body>
</html>
