// Global flag to prevent double initialization
let cartInitialized = false;

document.addEventListener('DOMContentLoaded', function() {
    if (cartInitialized) return;
    cartInitialized = true;
    
    console.log('=== CART INITIALIZATION START ===');
    initCart();
    console.log('=== CART INITIALIZATION COMPLETE ===');
});

// Show notification
function showNotification(message, type = 'info') {
    // Remove existing notifications
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
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

// Remove function
async function removeFromCart(event, itemId) {
    event.preventDefault();

    if (!confirm('Remove this item from your cart?')) return;

    const form = event.target;
    const button = form.querySelector('.remove-btn');
    const originalHTML = button ? button.innerHTML : '';

    try {
        // Show loading state
        if (button) {
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
            button.disabled = true;
        }

        console.log('Sending remove request for item:', itemId);
        
        const response = await fetch('cart-remove.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ 
                item_id: itemId 
            })
        });

        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Response data:', data);

        if (data.success) {
            // Fade out and remove the item
            const itemElement = form.closest('.cart-item');
            if (itemElement) {
                itemElement.style.transition = 'all 0.3s ease';
                itemElement.style.opacity = '0';
                itemElement.style.transform = 'translateX(-100px)';
                
                setTimeout(() => {
                    itemElement.remove();
                    console.log('Item element removed from DOM');
                    
                    // Update UI with the data from server
                    updateCartCount(data.cart_count);
                    updateOrderSummary(data.cart_total);
                    
                    showNotification('Item removed from cart', 'success');
                    
                    // Show empty cart if needed
                    if (data.cart_count === 0) {
                        console.log('Cart is empty, showing empty state');
                        setTimeout(() => {
                            showEmptyCart();
                        }, 500);
                    }
                }, 300);
            } else {
                console.warn('Item element not found, forcing UI update');
                // If we can't find the element, still update the UI
                updateCartCount(data.cart_count);
                updateOrderSummary(data.cart_total);
                
                if (data.cart_count === 0) {
                    showEmptyCart();
                }
            }
        } else {
            throw new Error(data.error || 'Failed to remove item');
        }

    } catch (error) {
        console.error('Remove error:', error);
        
        // Reset button
        if (button) {
            button.innerHTML = originalHTML;
            button.disabled = false;
        }
        
        showNotification('Failed to remove item: ' + error.message, 'error');
    }
}

// Update cart count in header
function updateCartCount(count) {
    console.log('Updating cart count to:', count);
    
    // Force create badges if they don't exist
    const desktopCartIcon = document.querySelector('.cart-icon');
    if (desktopCartIcon) {
        let countBadge = desktopCartIcon.querySelector('.cart-count');
        
        if (count > 0) {
            if (!countBadge) {
                console.log('Creating new desktop cart badge');
                countBadge = document.createElement('span');
                countBadge.className = 'cart-count';
                countBadge.style.cssText = 'position: absolute; top: -2px; right: -2px; background: #ef4444; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; font-weight: 600;';
                desktopCartIcon.style.position = 'relative';
                desktopCartIcon.appendChild(countBadge);
            }
            countBadge.textContent = count;
            countBadge.style.display = 'flex';
        } else if (countBadge) {
            countBadge.style.display = 'none';
        }
    }

    // Update mobile cart
    const mobileCart = document.querySelector('.mobile-nav-cart');
    if (mobileCart) {
        const mobileCountText = mobileCart.querySelector('.mobile-nav-cart-count');
        let mobileBadge = mobileCart.querySelector('.mobile-nav-cart-badge');
        
        if (mobileCountText) {
            mobileCountText.textContent = `${count} item${count !== 1 ? 's' : ''}`;
        }
        
        if (count > 0) {
            if (!mobileBadge) {
                console.log('Creating new mobile cart badge');
                const mobileCartIcon = mobileCart.querySelector('.mobile-nav-cart-icon');
                mobileBadge = document.createElement('span');
                mobileBadge.className = 'mobile-nav-cart-badge';
                mobileBadge.style.cssText = 'position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; font-weight: 600;';
                mobileCartIcon.style.position = 'relative';
                mobileCartIcon.appendChild(mobileBadge);
            }
            mobileBadge.textContent = count;
            mobileBadge.style.display = 'flex';
        } else if (mobileBadge) {
            mobileBadge.style.display = 'none';
        }
    }
    
    console.log('Cart count update completed');
}

// Update order summary
function updateOrderSummary(total) {
    console.log('Updating order summary to: R' + total);
    
    // Add visual feedback
    const summarySection = document.querySelector('.order-summary');
    if (summarySection) {
        summarySection.style.transition = 'all 0.3s ease';
        summarySection.style.boxShadow = '0 0 0 2px #10b981';
        
        setTimeout(() => {
            summarySection.style.boxShadow = '';
        }, 1000);
    }
    
    // Update all summary values
    const summaryValues = document.querySelectorAll('.summary-value');
    summaryValues.forEach(el => {
        const originalText = el.textContent;
        if (originalText.includes('R')) {
            el.textContent = `R${total}`;
            // Add visual feedback
            el.style.transition = 'all 0.3s ease';
            el.style.color = '#ef4444';
            el.style.fontWeight = 'bold';
            
            setTimeout(() => {
                el.style.color = '';
                el.style.fontWeight = '';
            }, 1000);
        }
    });
    
    console.log('Order summary update completed');
}

// Show empty cart state
function showEmptyCart() {
    console.log('Showing empty cart state');
    
    const cartContent = document.querySelector('.cart-content');
    if (cartContent) {
        cartContent.innerHTML = `
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="../products/products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i>
                    Start Shopping
                </a>
            </div>`;
    }
}

// User dropdown functionality
function initUserDropdown() {
    const userMenuButton = document.getElementById('userMenuButton');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userMenuButton && userDropdown) {
        userMenuButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (userDropdown.classList.contains('show') && 
                !userMenuButton.contains(e.target) && 
                !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('show');
            }
        });
    }
}

// Initialize all cart functionality
function initCart() {
    console.log('Initializing cart components...');
    initUserDropdown();
    console.log('All cart components initialized');
}

// Export functions
window.removeFromCart = removeFromCart;
window.showNotification = showNotification;
window.initCart = initCart;

