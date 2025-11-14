// Real-time stock validation for products
class StockValidator {
    constructor() {
        this.initStockValidation();
    }
    
    initStockValidation() {
        // Add event listeners to all quantity inputs
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', this.validateStock.bind(this));
            input.addEventListener('input', this.debounce(this.validateStock.bind(this), 500));
        });
        
        // Add real-time validation to add to cart buttons
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', this.validateBeforeAdd.bind(this));
        });
    }
    
    async validateStock(event) {
        const input = event.target;
        const form = input.closest('.add-to-cart-form');
        const productId = form.querySelector('input[name="product_id"]').value;
        const quantity = parseInt(input.value);
        const maxStock = parseInt(input.getAttribute('max')) || 999;
        const button = form.querySelector('.add-to-cart-btn');
        const stockDisplay = form.closest('.product-info').querySelector('.product-stock');
        
        // Immediate client-side validation
        if (quantity > maxStock) {
            this.showStockError(input, `Only ${maxStock} items available`);
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-shopping-cart"></i> Out of Stock';
            return;
        }
        
        if (quantity < 1) {
            input.value = 1;
            return;
        }
        
        // Real-time server-side validation
        try {
            const response = await fetch(`../../api/stock-check.php?product_id=${productId}`);
            if (!response.ok) throw new Error('Network error');
            
            const data = await response.json();
            
            if (data.available_stock < quantity) {
                this.showStockError(input, `Only ${data.available_stock} items available`);
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-shopping-cart"></i> Out of Stock';
                
                // Update stock display
                if (stockDisplay) {
                    stockDisplay.innerHTML = `<i class="fas fa-times-circle"></i> Only ${data.available_stock} left`;
                    stockDisplay.className = 'product-stock stock-low';
                }
            } else {
                this.clearStockError(input);
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                
                // Update stock display if it was changed
                if (stockDisplay && data.available_stock <= 10) {
                    stockDisplay.innerHTML = `<i class="fas fa-check-circle"></i> Only ${data.available_stock} left`;
                    stockDisplay.className = 'product-stock stock-low';
                }
            }
        } catch (error) {
            console.error('Stock check failed:', error);
            // If API fails, rely on client-side validation only
            this.clearStockError(input);
        }
    }
    
    async validateBeforeAdd(event) {
        const button = event.target;
        const form = button.closest('.add-to-cart-form');
        const productId = form.querySelector('input[name="product_id"]').value;
        const quantity = parseInt(form.querySelector('.quantity-input').value);
        
        // Quick validation before form submission
        try {
            const response = await fetch(`../../api/stock-check.php?product_id=${productId}`);
            const data = await response.json();
            
            if (data.available_stock < quantity) {
                event.preventDefault();
                this.showNotification(`Sorry, only ${data.available_stock} items available`, 'error');
                return false;
            }
        } catch (error) {
            console.error('Pre-add validation failed:', error);
            // Continue with form submission if validation fails
        }
        
        return true;
    }
    
    showStockError(input, message) {
        this.clearStockError(input);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'stock-error-message';
        errorDiv.style.cssText = `
            color: #e74c3c;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            font-weight: 500;
        `;
        errorDiv.textContent = message;
        
        input.parentNode.appendChild(errorDiv);
        input.style.borderColor = '#e74c3c';
        input.style.backgroundColor = '#fef2f2';
    }
    
    clearStockError(input) {
        const existingError = input.parentNode.querySelector('.stock-error-message');
        if (existingError) {
            existingError.remove();
        }
        input.style.borderColor = '';
        input.style.backgroundColor = '';
    }
    
    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            z-index: 10000;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease-out;
        `;
        
        if (type === 'success') {
            notification.style.background = '#10b981';
        } else {
            notification.style.background = '#e74c3c';
        }
        
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new StockValidator();
});