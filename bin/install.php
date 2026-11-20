<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$env = [];
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $env[trim($key)] = trim($value, " \t\"'");
}

foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_SSL', 'DB_SSL_CA'] as $key) {
    $value = getenv($key);
    if ($value !== false) {
        $env[$key] = $value;
    }
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$name = $env['DB_NAME'] ?? 'vending_machine';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';
$ssl = ($env['DB_SSL'] ?? 'false') === 'true';
$sslCa = $env['DB_SSL_CA'] ?? '/etc/ssl/certs/ca-certificates.crt';
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
if ($ssl) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

try {
    $server = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name),
        $user,
        $pass,
        $options,
    );
} catch (PDOException $exception) {
    try {
        $server = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port),
            $user,
            $pass,
            $options,
        );
        $server->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            str_replace('`', '', $name),
        ));
        $server->exec(sprintf('USE `%s`', str_replace('`', '', $name)));
    } catch (PDOException) {
        fwrite(STDERR, "Cannot connect to the configured MySQL database.\n");
        fwrite(STDERR, $exception->getMessage() . "\n");
        exit(1);
    }
}

runSql($server, (string) file_get_contents($root . '/database/schema.sql'));
ensureProductCategoryColumn($server);
ensureProductReorderPointColumn($server);
ensureCategoriesTable($server);

$users = (int) $server->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($users === 0) {
    runSql($server, (string) file_get_contents($root . '/database/seed.sql'));
    echo "Seeded admin@vending.test / Admin12345 and user@vending.test / User12345\n";
} else {
    echo "Users already exist, seed skipped.\n";
    seedMissingCategories($server);
}

echo "Database `{$name}` is ready.\n";

function ensureProductCategoryColumn(PDO $pdo): void
{
    $column = $pdo->query("SHOW COLUMNS FROM products LIKE 'category'")->fetch(PDO::FETCH_ASSOC);
    if ($column !== false) {
        return;
    }

    $pdo->exec("ALTER TABLE products ADD COLUMN category VARCHAR(50) NOT NULL DEFAULT 'Drink' AFTER quantity_available");
    $pdo->exec("UPDATE products SET category = 'Snack' WHERE slug IN ('kitkat', 'lays', 'oreo')");
    echo "Added products.category column.\n";
}

function ensureProductReorderPointColumn(PDO $pdo): void
{
    $column = $pdo->query("SHOW COLUMNS FROM products LIKE 'reorder_point'")->fetch(PDO::FETCH_ASSOC);
    if ($column !== false) {
        return;
    }

    $pdo->exec("ALTER TABLE products ADD COLUMN reorder_point INT UNSIGNED NOT NULL DEFAULT 5 AFTER quantity_available");
    echo "Added products.reorder_point column.\n";
}

function ensureCategoriesTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS categories (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL,
            slug VARCHAR(60) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY categories_name_unique (name),
            UNIQUE KEY categories_slug_unique (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    );
}

function seedMissingCategories(PDO $pdo): void
{
    $existing = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    if ($existing === 0) {
        $pdo->exec("INSERT INTO categories (name, slug) VALUES ('Drink', 'drink'), ('Snack', 'snack')");
        echo "Seeded default categories.\n";
    }

    $names = $pdo->query('SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category <> ""')->fetchAll(PDO::FETCH_COLUMN);
    $insert = $pdo->prepare('INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)');
    foreach ($names as $categoryName) {
        $categoryName = trim((string) $categoryName);
        if ($categoryName === '') {
            continue;
        }
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $categoryName) ?? '');
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'category';
        }
        $insert->execute([$categoryName, $slug]);
    }
}

function runSql(PDO $pdo, string $sql): void
{
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}
