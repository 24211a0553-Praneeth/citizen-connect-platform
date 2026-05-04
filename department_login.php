<?php  
session_start();  
  
$conn = new mysqli("localhost","root","","citizen_connect");  
  
if ($conn->connect_error) {  
    die("Connection failed: " . $conn->connect_error);  
}  
  
if(isset($_POST['login'])){  
    $username = $_POST['username'];  
    $password = $_POST['password'];  
  
    $res = $conn->query("SELECT * FROM users   
                         WHERE username='$username'   
                         AND password='$password'   
                         AND role='department'");  
  
    if($res->num_rows > 0){  
        $row = $res->fetch_assoc();  
  
        $_SESSION['department'] = $row['username'];  
        $_SESSION['user'] = $row['username'];  
  
        header("Location: department_dashboard.php");  
        exit();  
    }  
    else{  
        $msg = "❌ Invalid Department Login";  
    }  
}  
?>  
  
<!DOCTYPE html>  
<html>  
<head>  
<title>Department Login - Citizen Connect</title>  
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
/* Premium Background Override */
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: 
        radial-gradient(circle at 15% 50%, rgba(2, 27, 43, 1), transparent 50%),
        radial-gradient(circle at 85% 30%, rgba(13, 62, 89, 1), transparent 50%),
        radial-gradient(circle at 50% 80%, rgba(6, 40, 61, 1), transparent 50%),
        linear-gradient(135deg, #0f2027, #203a43, #2c5364);
    background-color: #0d1b2a;
    background-attachment: fixed;
    color: white;
    position: relative;
    z-index: 1;
}

body::before {
    content: '';
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background-image: 
        linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    z-index: -2;
    pointer-events: none;
}

body::after {
    content: '';
    position: fixed;
    top: -50%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(0,242,254,0.05) 0%, transparent 60%);
    animation: rotateBg 30s linear infinite;
    z-index: -1;
    pointer-events: none;
}

@keyframes rotateBg {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.login-box {
    width: 380px;
    padding: 40px;
    border-radius: 20px;
    background: rgba(20, 40, 50, 0.6);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.05);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
    text-align: center;
    animation: fadeUp 0.8s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.title-icon {
    font-size: 40px;
    color: #fbc02d;
    margin-bottom: 15px;
    filter: drop-shadow(0 0 10px rgba(251, 192, 45, 0.4));
}

.login-box h2 {
    margin: 0;
    font-size: 26px;
    font-weight: 600;
}

.login-box p {
    margin: 5px 0 25px;
    opacity: 0.7;
    font-size: 14px;
}

.msg {
    color: #ff4d4d;
    background: rgba(255, 77, 77, 0.1);
    padding: 10px;
    border-radius: 8px;
    border: 1px solid rgba(255, 77, 77, 0.3);
    margin-bottom: 20px;
    font-size: 14px;
}

.input-box {
    position: relative;
    margin-bottom: 20px;
}

.input-box i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #4caf50;
    opacity: 0.8;
}

.input-box input {
    width: 100%;
    padding: 14px 15px 14px 45px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.2);
    color: white;
    font-size: 15px;
    outline: none;
    transition: 0.3s;
    box-sizing: border-box;
}

.input-box input:focus {
    border-color: #4caf50;
    box-shadow: 0 0 15px rgba(76, 175, 80, 0.3);
}

.input-box input::placeholder {
    color: rgba(255,255,255,0.4);
}

button {
    width: 100%;
    padding: 14px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(45deg, #11998e, #38ef7d);
    color: white;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
    box-shadow: 0 5px 15px rgba(56, 239, 125, 0.3);
    margin-top: 10px;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(56, 239, 125, 0.5);
}

.back-link {
    display: block;
    margin-top: 25px;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    font-size: 13px;
    transition: 0.3s;
}
.back-link:hover {
    color: white;
}
</style>
</head>  
  
<body>  

<div class="login-box">  

    <i class="fas fa-building title-icon"></i>
    <h2>Department Desk</h2>  
    <p>Sign in to manage active cases</p>  

    <?php if(isset($msg)) echo "<div class='msg'>$msg</div>"; ?>  

    <form method="POST">  
        <div class="input-box">
            <i class="fas fa-server"></i>
            <input type="text" name="username" placeholder="Department ID (e.g. Water)" required>
        </div>

        <div class="input-box">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" placeholder="Access Code" required>
        </div>

        <button name="login">Proceed</button>  
    </form>  
    
    <a href="index.html" class="back-link"><i class="fas fa-home"></i> Back to Main Menu</a>
</div>  

</body>  
</html>