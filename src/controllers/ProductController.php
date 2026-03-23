<?php
require_once __DIR__ . '/../models/Product.php';

class ProductController {
    private $productModel;

    public function __construct() {
        $this->productModel = new Product();
    }

    public function index() {
        return $this->productModel->getAllProducts();
    }

    public function show($id) {
        $product = $this->productModel->getProductById($id);
        if (!$product) {
            return ['success' => false, 'error' => 'Product not found'];
        }
        return ['success' => true, 'product' => $product];
    }

    public function getProductById($id) {
        return $this->productModel->getProductById($id);
    }

    public function create($data) {
        $errors = $this->validateProductData($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $result = $this->productModel->createProduct($data);
        return ['success' => $result, 'product_id' => $this->productModel->getDb()->lastInsertId()];
    }

    public function update($id, $data) {
        $errors = $this->validateProductData($data, false);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $result = $this->productModel->updateProduct($id, $data);
        return ['success' => $result];
    }

    public function delete($id) {
        $result = $this->productModel->deleteProduct($id);
        return ['success' => $result];
    }

    public function search($query) {
        $products = $this->productModel->searchProducts($query);
        return ['success' => true, 'products' => $products];
    }

    public function getProductsBySeller($sellerId) {
        return $this->productModel->getProductsBySeller($sellerId);
    }

    private function validateProductData($data, $requireAll = true) {
        $errors = [];
        
        if ($requireAll || isset($data['name'])) {
            if (empty($data['name'])) {
                $errors[] = 'Product name is required';
            }
        }
        
        if ($requireAll || isset($data['price'])) {
            if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
                $errors[] = 'Valid price is required';
            }
        }
        
        if ($requireAll || isset($data['stock_quantity'])) {
            if (empty($data['stock_quantity']) || !is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0) {
                $errors[] = 'Valid stock quantity is required';
            }
        }
        
        return $errors;
    }
}
?>