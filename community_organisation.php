<?php
session_start();

// 1. Check if user is logged in and is a Community Organisation
$role = strtolower(trim((string) ($_SESSION['userrole'] ?? '')));
$role = str_replace([' ', '_'], '', $role);
if (!isset($_SESSION['access']) || !in_array($role, ['communityorganisation', 'communityorganization'], true)) {
    header('Location: login.html');
    exit;
}
require_once 'dbconnection.php';

function communityNoticeImageSrc(?string $imageData): ?string
{
    if ($imageData === null || $imageData === '') {
        return null;
    }

    $imageInfo = @getimagesizefromstring($imageData);
    if (!$imageInfo || empty($imageInfo['mime'])) {
        return null;
    }

    return 'data:' . $imageInfo['mime'] . ';base64,' . base64_encode($imageData);
}

$message = '';
$messageType = '';

// 2. Get display name from session
$defaultOrgName = trim((string) ($_SESSION['organisation_name'] ?? 'Community Organisation'));
if ($defaultOrgName === '') {
    $defaultOrgName = 'Community Organisation';
}

// 3. Handle Notice Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notice'])) {
    $noticeId = intval($_POST['notice_id'] ?? 0);
    if ($noticeId > 0) {
        $stmt = $conn->prepare("DELETE FROM community_notices WHERE id = ?");
        $stmt->bind_param("i", $noticeId);
        if ($stmt->execute()) {
            $message = "Notice deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Error deleting notice: " . $conn->error;
            $messageType = "error";
        }
        $stmt->close();
    }
}

// 4. Handle Notice Submission
    echo "<pre style='background:#fff;padding:10px;border:1px solid #ccc;'>FILES DEBUG:\n";
    print_r($_FILES);
    echo "</pre>";
    exit;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_notice'])) {
    $orgName = trim($_POST['organisation_name'] ?? $defaultOrgName);
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $photoData = null;

    // Handle Photo Upload (Save file to uploads/ folder)
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileSize = $_FILES['photo']['size'];
        $fileType = $_FILES['photo']['type'];

              $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (in_array($fileType, $allowedTypes)) {
            if ($fileSize < 5000000) { // 5MB limit
                $photoData = file_get_contents($fileTmpPath);
            } else {
                $message = "File is too large. Max 5MB.";
                $messageType = "error";
            }
        } else {
            $message = "Invalid file type. Only JPG, PNG, GIF, WEBP allowed.";
            $messageType = "error";
        }
    }

    // Insert into database
    if ($orgName && $title && $content && $messageType !== 'error') {
        $stmt = $conn->prepare("INSERT INTO community_notices (organisation_name, title, content, photo, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("sssb", $orgName, $title, $content, $photoData);
        
        if ($stmt->execute()) {
            $message = "Notice submitted successfully!";
            $messageType = "success";
        } else {
            $message = "Database error: " . $conn->error;
            $messageType = "error";
        }
        $stmt->close();
    } elseif ($messageType !== 'error') {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    }
}

// 5. Fetch ALL notices
$notices = [];
$result = $conn->query("
    SELECT id, organisation_name, title, content, status, date_created,
           CASE 
               WHEN photo IS NULL THEN NULL
               WHEN LENGTH(photo) < 1000 THEN NULL
               ELSE photo
           END AS photo
    FROM community_notices
    ORDER BY date_created DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notices[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Organisation Dashboard</title>
    <link rel="stylesheet" href="municipal.css?v=4">
    <style>
        * { box-sizing: border-box; }
        
        body { 
            background-color: #f4f1ea; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            color: #333; 
        }
        
        /* --- HEADER --- */
        .topbar { 
            background: #4a1c1c; 
            padding: 15px 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .brand { display: flex; align-items: center; gap: 15px; }
        .brand img { height: 40px; border-radius: 50%; }
        .brand strong { 
            font-size: 1.2rem; 
            color: #ffffff; 
            font-family: Georgia, serif; 
        }
        .brand span { 
            display: block; 
            font-size: 0.85rem; 
            color: #d1c0c0; 
        }
        .dropdown { position: relative; display: inline-block; }
        .dropdown-button { 
            background: transparent; 
            border: 1px solid #ffffff; 
            color: #ffffff;
            padding: 8px 16px; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 0.9rem;
        }
        .dropdown-button:hover { background: rgba(255,255,255,0.1); }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #fff;
            min-width: 180px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 100;
            border-radius: 4px;
            overflow: hidden;
        }
        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 0.9rem;
        }
        .dropdown-content a:hover { background-color: #f1f1f1; }
        .show { display: block; }

        /* --- MAIN --- */
        main { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 40px 30px; 
        }
        .eyebrow { 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 1.5px;
            color: #999; 
            margin-bottom: 10px; 
            font-weight: bold;
        }
        h1 { 
            font-family: Georgia, 'Times New Roman', serif; 
            font-size: 2.8rem; 
            color: #2c3e50; 
            margin: 0 0 10px 0; 
            font-weight: normal; 
        }
        .intro { 
            color: #666; 
            margin-bottom: 40px; 
            font-size: 1rem;
        }

        /* --- FULL WIDTH FORM CARD --- */
        .full-width-card {
            width: 100%;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid #e0e0e0;
            margin-bottom: 40px;
        }
        .banner { height: 100px; background: #4a1c1c; } 
        .card-content { padding: 30px 40px; }
        .tag { 
            display: inline-block; 
            background: #f7f3eb; 
            color: #555; 
            font-size: 0.65rem; 
            text-transform: uppercase; 
            letter-spacing: 1px;
            padding: 5px 10px; 
            border-radius: 3px; 
            margin-bottom: 15px; 
            font-weight: bold;
        }
        .card-content h3 { 
            font-family: Georgia, serif; 
            font-size: 1.6rem; 
            color: #2c3e50; 
            margin: 0 0 10px 0; 
            font-weight: normal; 
        }
        .card-content p { 
            color: #666; 
            font-size: 0.95rem; 
            line-height: 1.5; 
            margin: 0 0 25px 0; 
        }
        
        .form-group { margin-bottom: 18px; }
        label { 
            display: block; 
            font-size: 0.85rem; 
            color: #555; 
            margin-bottom: 6px; 
            font-weight: bold; 
        }
        input[type="text"], textarea, input[type="file"] { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            font-family: inherit; 
            font-size: 0.95rem;
        }
        textarea { min-height: 100px; resize: vertical; }
        
        .btn { 
            padding: 12px 28px; 
            border: 1px solid #2c3e50; 
            color: #2c3e50; 
            background: transparent; 
            border-radius: 4px; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 0.9rem;
        }
        .btn:hover { background: #2c3e50; color: #fff; }
        
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 25px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* --- STATUS SECTION --- */
        .status-section { margin-top: 20px; }
        .status-section h2 {
            font-family: Georgia, serif;
            font-size: 1.8rem;
            color: #2c3e50;
            margin: 0 0 25px 0;
            font-weight: normal;
        }
        .status-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 20px; 
        }
        .status-card { 
            background: #fff; 
            border-radius: 8px; 
            padding: 20px; 
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); 
            border-left: 5px solid #4a1c1c;
            display: flex; 
            flex-direction: column; 
        }
        
        .status-card.pending { border-left-color: #4a1c1c; }
        .status-card.approved { border-left-color: #28a745; }
        .status-card.rejected { border-left-color: #dc3545; }
        
        .status-card img { width: 100%; height: 150px; object-fit: cover; border-radius: 4px; margin-bottom: 12px; border: 1px solid #eee; }
        .status-card h4 { margin: 0 0 5px 0; font-family: Georgia, serif; font-size: 1.15rem; color: #2c3e50; }
        .status-card .org-name { font-size: 0.8rem; color: #2c3e50; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-card .date { font-size: 0.8rem; color: #888; margin-bottom: 10px; }
        .status-card .details { font-size: 0.85rem; color: #555; margin-bottom: 15px; line-height: 1.5; }
        
        .card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 10px; border-top: 1px solid #f0f0f0; }
        
        .badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .badge.pending { background: #4a1c1c; color: #ffffff; }
        .badge.approved { background: #d4edda; color: #155724; }
        .badge.rejected { background: #f8d7da; color: #721c24; }

        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
            font-weight: bold;
        }
        .delete-btn:hover { background: #c82333; }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">
            <img src="Makana-Municipality logo.png" alt="Logo">
            <div>
                <strong>Makana Municipality</strong>
                <span>Community Organisation workspace</span>
            </div>
        </div>
        <div class="dropdown"> 
            <button type="button" class="dropdown-button" onclick="toggleDropdown(event)"> Profile </button> 
            <div id="profileDropdown" class="dropdown-content"> 
                <a href="account_details.php">Account Details</a> 
                <a href="switch_resident.php">Switch to Resident</a> 
                <a href="logout.php">Sign out</a> 
            </div> 
        </div>
    </header>

    <main>
        <div class="eyebrow">Operations Centre</div>
        <h1>Welcome, <?php echo htmlspecialchars($defaultOrgName); ?></h1>
        <p class="intro">Manage notices and view your organisation's status.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- FULL WIDTH SUBMIT NOTICE CARD -->
        <div class="full-width-card">
            <div class="banner"></div>
            <div class="card-content">
                <span class="tag">Notice Management</span>
                <h3>Submit a Notice</h3>
                <p>Send a notice to the municipal manager. It will appear on the public homepage once approved.</p>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Organisation Name</label>
                        <input type="text" name="organisation_name" value="<?php echo htmlspecialchars($defaultOrgName); ?>" placeholder="e.g., Community Watch" required>
                    </div>
                    <div class="form-group">
                        <label>Notice Title</label>
                        <input type="text" name="title" placeholder="e.g., Water Maintenance Update" required>
                    </div>
                    <div class="form-group">
                        <label>Notice Details</label>
                        <textarea name="content" placeholder="Type your notice here..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Attach a Photo (Optional)</label>
                        <input type="file" name="photo" accept="image/jpeg, image/png, image/gif, image/webp">
                    </div>
                    <button type="submit" name="submit_notice" class="btn">Submit Notice</button>
                </form>
            </div>
        </div>

        <!-- STATUS SECTION -->
        <div class="status-section">
            <h2>Your Notice Status</h2>
            <?php if (empty($notices)): ?>
                <p><em>You have not submitted any notices yet.</em></p>
            <?php else: ?>
                <div class="status-grid">
                    <?php foreach ($notices as $notice): ?>
                        <div class="status-card <?php echo strtolower($notice['status']); ?>">
                            <?php $src = communityNoticeImageSrc($notice['photo'] ?? null); ?>
                            <?php if ($src !== null): ?>
                                <img src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>" alt="Notice Photo">
                            <?php endif; ?>
                            
                            <div class="org-name"><?php echo htmlspecialchars($notice['organisation_name']); ?></div>
                            <h4><?php echo htmlspecialchars($notice['title']); ?></h4>
                            <div class="date"><?php echo date('M d, Y', strtotime($notice['date_created'])); ?></div>
                            <div class="details"><?php echo htmlspecialchars(substr($notice['content'], 0, 100)) . '...'; ?></div>
                            
                            <div class="card-footer">
                                <span class="badge <?php echo strtolower($notice['status']); ?>">
                                    <?php echo htmlspecialchars($notice['status']); ?>
                                </span>
                                
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this notice?');" style="margin:0;">
                                    <input type="hidden" name="notice_id" value="<?php echo $notice['id']; ?>">
                                    <button type="submit" name="delete_notice" class="delete-btn">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    function toggleDropdown(event) {
        event.stopPropagation();
        document.getElementById("profileDropdown").classList.toggle("show");
    }

    window.onclick = function(event) {
        if (!event.target.matches('.dropdown-button')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
    </script>
</body>
</html>