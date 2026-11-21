<?php
require __DIR__ . '/includes/bootstrap.php';

$errors = [];
$name = '';
$email = '';
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
            $statement = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "user")');
            $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            flash('Your account is ready. Please log in.');
            redirect('/html/login.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'An account already uses that email address.';
            } else {
                throw $exception;
            }
        }
    }
}

render_header('Create account');
?>
<section class="auth-card">
  <h1>Create your Atelier account</h1>
  <p>Register as a customer to shop and track your orders.</p>
  <?php foreach ($errors as $error): ?><div class="notice error"><?= h($error) ?></div><?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <label>Full name<input name="name" value="<?= h($name) ?>" maxlength="120" required></label>
    <label>Email<input type="email" name="email" value="<?= h($email) ?>" maxlength="190" required></label>
    <label>Password (at least 8 characters)<input type="password" name="password" minlength="8" required></label>
    <button class="button" type="submit">Create account</button>
  </form>
  <p>Already registered? <a href="/html/login.php">Log in</a></p>
</section>
<?php render_footer(); ?>
