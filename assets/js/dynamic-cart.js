class DynamicCartTotals {
    constructor() {
        this.initCartUpdates();
    }
    
    initCartUpdates() {
        // Update totals when quantity changes
        document.querySelectorAll('.quantity-input, .quantity-btn').forEach(element => {
            element.addEventListener('change', this.updateTotals.bind(this));
            element.addEventListener('click', this.updateTotals.bind(this));
        });
    }
    
    updateTotals() {
        let subtotal = 0;
        
        // Calculate new subtotal
        document.querySelectorAll('.cart-item').forEach(item => {
            const price = parseFloat(item.dataset.price);
            const quantity = parseInt(item.querySelector('.quantity-input').value) || 1;
            subtotal += price * quantity;
            
            // Update item subtotal
            const itemSubtotal = item.querySelector('.item-subtotal');
            if (itemSubtotal) {
                itemSubtotal.textContent = `R${(price * quantity).toFixed(2)}`;
            }
        });
        
        // Update display
        document.querySelectorAll('.summary-value:not(.summary-total)').forEach(element => {
            element.textContent = `R${subtotal.toFixed(2)}`;
        });
        
        document.querySelectorAll('.summary-total').forEach(element => {
            element.textContent = `R${subtotal.toFixed(2)}`;
        });
        
        // Update checkout button
        const checkoutBtn = document.querySelector('.checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.innerHTML = `<i class="fas fa-lock"></i> Proceed to Checkout - R${subtotal.toFixed(2)}`;
        }
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    new DynamicCartTotals();
});