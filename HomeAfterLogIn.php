<?php

session_start();
include("dbconnection.php");

// Get logged-in user's information
$displayName = trim(
    ($_SESSION['firstname'] ?? '') . ' ' .
    ($_SESSION['surname'] ?? '')
);

$calendarNotices = [];

$sqlCalendar = "SELECT title, messagenotice, notice_date
                FROM notices
                WHERE statusnotice = 'open'
                AND notice_date IS NOT NULL
                ORDER BY notice_date ASC";

$resultCalendar = mysqli_query($conn, $sqlCalendar);

if ($resultCalendar) {
    // Fetch all approved notices and group them by date
    while ($rowCalendar = mysqli_fetch_assoc($resultCalendar)) {
        $date = $rowCalendar['notice_date'];

        // Initialize the array for this date if it doesn't exist
        if (!isset($calendarNotices[$date])) {
            $calendarNotices[$date] = [];
        }

        // Add the notice to the array for this date
        $calendarNotices[$date][] = [
            'title' => $rowCalendar['title'],
            'content' => $rowCalendar['messagenotice'],
            ];  
        }
}


$userRole = $_SESSION['userrole'] ?? 'Public';

/* Count all reported issues (Staistics)*/
$sqlReported = "SELECT COUNT(*) AS total FROM faultticket";
$resultReported = mysqli_query($conn, $sqlReported);
$rowReported = mysqli_fetch_assoc($resultReported);
$issuesReported = $rowReported['total'];

/* Count resolved issues */
$sqlResolved = "SELECT COUNT(*) AS total FROM faultticket WHERE status = 'Resolved'";
// Execute the query and fetch the result
$resultResolved = mysqli_query($conn, $sqlResolved);
// Fetch the count of resolved issues
if (!$resultResolved) {
    die("Error fetching resolved issues: " . mysqli_error($conn));
}
// Fetch the count of resolved issues
$rowResolved = mysqli_fetch_assoc($resultResolved);
$issuesResolved = $rowResolved['total']?? 0; // Default to 0 if no resolved issues found


   //FETCH DAM LEVELS//
   
$dams = [];

$sqlDams = "SELECT id, dam_name, level, updated_at
            FROM dam_levels
            ORDER BY dam_name ASC
            LIMIT 4";

$resultDams = mysqli_query($conn, $sqlDams);

if ($resultDams) {
    while ($rowDam = mysqli_fetch_assoc($resultDams)) {
        $dams[] = $rowDam;
    }
}

function damStatus(float $level): string {
    if ($level >= 70) return 'Stable';
    if ($level >= 40) return 'Moderate';
    return 'Low';
}

//search
$search = $_GET['search'] ?? '';
if ($search == "") {
    echo "<p></p>";
} else {

    $searchTerm = "%" . $search . "%";

    $sql = "SELECT *
            FROM faultticket
            WHERE title LIKE ?
            OR incident_type LIKE ?
            OR incident_description LIKE ?
            OR priority LIKE ?
            OR ward_number LIKE ?
            OR incident_address LIKE ?
            OR reported_by LIKE ?
            OR reported_access LIKE ?
            OR status LIKE ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sssssssss",
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    );

    $stmt->execute();

    $result = $stmt->get_result();

    echo "<p>Search results for: <strong>" .
         htmlspecialchars($search) .
         "</strong></p>";

    if ($result->num_rows > 0) {

        while ($row = $result->fetch_assoc()) {

            echo "<div class='result'>";

            echo "<h2>" .
                 htmlspecialchars($row['title']) .
                 "</h2>";

            echo "<p><strong>Incident Type:</strong> " .
                 htmlspecialchars($row['incident_type']) .
                 "</p>";

            echo "<p><strong>Description:</strong> " .
                 htmlspecialchars($row['incident_description']) .
                 "</p>";

            echo "<p><strong>Ward:</strong> " .
                 htmlspecialchars($row['ward_number']) .
                 "</p>";

            echo "<p><strong>Address:</strong> " .
                 htmlspecialchars($row['incident_address']) .
                 "</p>";

            echo "<p><strong>Priority:</strong> " .
                 htmlspecialchars($row['priority']) .
                 "</p>";

            echo "<p><strong>Status:</strong> " .
                 htmlspecialchars($row['status']) .
                 "</p>";

            echo "</div>";
        }

    } else {

       echo "<p>No results found.</p>";

    }

    $stmt->close();
}

?>

<html>
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Makana Municipality</title>
    <link rel="stylesheet" href="home 1.css">
    </head>

<body>

<div class="page-overlay" id="pageOverlay"></div>

<nav class="floating-nav" aria-label="Main">



    <!-- CENTER: Nav links -->
    <ul class="main-nav" id="mainNav">

        <li>
            <a href="HomeAfterLogin.php">
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
<button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu"> ☰</button>

</nav>

<!-- Main page content -->

<!-- Slideshow -->
<section class="slideshow">

    <div class="slide active">
        <img src="Church Square.png" alt="Makana Municipality">
        <div class="slide-text">
            <h1>Welcome to Makana Municipality</h1>
            <p>Connecting our community with better municipal services.</p>
            <a href="about.php" class="slide-button">Learn More</a>
        </div>
    </div>

    <div class="slide">
        <img src="Makhanda View.jpg" alt="Makhanda">
        <div class="slide-text">
            <h1>Our Community</h1>
            <p>Stay informed about developments and services in your ward.</p>
            <a href="viewwards.php" class="slide-button">View Wards</a>
        </div>
    </div>

    <div class="slide">
        <img src="services.jpg" alt="Municipal Services">
        <div class="slide-text">
            <h1>Report a Fault</h1>
            <p>Report water, electricity, roads and other municipal issues.</p>
            <a href="reportfault.php" class="slide-button">Report Now</a>
        </div>
    </div>

    <!-- Arrows -->
    <button class="prev" onclick="changeSlide(-1)">&#10094;</button>
    <button class="next" onclick="changeSlide(1)">&#10095;</button>

    <!-- Dots -->
    <div class="dots">
        <span class="dot active-dot" onclick="currentSlide(0)"></span>
        <span class="dot" onclick="currentSlide(1)"></span>
        <span class="dot" onclick="currentSlide(2)"></span>
    </div>

</section>
<!-- End of Slideshow -->
 
<!--display the user's name and role-->
<div class="welcome-card">
    <p>Welcome, <?php echo htmlspecialchars($displayName); ?>!</p>
    <p>Role: <?php echo htmlspecialchars($_SESSION['userrole'] ?? 'Public'); ?></p>
</div>

<!-- Statistics Section -->
<section class="statistics">

    <div class="stat-card">
        <h2> <?php echo $issuesReported; ?></h2>
        <p>Issues Reported</p>
    </div>

    <div class="stat-card">
        <h2>  <?php echo $issuesResolved; ?></h2>
        <p>Issues Resolved</p>
    </div>

    <div class="stat-card">
        <h2>14</h2>
        <p>Wards Connected</p>
    </div>

    <div class="stat-card">
        <?php
        if ($issuesReported > 0) {
            $resolutionRate = round(($issuesResolved / $issuesReported) * 100);
        } else {
            $resolutionRate = 0;
        }
        ?>

        <h2><?php echo $resolutionRate; ?>%</h2>
        <p>Resolution Rate</p>
    </div>
</section>

<!--Row 1 Info-->
<section class="info-row">

    <!-- Calendar -->
    <section class="calendar-section">
    <h2> Calendar </h2> 
    <div class="calendar-container">
        <div class="calendar-header">
            <button type="button" onclick="previousMonth()">‹</button>
            <h3 id="calendarMonth"></h3>
            <button type="button" onclick="nextMonth()">›</button>

        </div>

        <div class="days">
            <span>Sun</span>
            <span>Mon</span>
            <span>Tue</span>
            <span>Wed</span>
            <span>Thu</span>
            <span>Fri</span>
            <span>Sat</span>
        </div>

        <div class="dates" id="calendarDates"></div>

    </div>

</section>

<section class="weather-section"> 
    <div class="weather-container"> 
        
    <!-- Weather Header --> 
     <div class="weather-header"> 
        <div> 
            <p class="weather-label"> WEATHER </p> 
            <h2> Makhanda </h2> 
            <p id="weather-date"> Loading date... </p> 
        </div> 
        
        <div class="weather-location"> 📍 Makhanda </div>
    </div> 
    
    <!-- Loading --> 
    <div id="weather-loading" class="weather-loading"> Loading weather... </div> 
    
    <!-- Weather Content --> 
    <div id="weather-content" class="weather-content"> 
        <!-- Main Weather --> 
         <div class="weather-main"> 
            <div id="weather-icon" class="weather-icon"> ☀️ </div> 
            <div> 
                <div class="temperature"> 
                    <span id="current-temperature"> -- </span> 
                    <span class="temperature-unit"> °C </span> 
                </div> 
                
                <p id="weather-description"> Loading... </p> 
            </div> </div> 
            
            <!-- Weather Details --> 
            <div class="weather-details"> 
                <div class="weather-detail"> 
                    <span class="detail-icon"> ↑ </span> 
                    <div> 
                        <span class="detail-label"> High </span> 
                        <strong id="max-temperature"> --°C </strong> 
                    </div> 
                </div> 
                <div class="weather-detail"> 
                    <span class="detail-icon"> ↓ </span> 
                    <div> <span class="detail-label"> Low </span> 
                    <strong id="min-temperature"> --°C </strong> 
                </div> </div> <div class="weather-detail"> 
                    <span class="detail-icon"> 💧 </span> 
                    <div> 
                        <span class="detail-label"> Rain </span> 
                        <strong id="rain-probability"> --% </strong> 
                    </div> 
                </div> 
                
                <div class="weather-detail"> 
                    <span class="detail-icon"> 💨 </span> 
                    <div> 
                        <span class="detail-label"> Wind </span> 
                        <strong id="wind-speed"> -- km/h </strong> 
                    </div> 
                </div> 
            </div> 
        </div> 
        
        <!-- 7 DAY FORECAST --> 
        <div class="forecast-section"> 
            <h3> 7-Day Forecast </h3> 
            <div id="forecast-container" class="forecast-container"> 
                
            <!-- JavaScript inserts forecast cards --> 
            </div> 
        </div>
    </div> 
</section> 


<!-- Dam Levels -->
<section class="dam-levels-section">
    <h2>Dam Levels</h2>
    <?php if (count($dams) > 0): ?>
        <div class="dam-grid">
            <?php foreach ($dams as $dam): ?>
                <?php
                $level  = (float) $dam['level'];
                $status = damStatus($level);
                ?>

                <div class="dam-level">
                    <h4><?php echo htmlspecialchars($dam['dam_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                    <div class="dam-percentage"><?php echo $level; ?>%</div>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $level; ?>%;"></div>
                    </div>

                    <p>Current Water Level</p>
                    <span class="status status-<?php echo strtolower($status); ?>">
                        <?php echo $status; ?>
                    </span>
                    <?php if (!empty($dam['updated_at'])): ?>
                        <div class="dam-updated">
                            Updated: <?php echo date('d M Y, H:i', strtotime($dam['updated_at'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No dam data available at the moment.</p>
    <?php endif; ?>
    
</section>
</section>
</section>

<!--  NOTICE BOARD -->
<section class="notice-board">
    <div class="notice-heading">
        <h2>Notice Board</h2>
        <p>
            Stay informed about important municipal announcements,
            service updates and community information.
        </p>
    </div>

    <div class="notice-container">
        <?php if (!empty($calendarNotices)): ?>
            <?php foreach ($calendarNotices as $date => $noticesList): ?>
                <?php foreach ($noticesList as $notice): ?>
                    <?php 
                        // Format date into day (e.g., "26") and short month (e.g., "AUG")
                        $day = date('d', strtotime($date));
                        $month = strtoupper(date('M', strtotime($date)));
                    ?>
                    <div class="notice-ticket">
                        <!-- Date Badge -->
                        <div class="notice_date">
                            <span class="date-day"><?php echo $day; ?></span>
                            <span class="date-month"><?php echo $month; ?></span>
                        </div>

                        <!-- Notice Information -->
                        <div class="notice_content">
                            <h3 class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></h3>
                            <p class="notice-text"><?php echo htmlspecialchars($notice['content']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-notices">No upcoming notices scheduled at this time.</p>
        <?php endif; ?>        

            <!-- View All Notices -->
    <div class="notice_button_cont">
        <a href="notices.php" class="view_notices">
            View All Notices
        </a>
    </div>
    </div>

</section>

<!--Footer-->
<footer>
    <div class="footer-container">

        <div class="footer-sec">
            <h3>CONTACT US</h3>
            <p>Tel: <a href="">046 603 6731</a></p>
            <p>Email: <a href="">info@makanamunicipality.gov.za</a></p>
            <p>Postal Address: 1921 High Street, Makhanda, 6139</p>
            <p>WhatsApp Channel: <a href="">Join Channel</a></p>
            <p>Facebook: <a href="https://www.facebook.com/OfficialMakanaMunicipality/">Makana_Municipality</a></p>
        </div>

        <div class="footer-sec">
            <h3>ABOUT US</h3>
            <p><a href="historicalhistory.php">Historical background</a></p>
            <p><a href="about.php">vision and Mission</a></p>
            <p><a href="ward councillors.php">Councilors</a></p>
        </div>  

        <div class="footer-sec">
            <h3>Quick Links</h3>
            <p><a href="about.php">Vision and Mission</a></p>
            <p><a href="trackfault.php">Track Fault</a></p>
            <p><a href="notices.php">View Updates</a></p>
            <p><a href="reports.php">Ward Reports</a></p>
        </div>

        <div class="footer-sec">
            <h3>Physical Address</h3>
            <a href="https://www.bing.com/maps/search?name=Makana+Local+Municipality+Makhanda&trfc=&mepi=0%7E%7EEmbedded%7ELargeMapLink&FORM=MPSRPL&style=r&q=Makana+Local+Municipality+Makhanda&ss=id.ypid%3AYN47AB9989AA88C9DA&ppois=-33.30935287475586_26.52762222290039_Makana+Local+Municipality+Makhanda&cp=-33.309353%7E26.527622&lvl=15">
            <img src="google maps logo.jpg">
            </a>
        </div>  
</footer>

    <section class="bottom-footer">
        <p>Brought to you by Makana Municipality in collaboration with MyMakhanda Tech.<br>
        Makana Municipality Copyright @ 2026, All Rights Reserved.</p>
    </section>

<script src="home 1.js"></script>
</body>
</html>    