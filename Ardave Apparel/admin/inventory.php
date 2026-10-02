<?php
require_once 'config.php';

$page_title = 'Inventory';
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '') && ($_POST['action'] ?? '') === 'adjust') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $change_qty = (int)($_POST['change_qty'] ?? 0);
    $reason     = trim($_POST['reason'] ?? '');

    if ($product_id > 0 && $change_qty !== 0) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO inventory (product_id, change_qty, reason) VALUES (?, ?, ?)');
            $stmt->execute([$product_id, $change_qty, $reason]);
            $stmt = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
            $stmt->execute([$change_qty, $product_id]);
            $pdo->commit();
            $notice = 'Inventory updated successfully.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $notice = 'Failed to update inventory. Please try again.';
        }
    }
}

$products = $pdo->query('SELECT id, name, stock FROM products ORDER BY name')->fetchAll();

$logs = $pdo->query(
    'SELECT i.id, p.name AS product, i.change_qty, i.reason, i.created_at
     FROM inventory i
     JOIN products p ON p.id = i.product_id
     ORDER BY i.created_at DESC
     LIMIT 50'
)->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="admin-content">
    <div class="admin-header">
        <h1>Inventory</h1>
        <button class="btn-primary" onclick="document.getElementById('addForm').classList.toggle('hidden')">+ Adjust Stock</button>
    </div>

    <form id="addForm" class="inline-form hidden" method="POST" action="inventory.php">
        <input type="hidden" name="action" value="adjust">
        <select name="product_id" required>
            <option value="">Select product</option>
            <?php foreach ($products as $p): ?>
                <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (stock: <?= (int)$p['stock'] ?>)</option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="change_qty" placeholder="+10 or -5" required>
        <input type="text" name="reason" placeholder="Reason (e.g. restock, damaged)">
        <button type="submit" class="btn-primary">Apply</button>
    </form>

    <h2 style="margin:2rem 0 1rem;">Current Stock</h2>
    <table class="admin-table">
        <thead><tr><th>Product</th><th>Stock</th></tr></thead>
        <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= (int)$p['stock'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 style="margin:2rem 0 1rem;">Recent Adjustments</h2>
    <table class="admin-table">
        <thead><tr><th>Product</th><th>Change</th><th>Reason</th><th>Date</th></tr></thead>
        <tbody>
            <?php if ($logs): ?>
                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['product']) ?></td>
                        <td style="color:<?= $l['change_qty'] >= 0 ? '#28a745' : '#dc3545' ?>">
                            <?= $l['change_qty'] >= 0 ? '+' : '' ?><?= (int)$l['change_qty'] ?>
                        </td>
                        <td><?= htmlspecialchars($l['reason']) ?></td>
                        <td><?= date('M d, Y H:i', strtotime($l['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">No inventory logs yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>
<?php include 'includes/footer.php'; ?>
