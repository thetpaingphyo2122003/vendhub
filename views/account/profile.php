<?php
/** @var App\Model\User $profileUser */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

$name = (string) ($old['name'] ?? $profileUser->name);
$username = (string) ($old['username'] ?? $profileUser->username);
$initial = mb_strtoupper(mb_substr($name !== '' ? $name : $profileUser->name, 0, 1));
$passwordErrors = array_intersect_key($errors ?? [], array_flip(['current_password', 'password', 'password_confirmation']));
$openPasswordDialog = $passwordErrors !== [];
?>
<section class="shop-page profile-page">
    <header class="profile-hero">
        <div class="profile-hero-icon" aria-hidden="true"><?= icon('user', 22) ?></div>
        <div>
            <h1>Profile</h1>
            <p>Update your personal details and account security</p>
        </div>
    </header>

    <form method="post" action="<?= e(url('/profile')) ?>" class="profile-layout" data-validate="profile" novalidate>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <aside class="profile-identity">
            <div class="avatar large profile-avatar" data-profile-avatar><?= e($initial) ?></div>
            <div class="profile-identity-copy">
                <strong data-profile-name><?= e($name) ?></strong>
                <span data-profile-username><?= e($username) ?></span>
            </div>
            <button type="button" class="button button-ghost button-small profile-security-btn" data-open-password-dialog>
                <?= icon('lock', 15) ?> Change Password
            </button>
        </aside>

        <div class="profile-panel">
            <div class="profile-section-head">
                <h2>Account details</h2>
                <p>These details appear across your VendHub account.</p>
            </div>

            <div class="profile-fields">
                <div class="field" data-field="name">
                    <label for="name"><?= icon('user', 14) ?> Full Name</label>
                    <div class="input-shell">
                        <input id="name" name="name" type="text" value="<?= e($name) ?>" required maxlength="100" autocomplete="name">
                    </div>
                    <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
                </div>

                <div class="field" data-field="username">
                    <label for="username"><?= icon('user', 14) ?> Username</label>
                    <div class="input-shell">
                        <input id="username" name="username" value="<?= e($username) ?>" required minlength="3" maxlength="32" autocomplete="username">
                    </div>
                    <?php if (!empty($errors['username'])): ?><p class="field-error"><?= e($errors['username']) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="profile-actions">
                <a class="button button-ghost button-small" href="<?= e(url('/profile')) ?>">Cancel</a>
                <button type="submit" class="button button-small"><?= icon('check', 15) ?> Save Changes</button>
            </div>
        </div>

        <div
            class="profile-password-dialog"
            data-password-dialog
            role="dialog"
            aria-modal="true"
            aria-labelledby="password-dialog-title"
            <?= $openPasswordDialog ? '' : 'hidden' ?>
        >
            <div class="profile-password-dialog__backdrop" data-close-password-dialog></div>
            <div class="profile-password-dialog__panel">
                <div class="profile-password-dialog__head">
                    <div class="profile-password-dialog__badge" aria-hidden="true"><?= icon('lock', 18) ?></div>
                    <div>
                        <h2 id="password-dialog-title">Change Password</h2>
                        <p>Use a strong password with at least 8 characters.</p>
                    </div>
                </div>

                <div class="profile-password-grid">
                    <div class="field" data-field="current_password">
                        <label for="current_password">Current Password</label>
                        <div class="input-shell">
                            <span class="input-icon" aria-hidden="true"><?= icon('lock', 16) ?></span>
                            <input id="current_password" name="current_password" type="password" placeholder="Current password" autocomplete="current-password">
                            <button type="button" class="password-toggle" data-toggle-password="current_password" aria-label="Show password"></button>
                        </div>
                        <?php if (!empty($errors['current_password'])): ?><p class="field-error"><?= e($errors['current_password']) ?></p><?php endif; ?>
                    </div>

                    <div class="field" data-field="password">
                        <label for="password">New Password</label>
                        <div class="input-shell">
                            <span class="input-icon" aria-hidden="true"><?= icon('lock', 16) ?></span>
                            <input id="password" name="password" type="password" placeholder="New password" autocomplete="new-password">
                            <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password"></button>
                        </div>
                        <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
                    </div>

                    <div class="field" data-field="password_confirmation">
                        <label for="password_confirmation">Confirm New Password</label>
                        <div class="input-shell">
                            <span class="input-icon" aria-hidden="true"><?= icon('lock', 16) ?></span>
                            <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Confirm new password" autocomplete="new-password">
                            <button type="button" class="password-toggle" data-toggle-password="password_confirmation" aria-label="Show password"></button>
                        </div>
                        <?php if (!empty($errors['password_confirmation'])): ?><p class="field-error"><?= e($errors['password_confirmation']) ?></p><?php endif; ?>
                    </div>
                </div>

                <p class="field-hint">Leave blank if you do not want to change your password. Click Done, then Save Changes.</p>

                <div class="profile-password-dialog__actions">
                    <button type="button" class="button button-ghost button-small" data-close-password-dialog>Cancel</button>
                    <button type="button" class="button button-small" data-apply-password-dialog><?= icon('check', 15) ?> Done</button>
                </div>
            </div>
        </div>
    </form>
</section>
