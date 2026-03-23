<?php
class StorageHelper {
    private static function getUploadDir() {
        return $_SERVER['DOCUMENT_ROOT'] . '/images/products/';
    }
    
    public static function uploadImage($file, $subdir = 'products') {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        // Create directory if it doesn't exist
        $uploadDir = self::getUploadDir();
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $fileName = uniqid() . '_' . basename($file['name']);
        $uploadPath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $fileName;
        }
        
        return false;
    }
    
    public static function getImageUrl($filename) {
        if (!$filename) {
            return '/images/products/default.jpg';
        }
        return '/images/products/' . $filename;
    }
    
    public static function deleteImage($filename) {
        if (!$filename) return false;
        
        $imagePath = self::getUploadDir() . $filename;
        if (file_exists($imagePath)) {
            return unlink($imagePath);
        }
        return false;
    }
}
?>
