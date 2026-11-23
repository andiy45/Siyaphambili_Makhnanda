<?php
// Start session
session_start();

// Include database connection
require_once 'dbconnection.php';

// Check if connection exists
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

//Councillor guard
$role = strtolower(trim((string) ($_SESSION['userrole'] ?? '')));
if (!isset($_SESSION['access']) || $role !== 'councillor') {
    header('Location: login.php');
    exit;
}

//Ward guard
$councillorWard = $_SESSION['ward'] ?? $_SESSION['ward'] ?? null;
//if ($councillorWard === null || $councillorWard === '') {
    //die('<p style="font-family:Arial;padding:40px;text-align:center;">
        //Your account has no ward assigned.<br>Please contact an administrator.
    //</p>');
//}
$councillorWard = (int) $councillorWard;

//Resolve ward label for display
$wardLabel = 'Ward ' . $councillorWard;
$wStmt = $conn->prepare("SELECT ward_name FROM ward WHERE ward_id = ?");
$wStmt->bind_param('i', $councillorWard);
$wStmt->execute();
$wRow = $wStmt->get_result()->fetch_assoc();
$wStmt->close();
if ($wRow) $wardLabel = $wRow['ward_name'];

// Fetch only THIS councillor's ward tickets
$all_issues = [];

$sql = "SELECT ticket_id, incident_type, incident_address,
               priority, status, created_at
        FROM faultticket
        WHERE ward_number = ?
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $councillorWard);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $all_issues[] = $row;
}
$stmt->close();

// Escape helper
function e($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Fault Reports</title>
    <style>
        :root {
            --background: #f6efe6;
            --white: #ffffff;
            --wine: #4b1f2a;
            --text: #333333;
            --muted: #777777;
            --line: #e1ddd7;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: var(--background); color: var(--text); }
        
        .topbar {
            height: 75px; background-color: var(--wine); color: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 35px;
        }
        .main-content { width: 100%; max-width: 1200px; margin: auto; padding: 40px; }
        
        .content-card {
            background-color: var(--white); border-radius: 12px;
            border: 1px solid var(--line); padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .section-heading {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px;
        }
        .section-heading h2 { font-size: 24px; color: var(--wine); }
        
        .issue {
            display: flex; justify-content: space-between; align-items: center;
            padding: 16px 5px; border-bottom: 1px solid #eee;
        }
        .issue:last-child { border-bottom: none; }
        
        /* New styles for the details */
        .issue-details {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .issue-meta {
            font-size: 12px;
            color: var(--muted);
            display: flex;
            gap: 15px;
        }
        .priority-badge {
            font-weight: bold;
            text-transform: uppercase;
        }
        .priority-High { color: #b33; }
        .priority-Medium { color: #a87800; }
        .priority-Low { color: #388e3c; }
        
        .status { font-size: 13px; font-weight: bold; color: #b33; }
        
        .back-btn {
            display: inline-block; padding: 10px 18px;
            background-color: var(--wine); color: white;
            text-decoration: none; border-radius: 6px;
            font-size: 0.9rem; margin-bottom: 20px;
        }
        .back-btn:hover { opacity: 0.9; }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="topbar">
        <div class="brand">
            <strong>Makana Municipality</strong>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        
        <!-- Back Button -->
        <a href="councillor.php" class="back-btn">← Back to Dashboard</a>

        <section class="content-card">
            <div class="section-heading">
                <h2>All Reported Faults</h2>
            </div>

            <div class="issues">
                <?php if (count($all_issues) > 0): ?>
                    <?php foreach ($all_issues as $issue): ?>
                        <div class="issue">
                            <div class="issue-details">
                                <span>
                                    <strong>#<?php echo htmlspecialchars($issue['ticket_id']); ?></strong> - 
                                    <?php echo htmlspecialchars($issue['incident_type']); ?> 
                                    (<?php echo htmlspecialchars($issue['incident_address']); ?>)
                                </span>
                                <div class="issue-meta">
                                    <!-- Display Priority with dynamic color class -->
                                    <span>Priority: 
                                        <span class="priority-badge priority-<?php echo htmlspecialchars($issue['priority']); ?>">
                                            <?php echo htmlspecialchars($issue['priority']); ?>
                                        </span>
                                    </span>
                                    <!-- Display Created Date formatted nicely -->
                                    <span>Reported: 
                                        <?php echo date('d M Y, H:i', strtotime($issue['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <span class="status">Open</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="issue">
                        <span>No faults have been reported yet.</span>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

</body>
</html>