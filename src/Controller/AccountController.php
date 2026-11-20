<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Attribute\Route;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Url;
use App\Core\Http\View;
use App\Exception\ValidationException;
use App\Middleware\RequireSession;
use App\Middleware\VerifyCsrf;
use App\Repository\TransactionRepositoryInterface;
use App\Service\AuthServiceInterface;
use App\Validation\ProfileValidator;

final class AccountController extends WebController
{
    public function __construct(
        private TransactionRepositoryInterface $transactions,
        private ProfileValidator $profileValidator,
        AuthServiceInterface $auth,
        View $view,
        Url $url,
    ) {
        parent::__construct($auth, $view, $url);
    }

    #[Route('/purchases', methods: 'GET', middleware: [RequireSession::class])]
    public function purchases(Request $request): Response
    {
        if ($redirect = $this->requireUser()) {
            return $redirect;
        }

        $user = $this->auth->user();

        return $this->render('account/purchases', [
            'title' => 'My Purchases',
            'activeNav' => 'purchases',
            'transactions' => $this->transactions->forUser((int) $user->id),
        ]);
    }

    #[Route('/profile', methods: 'GET', middleware: [RequireSession::class])]
    public function profile(Request $request): Response
    {
        if ($redirect = $this->requireUser()) {
            return $redirect;
        }

        return $this->render('account/profile', [
            'title' => 'Profile',
            'activeNav' => 'profile',
            'profileUser' => $this->auth->user(),
        ]);
    }

    #[Route('/profile', methods: 'POST', middleware: [RequireSession::class, VerifyCsrf::class])]
    public function updateProfile(Request $request): Response
    {
        if ($redirect = $this->requireUser()) {
            return $redirect;
        }

        $user = $this->auth->user();
        $input = [
            'name' => (string) $request->input('name', ''),
            'username' => (string) $request->input('username', ''),
            'current_password' => (string) $request->input('current_password', ''),
            'password' => (string) $request->input('password', ''),
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
        ];

        $errors = $this->profileValidator->validate($input);
        if ($errors !== []) {
            return $this->render('account/profile', [
                'title' => 'Profile',
                'activeNav' => 'profile',
                'profileUser' => $user,
                'errors' => $errors,
                'old' => $input,
            ], 422);
        }

        try {
            $updated = $this->auth->updateProfile($user, $input['name'], $input['username']);
            $changingPassword = $input['current_password'] !== '' || $input['password'] !== '';
            if ($changingPassword) {
                $this->auth->changePassword($updated, $input['current_password'], $input['password']);
            }
        } catch (ValidationException $exception) {
            return $this->render('account/profile', [
                'title' => 'Profile',
                'activeNav' => 'profile',
                'profileUser' => $user,
                'errors' => $exception->errors,
                'old' => $input,
            ], 422);
        }

        $this->flash($changingPassword ? 'Profile and password updated.' : 'Profile updated.');

        return $this->redirect('/profile');
    }
}
