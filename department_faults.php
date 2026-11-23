<?php
session_start();
require_once 'dbconnection.php';

function normalize_role($value) {
    $role = strtolower(trim((string) $value));
    return preg_replace('/[ _-]+/', '', $role);
}

$role = normalize_role($_SESSION['userrole'] ?? '');
if (!isset($_SESSION['access']) || !in_array($role, ['worker', 'municipalityworker', 'municipalworker'], true)) {
    header('Location: login_testing.php');
    exit;
}

$username = (string) ($_SESSION['username'] ?? '');
$department = '';

$workerStmt = $conn->prepare('SELECT category FROM theusers WHERE username = ? LIMIT 1');
if ($workerStmt) {
    $workerStmt->bind_param('s', $username);
    $workerStmt->execute();
    $worker = $workerStmt->get_result()->fetch_assoc();
    $workerStmt->close();
    $department = trim((string) ($worker['category'] ?? ''));
}

$faults = [];
if ($department !== '') {
    $sql = 'SELECT * FROM faultticket WHERE incident_type = ? ORDER BY created_at DESC';
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $department);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $faults[] = $row;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($department); ?> Fault Tickets | Makana Municipality</title>
    <link rel="stylesheet" href="municipal.css">
    <style>
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content { display: none; position: absolute; right: 0; background-color: #f9f9f9; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1000; border-radius: 4px; }
        .dropdown-content a { color: black; padding: 12px 16px; text-decoration: none; display: block; }
        .dropdown-content a:hover { background-color: #f1f1f1; }
        .dropdown-content.show { display: block; }

        .faults-container { margin-top: 1.5rem; background: #ffffff; border-radius: 8px; border: 1px solid #e0e0e0; overflow: hidden; }
        table.faults-table { width: 100%; border-collapse: collapse; text-align: left; }
        table.faults-table th, table.faults-table td { padding: 12px 16px; border-bottom: 1px solid #eeeeee; }
        table.faults-table th { background-color: #f7f5f0; font-weight: bold; color: #333; }
        .back-btn { display: inline-block; margin-bottom: 1rem; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<header class="topbar">
    <div class="brand">
        <img src="Makana-Municipality logo.png" alt="Makana Municipality logo">
        <div><strong>Makana Municipality</strong><span>Municipal Worker workspace</span></div>
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

<main style="padding: 2rem; max-width: 1200px; margin: 0 auto;">
    
    <a href="municipal_dashboard.php" class="card-btn back-btn">&larr; Back to Dashboard</a>

    <div class="eyebrow">Department Overview</div>
    <h1><?php echo htmlspecialchars($department ?: 'Unassigned'); ?> Department Fault Tickets</h1>

    <div class="faults-container">
        <table class="faults-table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Issue</th>
                    <th>Ward</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($ticket = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ticket['ticket_id']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['ward_number']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['priority']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['status']); ?></td>
                            <td>
                                <!-- Fixed: Sends ticket_id instead of department string -->
                                <a href="view_fault.php?id=<?php echo urlencode($ticket['ticket_id']); ?>" class="card-btn" style="padding: 4px 12px; font-size: 0.85rem;">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem;">
                            No fault tickets found for your department.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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