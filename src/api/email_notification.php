<?php
/**
 * Email Notification Service
 * Sends email notifications for order status updates
 * 
 * Note: This uses PHP's mail() function which requires proper server configuration.
 * For production, use a service like SendGrid, Mailgun, or PHPMailer with SMTP.
 */

class EmailNotification {
    private $pdo;
    private $fromEmail = 'no-reply@itsmerch.id';
    private $fromName = 'myITS Merchandise';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($orderId) {
        $order = $this->getOrderDetails($orderId);
        if (!$order) return false;
        
        $subject = "Konfirmasi Pesanan #{$orderId} - myITS Merchandise";
        $body = $this->renderTemplate('order_confirmation', $order);
        
        return $this->send($order['user_email'], $subject, $body);
    }
    
    /**
     * Send payment success email
     */
    public function sendPaymentSuccess($orderId) {
        $order = $this->getOrderDetails($orderId);
        if (!$order) return false;
        
        $subject = "Pembayaran Berhasil - Pesanan #{$orderId}";
        $body = $this->renderTemplate('payment_success', $order);
        
        return $this->send($order['user_email'], $subject, $body);
    }
    
    /**
     * Send shipping notification email
     */
    public function sendShippingNotification($orderId, $trackingNumber, $courier) {
        $order = $this->getOrderDetails($orderId);
        if (!$order) return false;
        
        $order['tracking_number'] = $trackingNumber;
        $order['courier'] = strtoupper($courier);
        
        $subject = "Pesanan #{$orderId} Sedang Dikirim";
        $body = $this->renderTemplate('shipping', $order);
        
        return $this->send($order['user_email'], $subject, $body);
    }
    
    /**
     * Send order completed email
     */
    public function sendOrderCompleted($orderId) {
        $order = $this->getOrderDetails($orderId);
        if (!$order) return false;
        
        $subject = "Pesanan #{$orderId} Selesai - Terima Kasih!";
        $body = $this->renderTemplate('order_completed', $order);
        
        return $this->send($order['user_email'], $subject, $body);
    }
    
    /**
     * Get order details for email
     */
    private function getOrderDetails($orderId) {
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.name as user_name, u.email as user_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) return null;
        
        // Get items
        $itemsStmt = $this->pdo->prepare("
            SELECT oi.*, p.name as product_name
            FROM order_details oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$orderId]);
        $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $order;
    }
    
    /**
     * Render email template
     */
    private function renderTemplate($template, $data) {
        $header = $this->getEmailHeader();
        $footer = $this->getEmailFooter();
        
        switch ($template) {
            case 'order_confirmation':
                $content = $this->orderConfirmationTemplate($data);
                break;
            case 'payment_success':
                $content = $this->paymentSuccessTemplate($data);
                break;
            case 'shipping':
                $content = $this->shippingTemplate($data);
                break;
            case 'order_completed':
                $content = $this->orderCompletedTemplate($data);
                break;
            default:
                $content = '';
        }
        
        return $header . $content . $footer;
    }
    
    private function getEmailHeader() {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #007BC0, #005A8D); padding:30px; text-align:center;">
                            <h1 style="color:#ffffff; margin:0; font-size:24px;">my<span style="color:#4DB5E6">ITS</span> Merchandise</h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:30px;">
';
    }
    
    private function getEmailFooter() {
        return '
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#1E1E1E; padding:20px; text-align:center;">
                            <p style="color:#888; margin:0; font-size:12px;">
                                © 2024 myITS Merchandise. All rights reserved.<br>
                                Institut Teknologi Sepuluh Nopember
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    private function orderConfirmationTemplate($data) {
        $itemsHtml = '';
        foreach ($data['items'] as $item) {
            $subtotal = number_format($item['price'] * $item['quantity'], 0, ',', '.');
            $itemsHtml .= "
                <tr>
                    <td style='padding:10px; border-bottom:1px solid #eee;'>{$item['product_name']}</td>
                    <td style='padding:10px; border-bottom:1px solid #eee; text-align:center;'>{$item['quantity']}</td>
                    <td style='padding:10px; border-bottom:1px solid #eee; text-align:right;'>Rp {$subtotal}</td>
                </tr>
            ";
        }
        
        $total = number_format($data['total'], 0, ',', '.');
        
        return "
            <h2 style='color:#333; margin-top:0;'>Halo, {$data['user_name']}!</h2>
            <p style='color:#666; line-height:1.6;'>
                Terima kasih telah berbelanja di myITS Merchandise. Pesanan Anda telah kami terima.
            </p>
            
            <div style='background:#f9f9f9; padding:20px; border-radius:8px; margin:20px 0;'>
                <h3 style='margin-top:0; color:#007BC0;'>Detail Pesanan #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "</h3>
                <table width='100%' cellpadding='0' cellspacing='0'>
                    <tr style='background:#007BC0; color:#fff;'>
                        <th style='padding:10px; text-align:left;'>Produk</th>
                        <th style='padding:10px; text-align:center;'>Qty</th>
                        <th style='padding:10px; text-align:right;'>Subtotal</th>
                    </tr>
                    {$itemsHtml}
                    <tr>
                        <td colspan='2' style='padding:15px; text-align:right; font-weight:bold;'>Total:</td>
                        <td style='padding:15px; text-align:right; font-weight:bold; color:#007BC0;'>Rp {$total}</td>
                    </tr>
                </table>
            </div>
            
            <p style='color:#666;'>
                Silakan selesaikan pembayaran agar pesanan dapat segera diproses.
            </p>
        ";
    }
    
    private function paymentSuccessTemplate($data) {
        $total = number_format($data['total'], 0, ',', '.');
        
        return "
            <div style='text-align:center; margin-bottom:20px;'>
                <div style='font-size:60px;'>✓</div>
                <h2 style='color:#28a745; margin:10px 0;'>Pembayaran Berhasil!</h2>
            </div>
            
            <p style='color:#666; text-align:center; line-height:1.6;'>
                Halo {$data['user_name']}, pembayaran untuk pesanan <strong>#{$data['id']}</strong> 
                sebesar <strong style='color:#007BC0;'>Rp {$total}</strong> telah berhasil.
            </p>
            
            <div style='background:#e8f5e9; padding:20px; border-radius:8px; margin:20px 0; text-align:center;'>
                <p style='color:#2e7d32; margin:0;'>
                    <strong>Pesanan Anda sedang diproses!</strong><br>
                    Kami akan segera mengemaskan pesanan Anda.
                </p>
            </div>
        ";
    }
    
    private function shippingTemplate($data) {
        return "
            <div style='text-align:center; margin-bottom:20px;'>
                <div style='font-size:60px;'>🚚</div>
                <h2 style='color:#007BC0; margin:10px 0;'>Pesanan Dikirim!</h2>
            </div>
            
            <p style='color:#666; text-align:center; line-height:1.6;'>
                Halo {$data['user_name']}, pesanan <strong>#{$data['id']}</strong> sedang dalam perjalanan!
            </p>
            
            <div style='background:#e3f2fd; padding:20px; border-radius:8px; margin:20px 0; text-align:center;'>
                <p style='color:#1565c0; margin:0 0 10px 0;'><strong>Kurir: {$data['courier']}</strong></p>
                <p style='font-size:24px; margin:0; color:#0d47a1; font-family:monospace;'>{$data['tracking_number']}</p>
                <p style='color:#666; margin:10px 0 0 0; font-size:12px;'>Nomor Resi</p>
            </div>
            
            <p style='color:#666; text-align:center;'>
                Anda dapat melacak pengiriman melalui website kurir.
            </p>
        ";
    }
    
    private function orderCompletedTemplate($data) {
        return "
            <div style='text-align:center; margin-bottom:20px;'>
                <div style='font-size:60px;'>🎉</div>
                <h2 style='color:#28a745; margin:10px 0;'>Pesanan Selesai!</h2>
            </div>
            
            <p style='color:#666; text-align:center; line-height:1.6;'>
                Halo {$data['user_name']}, pesanan <strong>#{$data['id']}</strong> telah selesai.<br>
                Terima kasih telah berbelanja di myITS Merchandise!
            </p>
            
            <div style='background:#fff3e0; padding:20px; border-radius:8px; margin:20px 0; text-align:center;'>
                <p style='color:#e65100; margin:0;'>
                    ⭐ <strong>Bagaimana pengalaman Anda?</strong><br>
                    Berikan ulasan untuk membantu pembeli lain!
                </p>
            </div>
            
            <div style='text-align:center;'>
                <a href='#' style='display:inline-block; background:#007BC0; color:#fff; padding:12px 30px; text-decoration:none; border-radius:25px;'>
                    Tulis Ulasan
                </a>
            </div>
        ";
    }
    
    /**
     * Send email using SMTP or log to file
     */
    private function send($to, $subject, $body) {
        // Load SMTP config
        $configFile = __DIR__ . '/email_config.php';
        if (file_exists($configFile)) {
            require_once $configFile;
        }
        
        // Log email for debugging
        $logFile = __DIR__ . '/email_log.txt';
        $logEntry = "=== EMAIL LOG ===\n";
        $logEntry .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $logEntry .= "To: {$to}\n";
        $logEntry .= "Subject: {$subject}\n";
        $logEntry .= "Body length: " . strlen($body) . " chars\n";
        
        // Check if SMTP is enabled
        if (!defined('SMTP_ENABLED') || !SMTP_ENABLED) {
            $logEntry .= "Status: SMTP disabled, email logged only\n";
            $logEntry .= "================\n\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            return true;
        }
        
        // Check if credentials are configured
        if (SMTP_USERNAME === 'your-brevo-email@example.com') {
            $logEntry .= "Status: SMTP not configured, email logged only\n";
            $logEntry .= "================\n\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            return true;
        }
        
        try {
            // Use simple SMTP sending
            $result = $this->sendViaSMTP($to, $subject, $body);
            $logEntry .= "Status: " . ($result ? "Sent successfully via SMTP" : "Failed to send") . "\n";
            $logEntry .= "================\n\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            return $result;
        } catch (Exception $e) {
            $logEntry .= "Status: Error - " . $e->getMessage() . "\n";
            $logEntry .= "================\n\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            return false;
        }
    }
    
    /**
     * Send email via SMTP using fsockopen
     */
    private function sendViaSMTP($to, $subject, $body) {
        $host = SMTP_HOST;
        $port = SMTP_PORT;
        $username = SMTP_USERNAME;
        $password = SMTP_PASSWORD;
        $from = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : $this->fromEmail;
        $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : $this->fromName;
        
        // Connect to SMTP server
        $socket = @fsockopen($host, $port, $errno, $errstr, 30);
        if (!$socket) {
            throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
        }
        
        // Set timeout
        stream_set_timeout($socket, 30);
        
        // Read greeting
        $this->smtpRead($socket);
        
        // EHLO
        $this->smtpCommand($socket, "EHLO " . gethostname());
        
        // STARTTLS for secure connection
        if (defined('SMTP_SECURE') && SMTP_SECURE === 'tls') {
            $this->smtpCommand($socket, "STARTTLS");
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->smtpCommand($socket, "EHLO " . gethostname());
        }
        
        // Auth
        if (defined('SMTP_AUTH') && SMTP_AUTH) {
            $this->smtpCommand($socket, "AUTH LOGIN");
            $this->smtpCommand($socket, base64_encode($username));
            $this->smtpCommand($socket, base64_encode($password));
        }
        
        // Mail From
        $this->smtpCommand($socket, "MAIL FROM:<{$from}>");
        
        // Rcpt To
        $this->smtpCommand($socket, "RCPT TO:<{$to}>");
        
        // Data
        $this->smtpCommand($socket, "DATA");
        
        // Build message
        $headers = "From: {$fromName} <{$from}>\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "\r\n";
        
        $message = $headers . $body . "\r\n.";
        $this->smtpCommand($socket, $message);
        
        // Quit
        $this->smtpCommand($socket, "QUIT");
        
        fclose($socket);
        return true;
    }
    
    private function smtpCommand($socket, $command) {
        fwrite($socket, $command . "\r\n");
        return $this->smtpRead($socket);
    }
    
    private function smtpRead($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return $response;
    }
}

// API endpoint for sending emails
if (php_sapi_name() !== 'cli' && basename($_SERVER['PHP_SELF']) === 'email_notification.php') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'POST method required']);
        exit;
    }
    
    require_once 'db.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? '';
    $order_id = intval($input['order_id'] ?? 0);
    
    if (!$order_id || !$type) {
        echo json_encode(['success' => false, 'error' => 'order_id and type required']);
        exit;
    }
    
    $emailService = new EmailNotification($pdo);
    
    switch ($type) {
        case 'order_confirmation':
            $result = $emailService->sendOrderConfirmation($order_id);
            break;
        case 'payment_success':
            $result = $emailService->sendPaymentSuccess($order_id);
            break;
        case 'shipping':
            $tracking = $input['tracking_number'] ?? '';
            $courier = $input['courier'] ?? '';
            $result = $emailService->sendShippingNotification($order_id, $tracking, $courier);
            break;
        case 'order_completed':
            $result = $emailService->sendOrderCompleted($order_id);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid email type']);
            exit;
    }
    
    echo json_encode(['success' => $result]);
}
