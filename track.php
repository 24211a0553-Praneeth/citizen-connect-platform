<?php
$conn = new mysqli("localhost","root","","citizen_connect");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = null;
$searched = false;

if(isset($_POST['track'])){
    $searched = true;
    $id = $conn->real_escape_string($_POST['id']);
    
    // Removing # and leading zeros to match raw database ID if user copy-pastes
    $id = ltrim(str_replace('#', '', $id), '0');
    if($id == '') $id = '0'; // default case

    $sql = "SELECT * FROM complaints WHERE id='$id'";
    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Track Status | Citizen Connect</title>
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
.track-wrapper {
    width: 100%;
    max-width: 600px;
    padding: 20px;
    position: relative;
    z-index: 2;
}

.track-box {
    padding: 45px 50px;
    border-radius: 20px;
    background: rgba(20, 30, 40, 0.6);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    text-align: center;
    animation: fadeUp 0.8s cubic-bezier(0.1, 0.9, 0.2, 1);
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

.title-icon {
    font-size: 50px;
    color: #fbc02d;
    margin-bottom: 20px;
    filter: drop-shadow(0 0 15px rgba(251, 192, 45, 0.5));
}

.track-box h2 {
    margin: 0; font-size: 32px; font-weight: 700; letter-spacing: 0.5px;
}

.track-box p.subtitle {
    margin: 5px 0 35px; opacity: 0.7; font-size: 15px;
}

/* Search Bar */
.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
}

.search-input {
    flex-grow: 1;
    position: relative;
}

.search-input i {
    position: absolute; left: 18px; top: 50%; transform: translateY(-50%);
    color: #fbc02d; opacity: 0.8; font-size: 18px;
}

.search-input input {
    width: 100%; padding: 18px 15px 18px 50px;
    border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.4); color: white; font-size: 16px; outline: none; transition: 0.3s;
}

.search-input input:focus {
    border-color: #fbc02d; background: rgba(0, 0, 0, 0.6); box-shadow: 0 0 15px rgba(251, 192, 45, 0.2);
}

.search-input input::placeholder { color: rgba(255,255,255,0.4); }

.track-btn {
    padding: 0 30px; border-radius: 12px; border: none;
    background: linear-gradient(45deg, #f57f17, #fbc02d);
    color: white; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s;
    box-shadow: 0 5px 15px rgba(251, 192, 45, 0.3); white-space: nowrap;
}

.track-btn:hover {
    transform: translateY(-2px); box-shadow: 0 8px 25px rgba(251, 192, 45, 0.5);
}

/* Results Card */
.result-card {
    text-align: left;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 15px;
    padding: 30px;
    border: 1px solid rgba(255,255,255,0.05);
    margin-top: 20px;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.result-card .row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.result-card .row:last-child { border-bottom: none; padding-bottom: 0; }

.label {
    font-size: 13px; color: rgba(255,255,255,0.5); font-weight: 500; text-transform: uppercase; letter-spacing: 1px;
}
.value {
    font-size: 16px; font-weight: 500; color: white; text-align: right; max-width: 65%;
}

.status-badge {
    padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block;
}
.status-pending { background: rgba(255, 152, 0, 0.2); color: #ff9800; border: 1px solid rgba(255, 152, 0, 0.3); }
.status-inprogress { background: rgba(33, 150, 243, 0.2); color: #2196f3; border: 1px solid rgba(33, 150, 243, 0.3); }
.status-completed { background: rgba(76, 175, 80, 0.2); color: #4caf50; border: 1px solid rgba(76, 175, 80, 0.3); }

/* Messages */
.error-msg {
    color: #ff4d4d; background: rgba(255, 77, 77, 0.1); padding: 15px; border-radius: 12px;
    border: 1px solid rgba(255, 77, 77, 0.3); font-size: 15px; font-weight: 500; margin-top: 20px;
}

/* Image preview */
.img-container {
    margin-top: 15px;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.1);
    max-height: 200px;
}
.img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Navigation Link */
.back-link {
    display: inline-block; margin-top: 30px; color: rgba(255,255,255,0.6); text-decoration: none; font-size: 14px; transition: 0.3s;
}
.back-link:hover { color: white; transform: translateX(-3px); }
</style>
</head>

<body>
<div class="particles"></div>

<div class="track-wrapper">
    <div class="track-box">

        <i class="fas fa-search-location title-icon"></i>
        <h2>Live Case Tracker</h2>
        <p class="subtitle">Enter your Ticket ID to view real-time progression</p>

        <form method="POST" class="search-form">
            <div class="search-input">
                <i class="fas fa-hashtag"></i>
                <input type="text" name="id" placeholder="Ex: 0045" value="<?php echo isset($_POST['id']) ? htmlspecialchars($_POST['id']) : ''; ?>" required>
            </div>
            <button name="track" class="track-btn">Track Now</button>
        </form>

        <?php
        if ($searched) {
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                
                $statusClass = 'status-pending';
                if($row['status'] == 'In Progress') $statusClass = 'status-inprogress';
                if($row['status'] == 'Completed') $statusClass = 'status-completed';
        ?>
            
                <div class="result-card">
                    <div class="row">
                        <span class="label">Ticket ID</span>
                        <span class="value" style="color:#fbc02d;">#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Subject</span>
                        <span class="value"><?php echo htmlspecialchars($row['title']); ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Assignment</span>
                        <span class="value"><?php echo ucfirst(htmlspecialchars($row['department'])); ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Live Status</span>
                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                    </div>

                    <?php if(!empty($row['image'])){ ?>
                        <div class="row" style="flex-direction: column; align-items: flex-start;">
                            <span class="label" style="margin-bottom:10px;">Submitted Evidence</span>
                            <div class="img-container">
                                <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Evidence">
                            </div>
                        </div>
                    <?php } ?>

                    <div class="row">
                        <span class="label"><i class="fas fa-map-marker-alt" style="margin-right:5px; color:#fbc02d;"></i> Location</span>
                        <span class="value">
                            <?php echo htmlspecialchars($row['location']); ?>
                            <br>
                            <a href="https://www.google.com/maps?q=<?php echo urlencode($row['location']); ?>" target="_blank" style="color:#fbc02d; font-size:11px; text-decoration:none;">
                                <i class="fas fa-external-link-alt" style="font-size:10px;"></i> View on Maps
                            </a>
                        </span>
                    </div>
                    
                    <?php if(!empty($row['proof_image'])){ ?>
                        <div class="row" style="flex-direction: column; align-items: flex-start; margin-top: 15px; border-top: 1px solid rgba(76, 175, 80, 0.3); background: rgba(76, 175, 80, 0.05); border-radius: 12px; padding: 15px;">
                            <span class="label" style="color: #4caf50; margin-bottom:10px; font-weight:700;"><i class="fas fa-check-circle" style="margin-right:5px;"></i> Official Resolution Proof</span>
                            <div class="img-container" style="border-color: rgba(76, 175, 80, 0.3); box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
                                <img src="uploads/<?php echo htmlspecialchars($row['proof_image']); ?>" alt="Resolution Proof">
                            </div>
                        </div>
                    <?php } ?>
                </div>
            
        <?php
            } else {
                echo "<div class='error-msg'><i class='fas fa-exclamation-circle' style='margin-right:8px;'></i> We couldn't locate a complaint with that Ticket ID. Please check the number and try again.</div>";
            }
        }
        ?>

        <a href="index.html" class="back-link"><i class="fas fa-long-arrow-alt-left" style="margin-right:5px;"></i> Back to Platform</a>

    </div>
</div>

</body>
</html>