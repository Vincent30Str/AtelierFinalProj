<?php
require dirname(__DIR__) . '/html/includes/bootstrap.php';
require_role('seller');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
    if ($action === 'confirm_payment' && $orderId) {
        $statement = $pdo->prepare('UPDATE orders SET status = "payment_confirmed" WHERE id = ? AND status = "awaiting_payment"');
        $statement->execute([$orderId]);
        flash($statement->rowCount() ? "Payment confirmed for order #{$orderId}." : 'That order is not awaiting payment confirmation.', $statement->rowCount() ? 'success' : 'error');
    } elseif ($action === 'confirm_shipping' && $orderId) {
        $statement = $pdo->prepare('UPDATE orders SET status = "in_delivery" WHERE id = ? AND status = "payment_confirmed"');
        $statement->execute([$orderId]);
        flash($statement->rowCount() ? "Order #{$orderId} is now in delivery." : 'Confirm payment before shipping this order.', $statement->rowCount() ? 'success' : 'error');
    } else {
        flash('Invalid order action.', 'error');
    }
    redirect('/seller/');
}

$orders = $pdo->query('SELECT o.id, o.recipient_name, o.shipping_address, o.courier, o.total, o.status, o.created_at, u.email FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC')->fetchAll();
render_header('Seller dashboard');
?>
<div class="dashboard-title">
  <div><p class="eyebrow">ATELIER SELLER</p><h1>Seller dashboard</h1></div>
  <div class="dashboard-links"><a class="button" href="/seller/products.php">Add product</a><a class="button secondary" href="/seller/accounts.php">Create seller account</a></div>
</div>
<h2>Customer orders</h2>
<?php if (!$orders): ?><p class="empty-state">No customer orders yet.</p><?php endif; ?>
<?php foreach ($orders as $order): ?>
  <article class="order-card">
    <div class="order-heading"><h3>Order #<?= (int)$order['id'] ?></h3><span class="status"><?= h(str_replace('_', ' ', $order['status'])) ?></span></div>
    <p><strong>Customer:</strong> <?= h($order['recipient_name']) ?> (<?= h($order['email']) ?>)</p>
    <p><strong>Ship to:</strong> <?= nl2br(h($order['shipping_address'])) ?></p>
    <p><strong>Courier:</strong> <?= h($order['courier']) ?> · <strong>Total:</strong> <?= h(money((float)$order['total'])) ?></p>
    <div class="inline-actions">
      <?php if ($order['status'] === 'awaiting_payment'): ?>
        <form method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="confirm_payment"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><button class="button" type="submit">Confirm payment</button></form>
      <?php elseif ($order['status'] === 'payment_confirmed'): ?>
        <form method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="confirm_shipping"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><button class="button" type="submit">Confirm shipping</button></form>
      <?php else: ?><span class="small-note">Shipment confirmed.</span><?php endif; ?>
    </div>
  </article>
<?php endforeach; ?>
<?php render_footer(); ?>
