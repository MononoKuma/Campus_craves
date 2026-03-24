<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/helpers/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/controllers/SellerController.php';

// Determine correct image path for each product
function getProductImageUrl($imagePath) {
    if (!$imagePath) {
        return '/images/products/default.jpg';
    }
    // If it's a base64 data URI, return as-is
    if (strpos($imagePath, 'data:image/') === 0) {
        return $imagePath;
    }
    // If path already contains 'products/', just prepend '/images/'
    if (strpos($imagePath, 'products/') === 0) {
        return '/images/' . $imagePath;
    }
    // Otherwise, assume it's just a filename
    return '/images/products/' . $imagePath;
}

// Strict seller check
if (!isSeller()) {
    redirect('/dashboard.php');
}

$sellerController = new SellerController();
$sellerId = $_SESSION['user_id'];
$products = $sellerController->getSellerProducts($sellerId);

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $productId = intval($_GET['delete']);
    if ($sellerController->deleteProduct($productId, $sellerId)) {
        setFlashMessage('Product deleted successfully!', 'success');
        redirect('/seller/products.php');
    } else {
        setFlashMessage('Failed to delete product or product not found.', 'error');
    }
}
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/header.php'; ?>

<div class="modern-panel seller-products">
    <h1 class="page-title">My Products</h1>
    <div class="title-divider"></div>

    <?php displayFlashMessage(); ?>

    <div class="page-actions">
        <a href="/seller/add-product.php" class="modern-button primary">
            <span class="button-icon">+</span> Add New Product
        </a>
    </div>

    <div class="products-grid">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>No Products Yet</h3>
                <p>Start by adding your first product to the marketplace.</p>
                <a href="/seller/add-product.php" class="modern-button primary">Add Product</a>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-image-container">
                        <?php if ($product['image_path']): ?>
                            <img src="<?= getProductImageUrl($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                        <?php else: ?>
                            <div class="placeholder-image">🛠️</div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="product-description"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...</p>
                        <div class="product-meta">
                            <span class="price">Php <?= number_format($product['price'], 2) ?></span>
                            <span class="stock">Stock: <?= $product['stock_quantity'] ?></span>
                        </div>
                        <?php if (!empty($product['allergens'])): ?>
                            <div class="allergens">
                                <strong>Allergens:</strong>
                                <?php 
                                $allergens = json_decode($product['allergens'], true);
                                if ($allergens && is_array($allergens)):
                                    echo implode(', ', array_map('htmlspecialchars', $allergens));
                                else:
                                    echo 'None';
                                endif;
                                ?>
                            </div>
                        <?php endif; ?>
                        <div class="product-actions">
                            <a href="/seller/edit-product.php?id=<?= $product['id'] ?>" class="modern-button secondary small">Edit</a>
                            <a href="/seller/products.php?delete=<?= $product['id'] ?>" class="modern-button danger small" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/footer.php'; ?>
