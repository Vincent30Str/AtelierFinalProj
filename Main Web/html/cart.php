<?php
require __DIR__ . '/includes/bootstrap.php';
require_role('user');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'remove') {
        $id = (int)($_POST['product_id'] ?? 0);
        unset($_SESSION['cart'][$id]);
        flash('Item removed from your cart.');
        redirect('/html/cart.php');
    }
    if ($action === 'checkout') {
        $name = trim((string)($_POST['recipient_name'] ?? ''));
        $address = trim((string)($_POST['shipping_address'] ?? ''));
        $courier = trim((string)($_POST['courier'] ?? ''));
        $fees = ['LionParcel (Same Day)' => 15.00, 'JNE (2-3 Days)' => 10.00, 'Regular Economy (4-7 Days)' => 5.00];
        if (($_POST['payment_confirmed_by_customer'] ?? '') !== 'yes' || $name === '' || strlen($name) > 120 || $address === '' || strlen($address) > 1000 || !isset($fees[$courier])) {
            flash('Confirm payment and enter valid shipping details with a courier selection.', 'error');
            redirect('/html/cart.php');
        }
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) {
            flash('Your cart is empty.', 'error');
            redirect('/html/cart.php');
        }
        try {
            $pdo->beginTransaction();
            $ids = array_map('intval', array_keys($cart));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $query = $pdo->prepare("SELECT id, name, price FROM products WHERE is_active = 1 AND id IN ({$placeholders})");
            $query->execute($ids);
            $products = $query->fetchAll();
            if (count($products) !== count($ids)) {
                throw new RuntimeException('One or more cart products are no longer available.');
            }
            $subtotal = 0.0;
            foreach ($products as $product) {
                $subtotal += (float)$product['price'] * (int)$cart[$product['id']];
            }
            $insert = $pdo->prepare('INSERT INTO orders (user_id, recipient_name, shipping_address, courier, subtotal, shipping_fee, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, "awaiting_payment")');
            $shippingFee = $fees[$courier];
            $insert->execute([(int)current_user()['id'], $name, $address, $courier, $subtotal, $shippingFee, $subtotal + $shippingFee]);
            $orderId = (int)$pdo->lastInsertId();
            $itemInsert = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity) VALUES (?, ?, ?, ?, ?)');
            foreach ($products as $product) {
                $itemInsert->execute([$orderId, (int)$product['id'], $product['name'], (float)$product['price'], (int)$cart[$product['id']]]);
            }
            $pdo->commit();
            unset($_SESSION['cart']);
            flash('Order placed. Your payment confirmation is ready for seller review.');
            redirect('/html/order-status.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception instanceof RuntimeException) {
                flash($exception->getMessage(), 'error');
                redirect('/html/cart.php');
            }
            throw $exception;
        }
    }
}

$cart = $_SESSION['cart'] ?? [];
$items = [];
$subtotal = 0.0;
if ($cart) {
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare("SELECT id, name, price, image_path FROM products WHERE is_active = 1 AND id IN ({$placeholders})");
    $statement->execute($ids);
    $items = $statement->fetchAll();
    foreach ($items as $item) {
        $subtotal += (float)$item['price'] * (int)$cart[$item['id']];
    }
}
render_header('Your cart');
?>
<h1>Your shopping cart</h1>
<?php if (!$items): ?>
  <p class="empty-state">Your cart is empty. <a href="/html/products.php">Explore the collection</a>.</p>
<?php else: ?>
  <div class="cart-layout">
    <section>
      <?php foreach ($items as $item): ?>
        <article class="cart-item">
          <img src="<?= h($item['image_path']) ?>" alt="">
          <div><h2><?= h($item['name']) ?></h2><p><?= (int)$cart[$item['id']] ?> × <?= h(money((float)$item['price'])) ?></p></div>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
            <button class="button secondary" type="submit">Remove</button>
          </form>
        </article>
      <?php endforeach; ?>
    </section>
    <section class="checkout-card">
      <h2>Shipping and payment</h2>
      <p>Subtotal: <strong><?= h(money($subtotal)) ?></strong></p>
      <p>Shipping is added based on the courier you select.</p>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="checkout">
        <label>Recipient name<input name="recipient_name" maxlength="120" value="<?= h(current_user()['name']) ?>" required></label>
        <label>Shipping address<textarea name="shipping_address" rows="4" maxlength="1000" required></textarea></label>
        <label>Courier
          <select name="courier" required>
            <option value="">Select a courier</option>
            <option>LionParcel (Same Day)</option>
            <option>JNE (2-3 Days)</option>
            <option>Regular Economy (4-7 Days)</option>
          </select>
        </label>
        <p class="small-note">Demo checkout only: submitting confirms that you have made the payment. No card or bank details are collected; a seller must verify receipt before the order is marked payment confirmed.</p>
        <label class="checkbox-label"><input type="checkbox" name="payment_confirmed_by_customer" value="yes" required> I have made the payment and confirm this order.</label>
        <button class="button" type="submit">Place order and confirm payment</button>
      </form>
    </section>
  </div>
<?php endif; ?>
<?php render_footer(); ?>
