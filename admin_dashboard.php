<?php
session_start();
$conn = new mysqli("localhost", "root", "", "citizen_connect");
require_once 'NotificationHelper.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

// Core complaint metrics
$total     = $conn->query("SELECT COUNT(*) as c FROM complaints")->fetch_assoc()['c'];
$pending   = $conn->query("SELECT COUNT(*) as c FROM complaints WHERE status='Pending'")->fetch_assoc()['c'];
$completed = $conn->query("SELECT COUNT(*) as c FROM complaints WHERE status='Completed'")->fetch_assoc()['c'];
$progress  = $conn->query("SELECT COUNT(*) as c FROM complaints WHERE status='In Progress'")->fetch_assoc()['c'];

// Priority metrics
$urgent = $conn->query("SELECT COUNT(*) as c FROM complaints WHERE priority='Urgent'")->fetch_assoc()['c'];
$high   = $conn->query("SELECT COUNT(*) as c FROM complaints WHERE priority='High'")->fetch_assoc()['c'];
$normal = $conn->query("SELECT COUNT(*) as c FROM complaints WHERE priority='Normal'")->fetch_assoc()['c'];

// Department resolution data
$deptData = $conn->query("
    SELECT department,
    COUNT(*) as total,
    SUM(status='Completed') as resolved,
    SUM(status='In Progress') as in_progress,
    SUM(status='Pending') as pending_count
    FROM complaints GROUP BY department
");

// Total registered users
$total_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user' OR role IS NULL")->fetch_assoc()['c'];

// Recent feedback
$feedback_count = $conn->query("SELECT COUNT(*) as c FROM feedback")->fetch_assoc()['c'] ?? 0;

// Notification count
$notif_sent = $conn->query("SELECT COUNT(*) as c FROM notifications")->fetch_assoc()['c'] ?? 0;

// Recent complaints
$recent = $conn->query("SELECT * FROM complaints ORDER BY id DESC LIMIT 8");

// Handle notification sending
$notif_msg = "";
if(isset($_POST['send_notification'])) {
    $notif_title   = $conn->real_escape_string($_POST['notif_title']);
    $notif_body    = $conn->real_escape_string($_POST['notif_body']);
    $notif_dept    = $conn->real_escape_string($_POST['notif_dept']);
    $stmt = $conn->prepare("INSERT INTO notifications (title, message, department, sent_by) VALUES (?, ?, ?, 'admin')");
    $stmt->bind_param("sss", $notif_title, $notif_body, $notif_dept);
    if($stmt->execute()) {
        // --- BROADCAST TO CITIZENS (Email/SMS) ---
        $citizens_query = ($notif_dept === 'all') 
            ? "SELECT DISTINCT email, phone FROM users WHERE (role='user' OR role IS NULL) AND email IS NOT NULL AND phone IS NOT NULL"
            : "SELECT DISTINCT contact_email AS email, contact_phone AS phone FROM complaints WHERE department='$notif_dept'";
        
        $c_res = $conn->query($citizens_query);
        if($c_res && $c_res->num_rows > 0) {
            while($citizen = $c_res->fetch_assoc()) {
                NotificationHelper::broadcast(
                    $citizen['email'], 
                    $citizen['phone'], 
                    "Announcement: " . $notif_title,
                    "<html><body><h2>Important Citizen Notice</h2><p>$notif_body</p></body></html>",
                    "Notice: $notif_title. $notif_body",
                    null, // No DB record here (already done above)
                    null, // No target user (department-wide)
                    $notif_dept
                );
            }
        }

        $notif_msg = "success";
        $notif_sent++;
    } else {
        $notif_msg = "error";
    }
}

// Handle feedback reply
$fb_reply_msg = "";
if(isset($_POST['send_reply'])) {
    $fb_id    = (int)$_POST['fb_id'];
    $reply    = $conn->real_escape_string($_POST['admin_reply']);
    $conn->query("UPDATE feedback SET admin_reply='$reply', replied_at=NOW() WHERE id=$fb_id");
    $fb_reply_msg = "replied";
}

// Get all feedback
$feedbacks = $conn->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Admin Command Center | Citizen Connect</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Citizen Connect Admin Dashboard - Full system monitoring and management center">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --bg-dark:    #060b14;
    --bg-card:    rgba(13, 20, 35, 0.85);
    --bg-panel:   rgba(10, 17, 30, 0.9);
    --border:     rgba(255, 255, 255, 0.06);
    --border-glow:rgba(79, 172, 254, 0.35);
    --cyan:       #4facfe;
    --cyan-dark:  #00c6fb;
    --green:      #43e97b;
    --orange:     #fa8231;
    --red:        #ff4757;
    --purple:     #a855f7;
    --gold:       #ffd700;
    --text:       #e8edf5;
    --muted:      rgba(232, 237, 245, 0.5);
    --glow-cyan:  0 0 30px rgba(79, 172, 254, 0.25);
    --glow-green: 0 0 30px rgba(67, 233, 123, 0.2);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', sans-serif;
    background: var(--bg-dark);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
    background-image:
        radial-gradient(ellipse at 20% 10%, rgba(79,172,254,0.08) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 80%, rgba(168,85,247,0.06) 0%, transparent 50%);
}

/* ── SIDEBAR LAYOUT ── */
.layout { display: flex; min-height: 100vh; }

.sidebar {
    width: 260px;
    min-height: 100vh;
    background: rgba(5, 10, 20, 0.95);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    padding: 0;
    position: sticky; top: 0; height: 100vh;
    overflow-y: auto;
    flex-shrink: 0;
    backdrop-filter: blur(20px);
}

.sidebar-logo {
    padding: 28px 24px 20px;
    border-bottom: 1px solid var(--border);
}
.sidebar-logo h2 {
    font-size: 18px; font-weight: 800;
    background: linear-gradient(135deg, var(--cyan), var(--purple));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    display: flex; align-items: center; gap: 10px; margin: 0;
}
.sidebar-logo span {
    display: block; font-size: 11px; color: var(--muted); font-weight: 500;
    margin-top: 4px; -webkit-text-fill-color: initial;
    text-transform: uppercase; letter-spacing: 1.5px;
}

.sidebar-nav { padding: 20px 0; flex: 1; }
.nav-group-label {
    padding: 0 24px 8px; font-size: 10px; font-weight: 700; color: var(--muted);
    text-transform: uppercase; letter-spacing: 2px;
}
.nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 24px; font-size: 14px; font-weight: 500;
    color: var(--muted); text-decoration: none; transition: 0.25s;
    cursor: pointer; border: none; background: none; width: 100%;
    border-left: 3px solid transparent;
}
.nav-item:hover, .nav-item.active {
    color: var(--text); background: rgba(79, 172, 254, 0.08);
    border-left-color: var(--cyan);
}
.nav-item i { width: 20px; text-align: center; font-size: 15px; }
.nav-item.active i { color: var(--cyan); }
.nav-item.danger:hover { color: var(--red); background: rgba(255,71,87,0.08); border-left-color: var(--red); }

.sidebar-bottom {
    padding: 20px; border-top: 1px solid var(--border);
}
.admin-badge {
    display: flex; align-items: center; gap: 12px;
    padding: 12px; border-radius: 10px;
    background: rgba(79,172,254,0.08); border: 1px solid var(--border-glow);
}
.admin-badge .avatar {
    width: 38px; height: 38px; border-radius: 50%;
    background: linear-gradient(135deg, var(--cyan), var(--purple));
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; font-weight: 700; color: white;
}
.admin-badge .info .name { font-size: 13px; font-weight: 600; }
.admin-badge .info .role { font-size: 11px; color: var(--cyan); font-weight: 500; text-transform: uppercase; letter-spacing: 1px; }

/* ── MAIN CONTENT ── */
.main { flex: 1; overflow-y: auto; }

.top-header {
    padding: 20px 40px;
    background: rgba(5, 10, 20, 0.7);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center;
    position: sticky; top: 0; z-index: 100;
}
.header-left h1 { font-size: 22px; font-weight: 700; margin: 0 0 2px; }
.header-left p { font-size: 13px; color: var(--muted); margin: 0; }
.header-right { display: flex; gap: 12px; align-items: center; }
.time-badge {
    background: rgba(79,172,254,0.1); border: 1px solid var(--border-glow);
    padding: 8px 16px; border-radius: 20px; font-size: 13px; color: var(--cyan); font-weight: 600;
}
.logout-link {
    background: rgba(255, 71, 87, 0.1); border: 1px solid rgba(255,71,87,0.3);
    color: var(--red); padding: 8px 18px; border-radius: 20px;
    text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.25s;
    display: flex; align-items: center; gap: 6px;
}
.logout-link:hover { background: rgba(255, 71, 87, 0.25); }

/* ── CONTENT SECTIONS ── */
.content-section { padding: 32px 40px; display: none; }
.content-section.active { display: block; }

/* ── ANIMATED METRIC CARDS ── */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px; margin-bottom: 30px;
}
.metric-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
    display: flex; flex-direction: column; gap: 12px;
    transition: 0.3s; position: relative; overflow: hidden;
    backdrop-filter: blur(15px);
}
.metric-card::before {
    content: ''; position: absolute;
    top: 0; left: 0; right: 0; height: 2px;
    background: var(--accent-color, linear-gradient(90deg, var(--cyan), var(--purple)));
    border-radius: 16px 16px 0 0;
}
.metric-card:hover {
    transform: translateY(-5px);
    border-color: var(--border-glow);
    box-shadow: var(--glow-cyan);
    cursor: pointer;
}
.metric-card.cyan  { --accent-color: linear-gradient(90deg,#4facfe,#00f2fe); }
.metric-card.orange{ --accent-color: linear-gradient(90deg,#fa8231,#f7b733); }
.metric-card.green { --accent-color: linear-gradient(90deg,#43e97b,#38f9d7); }
.metric-card.red   { --accent-color: linear-gradient(90deg,#ff4757,#ff6b81); }
.metric-card.purple{ --accent-color: linear-gradient(90deg,#a855f7,#7c3aed); }
.metric-card.gold  { --accent-color: linear-gradient(90deg,#ffd700,#ffaa00); }

.metric-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
}
.metric-card.cyan   .metric-icon { background: rgba(79,172,254,0.15); color: var(--cyan); }
.metric-card.orange .metric-icon { background: rgba(250,130,49,0.15); color: var(--orange); }
.metric-card.green  .metric-icon { background: rgba(67,233,123,0.15); color: var(--green); }
.metric-card.red    .metric-icon { background: rgba(255,71,87,0.15);  color: var(--red); }
.metric-card.purple .metric-icon { background: rgba(168,85,247,0.15); color: var(--purple); }
.metric-card.gold   .metric-icon { background: rgba(255,215,0,0.15);  color: var(--gold); }

.metric-value { font-size: 38px; font-weight: 800; line-height: 1; }
.metric-label { font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }
.metric-sub {
    font-size: 12px; color: var(--muted); margin-top: -4px;
    display: flex; align-items: center; gap: 4px;
}

/* ── GLASS PANELS ── */
.panel {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px; padding: 28px;
    backdrop-filter: blur(15px);
    margin-bottom: 24px;
}
.panel-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 22px; padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
}
.panel-title {
    font-size: 16px; font-weight: 700;
    display: flex; align-items: center; gap: 10px;
}
.panel-title .dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--cyan); display: inline-block;
    box-shadow: 0 0 8px var(--cyan); animation: pulse 2s infinite;
}
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

/* ── CHARTS GRID ── */
.charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
.chart-wrap { height: 320px; position: relative; }

/* ── DEPARTMENT TABLE ── */
.dept-table-wrap { overflow-x: auto; }
.dept-table { width: 100%; border-collapse: collapse; }
.dept-table th {
    padding: 12px 16px; text-align: left;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1px; color: var(--muted);
    border-bottom: 1px solid var(--border);
}
.dept-table td {
    padding: 16px; font-size: 14px; color: var(--text);
    border-bottom: 1px solid var(--border);
}
.dept-table tr:last-child td { border-bottom: none; }
.dept-table tr:hover td { background: rgba(79,172,254,0.04); }

.progress-bar-wrap { height: 6px; background: rgba(255,255,255,0.07); border-radius: 3px; overflow: hidden; width: 120px; }
.progress-bar-fill { height: 100%; border-radius: 3px; transition: width 1s ease; }

/* ── COMPLAINTS TABLE ── */
.complaints-table-wrap { max-height: 480px; overflow-y: auto; }
.complaints-table-wrap::-webkit-scrollbar { width: 6px; }
.complaints-table-wrap::-webkit-scrollbar-track { background: rgba(255,255,255,0.03); border-radius: 3px; }
.complaints-table-wrap::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 3px; }
.comp-table { width: 100%; border-collapse: collapse; }
.comp-table th {
    padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1px; color: var(--muted);
    border-bottom: 1px solid var(--border); position: sticky; top: 0;
    background: var(--bg-panel); z-index: 10;
}
.comp-table td { padding: 14px 16px; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.03); }
.comp-table tr:hover td { background: rgba(79,172,254,0.04); }
.comp-id { color: var(--cyan); font-weight: 700; font-family: monospace; font-size: 13px; }

.priority-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.priority-badge.Urgent { background: rgba(255,71,87,0.15); color: var(--red); border: 1px solid rgba(255,71,87,0.3); }
.priority-badge.High   { background: rgba(250,130,49,0.15); color: var(--orange); border: 1px solid rgba(250,130,49,0.3); }
.priority-badge.Normal { background: rgba(67,233,123,0.15); color: var(--green); border: 1px solid rgba(67,233,123,0.3); }

.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
}
.status-badge.Pending    { background: rgba(250,130,49,0.15); color: var(--orange); border: 1px solid rgba(250,130,49,0.3); }
.status-badge.InProgress { background: rgba(79,172,254,0.15); color: var(--cyan); border: 1px solid rgba(79,172,254,0.3); }
.status-badge.Completed  { background: rgba(67,233,123,0.15); color: var(--green); border: 1px solid rgba(67,233,123,0.3); }

.action-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600;
    background: rgba(79,172,254,0.1); color: var(--cyan);
    border: 1px solid rgba(79,172,254,0.25); text-decoration: none; transition: 0.25s;
}
.action-btn:hover { background: rgba(79,172,254,0.25); }

/* ── NOTIFICATION FORM ── */
.notif-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { display: flex; flex-direction: column; gap: 8px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); }
.form-group input,
.form-group select,
.form-group textarea {
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 16px;
    color: var(--text);
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    transition: 0.25s;
    outline: none;
}
.form-group input::placeholder,
.form-group textarea::placeholder { color: var(--muted); }
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--border-glow);
    box-shadow: 0 0 0 3px rgba(79,172,254,0.1);
}
.form-group select option { background: #0d1423; }
.form-group textarea { min-height: 110px; resize: vertical; }
.send-btn {
    background: linear-gradient(135deg, var(--cyan), var(--purple));
    border: none; border-radius: 10px; padding: 14px 30px;
    color: white; font-size: 14px; font-weight: 700;
    cursor: pointer; transition: 0.3s;
    display: inline-flex; align-items: center; gap: 8px;
}
.send-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(79,172,254,0.4); }

/* ── NOTIFICATION HISTORY ── */
.notif-item {
    padding: 18px 20px; border-radius: 12px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--border);
    margin-bottom: 12px; transition: 0.25s;
}
.notif-item:hover { border-color: var(--border-glow); background: rgba(79,172,254,0.04); }
.notif-item .notif-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 8px; }
.notif-item .notif-title { font-size: 15px; font-weight: 600; }
.dept-tag {
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
    background: rgba(168,85,247,0.15); color: var(--purple); border: 1px solid rgba(168,85,247,0.3);
    white-space: nowrap;
}
.notif-item .notif-body { font-size: 13px; color: var(--muted); line-height: 1.5; }

/* ── FEEDBACK PANEL ── */
.feedback-item {
    padding: 20px; border-radius: 12px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--border);
    margin-bottom: 14px; transition: 0.25s;
}
.feedback-item:hover { border-color: var(--border-glow); }
.feedback-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.feedback-user { font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; }
.stars { color: var(--gold); font-size: 14px; letter-spacing: 2px; }
.feedback-text { font-size: 14px; color: var(--muted); line-height: 1.5; margin-bottom: 12px; }
.reply-form { display: flex; gap: 10px; flex-direction: column; }
.reply-input {
    background: rgba(255,255,255,0.05); border: 1px solid var(--border);
    border-radius: 8px; padding: 10px 14px; color: var(--text);
    font-size: 13px; font-family: 'Inter', sans-serif; resize: vertical; min-height: 70px; outline: none;
    transition: 0.25s;
}
.reply-input:focus { border-color: var(--border-glow); }
.reply-btn {
    background: rgba(67,233,123,0.15); border: 1px solid rgba(67,233,123,0.3);
    color: var(--green); border-radius: 8px; padding: 8px 18px;
    font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.25s;
    display: inline-flex; align-items: center; gap: 6px;
}
.reply-btn:hover { background: rgba(67,233,123,0.25); }
.admin-reply-bubble {
    background: rgba(79,172,254,0.08); border: 1px solid rgba(79,172,254,0.2);
    border-radius: 8px; padding: 12px 14px; margin-top: 8px;
}
.admin-reply-bubble .reply-label { font-size: 11px; font-weight: 700; color: var(--cyan); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
.admin-reply-bubble p { font-size: 13px; color: var(--text); margin: 0; }

/* ── SUCCESS / ERROR ALERT ── */
.flash {
    padding: 14px 20px; border-radius: 10px; margin-bottom: 20px;
    font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px;
    animation: fadeUp 0.4s ease;
}
.flash.success { background: rgba(67,233,123,0.12); border: 1px solid rgba(67,233,123,0.3); color: var(--green); }
.flash.error   { background: rgba(255,71,87,0.12); border: 1px solid rgba(255,71,87,0.3);   color: var(--red); }

@keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* ── ACTION BUTTONS FROM DASHBOARD ── */
.quick-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 24px; }
.quick-btn {
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;
    padding: 22px 16px; border-radius: 14px; border: 1px solid var(--border);
    background: rgba(255,255,255,0.02); text-decoration: none;
    color: var(--text); font-size: 13px; font-weight: 600; transition: 0.3s;
    cursor: pointer;
}
.quick-btn:hover { background: rgba(79,172,254,0.08); border-color: var(--border-glow); transform: translateY(-3px); }
.quick-btn i { font-size: 24px; }
.quick-btn.blue   i { color: var(--cyan); }
.quick-btn.purple i { color: var(--purple); }
.quick-btn.red    i { color: var(--red); }

/* ── RESPONSIVE ── */
@media(max-width:1024px) {
    .charts-grid { grid-template-columns: 1fr; }
    .notif-form-grid { grid-template-columns: 1fr; }
}
@media(max-width:768px) {
    .sidebar { display: none; }
    .content-section { padding: 20px; }
    .top-header { padding: 16px 20px; }
    .metrics-grid { grid-template-columns: 1fr 1fr; }
    .quick-actions { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="layout">

<!-- ── SIDEBAR ── -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <h2><i class="fas fa-city"></i> Citizen Connect</h2>
        <span>Admin Command Center</span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-group-label">Overview</div>
        <button class="nav-item active" onclick="showSection('overview', this)">
            <i class="fas fa-th-large"></i> Dashboard
        </button>
        <button class="nav-item" onclick="showSection('complaints', this)">
            <i class="fas fa-clipboard-list"></i> All Complaints
        </button>
        <button class="nav-item" onclick="showSection('departments', this)">
            <i class="fas fa-building"></i> Department Stats
        </button>

        <div class="nav-group-label" style="margin-top:20px;">Communication</div>
        <button class="nav-item" onclick="showSection('notifications', this)">
            <i class="fas fa-bell"></i> Send Notifications
            <?php if($notif_sent > 0): ?>
                <span style="margin-left:auto; background:var(--purple); color:white; font-size:10px; padding:2px 7px; border-radius:10px;"><?php echo $notif_sent; ?></span>
            <?php endif; ?>
        </button>
        <button class="nav-item" onclick="showSection('feedback', this)">
            <i class="fas fa-star"></i> Citizen Feedback
            <?php if($feedback_count > 0): ?>
                <span style="margin-left:auto; background:var(--gold); color:#000; font-size:10px; padding:2px 7px; border-radius:10px;"><?php echo $feedback_count; ?></span>
            <?php endif; ?>
        </button>

        <div class="nav-group-label" style="margin-top:20px;">Access</div>
        <a class="nav-item" href="view_complaints.php"><i class="fas fa-file-alt"></i> Master View</a>
        <a class="nav-item" href="department_login.php"><i class="fas fa-door-open"></i> Dept Login</a>
        <a class="nav-item danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
    <div class="sidebar-bottom">
        <div class="admin-badge">
            <div class="avatar">A</div>
            <div class="info">
                <div class="name">Administrator</div>
                <div class="role">God Mode</div>
            </div>
        </div>
    </div>
</aside>

<!-- ── MAIN ── -->
<main class="main">

    <!-- Top Header -->
    <div class="top-header">
        <div class="header-left">
            <h1 id="sectionTitle">Dashboard Overview</h1>
            <p id="sectionSub">Real-time city-wide performance command center</p>
        </div>
        <div class="header-right">
            <div class="time-badge"><i class="fas fa-clock" style="margin-right:6px;"></i><span id="liveClock"></span></div>
            <a href="logout.php" class="logout-link"><i class="fas fa-lock"></i> Secure Exit</a>
        </div>
    </div>

    <!-- ══════════════════════════════════════
         SECTION: OVERVIEW
    ══════════════════════════════════════ -->
    <section class="content-section active" id="section-overview">

        <!-- Metric Cards -->
        <div class="metrics-grid">
            <a href="view_complaints.php" style="text-decoration:none; color:inherit;">
                <div class="metric-card cyan">
                    <div class="metric-icon"><i class="fas fa-folder-open"></i></div>
                    <div class="metric-value"><?php echo $total; ?></div>
                    <div class="metric-label">Total Complaints</div>
                    <div class="metric-sub"><i class="fas fa-arrow-up" style="color:var(--green); font-size:10px;"></i> All time logged</div>
                </div>
            </a>
            <a href="view_complaints.php?status=Pending" style="text-decoration:none; color:inherit;">
                <div class="metric-card orange">
                    <div class="metric-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="metric-value"><?php echo $pending; ?></div>
                    <div class="metric-label">Pending Review</div>
                    <div class="metric-sub"><i class="fas fa-exclamation-circle" style="color:var(--orange); font-size:10px;"></i> Awaiting action</div>
                </div>
            </a>
            <a href="view_complaints.php?status=In Progress" style="text-decoration:none; color:inherit;">
                <div class="metric-card cyan" style="--accent-color: linear-gradient(90deg,#4facfe,#2196f3);">
                    <div class="metric-icon" style="background:rgba(33,150,243,0.15);color:#4facfe;"><i class="fas fa-tools"></i></div>
                    <div class="metric-value"><?php echo $progress; ?></div>
                    <div class="metric-label">In Progress</div>
                    <div class="metric-sub"><i class="fas fa-circle-notch fa-spin" style="color:var(--cyan); font-size:10px;"></i> Being resolved</div>
                </div>
            </a>
            <a href="view_complaints.php?status=Completed" style="text-decoration:none; color:inherit;">
                <div class="metric-card green">
                    <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="metric-value"><?php echo $completed; ?></div>
                    <div class="metric-label">Resolved</div>
                    <div class="metric-sub"><i class="fas fa-check" style="color:var(--green); font-size:10px;"></i> Successfully closed</div>
                </div>
            </a>
            <a href="view_complaints.php?priority=Urgent" style="text-decoration:none; color:inherit;">
                <div class="metric-card red">
                    <div class="metric-icon"><i class="fas fa-fire"></i></div>
                    <div class="metric-value"><?php echo $urgent; ?></div>
                    <div class="metric-label">Urgent Priority</div>
                    <div class="metric-sub"><i class="fas fa-exclamation" style="color:var(--red); font-size:10px;"></i> Immediate attention</div>
                </div>
            </a>
            <div class="metric-card gold">
                <div class="metric-icon"><i class="fas fa-users"></i></div>
                <div class="metric-value"><?php echo $total_users; ?></div>
                <div class="metric-label">Registered Users</div>
                <div class="metric-sub"><i class="fas fa-user-check" style="color:var(--gold); font-size:10px;"></i> Active accounts</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="panel" style="margin-bottom:0;">
                <div class="panel-header">
                    <div class="panel-title"><span class="dot"></span> Complaint Status Distribution</div>
                </div>
                <div class="chart-wrap"><canvas id="chartStatus"></canvas></div>
            </div>
            <div class="panel" style="margin-bottom:0;">
                <div class="panel-header">
                    <div class="panel-title"><span class="dot" style="background:var(--red);box-shadow:0 0 8px var(--red);"></span> Priority Load Analysis</div>
                </div>
                <div class="chart-wrap"><canvas id="chartPriority"></canvas></div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><span class="dot" style="background:var(--purple);box-shadow:0 0 8px var(--purple);"></span> Quick Actions</div>
            </div>
            <div class="quick-actions">
                <a href="view_complaints.php" class="quick-btn blue">
                    <i class="fas fa-file-alt"></i> Master Complaint View
                </a>
                <button class="quick-btn purple" onclick="showSection('notifications', document.querySelector('[onclick*=notifications]'))">
                    <i class="fas fa-bell"></i> Send Notification
                </button>
                <a href="department_login.php" class="quick-btn red">
                    <i class="fas fa-building"></i> Department Panel
                </a>
            </div>
        </div>

    </section>

    <!-- ══════════════════════════════════════
         SECTION: COMPLAINTS
    ══════════════════════════════════════ -->
    <section class="content-section" id="section-complaints">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><span class="dot"></span> All Citizen Complaints Directory</div>
                <div style="font-size:12px;color:var(--muted);">Total: <?php echo $total; ?> records</div>
            </div>
            <div class="complaints-table-wrap">
                <table class="comp-table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $all = $conn->query("SELECT * FROM complaints ORDER BY id DESC");
                    if($all && $all->num_rows > 0) {
                        while($row = $all->fetch_assoc()) {
                            $status_class = str_replace(' ', '', $row['status']);
                            $prio = $row['priority'] ?? 'Normal';
                    ?>
                    <tr>
                        <td><span class="comp-id">#<?php echo str_pad($row['id'],4,'0',STR_PAD_LEFT); ?></span></td>
                        <td style="font-weight:500; max-width:200px;"><?php echo htmlspecialchars($row['title']); ?></td>
                        <td style="color:var(--muted);"><?php echo ucfirst(htmlspecialchars($row['department'])); ?></td>
                        <td>
                            <span class="priority-badge <?php echo htmlspecialchars($prio); ?>">
                                <i class="fas fa-circle" style="font-size:7px;"></i>
                                <?php echo htmlspecialchars($prio); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <a href="update_status.php?id=<?php echo $row['id']; ?>" class="action-btn">
                                <i class="fas fa-edit"></i> Update
                            </a>
                        </td>
                    </tr>
                    <?php } } else { echo "<tr><td colspan='6' style='text-align:center;padding:40px;color:var(--muted);'>No complaints logged yet.</td></tr>"; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════
         SECTION: DEPARTMENTS
    ══════════════════════════════════════ -->
    <section class="content-section" id="section-departments">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><span class="dot" style="background:var(--green);box-shadow:0 0 8px var(--green);"></span> Department Performance Matrix</div>
            </div>
            <div class="dept-table-wrap">
                <table class="dept-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Total</th>
                            <th>Pending</th>
                            <th>In Progress</th>
                            <th>Resolved</th>
                            <th>Resolution Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $deptData->data_seek(0);
                    while($d = $deptData->fetch_assoc()) {
                        $t  = $d['total'];
                        $r  = $d['resolved'] ?? 0;
                        $pct= ($t > 0) ? round(($r/$t)*100) : 0;
                        $bar_color = $pct >= 70 ? 'linear-gradient(90deg,#43e97b,#38f9d7)' :
                                    ($pct >= 40 ? 'linear-gradient(90deg,#4facfe,#00f2fe)' :
                                                  'linear-gradient(90deg,#ff4757,#ff6b81)');
                    ?>
                    <tr>
                        <td style="font-weight:600;"><i class="fas fa-building" style="color:var(--cyan);margin-right:8px;"></i><?php echo ucfirst($d['department']); ?></td>
                        <td style="font-weight:700;"><?php echo $t; ?></td>
                        <td style="color:var(--orange);"><?php echo $d['pending_count'] ?? 0; ?></td>
                        <td style="color:var(--cyan);"><?php echo $d['in_progress'] ?? 0; ?></td>
                        <td style="color:var(--green);"><?php echo $r; ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="progress-bar-wrap">
                                    <div class="progress-bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $bar_color; ?>;"></div>
                                </div>
                                <span style="font-size:13px;font-weight:700;color:<?php echo $pct>=70?'var(--green)':($pct>=40?'var(--cyan)':'var(--red)'); ?>"><?php echo $pct; ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Charts in Dept section -->
        <div class="charts-grid">
            <div class="panel" style="margin-bottom:0;">
                <div class="panel-header"><div class="panel-title"><span class="dot"></span> Volume by Department</div></div>
                <div class="chart-wrap"><canvas id="chartDept"></canvas></div>
            </div>
            <div class="panel" style="margin-bottom:0;">
                <div class="panel-header"><div class="panel-title"><span class="dot" style="background:var(--purple);box-shadow:0 0 8px var(--purple);"></span> Priority Overview</div></div>
                <div class="chart-wrap"><canvas id="chartPriority2"></canvas></div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════
         SECTION: NOTIFICATIONS
    ══════════════════════════════════════ -->
    <section class="content-section" id="section-notifications">

        <?php if($notif_msg === 'success'): ?>
            <div class="flash success"><i class="fas fa-check-circle"></i> Notification broadcast successfully to all <?php echo htmlspecialchars($_POST['notif_dept']); ?> department users!</div>
        <?php elseif($notif_msg === 'error'): ?>
            <div class="flash error"><i class="fas fa-exclamation-circle"></i> Failed to send notification. Please try again.</div>
        <?php endif; ?>

        <!-- Send Form -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><span class="dot" style="background:var(--purple);box-shadow:0 0 8px var(--purple);"></span> Broadcast Notification to Citizens</div>
            </div>
            <form method="POST" action="#section-notifications">
                <div class="notif-form-grid">
                    <div class="form-group">
                        <label>Notification Title</label>
                        <input type="text" name="notif_title" placeholder="e.g. Water disruption notice..." required>
                    </div>
                    <div class="form-group">
                        <label>Target Department</label>
                        <select name="notif_dept">
                            <option value="all">📢 All Citizens (System-Wide)</option>
                            <option value="water">💧 Water Supply</option>
                            <option value="electricity">⚡ Electricity</option>
                            <option value="roads">🛣️ Roads & Transport</option>
                            <option value="sanitation">🗑️ Sanitation</option>
                            <option value="parks">🌳 Parks & Recreation</option>
                            <option value="health">🏥 Health Department</option>
                            <option value="education">📚 Education</option>
                            <option value="other">🔧 Other</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Message Body</label>
                        <textarea name="notif_body" placeholder="Enter your notification message for citizens..." required></textarea>
                    </div>
                </div>
                <div style="margin-top:20px;">
                    <button type="submit" name="send_notification" class="send-btn">
                        <i class="fas fa-paper-plane"></i> Broadcast Notification
                    </button>
                </div>
            </form>
        </div>

        <!-- Notification History -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><span class="dot"></span> Notification History</div>
                <div style="font-size:12px;color:var(--muted);"><?php echo $notif_sent; ?> sent</div>
            </div>
            <?php
            $notifs = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20");
            if($notifs && $notifs->num_rows > 0) {
                while($n = $notifs->fetch_assoc()) {
            ?>
            <div class="notif-item">
                <div class="notif-head">
                    <div class="notif-title"><i class="fas fa-bell" style="color:var(--purple);margin-right:8px;"></i><?php echo htmlspecialchars($n['title']); ?></div>
                    <div style="display:flex;gap:8px;align-items:center;flex-shrink:0;">
                        <span class="dept-tag"><?php echo ucfirst($n['department']); ?></span>
                        <span style="font-size:11px;color:var(--muted);"><?php echo date('M j, g:i a', strtotime($n['created_at'])); ?></span>
                    </div>
                </div>
                <div class="notif-body"><?php echo htmlspecialchars($n['message']); ?></div>
            </div>
            <?php } } else { echo "<p style='color:var(--muted);text-align:center;padding:30px;'>No notifications sent yet.</p>"; } ?>
        </div>
    </section>

    <!-- ══════════════════════════════════════
         SECTION: FEEDBACK
    ══════════════════════════════════════ -->
    <section class="content-section" id="section-feedback">

        <?php if($fb_reply_msg === 'replied'): ?>
            <div class="flash success"><i class="fas fa-check-circle"></i> Your reply was sent successfully.</div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><span class="dot" style="background:var(--gold);box-shadow:0 0 8px var(--gold);"></span> Citizen Feedback & Ratings</div>
                <div style="font-size:12px;color:var(--muted);"><?php echo $feedback_count; ?> submissions</div>
            </div>

            <?php
            $feedbacks->data_seek(0);
            if($feedbacks && $feedbacks->num_rows > 0) {
                while($fb = $feedbacks->fetch_assoc()) {
                    $stars = str_repeat('★', $fb['rating']) . str_repeat('☆', 5 - $fb['rating']);
            ?>
            <div class="feedback-item">
                <div class="feedback-header">
                    <div class="feedback-user">
                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--cyan),var(--purple));display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">
                            <?php echo strtoupper(substr($fb['username'], 0, 1)); ?>
                        </div>
                        <?php echo htmlspecialchars($fb['username']); ?>
                        <?php if($fb['complaint_id']): ?>
                            <span style="font-size:11px;color:var(--cyan);background:rgba(79,172,254,0.1);padding:2px 8px;border-radius:10px;">re: #<?php echo str_pad($fb['complaint_id'],4,'0',STR_PAD_LEFT); ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                        <span class="stars"><?php echo $stars; ?></span>
                        <span style="font-size:11px;color:var(--muted);"><?php echo date('M j, Y', strtotime($fb['created_at'])); ?></span>
                    </div>
                </div>
                <div class="feedback-text"><?php echo htmlspecialchars($fb['message']); ?></div>

                <?php if(!empty($fb['admin_reply'])): ?>
                    <div class="admin-reply-bubble">
                        <div class="reply-label"><i class="fas fa-shield-alt" style="margin-right:5px;"></i>Admin Reply</div>
                        <p><?php echo htmlspecialchars($fb['admin_reply']); ?></p>
                    </div>
                <?php else: ?>
                    <form method="POST" class="reply-form" action="#section-feedback">
                        <input type="hidden" name="fb_id" value="<?php echo $fb['id']; ?>">
                        <textarea class="reply-input" name="admin_reply" placeholder="Write a reply to this citizen feedback..."></textarea>
                        <div>
                            <button type="submit" name="send_reply" class="reply-btn">
                                <i class="fas fa-reply"></i> Send Reply
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <?php } } else { echo "<p style='color:var(--muted);text-align:center;padding:40px;'>No feedback submitted yet. Citizens can submit feedback from their dashboard.</p>"; } ?>
        </div>
    </section>

</main>
</div>

<script>
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = "rgba(232,237,245,0.6)";

// Status donut
new Chart(document.getElementById("chartStatus"), {
    type: "doughnut",
    data: {
        labels: ["Pending", "In Progress", "Resolved"],
        datasets: [{
            data: [<?php echo $pending ?>, <?php echo $progress ?>, <?php echo $completed ?>],
            backgroundColor: ["rgba(250,130,49,0.85)", "rgba(79,172,254,0.85)", "rgba(67,233,123,0.85)"],
            borderColor: ["#fa8231","#4facfe","#43e97b"],
            borderWidth: 2, hoverOffset: 8
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '70%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 20, font: { size: 13 }, color: 'rgba(232,237,245,0.7)' } }
        }
    }
});

// Priority bar
new Chart(document.getElementById("chartPriority"), {
    type: "bar",
    data: {
        labels: ["Urgent", "High", "Normal"],
        datasets: [{
            label: 'Complaints',
            data: [<?php echo $urgent ?>, <?php echo $high ?>, <?php echo $normal ?>],
            backgroundColor: ["rgba(255,71,87,0.7)", "rgba(250,130,49,0.7)", "rgba(79,172,254,0.7)"],
            borderColor: ["#ff4757","#fa8231","#4facfe"],
            borderWidth: 1, borderRadius: 8
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { stepSize: 1 } },
            x: { grid: { display: false }, ticks: { font: { weight: '700' } } }
        }
    }
});

// Dept charts (loaded lazily when section shown)
let deptChartsCreated = false;
function createDeptCharts() {
    if(deptChartsCreated) return;
    deptChartsCreated = true;
    const deptNames = <?php
        $deptData->data_seek(0);
        $names = [];
        while($dd = $deptData->fetch_assoc()) $names[] = ucfirst($dd['department']);
        $deptData->data_seek(0);
        echo json_encode($names);
    ?>;
    const deptTotals = <?php
        $deptData->data_seek(0);
        $totals = [];
        while($dd = $deptData->fetch_assoc()) $totals[] = $dd['total'];
        echo json_encode($totals);
    ?>;
    new Chart(document.getElementById("chartDept"), {
        type: "bar",
        data: {
            labels: deptNames,
            datasets: [{
                label: 'Total Complaints',
                data: deptTotals,
                backgroundColor: 'rgba(79,172,254,0.6)',
                borderColor: '#4facfe',
                borderWidth: 1, borderRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { grid: { display: false } }
            }
        }
    });
    new Chart(document.getElementById("chartPriority2"), {
        type: "doughnut",
        data: {
            labels: ["Urgent", "High", "Normal"],
            datasets: [{ data: [<?php echo $urgent ?>, <?php echo $high ?>, <?php echo $normal ?>],
                backgroundColor: ["rgba(255,71,87,0.85)","rgba(250,130,49,0.85)","rgba(67,233,123,0.85)"],
                borderWidth: 2, borderColor: ["#ff4757","#fa8231","#43e97b"], hoverOffset: 6 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '60%',
            plugins: { legend: { position: 'bottom', labels: { padding: 15, font: { size: 12 } } } } }
    });
}

// Navigation
const sectionMeta = {
    overview:      { title: 'Dashboard Overview',          sub: 'Real-time city-wide performance command center' },
    complaints:    { title: 'All Citizen Complaints',       sub: 'Full directory of every registered civic issue' },
    departments:   { title: 'Department Statistics',        sub: 'Resolution performance by municipal department' },
    notifications: { title: 'Send Notifications',           sub: 'Broadcast messages to citizens by department' },
    feedback:      { title: 'Citizen Feedback',             sub: 'Ratings, reviews and responses from the public' },
};

function showSection(name, el) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    // Show target
    document.getElementById('section-' + name).classList.add('active');
    if(el) el.classList.add('active');
    // Update header
    const meta = sectionMeta[name];
    if(meta) {
        document.getElementById('sectionTitle').textContent = meta.title;
        document.getElementById('sectionSub').textContent   = meta.sub;
    }
    // Lazy load dept charts
    if(name === 'departments') createDeptCharts();
    // scroll top
    document.querySelector('.main').scrollTo(0,0);
}

// Live clock
function updateClock() {
    const now  = new Date();
    const time = now.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    const el   = document.getElementById('liveClock');
    if(el) el.textContent = time;
}
setInterval(updateClock, 1000);
updateClock();

// Auto-jump to notification section if form was submitted
<?php if($notif_msg): ?>
showSection('notifications', document.querySelector('[onclick*=notifications]'));
<?php endif; ?>
<?php if($fb_reply_msg): ?>
showSection('feedback', document.querySelector('[onclick*=feedback]'));
<?php endif; ?>
</script>
</body>
</html>