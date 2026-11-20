<section class="auth-card">
    <div class="auth-visual">
        <div class="auth-brand">
            <img class="brand-logo brand-logo-auth" src="<?= e(brand_logo()) ?>" alt="VendHub">
        </div>
        <div class="auth-visual-copy">
            <h1>Create your VendHub account</h1>
            <p>Register once, then buy drinks and snacks anytime.</p>
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
            <h2>Register</h2>
            <p>Create a customer account</p>
        </div>

        <form method="post" action="<?= e(url('/register')) ?>" class="auth-form" data-validate="register" novalidate>
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

            <div class="field" data-field="name">
                <label class="sr-only" for="name">Name</label>
                <div class="input-shell">
                    <span class="input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input id="name" name="name" value="<?= e($old['name'] ?? '') ?>" placeholder="Full name" required maxlength="100" autocomplete="name">
                </div>
                <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>

            <div class="field" data-field="username">
                <label class="sr-only" for="username">Username</label>
                <div class="input-shell">
                    <span class="input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
                    </span>
                    <input id="username" name="username" value="<?= e($old['username'] ?? '') ?>" placeholder="Username" required minlength="3" maxlength="32" autocomplete="username">
                </div>
                <?php if (!empty($errors['username'])): ?><p class="field-error"><?= e($errors['username']) ?></p><?php endif; ?>
            </div>

            <div class="field" data-field="password">
                <label class="sr-only" for="password">Password</label>
                <div class="input-shell">
                    <span class="input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    </span>
                    <input id="password" name="password" type="password" placeholder="Strong password" required minlength="8" autocomplete="new-password">
                    <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </button>
                </div>
                <p class="field-hint">Use 8+ characters with upper, lower, number, and symbol.</p>
                <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
            </div>

            <div class="field" data-field="password_confirmation">
                <label class="sr-only" for="password_confirmation">Confirm password</label>
                <div class="input-shell">
                    <span class="input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    </span>
                    <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Confirm password" required autocomplete="new-password">
                    <button type="button" class="password-toggle" data-toggle-password="password_confirmation" aria-label="Show password">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </button>
                </div>
                <?php if (!empty($errors['password_confirmation'])): ?><p class="field-error"><?= e($errors['password_confirmation']) ?></p><?php endif; ?>
            </div>

            <button type="submit" class="auth-submit"><?= icon('user-plus', 16) ?> Create account</button>
        </form>

        <div class="auth-foot">
            <span>Already have an account?</span>
            <a href="<?= e(url('/login')) ?>">Sign In</a>
        </div>
    </div>
</section>
