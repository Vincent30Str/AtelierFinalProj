<?php
require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_role('user');
    $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
    $statement = $pdo->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1');
    $statement->execute([$productId]);
    if (!$productId || !$statement->fetch()) {
        flash('That product is unavailable.', 'error');
    } else {
        $_SESSION['cart'] ??= [];
        $_SESSION['cart'][$productId] = min(99, (int)($_SESSION['cart'][$productId] ?? 0) + 1);
        flash('Added to your cart.');
    }
    redirect('/html/products.php');
}

$products = $pdo->query('SELECT id, name, description, price, image_path FROM products WHERE is_active = 1 ORDER BY created_at DESC, id DESC')->fetchAll();
render_header('Collections');
?>
<section class="hero">
  <p class="eyebrow">THE ATELIER COLLECTION</p>
  <h1>Objects of lasting elegance</h1>
  <p>Explore a curated collection of exceptional bags and accessories.</p>
</section>
<section class="product-grid">
  <?php if (!$products): ?><p>No products are available yet.</p><?php endif; ?>
  <?php foreach ($products as $product): ?>
    <article class="product-card">
      <img src="<?= h($product['image_path']) ?>" alt="<?= h($product['name']) ?>">
      <div class="product-copy">
        <h2><?= h($product['name']) ?></h2>
        <p><?= nl2br(h($product['description'])) ?></p>
        <div class="product-buy"><strong><?= h(money((float)$product['price'])) ?></strong>
          <?php if (current_user() && current_user()['role'] === 'user'): ?>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
              <button class="button" type="submit">Add to cart</button>
            </form>
          <?php elseif (!current_user()): ?>
            <a class="button" href="/html/login.php">Log in to shop</a>
          <?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</section>
<?php render_footer(); ?>
