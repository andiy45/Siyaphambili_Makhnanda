<?php
include("dbconnection.php");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Fault - Makana Municipality</title>
    <link rel="stylesheet" href="home 1.css">
<style>
        /* General Styles */
    *, *::before, *::after {
        box-sizing: border-box;
    }

    html, body {
        margin: 0 auto;
        padding: 0;
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text",
                     "Helvetica Neue", Helvetica, Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
        color: #1d1d1f;
        background: #f5f5f7;
    }

    body {
        padding-top: 65px;
    }

    /* ── Back button — fixed to top-left corner ── */
.back-button {
    position: fixed;
    top: 15px;
    left: 120px;
    z-index: 10000;             
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    background: #4b1f2a;
    color: #ffffff;
    text-decoration: none;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(75, 31, 42, 0.25);
    transition: background 0.2s ease, transform 0.2s ease;
}

.back-button:hover {
    background: #631d2c;
    transform: translateX(-2px);
}

.back-button svg {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
}

/* On mobile, keep it a bit smaller so it doesn't crowd the brand */
@media (max-width: 860px) {
    .back-button {
        top: 12px;
        left: 10px;
        padding: 6px 10px;
        font-size: 13px;
    }
}

    .floating-nav {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        margin: 0 auto;
        height: 65px;
        background: rgba(255, 255, 255, 0.97);
        -webkit-backdrop-filter: saturate(180%) blur(20px);
        backdrop-filter: saturate(180%) blur(20px);
        box-shadow: 0 4px 20px rgba(255, 255, 255, 0.99);
        display: flex;
        align-items: center;
        justify-content: center; /* center the main nav */
        padding: 0 25px;
        z-index: 9999;
    }

    .brand {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: #4b1f2a;
        font-size: 17px;
        font-weight: 600;
        letter-spacing: -0.02em;
        white-space: nowrap;
        transition: opacity 0.2s ease;
    }

    .brand:hover { 
      opacity: 0.7; 
    }
    .brand-mark {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
        display: block;
        transition: transform 0.25s ease;
    }

    .brand:hover .brand-mark {
        transform: rotate(-6deg) scale(1.05);
    }

    .brand-text {
        font-size: 17px;
        font-weight: 600;
        letter-spacing: -0.02em;
    }
    .main-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 25px;
        list-style: none;
        margin: 0;
        padding: 0;
        height: 100%;
    }

    .main-nav > li {
        display: flex;
        align-items: center;
        height: 100%;
    }

    .main-nav > li > a,
    .main-nav > li > .dropdown-link {
        text-decoration: none;
        color: #4b1f2a;
        font-size: 15px;
        font-weight: 500;
        background: transparent;
        border: none;
        padding: 0;
        cursor: pointer;
        font-family: inherit;
        white-space: nowrap;
        height: 100%;
        display: inline-flex;
        align-items: center;
        transition: opacity 0.2s ease;
    }

    .main-nav > li > a:hover,
    .main-nav > li > .dropdown-link:hover {
        opacity: 0.65;
    }

    .nav-dropdown {
        position: relative;
        height: 100%;
        display: flex;
        align-items: center;
    }

    .dropdown-menu {
        position: fixed;
        top: 65px;                     /* 15px top + 65px bar height */
        left: 0;
        right: 0;
        width: 100vw;
        background: rgba(255, 255, 255, 0.97);
        -webkit-backdrop-filter: saturate(180%) blur(20px);
        backdrop-filter: saturate(180%) blur(20px);
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
        padding: 30px 0;
        display: inline-flex;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(8px);
        transition:
            opacity 0.25s ease,
            transform 0.25s ease,
            visibility 0s linear 0.25s;
        z-index: 9999;
    }

    /* Invisible hover bridge - fills the gap between the nav bar
       bottom (at 80px) and the top of the menu, so moving the mouse
       down doesn't close the menu. */
    .dropdown-menu::before {
        content: "";
        position: absolute;
        top: -20px;
        left: 0;
        right: 0;
        height: 20px;
    }

    .nav-dropdown:hover .dropdown-menu,
    .nav-dropdown.is-open .dropdown-menu {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0);
        transition:
            opacity 0.25s ease,
            transform 0.25s ease,
            visibility 0s linear 0s;
    }

    .dropdown-inner {
        width: 92%;
        max-width: 1200px;
        padding: 0 25px;
        display: flex;
        gap: 60px;
    }

    .dropdown-menu.small-menu .dropdown-inner {
        justify-content: flex-start;
    }

    /*DROPDOWN COLUMNS*/
    .dropdown-column {
        display: flex;
        flex-direction: column;
        min-width: 180px;
    }

    .dropdown-column h3 {
        font-size: 12px;
        font-weight: 600;
        color: #4b1f2a;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0 0 12px;
    }

    .dropdown-column a {
        text-decoration: none;
        color: #555;
        font-size: 14px;
        padding: 7px 0;
        transition: color 0.2s, padding-left 0.2s;
    }

    .dropdown-column a:hover {
        color: #661501;
        padding-left: 5px;
    }

    /* PAGE DIM OVERLAY (Apple-style) */
    .page-content {
    transition: filter 0.35s ease, transform 0.35s ease;
    transform-origin: top center;
    will-change: filter;
    }

   /* Applied when a menu is open */
    body.menu-open .page-content {
    filter: blur(8px);
    transform: scale(0.98);
    pointer-events: none;   /* prevents clicking blurred content */
    user-select: none;      /* prevents selecting blurred content */
    }

    .page-overlay {
        position: fixed;
        inset: 0;
        background: rgba(255, 255, 255, 0);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.25s ease, visibility 0s linear 0.25s;
        z-index: 9997;
        -webkit-backdrop-filter: saturate(180%) blur(20px);
        backdrop-filter: saturate(180%) blur(20px);
    }

    body.menu-open .page-overlay {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transition: opacity 0.25s ease, visibility 0s linear 0s;
    }

    /*SEARCH BUTTON*/
    .search-button {
        background: transparent;
        border: none;
        padding: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.2s ease;
    }

    .search-button:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .search-icon {
        width: 16px;
        height: 16px;
        display: block;
    }

    /* HAMBURGER MENU BUTTON */
    .menu-toggle {
        display: none;
        background: transparent;
        border: none;
        color: #4b1f2a;
        font-size: 26px;
        cursor: pointer;
        padding: 6px;
        line-height: 1;
    }

    /* Position brand and menu controls off the centered nav */
    .floating-nav .brand { position: absolute; left: 25px; z-index: 10001; }
    .floating-nav .menu-toggle { position: absolute; right: 25px; z-index: 10001; }

 /*MOBILE*/
    @media (max-width: 860px) {
        .menu-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .floating-nav { padding: 0 16px; }

        /* Hide the desktop row */
        .main-nav { display: none; }

        /* NEW: the mobile panel — a full-width sheet directly
          under the floating bar */
        .main-nav.is-open {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            position: fixed;                 
            top: 65px;                       
            left: 0;                        
            right: 0;
            width: 100vw;                 
            max-height: calc(100vh - 110px);
            overflow-y: auto;                
            background: rgba(255, 255, 255, 0.97);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            backdrop-filter: saturate(180%) blur(20px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            padding: 12px 20px;
            gap: 0;
            height: auto;
            z-index: 9999;                   
        }

        .main-nav > li {
            width: 100%;
            display: block;
            height: auto;
        }

        .main-nav > li > a,
        .main-nav > li > .dropdown-link {
            display: block;
            padding: 12px 0;
            width: 100%;
            text-align: left;
            font-size: 16px;
            height: auto;
            color: #4b1f2a;
        }

        /* Kill the full-width mega menu on mobile */
        .dropdown-menu,
        .dropdown-menu.small-menu {
            position: static;
            width: 100%;
            transform: none;
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            box-shadow: none;
            backdrop-filter: none;
            border-bottom: none;
            padding: 0;
            background: transparent;
            display: none;
        }

        .dropdown-menu::before { display: none; }

        .nav-dropdown.is-open .dropdown-menu {
            display: block;
        }

        .dropdown-inner {
            width: 100%;
            padding: 0 0 12px 12px;
            flex-direction: column;
            gap: 0;
        }

        .dropdown-column h3 { margin-top: 10px; }

        /* NEW: show hamburger, hide its rotation when closed */
        .menu-toggle { display: inline-flex; }

        .page-overlay { display: none; }
    }
    html, body {
        width: 100%;
        height: 100%;
       background-color:  #f6efe6;
    }



        /* Form Container Styling */
        .main-container {
            max-width: 800px;
            margin: 40px auto;
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 1px 4px 10px #4b1f2a;
        }

        .main-container h2 {
            color: #4b1f2a;
            margin-top: 0;
            border-bottom: 2px solid #b08d57;
            padding-bottom: 10px;
        }

        .main-container::before {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: rgba(75, 31, 42, 0.06);
            top: 80px;
            left: -180px;
            pointer-events: none;
        }

        .main-container::after {
            content: "";
            position: absolute;
            width: 350px;
            height: 350px;
            border-radius: 50%;
            background: rgba(64, 224, 208, 0.08);
            bottom: 30px;
            right: -130px;
            pointer-events: none;
        }
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
        }

        .form-group input[type="file"] {
            margin-top: 5px;
        }

        .submit-btn {
            background-color: #4b1f2a;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background-color: #b08d57;
        }

</style>
</head>
<body>

<header>
    <!-- Navigation bar -->
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
                        <a href="ward waouncillors.php">Ward Councillors</a>
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
                        <a href="contact_us.html">Contact Us</a>
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
                        <a href="trackfault.html">My Tickets</a>
                    </div>
                </div>
            </div>
        </li>
    </ul>
  
<!-- HAMBURGER BUTTON -->
<button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu"> ☰</button>

</nav>
</header>

    <!--Main Fault Reporting Section-->
    <main class="main-container">
        <h2>Report a Service Fault</h2>
        
        <form action="new_fault.php" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="issuetitle">Issue Title *</label>
                <input type="text" id="issuetitle" name="issuetitle" placeholder="e.g., Water Pipe Burst on High Street" required>
            </div>

            <div class="form-group">
                <label for="incident_type">Incident Type *</label>
                <select id="incident_type" name="incident_type" required>
                    <option value="" disabled selected>Select category...</option>
                    <option value="Water & Sanitation">Water & Sanitation</option>
                    <option value="Electricity / Power Outage">Electricity / Power Outage</option>
                    <option value="Roads & Potholes">Roads & Potholes</option>
                    <option value="Refuse / Waste Management">Refuse / Waste Management</option>
                    <option value="Street Lighting">Street Lighting</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="ward">Ward Number *</label>
                <select id="ward" name="ward" required>
                    <option value="" disabled selected>Select ward...</option>
                    <option value="1">Ward 1</option>
                    <option value="2">Ward 2</option>
                    <option value="3">Ward 3</option>
                    <option value="4">Ward 4</option>
                    <option value="5">Ward 5</option>
                    <option value="6">Ward 6</option>
                    <option value="7">Ward 7</option>
                    <option value="8">Ward 8</option>
                    <option value="9">Ward 9</option>
                    <option value="10">Ward 10</option>
                    <option value="11">Ward 11</option>
                    <option value="12">Ward 12</option>
                    <option value="13">Ward 13</option>
                    <option value="14">Ward 14</option>
                </select>
            </div>

            <div class="form-group">
                <label for="physAddress">Physical Address / Location *</label>
                <input type="text" id="physAddress" name="physAddress" placeholder="e.g., 1921 High Street, Makhanda" required>
            </div>

            <div class="form-group">
                <label for="priority">Priority Level *</label>
                <select id="priority" name="priority" required>
                    <option value="" disabled selected>Select priority...</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>

            <div class="form-group">
                <label for="incident_description">Detailed Description *</label>
                <textarea id="incident_description" name="incident_description" placeholder="Provide additional details regarding the fault..." required></textarea>
            </div>

            <div class="form-group">
                <label for="picture">Attach Photo (Optional - Max 5MB)</label>
                <input type="file" id="picture" name="picture" accept="image/jpeg, image/png, image/gif, image/webp">
            </div>

            <button type="submit" class="submit-btn">Submit Report</button>
        </form>
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
                <p>Facebook: <a href="#">Makana_Municipality</a></p>
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