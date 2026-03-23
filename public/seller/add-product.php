<?php
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/controllers/SellerController.php';

// Strict seller check
if (!isSeller()) {
    redirect('/dashboard.php');
}

$sellerController = new SellerController();
$sellerId = $_SESSION['user_id'];
$errors = [];

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

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['image']['type'], $allowedTypes)) {
            $uploadDir = '/images/products/';
            $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
            // Use absolute path for the container filesystem
            $uploadPath = $_SERVER['DOCUMENT_ROOT'] . $uploadDir . $fileName;
            
            // Ensure the directory exists
            $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/images/products/';
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $data['image_path'] = $fileName; // Store only filename, not full path
            } else {
                $errors[] = 'Failed to upload image. Error: ' . $_FILES['image']['error'];
            }
        } else {
            $errors[] = 'Invalid image type. Only JPEG, PNG, and GIF are allowed.';
        }
    }

    if (empty($errors)) {
        if ($sellerController->createProduct($sellerId, $data)) {
            setFlashMessage('Product created successfully!', 'success');
            redirect('/seller/products.php');
        } else {
            $errors[] = 'Failed to create product. Please try again.';
        }
    }
}
?>

<?php require_once __DIR__ . '/../src/views/partials/header.php'; ?>

<section class="add-product-section">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-text">
                    <h1 class="page-title">Add New Product</h1>
                    <p class="page-subtitle">List your delicious homemade food for fellow students</p>
                </div>
                <div class="header-actions">
                    <a href="/seller/products.php" class="back-button">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Back to Products
                    </a>
                </div>
            </div>
        </div>

        <?php displayFlashMessage(); ?>
        <?php displayErrors($errors); ?>

        <!-- Product Form -->
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data" class="add-product-form" id="addProductForm">
                <!-- Basic Information -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">📦</div>
                        <div>
                            <h2 class="section-title">Basic Information</h2>
                            <p class="section-description">Essential details about your product</p>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="name" class="form-label">
                                Product Name *
                                <span class="required-hint">Required</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="text" id="name" name="name" required 
                                       class="form-input"
                                       placeholder="e.g., Fresh Chocolate Chip Cookies"
                                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                                <div class="input-icon">🍪</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="price" class="form-label">
                                Price ($) *
                                <span class="required-hint">Required</span>
                            </label>
                            <div class="input-wrapper">
                                <span class="input-prefix">$</span>
                                <input type="number" id="price" name="price" step="0.01" min="0.01" required 
                                       class="form-input"
                                       placeholder="0.00"
                                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock_quantity" class="form-label">
                                Stock Quantity *
                                <span class="required-hint">Required</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="number" id="stock_quantity" name="stock_quantity" min="0" required 
                                       class="form-input"
                                       placeholder="10"
                                       value="<?= htmlspecialchars($_POST['stock_quantity'] ?? '0') ?>">
                                <div class="input-icon">📊</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">📝</div>
                        <div>
                            <h2 class="section-title">Description</h2>
                            <p class="section-description">Make your product irresistible with a great description</p>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description" class="form-label">Product Description</label>
                        <div class="textarea-wrapper">
                            <textarea id="description" name="description" rows="5" 
                                      class="form-textarea"
                                      placeholder="Describe your product's ingredients, taste, and what makes it special..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            <div class="char-counter">
                                <span id="charCount">0</span> / 500 characters
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Image -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">📸</div>
                        <div>
                            <h2 class="section-title">Product Image</h2>
                            <p class="section-description">Upload a high-quality photo to showcase your product</p>
                        </div>
                    </div>
                    
                    <div class="image-upload-section">
                        <div class="image-upload-area" id="imageUploadArea">
                            <div class="upload-placeholder">
                                <div class="upload-icon">📤</div>
                                <h3>Drop your image here</h3>
                                <p>or click to browse</p>
                                <small>Allowed formats: JPEG, PNG, GIF (Max 5MB)</small>
                            </div>
                            <input type="file" id="image" name="image" accept="image/*" class="file-input">
                            <div class="image-preview" id="imagePreview"></div>
                        </div>
                    </div>
                </div>

                <!-- Allergens -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">⚠️</div>
                        <div>
                            <h2 class="section-title">Allergen Information</h2>
                            <p class="section-description">Help students make safe choices by listing allergens</p>
                        </div>
                    </div>
                    
                    <div class="allergens-grid">
                        <?php 
                        $allergenOptions = ['milk', 'eggs', 'fish', 'shellfish', 'tree nuts', 'peanuts', 'wheat', 'soy', 'sesame'];
                        $selectedAllergens = $_POST['allergens'] ?? [];
                        ?>
                        <?php foreach ($allergenOptions as $allergen): ?>
                            <label class="allergen-card">
                                <input type="checkbox" name="allergens[]" value="<?= htmlspecialchars(ucwords($allergen)) ?>"
                                       class="allergen-input"
                                       <?= in_array(ucwords($allergen), $selectedAllergens) ? 'checked' : '' ?>>
                                <div class="allergen-content">
                                    <div class="allergen-icon"><?= getAllergenIcon($allergen) ?></div>
                                    <span class="allergen-name"><?= htmlspecialchars(ucwords($allergen)) ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="submit-button">
                        <span class="button-icon">✨</span>
                        Create Product
                    </button>
                    <a href="/seller/products.php" class="cancel-button">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../src/views/partials/footer.php'; ?>

<script>
// Image upload preview
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imagePreview');
    const uploadArea = document.getElementById('imageUploadArea');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="preview-image">`;
            uploadArea.classList.add('has-image');
        }
        reader.readAsDataURL(file);
    }
});

// Character counter
const descriptionTextarea = document.getElementById('description');
const charCount = document.getElementById('charCount');

descriptionTextarea.addEventListener('input', function() {
    const count = this.value.length;
    charCount.textContent = count;
    
    if (count > 500) {
        this.value = this.value.substring(0, 500);
        charCount.textContent = 500;
    }
});

// Drag and drop
const uploadArea = document.getElementById('imageUploadArea');

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('drag-over');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('drag-over');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        document.getElementById('image').files = files;
        const event = new Event('change', { bubbles: true });
        document.getElementById('image').dispatchEvent(event);
    }
});

uploadArea.addEventListener('click', () => {
    document.getElementById('image').click();
});
</script>

<?php
function getAllergenIcon($allergen) {
    $icons = [
        'milk' => '🥛',
        'eggs' => '🥚',
        'fish' => '🐟',
        'shellfish' => '🦐',
        'tree nuts' => '🌰',
        'peanuts' => '🥜',
        'wheat' => '🌾',
        'soy' => '🫘',
        'sesame' => '🫘'
    ];
    return $icons[$allergen] ?? '⚠️';
}
?>
