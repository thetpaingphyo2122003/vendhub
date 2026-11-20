<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · VendHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/app.css')) ?>">
</head>
<body class="store-app">
    <div class="store-drawer-backdrop" data-store-drawer-backdrop hidden></div>

    <aside class="store-drawer" id="store-drawer" data-store-drawer>
        <div class="store-drawer-head">
            <a class="store-brand" href="<?= e(url('/products')) ?>">
                <img class="brand-logo" src="<?= e(brand_logo()) ?>" alt="VendHub">
            </a>
            <button type="button" class="store-drawer-close" data-store-menu-close aria-label="Close menu">
                <?= icon('x', 18) ?>
            </button>
        </div>

        <nav class="store-drawer-nav" aria-label="Shop menu">
            <a href="<?= e(url('/products')) ?>" class="<?= ($activeNav ?? '') === 'products' ? 'is-active' : '' ?>">
                <span class="store-drawer-ico" aria-hidden="true"><?= icon('box', 16) ?></span>
                Products
            </a>
            <?php if ($authUser !== null): ?>
                <a href="<?= e(url('/purchases')) ?>" class="<?= ($activeNav ?? '') === 'purchases' ? 'is-active' : '' ?>">
                    <span class="store-drawer-ico" aria-hidden="true"><?= icon('list', 16) ?></span>
                    My Purchases
                </a>
                <a href="<?= e(url('/profile')) ?>" class="<?= ($activeNav ?? '') === 'profile' ? 'is-active' : '' ?>">
                    <span class="store-drawer-ico" aria-hidden="true"><?= icon('user', 16) ?></span>
                    Profile
                </a>
                <?php if ($authUser->isAdmin()): ?>
                    <a href="<?= e(url('/admin')) ?>">
                        <span class="store-drawer-ico" aria-hidden="true"><?= icon('layout', 16) ?></span>
                        Switch to admin
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>

        <div class="store-drawer-foot">
            <?php if ($authUser !== null): ?>
                <div class="user-chip store-drawer-user">
                    <span class="avatar"><?= e(mb_strtoupper(mb_substr($authUser->name, 0, 1))) ?></span>
                    <span class="user-chip-text">
                        <small>Welcome</small>
                        <strong><?= e($authUser->name) ?></strong>
                    </span>
                </div>
                <form method="post" action="<?= e(url('/logout')) ?>" data-confirm="Log out of your account?">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="next" value="<?= e($authUser->isAdmin() ? '/admin/login' : '/login') ?>">
                    <button type="submit" class="button button-small store-drawer-logout">Logout</button>
                </form>
            <?php else: ?>
                <a class="button button-small" href="<?= e(url('/login')) ?>"><?= icon('login', 15) ?> Sign In</a>
            <?php endif; ?>
        </div>
    </aside>

    <header class="store-topbar">
        <div class="store-topbar-inner">
            <button
                type="button"
                class="store-menu-toggle"
                data-store-menu-toggle
                aria-label="Open menu"
                aria-controls="store-drawer"
                aria-expanded="false"
            >
                <?= icon('menu', 18) ?>
            </button>

            <a class="store-brand" href="<?= e(url('/products')) ?>">
                <img class="brand-logo" src="<?= e(brand_logo()) ?>" alt="VendHub">
            </a>

            <nav class="store-nav" aria-label="Shop">
                <a href="<?= e(url('/products')) ?>" class="<?= ($activeNav ?? '') === 'products' ? 'is-active' : '' ?>">Products</a>
                <?php if ($authUser !== null): ?>
                    <a href="<?= e(url('/purchases')) ?>" class="<?= ($activeNav ?? '') === 'purchases' ? 'is-active' : '' ?>">My Purchases</a>
                    <a href="<?= e(url('/profile')) ?>" class="<?= ($activeNav ?? '') === 'profile' ? 'is-active' : '' ?>">Profile</a>
                    <?php if ($authUser->isAdmin()): ?>
                        <a href="<?= e(url('/admin')) ?>">Switch to admin</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="store-top-actions">
                <?php if ($authUser !== null): ?>
                    <div class="user-chip">
                        <span class="avatar"><?= e(mb_strtoupper(mb_substr($authUser->name, 0, 1))) ?></span>
                        <span class="user-chip-text">
                            <small>Welcome</small>
                            <strong><?= e($authUser->name) ?></strong>
                        </span>
                    </div>
                    <form method="post" action="<?= e(url('/logout')) ?>" class="store-logout" data-confirm="Log out of your account?">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="next" value="<?= e($authUser->isAdmin() ? '/admin/login' : '/login') ?>">
                        <button type="submit" class="button button-ghost button-small">Logout</button>
                    </form>
                <?php else: ?>
                    <a class="button button-small store-signin" href="<?= e(url('/login')) ?>"><?= icon('login', 15) ?> Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="store-content">
        <?php if (!empty($flash)): ?>
            <p class="flash flash-<?= e($flash['type']) ?>" data-flash-toast role="status"><?= e($flash['message']) ?></p>
        <?php endif; ?>
        <?= $content ?>
    </main>
    <script nonce="<?= e(csp_nonce()) ?>" src="<?= e(asset_url('assets/app.js')) ?>"></script>
</body>
</html>
