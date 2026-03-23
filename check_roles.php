<?php
// Quick script to check user roles in database
require_once __DIR__ . '/src/config/database.php';

header('Content-Type: text/plain');

echo "=== USER ROLES CHECK ===\n\n";

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Check all user roles
    $stmt = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY count DESC");
    echo "User roles in database:\n";
    while ($row = $stmt->fetch()) {
        echo "- {$row['role']}: {$row['count']} users\n";
    }
    
    echo "\nComplaint filtering analysis:\n";
    
    // Check buyer filtering logic
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
    $customerCount = $stmt->fetchColumn();
    echo "- Users with role 'customer': $customerCount (used for buyer filter)\n";
    
    // Check seller filtering logic
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'seller'");
    $sellerCount = $stmt->fetchColumn();
    echo "- Users with role 'seller': $sellerCount (used for seller filter)\n";
    
    // Check actual complaints
    $stmt = $conn->query("
        SELECT 
            c.id,
            c.complainant_id,
            c.respondent_id,
            u1.role as complainant_role,
            u2.role as respondent_role
        FROM complaints c
        JOIN users u1 ON c.complainant_id = u1.id
        JOIN users u2 ON c.respondent_id = u2.id
        LIMIT 5
    ");
    
    echo "\nSample complaints with roles:\n";
    while ($row = $stmt->fetch()) {
        echo "- Complaint #{$row['id']}: Complainant ({$row['complainant_role']}) vs Respondent ({$row['respondent_role']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECK COMPLETE ===\n";
?>
