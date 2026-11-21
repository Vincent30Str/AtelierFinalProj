<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

$dbHost = getenv('ATELIER_DB_HOST') ?: '127.0.0.1';
$dbName = getenv('ATELIER_DB_NAME') ?: 'atelier';
$dbUser = getenv('ATELIER_DB_USER') ?: 'root';
$dbPass = getenv('ATELIER_DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Database connection failed. Check the MySQL settings and setup instructions in README.md.');
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_role(string $role): void
{
    $user = current_user();
    if (!$user) {
        redirect('/html/login.php');
    }
    if ($user['role'] !== $role) {
        redirect($user['role'] === 'seller' ? '/seller/' : '/html/products.php');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(400);
        exit('Invalid form token. Please go back, refresh, and try again.');
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function render_header(string $title): void
{
    $user = current_user();
    $cartCount = array_sum($_SESSION['cart'] ?? []);
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?= h($title) ?> | Atelier</title>
      <link rel="stylesheet" href="/styles/store.css">
    </head>
    <body>
    <header class="site-header">
      <a class="brand" href="/html/products.php">ATELIER</a>
      <nav aria-label="Main navigation">
        <a href="/html/products.php">Collections</a>
        <?php if ($user && $user['role'] === 'user'): ?>
          <a href="/html/cart.php">Cart (<?= (int)$cartCount ?>)</a>
          <a href="/html/order-status.php">My orders</a>
        <?php endif; ?>
        <?php if ($user && $user['role'] === 'seller'): ?>
          <a href="/seller/">Seller dashboard</a>
        <?php endif; ?>
        <?php if ($user): ?>
          <span class="nav-user"><?= h($user['name']) ?></span>
          <form class="nav-form" method="post" action="/html/logout.php">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <button class="nav-button" type="submit">Log out</button>
          </form>
        <?php else: ?>
          <a href="/html/login.php">Log in</a>
          <a href="/html/register.php">Create account</a>
        <?php endif; ?>
      </nav>
    </header>
    <main class="page">
    <?php
    if (!empty($_SESSION['flash'])) {
        $notice = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="notice ' . h($notice['type']) . '">' . h($notice['message']) . '</div>';
    }
}

function render_footer(): void
{
    echo '</main><footer class="site-footer">Atelier · Luxury, thoughtfully curated</footer></body></html>';
}

function money(float $amount): string
{
    return '$' . number_format($amount, 2);
}
