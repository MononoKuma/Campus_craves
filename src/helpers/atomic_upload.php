<?php
class AtomicUpload {
    private $tempDir;
    private $finalDir;
    
    public function __construct() {
        $this->tempDir = $_SERVER['DOCUMENT_ROOT'] . '/images/temp/';
        $this->finalDir = $_SERVER['DOCUMENT_ROOT'] . '/images/products/';
        
        // Ensure directories exist
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
        if (!file_exists($this->finalDir)) {
            mkdir($this->finalDir, 0755, true);
        }
    }
    
    public function uploadImage($file) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Invalid file upload'];
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }
        
        // Generate unique filename
        $fileName = uniqid() . '_' . basename($file['name']);
        $tempPath = $this->tempDir . $fileName;
        $finalPath = $this->finalDir . $fileName;
        
        // First upload to temp directory
        if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }
        
        // Validate the uploaded file
        if (!$this->validateImage($tempPath)) {
            unlink($tempPath);
            return ['success' => false, 'error' => 'Invalid image file'];
        }
        
        return [
            'success' => true,
            'filename' => $fileName,
            'temp_path' => $tempPath,
            'final_path' => $finalPath
        ];
    }
    
    public function commitUpload($filename) {
        $tempPath = $this->tempDir . $filename;
        $finalPath = $this->finalDir . $filename;
        
        if (!file_exists($tempPath)) {
            return false;
        }
        
        // Move from temp to final directory (atomic operation)
        return rename($tempPath, $finalPath);
    }
    
    public function rollbackUpload($filename) {
        $tempPath = $this->tempDir . $filename;
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }
    
    private function validateImage($path) {
        $imageInfo = getimagesize($path);
        return $imageInfo !== false;
    }
    
    public static function getImageUrl($filename) {
        if (!$filename) {
            return '/images/products/default.jpg';
        }
        return '/images/products/' . $filename;
    }
}
?>
