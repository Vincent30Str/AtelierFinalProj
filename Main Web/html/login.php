<?php
require __DIR__ . '/includes/bootstrap.php';

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $statement = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ?');
    $statement->execute([$email]);
    $user = $statement->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        redirect($user['role'] === 'seller' ? '/seller/' : '/html/products.php');
    }
    $error = 'Email or password is incorrect.';
}

render_header('Log in');
?>
<section class="auth-card">
  <h1>Welcome back</h1>
  <p>Sign in with your customer or seller account.</p>
  <?php if ($error): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <label>Email<input type="email" name="email" value="<?= h($email) ?>" autocomplete="email" required></label>
    <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
    <button class="button" type="submit">Log in</button>
  </form>
  <p>New to Atelier? <a href="/html/register.php">Create a customer account</a>.</p>
</section>
<?php render_footer(); ?>
