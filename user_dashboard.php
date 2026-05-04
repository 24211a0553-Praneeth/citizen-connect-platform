<?php
session_start();
$conn = new mysqli("localhost", "root", "", "citizen_connect");

if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];

// Fetch user complaints with filtering
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filter_sql = "";
if($filter == 'pending') {
    $filter_sql = " AND (status='Pending' OR status='In Progress')";
} elseif($filter == 'resolved') {
    $filter_sql = " AND status='Completed'";
}
$sql = "SELECT * FROM complaints WHERE username='$user' $filter_sql ORDER BY id DESC";
$result = $conn->query($sql);

// Stats
$total = 0; $resolved = 0; $pending = 0;
$stats = $conn->query("SELECT status, count(*) as count FROM complaints WHERE username='$user' GROUP BY status");
if($stats) {
    while($row = $stats->fetch_assoc()) {
        $total += $row['count'];
        if($row['status'] == 'Completed') $resolved += $row['count'];
        else $pending += $row['count'];
    }
}

// Fetch notifications for this user (all + department-specific)
// Get departments the user's complaints belong to
$user_depts_res = $conn->query("SELECT DISTINCT department FROM complaints WHERE username='$user'");
$user_depts = [];
if($user_depts_res) {
    while($drow = $user_depts_res->fetch_assoc()) {
        $user_depts[] = $conn->real_escape_string($drow['department']);
    }
}

// Build notification query: all system-wide + relevant department + personal notifications
$dept_conditions = "'all'";
foreach($user_depts as $d) {
    $dept_conditions .= ", '$d'";
}
$notifications = $conn->query("SELECT * FROM notifications WHERE department IN ($dept_conditions) OR target_user='$user' ORDER BY created_at DESC LIMIT 10");
$notif_count = $notifications ? $notifications->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Dashboard | Citizen Connect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
/* Cinematic Background - Synced directly back to Index platform theme */
body {
    margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif;
    min-height: 100vh;
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

/* Sticky Nav */
.top-bar {
    display: flex; justify-content: space-between; align-items: center;
    padding: 15px 40px; background: rgba(15, 32, 39, 0.8); backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
    position: sticky; top: 0; z-index: 1000;
}
.logo { font-weight: 700; font-size: 24px; color: #00f2fe; display: flex; align-items: center; gap: 8px; }
.nav-actions { display: flex; gap: 12px; align-items: center; }

.btn-primary {
    background: linear-gradient(45deg, #00f2fe, #4facfe); padding: 10px 24px; border-radius: 25px;
    color: white; text-decoration: none; font-weight: 600; transition: 0.3s;
    box-shadow: 0 4px 15px rgba(0, 242, 254, 0.4); border: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 242, 254, 0.6); }

.nav-btn {
    background: rgba(255,255,255,0.08); padding: 10px 18px; border-radius: 25px;
    color: white; text-decoration: none; font-weight: 500; transition: 0.3s;
    border: 1px solid rgba(255,255,255,0.15); display: inline-flex; align-items: center; gap: 6px; font-size: 14px;
}
.nav-btn:hover { background: rgba(255,255,255,0.15); transform: translateY(-2px); }
.nav-btn.feedback-btn { border-color: rgba(255,215,0,0.3); color: #ffd700; }
.nav-btn.feedback-btn:hover { background: rgba(255,215,0,0.1); }
.logout-btn {
    background: rgba(255,255,255,0.08); padding: 10px 24px; border-radius: 25px;
    color: white; text-decoration: none; font-weight: 500; transition: 0.3s;
    border: 1px solid rgba(255,255,255,0.2); display: inline-flex; align-items: center; gap: 6px;
}
.logout-btn:hover { background: rgba(255,65,108,0.2); color: #ff416c; border-color: #ff416c; transform: translateY(-2px); }

/* Notification Bell */
.notif-wrapper { position: relative; }
.notif-bell {
    background: rgba(168,85,247,0.12); padding: 10px 18px; border-radius: 25px;
    color: #a855f7; text-decoration: none; font-weight: 500; transition: 0.3s;
    border: 1px solid rgba(168,85,247,0.3); display: inline-flex; align-items: center; gap: 6px;
    cursor: pointer; font-size: 14px;
}
.notif-bell:hover { background: rgba(168,85,247,0.25); }
.notif-dot {
    position: absolute; top: -3px; right: -3px;
    width: 18px; height: 18px; border-radius: 50%;
    background: #ff4757; color: white; font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid rgba(15,32,39,0.9);
}

/* Notification Dropdown */
.notif-dropdown {
    display: none; position: absolute; top: calc(100% + 12px); right: 0;
    width: 360px; background: rgba(10,17,30,0.97); backdrop-filter: blur(20px);
    border: 1px solid rgba(168,85,247,0.25); border-radius: 16px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.7); z-index: 999; overflow: hidden;
}
.notif-dropdown.show { display: block; animation: dropDown 0.3s ease; }
@keyframes dropDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.notif-dropdown-header {
    padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.06);
    font-size: 13px; font-weight: 700; color: #a855f7;
    display: flex; align-items: center; gap: 8px;
}
.notif-list { max-height: 320px; overflow-y: auto; }
.notif-list::-webkit-scrollbar { width: 4px; }
.notif-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }
.notif-list-item {
    padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.04); transition: 0.2s;
    cursor: pointer;
}
.notif-list-item:hover { background: rgba(168,85,247,0.07); }
.notif-list-item:last-child { border-bottom: none; }
.notif-list-item .n-title { font-size: 13px; font-weight: 600; color: white; margin-bottom: 4px; }
.notif-list-item .n-body  { font-size: 12px; color: rgba(255,255,255,0.55); line-height: 1.4; }
.notif-list-item .n-dept  { font-size: 10px; color: #a855f7; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
.notif-list-item .n-time  { font-size: 11px; color: rgba(255,255,255,0.3); margin-top: 4px; }
.notif-empty { padding: 30px; text-align: center; color: rgba(255,255,255,0.35); font-size: 13px; }

/* 📱 MOBILE ALERT MOCKUP STYLING */
.mobile-alert-float {
    position: fixed; bottom: 20px; right: 20px;
    background: rgba(168,85,247,0.9); backdrop-filter: blur(10px);
    padding: 15px 25px; border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.2);
    display: flex; align-items: center; gap: 12px;
    z-index: 9999; animation: slideInRight 0.5s ease;
}
@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

.container { width: 95%; max-width: 1300px; margin: 40px auto; position: relative; z-index: 2; }

/* Dashboard Header */
.header { margin: 25px 0 35px 0; display: flex; justify-content: space-between; align-items: flex-end; animation: fadeUp 0.8s ease; }
.header-text h2 { margin: 0 0 5px 0; font-size: 34px; font-weight: 700; }
.header-text p { margin: 0; opacity: 0.7; font-size: 16px; }

@keyframes fadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; animation: fadeUp 1s ease; }
.stat-card {
    background: rgba(20, 30, 40, 0.6); backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.05); padding: 25px; border-radius: 16px;
    display: flex; align-items: center; gap: 20px; transition: 0.3s;
    text-decoration: none; color: inherit; cursor: pointer;
}
.stat-card:hover { transform: translateY(-5px); border-color: rgba(0, 242, 254, 0.3); background: rgba(255,255,255,0.05); }
.stat-card.active { border-color: #00f2fe; background: rgba(0, 242, 254, 0.1); }
.stat-card i { font-size: 45px; color: #00f2fe; }
.stat-info h3 { font-size: 34px; margin: 0; font-weight: 800; }
.stat-info p { margin: 0; font-size: 14px; opacity: 0.7; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }

/* Ticket Grid */
.ticket-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 30px; animation: fadeUp 1.2s ease; }
.ticket-card {
    background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 16px; overflow: hidden;
    transition: 0.4s; position: relative; display: flex; flex-direction: column;
}
.ticket-card:hover { transform: translateY(-8px); border-color: rgba(0, 242, 254, 0.4); box-shadow: 0 15px 40px rgba(0,0,0,0.6); }

.ticket-media { width: 100%; height: 200px; background: rgba(0,0,0,0.6); display: flex; justify-content: center; align-items: center; overflow: hidden; position: relative; }
.ticket-media img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
.ticket-card:hover .ticket-media img { transform: scale(1.05); }
.no-img { color: rgba(255,255,255,0.3); font-size: 14px; font-weight: 500; }

.ticket-location {
    padding: 12px 20px; background: rgba(0, 0, 0, 0.5); border-bottom: 1px solid rgba(255,255,255,0.05);
    font-size: 13px; color: #ffcc80; display: flex; align-items: center; gap: 8px; font-weight: 500;
}
.ticket-body { padding: 25px 20px; flex-grow: 1; }
.ticket-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
.ticket-id { font-size: 14px; color: rgba(255,255,255,0.5); font-weight: 600; }
.ticket-title { font-size: 20px; font-weight: 600; margin: 5px 0 10px 0; color: white; line-height: 1.3;}
.ticket-desc { font-size: 14px; color: rgba(255,255,255,0.6); line-height: 1.6; margin-bottom: 15px; }
.ticket-footer { padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.2); }
.badge { padding: 6px 12px; border-radius: 10px; font-weight: 600; font-size: 12px; text-transform: uppercase; border: 1px solid transparent; letter-spacing: 0.5px;}
.badge-dept { background: rgba(255,255,255,0.1); color: white; font-size: 11px; }
.status-Pending    { background: rgba(255, 152, 0, 0.15); color: #ff9800; border-color: rgba(255, 152, 0, 0.3); }
.status-InProgress { background: rgba(33, 150, 243, 0.15); color: #2196f3; border-color: rgba(33, 150, 243, 0.3); }
.status-Completed  { background: rgba(76, 175, 80, 0.15); color: #4caf50; border-color: rgba(76, 175, 80, 0.3); }

/* Feedback CTA Strip */
.feedback-strip {
    background: linear-gradient(135deg, rgba(255,215,0,0.08), rgba(168,85,247,0.08));
    border: 1px solid rgba(255,215,0,0.2); border-radius: 16px;
    padding: 24px 30px; margin-bottom: 35px;
    display: flex; justify-content: space-between; align-items: center;
    animation: fadeUp 0.9s ease;
}
.feedback-strip .strip-text h3 { font-size: 18px; font-weight: 700; margin: 0 0 5px; color: #ffd700; }
.feedback-strip .strip-text p  { font-size: 14px; color: rgba(255,255,255,0.6); margin: 0; }
.feedback-strip-btn {
    background: linear-gradient(135deg, #ffd700, #ffaa00); color: #0b131a;
    padding: 12px 26px; border-radius: 25px; text-decoration: none;
    font-weight: 700; font-size: 14px; white-space: nowrap;
    display: flex; align-items: center; gap: 8px; transition: 0.3s;
    box-shadow: 0 4px 15px rgba(255,215,0,0.3);
}
.feedback-strip-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(255,215,0,0.5); }

.empty-state { text-align: center; padding: 80px 20px; background: rgba(0,0,0,0.3); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.2); animation: fadeUp 1s ease; }
.empty-state i { font-size: 70px; color: rgba(255,255,255,0.1); margin-bottom: 20px; }
.empty-state h3 { margin: 0 0 10px 0; font-size: 24px; color: white; }
.empty-state p { margin: 0 0 30px 0; color: rgba(255,255,255,0.6); font-size: 15px;}

@media(max-width: 768px) { .stats-row { grid-template-columns: 1fr; } .header { flex-direction: column; align-items: flex-start; gap: 15px; } .feedback-strip { flex-direction: column; gap: 16px; } }
    </style>
</head>
<body>
<div class="particles"></div>

<div class="top-bar">
    <div class="logo"><i class="fas fa-city"></i> Citizen Connect</div>
    <div class="nav-actions">
        <!-- Notification Bell -->
        <div class="notif-wrapper">
            <button class="notif-bell" onclick="toggleNotif()" id="notifBell">
                <i class="fas fa-bell"></i> Notifications
            </button>
            <?php if($notif_count > 0): ?>
                <span class="notif-dot"><?php echo $notif_count; ?></span>
            <?php endif; ?>
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-dropdown-header">
                    <i class="fas fa-bell"></i> Notifications for You
                </div>
                <div class="notif-list">
                    <?php if($notif_count > 0):
                        $notifications->data_seek(0);
                        while($n = $notifications->fetch_assoc()): ?>
                    <div class="notif-list-item">
                        <div class="n-dept"><?php echo $n['department'] === 'all' ? '📢 System Wide' : '🏢 ' . ucfirst($n['department']); ?></div>
                        <div class="n-title"><?php echo htmlspecialchars($n['title']); ?></div>
                        <div class="n-body"><?php echo htmlspecialchars(substr($n['message'], 0, 80)) . (strlen($n['message']) > 80 ? '...' : ''); ?></div>
                        <div class="n-time"><i class="fas fa-clock" style="margin-right:4px;"></i><?php echo date('M j, g:i a', strtotime($n['created_at'])); ?></div>
                    </div>
                    <?php endwhile; else: ?>
                    <div class="notif-empty"><i class="fas fa-bell-slash" style="font-size:30px;display:block;margin-bottom:10px;"></i>No notifications right now.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <a href="feedback.php" class="nav-btn feedback-btn"><i class="fas fa-star"></i> Give Feedback</a>
        <a href="track.php" class="nav-btn"><i class="fas fa-search-location"></i> Live Tracker</a>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="container">
    <div class="header">
        <div class="header-text">
            <h2><i class="fas fa-user-circle" style="color:#00f2fe; margin-right:8px;"></i> Welcome back, <?php echo htmlspecialchars($user); ?>!</h2>
            <p>Your complete portfolio of documented civic issues mapping real progress.</p>
        </div>
        <a href="complaint.php" class="btn-primary"><i class="fas fa-plus-circle"></i> Lodge New Issue</a>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <a href="user_dashboard.php" class="stat-card <?php echo $filter == 'all' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list" style="color: #00f2fe;"></i>
            <div class="stat-info"><h3><?php echo $total; ?></h3><p>Total Submissions</p></div>
        </a>
        <a href="user_dashboard.php?filter=pending" class="stat-card <?php echo $filter == 'pending' ? 'active' : ''; ?>">
            <i class="fas fa-tools" style="color: #ff9800;"></i>
            <div class="stat-info"><h3 style="color: #ff9800;"><?php echo $pending; ?></h3><p>Action Pending</p></div>
        </a>
        <a href="user_dashboard.php?filter=resolved" class="stat-card <?php echo $filter == 'resolved' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle" style="color: #4caf50;"></i>
            <div class="stat-info"><h3 style="color: #4caf50;"><?php echo $resolved; ?></h3><p>Successfully Resolved</p></div>
        </a>
    </div>

    <!-- Feedback CTA -->
    <div class="feedback-strip">
        <div class="strip-text">
            <h3><i class="fas fa-star" style="margin-right:8px;"></i>How are we doing?</h3>
            <p>Share your experience and help us improve civic services for everyone.</p>
        </div>
        <a href="feedback.php" class="feedback-strip-btn"><i class="fas fa-edit"></i> Write a Review</a>
    </div>

    <!-- Complaint Cards -->
    <?php if ($result->num_rows > 0): ?>
        <div class="ticket-grid">
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="ticket-card">
                    <div class="ticket-media">
                        <?php if(!empty($row['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Submitted Evidence">
                        <?php else: ?>
                            <div class="no-img"><i class="fas fa-camera-slash" style="margin-right: 5px;"></i> No Evidence Supplied</div>
                        <?php endif; ?>
                    </div>
                    <div class="ticket-location">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['location']); ?>
                    </div>
                    <div class="ticket-body">
                        <div class="ticket-header">
                            <span class="ticket-id">#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            <?php $s_class = "status-" . str_replace(" ", "", $row['status']); ?>
                            <span class="badge <?php echo $s_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                        </div>
                        <h3 class="ticket-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="ticket-desc"><?php echo htmlspecialchars(strlen($row['description']) > 120 ? substr($row['description'], 0, 120) . '...' : $row['description']); ?></div>
                    </div>
                    <div class="ticket-footer">
                        <span class="badge badge-dept"><i class="fas fa-building" style="opacity:0.6; margin-right:4px;"></i> <?php echo ucfirst(htmlspecialchars($row['department'])); ?></span>
                        <?php
                            $p_color = 'rgba(255,255,255,0.7)';
                            if(($row['priority'] ?? '') == 'Urgent') $p_color = '#ff4d4d';
                            else if(($row['priority'] ?? '') == 'High') $p_color = '#ffa502';
                            else if(($row['priority'] ?? '') == 'Normal') $p_color = '#4caf50';
                        ?>
                        <span style="font-size:12px; font-weight:600; color:<?php echo $p_color; ?>;">
                            <i class="fas fa-flag" style="margin-right:3px;"></i> <?php echo htmlspecialchars($row['priority'] ?? 'Normal'); ?>
                        </span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-city"></i>
            <h3>No Complaints Logged Yet</h3>
            <p>You haven't reported any civic issues under this account. Click below to make your municipality better.</p>
            <a href="complaint.php" class="btn-primary" style="padding: 15px 30px; display:inline-flex;"><i class="fas fa-plus-circle" style="margin-right: 8px;"></i> Report Your First Issue Now</a>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleNotif() {
    const dd = document.getElementById('notifDropdown');
    dd.classList.toggle('show');
    
    // Request permission on first interaction
    if (Notification.permission === "default") {
        Notification.requestPermission();
    }
}

// Browser Native Notification Logic
function showNativeNotification(title, body) {
    // 1. Show floating UI alert (Better UX)
    const alertBox = document.createElement('div');
    alertBox.className = 'mobile-alert-float';
    alertBox.innerHTML = `
        <i class="fas fa-bell-on" style="color:white; font-size:20px;"></i>
        <div>
            <div style="font-weight:700; font-size:14px;">${title}</div>
            <div style="font-size:12px; opacity:0.8;">${body}</div>
        </div>
        <i class="fas fa-times" onclick="this.parentElement.remove()" style="cursor:pointer; margin-left:10px; opacity:0.5;"></i>
    `;
    document.body.appendChild(alertBox);
    setTimeout(() => alertBox.remove(), 8000);

    // 2. Show Browser Native Push if granted
    if (Notification.permission === "granted") {
        new Notification(title, {
            body: body,
            icon: 'https://cdn-icons-png.flaticon.com/512/3602/3602145.png'
        });
    }
}

// Check for recent notifications on load
window.addEventListener('load', () => {
    <?php if($notif_count > 0): 
        $notifications->data_seek(0);
        $latest = $notifications->fetch_assoc();
        // Show if created in last 2 minutes
        if(time() - strtotime($latest['created_at']) < 120):
    ?>
    showNativeNotification("<?php echo addslashes($latest['title']); ?>", "<?php echo addslashes(substr($latest['message'], 0, 100)); ?>...");
    <?php endif; endif; ?>
});

// Close if clicked outside
document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.notif-wrapper');
    if(wrapper && !wrapper.contains(e.target)) {
        document.getElementById('notifDropdown').classList.remove('show');
    }
});
</script>
</body>
</html>
