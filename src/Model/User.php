<?php

declare(strict_types=1);

namespace App\Model;

final class User
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $username,
        public string $passwordHash,
        public string $role,
    ) {
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
