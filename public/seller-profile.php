<?php
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/controllers/ProductController.php';
require_once __DIR__ . '/../src/models/Users.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('/login.php');
}

$sellerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sellerId <= 0) {
    redirect('/dashboard.php');
}

$userModel = new User();
$seller = $userModel->getUserById($sellerId);

if (!$seller || $seller['role'] !== 'seller') {
    setFlashMessage('Seller not found.', 'error');
    redirect('/dashboard.php');
}

$productController = new ProductController();
$sellerProducts = $productController->getProductsBySeller($sellerId);

function getProductImageUrl($imagePath) {
    if (!$imagePath) {
        return '/images/products/default.jpg';
    }
    if (strpos($imagePath, 'products/') === 0) {
        return '/images/' . $imagePath;
    }
    return '/images/products/' . $imagePath;
}
?>

<?php require_once __DIR__ . '/../src/views/partials/header.php'; ?>

<div class="brass-panel">
    <div class="seller-profile">
        <div class="seller-header">
            <div class="seller-info">
                <h1 class="gears-title"><?php echo htmlspecialchars($seller['username']); ?>'s Shop</h1>
                <p class="seller-bio"><?php echo htmlspecialchars($seller['seller_application_reason'] ?? 'No description available.'); ?></p>
                <p class="seller-since">Seller since: <?php echo $seller['became_seller_at'] ? date('F j, Y', strtotime($seller['became_seller_at'])) : 'Recently'; ?></p>
            </div>
            <div class="seller-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($sellerProducts); ?></span>
                    <span class="stat-label">Products</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">⭐ 4.5</span>
                    <span class="stat-label">Rating</span>
                </div>
            </div>
        </div>
        
        <div class="copper-divider"></div>
        
        <h2 class="section-title">Products by <?php echo htmlspecialchars($seller['username']); ?></h2>
        
        <?php if (empty($sellerProducts)): ?>
            <div class="steam-alert">This seller has no products available.</div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($sellerProducts as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars(getProductImageUrl($product['image_path'] ?? 'default.jpg')); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                        
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            
                            <div class="quality-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="gear <?php echo $i <= $product['quality_rating'] ? 'active' : ''; ?>"></span>
                                <?php endfor; ?>
                            </div>
                            
                            <p class="product-description">
                                <?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?><?php echo strlen($product['description']) > 100 ? '...' : ''; ?>
                            </p>
                            
                            <div class="product-footer">
                                <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                                
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <span class="in-stock">In Stock (<?php echo $product['stock_quantity']; ?>)</span>
                                <?php else: ?>
                                    <span class="out-of-stock">Out of Stock</span>
                                <?php endif; ?>
                                
                                <div class="product-actions">
                                    <button type="button" class="piston-button small view-details-btn" data-product-id="<?php echo $product['id']; ?>">Details</button>
                                    
                                    <?php if (isLoggedIn() && $product['stock_quantity'] > 0): ?>
                                        <button type="button" class="piston-button small add-to-cart" 
                                                data-product-id="<?php echo $product['id']; ?>">
                                            Add to Cart
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../src/views/partials/footer.php'; ?>
