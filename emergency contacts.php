<?php

// CONTACT DETAILS ARRAY
$contactDetails = [

    // Website Support
    'website_support' => [
        'title' => 'Website Related Queries',
        'hours' => '8am - 5pm, Mon-Fri',
        'phone' => '086 144 4779',
        'email' => 'website@makanda.gov.za'
    ],

    // Call Center
    'call_center' => [
        'title' => 'Call Center',
        'hours' => '8am - 5pm (Mon-Fri)',
        'phone' => '043 705 9000',
        'email' => 'info@makanda.gov.za'
    ],

    // Customer Care
    'customer_care' => [
        'title' => 'Customer Care',
        'hours' => '8am - 5pm (Mon-Fri)',
        'phone' => '043 705 9000',
        'email' => 'customercare@makanda.gov.za'
    ],

    // Councillors
    'councillors' => [
        'title' => 'Makhanda Councillors',
        'postal_address' => 'PO Box 183, Makhanda, 6140, Eastern Cape, South Africa',
        'fax' => '+27 43 705 9001'
    ],

    // EMERGENCY SERVICES
    'emergency' => [
        'title' => 'Emergency Services',
        'hours' => '24/7',
        'phone' => '046 603 8111',
        'email' => 'emergency@makanda.gov.za'
    ],

    // POLICE
    'police' => [
        'title' => 'Makhanda Police Station',
        'hours' => '24/7',
        'phone' => '043 705 9100',
        'emergency' => '10111',
        'address' => 'High Street, Makhanda, 6139',
        'email' => 'makhanda.saps@saps.gov.za',
        'station_commander' => 'Colonel J. Smith'
    ],

    // HOSPITAL
    'hospital' => [
        'title' => 'Settlers Hospital',
        'hours' => '24/7 (Emergency Department)',
        'phone' => '043 709 1000',
        'emergency' => '10177',
        'address' => 'University Way, Makhanda, 6139',
        'email' => 'settlers.hospital@ecdoe.gov.za',

        'services' => [
            'Emergency Unit',
            'Maternity Ward',
            'Outpatient Clinic',
            'Pharmacy'
        ]
    ],

    // FIRE DEPARTMENT
    'fire' => [
        'title' => 'Makhanda Fire Department',
        'hours' => '24/7',
        'phone' => '043 705 9200',
        'emergency' => '10177',
        'address' => 'Fire Station, Somerset Street, Makhanda, 6139',
        'email' => 'fire@makhanda.gov.za',

        'services' => [
            'Fire Fighting',
            'Rescue Operations',
            'Hazardous Material Response'
        ]
    ],

    // AMBULANCE
    'ambulance' => [
        'title' => 'Ambulance Services',
        'hours' => '24/7',
        'emergency' => '10177',
        'phone' => '043 709 1111',
        'address' => 'Settlers Hospital, University Way, Makhanda, 6139'
    ]
];

// FUNCTION: EMAIL NOTIFICATION
function displayEmailNotification()
{
    ?>

    <div class="email-notification">

        <div class="notification-icon">
            ⚠️
        </div>

        <div class="notification-text">

            <strong>Important:</strong>

            Please make sure that you use an email address to which we can
            reply, as it often happens that email replies are returned as
            undeliverable because of incorrect email addresses or selective
            blocking at work email addresses.

        </div>

    </div>

    <?php
}

// FUNCTION: DISPLAY CONTACT DETAILS
function displayContactDetails($contact)
{
    ?>

    <div class="contact-section">


        <!--  WEBSITE SUPPORT-->

        <div class="contact-card">

            <h3>
                🌐 <?php echo $contact['website_support']['title']; ?>
            </h3>

            <p>
                <strong>Hours:</strong>
                <?php echo $contact['website_support']['hours']; ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?php echo $contact['website_support']['phone']; ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?php echo $contact['website_support']['email']; ?>
            </p>

        </div>


        <!--  CALL CENTER-->

        <div class="contact-card">

            <h3>
                📞 <?php echo $contact['call_center']['title']; ?>
            </h3>

            <p>
                <strong>Hours:</strong>
                <?php echo $contact['call_center']['hours']; ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?php echo $contact['call_center']['phone']; ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?php echo $contact['call_center']['email']; ?>
            </p>

        </div>


        <!--  CUSTOMER CARE -->

        <div class="contact-card">

            <h3>
                👥 <?php echo $contact['customer_care']['title']; ?>
            </h3>

            <p>
                <strong>Hours:</strong>
                <?php echo $contact['customer_care']['hours']; ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?php echo $contact['customer_care']['phone']; ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?php echo $contact['customer_care']['email']; ?>
            </p>

        </div>


        <!-- COUNCILLORS -->

        <div class="contact-card">

            <h3>
                🏛️ <?php echo $contact['councillors']['title']; ?>
            </h3>

            <p>
                <strong>Postal Address:</strong>
                <?php echo $contact['councillors']['postal_address']; ?>
            </p>

            <p>
                <strong>Fax:</strong>
                <?php echo $contact['councillors']['fax']; ?>
            </p>

        </div>


        <!-- emergency services -->
        <div class="contact-card emergency-card">
            <h3> 🚨 <?php echo $contact['emergency']['title']; ?> </h3>

            <p>
                <strong>Hours:</strong>
                <?php echo $contact['emergency']['hours']; ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?php echo $contact['emergency']['phone']; ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?php echo $contact['emergency']['email']; ?>
            </p>
        </div>


        <!-- Police -->

        <div class="contact-card emergency-card">

            <h3> 👮 <?php echo $contact['police']['title']; ?> </h3>

            <p>
                <strong>Hours:</strong>
                <?php echo $contact['police']['hours']; ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?php echo $contact['police']['phone']; ?>
            </p>

            <p>
                <strong>Emergency:</strong>
                <?php echo $contact['police']['emergency']; ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?php echo $contact['police']['email']; ?>
            </p>

            <p>
                <strong>Address:</strong>
                <?php echo $contact['police']['address']; ?>
            </p>

            <p>
                <strong>Station Commander:</strong>
                <?php echo $contact['police']['station_commander']; ?>
            </p>

        </div>


        <!-- Hospital -->
        <div class="contact-card emergency-card">

            <h3> 🏥 <?php echo $contact['hospital']['title']; ?> </h3>

            <p>
                <strong>Hours:</strong>
                <?php echo $contact['hospital']['hours']; ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?php echo $contact['hospital']['phone']; ?>
            </p>

            <p>
                <strong>Emergency:</strong>
                <?php echo $contact['hospital']['emergency']; ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?php echo $contact['hospital']['email']; ?>
            </p>

            <p>
                <strong>Address:</strong>
                <?php echo $contact['hospital']['address']; ?>
            </p>

            <p>
                <strong>Services:</strong>
            </p>

            <ul>

                <?php foreach ($contact['hospital']['services'] as $service): ?>

                    <li>
                        <?php echo $service; ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>


        <!--  Fire department -->
        <div class="contact-card emergency-card">

            <h3>  🔥 <?php echo $contact['fire']['title']; ?> </h3>

            <p>
                <strong>Hours:</strong>
                <?php echo $contact['fire']['hours']; ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?php echo $contact['fire']['phone']; ?>
            </p>

            <p>
                <strong>Emergency:</strong>
                <?php echo $contact['fire']['emergency']; ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?php echo $contact['fire']['email']; ?>
            </p>

            <p>
                <strong>Address:</strong>
                <?php echo $contact['fire']['address']; ?>
            </p>

            <p>
                <strong>Services:</strong>
            </p>

            <ul>
                <?php foreach ($contact['fire']['services'] as $service): ?>
                    <li> <?php echo $service; ?> </li>
                <?php endforeach; ?>
            </ul>

        </div>


        <!-- Ambulance -->
        <div class="contact-card emergency-card">
            <h3>  🚑 <?php echo $contact['ambulance']['title']; ?> </h3>

            <p>
                <strong>Hours:</strong>
                <?php echo $contact['ambulance']['hours']; ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?php echo $contact['ambulance']['phone']; ?>
            </p>

            <p>
                <strong>Emergency:</strong>
                <?php echo $contact['ambulance']['emergency']; ?>
            </p>

            <p>
                <strong>Address:</strong>
                <?php echo $contact['ambulance']['address']; ?>
            </p>
        </div>
    </div>

    <?php
}

// emergency table
function displayEmergencyTable($contact)
{
    ?>
    <div class="table-section">
        <h2>🚨 Emergency Services Quick Reference</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Phone</th>
                        <th>Emergency Number</th>
                        <th>Hours</th>
                    </tr>
                </thead>

                <tbody>
                    <!-- National Emergency -->
                    <tr class="national-emergency">
                        <td>
                            National Emergency
                        </td>
                        <td>
                            <?php echo $contact['emergency']['phone']; ?>
                        </td>
                        <td>  -</td>
                        <td>
                            <?php echo $contact['emergency']['hours']; ?>
                        </td>
                    </tr>

                    <!-- Police-->
                    <tr>
                        <td>
                            Makhanda Police
                        </td>
                        <td>
                            <?php echo $contact['police']['phone']; ?>
                        </td>
                        <td>
                            <?php echo $contact['police']['emergency']; ?>
                        </td>
                        <td>
                            <?php echo $contact['police']['hours']; ?>
                        </td>
                    </tr>

                    <!-- Hospittal -->
                    <tr>
                        <td>
                            Settlers Hospital
                        </td>
                        <td>
                            <?php echo $contact['hospital']['phone']; ?>
                        </td>
                        <td>
                            <?php echo $contact['hospital']['emergency']; ?>
                        </td>
                        <td>
                            <?php echo $contact['hospital']['hours']; ?>
                        </td>
                    </tr>

                    <!-- Fire -->
                    <tr>
                        <td>
                            Fire Department
                        </td>
                        <td>
                            <?php echo $contact['fire']['phone']; ?>
                        </td>
                        <td>
                            <?php echo $contact['fire']['emergency']; ?>
                        </td>
                        <td>
                            <?php echo $contact['fire']['hours']; ?>
                        </td>
                    </tr>

                    <!-- Ambulane -->
                    <tr>
                        <td>
                            Ambulance
                        </td>
                        <td>
                            <?php echo $contact['ambulance']['phone']; ?>
                        </td>
                        <td>
                            <?php echo $contact['ambulance']['emergency']; ?>
                        </td>
                        <td>
                            <?php echo $contact['ambulance']['hours']; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Contacts - Makhanda</title>
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

/* emergency service table */

.table-section {
    width: 92%;
    max-width: 1200px;
    margin: 60px auto;
    padding: 0;
}

/* Table heading */
.table-section h2 {
    text-align: center;
    color: #4b1f2a;
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 25px;
    letter-spacing: -0.02em;
}

/* Table wrapper */
.table-wrapper {
    width: 100%;
    background: #ffffff;
    border-radius: 18px;
    overflow-x: auto;
    box-shadow: 0 10px 30px rgba(75, 31, 42, 0.12);
    border: 1px solid rgba(75, 31, 42, 0.08);
}

/* Main table */
.table-wrapper table {
    width: 100%;
    min-width: 700px;
    border-collapse: collapse;
    border-spacing: 0;
}

/* table header */

.table-wrapper thead {
    background-color: #4b1f2a;
}

.table-wrapper th {
    padding: 18px 20px;
    color: #ffffff;
    text-align: left;
    font-size: 15px;
    font-weight: 600;
    white-space: nowrap;
    border: none;
}

/* table body */

.table-wrapper td {
    padding: 17px 20px;
    color: #333333;
    font-size: 15px;
    border-bottom: 1px solid #e8e2dc;
    vertical-align: middle;
}

/* Remove border from last row */
.table-wrapper tbody tr:last-child td {
    border-bottom: none;
}

/* Alternating rows */
.table-wrapper tbody tr:nth-child(even) {
    background-color: #faf7f3;
}

.table-wrapper tbody tr:nth-child(odd) {
    background-color: #ffffff;
}

/* hover effect */
.table-wrapper tbody tr {
    transition: background-color 0.2s ease, transform 0.2s ease;
}

.table-wrapper tbody tr:hover {
    background-color: #e9fffc;
}

/* service name */
.table-wrapper td:first-child {
    color: #4b1f2a;
    font-weight: 700;
}

/* phone numbers */
.table-wrapper td:nth-child(2) {
    font-weight: 600;
    white-space: nowrap;
}

/* emergency number */
.table-wrapper td:nth-child(3) {
    color: #4b1f2a;
    font-weight: 700;
    white-space: nowrap;
}

/* working hours */

.table-wrapper td:nth-child(4) {
    font-weight: 500;
    white-space: nowrap;
}

/* national emergency row */
.table-wrapper .national-emergency {
    background-color: #40e0d0;
}

.table-wrapper .national-emergency td {
    color: #4b1f2a;
    font-weight: 700;
    border-bottom: 1px solid rgba(75, 31, 42, 0.15);
}

.table-wrapper .national-emergency:hover {
    background-color: #40e0d0;
}

/* table corners*/
.table-wrapper thead tr:first-child th:first-child {
    border-top-left-radius: 18px;
}

.table-wrapper thead tr:first-child th:last-child {
    border-top-right-radius: 18px;
}

/* scrollbar */
.table-wrapper::-webkit-scrollbar {
    height: 8px;
}

.table-wrapper::-webkit-scrollbar-track {
    background: #f5f0eb;
    border-radius: 10px;
}

.table-wrapper::-webkit-scrollbar-thumb {
    background: #4b1f2a;
    border-radius: 10px;
}

.table-wrapper::-webkit-scrollbar-thumb:hover {
    background: #b08d57;
}

/* mobile */
@media (max-width: 860px) {
    .table-section {
        width: 94%;
        margin: 45px auto;
    }

    .table-section h2 {
        font-size: 23px;
        line-height: 1.3;
        margin-bottom: 20px;
    }

    .table-wrapper {
        border-radius: 14px;
    }

    .table-wrapper table {
        min-width: 650px;
    }

    .table-wrapper th {
        padding: 14px 15px;
        font-size: 14px;
    }

    .table-wrapper td {
        padding: 14px 15px;
        font-size: 14px;
    }

    .table-wrapper thead tr:first-child th:first-child {
        border-top-left-radius: 14px;
    }

    .table-wrapper thead tr:first-child th:last-child {
        border-top-right-radius: 14px;
    }
}

/* small phones */
@media (max-width: 500px) {

    .table-section {
        width: 95%;
        margin: 35px auto;
    }

    .table-section h2 {
        font-size: 21px;
    }

    .table-wrapper {
        box-shadow: 0 6px 20px rgba(75, 31, 42, 0.10);
    }
}


/*Footer*/
        footer {
            background-color: #4b1f2a;
            color: white;
            padding: 30px 50px 10px;
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


/* mobile footer*/
@media (max-width: 768px) {
    /* Main footer */
    footer { padding: 30px 20px 15px;}

    /* Stack footer sections vertically */
    .footer-container { display: flex;flex-direction: column; gap: 25px; width: 100%; max-width: 100%;margin: 0 auto;}

    /* Individual footer sections */
    .footer-sec { width: 100%; flex: none;}

    /* Footer headings */
    .footer-sec h3 { font-size: 16px; margin-top: 0; margin-bottom: 10px;}

    /* Footer paragraphs */
    .footer-sec p {font-size: 14px; line-height: 1.6; margin: 6px 0;  word-wrap: break-word;}

    /* Footer links */
    .footer-sec a { font-size: 14px; word-wrap: break-word;}

    /* Map image */
    .footer-sec img { width: 110px; height: 120px; object-fit: cover; margin-top: 8px;}

    /* Bottom footer */
    .bottom-footer { padding: 15px 15px; font-size: 12px; line-height: 1.5;}
    .bottom-footer p { margin: 0;}
}

/* smart phones */
@media (max-width: 480px) {
    footer { padding: 25px 15px 10px;}
    .footer-container { gap: 22px;}
    .footer-sec h3 { font-size: 15px;}
    .footer-sec p, .footer-sec a { font-size: 13px;}
    .footer-sec img { width: 100px; height: 110px;}
    .bottom-footer { padding: 12px 10px;font-size: 11px;}
}
        </style>
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

    <!-- Main Content-->
    <main class="contact-page">
        <!-- Page Title -->
        <div class="page-header">
            <h1>Emergency contacts</h1>
            <p>
                Important contact information and emergency services
                for the Makhanda community.
            </p>
        </div>

        <div class="page-content">
        <!-- EMAIL NOTICE -->
        <?php
        displayEmailNotification();
        ?>
        
        <!-- Emergency table -->
        <?php
        displayEmergencyTable($contactDetails);
        ?>
        </div>
    </main>


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
            <p><a href="historicalbackground.php">Historical background</a></p>
            <p><a href="about.php">vision and Mission</a></p>
            <p><a href="ward councillors">Councilors</a></p>
        </div>  

        <div class="footer-sec">
            <h3>Quick Links</h3>
            <p><a href="abount.php">Vision and Mission</a></p>
            <p><a href="trackfault.php">Track Fault</a></p>
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
