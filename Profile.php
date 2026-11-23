<?php

session_start();

require_once 'dbconnection.php';


/* check if user is logged in */

if (!isset($_SESSION['access']) || empty($_SESSION['username'])) {
    header("Location: login.html");
    exit;
}

$username = $_SESSION['username'];

/*  Get user information form the database */
$sql = "SELECT idnumber, firstname, surname, username, userrole, ward,
               phyAddress, contact, email
        FROM theusers
        WHERE username = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

/* check if user exist*/
if ($result->num_rows !== 1) {
    session_destroy();
    header("Location: login.html");
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile</title>
<link rel="stylesheet" href="home 1.css">


<style>
    .profile-section { display: flex; justify-content: center; align-items: center; padding: 50px 20px; min-height: 70vh;}
    .profile-card { background-color: #ffffff; width: 550px; max-width: 100%; padding: 40px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15); text-align: center;}
    .profile-icon { width: 90px; height: 90px; margin: 0 auto 15px; border-radius: 50%; background-color: #4b1f2a; color: white; display: flex; justify-content: center; align-items: center; font-size: 45px;}
    .profile-card h1 { color: #4b1f2a; font-size: 28px; margin-bottom: 8px;}
    .profile-card > p { color: #555555; margin-bottom: 25px;}

    /* profile details*/
    .profile-details { text-align: left; margin-top: 20px;}
    .profile-details p { font-size: 15px; padding: 13px 5px; margin: 0; border-bottom: 1px solid #e0d5ca;}
    .profile-details strong { color: #4b1f2a; display: inline-block; width: 130px;}

    /* Buttons*/
    .profile-buttons { margin-top: 30px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;}
    .profile-buttons a { display: inline-block; background-color: #4b1f2a; color: white; text-decoration: none; padding: 11px 22px; border-radius: 8px; transition: 0.2s;}
    .profile-buttons a:hover {background-color: #b08d57;}
    .logout-button {background-color: #777 !important;}
    .logout-button:hover {background-color: #555 !important;}

    /* Mobile */
    @media (max-width: 600px) {
        .profile-card { padding: 25px;}
        .profile-details strong {width: 110px;}
    }

</style>

</head>

<body>

<!-- Dim overlay — sits behind the nav, in front of page content -->
<div class="page-overlay" id="pageOverlay"></div>

<nav class="floating-nav" aria-label="Main">

    <!-- LEFT: Brand -->
    <a>
    <svg class="brand-mark" viewBox="0 0 40 40" aria-hidden="true">
        <!-- Circle background -->
        <circle cx="20" cy="20" r="20" fill="#4b1f2a"/>
        <!-- Stylised "M" -->
        <path d="M10 28 L10 14 L15 22 L20 14 L25 22 L30 14 L30 28"
              fill="none" stroke="#ffffff" stroke-width="2.5"
              stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    
    </a>

    <!-- CENTER: Nav links -->
    <ul class="main-nav" id="mainNav">

        <li>
            <a href="home 1.php">Home</a>
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
                        <a href="Councillors.html">Ward Councillors</a>
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
                        <a href="reportfault.html">Report Fault</a>
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
                        <a href="ContactUs.html">Contact Us</a>
                        <a href="contact_us.php">Emergencies</a>
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

    <!-- RIGHT: Search + mobile toggle -->
<form action="" method="GET" style="display:flex; align-items:center; gap:8px;">
<input 
        type="text" 
        id="search" 
        name="search" 
        placeholder="Search..." 
        value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"  >

    <button type="submit" class="search-button" aria-label="Search">
        <svg viewBox="0 0 24 24"
             fill="none"
             stroke="#5f6368"
             stroke-width="2.5"
             stroke-linecap="round"
             stroke-linejoin="round"
             class="search-icon">

            <circle cx="11" cy="11" r="7"></circle>
            <line x1="16.5" y1="16.5" x2="22" y2="22"></line>

        </svg>
    </button>
</form> 
  
<!-- HAMBURGER BUTTON -->
<button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu"> ☰</button>

</nav>

<!--  Profile -->
<section class="profile-section">
<div class="profile-card">
    <div class="profile-icon">
        👤
    </div>

    <h1> My Profile </h1>
    <p> View and manage your account information.</p>

    <!-- Profile information -->
    <div class="profile-details">
        <p> <strong>First Name:</strong>
            <?php echo htmlspecialchars($user['firstname'] ?? ''); ?> </p>
        <p> <strong>Surname:</strong>
            <?php echo htmlspecialchars($user['surname'] ?? ''); ?></p>
        <p> <strong>Username:</strong>
            <?php echo htmlspecialchars($user['username'] ?? ''); ?></p>
        <p> <strong>Email:</strong>
            <?php echo htmlspecialchars($user['email'] ?? ''); ?> </p>
        <p> <strong>Contact:</strong>
            <?php echo htmlspecialchars($user['contact'] ?? ''); ?></p>
        <p> <strong>Address:</strong>
            <?php echo htmlspecialchars($user['phyAddress'] ?? ''); ?> </p>
        <p> <strong>Ward:</strong>
            <?php echo htmlspecialchars($user['ward'] ?? ''); ?> </p>
        <p> <strong>Role:</strong>
            <?php echo htmlspecialchars($user['userrole'] ?? '');?></p>
    </div>

    <!-- Butoons -->
    <div class="profile-buttons">
        <a href="editprofile.php">Edit Profile</a>
        <a href="logout.php" class="logout-button">Log Out</a>
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
            <p><a href="historicalbackground.php">Historical background</a></p>
            <p><a href="about.php">vision and Mission</a></p>
            <p><a href=" ward councillors.php">Councilors</a></p>
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
