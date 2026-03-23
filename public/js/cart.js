// Enhanced Cart JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart functionality
    initCartFunctionality();
    initAddToCartButtons();
    initQuantityControls();
    initPickupOptions();
});

function initCartFunctionality() {
    // Update cart count in header if cart count element exists
    updateCartCount();
}

function initAddToCartButtons() {
    // Handle add to cart buttons with modern UI feedback
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            
            if (!productId) {
                showNotification('Error: Product ID not found', 'error');
                return;
            }
            
            // Check if quantity modal function exists (from products page)
            if (typeof window.showQuantityModal === 'function') {
                // Use enhanced quantity modal
                window.showQuantityModal(productId);
            } else {
                // Fallback to direct add (for cart page)
                // Show loading state
                const originalContent = this.innerHTML;
                this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>';
                this.disabled = true;
                
                // Add to cart via AJAX
                addToCart(productId, 1)
                    .then(response => {
                        if (response.success) {
                            showNotification('Item added to cart!', 'success');
                            updateCartCount(response.cart ? response.cart.length : null);
                            
                            // Animate button to show success
                            this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
                            this.style.background = '#22c55e';
                            
                            setTimeout(() => {
                                this.innerHTML = originalContent;
                                this.style.background = '';
                                this.disabled = false;
                            }, 2000);
                        } else {
                            showNotification(response.error || 'Failed to add item to cart', 'error');
                            this.innerHTML = originalContent;
                            this.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error adding item to cart', 'error');
                        this.innerHTML = originalContent;
                        this.disabled = false;
                    });
            }
        });
    });
}

function initQuantityControls() {
    // Handle quantity increase/decrease buttons
    const decreaseButtons = document.querySelectorAll('.quantity-btn.decrease');
    const increaseButtons = document.querySelectorAll('.quantity-btn.increase');
    
    decreaseButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = this.dataset.productId;
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            if (input) {
                const currentValue = parseInt(input.value);
                const newValue = Math.max(1, currentValue - 1);
                if (newValue !== currentValue) {
                    updateQuantity(productId, newValue);
                }
            }
        });
    });
    
    increaseButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = this.dataset.productId;
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            if (input) {
                const currentValue = parseInt(input.value);
                const maxValue = parseInt(input.max) || 99;
                const newValue = Math.min(maxValue, currentValue + 1);
                if (newValue !== currentValue) {
                    updateQuantity(productId, newValue);
                }
            }
        });
    });
}

function initPickupOptions() {
    const pickupRadios = document.querySelectorAll('input[name="pickup_mode"]');
    const meetupOptions = document.getElementById('meetup-options');
    const deliveryOptions = document.getElementById('delivery-options');
    const checkoutForm = document.querySelector('.checkout-form');
    
    if (pickupRadios && meetupOptions && deliveryOptions) {
        // Force initial state
        meetupOptions.style.display = 'none';
        deliveryOptions.style.display = 'block';
        
        pickupRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'meetup') {
                    // Force the display changes with !important
                    meetupOptions.style.setProperty('display', 'block', 'important');
                    deliveryOptions.style.setProperty('display', 'none', 'important');
                    
                    // Add required attributes when meetup is selected
                    document.getElementById('meetup_time').setAttribute('required', '');
                    document.getElementById('meetup_place').setAttribute('required', '');
                    // Remove required from delivery fields
                    document.getElementById('dorm_building').removeAttribute('required');
                    document.getElementById('room_number').removeAttribute('required');
                } else {
                    // Force the display changes with !important
                    meetupOptions.style.setProperty('display', 'none', 'important');
                    deliveryOptions.style.setProperty('display', 'block', 'important');
                    
                    // Remove required from meetup fields
                    document.getElementById('meetup_time').removeAttribute('required');
                    document.getElementById('meetup_place').removeAttribute('required');
                    // Add required attributes for delivery
                    document.getElementById('dorm_building').setAttribute('required', '');
                    document.getElementById('room_number').setAttribute('required', '');
                }
            });
        });
        
        // Initialize with delivery options visible and delivery fields required
        if (deliveryOptions) {
            deliveryOptions.style.setProperty('display', 'block', 'important');
            document.getElementById('dorm_building').setAttribute('required', '');
            document.getElementById('room_number').setAttribute('required', '');
        }
    }
    
    // Add form validation
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const pickupMode = document.querySelector('input[name="pickup_mode"]:checked')?.value;
            
            if (pickupMode === 'meetup') {
                const meetupTime = document.getElementById('meetup_time').value;
                const meetupPlace = document.getElementById('meetup_place').value;
                
                if (!meetupTime || !meetupPlace) {
                    e.preventDefault();
                    showNotification('Please fill in all required meetup information.', 'error');
                    return false;
                }
            } else if (pickupMode === 'delivery') {
                const dormBuilding = document.getElementById('dorm_building').value;
                const roomNumber = document.getElementById('room_number').value;
                
                if (!dormBuilding || !roomNumber) {
                    e.preventDefault();
                    showNotification('Please fill in all required delivery information.', 'error');
                    return false;
                }
            }
        });
    }
}

function addToCart(productId, quantity = 1) {
    console.log('addToCart called with:', { productId, quantity });
    
    return fetch('/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin', // Important for session cookies
        body: JSON.stringify({
            productId: productId,
            quantity: quantity
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        return data;
    })
    .catch(error => {
        console.error('Fetch error:', error);
        throw error;
    });
}

function updateQuantity(productId, newQuantity) {
    if (newQuantity < 1) return;
    
    // Show loading state
    const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
    if (input) {
        input.style.opacity = '0.5';
        input.disabled = true;
    }
    
    // Use the new update-cart-item endpoint to set the quantity
    fetch('/update-cart-item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            productId: productId,
            quantity: newQuantity
        })
    })
    .then(response => {
        console.log('Update quantity response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Update quantity response data:', data);
        if (data.success) {
            showNotification('Cart updated!', 'success');
            updateCartCount(data.cart ? Object.keys(data.cart).length : null);
            
            // Update the input value and remove loading state
            if (input) {
                input.value = newQuantity;
                input.style.opacity = '1';
                input.disabled = false;
            }
            
            // Update the subtotal for this item
            updateItemSubtotal(productId, newQuantity);
            
            // Update the cart total
            updateCartTotal(data.cart);
            
        } else {
            showNotification(data.error || 'Failed to update quantity', 'error');
            if (input) {
                input.style.opacity = '1';
                input.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error updating quantity:', error);
        showNotification('Error updating quantity', 'error');
        if (input) {
            input.style.opacity = '1';
            input.disabled = false;
        }
    });
}

// Remove old functions to prevent conflicts

function updateItemSubtotal(productId, newQuantity) {
    // Find the cart item card for this product
    const cartItemCard = document.querySelector(`.quantity-input[data-product-id="${productId}"]`).closest('.cart-item-card');
    if (cartItemCard) {
        // Get the price from the item details
        const priceElement = cartItemCard.querySelector('.item-price');
        const subtotalElement = cartItemCard.querySelector('.subtotal-amount');
        
        if (priceElement && subtotalElement) {
            // Extract price from the text (remove currency symbol and convert to number)
            const priceText = priceElement.textContent;
            const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
            
            // Calculate new subtotal
            const newSubtotal = price * newQuantity;
            
            // Update the subtotal display
            subtotalElement.textContent = formatPrice(newSubtotal);
        }
    }
}

function updateCartTotal(cart) {
    let total = 0;
    
    // Calculate total from cart data
    for (const productId in cart) {
        const item = cart[productId];
        total += item.product.price * item.quantity;
    }
    
    // Update the total display in the order summary
    const totalElements = document.querySelectorAll('.total-amount, .summary-value.total-amount');
    totalElements.forEach(element => {
        element.textContent = formatPrice(total);
    });
    
    // Update the subtotal in the order summary
    const subtotalElements = document.querySelectorAll('.summary-item:not(.total) .summary-value');
    subtotalElements.forEach(element => {
        const itemText = element.textContent;
        if (itemText.includes('items')) {
            const itemCount = Object.keys(cart).length;
            element.textContent = `${formatPrice(total)} (${itemCount} items)`;
        }
    });
}

function formatPrice(amount) {
    // Match the PHP formatPrice function: 'Php ' + number_format(price, 2)
    return 'Php ' + amount.toFixed(2);
}

function removeItem(productId) {
    if (confirm('Remove this item from cart?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="product_id" value="${productId}">
            <input type="hidden" name="remove" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function clearAllItems() {
    if (confirm('Clear all items from cart?')) {
        // This would need a backend endpoint to clear all items
        location.reload();
    }
}

function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('.cart-count, .cart-badge, .cart-counter');
    
    cartCountElements.forEach(element => {
        if (count !== null) {
            element.textContent = count;
            element.style.display = count > 0 ? 'inline-block' : 'none';
        } else {
            // If count is not provided, try to fetch it
            fetchCartCount().then(fetchedCount => {
                element.textContent = fetchedCount;
                element.style.display = fetchedCount > 0 ? 'inline-block' : 'none';
            });
        }
    });
}

function fetchCartCount() {
    return fetch('/add-to-cart.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => {
        // If the endpoint doesn't support GET, return 0
        if (!response.ok) return 0;
        return response.json();
    })
    .then(data => {
        return data.cart ? data.cart.length : 0;
    })
    .catch(() => 0);
}

function showNotification(message, type = 'info') {
    // Remove any existing notifications
    const existingNotifications = document.querySelectorAll('.notification-toast');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification-toast ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">
                ${getNotificationIcon(type)}
            </div>
            <div class="notification-message">${message}</div>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 400px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.1);
        animation: slideInRight 0.3s ease;
    `;
    
    if (type === 'success') {
        notification.style.borderLeft = '4px solid #22c55e';
    } else if (type === 'error') {
        notification.style.borderLeft = '4px solid #ef4444';
    } else {
        notification.style.borderLeft = '4px solid #3b82f6';
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

function getNotificationIcon(type) {
    if (type === 'success') {
        return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
    } else if (type === 'error') {
        return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    } else {
        return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>';
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
    }
    
    .notification-icon {
        flex-shrink: 0;
    }
    
    .notification-message {
        flex: 1;
        font-weight: 500;
        color: #1f2937;
    }
    
    .notification-close {
        background: none;
        border: none;
        cursor: pointer;
        color: #6b7280;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    
    .notification-close:hover {
        background: #f3f4f6;
        color: #374151;
    }
`;
document.head.appendChild(style);
