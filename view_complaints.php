<?php  
session_start();  
$conn = new mysqli("localhost", "root", "", "citizen_connect");  
require_once 'NotificationHelper.php';
  
if ($conn->connect_error) {  
    die("Connection failed: " . $conn->connect_error);  
}  
  
// CHECK LOGIN ROLE 
$role = '';
$dept = '';

if(isset($_SESSION['admin'])){
    $role = 'admin';
} elseif(isset($_SESSION['department'])){
    $role = 'department';
    $dept = $_SESSION['department'];
} else {
    header("Location: index.html");
    exit();
}
  
// UPDATE STATUS  
if(isset($_POST['update'])){  
    $id = $_POST['id'];  
    $status = $_POST['status'];  
  
    if($role == 'admin') {
        $stmt = $conn->prepare("UPDATE complaints SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);
        $res = $stmt->execute();
    } else {
        $stmt = $conn->prepare("UPDATE complaints SET status=? WHERE id=? AND department=?");
        $stmt->bind_param("sis", $status, $id, $dept);
        $res = $stmt->execute();
    }

    if($res) {
        $c_res = $conn->query("SELECT title, contact_phone, contact_email, username, department FROM complaints WHERE id='$id'");
        $c_data = $c_res->fetch_assoc();

        $subject = "Ticket Status Updated | #$id";
        $emailBody = "<html><body>"
            . "<h2>Hello " . ($c_data['username'] ?? 'Citizen') . ",</h2>"
            . "<p>The status of your complaint <strong>#" . str_pad($id, 4, '0', STR_PAD_LEFT) . "</strong> has been updated to: <strong>$status</strong>.</p>"
            . "<p><strong>Title:</strong> " . htmlspecialchars($c_data['title']) . "</p>"
            . "<p>You can track the progress on your dashboard.</p>"
            . "</body></html>";
        $smsBody = "Hi! Your complaint #$id status has been updated to $status. Track it on Citizen Connect.";

        NotificationHelper::broadcast($c_data['contact_email'], $c_data['contact_phone'], $subject, $emailBody, $smsBody, $conn, $c_data['username'], $c_data['department']);
    }
}  
  
// FETCH DATA  
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$priority_filter = isset($_GET['priority']) ? $conn->real_escape_string($_GET['priority']) : '';
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$where_clause = "";
if($role == 'admin') {
    $where_clause = "1=1";
} else {
    $where_clause = "department='$dept'";
}

if(!empty($status_filter)) {
    $where_clause .= " AND status='$status_filter'";
}
if(!empty($priority_filter)) {
    $where_clause .= " AND priority='$priority_filter'";
}
if(!empty($search_query)) {
    $where_clause .= " AND (id LIKE '%$search_query%' OR title LIKE '%$search_query%' OR description LIKE '%$search_query%' OR username LIKE '%$search_query%')";
}

$sql = "SELECT * FROM complaints WHERE $where_clause ORDER BY id DESC";  
$result = $conn->query($sql);  
?>  
  
<!DOCTYPE html>  
<html>  
<head>  
    <title><?php echo $role == 'admin' ? 'All Complaints (Admin)' : ucfirst($dept) . ' Complaints'; ?> - Citizen Connect</title>  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
/* Premium Background Override */
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
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

.top-bar {  
    display: flex;  
    justify-content: space-between;  
    align-items: center;
    padding: 15px 40px;  
    background: rgba(15, 32, 39, 0.8);  
    backdrop-filter: blur(20px);  
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
    position: sticky;
    top: 0;
    z-index: 1000;
} 

.logo {
    font-weight: 600;
    font-size: 24px;
    color: #00f2fe;
} 

.nav-actions {
    display: flex;
    gap: 15px;
}

.btn-link {  
    background: rgba(255,255,255,0.1);  
    padding: 10px 20px;  
    border-radius: 25px;  
    color: white;  
    text-decoration: none;  
    font-weight: 500;
    transition: 0.3s;
    border: 1px solid rgba(255,255,255,0.2);
}  
.btn-link:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
}

.logout-btn {  
    background: linear-gradient(45deg, #ff416c, #ff4b2b);  
    padding: 10px 24px;  
    border-radius: 25px;  
    color: white;  
    text-decoration: none;  
    font-weight: 500;
    transition: 0.3s;
    box-shadow: 0 4px 15px rgba(255, 75, 43, 0.4);
    border: none;
}  
.logout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 75, 43, 0.6);
}

.container {
    width: 95%;
    max-width: 1400px;
    margin: 40px auto;
}

.header {
    margin: 25px 0 35px 0;
}

.header h2 {
    margin: 0 0 5px 0;
    font-size: 32px;
    font-weight: 600;
}

.header p {
    margin: 0;
    opacity: 0.7;
    font-size: 15px;
}

/* 📋 TABLE */
.table-box {
    background: rgba(20, 40, 50, 0.6);  
    padding: 30px;  
    border-radius: 20px;  
    border: 1px solid rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(15px);  
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    transition: 0.3s;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 15px;
    text-align: left;
    font-size: 14px;
}

th {
    color: #00f2fe;
    font-weight: 500;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 1px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

tr {
    border-bottom: 1px solid rgba(255,255,255,0.05);
    transition: 0.2s;
}

tr:hover {
    background: rgba(255,255,255,0.05);
}

.img-preview {
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.2);
    object-fit: cover;
    transition: 0.3s;
}
.img-preview:hover {
    transform: scale(1.1);
}

.map-btn {
    display: inline-flex;
    align-items: center;
    background: rgba(33, 150, 243, 0.2);
    color: #90caf9;
    padding: 6px 12px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 12px;
    border: 1px solid rgba(33, 150, 243, 0.3);
    transition: 0.3s;
}
.map-btn:hover {
    background: rgba(33, 150, 243, 0.4);
    color: white;
}

/* UPDATE FORM IN TABLE */
.inline-form {
    display: flex;
    gap: 8px;
}

select {
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.3);
    color: white;
    font-size: 13px;
    outline: none;
}
select option { background: #0f2027; }

.update-btn {
    padding: 8px 12px;
    border-radius: 8px;
    border: none;
    background: linear-gradient(45deg, #00f2fe, #4facfe);
    color: white;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: 0.3s;
}
.update-btn:hover {
    box-shadow: 0 0 10px rgba(0, 242, 254, 0.5);
}

.success-msg {
    padding: 12px;
    background: rgba(76, 175, 80, 0.2);
    color: #4caf50;
    border: 1px solid rgba(76, 175, 80, 0.3);
    border-radius: 10px;
    margin-bottom: 20px;
    font-weight: 500;
    text-align: center;
}

/* 🔍 FILTER BAR */
.filter-bar {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    background: rgba(255, 255, 255, 0.03);
    padding: 20px;
    border-radius: 15px;
    border: 1px solid rgba(255, 255, 255, 0.05);
    align-items: center;
    flex-wrap: wrap;
}

.search-input {
    flex-grow: 1;
    min-width: 250px;
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.3);
    color: white;
    outline: none;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-btn {
    padding: 10px 20px;
    border-radius: 10px;
    border: none;
    background: #00f2fe;
    color: #0d1b2a;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}
.filter-btn:hover { background: white; }

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}
.status-Pending { background: rgba(255, 152, 0, 0.15); color: #ff9800; border: 1px solid rgba(255, 152, 0, 0.3); }
.status-InProgress { background: rgba(33, 150, 243, 0.15); color: #2196f3; border: 1px solid rgba(33, 150, 243, 0.3); }
.status-Completed { background: rgba(76, 175, 80, 0.15); color: #4caf50; border: 1px solid rgba(76, 175, 80, 0.3); }
    </style>
</head>  
  
<body> 

<div class="top-bar">
    <div class="logo">
        <i class="fas fa-building" style="margin-right:10px;"></i>Citizen Connect
    </div>
    <div class="nav-actions">
        <?php if($role == 'admin'): ?>
            <a href="admin_dashboard.php" class="btn-link"><i class="fas fa-chart-pie"></i> Admin Dashboard</a>
        <?php else: ?>
            <a href="department_dashboard.php" class="btn-link"><i class="fas fa-desktop"></i> Dept Dashboard</a>
        <?php endif; ?>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div> 

<div class="container">  

    <div class="header">
        <?php if($role == 'admin'): ?>
            <h2><i class="fas fa-globe"></i> Master Complaint Directory
                <?php if(!empty($status_filter)) echo " - <span style='color:#ff9800;'>$status_filter</span>"; ?>
                <?php if(!empty($priority_filter)) echo " - <span style='color:#ff4d4d;'>$priority_filter</span>"; ?>
            </h2>
            <p>Viewing comprehensive logs for all departments system-wide.</p>
        <?php else: ?>
            <h2><i class="fas fa-folder-open"></i> <?php echo ucfirst($dept); ?> Complaints
                <?php if(!empty($status_filter)) echo " - <span style='color:#ff9800;'>$status_filter</span>"; ?>
                <?php if(!empty($priority_filter)) echo " - <span style='color:#ff4d4d;'>$priority_filter</span>"; ?>
            </h2>
            <p>Viewing all active and resolved complaints assigned to your jurisdiction.</p>
        <?php endif; ?>
    </div>

    <!-- FILTER BAR -->
    <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="Search by Ticket ID, Title, User..." class="search-input" value="<?php echo htmlspecialchars($search_query); ?>">
        <div class="filter-group">
            <select name="status">
                <option value="">All Status</option>
                <option value="Pending" <?php if($status_filter=='Pending') echo 'selected'; ?>>Pending</option>
                <option value="In Progress" <?php if($status_filter=='In Progress') echo 'selected'; ?>>In Progress</option>
                <option value="Completed" <?php if($status_filter=='Completed') echo 'selected'; ?>>Completed</option>
            </select>
            <select name="priority">
                <option value="">All Priorities</option>
                <option value="Urgent" <?php if($priority_filter=='Urgent') echo 'selected'; ?>>Urgent</option>
                <option value="High" <?php if($priority_filter=='High') echo 'selected'; ?>>High</option>
                <option value="Normal" <?php if($priority_filter=='Normal') echo 'selected'; ?>>Normal</option>
            </select>
            <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> Apply Filters</button>
            <a href="view_complaints.php" style="color:rgba(255,255,255,0.5); font-size:13px; text-decoration:none;">Clear</a>
        </div>
    </form>

    <!-- SUCCESS MESSAGE -->  
    <?php if(isset($_POST['update'])){ ?>  
        <div class="success-msg"><i class="fas fa-check-circle"></i> Status Broadcasted Successfully to Citizen!</div>
    <?php } ?>  

    <div class="table-box">
        <div style="overflow-x: auto;">
            <table>  
                <tr>  
                    <th>Ticket ID</th>  
                    <th>Subject Description</th>  
                    <th>Contact Data</th>
                    <th>Priority</th>
                    <?php if($role == 'admin') echo '<th>Dept</th>'; ?>
                    <th>Evidence</th>  
                    <th>Location Tracking</th>
                    <th>Live Status</th>  
                </tr>  

                <?php  
                if ($result->num_rows > 0) {  
                    while($row = $result->fetch_assoc()) {  
                ?>  

                <tr>  
                    <td><b>#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></b></td>  
                    <td>
                        <strong style="font-size: 15px; color: #00f2fe;"><?php echo htmlspecialchars($row['title']); ?></strong><br>
                        <span style="opacity:0.7; font-size:13px;"><?php echo htmlspecialchars($row['description']); ?></span>
                    </td>  
                    
                    <td>
                        <?php if(!empty($row['contact_phone'])): ?>
                            <div style="font-size: 13px; margin-bottom: 4px; white-space:nowrap;"><i class="fas fa-phone-alt" style="color:#00f2fe; width:15px;"></i> <?php echo htmlspecialchars($row['contact_phone']); ?></div>
                        <?php endif; ?>
                        <?php if(!empty($row['contact_email'])): ?>
                            <div style="font-size: 13px; opacity:0.8;"><i class="fas fa-envelope" style="color:#00f2fe; width:15px;"></i> <?php echo htmlspecialchars($row['contact_email']); ?></div>
                        <?php endif; ?>
                        <?php if(empty($row['contact_phone']) && empty($row['contact_email'])): ?>
                            <span style="opacity:0.5; font-size:12px;">Anonymous Ticket</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php 
                            $p_color = 'rgba(255,255,255,0.7)'; $p_bg = 'rgba(255,255,255,0.1)';
                            if($row['priority'] == 'Urgent'){ $p_color = '#ff4d4d'; $p_bg = 'rgba(255,77,77,0.15)'; }
                            else if($row['priority'] == 'High'){ $p_color = '#ffa502'; $p_bg = 'rgba(255,165,2,0.15)'; }
                        ?>
                        <span style="color: <?php echo $p_color; ?>; background: <?php echo $p_bg; ?>; padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 11px; border: 1px solid <?php echo $p_color; ?>;"><?php echo $row['priority']; ?></span>
                    </td>
                    
                    <?php if($role == 'admin'): ?>
                        <td><span style="background: rgba(255,255,255,0.1); padding: 4px 8px; border-radius:6px;"><?php echo ucfirst($row['department']); ?></span></td>
                    <?php endif; ?>

                    <td>  
                        <?php if(!empty($row['image'])){ ?>  
                            <a href="uploads/<?php echo $row['image']; ?>" target="_blank">
                                <img src="uploads/<?php echo $row['image']; ?>" width="60" height="60" class="img-preview">  
                            </a>
                        <?php } else { echo "<span style='opacity:0.5;'><i class='fas fa-image'></i> N/A</span>"; } ?>  
                    </td> 

                    <td>
                        <div style="margin-bottom: 5px;"><i class="fas fa-map-marker-alt" style="color:#ffcc80;"></i> <?php echo htmlspecialchars($row['location']); ?></div>
                        <a href="https://www.google.com/maps?q=<?php echo urlencode($row['location']); ?>" target="_blank" class="map-btn">
                            <i class="fas fa-external-link-alt" style="margin-right:5px;"></i> Open Map
                        </a>
                    </td> 

                    <!-- UPDATE DROPDOWN -->
                    <td>  
                        <form method="POST" class="inline-form" onsubmit="return confirm('Update status and notify citizen?');">  
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">  
                            <?php 
                                $s_val = $row['status'];
                                $s_clean = str_replace(' ', '', $s_val);
                            ?>
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <span class="status-badge status-<?php echo $s_clean; ?>"><?php echo $s_val; ?></span>
                                <div style="display:flex; gap:5px;">
                                    <select name="status" style="padding: 5px; font-size: 11px;">  
                                        <option value="Pending" <?php if($s_val=='Pending') echo 'selected'; ?>>Pending</option>  
                                        <option value="In Progress" <?php if($s_val=='In Progress') echo 'selected'; ?>>In Progress</option>  
                                        <option value="Completed" <?php if($s_val=='Completed') echo 'selected'; ?>>Completed</option>  
                                    </select>  
                                    <button name="update" class="update-btn"><i class="fas fa-save"></i></button>
                                </div>
                            </div>
                        </form>  
                    </td> 
                </tr>  

                <?php  
                    }  
                } else {  
                    echo "<tr><td colspan='7' style='text-align:center; padding: 40px; opacity:0.6;'><i class='fas fa-inbox' style='font-size:40px; margin-bottom:10px;'></i><br>No complaints logged.</td></tr>";  
                }  
                ?>  

            </table>  
        </div>
    </div>  

</div>  

</body>  
</html>