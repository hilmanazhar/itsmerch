<?php
/**
 * Invoice PDF Generator
 * Generates a downloadable PDF invoice for completed orders
 * Uses HTML to PDF conversion (browser print)
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if (!$order_id || !$user_id) {
    die('Order ID dan User ID diperlukan');
}

try {
    // Get order details
    $orderStmt = $pdo->prepare("
        SELECT o.*, u.name as user_name, u.email as user_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $orderStmt->execute([$order_id, $user_id]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die('Pesanan tidak ditemukan');
    }

    // Get order items
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.image_url
        FROM order_details oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Use shipping_address from order (stored as formatted text)
    $address = null; // Address info is in $order['shipping_address']

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Calculate totals
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $order['shipping_cost'] ?? 0;
$discount = $order['discount_amount'] ?? 0;
$total = $order['total_amount'];

// Status badge colors
$statusColors = [
    'Belum Bayar' => '#dc3545',
    'Dikemas' => '#ffc107',
    'Dikirim' => '#17a2b8',
    'Selesai' => '#28a745',
    'Dibatalkan' => '#6c757d'
];
$statusColor = $statusColors[$order['status']] ?? '#6c757d';

// Format date
$orderDate = date('d F Y, H:i', strtotime($order['created_at']));

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $order_id ?> - myITS Merchandise</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-radius: 12px;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007BC0;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo img {
            width: 50px;
            height: 50px;
        }
        .logo h1 {
            font-size: 24px;
            color: #333;
        }
        .logo h1 span {
            color: #007BC0;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h2 {
            font-size: 28px;
            color: #007BC0;
            margin-bottom: 5px;
        }
        .invoice-title p {
            color: #666;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .info-box h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        .info-box p {
            margin-bottom: 5px;
            line-height: 1.5;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            color: white;
            font-weight: bold;
            font-size: 12px;
            background: <?= $statusColor ?>;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background: #f8f9fa;
            padding: 15px 10px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            border-bottom: 2px solid #dee2e6;
        }
        td {
            padding: 15px 10px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .product-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .product-cell img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .text-right {
            text-align: right;
        }
        .summary {
            width: 300px;
            margin-left: auto;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .summary-row.total {
            border-bottom: none;
            border-top: 2px solid #333;
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 15px;
        }
        .summary-row.total .value {
            color: #007BC0;
        }
        .summary-row.discount {
            color: #28a745;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 12px;
        }
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #007BC0;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 30px;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(0,123,192,0.3);
        }
        .print-btn:hover {
            background: #005A8D;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                padding: 20px;
            }
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="logo" style="display: flex; align-items: center; gap: 10px;">
                <img src="../assets/images/logo.png" alt="Logo" style="width: 50px; height: 50px;">
                <h1 style="margin: 0; font-size: 1.5rem;">my<span style="color:#9b59b6">ITS</span> Merch</h1>
            </div>
            <div class="invoice-title">
                <h2>INVOICE</h2>
                <p>#INV-<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h3>Diterbitkan Untuk</h3>
                <p><strong><?= htmlspecialchars($order['user_name']) ?></strong></p>
                <p><?= htmlspecialchars($order['user_email']) ?></p>
                <?php if (!empty($order['shipping_address'])): ?>
                <p style="margin-top:10px;"><strong>Alamat Pengiriman:</strong></p>
                <p><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                <?php endif; ?>
            </div>
            <div class="info-box" style="text-align: right;">
                <h3>Detail Pesanan</h3>
                <p><strong>Tanggal:</strong> <?= $orderDate ?></p>
                <p><strong>Status:</strong> <span class="status-badge"><?= $order['status'] ?></span></p>
                <?php if (!empty($order['tracking_number'])): ?>
                <p><strong>No. Resi:</strong> <?= htmlspecialchars($order['tracking_number']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div class="product-cell">
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="">
                            <span><?= htmlspecialchars($item['product_name']) ?></span>
                        </div>
                    </td>
                    <td class="text-right">Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                    <td class="text-right"><?= $item['quantity'] ?></td>
                    <td class="text-right">Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
            </div>
            <div class="summary-row">
                <span>Ongkos Kirim</span>
                <span>Rp <?= number_format($shipping, 0, ',', '.') ?></span>
            </div>
            <?php if ($discount > 0): ?>
            <div class="summary-row discount">
                <span>Diskon <?= !empty($order['coupon_code']) ? '(' . htmlspecialchars($order['coupon_code']) . ')' : '' ?></span>
                <span>- Rp <?= number_format($discount, 0, ',', '.') ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-row total">
                <span>Total</span>
                <span class="value">Rp <?= number_format($total, 0, ',', '.') ?></span>
            </div>
        </div>

        <div class="footer">
            <p>Terima kasih telah berbelanja di myITS Merchandise</p>
            <p>Pertanyaan? Hubungi kami di support@itsmerch.id</p>
        </div>
    </div>

    <button class="print-btn" onclick="window.print()">
        🖨️ Cetak / Download PDF
    </button>
</body>
</html>
