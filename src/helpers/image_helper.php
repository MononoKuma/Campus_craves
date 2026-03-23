<?php
/**
 * Centralized image helper functions
 */

/**
 * Get the correct URL for a product image
 * @param string $imagePath - Image path from database
 * @return string - Full URL to the image
 */
function getProductImageUrl($imagePath) {
    // Debug: Log what we're receiving (can be enabled for troubleshooting)
    // error_log("Image path from DB: " . ($imagePath ?? 'NULL'));
    
    if (!$imagePath) {
        return '/images/products/default.jpg';
    }
    
    // If path already contains 'products/', just prepend '/images/'
    if (strpos($imagePath, 'products/') === 0) {
        $finalPath = '/images/' . $imagePath;
    } else {
        // Otherwise, assume it's just a filename
        $finalPath = '/images/products/' . $imagePath;
    }
    
    // Debug: Log final path (can be enabled for troubleshooting)
    // error_log("Final image URL: " . $finalPath);
    
    return $finalPath;
}

/**
 * Check if an image file exists
 * @param string $url - Image URL
 * @return bool - Whether file exists
 */
function imageExists($url) {
    $filePath = $_SERVER['DOCUMENT_ROOT'] . $url;
    return file_exists($filePath);
}

/**
 * Get product image with fallback
 * @param string $imagePath - Image path from database
 * @param string $productName - Product name for alt text
 * @param string $class - CSS class for the img tag
 * @return string - HTML img tag with fallback
 */
function getProductImageTag($imagePath, $productName, $class = 'product-image') {
    $imageUrl = htmlspecialchars(getProductImageUrl($imagePath));
    $fallbackUrl = '/images/products/default.jpg';
    $altText = htmlspecialchars($productName);
    
    return "<img src=\"{$imageUrl}\" alt=\"{$altText}\" class=\"{$class}\" onerror=\"this.src='{$fallbackUrl}'; this.onerror=null;\">";
}
?>
