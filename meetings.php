<?php
session_start();
require_once 'dbconnection.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch ALL events ordered by date
$events = [];
$sql = "SELECT * FROM calender_events ORDER BY event_date ASC";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Upcoming Meetings</title>
    <style>
        /* Reusing the same CSS variables from your dashboard */
        :root {
            --background: #f6efe6;
            --white: #ffffff;
            --wine: #4b1f2a;
            --gold: #b08d57;
            --text: #333333;
            --muted: #777777;
            --line: #e1ddd7;
        }
        body { 
            font-family: Arial, sans-serif; 
            background-color: var(--background); 
            color: var(--text); 
            margin: 0; 
            padding: 0;
        }

        /* TOP HEADER */
        .topbar {
            height: 75px; width: 100%; background-color: var(--wine); color: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 35px; border-bottom: 1px solid #ddd; box-sizing: border-box;
        }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand img { width: 52px; height: 52px; object-fit: contain; background-color: white; border-radius: 50%; }
        .brand strong { display: block; font-size: 1.05rem; letter-spacing: 0.04em; }
        .brand span { display: block; color: #e9d9c1; font-size: 0.78rem; }
        .logout a {
            color: white; text-decoration: none; font-size: 0.875rem; font-weight: 500;
            padding: 8px 16px; border-radius: 6px; border: 2px solid var(--gold);
            background-color: var(--wine); transition: all 0.2s ease;
        }
        .logout a:hover { background-color: var(--gold); color: white; }

        /* MAIN CONTENT */
        .main-content { width: 100%; max-width: 1200px; margin: auto; padding: 40px; box-sizing: border-box; }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .header-section h1 { font-size: 28px; margin: 0; color: var(--wine); }
        
        .back-btn {
            background-color: var(--wine);
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .back-btn:hover { background-color: #3a1720; }

        /* TABLE STYLES */
        .table-card {
            background-color: var(--white);
            border-radius: 12px;
            border: 1px solid var(--line);
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        thead th {
            background-color: var(--wine);
            color: white;
            text-align: left;
            padding: 14px 12px;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        thead th:first-child { border-top-left-radius: 6px; }
        thead th:last-child { border-top-right-radius: 6px; }

        tbody td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--line);
            vertical-align: middle;
        }
        tbody tr:hover { background-color: #faf5ec; }
        tbody tr:last-child td { border-bottom: none; }

        .event-title { font-weight: bold; color: var(--wine); }
        .muted { color: var(--muted); font-size: 0.82rem; }

        .empty-row td {
            text-align: center;
            padding: 40px;
            color: var(--muted);
            font-style: italic;
        }

        @media (max-width: 800px) {
            .main-content { padding: 20px; }
            .header-section { flex-direction: column; align-items: flex-start; gap: 15px; }
            thead { display: none; }
            tbody tr { display: block; border: 1px solid var(--line); border-radius: 8px; margin-bottom: 15px; padding: 10px; }
            tbody td { display: flex; justify-content: space-between; border: none; padding: 8px 5px; }
            tbody td::before { content: attr(data-label); font-weight: bold; color: var(--muted); font-size: 0.75rem; text-transform: uppercase; margin-right: 15px; }
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="topbar">
    <div class="brand">
        <img src="Makana-Municipality logo.png" alt="Makana Municipality logo">
        <div><strong>Makana Municipality</strong><span>Councillor Workspace</span></div>
    </div>
    <div class="logout">
        <a href="councillor.php">Back to Dashboard</a>
    </div>
</header>

<!-- MAIN CONTENT -->
<main class="main-content">

    <div class="header-section">
        <h1>All Municipal Meetings & Events</h1>
        <a href="councillor.php" class="back-btn">← Back to Dashboard</a>
    </div>

    <section class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Event Title</th>
                    <th>Location</th>
                    <th>Date & Time</th>
                    <th>Created By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($events) > 0): ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td class="muted">#<?php echo htmlspecialchars($event['id']); ?></td>
                            <td class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></td>
                            <td><?php echo htmlspecialchars($event['event_location']); ?></td>
                            <td><?php echo date('l, d M Y', strtotime($event['event_date'])); ?></td>
                            <td class="muted"><?php echo htmlspecialchars($event['created_by']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="empty-row">
                        <td colspan="5">No events have been scheduled yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

</main>

</body>
</html>