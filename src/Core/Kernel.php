<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Router;
use App\Core\Http\View;
use App\Exception\AuthenticationException;
use App\Exception\AuthorizationException;
use App\Exception\NotFoundException;
use App\Exception\TooManyRequestsException;
use App\Exception\ValidationException;
use App\Security\Https;
use Throwable;

final class Kernel
{
    public function __construct(
        private Router $router,
        private View $view,
        private Config $config,
        private string $basePath,
    ) {
    }

    public function handle(?Request $request = null): Response
    {
        $request ??= Request::capture($this->basePath);
        $trusted = $this->trustedProxies();

        if ($this->config->get('security.force_https') === true && !Https::isSecure($request, $trusted)) {
            return $this->finalize(
                Response::redirect($this->httpsUrl($request))->withHeaders(['Cache-Control' => 'no-store']),
                $request,
            );
        }

        if ($request->method === 'OPTIONS' && $request->wantsJson()) {
            $cors = $this->corsHeaders($request);
            if ($cors === null) {
                return $this->finalize(new Response('', 403), $request);
            }

            return $this->finalize(new Response('', 204, $cors), $request);
        }

        try {
            $response = $this->router->dispatch($request);
        } catch (NotFoundException $exception) {
            $response = $this->error(404, $exception->getMessage(), $request);
        } catch (AuthorizationException $exception) {
            $response = $this->error(403, $exception->getMessage(), $request);
        } catch (AuthenticationException $exception) {
            if ($request->wantsJson()) {
                $response = Response::json(['message' => $exception->getMessage()], 401);
            } else {
                $login = str_starts_with($request->path, '/admin') ? '/admin/login' : '/login';
                $response = Response::redirect(\App\Core\Http\Url::to($login));
            }
        } catch (TooManyRequestsException $exception) {
            $retry = max(1, $exception->retryAfter);
            if ($request->wantsJson()) {
                $response = Response::json(['message' => $exception->getMessage()], 429)
                    ->withHeaders(['Retry-After' => (string) $retry]);
            } else {
                $response = $this->error(429, $exception->getMessage(), $request)
                    ->withHeaders(['Retry-After' => (string) $retry]);
            }
        } catch (ValidationException $exception) {
            $response = Response::json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ], 422);
        } catch (Throwable $exception) {
            $this->log($exception);
            $message = $this->config->get('app.debug') === true
                ? $exception->getMessage()
                : 'Something went wrong.';
            $response = $this->error(500, $message, $request);
        }

        return $this->finalize($response, $request);
    }

    private function finalize(Response $response, Request $request): Response
    {
        $headers = $this->securityHeaders($request);
        if ($request->wantsJson()) {
            $cors = $this->corsHeaders($request);
            if ($cors !== null) {
                $headers = $cors + $headers;
            }
        }

        return $response->withHeaders($headers);
    }

    private function error(int $status, string $message, Request $request): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['message' => $message], $status);
        }

        $template = match ($status) {
            403 => 'errors/403',
            404 => 'errors/404',
            429 => 'errors/429',
            default => 'errors/500',
        };

        $title = match ($status) {
            403 => 'Forbidden',
            404 => 'Page not found',
            429 => 'Too many requests',
            default => 'Something went wrong',
        };

        return Response::html($this->view->render($template, [
            'title' => $title,
            'message' => $message !== '' ? $message : $title,
            'layout' => 'errors/layout',
            'activeNav' => '',
        ]), $status);
    }

    /** @return array<string, string>|null */
    private function corsHeaders(Request $request): ?array
    {
        $allowed = $this->config->get('security.cors_origins', []);
        if (!is_array($allowed) || $allowed === []) {
            return null;
        }

        $origins = array_values(array_filter(array_map(
            static fn (mixed $origin): string => rtrim(trim((string) $origin), '/'),
            $allowed,
        )));
        if ($origins === []) {
            return null;
        }

        $requestOrigin = rtrim(trim((string) ($request->headers['origin'] ?? '')), '/');
        if ($requestOrigin === '' || !in_array($requestOrigin, $origins, true)) {
            return null;
        }

        return [
            'Access-Control-Allow-Origin' => $requestOrigin,
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Credentials' => 'true',
            'Vary' => 'Origin',
        ];
    }

    /** @return array<string, string> */
    private function securityHeaders(Request $request): array
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Content-Security-Policy' => implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
                "object-src 'none'",
                "img-src 'self' data: blob:",
                "font-src 'self' data: https://fonts.gstatic.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "script-src 'self' 'nonce-" . csp_nonce() . "' https://cdn.jsdelivr.net",
                "script-src-attr 'none'",
                "connect-src 'self'",
            ]),
        ];

        if (Https::isSecure($request, $this->trustedProxies())) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        return $headers;
    }

    private function httpsUrl(Request $request): string
    {
        $host = (string) ($request->server['HTTP_HOST'] ?? 'localhost');
        $uri = (string) ($request->server['REQUEST_URI'] ?? '/');

        return 'https://' . $host . $uri;
    }

    /** @return list<string> */
    private function trustedProxies(): array
    {
        $trusted = $this->config->get('security.trusted_proxies', []);
        if (!is_array($trusted)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $trusted)));
    }

    private function log(Throwable $exception): void
    {
        // Render captures stderr. Keep a safe diagnostic in its logs without
        // exposing SQL statements, request data, credentials, or stack traces.
        $driverCode = $exception instanceof \PDOException
            ? (string) ($exception->errorInfo[1] ?? 'unknown')
            : 'n/a';
        error_log(sprintf(
            'Application error: %s (code: %s, driver: %s)',
            $exception::class,
            (string) $exception->getCode(),
            $driverCode,
        ));

        $directory = dirname(__DIR__, 2) . '/var';
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        file_put_contents(
            $directory . '/app.log',
            date('c') . ' ' . $exception . PHP_EOL,
            FILE_APPEND,
        );
    }
}
