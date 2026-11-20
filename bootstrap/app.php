<?php

declare(strict_types=1);

use App\Controller\AccountController;
use App\Controller\Admin\DashboardController;
use App\Controller\Admin\NotificationsController;
use App\Controller\Api\AuthApiController;
use App\Controller\Api\ProductsApiController;
use App\Controller\AuthController;
use App\Controller\CategoriesController;
use App\Controller\ProductsController;
use App\Controller\TransactionsController;
use App\Controller\UsersController;
use App\Core\Config;
use App\Core\Container;
use App\Core\Database\Connection;
use App\Core\Http\Router;
use App\Core\Http\Url;
use App\Core\Http\View;
use App\Core\Kernel;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\PdoCategoryRepository;
use App\Repository\PdoProductRepository;
use App\Repository\PdoTransactionRepository;
use App\Repository\PdoUserRepository;
use App\Repository\ProductRepositoryInterface;
use App\Repository\TransactionRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Security\Https;
use App\Security\RateLimiter;
use App\Service\AuthService;
use App\Service\AuthServiceInterface;
use App\Service\PurchaseService;
use App\Service\PurchaseServiceInterface;

$root = dirname(__DIR__);
loadEnv($root);

$appEnv = strtolower((string) ($_ENV['APP_ENV'] ?? 'local'));
$appDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
$forceHttps = ($_ENV['FORCE_HTTPS'] ?? ($appEnv === 'production' ? 'true' : 'false')) === 'true';
$jwtSecret = (string) ($_ENV['JWT_SECRET'] ?? '');
$jwtTtl = max(300, (int) ($_ENV['JWT_TTL'] ?? 3600));

assertSecureConfig($appEnv, $appDebug, $jwtSecret);

$config = new Config([
    'app' => [
        'env' => $appEnv,
        'url' => $_ENV['APP_URL'] ?? 'http://localhost:8080',
        'debug' => $appEnv === 'production' ? false : $appDebug,
    ],
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'name' => $_ENV['DB_NAME'] ?? 'vending_machine',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
        'ssl' => ($_ENV['DB_SSL'] ?? 'false') === 'true',
        'ssl_ca' => $_ENV['DB_SSL_CA'] ?? '/etc/ssl/certs/ca-certificates.crt',
    ],
    'jwt' => [
        'secret' => $jwtSecret !== '' ? $jwtSecret : 'local-dev-only-change-before-deploy-32chars',
        'ttl' => $jwtTtl,
    ],
    'security' => [
        'force_https' => $forceHttps,
        'cors_origins' => parseList($_ENV['CORS_ALLOWED_ORIGINS'] ?? ''),
        'trusted_proxies' => parseList($_ENV['TRUSTED_PROXIES'] ?? ''),
    ],
]);

$base = requestBasePath();
$trustedProxies = $config->get('security.trusted_proxies', []);
$trustedProxies = is_array($trustedProxies) ? array_values(array_filter(array_map('strval', $trustedProxies))) : [];
$secureCookie = requestIsHttps($trustedProxies);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $secureCookie ? '1' : '0');
    session_name('vendhub_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $base === '' ? '/' : $base,
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf']) || strlen($_SESSION['csrf']) < 32) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$container = new Container();
$container->set(Config::class, static fn (): Config => $config);
$container->set(Url::class, static fn (): Url => new Url($base));
$container->set(View::class, static fn (): View => new View($root . '/views'));
$container->set(RateLimiter::class, static fn (): RateLimiter => new RateLimiter($root . '/var/rate-limit'));
$container->set(Connection::class, static function () use ($config): Connection {
    $db = $config->get('db');
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $db['host'],
        $db['port'],
        $db['name'],
    );
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if (($db['ssl'] ?? false) === true) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = (string) $db['ssl_ca'];
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }
    $pdo = new PDO($dsn, (string) $db['user'], (string) $db['pass'], $options);

    return new Connection($pdo);
});
$container->set(ProductRepositoryInterface::class, static fn (Container $c): PdoProductRepository => $c->get(PdoProductRepository::class));
$container->set(CategoryRepositoryInterface::class, static fn (Container $c): PdoCategoryRepository => $c->get(PdoCategoryRepository::class));
$container->set(UserRepositoryInterface::class, static fn (Container $c): PdoUserRepository => $c->get(PdoUserRepository::class));
$container->set(TransactionRepositoryInterface::class, static fn (Container $c): PdoTransactionRepository => $c->get(PdoTransactionRepository::class));
$container->set(AuthServiceInterface::class, static fn (Container $c): AuthService => $c->get(AuthService::class));
$container->set(PurchaseServiceInterface::class, static fn (Container $c): PurchaseService => $c->get(PurchaseService::class));

$router = new Router($container);
$router->register(AuthController::class);
$router->register(AccountController::class);
$router->register(DashboardController::class);
$router->register(NotificationsController::class);
$router->register(ProductsController::class);
$router->register(CategoriesController::class);
$router->register(UsersController::class);
$router->register(TransactionsController::class);
$router->register(AuthApiController::class);
$router->register(ProductsApiController::class);

return new Kernel($router, $container->get(View::class), $config, $base);

function loadEnv(string $root): void
{
    $env = $root . '/.env';
    $lines = is_file($env) ? file($env, FILE_IGNORE_NEW_LINES) : [];
    $lines = $lines === false ? [] : $lines;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $value = trim($value, " \t\"'");
        $key = trim($key);
        if (getenv($key) === false) {
            $_ENV[$key] = $value;
        }
    }

    foreach ([
        'APP_ENV', 'APP_URL', 'APP_DEBUG', 'FORCE_HTTPS',
        'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_SSL', 'DB_SSL_CA',
        'JWT_SECRET', 'JWT_TTL', 'CORS_ALLOWED_ORIGINS', 'TRUSTED_PROXIES',
    ] as $key) {
        $value = getenv($key);
        if ($value !== false) {
            $_ENV[$key] = $value;
        }
    }
}

function requestBasePath(): string
{
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script === '/' || $script === '.' || $script === '') {
        return '';
    }

    return rtrim($script, '/');
}

/** @return list<string> */
function parseList(string $value): array
{
    if (trim($value) === '') {
        return [];
    }

    return array_values(array_filter(array_map(
        static fn (string $item): string => trim($item),
        explode(',', $value),
    )));
}

/** @param list<string> $trustedProxies */
function requestIsHttps(array $trustedProxies): bool
{
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (!is_string($key) || !is_string($value)) {
            continue;
        }
        if (str_starts_with($key, 'HTTP_')) {
            $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
        }
    }

    $request = new App\Core\Http\Request('GET', '/', [], [], $headers, $_SERVER);

    return Https::isSecure($request, $trustedProxies);
}

function assertSecureConfig(string $appEnv, bool $appDebug, string $jwtSecret): void
{
    $weakSecrets = [
        '',
        'local-dev-only-change-before-deploy',
        'change-me',
        'secret',
        'jwt-secret',
    ];

    if ($appEnv !== 'production') {
        return;
    }

    if ($appDebug) {
        throw new RuntimeException('APP_DEBUG must be false when APP_ENV=production.');
    }

    if (in_array($jwtSecret, $weakSecrets, true) || strlen($jwtSecret) < 32) {
        throw new RuntimeException('JWT_SECRET must be a strong random value of at least 32 characters in production.');
    }
}
