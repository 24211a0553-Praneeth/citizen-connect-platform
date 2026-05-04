<?php
session_start();
$conn = new mysqli("localhost", "root", "", "citizen_connect");

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data for pre-filling
$username = $_SESSION['user'];
$user_res = $conn->query("SELECT email, phone FROM users WHERE username='$username'");
$userData = $user_res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Submit Complaint | Citizen Connect</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Securely report civic issues to your municipal department with photo evidence and live location tagging.">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Leaflet.js for live map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

body {
    display: flex; justify-content: center; align-items: flex-start; min-height: 100vh;
    background: url('https://images.unsplash.com/photo-1449824913935-59a10b8d2000?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
    background-attachment: fixed; color: white; position: relative; overflow-x: hidden;
    padding: 40px 20px;
}
body::before {
    content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(135deg, rgba(15,32,39,0.93) 0%, rgba(32,58,67,0.82) 50%, rgba(44,83,100,0.87) 100%); z-index: -1;
}
.particles {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background-image: radial-gradient(circle, rgba(255,255,255,0.08) 1px, transparent 1px);
    background-size: 60px 60px; z-index: -1; animation: drift 40s linear infinite; pointer-events: none;
}
@keyframes drift { from { transform: translateY(0); } to { transform: translateY(-100px); } }

/* Top Bar */
.top-bar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
    display: flex; justify-content: space-between; align-items: center;
    padding: 14px 40px; background: rgba(15,32,39,0.85); backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.logo { font-weight: 700; font-size: 20px; color: #00f2fe; display: flex; align-items: center; gap: 8px; text-decoration: none; }
.nav-links { display: flex; gap: 12px; }
.nav-btn {
    background: rgba(255,255,255,0.08); padding: 8px 18px; border-radius: 20px;
    color: white; text-decoration: none; font-weight: 500; transition: 0.3s;
    border: 1px solid rgba(255,255,255,0.15); font-size: 13px; display: inline-flex; align-items: center; gap: 6px;
}
.nav-btn:hover { background: rgba(255,65,108,0.2); color: #ff416c; border-color: #ff416c; }

/* Form Container */
.form-wrapper { width: 100%; max-width: 720px; margin-top: 70px; position: relative; z-index: 2; }

.form-box {
    padding: 45px 50px; border-radius: 24px; background: rgba(15,25,35,0.75); backdrop-filter: blur(24px);
    border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 30px 60px rgba(0,0,0,0.6);
    animation: fadeUp 0.8s cubic-bezier(0.1,0.9,0.2,1);
}
@keyframes fadeUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }

.header-title { display: flex; align-items: center; gap: 15px; margin-bottom: 5px; }
.header-title i { font-size: 38px; color: #00f2fe; filter: drop-shadow(0 0 15px rgba(0,242,254,0.5)); }
.header-title h1 { font-size: 28px; font-weight: 700; }
.subtitle { opacity: 0.65; font-size: 14px; margin-bottom: 32px; margin-left: 53px; }

/* Labels & Inputs */
.input-group { margin-bottom: 20px; }
.input-group label { display: block; margin-bottom: 8px; font-size: 13px; color: rgba(255,255,255,0.8); font-weight: 600; letter-spacing: 0.3px; }

input[type="text"], input[type="tel"], input[type="email"], textarea, select {
    width: 100%; padding: 14px 18px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.35); color: white; font-size: 14px; outline: none; transition: 0.3s; font-family: 'Poppins', sans-serif;
}
textarea { resize: vertical; min-height: 100px; }
select option { background: #0f2027; }
input:focus, textarea:focus, select:focus {
    border-color: #00f2fe; background: rgba(0,0,0,0.5); box-shadow: 0 0 18px rgba(0,242,254,0.2);
}
input::placeholder, textarea::placeholder { color: rgba(255,255,255,0.35); }

.row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

/* ═══════════════════════════════════════
   PHOTO SECTION
═══════════════════════════════════════ */
.photo-section {
    margin-bottom: 24px;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px; overflow: hidden;
    background: rgba(0,0,0,0.25);
}
.photo-tabs {
    display: grid; grid-template-columns: 1fr 1fr;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.photo-tab {
    padding: 14px; text-align: center; cursor: pointer;
    font-size: 13px; font-weight: 600; transition: 0.3s;
    color: rgba(255,255,255,0.5); display: flex; align-items: center; justify-content: center; gap: 8px;
    border: none; background: transparent; font-family: 'Poppins', sans-serif;
}
.photo-tab.active {
    color: #00f2fe;
    background: rgba(0,242,254,0.07);
    border-bottom: 2px solid #00f2fe;
}
.photo-tab:hover:not(.active) { background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.75); }

.photo-panel { display: none; padding: 22px; }
.photo-panel.active { display: block; }

/* Upload Area */
.upload-area {
    border: 2px dashed rgba(255,255,255,0.2); border-radius: 12px;
    padding: 30px 20px; text-align: center; cursor: pointer; transition: 0.3s;
    background: rgba(0,0,0,0.15);
}
.upload-area:hover { border-color: #00f2fe; background: rgba(0,242,254,0.04); }
.upload-area i { font-size: 36px; color: #00f2fe; margin-bottom: 10px; display: block; }
.upload-area .up-title { font-size: 15px; font-weight: 600; margin-bottom: 4px; }
.upload-area .up-sub { font-size: 12px; color: rgba(255,255,255,0.45); }
input[type="file"] { display: none; }

/* Camera Panel */
.camera-container { position: relative; border-radius: 12px; overflow: hidden; background: #000; }
#cameraStream {
    width: 100%; max-height: 320px; object-fit: cover;
    display: block; border-radius: 12px;
}
.camera-controls {
    display: flex; gap: 12px; margin-top: 14px; justify-content: center;
}
.cam-btn {
    padding: 12px 24px; border-radius: 25px; border: none; cursor: pointer;
    font-size: 13px; font-weight: 600; transition: 0.3s; font-family: 'Poppins', sans-serif;
    display: inline-flex; align-items: center; gap: 8px;
}
.cam-btn.start  { background: linear-gradient(45deg, #00f2fe, #4facfe); color: #0b131a; }
.cam-btn.capture { background: linear-gradient(45deg, #ff6b6b, #ee5a24); color: white; }
.cam-btn.retake  { background: rgba(255,255,255,0.12); color: white; border: 1px solid rgba(255,255,255,0.2); }
.cam-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.3); }
.cam-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }

/* Shared Preview */
.img-preview-wrap {
    display: none; position: relative; border-radius: 12px; overflow: hidden;
    border: 1px solid rgba(0,242,254,0.3); margin-top: 14px;
}
.img-preview-wrap img { width: 100%; max-height: 280px; object-fit: cover; display: block; }
.preview-badge {
    position: absolute; top: 10px; left: 10px;
    background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);
    border: 1px solid rgba(0,242,254,0.4); border-radius: 20px;
    padding: 5px 14px; font-size: 11px; font-weight: 600; color: #00f2fe; display: flex; align-items: center; gap: 6px;
}
.preview-clear {
    position: absolute; top: 10px; right: 10px; background: rgba(255,65,108,0.8);
    border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer;
    color: white; font-size: 12px; display: flex; align-items: center; justify-content: center; transition: 0.2s;
}
.preview-clear:hover { background: #ff416c; transform: scale(1.1); }

/* ═══════════════════════════════════════
   LIVE LOCATION MAP
═══════════════════════════════════════ */
.location-map-block {
    display: none; margin-bottom: 20px; border-radius: 16px; overflow: hidden;
    border: 1px solid rgba(0,242,254,0.25);
    box-shadow: 0 8px 30px rgba(0,0,0,0.4);
    animation: fadeUp 0.5s ease;
}
.map-header {
    padding: 12px 18px; background: rgba(0,242,254,0.08);
    border-bottom: 1px solid rgba(0,242,254,0.15);
    display: flex; align-items: center; gap: 10px;
    font-size: 13px; font-weight: 600; color: #00f2fe;
}
.map-pulse {
    width: 10px; height: 10px; border-radius: 50%; background: #00f2fe;
    animation: pulse 1.5s infinite; flex-shrink: 0;
}
@keyframes pulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(0,242,254,0.5); }
    50%      { box-shadow: 0 0 0 8px rgba(0,242,254,0); }
}
#liveMap { height: 280px; width: 100%; }
.map-address-bar {
    padding: 12px 18px; background: rgba(0,0,0,0.4);
    font-size: 12px; color: rgba(255,255,255,0.7); display: flex; align-items: center; gap: 8px;
}
.map-coords {
    padding: 8px 18px; background: rgba(0,0,0,0.25);
    font-size: 11px; color: rgba(255,255,255,0.4); display: flex; gap: 20px;
}
.coord-chip { display: flex; align-items: center; gap: 5px; }

/* Location input row */
.location-row { display: flex; gap: 10px; align-items: flex-end; }
.location-row .input-group { flex: 1; margin-bottom: 0; }
.loc-detect-btn {
    padding: 14px 16px; border-radius: 12px; border: 1px solid rgba(0,242,254,0.35);
    background: rgba(0,242,254,0.08); color: #00f2fe; cursor: pointer; transition: 0.3s;
    font-size: 18px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
    white-space: nowrap;
}
.loc-detect-btn:hover { background: rgba(0,242,254,0.18); transform: scale(1.05); }
.loc-detect-btn.spinning i { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Submit */
.submit-btn {
    width: 100%; padding: 16px; border-radius: 12px; border: none;
    background: linear-gradient(45deg, #00f2fe, #4facfe); color: white; font-size: 16px; font-weight: 700;
    cursor: pointer; transition: 0.3s; box-shadow: 0 5px 20px rgba(0,242,254,0.3);
    font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; gap: 10px;
    letter-spacing: 0.3px;
}
.submit-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,242,254,0.5); }

/* Section Divider Label */
.section-label {
    font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.4);
    text-transform: uppercase; letter-spacing: 2px; margin-bottom: 14px; margin-top: 6px;
    display: flex; align-items: center; gap: 10px;
}
.section-label::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.08); }

@media(max-width: 600px) {
    .form-box { padding: 30px 24px; }
    .row { grid-template-columns: 1fr; }
    .top-bar { padding: 12px 20px; }
    .photo-tabs { font-size: 12px; }
    .cam-btn { padding: 10px 16px; font-size: 12px; }
}
</style>
</head>

<body>
<div class="particles"></div>

<!-- Top Nav -->
<div class="top-bar">
    <a href="user_dashboard.php" class="logo"><i class="fas fa-city"></i> Citizen Connect</a>
    <div class="nav-links">
        <a href="user_dashboard.php" class="nav-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
        <a href="logout.php" class="nav-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="form-wrapper">
    <div class="form-box">
        <div class="header-title">
            <i class="fas fa-bullhorn"></i>
            <h1>Lodge a Complaint</h1>
        </div>
        <p class="subtitle">Securely report a civic issue with photo evidence and real-time location tagging.</p>

        <form action="process_complaint.php" method="POST" enctype="multipart/form-data" id="complaintForm">

            <!-- Hidden canvas for camera capture -->
            <canvas id="captureCanvas" style="display:none;"></canvas>
            <!-- Hidden file input that complaint.php receives -->
            <input type="file" name="image" id="hiddenFileInput" accept="image/*" style="display:none;">

            <!-- ── COMPLAINT DETAILS ── -->
            <div class="section-label">Complaint Details</div>

            <div class="input-group">
                <label><i class="fas fa-heading" style="margin-right:6px;opacity:0.6;"></i>Subject</label>
                <input type="text" name="title" id="titleInput" placeholder="E.g., Broken Pipeline on Main St." required>
            </div>

            <div class="row">
                <div class="input-group">
                    <label><i class="fas fa-building" style="margin-right:6px;opacity:0.6;"></i>Department</label>
                    <select name="department" id="department" onchange="checkDepartment()" required>
                        <option value="">Select Category</option>
                        <option value="Roads">🚧 Roads &amp; Transport</option>
                        <option value="Water">💧 Water &amp; Sanitation</option>
                        <option value="Electricity">⚡ Power &amp; Electricity</option>
                        <option value="Other">➕ Other</option>
                    </select>
                </div>
                <div class="input-group">
                    <label><i class="fas fa-flag" style="margin-right:6px;opacity:0.6;"></i>Severity Level</label>
                    <select name="priority" id="prioritySelect" required>
                        <option value="Normal">🟢 Normal</option>
                        <option value="High">🟠 High Priority</option>
                        <option value="Urgent">🔴 Urgent / Hazard</option>
                    </select>
                </div>
            </div>

            <div class="input-group" id="deptDiv" style="display:none;">
                <label>Specify Target Department</label>
                <input type="text" id="newDept" name="new_department" placeholder="Enter department name...">
            </div>

            <div class="input-group">
                <label><i class="fas fa-align-left" style="margin-right:6px;opacity:0.6;"></i>Detailed Description</label>
                <textarea name="description" id="descInput" placeholder="Provide full context, landmarks, and details of the issue..." required></textarea>
            </div>

            <!-- ── PHOTO EVIDENCE ── -->
            <div class="section-label">Photo Evidence</div>

            <div class="photo-section" id="photoSection">
                <!-- Tabs -->
                <div class="photo-tabs">
                    <button type="button" class="photo-tab active" id="tabUpload" onclick="switchTab('upload')">
                        <i class="fas fa-cloud-upload-alt"></i> Upload Image
                    </button>
                    <button type="button" class="photo-tab" id="tabCamera" onclick="switchTab('camera')">
                        <i class="fas fa-camera"></i> Take Photo
                    </button>
                </div>

                <!-- Upload Panel -->
                <div class="photo-panel active" id="panelUpload">
                    <label class="upload-area" for="filePickerInput" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div class="up-title">Click to select a photo</div>
                        <div class="up-sub">JPG, PNG, WEBP — Max 10MB</div>
                    </label>
                    <input type="file" id="filePickerInput" accept="image/*" onchange="handleUpload(event)">

                    <div class="img-preview-wrap" id="uploadPreviewWrap">
                        <img id="uploadPreviewImg" src="#" alt="Image Preview">
                        <div class="preview-badge"><i class="fas fa-check-circle"></i> Photo Attached</div>
                        <button type="button" class="preview-clear" onclick="clearUpload()" title="Remove photo">✕</button>
                    </div>
                </div>

                <!-- Camera Panel -->
                <div class="photo-panel" id="panelCamera">
                    <div class="camera-container">
                        <video id="cameraStream" autoplay playsinline style="display:none;"></video>
                        <canvas id="frozenFrame" style="display:none; width:100%; border-radius:12px;"></canvas>
                        <div id="cameraPlaceholder" style="text-align:center; padding:40px 20px; color:rgba(255,255,255,0.4);">
                            <i class="fas fa-camera" style="font-size:48px; margin-bottom:12px; display:block; color:rgba(255,255,255,0.15);"></i>
                            <p style="font-size:14px;">Click <strong style="color:#00f2fe;">Start Camera</strong> to activate your device camera</p>
                        </div>
                    </div>
                    <div class="camera-controls">
                        <button type="button" class="cam-btn start" id="startCamBtn" onclick="startCamera()">
                            <i class="fas fa-video"></i> Start Camera
                        </button>
                        <button type="button" class="cam-btn capture" id="captureBtn" onclick="capturePhoto()" disabled>
                            <i class="fas fa-camera"></i> Capture Photo
                        </button>
                        <button type="button" class="cam-btn retake" id="retakeBtn" onclick="retakePhoto()" style="display:none;">
                            <i class="fas fa-redo"></i> Retake
                        </button>
                    </div>

                    <div class="img-preview-wrap" id="cameraPreviewWrap">
                        <img id="cameraPreviewImg" src="#" alt="Captured Photo">
                        <div class="preview-badge"><i class="fas fa-map-marker-alt"></i> Live Photo Captured</div>
                        <button type="button" class="preview-clear" onclick="retakePhoto()" title="Remove photo">✕</button>
                    </div>
                </div>
            </div>

            <!-- ── LOCATION ── -->
            <div class="section-label">Location</div>

            <div class="input-group" style="margin-bottom:16px;">
                <label><i class="fas fa-map-marker-alt" style="margin-right:6px;opacity:0.6;"></i>Precise Location</label>
                <div class="location-row">
                    <div class="input-group">
                        <input type="text" name="location" id="locationInput" placeholder="Full address or nearest landmark" required>
                    </div>
                    <button type="button" class="loc-detect-btn" id="detectLocBtn" onclick="detectLocation()" title="Detect my location">
                        <i class="fas fa-crosshairs" id="detectIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Live Map (shown after location detected or photo taken) -->
            <div class="location-map-block" id="locationMapBlock">
                <div class="map-header">
                    <div class="map-pulse"></div>
                    <i class="fas fa-satellite-dish"></i>
                    Live Location Detected
                    <span id="mapAccuracy" style="margin-left:auto;font-size:11px;color:rgba(255,255,255,0.5);"></span>
                </div>
                <div id="liveMap"></div>
                <div class="map-address-bar">
                    <i class="fas fa-map-marker-alt" style="color:#00f2fe;"></i>
                    <span id="mapAddressText">Fetching address…</span>
                </div>
                <div class="map-coords">
                    <div class="coord-chip"><i class="fas fa-arrows-alt-v" style="color:#00f2fe;opacity:0.7;"></i> <span id="coordLat">—</span></div>
                    <div class="coord-chip"><i class="fas fa-arrows-alt-h" style="color:#00f2fe;opacity:0.7;"></i> <span id="coordLng">—</span></div>
                </div>
            </div>

            <!-- Hidden geo fields (stored but not required) -->
            <input type="hidden" name="geo_lat" id="geoLat">
            <input type="hidden" name="geo_lng" id="geoLng">

            <!-- ── CONTACT ── -->
            <div class="section-label">Your Contact</div>
            <div class="row">
                <div class="input-group">
                    <label><i class="fas fa-mobile-alt" style="margin-right:6px;opacity:0.6;"></i>Mobile Number</label>
                    <input type="tel" name="contact_phone" id="phoneInput" placeholder="For Live SMS Alerts" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label><i class="fas fa-envelope" style="margin-right:6px;opacity:0.6;"></i>Email <span style="font-size:11px;opacity:0.5;">(Optional)</span></label>
                    <input type="email" name="contact_email" id="emailInput" placeholder="For Status Receipts" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>">
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                <i class="fas fa-paper-plane"></i> Submit Official Complaint
            </button>

        </form>
    </div>
</div>

<script>
/* ═══════════════════════════════════════════
   TAB SWITCHER
═══════════════════════════════════════════ */
let currentTab = 'upload';
let mediaStream = null;

function switchTab(tab) {
    currentTab = tab;
    document.getElementById('tabUpload').classList.toggle('active', tab === 'upload');
    document.getElementById('tabCamera').classList.toggle('active', tab === 'camera');
    document.getElementById('panelUpload').classList.toggle('active', tab === 'upload');
    document.getElementById('panelCamera').classList.toggle('active', tab === 'camera');

    // Stop camera when switching away
    if(tab === 'upload' && mediaStream) {
        mediaStream.getTracks().forEach(t => t.stop());
        mediaStream = null;
        document.getElementById('cameraStream').style.display = 'none';
        document.getElementById('cameraPlaceholder').style.display = 'block';
        document.getElementById('captureBtn').disabled = true;
        document.getElementById('startCamBtn').style.display = 'inline-flex';
    }
}

/* ═══════════════════════════════════════════
   UPLOAD HANDLER
═══════════════════════════════════════════ */
function handleUpload(event) {
    const file = event.target.files[0];
    if(!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('uploadPreviewImg').src = e.target.result;
        document.getElementById('uploadPreviewWrap').style.display = 'block';
        document.getElementById('uploadArea').style.display = 'none';

        // Transfer to the real form input
        transferFileToForm(file);
    };
    reader.readAsDataURL(file);
}

function clearUpload() {
    document.getElementById('uploadPreviewWrap').style.display = 'none';
    document.getElementById('uploadArea').style.display = 'flex';
    document.getElementById('filePickerInput').value = '';
    document.getElementById('hiddenFileInput').value = '';
}

/* ═══════════════════════════════════════════
   CAMERA HANDLER
═══════════════════════════════════════════ */
async function startCamera() {
    try {
        const btn = document.getElementById('startCamBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting…';
        btn.disabled = true;

        mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
        const video = document.getElementById('cameraStream');
        video.srcObject = mediaStream;
        video.style.display = 'block';

        document.getElementById('cameraPlaceholder').style.display = 'none';
        document.getElementById('frozenFrame').style.display = 'none';
        document.getElementById('captureBtn').disabled = false;
        document.getElementById('retakeBtn').style.display = 'none';
        document.getElementById('cameraPreviewWrap').style.display = 'none';
        btn.innerHTML = '<i class="fas fa-video"></i> Camera Live';
        btn.style.display = 'none';
    } catch(err) {
        const btn = document.getElementById('startCamBtn');
        btn.innerHTML = '<i class="fas fa-video"></i> Start Camera';
        btn.disabled = false;
        alert('📷 Camera access denied or unavailable.\n\nPlease allow camera permission in your browser settings, or use the "Upload Image" tab instead.');
    }
}

function capturePhoto() {
    const video = document.getElementById('cameraStream');
    const canvas = document.getElementById('captureCanvas');
    const frozen = document.getElementById('frozenFrame');

    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    // Show frozen frame
    frozen.width  = video.videoWidth;
    frozen.height = video.videoHeight;
    frozen.getContext('2d').drawImage(video, 0, 0);
    frozen.style.display = 'block';
    video.style.display  = 'none';

    // Stop stream
    if(mediaStream) mediaStream.getTracks().forEach(t => t.stop());
    mediaStream = null;

    // Preview
    canvas.toBlob(function(blob) {
        const url = URL.createObjectURL(blob);
        document.getElementById('cameraPreviewImg').src = url;
        document.getElementById('cameraPreviewWrap').style.display = 'block';

        // Transfer to form
        const file = new File([blob], 'captured_photo_' + Date.now() + '.jpg', { type: 'image/jpeg' });
        transferFileToForm(file);
    }, 'image/jpeg', 0.92);

    document.getElementById('captureBtn').disabled = true;
    document.getElementById('retakeBtn').style.display = 'inline-flex';

    // 🔥 Auto-detect location after capturing photo
    detectLocation();
}

function retakePhoto() {
    document.getElementById('cameraPreviewWrap').style.display = 'none';
    document.getElementById('frozenFrame').style.display = 'none';
    document.getElementById('cameraStream').style.display = 'none';
    document.getElementById('cameraPlaceholder').style.display = 'block';
    document.getElementById('captureBtn').disabled = true;
    document.getElementById('retakeBtn').style.display = 'none';
    document.getElementById('startCamBtn').innerHTML = '<i class="fas fa-video"></i> Start Camera';
    document.getElementById('startCamBtn').disabled = false;
    document.getElementById('startCamBtn').style.display = 'inline-flex';
    document.getElementById('hiddenFileInput').value = '';

    // Hide map
    document.getElementById('locationMapBlock').style.display = 'none';
}

/* Transfer a File object to the hidden <input type=file> (works via DataTransfer) */
function transferFileToForm(file) {
    try {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('hiddenFileInput').files = dt.files;
    } catch(e) {
        // Safari fallback — form submission still works with canvas base64 via hidden field
    }
}

/* ═══════════════════════════════════════════
   LIVE LOCATION + MAP
═══════════════════════════════════════════ */
let leafletMap = null;
let locationMarker = null;

function detectLocation() {
    if(!navigator.geolocation) {
        alert('⚠️ Geolocation is not supported by your browser.');
        return;
    }
    const btn = document.getElementById('detectLocBtn');
    btn.classList.add('spinning');
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            const acc = Math.round(pos.coords.accuracy);

            // Fill hidden fields
            document.getElementById('geoLat').value = lat;
            document.getElementById('geoLng').value = lng;

            // Update coordinate display
            document.getElementById('coordLat').textContent = 'Lat: ' + lat.toFixed(6);
            document.getElementById('coordLng').textContent = 'Lng: ' + lng.toFixed(6);
            document.getElementById('mapAccuracy').textContent = '±' + acc + 'm';

            // Show map block
            const block = document.getElementById('locationMapBlock');
            block.style.display = 'block';

            // Initialise or update map
            if(!leafletMap) {
                leafletMap = L.map('liveMap', { zoomControl: true, attributionControl: false }).setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19
                }).addTo(leafletMap);

                // Custom glowing marker
                const pulseIcon = L.divIcon({
                    className: '',
                    html: `<div style="
                        width:20px;height:20px;border-radius:50%;
                        background:#00f2fe;border:3px solid white;
                        box-shadow:0 0 0 0 rgba(0,242,254,0.6);
                        animation:pulse 1.5s infinite;
                    "></div>`,
                    iconSize: [20,20],
                    iconAnchor: [10,10]
                });
                locationMarker = L.marker([lat, lng], { icon: pulseIcon })
                    .addTo(leafletMap)
                    .bindPopup('<b>📍 Your Location</b><br>Accuracy: ±' + acc + 'm')
                    .openPopup();

                // Accuracy circle
                L.circle([lat, lng], { radius: acc, color: '#00f2fe', fillColor: '#00f2fe', fillOpacity: 0.08, weight: 1 }).addTo(leafletMap);
            } else {
                leafletMap.setView([lat, lng], 16);
                locationMarker.setLatLng([lat, lng]);
            }

            // Reverse geocode with Nominatim
            document.getElementById('mapAddressText').textContent = 'Fetching address…';
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=17&addressdetails=1`)
                .then(r => r.json())
                .then(data => {
                    const addr = data.display_name || `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                    document.getElementById('mapAddressText').textContent = addr;
                    // Auto-fill location input if empty
                    const locIn = document.getElementById('locationInput');
                    if(!locIn.value.trim()) locIn.value = addr;
                })
                .catch(() => {
                    const fallback = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                    document.getElementById('mapAddressText').textContent = fallback;
                    const locIn = document.getElementById('locationInput');
                    if(!locIn.value.trim()) locIn.value = fallback;
                });

            btn.classList.remove('spinning');
            btn.disabled = false;

            // Invalidate map size (it may have been hidden during init)
            setTimeout(() => leafletMap && leafletMap.invalidateSize(), 200);
        },
        function(err) {
            btn.classList.remove('spinning');
            btn.disabled = false;
            const msgs = {
                1: 'Location permission was denied. Please allow location access in your browser.',
                2: 'Location could not be determined. Make sure GPS/WiFi is enabled.',
                3: 'Location request timed out. Please try again.'
            };
            alert('📍 ' + (msgs[err.code] || 'Could not get location.'));
        },
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
    );
}

/* ═══════════════════════════════════════════
   DEPARTMENT TOGGLE
═══════════════════════════════════════════ */
function checkDepartment() {
    const dept = document.getElementById('department').value;
    const div  = document.getElementById('deptDiv');
    const inp  = document.getElementById('newDept');
    if(dept === 'Other') { div.style.display = 'block'; inp.required = true; }
    else                 { div.style.display = 'none';  inp.required = false; }
}

/* ═══════════════════════════════════════════
   FORM SUBMIT GUARD
═══════════════════════════════════════════ */
document.getElementById('complaintForm').addEventListener('submit', function(e) {
    // Make sure the hidden file input has the file (for upload tab)
    const hidden = document.getElementById('hiddenFileInput');
    const picker = document.getElementById('filePickerInput');

    if(currentTab === 'upload' && picker.files.length > 0 && hidden.files.length === 0) {
        try {
            const dt = new DataTransfer();
            dt.items.add(picker.files[0]);
            hidden.files = dt.files;
        } catch(ex) {}
    }

    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';
    btn.disabled = true;
});
</script>

</body>
</html>