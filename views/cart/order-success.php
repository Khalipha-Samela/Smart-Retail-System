<?php
/**
 * SmartRetail - Order Success Page
 * Displays order confirmation and thank you message
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/config.php';
require_once '../../utils/Auth.php';

// Require authentication
Auth::requireAuth();

// Get user information
$user_id = Auth::getUserId();
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

// Generate a fake order ID for demo purposes
$order_id = 'SR-' . strtoupper(uniqid());
$order_date = date('F j, Y \a\t g:i A');
$estimated_delivery = date('F j, Y', strtotime('+3 days'));

// Clear the cart after successful order
if (isset($_SESSION['cart'])) {
    $ordered_items = $_SESSION['cart']; // Store items for display before clearing
    $_SESSION['cart'] = [];
} else {
    $ordered_items = [];
}

// Calculate order total
$order_total = 0;
foreach ($ordered_items as $item) {
    $order_total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Successful - SmartRetail</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --primary-color: #28ACD8;
        --primary-dark: #1d4ed8;
        --secondary-color: #64748b;
        --success-color: #10b981;
        --light-bg: #f8fafc;
        --text-dark: #334155;
        --text-light: #64748b;
        --border-color: #e2e8f0;
    }

    body {
        font-family: 'Inter', sans-serif;
        line-height: 1.6;
        color: var(--text-dark);
        background-color: var(--light-bg);
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Success Page Styles */
    .success-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 0;
    }

    .success-card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        padding: 3rem;
        text-align: center;
        width: 100%;
    }

    .success-icon {
        width: 80px;
        height: 80px;
        background: var(--success-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        font-size: 2.5rem;
        color: white;
    }

    .success-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1rem;
    }

    .success-message {
        font-size: 1.125rem;
        color: var(--text-light);
        margin-bottom: 2rem;
        line-height: 1.8;
    }

    .order-details {
        background: var(--light-bg);
        border-radius: 0.75rem;
        padding: 2rem;
        margin: 2rem 0;
        text-align: left;
    }

    .order-details h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .detail-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .detail-label {
        color: var(--text-light);
        font-weight: 500;
    }

    .detail-value {
        color: var(--text-dark);
        font-weight: 600;
    }

    .order-items {
        margin-top: 1.5rem;
    }

    .order-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid var(--border-color);
    }

    .order-item:last-child {
        border-bottom: none;
    }

    .item-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .item-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
    }

    .item-name {
        font-weight: 500;
    }

    .item-quantity {
        color: var(--text-light);
        font-size: 0.875rem;
    }

    .item-total {
        font-weight: 600;
        color: var(--primary-color);
    }

    .order-total {
        background: white;
        border: 2px solid var(--primary-color);
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin: 2rem 0;
        text-align: center;
    }

    .total-label {
        font-size: 1rem;
        color: var(--text-light);
        margin-bottom: 0.5rem;
    }

    .total-amount {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
        flex-wrap: wrap;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 0.875rem;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }

    .btn-outline {
        border: 1px solid var(--border-color);
        background: white;
        color: var(--text-dark);
    }

    .btn-outline:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .delivery-info {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin: 2rem 0;
        text-align: center;
    }

    .delivery-icon {
        font-size: 2rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    .delivery-text {
        color: var(--text-dark);
        font-weight: 500;
    }

    .delivery-date {
        color: var(--primary-color);
        font-weight: 700;
        font-size: 1.125rem;
        margin-top: 0.5rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .success-card {
            padding: 2rem 1.5rem;
            margin: 1rem;
        }

        .success-title {
            font-size: 2rem;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }

        .detail-row {
            flex-direction: column;
            gap: 0.5rem;
        }
    }
</style>
</head>
<body>
    <div class="success-page">
        <div class="container">
            <div class="success-card">
                <!-- Success Icon -->
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>

                <!-- Success Title & Message -->
                <h1 class="success-title">Order Confirmed!</h1>
                <p class="success-message">
                    Thank you for your purchase, <?php echo htmlspecialchars($full_name); ?>! Your order has been successfully placed and is being processed.
                </p>

                <!-- Order Details -->
                <div class="order-details">
                    <h3><i class="fas fa-receipt"></i> Order Information</h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Order Number:</span>
                        <span class="detail-value"><?php echo $order_id; ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Order Date:</span>
                        <span class="detail-value"><?php echo $order_date; ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Customer:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($full_name); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($email); ?></span>
                    </div>

                    <!-- Ordered Items -->
                    <?php if (!empty($ordered_items)): ?>
                    <div class="order-items">
                        <h3><i class="fas fa-shopping-bag"></i> Ordered Items</h3>
                        <?php foreach ($ordered_items as $item): ?>
                            <div class="order-item">
                                <div class="item-info">
                                    <div class="item-icon">
                                        <i class="<?php echo $item['icon']; ?>"></i>
                                    </div>
                                    <div>
                                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                                    </div>
                                </div>
                                <div class="item-total">
                                    R<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Delivery Information -->
                <div class="delivery-info">
                    <div class="delivery-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <div class="delivery-text">Estimated Delivery</div>
                    <div class="delivery-date"><?php echo $estimated_delivery; ?></div>
                </div>

                <!-- Order Total -->
                <div class="order-total">
                    <div class="total-label">Total Amount Paid</div>
                    <div class="total-amount">R<?php echo number_format($order_total, 2); ?></div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="../products/products.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i>
                        Continue Shopping
                    </a>
                    
                    <a href="../order/orders.php" class="btn btn-outline">
                        <i class="fas fa-list-alt"></i>
                        View Order History
                    </a>
                    
                    <a href="../../dashboard.php" class="btn btn-outline">
                        <i class="fas fa-tachometer-alt"></i>
                        Go to Dashboard
                    </a>
                </div>

                <!-- Additional Information -->
                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                    <p style="color: var(--text-light); font-size: 0.875rem;">
                        <i class="fas fa-envelope"></i>
                        A confirmation email has been sent to <?php echo htmlspecialchars($email); ?>. 
                        You'll receive tracking information once your order ships.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

    <script src="../../assets/js/enhanced-features.js"></script>

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add celebration effect
            const successIcon = document.querySelector('.success-icon');
            successIcon.style.transform = 'scale(0)';
            successIcon.style.transition = 'transform 0.6s ease-out';
            
            setTimeout(() => {
                successIcon.style.transform = 'scale(1)';
            }, 100);

            // Add confetti effect (simple version)
            function createConfetti() {
                const colors = ['#28ACD8', '#10b981', '#f59e0b', '#ef4444'];
                for (let i = 0; i < 20; i++) {
                    setTimeout(() => {
                        const confetti = document.createElement('div');
                        confetti.innerHTML = '🎉';
                        confetti.style.position = 'fixed';
                        confetti.style.left = Math.random() * 100 + 'vw';
                        confetti.style.top = '-50px';
                        confetti.style.fontSize = (Math.random() * 20 + 10) + 'px';
                        confetti.style.opacity = '0.8';
                        confetti.style.zIndex = '9999';
                        confetti.style.pointerEvents = 'none';
                        confetti.style.animation = `fall ${Math.random() * 3 + 2}s linear forwards`;
                        document.body.appendChild(confetti);

                        setTimeout(() => {
                            confetti.remove();
                        }, 5000);
                    }, i * 100);
                }
            }

            // Add CSS for confetti animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fall {
                    to {
                        transform: translateY(100vh) rotate(360deg);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);

            // Trigger confetti
            createConfetti();
        });
    </script>
</body>
</html>