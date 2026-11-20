<section class="auth-card">
    <div class="auth-visual">
        <div class="auth-brand">
            <img class="brand-logo brand-logo-auth" src="<?= e(brand_logo()) ?>" alt="VendHub">
        </div>
        <div class="auth-visual-copy">
            <h1>Welcome to VendHub</h1>
            <p>Buy your favorite drinks anytime.</p>
        </div>
        <div class="auth-visual-art" aria-hidden="true">
            <div class="auth-art-stage">
                <img src="<?= e(machine_image()) ?>" alt="" width="360" height="480">
            </div>
        </div>
    </div>

    <div class="auth-form-pane">
        <div class="auth-form-head">
            <span class="auth-badge" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="6" y="2" width="12" height="20" rx="2"></rect>
                    <path d="M9 6h6M9 10h6M9 14h3"></path>
                    <path d="M8 18h8"></path>
                </svg>
            </span>
            <h2>Sign In</h2>
            <p>Access your account</p>
        </div>

        <form method="post" action="<?= e(url('/login')) ?>" class="auth-form" data-validate="login" novalidate>
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

            <div class="field" data-field="username">
                <label class="sr-only" for="username">Username</label>
                <div class="input-shell">
                    <span class="input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input id="username" name="username" value="<?= e($old['username'] ?? '') ?>" placeholder="Username" required autocomplete="username">
                </div>
                <?php if (!empty($errors['username'])): ?><p class="field-error"><?= e($errors['username']) ?></p><?php endif; ?>
            </div>

            <div class="field" data-field="password">
                <label class="sr-only" for="password">Password</label>
                <div class="input-shell">
                    <span class="input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    </span>
                    <input id="password" name="password" type="password" placeholder="Password" required autocomplete="current-password">
                    <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </button>
                </div>
            </div>

            <div class="auth-row">
                <label class="check-line">
                    <input type="checkbox" name="remember" value="1">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="auth-submit"><?= icon('login', 16) ?> Sign In</button>
        </form>

        <div class="auth-foot">
            <span>Don't have an account?</span>
            <a href="<?= e(url('/register')) ?>">Register</a>
        </div>
    </div>
</section>
