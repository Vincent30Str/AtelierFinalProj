<?php
require dirname(__DIR__) . '/html/includes/bootstrap.php';
require_role('seller');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($name === '' || strlen($name) > 180) {
        $errors[] = 'Product name is required and must be 180 characters or fewer.';
    }
    if ($description === '' || strlen($description) > 5000) {
        $errors[] = 'Description is required and must be 5,000 characters or fewer.';
    }
    if ($price === false || $price <= 0 || $price > 999999999) {
        $errors[] = 'Enter a valid positive price.';
    }
    $image = $_FILES['image'] ?? null;
    if (!$image || $image['error'] !== UPLOAD_ERR_OK || $image['size'] > 5 * 1024 * 1024) {
        $errors[] = 'Choose an image smaller than 5 MB.';
    }
    $mime = '';
    if ($image && $image['error'] === UPLOAD_ERR_OK && $image['size'] <= 5 * 1024 * 1024) {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fileInfo->file($image['tmp_name']) ?: '';
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $errors[] = 'Images must be JPG, PNG, or WebP.';
        }
    }
    if (!$errors) {
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $uploadDir = dirname(__DIR__) . '/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Could not create the product upload directory.');
        }
        $fileName = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($image['tmp_name'], $uploadDir . '/' . $fileName)) {
            throw new RuntimeException('Could not save the uploaded product image.');
        }
        $imagePath = '/uploads/' . $fileName;
        $statement = $pdo->prepare('INSERT INTO products (seller_id, name, description, price, image_path) VALUES (?, ?, ?, ?, ?)');
        $statement->execute([(int)current_user()['id'], $name, $description, $price, $imagePath]);
        flash('Product added to the collection.');
        redirect('/seller/products.php');
    }
}

$products = $pdo->prepare('SELECT id, name, price, image_path, is_active FROM products WHERE seller_id = ? ORDER BY created_at DESC');
$products->execute([(int)current_user()['id']]);
render_header('Manage products');
?>
<p><a href="/seller/">← Seller dashboard</a></p>
<h1>Add a product</h1>
<?php foreach ($errors as $error): ?><div class="notice error"><?= h($error) ?></div><?php endforeach; ?>
<section class="form-card">
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <label>Product name<input name="name" maxlength="180" required></label>
    <label>Description<textarea name="description" rows="5" maxlength="5000" required></textarea></label>
    <label>Price (USD)<input type="number" name="price" min="0.01" step="0.01" required></label>
    <label>Product image (JPG, PNG, or WebP; max 5 MB)<input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label>
    <button class="button" type="submit">Publish product</button>
  </form>
</section>
<h2>Your products</h2>
<div class="seller-product-list">
  <?php foreach ($products as $product): ?>
    <article class="seller-product">
      <img src="<?= h($product['image_path']) ?>" alt="">
      <div><strong><?= h($product['name']) ?></strong><p><?= h(money((float)$product['price'])) ?> · <?= $product['is_active'] ? 'Active' : 'Hidden' ?></p></div>
    </article>
  <?php endforeach; ?>
</div>
<?php render_footer(); ?>
