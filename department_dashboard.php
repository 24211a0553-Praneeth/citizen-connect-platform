<?php  
session_start();  
$conn = new mysqli("localhost", "root", "", "citizen_connect");  
  
if(!isset($_SESSION['department'])){  
    header("Location: department_login.php");  
    exit();  
}  
  
$dept = $_SESSION['department'];  

$total     = $conn->query("SELECT COUNT(*) as c FROM complaints WHERE department='$dept'")->fetch_assoc()['c'];  
$pending   = $conn->query("SELECT COUNT(*) as c FROM complaints WHERE department='$dept' AND status='Pending'")->fetch_assoc()['c'];  
$completed = $conn->query("SELECT COUNT(*) as c FROM complaints WHERE department='$dept' AND status='Completed'")->fetch_assoc()['c'];  
$progress  = $conn->query("SELECT COUNT(*) as c FROM complaints WHERE department='$dept' AND status='In Progress'")->fetch_assoc()['c'];  
$remaining = $total - $completed;

// Handle notification send
$notif_msg = "";
if(isset($_POST['send_dept_notification'])) {
    $n_title   = $conn->real_escape_string(trim($_POST['notif_title']));
    $n_body    = $conn->real_escape_string(trim($_POST['notif_body']));
    $sent_by   = $conn->real_escape_string($dept);
    if($n_title && $n_body) {
        $stmt = $conn->prepare("INSERT INTO notifications (title, message, department, sent_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $n_title, $n_body, $dept, $sent_by);
        $notif_msg = $stmt->execute() ? "success" : "error";
    } else {
        $notif_msg = "empty";
    }
}

// Fetch this department's sent notifications
$dept_notifs = $conn->query("SELECT * FROM notifications WHERE department='$dept' ORDER BY created_at DESC LIMIT 10");
$notif_count = $dept_notifs ? $dept_notifs->num_rows : 0;
?>  

<!DOCTYPE html>
<html lang="en">
<head>
<title>Department Dashboard – <?php echo ucfirst($dept); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Citizen Connect Department Dashboard – manage and track assigned complaints.">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ─── Base ─────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    background:
        radial-gradient(circle at 15% 50%, rgba(2,27,43,1), transparent 50%),
        radial-gradient(circle at 85% 30%, rgba(13,62,89,1), transparent 50%),
        radial-gradient(circle at 50% 80%, rgba(6,40,61,1), transparent 50%),
        linear-gradient(135deg, #0f2027, #203a43, #2c5364);
    background-color: #0d1b2a;
    background-attachment: fixed;
    color: #fff;
    position: relative;
}

body::before {
    content: '';
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background-image:
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    z-index: -2; pointer-events: none;
}

body::after {
    content: '';
    position: fixed; top: -50%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(0,242,254,0.05) 0%, transparent 60%);
    animation: rotateBg 30s linear infinite;
    z-index: -1; pointer-events: none;
}

@keyframes rotateBg { 100% { transform: rotate(360deg); } }
html { scroll-behavior: smooth; }

/* ─── Top Bar ──────────────────────────────────────── */
.top-bar {
    display: flex; justify-content: space-between; align-items: center;
    padding: 15px 40px;
    background: rgba(15,32,39,0.85);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    box-shadow: 0 4px 30px rgba(0,0,0,0.5);
    position: sticky; top: 0; z-index: 1000;
}

.logo { font-weight: 700; font-size: 22px; color: #00f2fe; letter-spacing: 1px; }
.logo i { margin-right: 10px; }

.dept-badge {
    background: rgba(0,242,254,0.12);
    border: 1px solid rgba(0,242,254,0.3);
    color: #00f2fe;
    padding: 6px 18px; border-radius: 20px;
    font-size: 13px; font-weight: 600; letter-spacing: 1px;
}

.logout-btn {
    background: linear-gradient(45deg, #ff416c, #ff4b2b);
    padding: 10px 22px; border-radius: 25px;
    color: #fff; text-decoration: none; font-weight: 500;
    transition: .3s; box-shadow: 0 4px 15px rgba(255,75,43,0.4);
    font-size: 14px;
}
.logout-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255,75,43,0.6); }

/* ─── Dashboard Wrapper ────────────────────────────── */
.dashboard { width: 92%; max-width: 1280px; margin: 36px auto 60px; }

.page-header { margin-bottom: 30px; }
.page-header h2 { font-size: 30px; font-weight: 700; }
.page-header p  { font-size: 14px; opacity: .65; margin-top: 4px; }

/* ─── Stat Cards ───────────────────────────────────── */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 22px; margin-bottom: 32px;
}

.card {
    background: rgba(20,40,50,0.6);
    padding: 24px; border-radius: 20px;
    display: flex; align-items: center; gap: 18px;
    border: 1px solid rgba(255,255,255,0.05);
    backdrop-filter: blur(15px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    transition: .35s;
    cursor: pointer;
}
.card:hover, .card.active {
    transform: translateY(-8px);
    border-color: rgba(0,242,254,0.35);
    box-shadow: 0 15px 40px rgba(0,242,254,0.2);
    background: rgba(0,242,254,0.08);
}

.icon {
    width: 58px; height: 58px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 14px; font-size: 24px;
}
.blue   { background: rgba(0,198,255,0.18);  color: #00c6ff; }
.orange { background: rgba(255,152,0,0.18);  color: #ff9800; }
.yellow { background: rgba(251,192,45,0.18); color: #fbc02d; }
.green  { background: rgba(76,175,80,0.18);  color: #4caf50; }
.purple { background: rgba(168,85,247,0.18); color: #a855f7; }

.card-info h3 { font-size: 26px; font-weight: 700; }
.card-info p  { font-size: 12px; opacity: .75; text-transform: uppercase; letter-spacing: 1px; margin-top: 3px; }

/* ─── Toolbar (Search + Filters) ──────────────────── */
.toolbar {
    display: flex; gap: 12px; margin-bottom: 22px;
    flex-wrap: wrap; align-items: center;
}

.search-wrap {
    position: relative; flex: 1; min-width: 200px; max-width: 340px;
}
.search-wrap i {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: rgba(255,255,255,0.4); font-size: 14px; pointer-events: none;
}
#searchInput {
    width: 100%; padding: 11px 14px 11px 40px;
    border-radius: 12px; border: 1px solid rgba(255,255,255,0.12);
    background: rgba(0,0,0,0.25); color: #fff;
    font-size: 14px; font-family: inherit; outline: none; transition: .3s;
}
#searchInput::placeholder { color: rgba(255,255,255,0.4); }
#searchInput:focus { border-color: #00f2fe; box-shadow: 0 0 14px rgba(0,242,254,0.2); }

#statusFilter {
    padding: 11px 15px; border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.12);
    background: rgba(0,0,0,0.25); color: #fff;
    font-size: 14px; font-family: inherit; outline: none; transition: .3s;
    cursor: pointer; appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23aaa'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center; background-size: 20px;
    padding-right: 36px;
}
#statusFilter option { background: #0f2027; }
#statusFilter:focus { border-color: #00f2fe; box-shadow: 0 0 14px rgba(0,242,254,0.2); }

/* Priority filter buttons */
.priority-filters { display: flex; gap: 8px; flex-wrap: wrap; }

.pf-btn {
    padding: 8px 16px; border-radius: 20px;
    border: 1.5px solid rgba(255,255,255,0.15);
    background: rgba(0,0,0,0.2); color: rgba(255,255,255,0.65);
    font-size: 12px; font-weight: 600; font-family: inherit;
    cursor: pointer; transition: .25s; letter-spacing: .5px;
}
.pf-btn:hover { background: rgba(255,255,255,0.08); color: #fff; }

.pf-btn[data-priority="All"].active    { background: rgba(0,242,254,0.2); border-color: #00f2fe; color: #00f2fe; }
.pf-btn[data-priority="Urgent"].active { background: rgba(255,77,77,0.2); border-color: #ff4d4d; color: #ff4d4d; }
.pf-btn[data-priority="High"].active   { background: rgba(255,165,2,0.2); border-color: #ffa502; color: #ffa502; }
.pf-btn[data-priority="Normal"].active { background: rgba(76,175,80,0.2); border-color: #4caf50; color: #4caf50; }
.pf-btn[data-priority="Low"].active    { background: rgba(100,100,255,0.2); border-color: #6464ff; color: #9090ff; }

/* ─── Table Box ────────────────────────────────────── */
.table-box {
    background: rgba(20,40,50,0.6);
    padding: 28px 30px; border-radius: 22px;
    border: 1px solid rgba(255,255,255,0.06);
    backdrop-filter: blur(15px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    transition: .3s;
}
.table-box:hover { box-shadow: 0 15px 40px rgba(0,0,0,0.4); border-color: rgba(255,255,255,0.1); }

.table-title {
    font-size: 18px; font-weight: 600; margin-bottom: 20px;
    padding-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex; align-items: center; justify-content: space-between;
}
.table-title i { margin-right: 10px; color: #00f2fe; }

#resultCount {
    font-size: 12px; font-weight: 500;
    background: rgba(0,242,254,0.12); color: #00f2fe;
    padding: 4px 14px; border-radius: 14px; border: 1px solid rgba(0,242,254,0.2);
}

table { width: 100%; border-collapse: collapse; }

th, td { padding: 14px 16px; text-align: left; font-size: 13.5px; }

th {
    color: #00f2fe; font-weight: 500;
    text-transform: uppercase; font-size: 12px; letter-spacing: 1px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

tr { border-bottom: 1px solid rgba(255,255,255,0.04); transition: .2s; }
tr:last-child { border-bottom: none; }
tr:hover td { background: rgba(255,255,255,0.04); }

/* Status badge */
.badge-status {
    padding: 5px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 600; display: inline-block; letter-spacing: .5px;
}
.badge-pending    { background: rgba(255,152,0,0.18); color: #ff9800; border: 1px solid rgba(255,152,0,0.3); }
.badge-inprogress { background: rgba(33,150,243,0.18); color: #42a5f5; border: 1px solid rgba(33,150,243,0.3); }
.badge-completed  { background: rgba(76,175,80,0.18); color: #66bb6a; border: 1px solid rgba(76,175,80,0.3); }

/* Priority badge */
.badge-priority {
    padding: 4px 10px; border-radius: 12px;
    font-size: 11px; font-weight: 700; display: inline-block;
}

/* Update button */
.btn-update {
    padding: 7px 16px; border-radius: 20px;
    background: rgba(0,242,254,0.15); color: #00f2fe;
    text-decoration: none; font-size: 12px; font-weight: 600;
    border: 1px solid rgba(0,242,254,0.3); transition: .25s;
    white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;
}
.btn-update:hover {
    background: rgba(0,242,254,0.3); transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0,242,254,0.25);
}

/* ─── Notification Button ─── */
.notif-btn {
    padding: 9px 20px; border-radius: 25px;
    background: rgba(168,85,247,0.12); color: #a855f7;
    border: 1px solid rgba(168,85,247,0.3);
    font-size: 13px; font-weight: 600; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    transition: .25s; font-family: inherit;
    position: relative;
}
.notif-btn:hover { background: rgba(168,85,247,0.25); transform: translateY(-2px); }
.notif-badge {
    background: #ff4757; color: #fff;
    font-size: 10px; font-weight: 800;
    width: 18px; height: 18px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    margin-left: 2px;
}

/* ─── Notification Panel ─── */
.notif-panel {
    background: rgba(20,30,50,0.7); backdrop-filter: blur(20px);
    border: 1px solid rgba(168,85,247,0.2); border-radius: 20px;
    padding: 28px 30px; margin-bottom: 30px;
    animation: slideDown .35s ease;
}
@keyframes slideDown { from { opacity:0; transform:translateY(-15px); } to { opacity:1; transform:translateY(0); } }

.notif-panel-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 22px; padding-bottom: 16px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.notif-panel-title { font-size: 17px; font-weight: 700; color: #a855f7;
    display: flex; align-items: center; gap: 10px; }

.notif-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 14px; }
.notif-form-row.single { grid-template-columns: 1fr; }
.nf-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
    color: rgba(255,255,255,0.45); margin-bottom: 7px; display: block; }
.nf-input, .nf-textarea {
    width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 11px; padding: 11px 15px; color: #fff;
    font-size: 14px; font-family: inherit; outline: none; transition: .25s;
}
.nf-input::placeholder, .nf-textarea::placeholder { color: rgba(255,255,255,0.3); }
.nf-input:focus, .nf-textarea:focus {
    border-color: rgba(168,85,247,0.5);
    box-shadow: 0 0 0 3px rgba(168,85,247,0.1);
}
.nf-textarea { min-height: 90px; resize: vertical; }

.nf-send-btn {
    background: linear-gradient(135deg, #a855f7, #7c3aed);
    border: none; border-radius: 11px; padding: 12px 28px;
    color: #fff; font-size: 14px; font-weight: 700;
    cursor: pointer; transition: .3s; font-family: inherit;
    display: inline-flex; align-items: center; gap: 8px;
}
.nf-send-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(168,85,247,0.4); }

.notif-flash {
    padding: 12px 16px; border-radius: 10px; margin-bottom: 16px;
    font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px;
}
.notif-flash.success { background: rgba(67,233,123,0.12); border: 1px solid rgba(67,233,123,0.3); color: #43e97b; }
.notif-flash.error   { background: rgba(255,71,87,0.12);  border: 1px solid rgba(255,71,87,0.3);  color: #ff4757; }

/* Sent notifications history */
.sent-notif-item {
    padding: 13px 16px; border-radius: 12px;
    background: rgba(168,85,247,0.06); border: 1px solid rgba(168,85,247,0.15);
    margin-top: 12px;
}
.sent-notif-item .sn-title { font-size: 13px; font-weight: 600; margin-bottom: 4px; }
.sent-notif-item .sn-body  { font-size: 12px; color: rgba(255,255,255,0.55); line-height: 1.4; }
.sent-notif-item .sn-time  { font-size: 11px; color: rgba(168,85,247,0.7); margin-top: 5px; }

/* Empty state */
.empty-state {
    text-align: center; padding: 50px 20px;
    color: rgba(255,255,255,0.35); font-size: 15px;
}
.empty-state i { font-size: 40px; margin-bottom: 14px; display: block; }

/* No results row */
#noResults { display: none; }
#noResults td { text-align: center; padding: 40px; color: rgba(255,255,255,0.4); }
</style>
</head>
<body>

<!-- ─── Top Bar ─────────────────────────────────────── -->
<div class="top-bar">
    <div class="logo"><i class="fas fa-building"></i>Citizen Connect</div>
    <div class="dept-badge"><i class="fas fa-shield-alt" style="margin-right:6px;"></i><?php echo strtoupper($dept); ?> DEPT</div>
    <div style="display:flex;gap:12px;align-items:center;">
        <button class="notif-btn" onclick="toggleNotifPanel()" id="notifToggleBtn">
            <i class="fas fa-bell"></i> Notify Citizens
            <?php if($notif_count > 0): ?><span class="notif-badge"><?php echo $notif_count; ?></span><?php endif; ?>
        </button>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="dashboard">

    <!-- Page Header -->
    <div class="page-header">
        <h2><?php echo ucfirst($dept); ?> Department</h2>
        <p><i class="fas fa-calendar-alt" style="margin-right:6px; color:#00f2fe;"></i>Manage and resolve assigned citizen complaints</p>
    </div>

    <!-- ─── Stat Cards ─────────────────────────────────── -->
    <div class="cards">
        <div class="card" onclick="setCardFilter('')" id="card-all">
            <div class="icon blue"><i class="fa fa-file-alt"></i></div>
            <div class="card-info">
                <h3><?php echo $total; ?></h3>
                <p>Total Complaints</p>
            </div>
        </div>
        <div class="card" onclick="setCardFilter('Remaining')" id="card-remaining">
            <div class="icon purple"><i class="fa fa-hourglass-half"></i></div>
            <div class="card-info">
                <h3><?php echo $remaining; ?></h3>
                <p>Remaining</p>
            </div>
        </div>
        <div class="card" onclick="setCardFilter('Pending')" id="card-pending">
            <div class="icon orange"><i class="fa fa-clock"></i></div>
            <div class="card-info">
                <h3><?php echo $pending; ?></h3>
                <p>Pending</p>
            </div>
        </div>
        <div class="card" onclick="setCardFilter('In Progress')" id="card-inprogress">
            <div class="icon yellow"><i class="fa fa-spinner fa-spin"></i></div>
            <div class="card-info">
                <h3><?php echo $progress; ?></h3>
                <p>In Progress</p>
            </div>
        </div>
        <div class="card" onclick="setCardFilter('Completed')" id="card-resolved">
            <div class="icon green"><i class="fa fa-check-circle"></i></div>
            <div class="card-info">
                <h3><?php echo $completed; ?></h3>
                <p>Resolved</p>
            </div>
        </div>
    </div>

    <!-- ─── Notification Panel (toggled) ─────────────────── -->
    <div class="notif-panel" id="notifPanel" style="display:none;">
        <div class="notif-panel-header">
            <div class="notif-panel-title"><i class="fas fa-satellite-dish"></i> Broadcast to Citizens — <?php echo ucfirst($dept); ?> Department</div>
            <button onclick="toggleNotifPanel()" style="background:none;border:none;color:rgba(255,255,255,0.4);font-size:18px;cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>

        <?php if($notif_msg === 'success'): ?>
            <div class="notif-flash success"><i class="fas fa-check-circle"></i> Notification sent! All citizens linked to the <?php echo ucfirst($dept); ?> department will see it.</div>
        <?php elseif($notif_msg === 'error'): ?>
            <div class="notif-flash error"><i class="fas fa-exclamation-circle"></i> Failed to send. Please try again.</div>
        <?php elseif($notif_msg === 'empty'): ?>
            <div class="notif-flash error"><i class="fas fa-exclamation-circle"></i> Please fill in both the title and message fields.</div>
        <?php endif; ?>

        <form method="POST">
            <div class="notif-form-row">
                <div>
                    <label class="nf-label"><i class="fas fa-heading" style="margin-right:4px;"></i>Notification Title</label>
                    <input type="text" name="notif_title" class="nf-input" placeholder="e.g. Scheduled maintenance update..." required>
                </div>
                <div>
                    <label class="nf-label"><i class="fas fa-tag" style="margin-right:4px;"></i>Sent From</label>
                    <input type="text" class="nf-input" value="<?php echo ucfirst($dept); ?> Department" disabled style="opacity:0.6;">
                </div>
            </div>
            <div class="notif-form-row single">
                <div>
                    <label class="nf-label"><i class="fas fa-comment" style="margin-right:4px;"></i>Message Body</label>
                    <textarea name="notif_body" class="nf-textarea" placeholder="Write your message to citizens with complaints under this department..." required></textarea>
                </div>
            </div>
            <button type="submit" name="send_dept_notification" class="nf-send-btn">
                <i class="fas fa-paper-plane"></i> Send Notification
            </button>
        </form>

        <?php if($notif_count > 0): ?>
        <div style="margin-top:22px; padding-top:18px; border-top:1px solid rgba(255,255,255,0.06);">
            <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:rgba(255,255,255,0.4); margin-bottom:10px;">Recent Broadcasts</div>
            <?php $dept_notifs->data_seek(0); while($n = $dept_notifs->fetch_assoc()): ?>
            <div class="sent-notif-item">
                <div class="sn-title"><i class="fas fa-bell" style="color:#a855f7;margin-right:6px;"></i><?php echo htmlspecialchars($n['title']); ?></div>
                <div class="sn-body"><?php echo htmlspecialchars($n['message']); ?></div>
                <div class="sn-time"><i class="fas fa-clock" style="margin-right:4px;"></i><?php echo date('M j, Y · g:i a', strtotime($n['created_at'])); ?></div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ─── Toolbar ────────────────────────────────────── -->
    <div class="toolbar">
        <!-- Search by Complaint ID -->
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by Complaint ID, title…" oninput="applyFilters()">
        </div>

        <!-- Filter by Status -->
        <select id="statusFilter" onchange="applyFilters()">
            <option value="">All Statuses</option>
            <option value="Remaining">Remaining</option>
            <option value="Pending">Pending</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
        </select>

        <!-- Priority Filter Buttons -->
        <div class="priority-filters" id="priorityFilters">
            <button class="pf-btn active" data-priority="All"    onclick="setPriority(this)">All</button>
            <button class="pf-btn"        data-priority="Urgent"  onclick="setPriority(this)">🔴 Urgent</button>
            <button class="pf-btn"        data-priority="High"    onclick="setPriority(this)">🟠 High</button>
            <button class="pf-btn"        data-priority="Normal"  onclick="setPriority(this)">🟢 Normal</button>
            <button class="pf-btn"        data-priority="Low"     onclick="setPriority(this)">🔵 Low</button>
        </div>
    </div>

    <!-- ─── Complaints Table ────────────────────────────── -->
    <div class="table-box">
        <div class="table-title">
            <span><i class="fas fa-list-ul"></i>Assigned Complaints Tracker</span>
            <span id="resultCount">Loading…</span>
        </div>

        <div style="overflow-x:auto;">
            <table id="complaintsTable">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Location</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
<?php
$result = $conn->query("SELECT * FROM complaints WHERE department='$dept' ORDER BY id DESC");

if ($result && $result->num_rows > 0):
    while($row = $result->fetch_assoc()):
        $ticketId   = str_pad($row['id'], 4, '0', STR_PAD_LEFT);
        $priority   = $row['priority'] ?? 'Normal';
        $statusRaw  = $row['status'];
        $statusClass = 'badge-' . strtolower(str_replace(' ', '', $statusRaw));

        // Priority colours
        $pColor = '#aaa'; $pBg = 'rgba(255,255,255,0.08)';
        if($priority == 'Urgent')     { $pColor='#ff4d4d'; $pBg='rgba(255,77,77,0.15)'; }
        elseif($priority == 'High')   { $pColor='#ffa502'; $pBg='rgba(255,165,2,0.15)'; }
        elseif($priority == 'Normal') { $pColor='#4caf50'; $pBg='rgba(76,175,80,0.15)'; }
        elseif($priority == 'Low')    { $pColor='#9090ff'; $pBg='rgba(100,100,255,0.15)'; }
?>
                    <tr class="complaint-row"
                        data-id="<?php echo $row['id']; ?>"
                        data-title="<?php echo htmlspecialchars(strtolower($row['title'])); ?>"
                        data-location="<?php echo htmlspecialchars(strtolower($row['location'])); ?>"
                        data-status="<?php echo htmlspecialchars($statusRaw); ?>"
                        data-priority="<?php echo htmlspecialchars($priority); ?>">

                        <td><b style="color:#00f2fe;">#<?php echo $ticketId; ?></b></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><i class="fas fa-map-marker-alt" style="color:#ffcc80; margin-right:5px;"></i><?php echo htmlspecialchars($row['location']); ?></td>
                        <td>
                            <span class="badge-priority"
                                  style="color:<?php echo $pColor; ?>; background:<?php echo $pBg; ?>; border:1px solid <?php echo $pColor; ?>;">
                                <?php echo htmlspecialchars($priority); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-status <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($statusRaw); ?>
                            </span>
                        </td>
                        <td>
                            <a href="update_status.php?id=<?php echo $row['id']; ?>" class="btn-update">
                                Update <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
<?php
    endwhile;
else:
?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                No complaints assigned to this department yet.
                            </div>
                        </td>
                    </tr>
<?php endif; ?>

                    <!-- No-match row shown by JS -->
                    <tr id="noResults">
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                No complaints match your current filters.
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.dashboard -->

<script>
let activePriority = 'All';

/* ── Priority button click ── */
function setPriority(btn) {
    document.querySelectorAll('.pf-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activePriority = btn.dataset.priority;
    applyFilters();
}

/* ── Main filter engine ── */
function applyFilters() {
    const searchRaw    = document.getElementById('searchInput').value.trim().toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const rows         = document.querySelectorAll('.complaint-row');

    let visible = 0;

    rows.forEach(row => {
        const id       = row.dataset.id;          // numeric ID
        const title    = row.dataset.title;
        const location = row.dataset.location;
        const status   = row.dataset.status;
        const priority = row.dataset.priority;

        // Search: match padded ID (e.g. "0004"), plain number ("4"), title, or location
        const paddedId = id.padStart(4, '0');
        const matchSearch = !searchRaw ||
            paddedId.includes(searchRaw) ||
            id.includes(searchRaw) ||
            title.includes(searchRaw) ||
            location.includes(searchRaw);

        const matchStatus = !statusFilter || 
                           (statusFilter === 'Remaining' ? status !== 'Completed' : status === statusFilter);
        const matchPriority = activePriority === 'All' || priority === activePriority;

        if (matchSearch && matchStatus && matchPriority) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    // Highlight active card
    document.querySelectorAll('.card').forEach(c => c.classList.remove('active'));
    if (statusFilter === '') document.getElementById('card-all').classList.add('active');
    else if (statusFilter === 'Remaining') document.getElementById('card-remaining').classList.add('active');
    else if (statusFilter === 'Pending') document.getElementById('card-pending').classList.add('active');
    else if (statusFilter === 'In Progress') document.getElementById('card-inprogress').classList.add('active');
    else if (statusFilter === 'Completed') document.getElementById('card-resolved').classList.add('active');

    // Toggle no-results message
    document.getElementById('noResults').style.display = visible === 0 ? '' : 'none';

    // Update counter badge
    const total = rows.length;
    document.getElementById('resultCount').textContent =
        visible === total ? `${total} tickets` : `${visible} / ${total} tickets`;
}

function setCardFilter(val) {
    document.getElementById('statusFilter').value = val;
    applyFilters();
}

// Run on page load to set initial count
window.addEventListener('DOMContentLoaded', applyFilters);

// Toggle notification panel
function toggleNotifPanel() {
    const panel = document.getElementById('notifPanel');
    const isHidden = panel.style.display === 'none';
    panel.style.display = isHidden ? 'block' : 'none';
    if(isHidden) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Auto-open panel if a notification was just sent
<?php if($notif_msg): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('notifPanel').style.display = 'block';
});
<?php endif; ?>
</script>

</body>
</html>