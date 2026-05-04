<?php  
$conn = new mysqli("localhost", "root", "", "citizen_connect");  

// Check if notifications table exists, create if not
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    department VARCHAR(100) DEFAULT 'all',
    target_user VARCHAR(100) DEFAULT NULL,
    sent_by VARCHAR(50) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Check if target_user column exists
$col_check = $conn->query("SHOW COLUMNS FROM notifications LIKE 'target_user'");
if($col_check && $col_check->num_rows == 0) {
    $conn->query("ALTER TABLE notifications ADD COLUMN target_user VARCHAR(100) DEFAULT NULL AFTER department");
}

echo "Database updated successfully.";
?>
