<?php
ob_start();
session_start();

$conn = new mysqli("localhost","root","","citizen_connect");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if(isset($_POST['login'])){
    $u = $_POST['username'];
    $p = $_POST['password'];

    $res = $conn->query("SELECT * FROM users WHERE username='$u' AND password='$p'");
    
    if($res && $res->num_rows > 0){
        $row = $res->fetch_assoc();

        $_SESSION['user'] = $row['username'];
        $_SESSION['role'] = $row['role'];

        if($row['role'] == "admin"){
            header("Location: view_complaints.php");
            exit();
        } else {
            header("Location: user_dashboard.php");
            exit();
        }
    } else {
        $msg = "❌ Invalid Login Credentials";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Citizen Login | Citizen Connect</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* Base Reset */
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

/* Cinematic Background - Synced with Index */
body {
    display: flex; justify-content: center; align-items: center; min-height: 100vh;
    background: url('https://images.unsplash.com/photo-1449824913935-59a10b8d2000?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
    background-attachment: fixed; color: white; position: relative; overflow-x: hidden;
}

/* Gradient Overlay */
body::before {
    content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(135deg, rgba(15, 32, 39, 0.92) 0%, rgba(32, 58, 67, 0.8) 50%, rgba(44, 83, 100, 0.85) 100%); z-index: -1;
}

/* Drifting Particles */
.particles {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
    background-size: 60px 60px; z-index: -1; animation: drift 40s linear infinite; pointer-events: none;
}
@keyframes drift { from { transform: translateY(0); } to { transform: translateY(-100px); } }

/* Glassmorphism Panel */
.login-box {
    width: 420px;
    padding: 45px 40px;
    border-radius: 20px;
    background: rgba(20, 30, 40, 0.6);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    text-align: center;
    animation: fadeUp 0.8s cubic-bezier(0.1, 0.9, 0.2, 1);
    position: relative;
    z-index: 2;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

.title-icon {
    font-size: 45px;
    color: #00f2fe;
    margin-bottom: 15px;
    filter: drop-shadow(0 0 15px rgba(0, 242, 254, 0.5));
}

.login-box h2 {
    margin: 0;
    font-size: 26px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.login-box p {
    margin: 5px 0 25px;
    opacity: 0.7;
    font-size: 14px;
}

/* Links Bottom */
.links-bottom {
    margin-top: 25px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: center;
}

.links-bottom a {
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: 0.3s;
}

.links-bottom a:hover { 
    color: #00f2fe; 
}

/* Message */
.msg {
    color: #ff4d4d;
    background: rgba(255, 77, 77, 0.1);
    padding: 12px;
    border-radius: 8px;
    border: 1px solid rgba(255, 77, 77, 0.3);
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 500;
}

/* Input Fields */
.input-box {
    position: relative;
    margin-bottom: 18px;
}

.input-box i {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: #00f2fe;
    opacity: 0.8;
}

.input-box input {
    width: 100%;
    padding: 15px 15px 15px 50px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.3);
    color: white;
    font-size: 15px;
    outline: none;
    transition: 0.3s;
    box-sizing: border-box;
}

.input-box input:focus {
    border-color: #00f2fe;
    background: rgba(0, 0, 0, 0.5);
    box-shadow: 0 0 15px rgba(0, 242, 254, 0.2);
}

.input-box input::placeholder {
    color: rgba(255,255,255,0.4);
}

/* Button */
button {
    width: 100%;
    padding: 15px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(45deg, #00f2fe, #4facfe);
    color: white;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
    box-shadow: 0 5px 15px rgba(0, 242, 254, 0.3);
    margin-top: 10px;
    letter-spacing: 0.5px;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 242, 254, 0.5);
}

/* Navigation Link */
.back-link {
    display: inline-block;
    margin-top: 25px;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    font-size: 14px;
    transition: 0.3s;
}
.back-link:hover {
    color: white;
    transform: translateX(-3px);
}
</style>

</head>

<body>
<div class="particles"></div>

<div class="login-box">

    <i class="fas fa-users title-icon"></i>
    <h2>Citizen Portal</h2>
    <p style="margin-bottom: 30px;">Sign in to report and track issues</p>

    <?php if(isset($msg)) echo "<div class='msg'>$msg</div>"; ?>

    <form method="POST">
        <div class="input-box">
            <i class="fas fa-envelope"></i>
            <input name="username" placeholder="Email / Username" required>
        </div>
        <div class="input-box">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" placeholder="Password" required>
        </div>
        <button name="login">Sign In <i class="fas fa-arrow-right" style="margin-left: 8px; font-size: 14px;"></i></button>
    </form>
    
    <div class="links-bottom">
        <a href="register.php">Don't have an account? Register Here</a>
        <a href="forgot.php">Forgot your Password?</a>
    </div>

    <a href="index.html" class="back-link" style="margin-top:30px;"><i class="fas fa-long-arrow-alt-left" style="margin-right:5px;"></i> Back to Platform</a>

</div>

</body>
</html>