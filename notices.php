<?php

session_start();

require_once 'dbconnection.php';

/* ACCESS CONTROL*/


/* STATE & DATA INITIALIZATION*/

$editMode = false;
$editNotice = null;

/*FETCH NOTICE FOR EDITING*/

if (isset($_GET['edit'])) {

    $notice_id = filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT);

    if ($notice_id) {

        $sql = "SELECT * FROM notices WHERE notice_id = ?";

        if ($stmt = mysqli_prepare($conn, $sql)) {

            mysqli_stmt_bind_param($stmt,"i",$notice_id);

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                $editNotice = $row;
                $editMode = true;
            }

            mysqli_stmt_close($stmt);
        }
    }
}

/*FETCH NOTICES
*/

$sqlNotices = "
    SELECT *
    FROM notices
    ORDER BY notice_date DESC, created_at DESC
";

$resultNotices = mysqli_query($conn, $sqlNotices);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notices | Makana Municipality</title>
    <link rel="stylesheet" href="home 1.css">
    <link rel="stylesheet" href="notices.css">
        
</head>

<body>


    <!-- Dim overlay — sits behind the nav, in front of page content -->
<div class="page-overlay" id="pageOverlay"></div>
<nav class="floating-nav" aria-label="Main">

    

    <!-- CENTER: Nav links -->
    <ul class="main-nav" id="mainNav">
        <li>
            <a href="home 1.php">
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
                        <a href="register.html">Register</a>
                        <a href="login.php">Sign in</a>
                        <a href="trackfault.php">My Tickets</a>
                    </div>
                </div>
            </div>
        </li>
    </ul>
  
<!-- HAMBURGER BUTTON -->
<button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu"> ☰</button>
</nav>

    <!-- HEADER -->
    <header class="notice-header">
        <div class="notice-header-content">
            <h1>Municipal Notices</h1>
            <p>
                Stay informed about the latest notices,
                events and meetings in Makhanda.
            </p>
        </div>
    </header>

    <!-- NOTICES -->
    <section class="notices-section">
        <div class="notices-container">

            <?php
            if ($resultNotices && mysqli_num_rows($resultNotices) > 0) {

                while ($notice = mysqli_fetch_assoc($resultNotices)) {
            ?>

                    <!-- NOTICE CARD -->
                    <div class="notice-card">

                        <!-- NOTICE PRIORITY / BADGE -->
                        <?php if (!empty($notice['priority'])) : ?>
                            <div class="notice-type">
                                <?php echo htmlspecialchars($notice['priority']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- NOTICE DATE -->
                        <div class="notice-date">
                            <?php if (!empty($notice['notice_date'])) {echo date("d F Y",strtotime($notice['notice_date']));} ?>
                        </div>

                        <!-- TITLE -->
                        <h2>
                            <?php
                            echo htmlspecialchars($notice['title']);
                            ?>
                        </h2>

                        <!-- CONTENT (messagenotice) -->
                        <div class="notice-content">
                            <?php
                            echo nl2br(htmlspecialchars($notice['messagenotice'] ?? ''));?>
                        </div>

                    </div>

            <?php 
            }

                mysqli_free_result($resultNotices);

            } else {
            ?>

                <!-- NO NOTICES -->
                <div class="no-notices">
                    <h2>No Notices Available</h2>
                    <p>There are currently no municipal notices.</p>
                </div>

            <?php
            }
            ?>

        </div>
    </section>

</body>

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
            <p><a href="updates.php">View Updates</a></p>
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

<!-- JavaScript -->
<script src="home 1.js"></script> 

</html>