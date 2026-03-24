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
$errors = [];

// Get product ID from URL
$productId = intval($_GET['id'] ?? 0);
if ($productId <= 0) {
    setFlashMessage('Invalid product ID.', 'error');
    redirect('/seller/products.php');
}

// Get product details
$product = $sellerController->getProductById($productId);
if (!$product || $product['seller_id'] != $sellerId) {
    setFlashMessage('Product not found or access denied.', 'error');
    redirect('/seller/products.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'price' => floatval($_POST['price'] ?? 0),
        'stock_quantity' => intval($_POST['stock_quantity'] ?? 0),
        'allergens' => isset($_POST['allergens']) ? $_POST['allergens'] : []
    ];

    // Validation
    if (empty($data['name'])) {
        $errors[] = 'Product name is required.';
    }
    if ($data['price'] <= 0) {
        $errors[] = 'Price must be greater than 0.';
    }
    if ($data['stock_quantity'] < 0) {
        $errors[] = 'Stock quantity cannot be negative.';
    }

    // Handle image upload - store as base64 data URI in database
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['image']['type'];
        if (in_array($fileType, $allowedTypes)) {
            $maxSize = 5 * 1024 * 1024; // 5MB limit
            if ($_FILES['image']['size'] <= $maxSize) {
                $imageData = file_get_contents($_FILES['image']['tmp_name']);
                if ($imageData !== false) {
                    $base64 = base64_encode($imageData);
                    $data['image_path'] = 'data:' . $fileType . ';base64,' . $base64;
                } else {
                    $errors[] = 'Failed to read uploaded image.';
                }
            } else {
                $errors[] = 'Image file is too large. Maximum size is 5MB.';
            }
        } else {
            $errors[] = 'Invalid image type. Only JPEG, PNG, and GIF are allowed.';
        }
    }

    if (empty($errors)) {
        if ($sellerController->updateProduct($productId, $data)) {
            setFlashMessage('Product updated successfully!', 'success');
            redirect('/seller/products.php');
        } else {
            $errors[] = 'Failed to update product. Please try again.';
        }
    }
}

// Parse existing allergens
$existingAllergens = [];
if (!empty($product['allergens'])) {
    $existingAllergens = json_decode($product['allergens'], true) ?: [];
}
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/header.php'; ?>

<div class="brass-panel edit-product">
    <h1 class="gears-title">⚙️ Edit Product</h1>
    <div class="copper-divider"></div>

    <?php displayFlashMessage(); ?>
    <?php displayErrors($errors); ?>

    <form method="POST" enctype="multipart/form-data" class="steam-form">
        <div class="form-row">
            <div class="form-group">
                <label for="name">Product Name *</label>
                <input type="text" id="name" name="name" required 
                       value="<?= htmlspecialchars($product['name']) ?>">
            </div>
            <div class="form-group">
                <label for="price">Price ($) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0.01" required 
                       value="<?= htmlspecialchars($product['price']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="stock_quantity">Stock Quantity *</label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" required 
                       value="<?= htmlspecialchars($product['stock_quantity']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="image">Product Image</label>
            <input type="file" id="image" name="image" accept="image/*">
            <small>Allowed formats: JPEG, PNG, GIF. Leave empty to keep current image.</small>
            <?php if (!empty($product['image_path'])): ?>
                <div class="current-image">
                    <img src="<?= getProductImageUrl($product['image_path']) ?>" alt="Current product image" style="max-width: 200px; margin-top: 10px;">
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Allergens (for filtering)</label>
            <div class="checkbox-group">
                <?php 
                $allergenOptions = [
                    'nuts' => 'Nuts',
                    'dairy' => 'Dairy',
                    'gluten' => 'Gluten',
                    'eggs' => 'Eggs',
                    'soy' => 'Soy',
                    'shellfish' => 'Shellfish',
                    'sesame' => 'Sesame'
                ];
                $selectedAllergens = $_POST['allergens'] ?? $existingAllergens;
                ?>
                <?php foreach ($allergenOptions as $value => $label): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="allergens[]" value="<?= htmlspecialchars($value) ?>"
                               <?= in_array($value, $selectedAllergens) ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        <?= htmlspecialchars($label) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <small>Select all allergens that apply to this product</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="steam-button primary">
                <span class="gear-icon">⚙️</span> Update Product
            </button>
            <a href="/seller/products.php" class="steam-button secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/src/views/partials/footer.php'; ?>
