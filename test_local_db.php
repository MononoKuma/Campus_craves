<?php
require_once __DIR__ . '/src/config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    echo "Database connection: SUCCESS\n";
    
    // Check if complaints table exists
    $stmt = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'complaints'");
    $result = $stmt->fetch();
    echo "Complaints table exists: " . ($result ? 'YES' : 'NO') . "\n";
    
    // Check if complaint_responses table exists
    $stmt = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'complaint_responses'");
    $result = $stmt->fetch();
    echo "Complaint_responses table exists: " . ($result ? 'YES' : 'NO') . "\n";
    
    // Check users table
    $stmt = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'users'");
    $result = $stmt->fetch();
    echo "Users table exists: " . ($result ? 'YES' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
