<?php
/** @var list<App\Model\User> $users */
/** @var int $adminCount */
/** @var int $userCount */
/** @var App\Model\User|null $authUser */

$actorId = $authUser?->id;
$total = count($users);
?>
<section class="admin-users">
    <div class="admin-users-stats">
        <article class="dash-stat dash-stat--mint">
            <div>
                <p>Total Users</p>
                <strong><?= e((string) $total) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('user', 18) ?></span>
        </article>
        <article class="dash-stat dash-stat--sky">
            <div>
                <p>Admins</p>
                <strong><?= e((string) $adminCount) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('shield', 18) ?></span>
        </article>
        <article class="dash-stat dash-stat--peach">
            <div>
                <p>Customers</p>
                <strong><?= e((string) $userCount) ?></strong>
            </div>
            <span class="dash-stat-icon"><?= icon('user-plus', 18) ?></span>
        </article>
    </div>

    <div class="admin-users-card">
        <?php if ($users === []): ?>
            <div class="admin-users-empty">
                <p>No users found.</p>
            </div>
        <?php else: ?>
            <div class="admin-users-table-wrap">
                <table class="admin-users-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $isSelf = $actorId !== null && $user->id === $actorId;
                            $initial = mb_strtoupper(mb_substr($user->name !== '' ? $user->name : 'U', 0, 1));
                            ?>
                            <tr>
                                <td>
                                    <div class="admin-user-cell">
                                        <span class="admin-user-avatar"><?= e($initial) ?></span>
                                        <div>
                                            <strong>
                                                <?= e($user->name) ?>
                                                <?php if ($isSelf): ?><em>You</em><?php endif; ?>
                                            </strong>
                                            <span><?= e($user->username) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isSelf): ?>
                                        <span class="admin-role-pill is-<?= e($user->role) ?>"><?= e($user->role) ?></span>
                                    <?php else: ?>
                                        <form class="admin-users-role-form" method="post" action="<?= e(url('/admin/users/' . $user->id . '/role')) ?>" data-confirm="Change role for <?= e($user->name) ?>?">
                                            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                            <select name="role" aria-label="Role for <?= e($user->name) ?>">
                                                <option value="user" <?= $user->role === 'user' ? 'selected' : '' ?>>user</option>
                                                <option value="admin" <?= $user->role === 'admin' ? 'selected' : '' ?>>admin</option>
                                            </select>
                                            <button type="submit" class="admin-icon-btn" title="Save role" aria-label="Save role for <?= e($user->name) ?>">
                                                <?= icon('save', 14) ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isSelf): ?>
                                        <span class="admin-users-locked">—</span>
                                    <?php else: ?>
                                        <div class="admin-row-actions">
                                            <form method="post" action="<?= e(url('/admin/users/' . $user->id . '/delete')) ?>" data-confirm="Delete this user?">
                                                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                                <button type="submit" class="admin-icon-btn is-danger" title="Delete" aria-label="Delete <?= e($user->name) ?>">
                                                    <?= icon('trash', 15) ?>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <footer class="admin-users-footer">
                <p>Showing <?= e((string) $total) ?> user<?= $total === 1 ? '' : 's' ?></p>
            </footer>
        <?php endif; ?>
    </div>
</section>
