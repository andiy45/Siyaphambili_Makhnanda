<?php

?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>

    <style>


    /*GLOBAL RESETS*/
    *, *::before, *::after {
        box-sizing: border-box;
    }

    html, body {
        margin: 0;
        padding: 0;
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text",
                     "Helvetica Neue", Helvetica, Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
        color: #1d1d1f;
        background: #f5f5f7;
    }

    body {
        padding-top: 110px;
    }

     /* ── Back button — fixed to top-left corner ── */
.back-button {
    position: fixed;
    top: 15px;
    left: 120px;
    z-index: 10000;              /* above the floating nav */
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
        justify-content: space-between;
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
        gap: 25px;
        list-style: none;
        margin: 0 auto;
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
    }


/*Contact Us*/
.contacts{
    width: 80%;
    margin: 50px auto;
}

.contacts h2{
    color: #4b1f2a;
    font-weight: 500;
    border-bottom: 2px solid #40a84b;
}

.contact-details, .municipal-contacts {
    width: 50%;
}

.contact-details h3, .municipal-contacts h3 {
    color: #4b1f2a;
    font-size: 22px;
    font-weight: 500;
    padding-bottom: 15px;
    border-bottom: 2px solid #40a84b;
    margin-bottom: 20px;
}

.contact-details p, .municipal-contacts p {
    color: #222;
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 15px;
}

.contact-details td, .municipal-contacts td {
    border: 1px solid #c9c9c9;
    height: 40px;
}

.contact-details a, .municipal-contacts a {
    color: #4B1F2A;
    text-decoration: none;
}

.contact-details a:hover, .municipal-contacts a:hover {
    color: #40a84b;
    text-decoration: underline;
}

.contact-details table, .municipal-contacts table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}

/*Footer*/
        footer{
            background-color: #4b1f2a;
            color: white;
            padding: 30px 50px 10px
        }

        .footer-container{
            display:flex;
            justify-content: space-between;
            gap:30px;
        }

        .footer-sec{
            flex:1;
        }

        .footer-sec a{
            color: white;
            text-decoration: none;
        }

        .footer-sec a:hover {
            text-decoration: underline;
        }

        .footer-sec img {
            width: 145px;
            height: 145px;
            object-fit: cover;
            cursor: pointer;
            transition: 0.3s;
        }

        .footer-sec img:hover {
            transform: scale(1.05);
        }

        .bottom-footer {
            background-color: #6B2F3A;
            text-align: center;
            padding: 15px;
            color: white;
        }

        .bottom-footer p {
            margin: 0;
            color: #ffff;
        } 

        section nav {
            height: 60%;
            background-color: #f6efe6;
        }
    </style>
</head>
<body>


<!-- Header -->
<!-- Dim overlay — sits behind the nav, in front of page content -->
<div class="page-overlay" id="pageOverlay"></div>

<nav class="floating-nav" aria-label="Main">


    <!-- CENTER: Nav links -->
    <ul class="main-nav" id="mainNav">

        <li>
            <a href="newhead.html">
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
                        <a href="about.html">Vision and Mission</a>
                        <a href="historicalbackground.html">History</a>
                    </div>

                    <div class="dropdown-column">
                        <h3>Governance</h3>
                        <a href="council.php">Municipal Council</a>
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
                        <a href="trackfault.html">My Tickets</a>
                    </div>
                </div>
            </div>
        </li>
    </ul>
 
  
<!-- HAMBURGER BUTTON -->
<button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu"> ☰</button>

</nav>

<!-- Contact Us Section -->
<section class="contacts">

    <h2>Contact Us</h2>

    <div class="contact-details">
        <h3>Contact Details</h3>
        
        <p>Municipal related issues (8am-5pm,Mon-Fri)</p>

        <table>
            <tr>
                <td class="icon">☎</td>
                <td>046 426 9682</td>
            </tr>

            <tr>
                <td class="icon">✉</td>
                <td><a href="mailto:info@makana.gov.za"> info@makana.gov.za</a></td>
            </tr>
        </table>

            <p>Municipal related issues (8am-5pm,Mon-Fri)</p>

        <table>
            <tr>
                <td class="icon">☎</td>
                <td>046 603 6111</td>
            </tr>

            <tr>
                <td class="icon">✉</td>
                <td><a href="mailto:info@makanamunicipa.gov.za"> info@makana.gov.za</a></td>
            </tr>
        </table>
    </div>
<!-- Municipal Contacts Section -->
    <div class="municipal-contacts">

        <h3>Makana Municipality </h3>

        <p>Makana Municipality can be contacted for municipal services, 
            community enquiries and service-related issues.
        </p>

        <h4>Municipal Offices:</h4>
        <p>
            PO Box 176, Makhanda, 6140
        </p>

        <table>
            <tr>
                <td class="icon">☎</td>
                <td>046 603 6111</td>
            </tr>
        </table>

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
            <p>Facebook: <a href="">Makana_Municipality</a></p>
        </div>

        <div class="footer-sec">
            <h3>ABOUT US</h3>
            <p><a href="">Historical background</a></p>
            <p><a href="">vision and Mission</a></p>
            <p><a href="">Councilors</a></p>
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

    <script>
        /* Dropdown Logic */
(function () {

    const dropdowns = document.querySelectorAll(".nav-dropdown");
    const body = document.body;


    function closeAll(except) {
        dropdowns.forEach(function (dropdown) {
            if (dropdown !== except) {
                dropdown.classList.remove("is-open");
                const button = dropdown.querySelector(".dropdown-link");

                if (button) {
                    button.setAttribute("aria-expanded", "false");
                }
            }
        });

        if (!document.querySelector(".nav-dropdown.is-open") && !document.querySelector(".nav-dropdown:hover")) {
            body.classList.remove("menu-open");
        }
    }


    dropdowns.forEach(function (dropdown) {
        const trigger = dropdown.querySelector(".dropdown-link");

        if (!trigger) {
            return;
        }


        /* Click dropdown */
        trigger.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            const isOpen =  dropdown.classList.contains("is-open");
            closeAll(dropdown);
            dropdown.classList.toggle( "is-open", !isOpen );
            trigger.setAttribute(  "aria-expanded", String(!isOpen) );
            body.classList.toggle("menu-open",!isOpen);
        });

        /* Hover */
        dropdown.addEventListener(
            "mouseenter",
            function () {
                body.classList.add("menu-open");
            }
        );

        dropdown.addEventListener(
            "mouseleave",
            function () {
                setTimeout(function () {
                    if (
                        !document.querySelector(
                            ".nav-dropdown:hover"
                        ) &&
                        !document.querySelector(
                            ".nav-dropdown.is-open"
                        )
                    ) {
                        body.classList.remove(
                            "menu-open"
                        );
                    }
                }, 60);
            }
        );
    });

    /* Click outside dropdown */
    document.addEventListener("click",
        function (event) {
            if (!event.target.closest(".nav-dropdown")) {
                closeAll();
            }
        }
    );

    /* Escape key */
    document.addEventListener( "keydown",
        function (event) {
            if (event.key === "Escape") {
                closeAll();
                const mainNav = document.getElementById( "mainNav");

                if (mainNav) {
                    mainNav.classList.remove( "is-open" );
                }
            }
        }
    );

    /* Mobile Menu */
    const toggle = document.getElementById("menuToggle");
    const mainNav = document.getElementById("mainNav");
    if (toggle && mainNav) {
        toggle.addEventListener( "click",
            function (event) {
                event.stopPropagation();

                const open = mainNav.classList.toggle( "is-open");
                toggle.setAttribute(  "aria-expanded", String(open));
            }
        );
    }

    /* Close mobile menu */
    document.addEventListener("click",
        function (event) {
            if (!event.target.closest( ".floating-nav")) {
                if (mainNav) { 
                    mainNav.classList.remove("is-open");
                }

                if (toggle) {
                    toggle.setAttribute( "aria-expanded", "false");
                }
            }
        }
    );
})();
    </script>
</body>
</html>
