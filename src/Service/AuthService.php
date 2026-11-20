<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ValidationException;
use App\Model\User;
use App\Repository\UserRepositoryInterface;

final class AuthService implements AuthServiceInterface
{
    private const DUMMY_HASH = '$2y$10$lJZQw4cML8MeVlsUhsHFNu/wjMUSQmXdPc0ErdD9lof12zvkvh81m';

    public function __construct(private UserRepositoryInterface $users)
    {
    }

    public function user(): ?User
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
            return null;
        }

        return $this->users->findById((int) $id);
    }

    public function attempt(string $username, string $password): bool
    {
        $user = $this->verify($username, $password);
        if ($user === null || $user->id === null) {
            return false;
        }

        $this->login($user);

        return true;
    }

    public function verify(string $username, string $password): ?User
    {
        $user = $this->users->findByUsername($this->normalizeUsername($username));
        $hash = $user->passwordHash ?? self::DUMMY_HASH;
        $valid = password_verify($password, $hash);

        if ($user === null || !$valid) {
            return null;
        }

        return $user;
    }

    public function logout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
        $this->rotateCsrf();
    }

    public function register(string $name, string $username, string $password): User
    {
        $username = $this->normalizeUsername($username);
        if ($this->users->findByUsername($username) !== null) {
            throw new ValidationException(['username' => 'This username is already taken.']);
        }

        $user = $this->users->create($name, $username, password_hash($password, PASSWORD_DEFAULT), 'user');
        $this->login($user);

        return $user;
    }

    public function updateProfile(User $user, string $name, string $username): User
    {
        $username = $this->normalizeUsername($username);
        $existing = $this->users->findByUsername($username);
        if ($existing !== null && (int) $existing->id !== (int) $user->id) {
            throw new ValidationException(['username' => 'This username is already taken.']);
        }

        $updated = new User((int) $user->id, trim($name), $username, $user->passwordHash, $user->role);
        $this->users->update($updated);

        return $updated;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!password_verify($currentPassword, $user->passwordHash)) {
            throw new ValidationException(['current_password' => 'Current password is incorrect.']);
        }

        $this->users->updatePassword((int) $user->id, password_hash($newPassword, PASSWORD_DEFAULT));
    }

    private function login(User $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->id;
        $this->rotateCsrf();
    }

    private function rotateCsrf(): void
    {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    private function normalizeUsername(string $username): string
    {
        return strtolower(trim($username));
    }
}
