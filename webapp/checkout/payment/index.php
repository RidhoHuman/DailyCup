<?php
// Simple dev-friendly success page rendered by Apache (for local UX while Next.js runs separately)
$orderId = $_GET['orderId'] ?? null;
$status = 'unknown';
$order = null;
if ($orderId) {
    $url = 'http://dailycup.test/backend/api/get_order.php?orderId=' . urlencode($orderId);
    $json = @file_get_contents($url);
    if ($json) {
        $data = json_decode($json, true);
        if ($data && isset($data['order'])) {
            $order = $data['order'];
            $status = $order['status'] ?? 'unknown';
        }
    }
}
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payment Status</title>
</head>
<body>
  <h1>Payment Status</h1>
  <?php if ($orderId): ?>
    <p>Order ID: <strong><?php echo htmlspecialchars($orderId); ?></strong></p>
    <p>Status: <strong><?php echo htmlspecialchars($status); ?></strong></p>
    <?php if ($order): ?>
      <p>Total: Rp <?php echo number_format($order['total']); ?></p>
    <?php endif; ?>
    <form method="post" action="/backend/api/notify_xendit.php" style="margin-top:1rem;">
      <input type="hidden" name="external_id" value="<?php echo htmlspecialchars($orderId); ?>">
      <input type="hidden" name="status" value="PAID">
      <input type="submit" value="Simulate Webhook (mark PAID)" />
    </form>
  <?php else: ?>
    <p>No order specified. Use ?orderId=ORD-... in the URL.</p>
  <?php endif; ?>
</body>
</html>