<?php
require __DIR__ . '/includes/bootstrap.php';
require_role('user');

$statement = $pdo->prepare('SELECT id, recipient_name, shipping_address, courier, subtotal, shipping_fee, total, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$statement->execute([(int)current_user()['id']]);
$orders = $statement->fetchAll();
$itemQuery = $pdo->prepare('SELECT product_name, unit_price, quantity FROM order_items WHERE order_id = ?');
render_header('My orders');
?>
<h1>Your orders</h1>
<p>Order updates appear here after the seller reviews payment and confirms dispatch.</p>
<?php if (!$orders): ?><p class="empty-state">You have no orders yet. <a href="/html/products.php">Browse collections</a>.</p><?php endif; ?>
<?php foreach ($orders as $order): ?>
  <article class="order-card">
    <div class="order-heading"><h2>Order #<?= (int)$order['id'] ?></h2><span class="status"><?= h($order['status'] === 'awaiting_payment' ? 'Awaiting payment confirmation' : ($order['status'] === 'payment_confirmed' ? 'Payment confirmed' : 'In delivery')) ?></span></div>
    <p><strong>Recipient:</strong> <?= h($order['recipient_name']) ?></p>
    <p><strong>Shipping address:</strong> <?= nl2br(h($order['shipping_address'])) ?></p>
    <p><strong>Courier:</strong> <?= h($order['courier']) ?></p>
    <?php $itemQuery->execute([(int)$order['id']]); ?>
    <ul class="order-items">
      <?php foreach ($itemQuery->fetchAll() as $item): ?>
        <li><?= h($item['product_name']) ?> · <?= (int)$item['quantity'] ?> × <?= h(money((float)$item['unit_price'])) ?></li>
      <?php endforeach; ?>
    </ul>
    <p>Subtotal <?= h(money((float)$order['subtotal'])) ?> · Shipping <?= h(money((float)$order['shipping_fee'])) ?> · <strong>Total <?= h(money((float)$order['total'])) ?></strong></p>
    <p class="small-note">Placed <?= h($order['created_at']) ?></p>
  </article>
<?php endforeach; ?>
<?php render_footer(); ?>
