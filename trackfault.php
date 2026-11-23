<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once('dbconnection.php');
} catch (Exception $e) {
    die("<b>Database Connection Error:</b> " . $e->getMessage());
}

/* Check login */
if (!isset($_SESSION['access']) || $_SESSION['access'] !== true) {
    header('Location: login.html');
    exit;
}

$currentuser = trim((string)($_SESSION['username'] ?? ''));

if ($currentuser === '') {
    die("User session could not be identified.");
}

$search = trim((string)($_GET['search'] ?? ''));
$status = trim((string)($_GET['status'] ?? 'All'));
$category = trim((string)($_GET['category'] ?? 'All'));

$allowedStatuses = ['All', 'Open', 'Pending', 'Resolved', 'Closed'];

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'All';
}

$allowedCategories = ['All', 'Water & Sanitation', 'Electricity / Power Outage', 'Roads & Potholes', 'Refuse / Waste Management', 'Street Lighting', 'Other'];

if (!in_array($category, $allowedCategories, true)) {
    $category = 'All';
}

$sql = "SELECT ticket_id, title, incident_type, incident_description, priority, ward_number, incident_address, created_at, status
    FROM faultticket
    WHERE reported_by = ? ";

$params = [$currentuser];
$types = "s";

if ($search !== '') {
    $sql .= "
        AND (
            title LIKE ?
            OR incident_type LIKE ?
            OR incident_description LIKE ?
            OR CAST(ticket_id AS CHAR) LIKE ?) ";

    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if ($category !== 'All') {
    $sql .= " AND incident_type = ? ";
    $params[] = $category;
    $types .= "s";
}

if ($status !== 'All') {
    $sql .= " AND status = ? ";
    $params[] = $status;
    $types .= "s";
}

/* SORTING */
$sql .= "
    ORDER BY
        CASE status
            WHEN 'Open' THEN 1
            WHEN 'Pending' THEN 2
            WHEN 'Resolved' THEN 3
            WHEN 'Closed' THEN 4
            ELSE 5
        END,
        created_at DESC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param($types, ...$params);
$stmt->execute();

$result = $stmt->get_result();
$tickets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$total = count($tickets);

function e($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function statusClass($status): string
{
    return 'status-' . strtolower(trim($status));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Fault | Makana Municipaliy</title>
    <link rel="stylesheet" href="home 1.css">
    <link rel="stylesheet" href="trackfault.css">
</head>
<body>

<header>
    <!-- Navigation bar -->
<!-- Dim overlay that sits behind the nav, in front of page content -->
<div class="page-overlay" id="pageOverlay"></div>

<nav class="floating-nav" aria-label="Main">

    

    <!-- CENTER: Nav links -->
    <ul class="main-nav" id="mainNav">

        <li>
            <a href="homeAfterLogIn.php">
    <svg class="brand-mark" viewBox="0 0 40 40" aria-hidden="true">
        <!-- Circle background -->
        <circle cx="20" cy="20" r="20" fill="#4b1f2a"/>
        <!-- Stylised "M" -->
        <path d="M10 28 L10 14 L15 22 L20 14 L25 22 L30 14 L30 28"
              fill="none" stroke="#ffffff" stroke-width="2.5"
              stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    
    </a>
        </li>

        <!-- ABOUT -->
        <li class="nav-dropdown">
            <button type="button" class="dropdown-link" aria-haspopup="true" aria-expanded="false">
                About
            </button>

            <div class="dropdown-menu" role="menu">
                <div class="dropdown-inner">
                    <div class="dropdown-column">
                        <h3>About Makana</h3>
                        <a href="about.php">Vision and Mission</a>
                        <a href="historicalbackground.php">History</a>
                    </div>

                    <div class="dropdown-column">
                        <h3>Governance</h3>
                        <a href="ward councillors.php">Ward Councillors</a>
                        <a href="departments.php">Municipal Departments</a>
                    </div>

                    <div class="dropdown-column">
                        <h3>Information</h3>
                        <a href="notices.php">Municipal Notices</a>
                        <a href="documents.php">Documents</a>
                    </div>
                </div>
            </div>
        </li>

        <!-- FAULT -->
        <li class="nav-dropdown">
            <button type="button" class="dropdown-link" aria-haspopup="true" aria-expanded="false">
                Fault
            </button>

            <div class="dropdown-menu small-menu" role="menu">
                <div class="dropdown-inner">
                    <div class="dropdown-column">
                        <h3>Report an Issue</h3>
                        <a href="reportfault.php">Report Fault</a>
                        <a href="trackfault.php">Track Fault</a>
                        <a href="reports.php">View Reports</a>
                    </div>
                </div>
            </div>
        </li>

        <!-- SUPPORT -->
        <li class="nav-dropdown">
            <button type="button" class="dropdown-link" aria-haspopup="true" aria-expanded="false">
                Support
            </button>

            <div class="dropdown-menu small-menu" role="menu">
                <div class="dropdown-inner">
                    <div class="dropdown-column">
                        <h3>Contacts &amp; Map</h3>
                        <a href="contact_us.php">Contact Us</a>
                        <a href="emergency contacts.php">Emergencies</a>
                        <a href="Map.JPG">Map</a>
                    </div>
                </div>
            </div>
        </li>

        <!-- PROFILE -->
        <li class="nav-dropdown">
            <button type="button" class="dropdown-link" aria-haspopup="true" aria-expanded="false">
                Profile
            </button>

            <div class="dropdown-menu small-menu" role="menu">
                <div class="dropdown-inner">
                    <div class="dropdown-column">
                        <h3>My Profile</h3>
                        <a href="profile.php">Profile</a>
                        <a href="trackfault.php">My Tickets</a>
                        <a href="logout.php">Sign out</a>
                    </div>
                </div>
            </div>
        </li>
    </ul>
 
  
<!-- HAMBURGER BUTTON -->
<button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu"></button>

</nav>
</header>

<main class="page">

    <section class="page-heading">
        <h1> Track Fault</h1>
        <p> View and monitor the status of the fault reports you have submitted. </p>
    </section>

<form class="filters" method="GET" action="trackfault.php">
    <div class="filter-group">
        <label for="category">Fault Category</label>
        <select name="category" id="category">
            <option value="All" <?php echo ($category === 'All') ? 'selected' : ''; ?>>  All Categories </option>
            <option value="Water & Sanitation" <?php echo ($category === 'Water & Sanitation') ? 'selected' : ''; ?>> Water & Sanitation </option>
            <option value="Electricity / Power Outage" <?php echo ($category === 'Electricity / Power Outage') ? 'selected' : ''; ?>> Electricity / Power Outage </option>
            <option value="Roads & Potholes" <?php echo ($category === 'Roads & Potholes') ? 'selected' : ''; ?>> Roads & Potholes </option>
            <option value="Refuse / Waste Management" <?php echo ($category === 'Refuse / Waste Management') ? 'selected' : ''; ?>> Refuse / Waste Management </option>
            <option value="Street Lighting" <?php echo ($category === 'Street Lighting') ? 'selected' : ''; ?>> Street Lighting </option>
            <option value="Other" <?php echo ($category === 'Other') ? 'selected' : ''; ?>> Other</option>
        </select>
    </div>

    <div class="filter-group">
        <label for="status">Status</label>
        <select name="status" id="status">
            <option value="All" <?php echo ($status === 'All') ? 'selected' : ''; ?>>All Statuses </option>
            <option value="Open" <?php echo ($status === 'Open') ? 'selected' : ''; ?>> Open  </option>
            <option value="Pending" <?php echo ($status === 'Pending') ? 'selected' : ''; ?>> Pending </option>
            <option value="Resolved" <?php echo ($status === 'Resolved') ? 'selected' : ''; ?>> Resolved </option>
            <option value="Closed" <?php echo ($status === 'Closed') ? 'selected' : ''; ?>>Closed</option>
        </select>
    </div>
    <button type="submit" class="filter-button">Filter</button>

</form>
    <section class="report-panel">
        <div class="panel-header">
            <div>
                <h2> My Fault Reports</h2>
                <p> Faults reported using your account.</p>
            </div>
                <span class="ticket-count">
                    <?php echo $total; ?> fault<?php echo ($total == 1) ? '' : 's'; ?>
                </span>
        </div>

        <div class="table-container">

            <table>
                <thead>
                    <tr>
                        <th> ID</th>
                        <th> Title</th>
                        <th> Type</th>
                        <th> Priority</th>
                        <th> Ward</th>
                        <th> Status</th>
                        <th> Reported</th>
                    </tr>
                </thead>

            <tbody>
            <?php if ($total > 0): ?>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td>
                            <?php echo e($ticket['ticket_id']); ?>
                        </td>

                        <td>
                            <strong>
                                <?php echo e($ticket['title']); ?>
                            </strong>
                        </td>

                        <td>
                            <?php echo e($ticket['incident_type']); ?>
                        </td>

                        <td>
                            <span class="priority-<?php echo strtolower(e($ticket['priority'])); ?>">
                                <?php echo e($ticket['priority']); ?>
                            </span>
                        </td>

                        <td>
                            Ward <?php echo e($ticket['ward_number']); ?>
                        </td>

                        <td>
                            <span class="status-badge <?php echo e(statusClass($ticket['status'])); ?>">
                                <?php echo e($ticket['status']); ?>
                            </span>
                        </td>

                        <td>
                            <?php echo e( date( 'd M Y', strtotime($ticket['created_at'])) );?>
                        </td>
                    </tr>

                <?php endforeach; ?>
            <?php else: ?>

                <tr>
                    <td colspan="7" class="no-results">
                        <h3>No fault reports found</h3>
                        <p>
                            <?php if ($search !== ''): ?>
                                No faults matched your search.
                            <?php elseif ($category !== 'All'): ?>
                                You have not reported any faults in this category.
                            <?php elseif ($status !== 'All'): ?>
                                You have no faults with this status.
                            <?php else: ?>
                                You have not reported any faults yet.
                            <?php endif; ?>
                        </p>

                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
            </table>
        </div>
    </section>
</main>

 <!--Footer-->
    <footer>
        <div class="footer-container">
            <div class="footer-sec">
                <h3>CONTACT US</h3>
                <p>Tel: <a href="tel:0466036731">046 603 6731</a></p>
                <p>Email: <a href="mailto:info@makanamunicipality.gov.za">info@makanamunicipality.gov.za</a></p>
                <p>Postal Address: 1921 High Street, Makhanda, 6139</p>
                <p>WhatsApp Channel: <a href="#">Join Channel</a></p>
                <p>Facebook: <a href="https://www.facebook.com/OfficialMakanaMunicipality/">Makana_Municipality</a></p>
            </div>

            <div class="footer-sec">
                <h3>ABOUT US</h3>
                <p><a href="historicalbackground.php">Historical background</a></p>
                <p><a href="about.php">Vision and Mission</a></p>
                <p><a href="ward councillors.php">Councilors</a></p>
            </div>  

            <div class="footer-sec">
                <h3>Quick Links</h3>
                <p><a href="about.php">Vision and Mission</a></p>
                <p><a href="trackfault.php">Track Fault</a></p>
                <p><a href="#">View Updates</a></p>
                <p><a href="reports.php">Ward Reports</a></p>
            </div>

            <div class="footer-sec">
                <h3>Physical Address</h3>
                <a href="https://www.bing.com/maps/search?name=Makana+Local+Municipality+Makhanda&trfc=&mepi=0%7E%7EEmbedded%7ELargeMapLink&FORM=MPSRPL&style=r&q=Makana+Local+Municipality+Makhanda&ss=id.ypid%3AYN47AB9989AA88C9DA&ppois=-33.30935287475586_26.52762222290039_Makana+Local+Municipality+Makhanda&cp=-33.309353%7E26.527622&lvl=15" target="_blank">
                    <img src="google maps logo.jpg" alt="Google Maps Location">
                </a>
            </div>  
        </div>
    </footer>

    <section class="bottom-footer">
        <p>Brought to you by Makana Municipality in collaboration with MyMakhanda Tech.<br>
        Makana Municipality Copyright @ 2026, All Rights Reserved.</p>
    </section>

    <script src="home 1.js"></script>
</body>
</html>