<?php
session_start();
require_once "dbconnection.php";

/*check login */
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit;
}

$username = $_SESSION['username'];

/* check the users ward */
$userWard = "";
$userStmt = $conn->prepare("SELECT ward, userrole FROM theusers WHERE username = ? LIMIT 1");
if ($userStmt) {
    $userStmt->bind_param("s", $username);
    $userStmt->execute();

    $userResult = $userStmt->get_result();

    if ($userResult && $userResult->num_rows === 1) {
        $userData = $userResult->fetch_assoc();
        $userWard = trim((string)$userData['ward']);
    }

    $userStmt->close();
}

/*stop if there's no ward*/
if ($userWard === "") {
    die("Your account is not assigned to a ward. Please contact the administrator.");
}

/* sammary statistics */
$totalReports = 0;
$openReports = 0;
$progressReports = 0;
$resolvedReports = 0;
$highPriorityReports = 0;

/* Total reports */
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM faultticket WHERE ward_number = ?");

if ($stmt) {
    $stmt->bind_param("s", $userWard);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $row = $result->fetch_assoc();
        $totalReports = (int)$row['total'];
    }

    $stmt->close();
}

/* Open reports */
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM faultticket WHERE ward_number = ? AND LOWER(TRIM(status)) = 'open'");

if ($stmt) {
    $stmt->bind_param("s", $userWard);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $row = $result->fetch_assoc();
        $openReports = (int)$row['total'];
    }

    $stmt->close();
}

/* In progress */
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM faultticket WHERE ward_number = ? AND LOWER(TRIM(status)) IN ('in progress','in-progress','processing')");

if ($stmt) {
    $stmt->bind_param("s", $userWard);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $row = $result->fetch_assoc();
        $progressReports = (int)$row['total'];
    }

    $stmt->close();
}

/* Resolved */
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM faultticket WHERE ward_number = ? AND LOWER(TRIM(status)) = 'resolved'");

if ($stmt) {
    $stmt->bind_param("s", $userWard);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $row = $result->fetch_assoc();
        $resolvedReports = (int)$row['total'];
    }

    $stmt->close();
}

/* High priority */
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM faultticket WHERE ward_number = ? AND LOWER(TRIM(priority)) = 'high'");

if ($stmt) {
    $stmt->bind_param("s", $userWard);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $row = $result->fetch_assoc();
        $highPriorityReports = (int)$row['total'];
    }

    $stmt->close();
}

/* category counts */
$categories = [
    "Water & Sanitation" => 0,
    "Electricity / Power Outage" => 0,
    "Roads & Potholes" => 0,
    "Refuse / Waste Management" => 0,
    "Street Lighting" => 0,
    "Other" => 0
];

$categoryStmt = $conn->prepare("
    SELECT incident_type, COUNT(*) AS total
    FROM faultticket
    WHERE ward_number = ?
    GROUP BY incident_type");

if ($categoryStmt) {
    $categoryStmt->bind_param("s", $userWard);
    $categoryStmt->execute();

    $categoryResult = $categoryStmt->get_result();

    if ($categoryResult) {
        while ($category = $categoryResult->fetch_assoc()) {
            $categoryName = trim((string)$category['incident_type']);
            $categoryTotal = (int)$category['total'];

            if (array_key_exists($categoryName, $categories)) {
                $categories[$categoryName] = $categoryTotal;
            } else {
                $categories["Other"] += $categoryTotal;
            }
        }
    }

    $categoryStmt->close();
}

/* recent reports */

$reports = [];
$reportStmt = $conn->prepare("
    SELECT 
        ticket_id,
        title,
        incident_type,
        incident_description,
        priority,
        incident_address,
        status,
        created_at
    FROM faultticket
    WHERE ward_number = ?
    ORDER BY created_at DESC");

if ($reportStmt) {
    $reportStmt->bind_param("s", $userWard);
    $reportStmt->execute();

    $reportResult = $reportStmt->get_result();

    if ($reportResult) {
        while ($report = $reportResult->fetch_assoc()) {
            $reports[] = $report;
        }
    }

    $reportStmt->close();
}

$totalCategoryReports = array_sum($categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ward Reports</title>

    <link rel="stylesheet" href="wardreport.css">
</head>

<body>

<!-- Dim overlay — sits behind the nav, in front of page content -->
<div class="page-overlay" id="pageOverlay"></div>

<nav class="floating-nav" aria-label="Main">


    <!-- CENTER: Nav links -->
    <ul class="main-nav" id="mainNav">

        <li>
            <a href="home 1.php">    <svg class="brand-mark" viewBox="0 0 40 40" aria-hidden="true">
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
                        <a href="updates.php">Municipal Updates</a>
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
                        <a href="trackfault.html">Track Fault</a>
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
                        <a href="emergencies.php">Emergencies</a>
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
<button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu"> ☰</button>

</nav>

<main class="reports-page">

    <!-- PAGE HEADER -->
    <section class="page-header">
        <div>
            <h1> Ward <?php echo htmlspecialchars($userWard); ?> Reports</h1>
            <p class="page-description">
                View service issues and reports submitted within your ward.
            </p>
        </div>

        <div class="ward-badge">
            Ward <?php echo htmlspecialchars($userWard); ?>
        </div>
    </section>

    <!-- summary cards-->

    <section class="summary-grid">
        <div class="summary-card">

            <div class="summary-icon">📋</div>

            <div>
                <p>Total Reports</p>
                <h2><?php echo $totalReports; ?></h2>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon">🔓</div>

            <div>
                <p>Open</p>
                <h2><?php echo $openReports; ?></h2>
            </div>

        </div>

        <div class="summary-card">
            <div class="summary-icon">🔧</div>

            <div>
                <p>In Progress</p>
                <h2><?php echo $progressReports; ?></h2>
            </div>

        </div>

        <div class="summary-card">
            <div class="summary-icon">✓</div>
            <div>
                <p>Resolved</p>
                <h2><?php echo $resolvedReports; ?></h2>
            </div>

        </div>

        <div class="summary-card priority-card">
            <div class="summary-icon">!</div>
            <div>
                <p>High Priority</p>
                <h2><?php echo $highPriorityReports; ?></h2>
            </div>

        </div>

    </section>


    <!-- category section-->
    <section class="category-section">

        <div class="section-heading">
            <div>
                <p class="small-heading">SERVICE ISSUES</p>
                <h2>Reports by Category</h2>
            </div>
            <span class="category-total"> <?php echo $totalCategoryReports; ?> reports </span>
        </div>

        <div class="category-grid">

            <?php foreach ($categories as $categoryName => $categoryCount): ?>

                <?php

                if ($totalCategoryReports > 0) {
                    $percentage = ($categoryCount / $totalCategoryReports) * 100;
                } else {
                    $percentage = 0;
                }

                ?>

                <div class="category-card">

                    <div class="category-top">
                        <span class="category-name"> <?php echo htmlspecialchars($categoryName); ?> </span>
                        <strong> <?php echo $categoryCount; ?> </strong>
                    </div>

                    <div class="category-bar">

                        <div
                            class="category-fill"
                            style="width: <?php echo $percentage; ?>%;">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

    </section>


    <!--recent reports -->
    <section class="reports-section">

        <div class="section-heading">
            <div>
                <p class="small-heading">YOUR WARD</p>
                <h2>Recent Reports</h2>
            </div>
        </div>


        <!-- FILTERS -->
        <div class="filters">

            <div class="search-box">
                <span class="search-icon">⌕</span>
                <input type="text" id="reportSearch" placeholder="Search reports...">
            </div>

            <select id="categoryFilter">
                <option value="">All Categories</option>
                <option value="Water & Sanitation"> Water & Sanitation </option>
                <option value="Electricity / Power Outage">  Electricity / Power Outage </option>
                <option value="Roads & Potholes"> Roads & Potholes </option>
                <option value="Refuse / Waste Management"> Refuse / Waste Management </option>
                <option value="Street Lighting">  Street Lighting </option>
                <option value="Other"> Other </option>
            </select>


            <select id="statusFilter">
                <option value="">All Statuses</option>
                <option value="Open">Open</option>
                <option value="In Progress">In Progress</option>
                <option value="Resolved">Resolved</option>
            </select>
        </div>


        <!-- report table -->

        <div class="table-container">

            <table id="reportsTable">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Issue</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($reports) > 0): ?>

                    <?php foreach ($reports as $report): ?>

                        <?php
                        $status = trim((string)$report['status']);
                        $priority = trim((string)$report['priority']);
                        $createdDate = "";
                        if (!empty($report['created_at'])) {
                            $createdDate = date( "d M Y", strtotime($report['created_at']) );
                        }
                        ?>

                        <tr
                            data-category="<?php echo htmlspecialchars($report['incident_type']); ?>"
                            data-status="<?php echo htmlspecialchars($status); ?>" >

                            <td>
                                <span class="ticket-number">  #<?php echo htmlspecialchars($report['ticket_id']); ?> </span>
                            </td>

                            <td>
                                <div class="issue-title">
                                    <?php echo htmlspecialchars($report['title']); ?>
                                </div>

                                <div class="issue-address">
                                    <?php echo htmlspecialchars($report['incident_address']); ?>
                                </div>

                            </td>

                            <td>
                                <?php echo htmlspecialchars($report['incident_type']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($createdDate); ?>
                            </td>

                            <td>
                                <span class="priority priority-<?php echo strtolower(htmlspecialchars($priority)); ?>"> <?php echo htmlspecialchars($priority); ?> </span>
                            </td>

                            <td>
                                <span class="status status-<?php echo strtolower(str_replace(" ", "-", htmlspecialchars($status))); ?>">  <?php echo htmlspecialchars($status); ?> </span>
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="view-button"
                                    onclick="openReport(
                                        <?php echo htmlspecialchars(json_encode($report['ticket_id'])); ?>,
                                        <?php echo htmlspecialchars(json_encode($report['title'])); ?>,
                                        <?php echo htmlspecialchars(json_encode($report['incident_type'])); ?>,
                                        <?php echo htmlspecialchars(json_encode($report['incident_description'])); ?>,
                                        <?php echo htmlspecialchars(json_encode($report['priority'])); ?>,
                                        <?php echo htmlspecialchars(json_encode($report['incident_address'])); ?>,
                                        <?php echo htmlspecialchars(json_encode($report['status'])); ?>,
                                        <?php echo htmlspecialchars(json_encode($createdDate)); ?>
                                    )" > View </button>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="no-reports">
                            <div class="empty-icon">📋</div>
                            <h3>No reports yet</h3>
                            <p>There are currently no service reports recorded for your ward.</p>
                            <a href="reportfault.php" class="report-button">Report an Issue</a>
                        </td>
                    </tr>

                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>


<!-- report detaols modal-->
<div class="modal-overlay" id="reportModal">
    <div class="report-modal">
        <button type="button" class="close-modal" onclick="closeReport()" >   × </button>

        <p class="small-heading">REPORT DETAILS</p>
        <h2 id="modalTitle"></h2>

        <div class="modal-ticket">
            Ticket #<span id="modalTicket"></span>
        </div>

        <div class="modal-grid">
            <div class="detail-item">
                <span>Category</span>
                <strong id="modalCategory"></strong>
            </div>

            <div class="detail-item">
                <span>Date Reported</span>
                <strong id="modalDate"></strong>
            </div>

            <div class="detail-item">
                <span>Priority</span>
                <strong id="modalPriority"></strong>
            </div>

            <div class="detail-item">
                <span>Status</span>
                <strong id="modalStatus"></strong>
            </div>
        </div>

        <div class="detail-block">
            <span>Location</span>
            <p id="modalAddress"></p>
        </div>

        <div class="detail-block">
            <span>Description</span>
            <p id="modalDescription"></p>
        </div>
    </div>
</div>


<!-- Footer -->
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
            <p><a href="historicalbackground.php">Historical Background</a></p>
            <p><a href="about.php">Vision and Mission</a></p>
            <p><a href="ward councillors.php">Ward Councillors</a></p>
        </div>  

        <div class="footer-sec">
            <h3>Quick Links</h3>
            <p><a href="about.php">Vision and Mission</a></p>
            <p><a href="trackfault.php">Track Fault</a></p>
            <p><a href="notices.php">View Updates</a></p>
            <p><a href="WardReports.php">Ward Reports</a></p>
        </div>

        <div class="footer-sec">
            <h3>Physical Address</h3>
            <a href="https://www.bing.com/maps/search?name=Makana+Local+Municipality+Makhanda&trfc=&mepi=0%7E%7EEmbedded%7ELargeMapLink&FORM=MPSRPL&style=r&q=Makana+Local+Municipality+Makhanda&ss=id.ypid%3AYN47AB9989AA88C9DA&ppois=-33.30935287475586_26.52762222290039_Makana+Local+Municipality+Makhanda&cp=-33.309353%7E26.527622&lvl=15" target="_blank" rel="noopener">
                <img src="google maps logo.jpg" alt="Makana Municipality Location Map">
            </a>
        </div> 

    </div> <!-- Added missing closing container div -->
</footer>

<!-- Bottom Footer -->
<section class="bottom-footer">
    <p>Brought to you by Makana Municipality in collaboration with MyMakhanda Tech.<br>
    Makana Municipality Copyright @ 2026, All Rights Reserved.</p>
</section>

<script src="wardreports.js"></script>
<script src="home 1.js"></script>

</body>
</html>