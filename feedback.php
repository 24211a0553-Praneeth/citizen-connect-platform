<?php
session_start();
$conn = new mysqli("localhost", "root", "", "citizen_connect");

if(!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];

// Handle feedback submission
$msg = "";
if(isset($_POST['submit_feedback'])) {
    $rating     = (int)$_POST['rating'];
    $fb_message = $conn->real_escape_string($_POST['fb_message']);
    $comp_id    = !empty($_POST['complaint_id']) ? (int)$_POST['complaint_id'] : null;

    if($rating < 1 || $rating > 5) {
        $msg = "error|Please select a rating between 1 and 5 stars.";
    } else {
        if($comp_id) {
            $stmt = $conn->prepare("INSERT INTO feedback (username, complaint_id, rating, message) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siis", $user, $comp_id, $rating, $fb_message);
        } else {
            $stmt = $conn->prepare("INSERT INTO feedback (username, rating, message) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $user, $rating, $fb_message);
        }
        if($stmt->execute()) {
            $msg = "success|Thank you for your feedback! The admin team will review it shortly.";
        } else {
            $msg = "error|Failed to submit feedback. Please try again.";
        }
    }
}

// Get user's complaints for dropdown
$my_complaints = $conn->query("SELECT id, title, status FROM complaints WHERE username='$user' ORDER BY id DESC");

// Get user's past feedbacks
$my_feedbacks = $conn->query("SELECT * FROM feedback WHERE username='$user' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Submit Feedback | Citizen Connect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Submit feedback about your civic complaint experience on Citizen Connect">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Inter', sans-serif;
        min-height: 100vh;
        background: url('https://images.unsplash.com/photo-1449824913935-59a10b8d2000?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') no-repeat center/cover fixed;
        color: white; overflow-x: hidden; position: relative;
    }
    body::before {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(135deg, rgba(6,11,20,0.94) 0%, rgba(15,32,50,0.88) 100%);
        z-index: 0;
    }
    .particles {
        position: fixed; inset: 0;
        background-image: radial-gradient(circle, rgba(255,255,255,0.08) 1px, transparent 1px);
        background-size: 55px 55px; z-index: 0; pointer-events: none;
        animation: drift 50s linear infinite;
    }
    @keyframes drift { to { transform: translateY(-80px); } }

    /* NAV */
    .top-bar {
        position: sticky; top: 0; z-index: 100;
        background: rgba(6,11,20,0.85); backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255,255,255,0.07);
        padding: 15px 40px; display: flex; justify-content: space-between; align-items: center;
    }
    .logo { font-size: 20px; font-weight: 800; color: #4facfe; display: flex; align-items: center; gap: 10px; }
    .nav-links { display: flex; gap: 12px; }
    .nav-link {
        padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 600;
        text-decoration: none; transition: 0.25s; color: rgba(255,255,255,0.7);
        border: 1px solid rgba(255,255,255,0.08);
    }
    .nav-link:hover { background: rgba(79,172,254,0.1); color: #4facfe; border-color: rgba(79,172,254,0.3); }
    .nav-link.back { background: rgba(255,255,255,0.05); }

    /* PAGE */
    .page { position: relative; z-index: 1; padding: 40px 5%; max-width: 900px; margin: 0 auto; }

    .page-header { margin-bottom: 32px; animation: fadeUp 0.6s ease; }
    .page-header h1 { font-size: 32px; font-weight: 800; margin-bottom: 8px;
        background: linear-gradient(135deg, #fff, #4facfe);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .page-header p { color: rgba(255,255,255,0.6); font-size: 15px; }

    @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    /* FLASH */
    .flash {
        padding: 14px 20px; border-radius: 12px; margin-bottom: 24px;
        font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px;
        animation: fadeUp 0.4s ease;
    }
    .flash.success { background: rgba(67,233,123,0.12); border: 1px solid rgba(67,233,123,0.3); color: #43e97b; }
    .flash.error   { background: rgba(255,71,87,0.12); border: 1px solid rgba(255,71,87,0.3); color: #ff4757; }

    /* FORM CARD */
    .form-card {
        background: rgba(13,20,35,0.85); backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.07); border-radius: 20px;
        padding: 36px; margin-bottom: 30px; animation: fadeUp 0.7s ease;
    }
    .form-card h2 { font-size: 18px; font-weight: 700; margin-bottom: 24px;
        display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 16px;
    }
    .form-card h2 i { color: #4facfe; }

    /* STAR RATING */
    .star-group { margin-bottom: 24px; }
    .star-group label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.5); margin-bottom: 12px; }
    .stars-input { display: flex; gap: 8px; flex-direction: row-reverse; justify-content: flex-end; }
    .stars-input input { display: none; }
    .stars-input label {
        font-size: 36px; color: rgba(255,255,255,0.15); cursor: pointer;
        transition: 0.2s; -webkit-text-fill-color: initial;
    }
    .stars-input label:hover,
    .stars-input label:hover ~ label,
    .stars-input input:checked ~ label { color: #ffd700; text-shadow: 0 0 10px rgba(255,215,0,0.5); }

    /* FORM GROUPS */
    .fg { margin-bottom: 20px; }
    .fg label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.5); margin-bottom: 8px; }
    .fg select, .fg textarea {
        width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px; padding: 12px 16px; color: white; font-size: 14px;
        font-family: 'Inter', sans-serif; outline: none; transition: 0.25s;
    }
    .fg select option { background: #0d1423; }
    .fg select:focus, .fg textarea:focus {
        border-color: rgba(79,172,254,0.4);
        box-shadow: 0 0 0 3px rgba(79,172,254,0.1);
    }
    .fg textarea { min-height: 120px; resize: vertical; }

    .submit-btn {
        width: 100%; padding: 16px; border-radius: 12px; border: none;
        background: linear-gradient(135deg, #4facfe, #a855f7);
        color: white; font-size: 16px; font-weight: 700; cursor: pointer;
        transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(79,172,254,0.4); }

    /* PAST FEEDBACKS */
    .fb-item {
        background: rgba(13,20,35,0.85); backdrop-filter: blur(15px);
        border: 1px solid rgba(255,255,255,0.07); border-radius: 16px;
        padding: 24px; margin-bottom: 16px; animation: fadeUp 0.8s ease;
        transition: 0.3s;
    }
    .fb-item:hover { border-color: rgba(79,172,254,0.25); }
    .fb-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .fb-stars { color: #ffd700; font-size: 18px; letter-spacing: 3px; }
    .fb-date { font-size: 12px; color: rgba(255,255,255,0.4); }
    .comp-ref {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(79,172,254,0.1); border: 1px solid rgba(79,172,254,0.2);
        padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; color: #4facfe;
        margin-bottom: 10px;
    }
    .fb-message { font-size: 14px; color: rgba(255,255,255,0.7); line-height: 1.6; }
    .admin-reply {
        margin-top: 14px; padding: 14px 16px; border-radius: 10px;
        background: rgba(79,172,254,0.07); border: 1px solid rgba(79,172,254,0.2);
    }
    .reply-label { font-size: 11px; font-weight: 700; color: #4facfe; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
    .reply-text { font-size: 13px; color: rgba(255,255,255,0.8); }
    .no-reply { font-size: 13px; color: rgba(255,255,255,0.3); font-style: italic; margin-top: 10px; display: flex; align-items: center; gap: 6px; }
    </style>
</head>
<body>
<div class="particles"></div>

<nav class="top-bar">
    <div class="logo"><i class="fas fa-city"></i> Citizen Connect</div>
    <div class="nav-links">
        <a href="user_dashboard.php" class="nav-link back"><i class="fas fa-arrow-left" style="margin-right:5px;"></i> My Dashboard</a>
        <a href="complaint.html" class="nav-link"><i class="fas fa-plus-circle" style="margin-right:5px;"></i> New Complaint</a>
        <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt" style="margin-right:5px;"></i> Logout</a>
    </div>
</nav>

<div class="page">
    <div class="page-header">
        <h1><i class="fas fa-star" style="color:#ffd700; -webkit-text-fill-color:#ffd700; font-size:28px; margin-right:10px;"></i>Share Your Feedback</h1>
        <p>Help us improve civic services. Your experience matters to the community.</p>
    </div>

    <?php if($msg) {
        [$type, $text] = explode('|', $msg, 2);
        $icon = $type === 'success' ? 'check-circle' : 'exclamation-circle';
        echo "<div class='flash $type'><i class='fas fa-$icon'></i> $text</div>";
    } ?>

    <!-- Feedback Form -->
    <div class="form-card">
        <h2><i class="fas fa-edit"></i> Write Your Review</h2>
        <form method="POST" id="feedbackForm">

            <!-- Star Rating -->
            <div class="star-group">
                <label>Your Rating *</label>
                <div class="stars-input">
                    <input type="radio" name="rating" id="s5" value="5"><label for="s5">★</label>
                    <input type="radio" name="rating" id="s4" value="4"><label for="s4">★</label>
                    <input type="radio" name="rating" id="s3" value="3"><label for="s3">★</label>
                    <input type="radio" name="rating" id="s2" value="2"><label for="s2">★</label>
                    <input type="radio" name="rating" id="s1" value="1"><label for="s1">★</label>
                </div>
            </div>

            <!-- Link to Complaint (optional) -->
            <div class="fg">
                <label><i class="fas fa-link" style="margin-right:4px;"></i> Link to Complaint (Optional)</label>
                <select name="complaint_id">
                    <option value="">— General Feedback (no specific complaint) —</option>
                    <?php if($my_complaints && $my_complaints->num_rows > 0): ?>
                        <?php $my_complaints->data_seek(0); while($c = $my_complaints->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>">
                            #<?php echo str_pad($c['id'],4,'0',STR_PAD_LEFT); ?> — <?php echo htmlspecialchars(substr($c['title'],0,50)); ?> [<?php echo $c['status']; ?>]
                        </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Message -->
            <div class="fg">
                <label><i class="fas fa-comment" style="margin-right:4px;"></i> Your Message *</label>
                <textarea name="fb_message" placeholder="Tell us about your experience — what went well, what can be improved, how satisfied were you with the resolution..." required></textarea>
            </div>

            <button type="submit" name="submit_feedback" class="submit-btn" id="submitFeedbackBtn">
                <i class="fas fa-paper-plane"></i> Submit Feedback
            </button>
        </form>
    </div>

    <!-- Past Feedbacks -->
    <?php if($my_feedbacks && $my_feedbacks->num_rows > 0): ?>
    <div class="form-card">
        <h2><i class="fas fa-history"></i> Your Past Feedback</h2>
        <?php while($fb = $my_feedbacks->fetch_assoc()): ?>
        <?php $stars = str_repeat('★', $fb['rating']) . str_repeat('☆', 5 - $fb['rating']); ?>
        <div class="fb-item">
            <div class="fb-header">
                <span class="fb-stars"><?php echo $stars; ?></span>
                <span class="fb-date"><?php echo date('M j, Y', strtotime($fb['created_at'])); ?></span>
            </div>
            <?php if($fb['complaint_id']): ?>
                <div class="comp-ref"><i class="fas fa-hashtag"></i> Complaint #<?php echo str_pad($fb['complaint_id'],4,'0',STR_PAD_LEFT); ?></div>
            <?php endif; ?>
            <div class="fb-message"><?php echo htmlspecialchars($fb['message']); ?></div>
            <?php if(!empty($fb['admin_reply'])): ?>
            <div class="admin-reply">
                <div class="reply-label"><i class="fas fa-shield-alt"></i> Admin Response</div>
                <div class="reply-text"><?php echo htmlspecialchars($fb['admin_reply']); ?></div>
            </div>
            <?php else: ?>
                <div class="no-reply"><i class="fas fa-clock"></i> Awaiting admin response...</div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('feedbackForm').addEventListener('submit', function(e) {
    const rating = document.querySelector('input[name="rating"]:checked');
    if (!rating) {
        e.preventDefault();
        alert('⭐ Please select a star rating before submitting!');
    }
});
</script>
</body>
</html>
