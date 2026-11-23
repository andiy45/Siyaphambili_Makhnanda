<?php

include("dbconnection.php");

//search
$search = $_GET['search'] ?? '';
if ($search == "") {

    echo "<p>Please enter something to search.</p>";

} else {

    $searchTerm = "%" . $search . "%";

    $sql = "SELECT *
            FROM faultticket
            WHERE title LIKE ?
            OR incident_type LIKE ?
            OR incident_description LIKE ?
            OR priority LIKE ?
            OR ward_number LIKE ?
            OR picture LIKE ?
            OR incident_address LIKE ?
            OR reported_by LIKE ?
            OR reported_access LIKE ?
            OR status LIKE ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sssssbssss",
        $searchTerm,
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


            echo "<p><strong>Priority:</strong> " . htmlspecialchars($row['priority']) ."</p>";

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

</head>
<html>
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Makana Municipality</title>
    <link rel="stylesheet" href="home 1.css">
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
                        <a href="login.html">Sign in</a>
                        <a href="trackfault.php">My Tickets</a>
                    </div>
                </div>
            </div>
        </li>
    </ul>

    <!-- RIGHT: Search + mobile toggle -->
<form action="search_results.php" method="GET" 
style="display:flex; align-items:center; gap:8px;" onsubmit="return searchNotice();">
<input 
        type="text" 
        id="search" 
        name="search" 
        placeholder="Search..." 
        value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"   
    >

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


<body>
    <!--Footer-->
<footer>
    <div class="footer-container">

        <div class="footer-sec">
            <h3>CONTACT US</h3>
            <p>Tel: <a href="">046 603 6731</a></p>
            <p>Email: <a href="">info@makanamunicipality.gov.za</a></p>
            <p>Postal Address: 1921 High Street, Makhanda, 6139</p>
            <p>WhatsApp Channel: <a href="">Join Channel</a></p>
            <p>Facebook: <a href="">Makana_Municipality</a></p>
        </div>

        <div class="footer-sec">
            <h3>ABOUT US</h3>
            <p><a href="">Historical background</a></p>
            <p><a href="">vision and Mission</a></p>
            <p><a href="Councillors.html">Councilors</a></p>
        </div>  

        <div class="footer-sec">
            <h3>Quick Links</h3>
            <p><a href="">Vision and Mission</a></p>
            <p><a href="">Track Fault</a></p>
            <p><a href="">View Updates</a></p>
            <p><a href="">Ward Reports</a></p>
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


</body>
</html>