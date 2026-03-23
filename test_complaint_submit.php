<?php
// Simple complaint submission test for Render.com
// Upload this file and access via: your-domain.com/test_complaint_submit.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== COMPLAINT SUBMISSION TEST ===\n\n";

// Load required files
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/src/controllers/ComplaintController.php';

// Start session (required for complaint system)
session_start();

// Set a test user session (adjust ID as needed)
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'customer';

echo "1. Testing database connection...\n";
try {
    $db = new Database();
    $conn = $db->connect();
    echo "✓ Database connected successfully\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "✗ Error code: " . $e->getCode() . "\n";
    exit(1);
}

echo "\n2. Testing complaint controller...\n";
try {
    $controller = new ComplaintController();
    echo "✓ ComplaintController loaded\n";
} catch (Exception $e) {
    echo "✗ ComplaintController failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n3. Testing complaint creation...\n";
try {
    $complaintData = [
        'complainant_id' => $_SESSION['user_id'],
        'respondent_id' => 2, // Adjust this to a valid user ID in your database
        'complaint_type' => 'product_issue',
        'subject' => 'Test Complaint - Debug Script',
        'description' => 'This is a test complaint created by the debug script to test the complaint submission functionality.',
        'order_id' => null,
        'product_id' => null
    ];
    
    echo "Submitting complaint with data:\n";
    echo "- Complainant ID: " . $complaintData['complainant_id'] . "\n";
    echo "- Respondent ID: " . $complaintData['respondent_id'] . "\n";
    echo "- Type: " . $complaintData['complaint_type'] . "\n";
    echo "- Subject: " . $complaintData['subject'] . "\n";
    echo "- Description: " . substr($complaintData['description'], 0, 50) . "...\n\n";
    
    $result = $controller->createComplaint($complaintData);
    
    if ($result['success']) {
        echo "✓ COMPLAINT SUBMISSION SUCCESSFUL!\n";
        echo "✓ Complaint ID: " . $result['complaint_id'] . "\n";
        echo "✓ Message: " . $result['message'] . "\n";
    } else {
        echo "✗ COMPLAINT SUBMISSION FAILED!\n";
        echo "✗ Error: " . $result['message'] . "\n";
        echo "✗ This is likely the error you're seeing in the main application.\n";
    }
    
} catch (Exception $e) {
    echo "✗ Exception during complaint creation:\n";
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "✗ Error code: " . $e->getCode() . "\n";
    echo "✗ File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "✗ Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
