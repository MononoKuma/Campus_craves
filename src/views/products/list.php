<?php
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/CartController.php';
require_once __DIR__ . '/../../helpers/review_helpers.php';

$productController = new ProductController();
$cartController = new CartController();
$searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';

// Get cart items for counter
$cartItems = [];
if (isLoggedIn()) {
    $cartItems = $cartController->getCart();
}

// Filter parameters
$allergenFilter = isset($_GET['allergen_filter']) ? $_GET['allergen_filter'] : 'all';
$selectedAllergens = isset($_GET['allergens']) ? (array)$_GET['allergens'] : [];
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : PHP_FLOAT_MAX;
$minRating = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0;

// Get user allergens if logged in
$userAllergens = [];
if (isLoggedIn()) {
    require_once __DIR__ . '/../../models/Users.php';
    $userModel = new User();
    $user = $userModel->getUserById($_SESSION['user_id']);
    if ($user && $user['allergens']) {
        $userAllergens = json_decode($user['allergens'], true) ?: [];
    }
}

if ($searchQuery !== '') {
    $result = $productController->search($searchQuery);
    $products = $result['products'] ?? [];
} else {
    $products = $productController->index();
}

// Apply filters
$filteredProducts = [];
foreach ($products as $product) {
    // Price filter
    if ($product['price'] < $minPrice || $product['price'] > $maxPrice) {
        continue;
    }
    
    // Rating filter
    $productRating = isset($product['average_rating']) ? (float)$product['average_rating'] : 0;
    if ($productRating < $minRating) {
        continue;
    }
    
    // Get product allergens
    $productAllergens = isset($product['allergens']) ? json_decode($product['allergens'], true) : [];
    if (!is_array($productAllergens)) {
        $productAllergens = [];
    }
    
    // User allergen filter (safe/contains my allergens)
    if ($allergenFilter !== 'all' && isLoggedIn() && !empty($userAllergens)) {
        $hasUserAllergens = !empty(array_intersect($productAllergens, $userAllergens));
        
        if ($allergenFilter === 'safe' && $hasUserAllergens) {
            // Skip products that contain user's allergens when looking for safe products
            continue;
        } elseif ($allergenFilter === 'allergen' && !$hasUserAllergens) {
            // Skip products that don't contain user's allergens when looking for allergen-containing products
            continue;
        }
    }
    
    // Specific allergen filter (checkbox selection)
    if (!empty($selectedAllergens)) {
        $hasSelectedAllergen = !empty(array_intersect($productAllergens, $selectedAllergens));
        if (!$hasSelectedAllergen) {
            // Only show products that contain at least one selected allergen
            continue;
        }
    }
    
    $filteredProducts[] = $product;
}

$products = $filteredProducts;

// Determine correct image path for each product
function getProductImageUrl($imagePath) {
    if (!$imagePath) {
        return '/images/products/default.jpg';
    }
    // If path already contains 'products/', just prepend '/images/'
    if (strpos($imagePath, 'products/') === 0) {
        return '/images/' . $imagePath;
    }
    // Otherwise, assume it's just a filename
    return '/images/products/' . $imagePath;
}

// Check if image file exists (simple check)
function imageExists($url) {
    $filePath = __DIR__ . '/../../public' . $url;
    return file_exists($filePath);
}
?>

<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="products-page">
    <div class="products-header">
        <div class="header-content">
            <h1 class="page-title">Browse Products</h1>
        </div>
        
        <div class="header-search">
            <form method="GET" action="/products.php" class="search-form">
                <div class="search-input-group">
                    <input type="text" name="query" placeholder="Search products..." class="search-input" value="<?= htmlspecialchars($searchQuery) ?>">
                    <button type="submit" class="search-button">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                    </button>
                </div>
            </form>
            
            <!-- Shopping Cart Icon with Counter -->
            <div class="header-cart">
                <a href="/cart.php" class="cart-link">
                    <div class="cart-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"/>
                            <circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                        <?php if (isLoggedIn() && !empty($cartItems)): ?>
                            <span class="cart-counter"><?= count($cartItems) ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </div>
        
        <?php if (isAdmin()): ?>
            <div class="admin-actions">
                <a href="/admin/products/create.php" class="admin-button">Add New Product</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Product Filters -->
    <div class="product-filters">
        <div class="filters-header">
            <h3 class="filters-title">Filters</h3>
            <div class="filters-actions">
                <button type="button" class="toggle-filters-btn" id="toggle-filters-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                    Filters
                </button>
                <a href="/products.php" class="clear-filters-btn">Clear All</a>
            </div>
        </div>
        
        <div class="filters-content" id="filters-content">
            <form method="GET" action="/products.php" class="filter-form">
                <input type="hidden" name="query" value="<?= htmlspecialchars($searchQuery) ?>">
                
                <div class="filter-dropdowns">
                    <div class="filter-dropdown">
                        <button type="button" class="dropdown-toggle" data-dropdown="allergen">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                                <path d="M12 8v4"/>
                                <path d="M12 16h.01"/>
                            </svg>
                            Allergens
                            <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="dropdown-content" id="allergen-dropdown">
                            <select name="allergen_filter" class="compact-select">
                                <option value="all" <?= $allergenFilter === 'all' ? 'selected' : '' ?>>All Products</option>
                                <?php if (isLoggedIn() && !empty($userAllergens)): ?>
                                    <option value="safe" <?= $allergenFilter === 'safe' ? 'selected' : '' ?>>Safe for Me</option>
                                    <option value="allergen" <?= $allergenFilter === 'allergen' ? 'selected' : '' ?>>Contains My Allergens</option>
                                <?php endif; ?>
                            </select>
                            <div class="compact-checkboxes">
                                <?php 
                                $allergenOptions = [
                                    'nuts' => 'Nuts',
                                    'dairy' => 'Dairy', 
                                    'gluten' => 'Gluten',
                                    'eggs' => 'Eggs',
                                    'soy' => 'Soy',
                                    'shellfish' => 'Shellfish',
                                    'sesame' => 'Sesame',
                                    'fish' => 'Fish',
                                    'peanuts' => 'Peanuts',
                                    'wheat' => 'Wheat',
                                    'tree nuts' => 'Tree Nuts',
                                    'milk' => 'Milk'
                                ];
                                ?>
                                <?php foreach ($allergenOptions as $value => $label): ?>
                                    <label class="compact-checkbox-label">
                                        <input type="checkbox" name="allergens[]" value="<?= htmlspecialchars($value) ?>"
                                               <?= in_array($value, $selectedAllergens) ? 'checked' : '' ?>
                                               class="compact-checkbox">
                                        <span class="compact-checkbox-text"><?= htmlspecialchars($label) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-dropdown">
                        <button type="button" class="dropdown-toggle" data-dropdown="price">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"/>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                            Price Range
                            <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="dropdown-content" id="price-dropdown">
                            <div class="compact-price-range">
                                <input type="number" name="min_price" placeholder="Min" class="compact-price-input" 
                                       value="<?= $minPrice > 0 ? number_format($minPrice, 2, '.', '') : '' ?>" step="0.01" min="0">
                                <span class="compact-price-separator">-</span>
                                <input type="number" name="max_price" placeholder="Max" class="compact-price-input" 
                                       value="<?= $maxPrice < PHP_FLOAT_MAX ? number_format($maxPrice, 2, '.', '') : '' ?>" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-dropdown">
                        <button type="button" class="dropdown-toggle" data-dropdown="rating">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                            Rating
                            <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="dropdown-content" id="rating-dropdown">
                            <div class="compact-rating-options">
                                <?php for ($rating = 0; $rating <= 5; $rating++): ?>
                                    <label class="compact-rating-option">
                                        <input type="radio" name="min_rating" value="<?= $rating ?>" 
                                               <?= $minRating === $rating ? 'checked' : '' ?> class="compact-rating-input">
                                        <?php if ($rating === 0): ?>
                                            <span class="compact-rating-text">Any Rating</span>
                                        <?php else: ?>
                                            <div class="compact-rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="compact-rating-star <?= $i <= $rating ? 'filled' : '' ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="compact-rating-text"><?= $rating === 5 ? '5 Stars' : $rating . '+ Stars' ?></span>
                                        <?php endif; ?>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="apply-filters-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                        </svg>
                        Apply Filters
                    </button>
                </div>
            </form>
            
            <div class="filter-results">
                <div class="results-info">
                    <span class="results-count"><?= count($products) ?></span>
                    <span class="results-text">products found</span>
                </div>
                <?php if (count($products) > 0): ?>
                    <div class="sort-options">
                        <select class="sort-select" onchange="sortProducts(this.value)">
                            <option value="name">Sort by Name</option>
                            <option value="price-low">Price: Low to High</option>
                            <option value="price-high">Price: High to Low</option>
                            <option value="rating">Highest Rating</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <?php if (empty($products)): ?>
        <div class="steam-alert">No products found.</div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php 
                    $imageUrl = htmlspecialchars(getProductImageUrl($product['image_path'] ?? 'default.jpg'));
                    $fallbackUrl = '/images/products/default.jpg';
                    ?>
                    <img src="<?= $imageUrl ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="product-image"
                         onerror="this.src='<?= $fallbackUrl ?>'; this.onerror=null;">
                    
                    <div class="product-info">
                        <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                        <?php displayProductRating($product, true, 'small'); ?>
                        
                        <p class="product-description">
                            <?= htmlspecialchars(substr($product['description'], 0, 100)) ?><?= strlen($product['description']) > 100 ? '...' : '' ?>
                        </p>
                        
                        <div class="product-footer">
                            <span class="product-price"><?= formatPrice($product['price']) ?></span>
                            
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <span class="in-stock">In Stock (<?= $product['stock_quantity'] ?>)</span>
                            <?php else: ?>
                                <span class="out-of-stock">Out of Stock</span>
                            <?php endif; ?>
                            
                            <?php 
                            // Show allergen safety indicator for logged-in users
                            if (isLoggedIn() && !empty($userAllergens) && isset($product['allergens'])) {
                                $productAllergens = json_decode($product['allergens'], true) ?: [];
                                $hasUserAllergens = !empty(array_intersect($productAllergens, $userAllergens));
                                
                                if ($hasUserAllergens): ?>
                                    <span class="allergen-warning">⚠️ Contains Your Allergens</span>
                                <?php else: ?>
                                    <span class="allergen-safe">✅ Safe for You</span>
                                <?php endif;
                            }
                            ?>
                            
                            <div class="product-actions">
                                <button type="button" class="piston-button small view-details-btn" data-product-id="<?= $product['id'] ?>">Details</button>
                                
                                <?php if (isLoggedIn() && $product['stock_quantity'] > 0): ?>
                                    <?php 
                                    // Check if product contains user's allergens before showing Add to Cart
                                    $canAddToCart = true;
                                    if (!empty($userAllergens) && isset($product['allergens'])) {
                                        $productAllergens = json_decode($product['allergens'], true) ?: [];
                                        $hasUserAllergens = !empty(array_intersect($productAllergens, $userAllergens));
                                        $canAddToCart = !$hasUserAllergens;
                                    }
                                    ?>
                                    <?php if ($canAddToCart): ?>
                                        <button type="button" class="piston-button small add-to-cart-btn" 
                                                data-product-id="<?= $product['id'] ?>">
                                            Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="piston-button small disabled" 
                                                title="This product contains your allergens" disabled>
                                            ⚠️ Allergen Alert
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (isAdmin()): ?>
                                    <a href="/admin/products/edit.php?id=<?= $product['id'] ?>" 
                                       class="piston-button small">Edit</a>
                                <?php endif; ?>
                                
                                <?php if (isLoggedIn() && !isAdmin()): ?>
                                    <a href="/file-complaint.php?product=<?= $product['id'] ?>&type=product_issue" 
                                       class="piston-button small report-btn" 
                                       title="Report an issue with this product">
                                        Report
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Product Details Modals -->
        <?php foreach ($products as $product): ?>
            <div id="product-modal-<?= $product['id'] ?>" class="product-modal" style="display:none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><?= htmlspecialchars($product['name']) ?></h2>
                        <button class="close-modal-btn" data-product-id="<?= $product['id'] ?>">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-image">
                            <?php 
                            $imageUrl = htmlspecialchars(getProductImageUrl($product['image_path'] ?? 'default.jpg'));
                            $fallbackUrl = '/images/products/default.jpg';
                            ?>
                            <img src="<?= $imageUrl ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                 onerror="this.src='<?= $fallbackUrl ?>'; this.onerror=null;">
                        </div>
                        <div class="modal-info">
                            <div class="product-price"><?= formatPrice($product['price']) ?></div>
                            <?php displayProductRating($product, true, 'medium'); ?>
                            
                            <div class="product-description">
                                <h3>Description</h3>
                                <p><?= htmlspecialchars($product['description']) ?></p>
                            </div>
                            
                            <div class="stock-info">
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <span class="in-stock">In Stock (<?= $product['stock_quantity'] ?> available)</span>
                                <?php else: ?>
                                    <span class="out-of-stock">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php 
                            // Show allergen safety indicator for logged-in users
                            if (isLoggedIn() && !empty($userAllergens) && isset($product['allergens'])) {
                                $productAllergens = json_decode($product['allergens'], true) ?: [];
                                $hasUserAllergens = !empty(array_intersect($productAllergens, $userAllergens));
                                
                                if ($hasUserAllergens): ?>
                                    <div class="allergen-warning">⚠️ Contains Your Allergens</div>
                                <?php else: ?>
                                    <div class="allergen-safe">✅ Safe for You</div>
                                <?php endif;
                            }
                            ?>
                            
                            <?php if (isset($product['allergens']) && !empty($product['allergens'])): ?>
                                <div class="allergen-info">
                                    <h4>Allergens</h4>
                                    <p><?= htmlspecialchars(implode(', ', json_decode($product['allergens'], true) ?: [])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <?php if (isLoggedIn() && $product['stock_quantity'] > 0): ?>
                            <?php 
                            // Check if product contains user's allergens before showing Add to Cart
                            $canAddToCart = true;
                            if (!empty($userAllergens) && isset($product['allergens'])) {
                                $productAllergens = json_decode($product['allergens'], true) ?: [];
                                $hasUserAllergens = !empty(array_intersect($productAllergens, $userAllergens));
                                $canAddToCart = !$hasUserAllergens;
                            }
                            ?>
                            <?php if ($canAddToCart): ?>
                                <button type="button" class="piston-button add-to-cart-btn" 
                                        data-product-id="<?= $product['id'] ?>">
                                    Add to Cart
                                </button>
                            <?php else: ?>
                                <button type="button" class="piston-button disabled" 
                                        title="This product contains your allergens" disabled>
                                    ⚠️ Allergen Alert
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (isLoggedIn() && !isAdmin()): ?>
                            <a href="/file-complaint.php?product=<?= $product['id'] ?>&type=product_issue" 
                               class="piston-button report-btn" 
                               title="Report an issue with this product">
                                Report
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Quantity Modal -->
        <div id="quantity-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Select Quantity</h2>
                    <div class="item-counter">
                        <span class="counter-label">Items to add:</span>
                        <span class="counter-value" id="item-count">1</span>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="quantity-section">
                        <label for="modal-quantity-input">Quantity:</label>
                        <div class="quantity-input-group">
                            <button type="button" id="modal-decrease-btn" class="quantity-btn">-</button>
                            <input type="number" id="modal-quantity-input" min="1" value="1" max="99">
                            <button type="button" id="modal-increase-btn" class="quantity-btn">+</button>
                        </div>
                    </div>
                    <div class="total-preview">
                        <div class="total-row">
                            <span>Item Price:</span>
                            <span id="item-price">$0.00</span>
                        </div>
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span id="subtotal-price" class="subtotal">$0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button id="modal-add-btn" class="add-btn">
                        <span class="btn-text">Add to Cart</span>
                        <span class="btn-count">(1 item)</span>
                    </button>
                    <button id="modal-cancel-btn" class="cancel-btn">Cancel</button>
                </div>
            </div>
        </div>
        
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div class="pagination">
                <?php foreach (getPaginationLinks($totalItems, $itemsPerPage, $currentPage, '/products') as $link): ?>
                    <a href="<?= $link['url'] ?>" 
                       class="<?= $link['active'] ? 'active' : '' ?>">
                        <?= $link['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
/* Modern Products Page Layout */
.products-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.products-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-radius: 16px;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

.header-content {
    flex: 1;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
    line-height: 1.2;
}

.page-subtitle {
    color: var(--text-secondary);
    font-size: 1rem;
    margin: 0;
}

.header-search {
    flex: 0 0 auto;
    min-width: 300px;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.search-form {
    display: block;
}

.search-input-group {
    display: flex;
    align-items: center;
    background: var(--white);
    border: 2px solid var(--medium-gray);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.search-input-group:focus-within {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-input {
    flex: 1;
    padding: 0.875rem 1rem;
    border: none;
    outline: none;
    font-size: 0.95rem;
    background: transparent;
    color: var(--text-primary);
}

.search-input::placeholder {
    color: var(--text-secondary);
}

.search-button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.875rem;
    background: var(--primary-blue);
    border: none;
    color: white;
    cursor: pointer;
    transition: all 0.2s ease;
}

.search-button:hover {
    background: var(--dark-blue);
}

/* Cart Icon Styles */
.header-cart {
    flex: 0 0 auto;
}

.cart-link {
    display: block;
    text-decoration: none;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.cart-link:hover {
    background: rgba(37, 99, 235, 0.1);
    transform: translateY(-1px);
}

.cart-icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    color: var(--primary-blue);
    transition: all 0.2s ease;
}

.cart-link:hover .cart-icon {
    color: var(--dark-blue);
}

.cart-counter {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.2rem 0.4rem;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    line-height: 1;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    animation: cartPulse 2s infinite;
}

@keyframes cartPulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

.admin-actions {
    flex: 0 0 auto;
}

.admin-button {
    display: inline-flex;
    align-items: center;
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.admin-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

/* Responsive Header */
@media (max-width: 1024px) {
    .products-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1.5rem;
    }
    
    .header-search {
        min-width: auto;
    }
    
    .admin-actions {
        align-self: flex-start;
    }
}

@media (max-width: 768px) {
    .products-page {
        padding: 1rem;
    }
    
    .products-header {
        padding: 1.5rem;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .header-search {
        flex-direction: column;
        gap: 0.75rem;
        min-width: auto;
    }
    
    .search-form {
        order: 1;
    }
    
    .header-cart {
        order: 2;
        align-self: flex-end;
    }
    
    .cart-icon {
        width: 36px;
        height: 36px;
    }
    
    .search-input {
        font-size: 0.9rem;
    }
}

/* Compact Filter Dropdowns */
.filter-dropdowns {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
    position: relative;
}

.filter-dropdown.active {
    z-index: 100;
}

.filter-dropdown {
    position: relative;
    flex: 1;
    min-width: 200px;
    z-index: 1;
}

.dropdown-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--white);
    border: 1px solid var(--medium-gray);
    border-radius: 8px;
    font-weight: 500;
    color: var(--text-primary);
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

.dropdown-toggle:hover {
    border-color: var(--primary-blue);
    background: rgba(37, 99, 235, 0.05);
}

.dropdown-toggle.active {
    border-color: var(--primary-blue);
    background: rgba(37, 99, 235, 0.1);
}

.dropdown-arrow {
    margin-left: auto;
    transition: transform 0.2s ease;
}

.dropdown-toggle.active .dropdown-arrow {
    transform: rotate(180deg);
}

.dropdown-content {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1000;
    background: var(--white);
    border: 1px solid var(--medium-gray);
    border-radius: 8px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    padding: 1rem;
    margin-top: 0.5rem;
    display: none;
    min-width: 250px;
    max-height: none;
    overflow: visible;
}

.dropdown-content.show {
    z-index: 99999;
    display: block;
    animation: dropdownSlide 0.2s ease-out;
}

@keyframes dropdownSlide {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Compact Form Elements */
.compact-select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--medium-gray);
    border-radius: 6px;
    font-size: 0.85rem;
    margin-bottom: 0.75rem;
    background: var(--white);
}

.compact-checkboxes {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}

.compact-checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.375rem;
    border-radius: 4px;
    transition: background 0.2s ease;
    font-size: 0.8rem;
}

.compact-checkbox-label:hover {
    background: rgba(37, 99, 235, 0.05);
}

.compact-checkbox {
    width: 14px;
    height: 14px;
    accent-color: var(--primary-blue);
}

.compact-checkbox-text {
    font-weight: 500;
    color: var(--text-primary);
}

.compact-price-range {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.compact-price-input {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid var(--medium-gray);
    border-radius: 6px;
    font-size: 0.85rem;
    width: 80px;
}

.compact-price-separator {
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 0.9rem;
}

.compact-rating-options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.compact-rating-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: background 0.2s ease;
    font-size: 0.85rem;
}

.compact-rating-option:hover {
    background: rgba(37, 99, 235, 0.05);
}

.compact-rating-input {
    accent-color: var(--primary-blue);
}

.compact-rating-stars {
    display: flex;
    gap: 0.125rem;
}

.compact-rating-star {
    font-size: 0.8rem;
    color: #d1d5db;
}

.compact-rating-star.filled {
    color: #fbbf24;
}

.compact-rating-text {
    font-weight: 500;
    color: var(--text-primary);
}

/* Modern Filter Styles */
.product-filters {
    background: var(--white);
    border-radius: 16px;
    border: 1px solid var(--medium-gray);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    margin-bottom: 2rem;
    overflow: visible;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
}

.filters-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 1px solid var(--medium-gray);
}

.filters-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.filters-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.toggle-filters-btn {
    display: none;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: var(--primary-blue);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.toggle-filters-btn:hover {
    background: var(--dark-blue);
    transform: translateY(-1px);
}

.clear-filters-btn {
    color: #ef4444;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    padding: 0.5rem 0;
}

.clear-filters-btn:hover {
    text-decoration: underline;
}

.filters-content {
    padding: 2rem;
}

/* Filter Groups */
.filter-group {
    margin-bottom: 2.5rem;
}

.filter-group:last-child {
    margin-bottom: 0;
}

.filter-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--medium-gray);
}

.filter-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1;
}

.filter-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.filter-options {
    padding-left: 3.5rem;
}

/* Modern Select */
.modern-select {
    width: 100%;
    max-width: 400px;
    padding: 0.875rem 1rem;
    border: 1px solid var(--medium-gray);
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
    background: var(--white);
    color: var(--text-primary);
    cursor: pointer;
    transition: all 0.2s ease;
}

.modern-select:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Checkbox Grid */
.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0.75rem;
    border-radius: 8px;
}

.checkbox-item:hover {
    background: rgba(37, 99, 235, 0.05);
}

.checkbox-input {
    display: none;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid var(--medium-gray);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.checkbox-input:checked + .checkbox-custom {
    background: var(--primary-blue);
    border-color: var(--primary-blue);
}

.checkbox-input:checked + .checkbox-custom::after {
    content: '✓';
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.checkbox-label {
    font-size: 0.9rem;
    color: var(--text-primary);
    font-weight: 500;
    line-height: 1.4;
}

.filter-description {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin: 0.75rem 0 0 0;
    font-style: italic;
    line-height: 1.4;
}

/* Price Range */
.price-range-container {
    width: 100%;
    max-width: 500px;
}

.price-input-group {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.price-input {
    flex: 1;
    padding: 0.875rem 1rem;
    border: 1px solid var(--medium-gray);
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.price-input:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.price-separator {
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 1.1rem;
    padding: 0 0.5rem;
}

/* Rating Options */
.rating-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-width: 500px;
}

.rating-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    cursor: pointer;
    padding: 1rem;
    border: 1px solid var(--medium-gray);
    border-radius: 8px;
    transition: all 0.2s ease;
}

.rating-option:hover {
    background: rgba(37, 99, 235, 0.05);
    border-color: var(--primary-blue);
}

.rating-option:has(.rating-input:checked) {
    background: rgba(37, 99, 235, 0.1);
    border-color: var(--primary-blue);
}

.rating-input {
    display: none;
}

.rating-display {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.rating-stars {
    display: flex;
    gap: 0.25rem;
}

.rating-star {
    font-size: 1rem;
    color: #d1d5db;
}

.rating-star.filled {
    color: #fbbf24;
}

.rating-text {
    font-size: 0.9rem;
    color: var(--text-primary);
    font-weight: 500;
}

/* Filter Actions */
.filter-actions {
    margin-top: 2.5rem;
    padding-top: 2rem;
    border-top: 1px solid var(--medium-gray);
}

.apply-filters-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    max-width: 400px;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    justify-content: center;
    margin: 0 auto;
}

.apply-filters-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

/* Filter Results */
.filter-results {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--medium-gray);
}

.results-info {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
}

.results-count {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-blue);
}

.results-text {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.sort-options {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.sort-select {
    padding: 0.625rem 1rem;
    border: 1px solid var(--medium-gray);
    border-radius: 6px;
    font-family: inherit;
    font-size: 0.85rem;
    background: var(--white);
    color: var(--text-primary);
    cursor: pointer;
    min-width: 180px;
}

.sort-select:focus {
    outline: none;
    border-color: var(--primary-blue);
}

/* Allergen Safety Indicators */
.allergen-safe {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: rgba(34, 197, 94, 0.1);
    color: #166534;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid #22c55e;
}

.allergen-warning {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: rgba(239, 68, 68, 0.1);
    color: #991b1b;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid #ef4444;
}

.piston-button.small.disabled {
    background: #6b7280;
    color: white;
    cursor: not-allowed;
    opacity: 0.6;
}

.piston-button.small.disabled:hover {
    background: #6b7280;
    transform: none;
    box-shadow: none;
}

.piston-button.small.report-btn {
    background: #f59e0b;
    color: white;
    border-color: #f59e0b;
}

.piston-button.small.report-btn:hover {
    background: #d97706;
    border-color: #d97706;
    transform: translateY(-1px);
}

/* Responsive Design */
@media (max-width: 1400px) {
    .product-filters {
        margin-left: 1rem;
        margin-right: 1rem;
    }
    
    .filters-header {
        padding: 1.25rem 1.5rem;
    }
    
    .filters-content {
        padding: 1.5rem;
    }
    
    .filter-options {
        padding-left: 2.5rem;
    }
    
    .checkbox-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 1200px) {
    .product-filters {
        margin-left: 0.5rem;
        margin-right: 0.5rem;
    }
    
    .filters-header {
        padding: 1rem 1.25rem;
    }
    
    .filters-content {
        padding: 1.25rem;
    }
    
    .filter-group {
        margin-bottom: 2rem;
    }
    
    .filter-header {
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
    }
    
    .filter-options {
        padding-left: 2rem;
    }
    
    .modern-select {
        max-width: 350px;
    }
    
    .price-range-container {
        max-width: 450px;
    }
    
    .rating-options {
        max-width: 450px;
    }
    
    .apply-filters-btn {
        max-width: 350px;
    }
}

@media (max-width: 1024px) {
    .product-filters {
        margin: 0 1rem 2rem 1rem;
    }
    
    .filters-header {
        padding: 1rem;
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .filters-content {
        padding: 1rem;
    }
    
    .filter-group {
        margin-bottom: 1.75rem;
    }
    
    .filter-header {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
    }
    
    .filter-options {
        padding-left: 0;
        text-align: center;
    }
    
    .checkbox-grid {
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 0.75rem;
    }
    
    .price-input-group {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .price-separator {
        display: none;
    }
    
    .filter-actions {
        margin-top: 2rem;
        padding-top: 1.5rem;
    }
    
    .filter-results {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
    }
}

@media (max-width: 768px) {
    .product-filters {
        margin: 0 0.5rem 2rem 0.5rem;
    }
    
    .filters-header {
        padding: 1rem 0.75rem;
    }
    
    .filters-content {
        padding: 0.75rem;
    }
    
    .toggle-filters-btn {
        display: flex;
    }
    
    .filters-content {
        display: none;
    }
    
    .filters-content.show {
        display: block;
    }
    
    .filter-group {
        margin-bottom: 1.5rem;
    }
    
    .filter-header {
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
    }
    
    .checkbox-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .checkbox-item {
        padding: 0.5rem;
    }
    
    .modern-select {
        max-width: 100%;
        padding: 0.75rem;
    }
    
    .price-input {
        padding: 0.75rem;
    }
    
    .rating-option {
        padding: 0.75rem;
    }
    
    .filter-actions {
        margin-top: 1.5rem;
        padding-top: 1rem;
    }
    
    .apply-filters-btn {
        max-width: 100%;
        padding: 0.875rem 1rem;
    }
}

@media (max-width: 480px) {
    .product-filters {
        margin: 0 0.25rem 1.5rem 0.25rem;
    }
    
    .filters-header {
        padding: 0.75rem 0.5rem;
    }
    
    .filters-content {
        padding: 0.5rem;
    }
    
    .filter-group {
        margin-bottom: 1.25rem;
    }
    
    .filter-header {
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
    }
    
    .filter-icon {
        width: 32px;
        height: 32px;
    }
    
    .filter-section-title {
        font-size: 1rem;
    }
    
    .checkbox-item {
        padding: 0.5rem;
        gap: 0.5rem;
    }
    
    .checkbox-label {
        font-size: 0.85rem;
    }
    
    .filter-description {
        font-size: 0.8rem;
        margin-top: 0.5rem;
    }
    
    .modern-select {
        padding: 0.625rem 0.75rem;
        font-size: 0.9rem;
    }
    
    .price-input {
        padding: 0.625rem 0.75rem;
        font-size: 0.9rem;
    }
    
    .rating-option {
        padding: 0.625rem;
        gap: 0.75rem;
    }
    
    .rating-display {
        gap: 0.75rem;
    }
    
    .rating-text {
        font-size: 0.85rem;
    }
    
    .apply-filters-btn {
        padding: 0.75rem;
        font-size: 0.9rem;
    }
    
    .results-count {
        font-size: 1.25rem;
    }
    
    .results-text {
        font-size: 0.85rem;
    }
    
    .sort-select {
        min-width: 150px;
        padding: 0.5rem 0.75rem;
        font-size: 0.8rem;
    }
}

@media (min-width: 1600px) {
    .product-filters {
        max-width: 1600px;
    }
    
    .filters-header {
        padding: 2rem 3rem;
    }
    
    .filters-content {
        padding: 2.5rem 3rem;
    }
    
    .filter-group {
        margin-bottom: 3rem;
    }
    
    .filter-header {
        margin-bottom: 2rem;
        padding-bottom: 1.25rem;
    }
    
    .filter-options {
        padding-left: 4rem;
    }
    
    .checkbox-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
    }
    
    .modern-select {
        max-width: 500px;
        padding: 1rem 1.25rem;
        font-size: 1rem;
    }
    
    .price-range-container {
        max-width: 600px;
    }
    
    .price-input-group {
        gap: 1.5rem;
    }
    
    .price-input {
        padding: 1rem 1.25rem;
        font-size: 1rem;
    }
    
    .rating-options {
        max-width: 600px;
        gap: 1.25rem;
    }
    
    .rating-option {
        padding: 1.25rem;
        gap: 1.25rem;
    }
    
    .filter-actions {
        margin-top: 3rem;
        padding-top: 2.5rem;
    }
    
    .apply-filters-btn {
        max-width: 500px;
        padding: 1.25rem 2rem;
        font-size: 1.1rem;
    }
    
    .filter-results {
        margin-top: 2.5rem;
        padding-top: 2.5rem;
    }
}


/* Ultra-wide screens */
@media (min-width: 2000px) {
    .product-filters {
        max-width: 1800px;
    }
    
    .filters-header {
        padding: 2.5rem 4rem;
    }
    
    .filters-content {
        padding: 3rem 4rem;
    }
    
    .checkbox-grid {
        grid-template-columns: repeat(5, 1fr);
    }
    
    .modern-select {
        max-width: 600px;
    }
    
    .price-range-container {
        max-width: 700px;
    }
    
    .rating-options {
        max-width: 700px;
    }
    
    .apply-filters-btn {
        max-width: 600px;
    }
}

/* Product Details Modal */
.product-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.product-modal .modal-content {
    background: white;
    border-radius: 16px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.product-modal .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem 2rem 1rem 2rem;
    border-bottom: 1px solid var(--medium-gray);
}

.product-modal .modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
}

.product-modal .close-modal-btn {
    background: none;
    border: none;
    font-size: 2rem;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    line-height: 1;
}

.product-modal .close-modal-btn:hover {
    background: var(--light-gray);
    color: var(--text-primary);
}

.product-modal .modal-body {
    padding: 2rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.product-modal .modal-image {
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--light-gray);
    border-radius: 12px;
    aspect-ratio: 1;
    overflow: hidden;
}

.product-modal .modal-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-modal .modal-info {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.product-modal .product-price {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--primary-blue);
}

.product-modal .product-description h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.product-modal .product-description p {
    margin: 0;
    color: var(--text-secondary);
    line-height: 1.6;
}

.product-modal .stock-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.product-modal .allergen-info h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.product-modal .allergen-info p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.product-modal .modal-footer {
    padding: 1rem 2rem 2rem 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    justify-content: flex-end;
}

.product-modal .piston-button {
    padding: 0.875rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.product-modal .piston-button.add-to-cart {
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    color: white;
}

.product-modal .piston-button.add-to-cart:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.product-modal .piston-button.disabled {
    background: #6b7280;
    color: white;
    cursor: not-allowed;
    opacity: 0.6;
}

.product-modal .piston-button.report-btn {
    background: #f59e0b;
    color: white;
}

.product-modal .piston-button.report-btn:hover {
    background: #d97706;
    transform: translateY(-1px);
}

/* Modal Responsive Design */
@media (max-width: 768px) {
    .product-modal {
        padding: 1rem;
    }
    
    .product-modal .modal-content {
        max-height: 95vh;
    }
    
    .product-modal .modal-header {
        padding: 1.5rem 1.5rem 1rem 1.5rem;
    }
    
    .product-modal .modal-header h2 {
        font-size: 1.25rem;
    }
    
    .product-modal .modal-body {
        padding: 1.5rem;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .product-modal .modal-info {
        gap: 1rem;
    }
    
    .product-modal .product-price {
        font-size: 1.5rem;
    }
    
    .product-modal .modal-footer {
        padding: 1rem 1.5rem 1.5rem 1.5rem;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .product-modal .piston-button {
        width: 100%;
        padding: 1rem;
    }
}

@media (min-width: 1600px) {
    .product-modal .modal-content {
        max-width: 1000px;
    }
}

/* Enhanced Quantity Modal Styles */
#quantity-modal.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
}

#quantity-modal .modal-content {
    background: white;
    border-radius: 16px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

#quantity-modal .modal-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 1px solid var(--medium-gray);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

#quantity-modal .modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
}

#quantity-modal .item-counter {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--primary-blue);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

#quantity-modal .counter-label {
    opacity: 0.9;
}

#quantity-modal .counter-value {
    font-size: 1rem;
    font-weight: 700;
}

#quantity-modal .modal-body {
    padding: 1.5rem;
}

#quantity-modal .quantity-section {
    margin-bottom: 1.5rem;
}

#quantity-modal .quantity-section label {
    display: block;
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: var(--text-primary);
}

#quantity-modal .quantity-input-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--light-gray);
    border-radius: 12px;
    padding: 0.25rem;
    border: 2px solid transparent;
    transition: all 0.2s ease;
}

#quantity-modal .quantity-input-group:focus-within {
    border-color: var(--primary-blue);
    background: white;
}

#quantity-modal .quantity-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: white;
    border-radius: 8px;
    color: var(--primary-blue);
    font-size: 1.2rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

#quantity-modal .quantity-btn:hover {
    background: var(--primary-blue);
    color: white;
    transform: scale(1.05);
}

#quantity-modal .quantity-btn:active {
    transform: scale(0.95);
}

#quantity-modal #modal-quantity-input {
    flex: 1;
    text-align: center;
    border: none;
    background: transparent;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--text-primary);
    outline: none;
    min-width: 60px;
}

#quantity-modal .total-preview {
    background: rgba(37, 99, 235, 0.05);
    border-radius: 12px;
    padding: 1rem;
    border: 1px solid rgba(37, 99, 235, 0.1);
}

#quantity-modal .total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

#quantity-modal .total-row:last-child {
    margin-bottom: 0;
    padding-top: 0.5rem;
    border-top: 1px solid rgba(37, 99, 235, 0.2);
}

#quantity-modal .total-row span:first-child {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

#quantity-modal .total-row span:last-child {
    color: var(--text-primary);
    font-weight: 600;
}

#quantity-modal .subtotal {
    color: var(--primary-blue) !important;
    font-size: 1.1rem !important;
}

#quantity-modal .modal-buttons {
    padding: 1.5rem;
    background: var(--light-gray);
    display: flex;
    gap: 1rem;
}

#quantity-modal .add-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

#quantity-modal .add-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

#quantity-modal .add-btn:active {
    transform: translateY(0);
}

#quantity-modal .btn-text {
    font-size: 1rem;
}

#quantity-modal .btn-count {
    font-size: 0.9rem;
    opacity: 0.9;
}

#quantity-modal .cancel-btn {
    padding: 1rem 1.5rem;
    background: white;
    color: var(--text-secondary);
    border: 2px solid var(--medium-gray);
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

#quantity-modal .cancel-btn:hover {
    background: var(--light-gray);
    border-color: var(--text-secondary);
    color: var(--text-primary);
}

/* Responsive Design for Quantity Modal */
@media (max-width: 480px) {
    #quantity-modal .modal-content {
        width: 95%;
        margin: 1rem;
    }
    
    #quantity-modal .modal-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    #quantity-modal .modal-body {
        padding: 1rem;
    }
    
    #quantity-modal .modal-buttons {
        flex-direction: column;
        padding: 1rem;
    }
    
    #quantity-modal .add-btn {
        order: 2;
    }
    
    #quantity-modal .cancel-btn {
        order: 1;
    }
}

</style>

<script>
// Simple and reliable dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dropdown script loaded');
    
    // Log current URL parameters
    const currentUrl = new URL(window.location);
    console.log('Current URL parameters:');
    for (const [key, value] of currentUrl.searchParams) {
        console.log(`  ${key}: ${value}`);
    }
    
    // Get all dropdown toggles and contents
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    const dropdownContents = document.querySelectorAll('.dropdown-content');
    
    console.log('Found dropdown toggles:', dropdownToggles.length);
    console.log('Found dropdown contents:', dropdownContents.length);
    
    // Function to close all dropdowns
    function closeAllDropdowns() {
        dropdownToggles.forEach(toggle => toggle.classList.remove('active'));
        dropdownContents.forEach(content => content.classList.remove('show'));
        document.querySelectorAll('.filter-dropdown').forEach(dropdown => dropdown.classList.remove('active'));
    }
    
    // Function to open specific dropdown
    function openDropdown(dropdownId) {
        closeAllDropdowns();
        const toggle = document.querySelector(`[data-dropdown="${dropdownId}"]`);
        const content = document.getElementById(`${dropdownId}-dropdown`);
        const dropdown = toggle?.closest('.filter-dropdown');
        
        if (toggle && content) {
            toggle.classList.add('active');
            content.classList.add('show');
            if (dropdown) {
                dropdown.classList.add('active');
            }
            console.log('Opened dropdown:', dropdownId);
        }
    }
    
    // Add click handlers to dropdown toggles
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdownId = this.getAttribute('data-dropdown');
            const isActive = this.classList.contains('active');
            
            console.log('Toggle clicked:', dropdownId, 'Active:', isActive);
            
            if (isActive) {
                closeAllDropdowns();
            } else {
                openDropdown(dropdownId);
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.filter-dropdown')) {
            closeAllDropdowns();
        }
    });
    
    // Prevent dropdown content clicks from closing dropdown
    dropdownContents.forEach(content => {
        content.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Handle filter form submission (only when Apply Filters button is clicked)
    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        console.log('Filter form found:', filterForm);
        
        // Clean form data before submission
        function cleanFormData(formData) {
            // Remove empty price values
            if (formData.get('min_price') === '') {
                formData.delete('min_price');
            }
            if (formData.get('max_price') === '') {
                formData.delete('max_price');
            }
            return formData;
        }
        
        // Handle form submission via Apply Filters button
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            console.log('Apply Filters button clicked');
            
            // Clean form data
            const formData = new FormData(filterForm);
            const cleanData = cleanFormData(formData);
            
            console.log('Filter data being submitted:');
            for (const [key, value] of cleanData) {
                console.log(`  ${key}: ${value}`);
            }
            
            // Submit the form
            try {
                const params = new URLSearchParams(cleanData);
                window.location.href = filterForm.action + '?' + params.toString();
            } catch (error) {
                console.error('Form submission failed:', error);
                // Fallback to normal form submission
                this.submit();
            }
        });
        
        // Note: Removed auto-submission on individual filter changes
        // Users must now click "Apply Filters" to apply any filter changes
        
    } else {
        console.error('Filter form NOT found!');
    }
    
    // Sort products function
    window.sortProducts = function(sortBy) {
        const url = new URL(window.location);
        url.searchParams.set('sort', sortBy);
        window.location.href = url.toString();
    };
    
    console.log('Dropdown functionality initialized');
    
    // Initialize modal functionality for dynamically added modals
    initializeModalFunctionality();
    
});

// Currency formatting helper function
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
}

// Initialize modal functionality
function initializeModalFunctionality() {
    // Enhanced quantity modal logic
    let selectedProductId = null;
    let selectedProductPrice = 0;
    const quantityModal = document.getElementById('quantity-modal');
    const quantityInput = document.getElementById('modal-quantity-input');
    const modalAddBtn = document.getElementById('modal-add-btn');
    const modalCancelBtn = document.getElementById('modal-cancel-btn');
    const modalDecreaseBtn = document.getElementById('modal-decrease-btn');
    const modalIncreaseBtn = document.getElementById('modal-increase-btn');
    const itemCount = document.getElementById('item-count');
    const itemPrice = document.getElementById('item-price');
    const subtotalPrice = document.getElementById('subtotal-price');
    const btnCount = document.querySelector('.btn-count');

    function updateQuantityDisplay() {
        const quantity = parseInt(quantityInput.value) || 1;
        const subtotal = selectedProductPrice * quantity;
        
        // Update counter displays
        itemCount.textContent = quantity;
        btnCount.textContent = `(${quantity} item${quantity !== 1 ? 's' : ''})`;
        
        // Update price displays
        itemPrice.textContent = formatCurrency(selectedProductPrice);
        subtotalPrice.textContent = formatCurrency(subtotal);
        
        // Update button state
        modalAddBtn.disabled = false;
    }

    function showQuantityModal(productId) {
        selectedProductId = productId;
        
        // Get product price from the product card or modal
        const productCard = document.querySelector(`[data-product-id="${productId}"]`).closest('.product-card');
        const productModal = document.getElementById(`product-modal-${productId}`);
        
        if (productModal && productModal.style.display !== 'none') {
            // Get price from modal
            const priceElement = productModal.querySelector('.product-price');
            selectedProductPrice = parseFloat(priceElement.textContent.replace(/[^0-9.]/g, ''));
        } else {
            // Get price from product card
            const priceElement = productCard.querySelector('.product-price');
            selectedProductPrice = parseFloat(priceElement.textContent.replace(/[^0-9.]/g, ''));
        }
        
        // Reset and show modal
        quantityInput.value = 1;
        quantityInput.max = 99;
        updateQuantityDisplay();
        
        if (quantityModal) {
            quantityModal.style.display = 'flex';
            quantityModal.classList.add('show');
            // Focus the input for better UX
            setTimeout(() => quantityInput.focus(), 100);
        }
    }

    function hideQuantityModal() {
        selectedProductId = null;
        selectedProductPrice = 0;
        if (quantityModal) {
            quantityModal.classList.remove('show');
            setTimeout(() => {
                quantityModal.style.display = 'none';
            }, 300);
        }
    }

    // Quantity input event listeners
    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            let value = parseInt(this.value) || 1;
            value = Math.max(1, Math.min(99, value)); // Clamp between 1 and 99
            this.value = value;
            updateQuantityDisplay();
        });
    }

    // Increase/decrease buttons
    if (modalIncreaseBtn) {
        modalIncreaseBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value) || 1;
            if (currentValue < 99) {
                quantityInput.value = currentValue + 1;
                updateQuantityDisplay();
            }
        });
    }

    if (modalDecreaseBtn) {
        modalDecreaseBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value) || 1;
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
                updateQuantityDisplay();
            }
        });
    }

    // Modal action buttons
    if (modalAddBtn) {
        modalAddBtn.onclick = function() {
            const qty = parseInt(quantityInput.value, 10) || 1;
            if (selectedProductId && qty > 0) {
                // Add loading state to button
                this.innerHTML = '<span class="btn-text">Adding...</span>';
                this.disabled = true;
                
                // Call the existing addToCart function
                addToCart(selectedProductId, qty)
                    .then(response => {
                        if (response.success) {
                            showNotification(`${qty} item${qty !== 1 ? 's' : ''} added to cart!`, 'success');
                            updateCartCount(response.cart ? Object.keys(response.cart).length : null);
                            hideQuantityModal();
                        } else {
                            showNotification('Failed to add to cart: ' + (response.error || 'Unknown error'), 'error');
                            // Reset button state
                            this.innerHTML = '<span class="btn-text">Add to Cart</span><span class="btn-count">(1 item)</span>';
                            this.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error adding to cart:', error);
                        showNotification('Failed to add to cart. Please try again.', 'error');
                        // Reset button state
                        this.innerHTML = '<span class="btn-text">Add to Cart</span><span class="btn-count">(1 item)</span>';
                        this.disabled = false;
                    });
            }
        };
    }

    if (modalCancelBtn) {
        modalCancelBtn.onclick = function() {
            hideQuantityModal();
        };
    }

    // Close modal when clicking outside
    if (quantityModal) {
        quantityModal.addEventListener('click', function(e) {
            if (e.target === quantityModal) {
                hideQuantityModal();
            }
        });
    }

    // Keyboard support
    document.addEventListener('keydown', function(e) {
        if (quantityModal && quantityModal.style.display === 'flex') {
            if (e.key === 'Escape') {
                hideQuantityModal();
            } else if (e.key === 'Enter') {
                modalAddBtn.click();
            }
        }
    });

    // Make showQuantityModal globally available
    window.showQuantityModal = showQuantityModal;
    
    // Product Details Modal logic
    document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const modal = document.getElementById('product-modal-' + productId);
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }
        });
    });
    
    document.querySelectorAll('.close-modal-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const modal = document.getElementById('product-modal-' + productId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
            }
        });
    });
    
    // Close modal when clicking outside the modal content
    document.querySelectorAll('.product-modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
            }
        });
    });
}
</script>

<script src="/js/cart.js"></script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>