<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/helpers/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/controllers/AdminController.php';

if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

$adminController = new AdminController();

// Handle filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$stock_status = $_GET['stock_status'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $adminController->deleteProduct($_POST['delete_id']);
        // Redirect to preserve filters
        header('Location: /admin/products.php?' . http_build_query([
            'search' => $search,
            'category' => $category,
            'price_min' => $price_min,
            'price_max' => $price_max,
            'stock_status' => $stock_status
        ]));
        exit();
    }
}

$products = $adminController->getFilteredProducts($search, $category, $price_min, $price_max, $stock_status);
$categories = $adminController->getAllCategories();

?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/header.php'; ?>

<div class="admin-container">
    <div class="admin-header-flex brass-panel">
        <h1 class="modern-title">🛠️ Product Management</h1>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="/admin/dashboard.php" class="modern-button header-small secondary">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="modern-panel">
        <div class="filter-header">
            <h2 class="filter-title">🔍 Product Filters</h2>
            <button type="button" class="clear-filters-btn" onclick="clearFilters()">Clear All</button>
        </div>
        
        <form method="GET" class="filter-form">
            <div class="filter-grid">
                <!-- Search Filter -->
                <div class="filter-group">
                    <label for="search">Search Products</label>
                    <div class="search-input-wrapper">
                        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Search by name or description..." class="filter-input">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="filter-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="filter-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Price Range Filters -->
                <div class="filter-group">
                    <label for="price_min">Min Price (Php)</label>
                    <input type="number" id="price_min" name="price_min" value="<?= htmlspecialchars($price_min) ?>" 
                           placeholder="0" min="0" step="0.01" class="filter-input">
                </div>

                <div class="filter-group">
                    <label for="price_max">Max Price (Php)</label>
                    <input type="number" id="price_max" name="price_max" value="<?= htmlspecialchars($price_max) ?>" 
                           placeholder="99999" min="0" step="0.01" class="filter-input">
                </div>

                <!-- Stock Status Filter -->
                <div class="filter-group">
                    <label for="stock_status">Stock Status</label>
                    <select id="stock_status" name="stock_status" class="filter-select">
                        <option value="">All Stock</option>
                        <option value="in_stock" <?= $stock_status === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                        <option value="out_of_stock" <?= $stock_status === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                        <option value="low_stock" <?= $stock_status === 'low_stock' ? 'selected' : '' ?>>Low Stock (≤5)</option>
                    </select>
                </div>

                <!-- Filter Actions -->
                <div class="filter-group filter-actions">
                    <button type="submit" class="apply-filters-btn">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Products Grid -->
    <div class="modern-panel">
        <div class="products-header">
            <h2 class="modern-title" style="font-size: 1.8rem; color: var(--text-primary);">Current Products</h2>
            <div class="results-count">
                <?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?> found
            </div>
        </div>
        
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                <div style="font-size: 3rem; margin-bottom: 16px;">📦</div>
                <h3 style="color: var(--text-primary); margin-bottom: 8px;">No products yet</h3>
                <p>Products are managed by sellers through their seller dashboard.</p>
            </div>
        <?php else: ?>
            <div class="admin-products-grid">
                <?php foreach ($products as $product): ?>
                <div class="admin-product-card">
                    <div class="admin-product-image">
                        <?php
                        $imgPath = $product['image_path'] ?? '';
                        if (strpos($imgPath, 'data:image/') === 0) {
                            $imgSrc = $imgPath;
                        } elseif ($imgPath) {
                            $imgSrc = '/images/products/' . $imgPath;
                        } else {
                            $imgSrc = '/images/products/default.jpg';
                        }
                        ?>
                        <img src="<?= $imgSrc ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <span class="in-stock">In Stock (<?= $product['stock_quantity'] ?>)</span>
                        <?php else: ?>
                            <span class="out-of-stock">Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="admin-product-info">
                        <h3 class="admin-product-name"><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="admin-product-description"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?><?= strlen($product['description']) > 100 ? '...' : '' ?></p>
                        <div class="admin-product-price">Php <?= number_format($product['price'], 2) ?></div>
                        
                        <div class="admin-product-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="delete_id" value="<?= $product['id'] ?>">
                                <button type="submit" class="modern-button small danger" 
                                        onclick="return confirm('Are you sure you want to delete this product?')">
                                    🗑️ Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Filter Styles */
.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.filter-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.clear-filters-btn {
    background: transparent;
    border: 2px solid var(--medium-gray);
    color: var(--text-secondary);
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.clear-filters-btn:hover {
    background: var(--light-gray);
    border-color: var(--primary-blue);
    color: var(--primary-blue);
}

.filter-form {
    margin: 0;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-group label {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
    margin: 0;
}

.filter-input,
.filter-select {
    padding: 12px 16px;
    border: 2px solid var(--medium-gray);
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    background: var(--white);
    color: var(--text-primary);
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-input-wrapper {
    position: relative;
}

.search-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
    pointer-events: none;
}

.filter-actions {
    justify-content: flex-end;
}

.apply-filters-btn {
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.apply-filters-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.results-count {
    color: var(--text-secondary);
    font-size: 0.95rem;
    font-weight: 500;
    background: var(--light-gray);
    padding: 8px 16px;
    border-radius: 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .filter-header {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .products-header {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .results-count {
        text-align: center;
    }
}

@media (max-width: 480px) {
    .filter-group {
        gap: 6px;
    }
    
    .filter-input,
    .filter-select {
        padding: 10px 12px;
        font-size: 0.9rem;
    }
    
    .apply-filters-btn {
        padding: 10px 20px;
        font-size: 0.9rem;
    }
}
</style>

<script>
function clearFilters() {
    window.location.href = '/admin/products.php';
}

// Auto-submit on change for better UX
document.addEventListener('DOMContentLoaded', function() {
    const filterInputs = document.querySelectorAll('.filter-input, .filter-select');
    
    filterInputs.forEach(input => {
        // Add debounced auto-submit
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if (this.type !== 'text' || this.value.length > 2 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 500);
        });
        
        input.addEventListener('change', function() {
            if (this.type !== 'text') {
                this.form.submit();
            }
        });
    });
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/footer.php'; ?>