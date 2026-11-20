<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\User;

interface UserRepositoryInterface
{
    public function create(string $name, string $username, string $passwordHash, string $role): User;

    public function findById(int $id): ?User;

    public function findByUsername(string $username): ?User;

    /** @return list<User> */
    public function all(): array;

    public function update(User $user): void;

    public function updatePassword(int $id, string $passwordHash): void;

    public function delete(int $id): void;

    public function countByRole(string $role): int;
}
