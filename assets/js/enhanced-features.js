// Main initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize based on current page
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('products.php')) {
        // Product page features
        new StockValidator();
        new SearchAutocomplete();
    }
    
    if (currentPage.includes('cart.php')) {
        // Cart page features
        new DynamicCartManager();
    }
    
    // Global cart count updates
    if (typeof CartManager !== 'undefined') {
        new CartManager();
    }
});

// Global utility functions
class UtilityHelpers {
    static debounce(func, wait) {
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
    
    static formatCurrency(amount) {
        return 'R' + parseFloat(amount).toFixed(2);
    }
    
    static showLoading(element) {
        element.disabled = true;
        const originalText = element.innerHTML;
        element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        return originalText;
    }
    
    static hideLoading(element, originalText) {
        element.disabled = false;
        element.innerHTML = originalText;
    }
}