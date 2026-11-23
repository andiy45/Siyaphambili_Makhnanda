<?php
session_start();

$role = strtolower(trim((string) ($_SESSION['userrole'] ?? '')));
$role = str_replace([' ', '_'], '', $role);

if (!isset($_SESSION['access']) || !in_array($role, ['municipalworker', 'municipalityworker', 'worker', 'workers'], true)) {
    header('Location: login_testing.php');
    exit;
}

$displayName = trim(
    (string) ($_SESSION['firstname'] ?? '') . ' ' .
    (string) ($_SESSION['surname'] ?? '')
);

if ($displayName === '') {
    $displayName = (string) ($_SESSION['username'] ?? 'Manager');
}
require_once 'dbconnection.php';

/* 1. GET THE WORKER'S DEPARTMENT (CATEGORY) */
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

/* 2. GET FAULT TICKETS MATCHING WORKER'S CATEGORY */
$tickets = [];
if ($department !== '') {
    $sql = 'SELECT * FROM faultticket WHERE incident_type = ? ORDER BY created_at DESC';
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $department);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        $stmt->close();
    }
}

$departmentTicketCount = count($tickets);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Municipal Worker Dashboard | Makana Municipality</title>
    <link rel="stylesheet" href="municipal.css">
    <style>
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content { display: none; position: absolute; right: 0; background-color: #f9f9f9; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1000; border-radius: 4px; }
        .dropdown-content a { color: black; padding: 12px 16px; text-decoration: none; display: block; }
        .dropdown-content a:hover { background-color: #f1f1f1; }
        .dropdown-content.show { display: block; }

        /* Modal Overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        /* Modal Content Box */
        .modal-container {
            background-color: #fdfbf7;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2d9cd;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .modal-header h2 { margin: 0; font-size: 1.3rem; color: #3d1b1f; }
        .close-btn { background: none; border: none; font-size: 1.6rem; cursor: pointer; color: #666; }

        .modal-body { overflow-y: auto; margin-bottom: 16px; }

        /* Modal Table Styling */
        .modal-table { width: 100%; border-collapse: collapse; text-align: left; }
        .modal-table th, .modal-table td { border: 1px solid #e2d9cd; padding: 10px 12px; }
        .modal-table th { background-color: #ffffff; font-weight: bold; width: 35%; }

        .modal-footer { text-align: right; border-top: 1px solid #e2d9cd; padding-top: 12px; }
        .btn-close-modal { background-color: #e8ded2; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; font-weight: bold; color: #3d1b1f; }
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
            <a href="#" onclick="openAccountModal(); return false;">Account Details</a> 
            <a href="switch_resident.php">Switch to Resident</a> 
            <a href="logout.php">Sign out</a> 
        </div> 
    </div>
</header>

<main>
    <div class="eyebrow">Control centre</div>
        <h1>
    Welcome, <?php echo htmlspecialchars( $displayName, ENT_QUOTES,'UTF-8' );?>
</h1>
    <p class="intro">Review the current platform overview and Municipal Worker account details.</p>



    <section class="card-grid" aria-label="Municipal worker panels">
        <!-- FAULT TICKETS CARD -->
        <div class="card">
            <div class="card-banner gold"></div>
            <div class="card-content">
                <span class="card-tag">Fault Tickets</span>
                <h3>Department Fault Tickets</h3>
                
                <div class="notice">
                    <p class="notice">
                        Total Tickets: <strong><?php echo $departmentTicketCount; ?></strong>. Click "View" for full breakdown.
                    </p>
                </div>

                <div style="margin-top:auto">
                    <button type="button" class="card-btn" onclick="openTicketsModal()">View</button>
                </div>
            </div>
        </div>

</main>

<!-- FAULT TICKETS POPUP MODAL -->
<div id="ticketsModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2><?php echo htmlspecialchars($department ?: 'Unassigned', ENT_QUOTES, 'UTF-8'); ?> Department Fault Tickets</h2>
            <button type="button" class="close-btn" onclick="closeTicketsModal()">&times;</button>
        </div>

        <div class="modal-body">
            <table class="modal-table">
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
                    <?php if ($departmentTicketCount > 0): ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ticket['ticket_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($ticket['title'] ?? 'Untitled Fault', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($ticket['ward_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($ticket['priority'] ?? 'Normal', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($ticket['status'] ?? 'Pending', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <a href="view_fault.php?id=<?php echo urlencode($ticket['ticket_id'] ?? ''); ?>" class="card-btn" style="padding: 4px 10px; font-size: 0.85rem; text-decoration: none;">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 1.5rem;">
                                No fault tickets found for your department.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-close-modal" onclick="closeTicketsModal()">Close</button>
        </div>
    </div>
</div>

<!-- ACCOUNT DETAILS POPUP MODAL -->
<div id="accountModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2>Account Details</h2>
            <button type="button" class="close-btn" onclick="closeAccountModal()">&times;</button>
        </div>

        <div class="modal-body">
            <table class="modal-table">
                <tr>
                    <th>Full Name</th>
                    <td><?php echo htmlspecialchars(trim(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['surname'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <th>Username</th>
                    <td><?php echo htmlspecialchars((string)($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo htmlspecialchars((string)($_SESSION['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <th>Contact Number</th>
                    <td><?php echo htmlspecialchars((string)($_SESSION['contact'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <th>Role</th>
                    <td><?php echo htmlspecialchars((string)($_SESSION['userrole'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <th>Assigned Category</th>
                    <td><?php echo htmlspecialchars($department ?: 'Unassigned', ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <th>Address</th>
                    <td><?php echo htmlspecialchars((string)($_SESSION['physAddress'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            </table>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-close-modal" onclick="closeAccountModal()">Close</button>
        </div>
    </div>
</div>

<script>
    function toggleDropdown(event) {
        event.stopPropagation();
        document.getElementById("profileDropdown").classList.toggle("show");
    }

    function openTicketsModal() {
        document.getElementById("ticketsModal").style.display = "flex";
    }

    function closeTicketsModal() {
        document.getElementById("ticketsModal").style.display = "none";
    }

    function openAccountModal() {
        document.getElementById("accountModal").style.display = "flex";
    }

    function closeAccountModal() {
        document.getElementById("accountModal").style.display = "none";
    }

    // Close modals and dropdowns when clicking outside
    window.onclick = function(event) {
        var ticketsModal = document.getElementById("ticketsModal");
        var accountModal = document.getElementById("accountModal");

        if (event.target === ticketsModal) closeTicketsModal();
        if (event.target === accountModal) closeAccountModal();

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