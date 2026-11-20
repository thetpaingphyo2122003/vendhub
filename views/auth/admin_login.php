<section class="auth-card">
    <div class="auth-visual">
        <div class="auth-brand">
            <img class="brand-logo brand-logo-auth" src="<?= e(brand_logo()) ?>" alt="VendHub">
        </div>
        <div class="auth-visual-copy">
            <h1>VendHub Admin</h1>
            <p>Manage products, users, and sales.</p>
        </div>
        <div class="auth-visual-art" aria-hidden="true">
            <div class="auth-art-stage">
                <img src="<?= e(machine_image()) ?>" alt="" width="360" height="480">
            </div>
        </div>
    </div>

    <div class="auth-form-pane">
        <div class="auth-form-head">
            <span class="auth-badge" aria-hidden="true"><?= icon('shield', 22) ?></span>
            <h2>Admin Sign In</h2>
            <p>Admin access only</p>
        </div>

        <form method="post" action="<?= e(url('/admin/login')) ?>" class="auth-form" data-validate="login" novalidate>
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

            <div class="field" data-field="username">
                <label class="sr-only" for="admin-username">Username</label>
                <div class="input-shell">
                    <span class="input-icon" aria-hidden="true"><?= icon('mail', 16) ?></span>
                    <input id="admin-username" name="username" value="<?= e($old['username'] ?? '') ?>" placeholder="Admin username" required autocomplete="username">
                </div>
                <?php if (!empty($errors['username'])): ?><p class="field-error"><?= e($errors['username']) ?></p><?php endif; ?>
            </div>

            <div class="field" data-field="password">
                <label class="sr-only" for="admin-password">Password</label>
                <div class="input-shell">
                    <span class="input-icon" aria-hidden="true"><?= icon('lock', 16) ?></span>
                    <input id="admin-password" name="password" type="password" placeholder="Password" required autocomplete="current-password">
                    <button type="button" class="password-toggle" data-toggle-password="admin-password" aria-label="Show password"></button>
                </div>
            </div>

            <button type="submit" class="auth-submit">Sign In</button>
        </form>

        <div class="auth-foot">
            <span>Customer account?</span>
            <a href="<?= e(url('/login')) ?>">Shop login</a>
        </div>
    </div>
</section>
