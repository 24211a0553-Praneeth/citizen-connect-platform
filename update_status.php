<?php
session_start();
$conn = new mysqli("localhost", "root", "", "citizen_connect");
require_once 'NotificationHelper.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check department login
if(!isset($_SESSION['department'])){
    header("Location: department_login.php");
    exit();
}

$dept = $_SESSION['department'];
$msg = "";
$msgType = "success";

// Update logic
if(isset($_POST['update'])){
    $id = $_POST['id'];
    $status = $_POST['status'];
    $proof_image = "";
    $hasProof = isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] == 0;

    // ⛔ Server-side guard: proof photo is REQUIRED when marking as Completed
    if($status === 'Completed' && !$hasProof) {
        $msg = "⚠️ Proof of resolution photo is required to mark this complaint as Completed. Please attach an image.";
        $msgType = "error";
    } else {
        if($hasProof){
            $proof_image = $_FILES['proof_image']['name'];
            $tmp = $_FILES['proof_image']['tmp_name'];
            $new_filename = "proof_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.\-_]/", "", basename($proof_image));
            move_uploaded_file($tmp, "uploads/" . $new_filename);

            $update = $conn->prepare("UPDATE complaints SET status=?, proof_image=? WHERE id=? AND department=?");
            $update->bind_param("ssis", $status, $new_filename, $id, $dept);
            $res = $update->execute();
        } else {
            $update = $conn->prepare("UPDATE complaints SET status=? WHERE id=? AND department=?");
            $update->bind_param("sis", $status, $id, $dept);
            $res = $update->execute();
        }

        if($res) {
            $c_res = $conn->query("SELECT title, contact_phone, contact_email, username FROM complaints WHERE id='$id'");
            $c_data = $c_res->fetch_assoc();

            // --- TRIGGER NOTIFICATION ---
            $subject = "Ticket Status Update | ID: #$id";
            $status_color = ($status === 'Completed') ? '#4caf50' : '#00f2fe';
            $notif_username = $c_data['username'] ?? 'Citizen';
            
            $emailBody = "<html><body style='font-family: Arial, sans-serif;'>"
                . "<h2>Hello $notif_username,</h2>"
                . "<p>The status of your complaint <strong>#" . str_pad($id, 4, '0', STR_PAD_LEFT) . "</strong> has been updated.</p>"
                . "<div style='padding: 15px; background: #f4f4f4; border-radius: 8px;'>"
                . "<p><strong>Title:</strong> " . htmlspecialchars($c_data['title']) . "</p>"
                . "<p><strong>New Status:</strong> <span style='color: $status_color; font-weight: bold;'>" . strtoupper($status) . "</span></p>"
                . ($status === 'Completed' ? "<p>✅ <strong>Resolution:</strong> The issue has been resolved. You can view the resolution proof photo in your dashboard.</p>" : "<p>Our team is currently addressing the reported issue.</p>")
                . "</div>"
                . "<p>Thank you for using Citizen Connect.</p>"
                . "</body></html>";

            $smsBody = "Hi $notif_username! Your complaint #" . str_pad($id, 4, '0', STR_PAD_LEFT) . " status updated to: $status. " . ($status === 'Completed' ? "Check resolution proof on Citizen Connect." : "Track progress on your dashboard.");

            $notif_res = NotificationHelper::broadcast($c_data['contact_email'], $c_data['contact_phone'], $subject, $emailBody, $smsBody);

            $notif = "<br>";
            if($notif_res['sms']) {
                $notif .= " 🔔 SMS Broadcast dispatched to " . htmlspecialchars($c_data['contact_phone']) . ".<br>";
            }
            if($notif_res['email']) {
                $notif .= " ✉ Email Receipt queued for " . htmlspecialchars($c_data['contact_email']) . ".";
            }
            $msg = "✅ Ticket updated securely! " . ($proof_image ? "📎 Proof Attached." : "") . $notif;
            $msgType = "success";
        } else {
            $msg = "❌ Failed to update status. Ensure your database has the 'proof_image' column.";
            $msgType = "error";
        }
    }
}

// Fetch current complaint details
$complaint_id = $_GET['id'] ?? ($_POST['id'] ?? '');
$complaint = null;

if($complaint_id){
    $res = $conn->query("SELECT * FROM complaints WHERE id='$complaint_id' AND department='$dept'");
    if($res && $res->num_rows > 0){
        $complaint = $res->fetch_assoc();
    } else {
        $msg = "❌ Complaint not found or unauthorized.";
        $msgType = "error";
    }
} else {
    header("Location: department_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Complaint - Citizen Connect</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
/* Cinematic Background - Synced with Index */
body {
    margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif;
    display: flex; justify-content: center; align-items: center; min-height: 100vh;
    background: url('https://images.unsplash.com/photo-1449824913935-59a10b8d2000?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
    background-attachment: fixed; color: white; position: relative; overflow-x: hidden;
}
body::before {
    content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(135deg, rgba(15, 32, 39, 0.92) 0%, rgba(32, 58, 67, 0.8) 50%, rgba(44, 83, 100, 0.85) 100%); z-index: -1;
}
.particles {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
    background-size: 60px 60px; z-index: -1; animation: drift 40s linear infinite; pointer-events: none;
}
@keyframes drift { from { transform: translateY(0); } to { transform: translateY(-100px); } }

.update-box {
    width: 480px; padding: 35px 40px; border-radius: 20px;
    background: rgba(20, 30, 40, 0.6); backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    animation: fadeUp 0.6s ease; position: relative; z-index: 2; margin: 40px 0;
}
@keyframes fadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.header { text-align: center; margin-bottom: 25px; }
.header i { font-size: 40px; color: #4caf50; margin-bottom: 10px; filter: drop-shadow(0 0 10px rgba(76, 175, 80, 0.4)); }
.header h2 { margin: 0; font-size: 26px; font-weight: 600; }
.header p { margin: 5px 0 0; opacity: 0.7; font-size: 14px; }

/* DETAILS */
.details-card {
    background: rgba(0, 0, 0, 0.3); padding: 20px; border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.05); margin-bottom: 25px;
}
.details-card .row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
.details-card .row:last-child { border-bottom: none; }
.label { font-size: 13px; color: rgba(255,255,255,0.6); font-weight: 500; text-transform: uppercase; letter-spacing: 1px; }
.value { font-size: 15px; font-weight: 500; color: white; text-align: right; max-width: 60%; }

/* ALERT */
.alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; font-weight: 500; }
.alert.success { background: rgba(76, 175, 80, 0.2); color: #4caf50; border: 1px solid rgba(76, 175, 80, 0.3); }
.alert.error { background: rgba(255, 77, 77, 0.1); color: #ff4d4d; border: 1px solid rgba(255, 77, 77, 0.3); }

/* FORM */
.input-group { margin-bottom: 20px; }
.input-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; color: rgba(255,255,255,0.8); }

select {
    width: 100%; padding: 14px 15px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.3); color: white; font-size: 15px; outline: none; transition: 0.3s;
    appearance: none; cursor: pointer;
}
select option { background: #0f2027; color: white; }
select:focus { border-color: #00f2fe; box-shadow: 0 0 15px rgba(0, 242, 254, 0.2); }

/* Upload Box for Proof */
.upload-box {
    display: none; /* controlled by JS */
    flex-direction: column; align-items: center; justify-content: center;
    border: 2px dashed rgba(76, 175, 80, 0.4); border-radius: 12px; padding: 25px;
    background: rgba(76, 175, 80, 0.05); cursor: pointer; transition: 0.3s; text-align: center;
    margin-bottom: 20px; margin-top: 10px;
}
.upload-box.show { display: flex; animation: slideDown 0.3s ease; }
@keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
.upload-box:hover { background: rgba(76, 175, 80, 0.15); border-color: #4caf50; }
.upload-box i { font-size: 30px; color: #4caf50; margin-bottom: 10px; }
.upload-box span { font-size: 14px; font-weight: 500; color: rgba(255,255,255,0.8); }
input[type="file"] { display: none; }
#preview { display: none; max-width: 100%; border-radius: 8px; margin-top: 15px; border: 1px solid rgba(76, 175, 80, 0.3); }

button {
    width: 100%; padding: 15px; border-radius: 12px; border: none;
    background: linear-gradient(45deg, #00f2fe, #4facfe); color: white; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s; box-shadow: 0 5px 15px rgba(0, 242, 254, 0.3);
}
button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0, 242, 254, 0.5); }

.back-link { display: block; text-align: center; margin-top: 20px; color: rgba(255,255,255,0.6); text-decoration: none; font-size: 14px; transition: 0.3s; }
.back-link:hover { color: white; }
</style>
</head>
<body>

<div class="particles"></div>

<div class="update-box">
    
    <div class="header">
        <i class="fas fa-edit"></i>
        <h2>Manage Complaint</h2>
        <p>Update ticket status for citizen tracking</p>
    </div>

    <?php if($msg != "") { echo "<div class='alert $msgType'>$msg</div>"; } ?>

    <?php if($complaint): ?>
        <div class="details-card">
            <div class="row">
                <span class="label">Ticket ID</span>
                <span class="value">#<?php echo str_pad($complaint['id'], 4, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="row">
                <span class="label">Priority</span>
                <span class="value" style="color: #ff9800; font-weight: 700;"><?php echo htmlspecialchars($complaint['priority'] ?? 'Normal'); ?></span>
            </div>
            <div class="row">
                <span class="label">Subject</span>
                <span class="value"><?php echo htmlspecialchars($complaint['title']); ?></span>
            </div>
            <div class="row">
                <span class="label">Current Status</span>
                <span class="value" style="color: #00f2fe;"><?php echo htmlspecialchars($complaint['status']); ?></span>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($complaint['id']); ?>">

            <div class="input-group">
                <label>Set New Status Target:</label>
                <div style="position: relative;">
                    <select name="status" id="statusSelect" required onchange="toggleProofUpload()">
                        <option value="Pending" <?php if($complaint['status']=='Pending') echo 'selected'; ?>>Pending</option>
                        <option value="In Progress" <?php if($complaint['status']=='In Progress') echo 'selected'; ?>>In Progress</option>
                        <option value="Completed" <?php if($complaint['status']=='Completed') echo 'selected'; ?>>Completed</option>
                    </select>
                    <i class="fas fa-chevron-down" style="position: absolute; right: 20px; top: 18px; color: rgba(255,255,255,0.5); pointer-events: none;"></i>
                </div>
            </div>

            <!-- Required proof notice -->
            <p id="proofNote" style="display:none; font-size:13px; color:#ff9800; margin-bottom:8px;">
                <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>
                <strong>Required:</strong> You must upload a photo as proof of resolution before submitting.
            </p>
            <label class="upload-box" id="proofUploadBox" for="proofFileInput">
                <input type="file" id="proofFileInput" name="proof_image" accept="image/*" onchange="previewProofImage(event)">
                <i class="fas fa-camera-retro"></i>
                <span id="proofFileName">📸 Click to Attach Proof Photo (Required)</span>
                <img id="preview" src="#" alt="Proof Preview">
            </label>

            <button type="submit" name="update"><i class="fas fa-check-circle" style="margin-right: 8px;"></i> Broadcast Update To Citizen</button>
        </form>
    <?php endif; ?>

    <a href="department_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Return to Department Desk</a>

</div>

<script>
function toggleProofUpload() {
    const select    = document.getElementById('statusSelect');
    const uploadBox = document.getElementById('proofUploadBox');
    const proofNote = document.getElementById('proofNote');
    const fileInput = document.getElementById('proofFileInput');

    if (select.value === 'Completed') {
        uploadBox.classList.add('show');
        proofNote.style.display = 'block';
        fileInput.required = true;   // HTML5 required
    } else {
        uploadBox.classList.remove('show');
        proofNote.style.display = 'none';
        fileInput.required = false;
    }
}

// Client-side guard before form submit
document.addEventListener('DOMContentLoaded', function() {
    toggleProofUpload(); // set initial state

    document.querySelector('form').addEventListener('submit', function(e) {
        const select    = document.getElementById('statusSelect');
        const fileInput = document.getElementById('proofFileInput');

        if (select.value === 'Completed' && fileInput.files.length === 0) {
            e.preventDefault();
            alert('⚠️ Please attach a proof photo before marking this complaint as Completed.');
            document.getElementById('proofUploadBox').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});

function previewProofImage(event) {
    const output = document.getElementById('preview');
    const span   = document.getElementById('proofFileName');
    const file   = event.target.files[0];

    if (file) {
        span.innerText = '✅ ' + file.name;
        const reader = new FileReader();
        reader.onload = function() {
            output.src = reader.result;
            output.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}
</script>

</body>
</html>