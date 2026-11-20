<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Error') ?> · VendHub</title>
    <link rel="stylesheet" href="<?= e(asset_url('assets/app.css')) ?>">
    <style>
        html, body {
            margin: 0;
            min-height: 100%;
            background: #f3f4f6 !important;
            color: #111827 !important;
        }
        .error-shell {
            min-height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr;
            background: #f3f4f6;
            color: #111827;
        }
        .error-shell__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 20px;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
        }
        .error-shell__brand img {
            display: block;
            height: 40px;
            width: auto;
            max-width: 200px;
        }
        .error-shell__main {
            display: grid;
            place-items: center;
            padding: 32px 16px 48px;
        }
    </style>
</head>
<body class="error-shell-body">
    <div class="error-shell">
        <header class="error-shell__top">
            <a class="error-shell__brand" href="<?= e(url('/products')) ?>">
                <img src="<?= e(brand_logo()) ?>" alt="VendHub">
            </a>
            <a class="button button-small" href="<?= e(url('/products')) ?>"><?= icon('home', 15) ?> Products</a>
        </header>
        <main class="error-shell__main">
            <?= $content ?>
        </main>
    </div>
    <script nonce="<?= e(csp_nonce()) ?>" src="<?= e(asset_url('assets/app.js')) ?>"></script>
</body>
</html>
