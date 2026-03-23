<?php
// Lazy loading image handler with optimization
if (!isset($_GET['src'])) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$imagePath = __DIR__ . '/' . $_GET['src'];

// Security check - ensure path is within images directory
$realPath = realpath($imagePath);
$imagesDir = realpath(__DIR__ . '/images');
if ($realPath === false || strpos($realPath, $imagesDir) !== 0) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

if (!file_exists($imagePath)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Get image info
$imageInfo = getimagesize($imagePath);
if (!$imageInfo) {
    header('HTTP/1.0 400 Bad Request');
    exit;
}

$mimeType = $imageInfo['mime'];
$lastModified = filemtime($imagePath);
$etag = md5_file($imagePath);

// Check cache
header('Cache-Control: public, max-age=31536000');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
header('ETag: "' . $etag . '"');

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $etag . '"') {
    header('HTTP/1.1 304 Not Modified');
    exit;
}

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified) {
    header('HTTP/1.1 304 Not Modified');
    exit;
}

// Set content type
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($imagePath));

// Output image
readfile($imagePath);
?>
