<?php
require dirname(__DIR__) . '/html/includes/bootstrap.php';
require_role('seller');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if ($name === '' || strlen($name) > 120) {
        $errors[] = 'Enter a name of up to 120 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (!$errors) {
        try {
            $statement = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "seller")');
            $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            flash('Seller account created. They can now log in with their email and password.');
            redirect('/seller/accounts.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'An account already uses that email address.';
            } else {
                throw $exception;
            }
        }
    }
}

render_header('Create seller account');
?>
<p><a href="/seller/">← Seller dashboard</a></p>
<section class="auth-card">
  <h1>Create another seller account</h1>
  <p>Only a signed-in seller can create seller accounts.</p>
  <?php foreach ($errors as $error): ?><div class="notice error"><?= h($error) ?></div><?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <label>Full name<input name="name" maxlength="120" required></label>
    <label>Email<input type="email" name="email" maxlength="190" required></label>
    <label>Password (at least 8 characters)<input type="password" name="password" minlength="8" required></label>
    <button class="button" type="submit">Create seller account</button>
  </form>
</section>
<?php render_footer(); ?>
