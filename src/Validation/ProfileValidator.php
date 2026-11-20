<?php

declare(strict_types=1);

namespace App\Validation;

final class ProfileValidator
{
    /** @param array<string, mixed> $input
     *  @return array<string, string>
     */
    public function validate(array $input): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $username = trim((string) ($input['username'] ?? ''));
        $current = (string) ($input['current_password'] ?? '');
        $password = (string) ($input['password'] ?? '');
        $confirmation = (string) ($input['password_confirmation'] ?? '');
        $changingPassword = $current !== '' || $password !== '' || $confirmation !== '';

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        } elseif (contains_html($name)) {
            $errors['name'] = 'Name cannot contain HTML.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Name must be 100 characters or fewer.';
        }

        if ($username === '') {
            $errors['username'] = 'Username is required.';
        } elseif (preg_match('/^[a-zA-Z0-9_-]{3,32}$/', $username) !== 1) {
            $errors['username'] = 'Username must be 3-32 letters, numbers, hyphens, or underscores.';
        }

        if ($changingPassword) {
            if ($current === '') {
                $errors['current_password'] = 'Current password is required.';
            }
            $passwordError = PasswordStrength::error($password);
            if ($passwordError !== null) {
                $errors['password'] = $passwordError;
            }
            if ($password !== $confirmation) {
                $errors['password_confirmation'] = 'Password confirmation does not match.';
            }
        }

        return $errors;
    }
}
