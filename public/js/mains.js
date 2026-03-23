// IMPORTANT: If you change this file, do a hard refresh (Ctrl+F5) or use a cache-busting query string in your HTML script tag!
// Example: <script src="/js/mains.js?v=2"></script>
// Shopping Cart Toggle
document.addEventListener('DOMContentLoaded', function() {
    // Quantity Modal logic
    let selectedProductId = null;
    const quantityModal = document.getElementById('quantity-modal');
    const quantityInput = document.getElementById('modal-quantity-input');
    const modalAddBtn = document.getElementById('modal-add-btn');
    const modalCancelBtn = document.getElementById('modal-cancel-btn');

    function showQuantityModal(productId) {
        selectedProductId = productId;
        if (quantityInput) quantityInput.value = 1;
        if (quantityModal) {
            quantityModal.style.display = 'flex';
            quantityModal.classList.add('show');
        }
    }
    function hideQuantityModal() {
        selectedProductId = null;
        if (quantityModal) {
            quantityModal.classList.remove('show');
            setTimeout(() => {
                quantityModal.style.display = 'none';
            }, 300);
        }
    }
    if (modalAddBtn) {
        modalAddBtn.onclick = function() {
            const qty = parseInt(quantityInput.value, 10) || 1;
            if (selectedProductId) {
                addToCart(selectedProductId, qty);
            }
            hideQuantityModal();
        };
    }
    if (modalCancelBtn) {
        modalCancelBtn.onclick = function() {
            hideQuantityModal();
        };
    }
    // Add to cart buttons (form and button)
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            showQuantityModal(productId);
        });
    });
    
    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const password = this.querySelector('#password');
            const confirmPassword = this.querySelector('#confirm_password');
            
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    });

    // Product Details Modal logic
    document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const modal = document.getElementById('product-modal-' + productId);
            if (modal) modal.style.display = 'flex';
        });
    });
    document.querySelectorAll('.close-modal-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const modal = document.getElementById('product-modal-' + productId);
            if (modal) modal.style.display = 'none';
        });
    });
    // Optional: Close modal when clicking outside the modal card
    document.querySelectorAll('.product-modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Interactive Star Ratings
    initInteractiveStarRatings();
});

function addToCart(productId, quantity = 1) {
    // Find and animate the button
    const button = document.querySelector(`.add-to-cart[data-product-id="${productId}"]`);
    if (button) {
        button.classList.add('loading');
        button.disabled = true;
    }
    
    fetch('/add-to-cart.php', { // <-- .php is required!
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ productId, quantity })
    })
    .then(async response => {
        if (!response.ok) {
            // Try to extract error message from response, fallback to status text
            let errorMsg = response.statusText;
            try {
                const data = await response.json();
                errorMsg = data.error || errorMsg;
            } catch (e) {}
            throw new Error(errorMsg);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success animation
            if (button) {
                button.classList.remove('loading');
                button.classList.add('success');
                button.textContent = '✓ Added';
                
                // Reset button after animation
                setTimeout(() => {
                    button.classList.remove('success');
                    button.textContent = 'Add to Cart';
                    button.disabled = false;
                }, 2000);
            }
            
            updateCartUI(data.cart);
            
            // Show subtle success notification
            showNotification('Product added to cart successfully!', 'success');
        } else {
            // Reset button on error
            if (button) {
                button.classList.remove('loading');
                button.disabled = false;
            }
            showNotification('Failed to add to cart: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        // Reset button on error
        if (button) {
            button.classList.remove('loading');
            button.disabled = false;
        }
        showNotification('Failed to add to cart: ' + error.message, 'error');
        console.error(error);
    });
}

function updateCartUI(cart) {
    const cartBadge = document.querySelector('.cart-badge');
    if (cartBadge) {
        const itemCount = Object.keys(cart).length;
        cartBadge.textContent = itemCount;
        cartBadge.style.display = itemCount > 0 ? 'flex' : 'none';
    }
    
    // Update cart sidebar if open
    const cartSidebar = document.querySelector('.cart-sidebar');
    if (cartSidebar && cartSidebar.classList.contains('open')) {
        renderCartItems(cart);
    }
}

function renderCartItems(cart) {
    const cartItemsContainer = document.querySelector('.cart-items');
    const cartTotalElement = document.querySelector('.cart-total');
    
    if (!cartItemsContainer || !cartTotalElement) return;
    
    cartItemsContainer.innerHTML = '';
    let total = 0;
    
    Object.entries(cart).forEach(([productId, item]) => {
        const cartItemElement = document.createElement('div');
        cartItemElement.className = 'cart-item';
        
        cartItemElement.innerHTML = `
            <div>
                <h4>${item.product.name}</h4>
                <p>${item.quantity} x $${item.product.price.toFixed(2)}</p>
            </div>
            <div>
                <button class="remove-item" data-product-id="${productId}">×</button>
            </div>
        `;
        
        cartItemsContainer.appendChild(cartItemElement);
        total += item.product.price * item.quantity;
    });
    
    cartTotalElement.textContent = `Total: $${total.toFixed(2)}`;
    
    // Add event listeners to remove buttons
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function() {
            removeFromCart(this.dataset.productId);
        });
    });
}

// Modern notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notif => notif.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    // Style the notification
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '16px 24px',
        borderRadius: '12px',
        color: 'white',
        fontWeight: '600',
        zIndex: '4000',
        transform: 'translateX(100%)',
        transition: 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
        boxShadow: '0 8px 25px rgba(0, 0, 0, 0.15)',
        maxWidth: '300px',
        wordWrap: 'break-word'
    });
    
    // Set background based on type
    if (type === 'success') {
        notification.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
    } else if (type === 'error') {
        notification.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
    } else {
        notification.style.background = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

function removeFromCart(productId) {
    fetch('remove-from-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ productId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartUI(data.cart);
        }
    });
}

// Interactive Star Ratings Functionality
function initInteractiveStarRatings() {
    const interactiveRatings = document.querySelectorAll('.product-rating.rating-interactive');
    
    interactiveRatings.forEach(ratingContainer => {
        const stars = ratingContainer.querySelectorAll('.rating-star');
        const productId = ratingContainer.dataset.productId;
        const currentRating = parseFloat(ratingContainer.dataset.currentRating) || 0;
        
        // Add event listeners to each star
        stars.forEach((star, index) => {
            const starValue = parseInt(star.dataset.starValue);
            
            // Hover effect
            star.addEventListener('mouseenter', function() {
                updateStarDisplay(ratingContainer, starValue, true);
                updateTooltip(ratingContainer, starValue);
            });
            
            // Click to set rating
            star.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                submitProductRating(productId, starValue, ratingContainer);
            });
        });
        
        // Reset on mouse leave
        ratingContainer.addEventListener('mouseleave', function() {
            updateStarDisplay(ratingContainer, currentRating, false);
            updateTooltip(ratingContainer, currentRating);
        });
        
        // Initialize tooltip
        updateTooltip(ratingContainer, currentRating);
    });
}

function updateStarDisplay(ratingContainer, rating, isHover) {
    const stars = ratingContainer.querySelectorAll('.rating-star');
    
    stars.forEach((star, index) => {
        const starValue = parseInt(star.dataset.starValue);
        
        // Remove all state classes
        star.classList.remove('star-filled', 'star-empty', 'star-hover', 'star-selected');
        
        if (starValue <= rating) {
            if (isHover) {
                star.classList.add('star-hover');
            } else {
                star.classList.add('star-filled');
            }
        } else {
            star.classList.add('star-empty');
        }
    });
}

function updateTooltip(ratingContainer, rating) {
    const tooltips = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
    const tooltip = tooltips[rating] || '';
    ratingContainer.setAttribute('data-rating-tooltip', tooltip);
}

function submitProductRating(productId, rating, ratingContainer) {
    // Show loading state
    ratingContainer.style.opacity = '0.6';
    ratingContainer.style.pointerEvents = 'none';
    
    fetch('/submit-review.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            rating: rating,
            quick_rating: true // Flag to indicate this is a quick rating from product card
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the display with new rating
            ratingContainer.dataset.currentRating = data.new_average_rating || rating;
            updateStarDisplay(ratingContainer, data.new_average_rating || rating, false);
            
            // Update rating count if available
            const countElement = ratingContainer.querySelector('.rating-count');
            if (countElement && data.new_review_count) {
                countElement.textContent = '(' + (data.new_average_rating || rating).toFixed(1) + ')';
            }
            
            // Show success animation
            const stars = ratingContainer.querySelectorAll('.rating-star');
            stars.forEach(star => {
                star.classList.add('star-selected');
                setTimeout(() => {
                    star.classList.remove('star-selected');
                }, 300);
            });
            
            showNotification('Thank you for your rating!', 'success');
        } else {
            showNotification('Failed to submit rating: ' + (data.error || 'Please try again'), 'error');
        }
    })
    .catch(error => {
        console.error('Rating submission error:', error);
        showNotification('Failed to submit rating. Please try again.', 'error');
    })
    .finally(() => {
        // Restore interactive state
        ratingContainer.style.opacity = '1';
        ratingContainer.style.pointerEvents = 'auto';
    });
}