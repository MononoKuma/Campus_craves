// Review System JavaScript
class ReviewSystem {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadReviewData();
    }

    bindEvents() {
        // Review form submission
        const reviewForms = document.querySelectorAll('#review-form');
        reviewForms.forEach(form => {
            form.addEventListener('submit', this.handleReviewSubmit.bind(this));
        });

        // Rating star interactions
        const ratingInputs = document.querySelectorAll('.rating-radio');
        ratingInputs.forEach(input => {
            input.addEventListener('change', this.updateRatingDisplay.bind(this));
        });

        // Helpful vote buttons
        const helpfulBtns = document.querySelectorAll('.helpful-btn');
        helpfulBtns.forEach(btn => {
            btn.addEventListener('click', this.handleHelpfulVote.bind(this));
        });

        // Review edit/delete buttons
        const editBtns = document.querySelectorAll('.edit-review-btn');
        const deleteBtns = document.querySelectorAll('.delete-review-btn');
        
        editBtns.forEach(btn => {
            btn.addEventListener('click', this.handleEditReview.bind(this));
        });

        deleteBtns.forEach(btn => {
            btn.addEventListener('click', this.handleDeleteReview.bind(this));
        });

        // Product detail buttons
        const detailBtns = document.querySelectorAll('.view-details-btn');
        detailBtns.forEach(btn => {
            btn.addEventListener('click', this.showProductDetails.bind(this));
        });
    }

    handleReviewSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        
        fetch('submit-review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(data.message, 'success');
                if (form.querySelector('input[name="action"]').value === 'submit') {
                    // Reset form for new reviews
                    form.reset();
                } else {
                    // Redirect back to product page for updates
                    setTimeout(() => {
                        window.location.href = '/products.php';
                    }, 1500);
                }
            } else {
                this.showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }

    updateRatingDisplay(e) {
        const rating = e.target.value;
        const container = e.target.closest('.rating-input');
        const stars = container.querySelectorAll('.rating-label');
        
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('star-selected');
            } else {
                star.classList.remove('star-selected');
            }
        });
    }

    handleHelpfulVote(e) {
        const btn = e.target;
        const reviewId = btn.dataset.reviewId || btn.getAttribute('onclick').match(/\d+/)[0];
        
        if (btn.disabled) return;
        
        const formData = new FormData();
        formData.append('action', 'helpful');
        formData.append('review_id', reviewId);
        
        btn.disabled = true;
        btn.textContent = 'Voting...';
        
        fetch('submit-review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update helpful count
                const currentCount = parseInt(btn.textContent.match(/\d+/)[0]);
                btn.textContent = `👍 Helpful (${currentCount + 1})`;
                btn.disabled = true;
            } else {
                btn.textContent = `👍 Helpful (${currentCount})`;
                btn.disabled = false;
                this.showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.textContent = `👍 Helpful (${currentCount})`;
            btn.disabled = false;
            this.showNotification('An error occurred. Please try again.', 'error');
        });
    }

    handleEditReview(e) {
        const reviewId = e.target.dataset.reviewId || e.target.getAttribute('onclick').match(/\d+/)[0];
        // Redirect to edit page or open modal
        window.location.href = `/submit-review.php?review_id=${reviewId}`;
    }

    handleDeleteReview(e) {
        const reviewId = e.target.dataset.reviewId || e.target.getAttribute('onclick').match(/\d+/)[0];
        
        if (!confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('review_id', reviewId);
        
        fetch('submit-review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(data.message, 'success');
                // Remove review card from DOM
                const reviewCard = e.target.closest('.review-card');
                reviewCard.style.transition = 'opacity 0.3s ease';
                reviewCard.style.opacity = '0';
                setTimeout(() => {
                    reviewCard.remove();
                    this.updateReviewSummary();
                }, 300);
            } else {
                this.showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('An error occurred. Please try again.', 'error');
        });
    }

    showProductDetails(e) {
        const productId = e.target.dataset.productId;
        // Create and show product details modal
        this.showProductModal(productId);
    }

    showProductModal(productId) {
        // Check if modal already exists
        let modal = document.getElementById(`product-modal-${productId}`);
        if (modal) {
            modal.style.display = 'flex';
            return;
        }

        // Create modal if it doesn't exist
        modal = document.createElement('div');
        modal.id = `product-modal-${productId}`;
        modal.className = 'product-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 3000;
        `;

        // Load product details
        this.loadProductDetails(productId, modal);
        document.body.appendChild(modal);
    }

    loadProductDetails(productId, modal) {
        fetch(`/api/product.php?id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.renderProductModal(data.product, modal);
            } else {
                this.showNotification('Product not found', 'error');
                modal.remove();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('Failed to load product details', 'error');
            modal.remove();
        });
    }

    renderProductModal(product, modal) {
        const modalContent = document.createElement('div');
        modalContent.className = 'modal-card';
        modalContent.style.cssText = `
            background: white;
            border-radius: 16px;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            margin: 20px;
        `;

        modalContent.innerHTML = `
            <button class="close-modal-btn" onclick="this.closest('.product-modal').remove()" 
                    style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 2rem; cursor: pointer; z-index: 10;">
                ×
            </button>
            <div style="display: flex; gap: 2rem; padding: 2rem;">
                <div style="flex: 0 0 300px;">
                    <img src="${product.image_path || '/images/products/default.jpg'}" 
                         alt="${product.name}" 
                         style="width: 100%; height: 300px; object-fit: cover; border-radius: 12px;">
                </div>
                <div style="flex: 1;">
                    <h2 style="margin: 0 0 1rem 0; color: var(--text-primary);">${product.name}</h2>
                    <div class="product-rating rating-large" style="margin-bottom: 1rem;">
                        ${this.generateStarRating(product.average_rating || 0)}
                        <span class="rating-text">(${product.review_count || 0} reviews)</span>
                    </div>
                    <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 1.5rem;">${product.description}</p>
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                        <span style="font-size: 1.5rem; font-weight: 600; color: var(--primary-blue);">${this.formatPrice(product.price)}</span>
                        <span class="stock-status ${product.stock_quantity > 0 ? 'in-stock' : 'out-of-stock'}" 
                              style="padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem;">
                            ${product.stock_quantity > 0 ? `In Stock (${product.stock_quantity})` : 'Out of Stock'}
                        </span>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button class="modern-button primary" onclick="window.location.href='/submit-review.php?product_id=${product.id}'">
                            Write Review
                        </button>
                        <button class="modern-button secondary" onclick="this.closest('.product-modal').remove()">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        `;

        modal.appendChild(modalContent);

        // Add click outside to close
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    generateStarRating(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            const filled = i <= Math.round(rating) ? 'star-filled' : 'star-empty';
            stars += `<span class="rating-star ${filled}">★</span>`;
        }
        return stars;
    }

    formatPrice(price) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(price);
    }

    loadReviewData() {
        // Load review statistics and ratings for products on the page
        const productElements = document.querySelectorAll('[data-product-id]');
        const productIds = Array.from(productElements).map(el => el.dataset.productId);
        
        if (productIds.length === 0) return;
        
        // This would typically load from an API endpoint
        // For now, ratings are already embedded in the HTML
    }

    updateReviewSummary() {
        // Update review summary after deleting a review
        const summaryElement = document.querySelector('.review-summary');
        if (summaryElement) {
            // Reload the page to update summary
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }

    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existing = document.querySelector('.notification');
        if (existing) {
            existing.remove();
        }

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 4000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 400px;
        `;

        const colors = {
            success: 'linear-gradient(135deg, #059669, #047857)',
            error: 'linear-gradient(135deg, #dc2626, #b91c1c)',
            info: 'linear-gradient(135deg, #2563eb, #1e40af)'
        };

        notification.style.background = colors[type] || colors.info;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Remove after 5 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }
}

// Global functions for inline onclick handlers
window.markHelpful = function(reviewId) {
    const reviewSystem = new ReviewSystem();
    const btn = document.querySelector(`.helpful-btn[onclick*="${reviewId}"]`);
    if (btn) {
        btn.click();
    }
};

window.editReview = function(reviewId) {
    window.location.href = `/submit-review.php?review_id=${reviewId}`;
};

window.deleteReview = function(reviewId) {
    if (confirm('Are you sure you want to delete this review?')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('review_id', reviewId);
        
        fetch('submit-review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message);
            }
        });
    }
};

// Initialize review system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ReviewSystem();
});

// Add CSS for star rating interactions
const style = document.createElement('style');
style.textContent = `
    .rating-label.star-selected {
        color: #fbbf24 !important;
    }
    
    .rating-label:hover {
        color: #fbbf24 !important;
        transform: scale(1.1);
    }
    
    .modal-card {
        animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);
