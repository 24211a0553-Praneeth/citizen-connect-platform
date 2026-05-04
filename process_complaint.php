<?php
session_start();
$conn = new mysqli("localhost", "root", "", "citizen_connect");
require_once 'NotificationHelper.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ── Redirect if not logged in ──
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username      = $conn->real_escape_string($_SESSION['user']);
$title         = $conn->real_escape_string($_POST['title']        ?? '');
$description   = $conn->real_escape_string($_POST['description']  ?? '');
$location      = $conn->real_escape_string($_POST['location']     ?? '');
$priority      = $conn->real_escape_string($_POST['priority']     ?? 'Normal');
$contact_phone = $conn->real_escape_string($_POST['contact_phone'] ?? '');
$contact_email = $conn->real_escape_string($_POST['contact_email'] ?? '');
$geo_lat       = $conn->real_escape_string($_POST['geo_lat']      ?? '');
$geo_lng       = $conn->real_escape_string($_POST['geo_lng']      ?? '');

// ── Department Resolution ──
$department = $conn->real_escape_string($_POST['department'] ?? '');

if ($department === "Other" && !empty($_POST['new_department'])) {
    $department = $conn->real_escape_string($_POST['new_department']);
}
if (empty($department)) {
    $department = "General";
}

// Auto-create department login if it doesn't exist yet
$check = $conn->query("SELECT id FROM users WHERE username='$department' AND role='department'");
if ($check && $check->num_rows == 0) {
    $conn->query("INSERT INTO users (username, password, role, department)
                  VALUES ('$department', '1234', 'department', '$department')");
}

// ── Image Upload (from file picker OR camera capture) ──
$image = "";

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $_FILES['image']['size'] > 0) {
    $original  = basename($_FILES['image']['name']);
    $ext       = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    // Allow safe extensions only
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        echo "<script>alert('⚠️ Invalid file type. Please upload JPG, PNG, WEBP or GIF.'); history.back();</script>";
        exit();
    }

    // Unique filename to prevent overwrite collisions
    $image    = 'complaint_' . time() . '_' . uniqid() . '.' . $ext;
    $destPath = "uploads/" . $image;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
        echo "<script>alert('❌ Image upload failed. Please check uploads/ folder permissions.'); history.back();</script>";
        exit();
    }
}

// ── Check if geo_lat/geo_lng columns exist; store if present ──
// Gracefully add the geo columns if they don't exist yet
$col_check = $conn->query("SHOW COLUMNS FROM complaints LIKE 'geo_lat'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE complaints ADD COLUMN geo_lat VARCHAR(30) DEFAULT NULL");
    $conn->query("ALTER TABLE complaints ADD COLUMN geo_lng VARCHAR(30) DEFAULT NULL");
}

// ── Insert Complaint ──
$sql = "INSERT INTO complaints
            (username, title, description, department, location, priority, image, contact_phone, contact_email, geo_lat, geo_lng)
        VALUES
            ('$username','$title','$description','$department','$location','$priority','$image','$contact_phone','$contact_email','$geo_lat','$geo_lng')";

if ($conn->query($sql) === TRUE) {
    $id = $conn->insert_id;

    // --- TRIGGER NOTIFICATION ---
    $subject = "Complaint Logged Successfully | ID: #$id";
    $emailBody = "<html><body>"
        . "<h2>Hello $username,</h2>"
        . "<p>Your complaint has been successfully received by the <strong>$department</strong> department.</p>"
        . "<p><strong>Tracking ID:</strong> #$id</p>"
        . "<p><strong>Title:</strong> $title</p>"
        . "<p><strong>Status:</strong> Pending</p>"
        . "<p>You can track its progress on your dashboard.</p>"
        . "</body></html>";
    $smsBody = "Hi $username! Your complaint #$id has been received by the $department department. Status: Pending. Track it on your Citizen Connect dashboard.";

    NotificationHelper::broadcast($contact_email, $contact_phone, $subject, $emailBody, $smsBody, $conn, $username, $department);

    echo "<script>
        alert('✅ Complaint Logged Successfully!\\nYour Tracking ID is: #$id');
        window.location.href='user_dashboard.php';
    </script>";
} else {
    echo "<script>alert('❌ Error: " . addslashes($conn->error) . "'); history.back();</script>";
}

$conn->close();
?>