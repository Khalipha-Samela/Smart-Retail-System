<?php
/**
 * SmartRetail - Checkout Page
 * Gets items from cart session and displays order summary
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/config.php';
require_once '../../utils/Auth.php';

// Require authentication
Auth::requireAuth();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get cart items from session
$cart_items = $_SESSION['cart'];
$cart_total = 0;

// Calculate total from actual cart items and ensure proper structure
if (!empty($cart_items)) {
    foreach ($cart_items as &$item) {
        // Ensure all required fields exist with proper structure
        $item['id'] = $item['id'] ?? $item['product_id'] ?? uniqid();
        $item['product_id'] = $item['product_id'] ?? $item['id'] ?? uniqid();
        $item['name'] = $item['name'] ?? 'Product';
        $item['price'] = $item['price'] ?? 0;
        $item['quantity'] = $item['quantity'] ?? 1;
        $item['icon'] = $item['icon'] ?? 'fas fa-box';
        $item['stock_quantity'] = $item['stock_quantity'] ?? 10;
        
        // Calculate item total
        $cart_total += $item['price'] * $item['quantity'];
    }
}

// If cart is empty, redirect to cart page
if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartRetail - Checkout</title>
<style>
    body {
        font-family: "Inter", sans-serif;
        background: #f9fafb;
        color: #1f2937;
        margin: 0;
        padding: 0;
    }
    header {
        background: white;
        border-bottom: 1px solid #e5e7eb;
        padding: 1rem 2rem;
        font-size: 1.25rem;
        font-weight: 600;
        color: #0284c7;
    }
    .checkout-container {
        max-width: 1200px;
        margin: 2rem auto;
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 2rem;
        padding: 0 1rem;
    }
    .section {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 2rem;
    }
    .section h2 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #111827;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-label {
        display: block;
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    .form-input, textarea {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 1rem;
    }
    .form-input:focus, textarea:focus {
        outline: none;
        border-color: #0284c7;
        box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
    }
    .payment-methods {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }
    .payment-method {
        flex: 1;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        text-align: center;
        padding: 1rem;
        cursor: pointer;
        transition: 0.3s;
    }
    .payment-method:hover {
        border-color: #0284c7;
    }
    .payment-method.selected {
        border-color: #0284c7;
        background: #f0f9ff;
    }
    .order-summary {
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        background: white;
        padding: 2rem;
        height: fit-content;
    }
    .order-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #f3f4f6;
    }
    .order-item:last-child {
        border-bottom: none;
    }
    .order-total {
        border-top: 1px solid #e5e7eb;
        margin-top: 1rem;
        padding-top: 1rem;
        font-size: 1.125rem;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
    }
    .btn-submit {
        width: 100%;
        background: #0284c7;
        color: white;
        border: none;
        border-radius: 0.5rem;
        padding: 0.75rem;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.3s;
    }
    .btn-submit:hover {
        background: #0369a1;
    }
    .btn-submit:disabled {
        background: #9ca3af;
        cursor: not-allowed;
    }
</style>
</head>
<body>
<header>
    SmartRetail - Checkout
</header>

<div class="checkout-container">
    <!-- Shipping Section -->
    <div class="section">
        <h2>📦 Shipping Address</h2>
        <form id="shippingForm">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="shipping_name" class="form-input" required value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="shipping_email" class="form-input" required value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Street Address</label>
                <textarea name="shipping_address" rows="2" class="form-input" required placeholder="Enter your full street address"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" name="shipping_city" class="form-input" required placeholder="Enter your city">
            </div>
            <div class="form-group">
                <label class="form-label">Postal Code</label>
                <input type="text" name="shipping_postal_code" class="form-input" required placeholder="Enter postal code">
            </div>
        </form>

        <h2 style="margin-top:2rem;">💳 Payment Method</h2>
        <div class="payment-methods">
            <div class="payment-method selected" data-method="credit_card">Credit/Debit Card</div>
            <div class="payment-method" data-method="cash">Cash on Delivery</div>
        </div>

        <!-- Credit Card Inputs -->
        <div id="creditCardForm" style="margin-top:1rem;">
            <div class="form-group">
                <label class="form-label">Card Number</label>
                <input type="text" class="form-input" placeholder="1234 5678 9012 3456" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Expiry Date</label>
                    <input type="text" class="form-input" placeholder="MM/YY" required>
                </div>
                <div class="form-group">
                    <label class="form-label">CVV</label>
                    <input type="text" class="form-input" placeholder="123" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="order-summary">
        <h2>🧾 Order Summary</h2>
        <?php if (!empty($cart_items)): ?>
            <?php foreach ($cart_items as $item): ?>
                <div class="order-item">
                    <span><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></span>
                    <span>R<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="order-item">
                <span>No items in cart</span>
            </div>
        <?php endif; ?>

        <div class="order-total">
            <span>Total:</span>
            <span id="total">R<?php echo number_format($cart_total, 2); ?></span>
        </div>

        <button id="placeOrderBtn" class="btn-submit" style="margin-top:1.5rem;">
            Place Order - R<?php echo number_format($cart_total, 2); ?>
        </button>
        
        <div style="text-align: center; margin-top: 1rem;">
            <a href="cart.php" style="color: #0284c7; text-decoration: none;">← Return to Cart</a>
        </div>
    </div>
</div>

<script src="../../assets/js/enhanced-features.js"></script>
<script>
// Payment method toggle
document.querySelectorAll('.payment-method').forEach(method => {
    method.addEventListener('click', function() {
        document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
        this.classList.add('selected');
        const ccForm = document.getElementById('creditCardForm');
        const inputs = ccForm.querySelectorAll('input');
        
        if (this.dataset.method === 'credit_card') {
            ccForm.style.display = 'block';
            inputs.forEach(input => input.required = true);
        } else {
            ccForm.style.display = 'none';
            inputs.forEach(input => input.required = false);
        }
    });
});

// Form submission
document.getElementById('placeOrderBtn').addEventListener('click', function() {
    const shippingForm = document.getElementById('shippingForm');
    const selectedPayment = document.querySelector('.payment-method.selected').dataset.method;
    
    // Validate shipping form
    if (!shippingForm.checkValidity()) {
        alert('Please fill in all required shipping information.');
        return;
    }
    
    // Validate credit card if selected
    if (selectedPayment === 'credit_card') {
        const cardInputs = document.querySelectorAll('#creditCardForm input');
        for (let input of cardInputs) {
            if (!input.value.trim()) {
                alert('Please fill in all credit card details.');
                return;
            }
        }
    }
    
    // Show loading state
    this.innerHTML = 'Processing Order...';
    this.disabled = true;
    
    // Simulate order processing
    setTimeout(() => {
        alert('Order placed successfully! Thank you for your purchase.');
        // In a real application, you would submit the form to a server endpoint
        window.location.href = 'order-success.php';
    }, 2000);
});

// Initialize payment method on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set credit card as default selected
    document.querySelector('.payment-method[data-method="credit_card"]').click();
});
</script>
</body>
</html>