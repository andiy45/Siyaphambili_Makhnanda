<?php
session_start();

// 1. Optional: guard so only managers can view
$role = strtolower(trim((string) ($_SESSION['userrole'] ?? '')));
if (!isset($_SESSION['access']) || !in_array($role, ['manager'], true)) {
    header('Location: login.html');
    exit;
}

require_once 'dbconnection.php';

// 2. Fetch ALL fault tickets (add status column if you have one)
$all_issues = [];
$sql = "SELECT ticket_id, incident_type, incident_address, priority, status, created_at 
        FROM faultticket 
        ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $all_issues[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Fault Reports | Manager</title>
    <style>
        :root {
            --ink: #242329;
            --muted: #706d73;
            --paper: #f8f4ee;
            --panel: #ffffff;
            --wine: #4b1f2a;
            --gold: #b08d57;
            --line: #e6ddd2;
            --green: #2f7463;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--paper);
            color: var(--ink);
            font-family: Arial, sans-serif;
        }

        /* Top bar */
        .topbar {
            background: var(--wine);
            color: #fff;
            padding: 16px clamp(20px, 5vw, 72px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand img { width: 52px; height: 52px; object-fit: contain; background: #fff; border-radius: 50%; }
        .brand strong { display: block; font-size: 1.05rem; letter-spacing: .04em; }
        .brand span { color: #e9d9c1; font: .78rem Arial, sans-serif; }
        .logout {
            color: #fff; border: 1px solid #d5b987; padding: 9px 14px;
            text-decoration: none; font: 700 .8rem Arial, sans-serif;
        }

        main {
            width: min(1180px, calc(100% - 40px));
            margin: 42px auto 70px;
        }

        .eyebrow {
            color: var(--gold);
            font: 700 .74rem Arial, sans-serif;
            letter-spacing: .16em;
            text-transform: uppercase;
        }
        h1 { margin: 8px 0 8px; font-size: clamp(1.8rem, 3.5vw, 2.8rem); font-weight: 400; }
        .intro { color: var(--muted); margin: 0 0 24px; font: 1rem/1.6 Arial, sans-serif; }

        .back-btn {
            display: inline-block;
            padding: 9px 16px;
            background: var(--wine);
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: .85rem;
            margin-bottom: 20px;
        }
        .back-btn:hover { opacity: .9; }

        /* Table card */
        .table-card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 4px 10px rgba(0,0,0,.04);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            font-size: .9rem;
        }
        thead th {
            background: var(--wine);
            color: #fff;
            text-align: left;
            padding: 12px 14px;
            font-size: .75rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        thead th:first-child { border-top-left-radius: 6px; }
        thead th:last-child  { border-top-right-radius: 6px; }

        tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--line);
            vertical-align: middle;
        }
        tbody tr:hover { background: #faf5ec; }
        tbody tr:last-child td { border-bottom: none; }

        .ticket-id { font-weight: 700; color: var(--wine); }
        .muted { color: var(--muted); font-size: .82rem; }

        /* Priority badges */
        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .badge-Low      { background: #e3f3ea; color: #2f7463; }
        .badge-Medium   { background: #fff3d6; color: #a87800; }
        .badge-High     { background: #fbe2e2; color: #b33; }
        .badge-Urgent   { background: #f5c2c2; color: #7a1010; }

        /* Status badges */
        .status {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-Pending     { background: #f1f1f1; color: #666; }
        .status-In\ Progress{ background: #e0ecff; color: #2b5fab; }
        .status-Resolved    { background: #e3f3ea; color: #2f7463; }

        .empty-row td {
            text-align: center;
            padding: 40px;
            color: var(--muted);
            font-style: italic;
        }

        @media (max-width: 720px) {
            thead { display: none; }
            tbody tr {
                display: block;
                border: 1px solid var(--line);
                border-radius: 8px;
                margin-bottom: 12px;
                padding: 10px;
            }
            tbody td {
                display: flex;
                justify-content: space-between;
                border: none;
                padding: 6px 4px;
            }
            tbody td::before {
                content: attr(data-label);
                font-weight: 700;
                color: var(--muted);
                font-size: .75rem;
                text-transform: uppercase;
                margin-right: 12px;
            }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="brand">
        <img src="Makana-Municipality logo.png" alt="Makana Municipality logo">
        <div>
            <strong>Makana Municipality</strong>
            <span>Manager workspace</span>
        </div>
    </div>
    <a class="logout" href="logout.php">Sign out</a>
</header>

<main>
    <a href="manager_dashboard.php" class="back-btn">← Back to Dashboard</a>

    <div class="eyebrow">Operations centre</div>
    <h1>All Reported Faults</h1>
    <p class="intro">A complete list of every fault ticket submitted through the system.</p>

    <section class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Ticket #</th>
                    <th>Fault Type</th>
                    <th>Address</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Reported</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($all_issues) > 0): ?>
                    <?php foreach ($all_issues as $issue): ?>
                        <?php
                            $priority = htmlspecialchars($issue['priority'] ?? 'Low');
                            $status   = htmlspecialchars($issue['status'] ?? 'Pending');
                        ?>
                        <tr>
                            <td data-label="Ticket #" class="ticket-id">
                                #<?php echo htmlspecialchars($issue['ticket_id']); ?>
                            </td>
                            <td data-label="Fault Type">
                                <?php echo htmlspecialchars($issue['incident_type']); ?>
                            </td>
                            <td data-label="Address">
                                <?php echo htmlspecialchars($issue['incident_address']); ?>
                            </td>
                            <td data-label="Priority">
                                <span class="badge badge-<?php echo $priority; ?>">
                                    <?php echo $priority; ?>
                                </span>
                            </td>
                            <td data-label="Status">
                                <span class="status status-<?php echo $status; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td data-label="Reported" class="muted">
                                <?php echo date('d M Y, H:i', strtotime($issue['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="empty-row">
                        <td colspan="6">No faults have been reported yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

</body>
</html>