// Global flag to prevent double initialization
let dashboardInitialized = false;

document.addEventListener('DOMContentLoaded', function() {
    // Prevent double initialization
    if (dashboardInitialized) return;
    dashboardInitialized = true;
    
    console.log('=== DASHBOARD INITIALIZATION START ===');
    
    // Initialize all functionality
    initDashboard();
    
    // Show success message if item was added
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('added') === '1') {
        showNotification('Item added to cart!', 'success');
        
        // Remove the parameter from URL without reloading
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
    
    console.log('=== DASHBOARD INITIALIZATION COMPLETE ===');
});

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.innerHTML = `
        <div class="notification-content notification-${type}">
            <span class="notification-message">${message}</span>
            <button class="btn-icon" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Newsletter subscription
async function subscribeNewsletter(event) {
    event.preventDefault();
    const form = event.target;
    const email = form.querySelector('.newsletter-input').value;
    const button = form.querySelector('button');
    
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000));
        showNotification('Thank you for subscribing!', 'success');
        form.reset();
    } catch (error) {
        showNotification('Failed to subscribe. Please try again.', 'error');
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

// User dropdown functionality - FIXED VERSION
function initUserDropdown() {
    const userMenuButton = document.getElementById('userMenuButton');
    const userDropdown = document.getElementById('userDropdown');
    
    console.log('Initializing user dropdown...', {
        button: userMenuButton,
        dropdown: userDropdown
    });
    
    if (userMenuButton && userDropdown) {
        // Remove any existing event listeners first
        const newButton = userMenuButton.cloneNode(true);
        userMenuButton.parentNode.replaceChild(newButton, userMenuButton);
        
        const newDropdown = userDropdown.cloneNode(true);
        userDropdown.parentNode.replaceChild(newDropdown, userDropdown);
        
        // Get fresh references
        const freshButton = document.getElementById('userMenuButton');
        const freshDropdown = document.getElementById('userDropdown');
        
        // Toggle dropdown
        freshButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('User dropdown clicked - toggling');
            freshDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (freshDropdown.classList.contains('show') && 
                !freshButton.contains(e.target) && 
                !freshDropdown.contains(e.target)) {
                console.log('Closing dropdown - clicked outside');
                freshDropdown.classList.remove('show');
            }
        });
        
        // Close dropdown when clicking on a link
        freshDropdown.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' || e.target.closest('a')) {
                console.log('Closing dropdown - link clicked');
                freshDropdown.classList.remove('show');
            }
        });
        
        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && freshDropdown.classList.contains('show')) {
                freshDropdown.classList.remove('show');
            }
        });
        
        console.log('User dropdown initialized successfully');
    } else {
        console.error('User dropdown elements not found');
    }
}

// Mobile Navigation Functionality
function initMobileNavigation() {
    const mobileNavToggle = document.getElementById('mobileNavToggle');
    const mobileNavClose = document.getElementById('mobileNavClose');
    const mobileNav = document.getElementById('mobileNav');
    
    if (!mobileNavToggle || !mobileNav) return;
    
    // Toggle mobile navigation
    mobileNavToggle.addEventListener('click', function() {
        mobileNav.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
    
    // Close mobile navigation
    if (mobileNavClose) {
        mobileNavClose.addEventListener('click', function() {
            mobileNav.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Close mobile nav when clicking on overlay (outside sidebar)
    mobileNav.addEventListener('click', function(e) {
        if (e.target === mobileNav) {
            mobileNav.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // Close mobile nav when clicking on a link
    const mobileNavLinks = mobileNav.querySelectorAll('a');
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', function() {
            mobileNav.classList.remove('active');
            document.body.style.overflow = '';
        });
    });
    
    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileNav.classList.contains('active')) {
            mobileNav.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
}

// Product card interactions
function initProductInteractions() {
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        // Add hover effects
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
        });
        
        // Add to cart animation
        const addToCartBtn = card.querySelector('button[type="submit"]');
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function(e) {
                // Add temporary animation class
                this.classList.add('adding-to-cart');
                
                // Remove animation class after animation completes
                setTimeout(() => {
                    this.classList.remove('adding-to-cart');
                }, 600);
            });
        }
    });
}

// Search functionality
function initSearch() {
    const searchForms = document.querySelectorAll('.search-form, .search-box');
    
    searchForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[type="text"]');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                showNotification('Please enter a search term', 'error');
            }
        });
    });
}

// Products page search functionality
function initProductsSearch() {
    const searchInput = document.querySelector('input[name="search"]');
    const categorySelect = document.querySelector('select[name="category"]');
    const sortSelect = document.querySelector('select[name="sort"]');
    const applyFiltersBtn = document.querySelector('button[type="submit"]');
    const filtersForm = document.querySelector('.filters-grid');
    
    if (!filtersForm) return; // Only run on products page
    
    console.log('Initializing products search...');
    
    // Show loading state when searching
    if (applyFiltersBtn) {
        const originalText = applyFiltersBtn.innerHTML;
        
        // Listen to form submission
        filtersForm.addEventListener('submit', function(e) {
            // Don't prevent default - let form submit normally
            applyFiltersBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
            applyFiltersBtn.disabled = true;
            
            // Re-enable button after a timeout (in case submission fails)
            setTimeout(() => {
                applyFiltersBtn.innerHTML = originalText;
                applyFiltersBtn.disabled = false;
            }, 3000);
        });
    }
    
    // Real-time search with auto-submit
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Only auto-submit if there are 3+ characters or empty
                if (e.target.value.length >= 3 || e.target.value.length === 0) {
                    console.log('Auto-submitting search:', e.target.value);
                    filtersForm.submit();
                }
            }, 800);
        });
    }
    
    // Auto-submit when category or sort changes
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            console.log('Category changed, submitting form');
            filtersForm.submit();
        });
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            console.log('Sort changed, submitting form');
            filtersForm.submit();
        });
    }
    
    // Update page header with search results info
    updateSearchResultsInfo();
}

// Update search results information
function updateSearchResultsInfo() {
    const searchKeywords = document.querySelector('input[name="search"]')?.value || '';
    const categoryFilter = document.querySelector('select[name="category"]')?.value || '';
    const resultsCount = document.querySelectorAll('.product-card').length;
    const pageHeader = document.querySelector('.page-header p');
    
    if ((searchKeywords || categoryFilter) && pageHeader) {
        let message = '';
        
        if (resultsCount > 0) {
            message = `Found ${resultsCount} product${resultsCount !== 1 ? 's' : ''}`;
            
            if (searchKeywords) {
                message += ` for "${searchKeywords}"`;
            }
            
            if (categoryFilter) {
                const categoryName = document.querySelector(`select[name="category"] option[value="${categoryFilter}"]`)?.textContent || '';
                if (searchKeywords) {
                    message += ` in ${categoryName}`;
                } else {
                    message += ` in ${categoryName} category`;
                }
            }
        } else {
            message = 'No products found';
            if (searchKeywords) {
                message += ` for "${searchKeywords}"`;
            }
            if (categoryFilter) {
                const categoryName = document.querySelector(`select[name="category"] option[value="${categoryFilter}"]`)?.textContent || '';
                message += searchKeywords ? ` in ${categoryName}` : ` in ${categoryName} category`;
            }
            message += '. Try different keywords or categories.';
        }
        
        pageHeader.textContent = message;
    }
}

// ============================================================================
// ADMIN DASHBOARD SPECIFIC FUNCTIONS
// ============================================================================

// Admin dashboard specific initialization
function initAdminDashboard() {
    console.log('Initializing admin dashboard components...');
    
    // Tab functionality
    const tabHeaders = document.querySelectorAll('.tab-header');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const tabName = this.getAttribute('onclick')?.match(/showTab\('([^']+)'\)/)?.[1];
            if (tabName) {
                showTab(tabName);
            }
        });
    });
    
    // Initialize URL tab parameter
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab && ['dashboard', 'products', 'orders'].includes(tab)) {
        showTab(tab);
    }
    
    console.log('Admin dashboard components initialized');
}

// Tab functionality for admin dashboard
function showTab(tabName) {
    // Hide all tab panes
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('active');
    });
    
    // Show selected tab pane
    const targetTab = document.getElementById(tabName + '-tab');
    if (targetTab) {
        targetTab.classList.add('active');
    }
    
    // Update tab header states
    document.querySelectorAll('.tab-header').forEach(header => {
        header.classList.remove('active');
    });
    
    // Set current tab header as active
    event.target.classList.add('active');
}

// Update stock modal
function updateStock(productId, productName, currentStock) {
    const newStock = prompt(`Update stock for "${productName}"\nCurrent stock: ${currentStock}\n\nEnter new stock quantity:`, currentStock);
    
    if (newStock !== null && newStock !== '' && !isNaN(newStock)) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const productIdInput = document.createElement('input');
        productIdInput.name = 'product_id';
        productIdInput.value = productId;
        form.appendChild(productIdInput);
        
        const stockInput = document.createElement('input');
        stockInput.name = 'stock_quantity';
        stockInput.value = newStock;
        form.appendChild(stockInput);
        
        const submitInput = document.createElement('input');
        submitInput.name = 'update_stock';
        submitInput.value = '1';
        form.appendChild(submitInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Update order status modal
function updateOrderStatus(orderId, currentStatus) {
    const newStatus = prompt(`Update order #${orderId.toString().padStart(6, '0')}\nCurrent status: ${currentStatus}\n\nEnter new status (pending/completed/cancelled):`, currentStatus);
    
    if (newStatus && ['pending', 'completed', 'cancelled'].includes(newStatus.toLowerCase())) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const orderIdInput = document.createElement('input');
        orderIdInput.name = 'order_id';
        orderIdInput.value = orderId;
        form.appendChild(orderIdInput);
        
        const statusInput = document.createElement('input');
        statusInput.name = 'status';
        statusInput.value = newStatus.toLowerCase();
        form.appendChild(statusInput);
        
        const submitInput = document.createElement('input');
        submitInput.name = 'update_order_status';
        submitInput.value = '1';
        form.appendChild(submitInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// View order details
function viewOrder(orderId) {
    alert(`Viewing order #${orderId.toString().padStart(6, '0')}\n\nIn a real application, this would show detailed order information including:\n- Customer details\n- Shipping address\n- Order items\n- Payment information\n- Order history`);
}

// Edit product
function editProduct(productId) {
    alert(`Editing product #${productId}\n\nIn a real application, this would open a product edit form with pre-filled data.`);
}

// ============================================================================
// MAIN INITIALIZATION FUNCTION
// ============================================================================

// Initialize all functionality
function initDashboard() {
    console.log('Initializing dashboard components...');
    initUserDropdown();
    initMobileNavigation();
    initProductInteractions();
    initSearch();
    initProductsSearch();
    
    // Check if we're on admin dashboard
    const isAdminDashboard = document.querySelector('.admin-page') !== null || 
                            document.querySelector('.admin-welcome') !== null ||
                            document.querySelector('.admin-subtitle') !== null;
    
    if (isAdminDashboard) {
        console.log('Admin dashboard detected - initializing admin components');
        initAdminDashboard();
    }
    
    console.log('All dashboard components initialized');
}

// Export functions for global access
window.showNotification = showNotification;
window.subscribeNewsletter = subscribeNewsletter;
window.initDashboard = initDashboard;
window.initProductsSearch = initProductsSearch;
window.updateSearchResultsInfo = updateSearchResultsInfo;

// Export admin dashboard functions for global access
window.showTab = showTab;
window.updateStock = updateStock;
window.updateOrderStatus = updateOrderStatus;
window.viewOrder = viewOrder;
window.editProduct = editProduct;
window.initAdminDashboard = initAdminDashboard;