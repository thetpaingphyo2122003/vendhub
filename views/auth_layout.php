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
<body class="auth-body">
    <div class="auth-wave auth-wave-top" aria-hidden="true"></div>
    <div class="auth-wave auth-wave-bottom" aria-hidden="true"></div>
    <main class="auth-page">
        <?php if (!empty($flash)): ?>
            <p class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
        <?php endif; ?>
        <?= $content ?>
    </main>
    <script nonce="<?= e(csp_nonce()) ?>" src="<?= e(asset_url('assets/app.js')) ?>"></script>
</body>
</html>
