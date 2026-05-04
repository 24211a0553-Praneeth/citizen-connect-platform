<?php
session_start();

// Store role before destroying session
$role = isset($_SESSION['role']) ? $_SESSION['role'] : "";
$dept = isset($_SESSION['department']) ? $_SESSION['department'] : "";

// Destroy session
session_unset();
session_destroy();

// Redirect based on role
if($role == "admin"){
    header("Location: admin_login.php");
}
else if($role == "department" || $dept){
    header("Location: department_login.php");
}
else{
    header("Location: login.php");
}

exit();
?>