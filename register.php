<?php
    include "dbconnection.php";
// fetch the form data from the POST request
    $idnumber = isset($_POST["idnumber"]) ? $_POST["idnumber"] : null;
    $firstname = isset($_POST["firstname"]) ? $_POST["firstname"] : null;
    $surname = isset($_POST["surname"]) ? $_POST["surname"] : null;
    $gender = isset($_POST["gender"]) ? $_POST["gender"] : null;
    $username = isset($_POST["username"]) ? $_POST["username"] : null;
    $pword = isset($_POST["pword"]) ? $_POST["pword"] : null;
    $userrole = isset($_POST["userrole"]) ? $_POST["userrole"] : null;
    $physicaladdress = isset($_POST["physAddress"]) ? $_POST["physAddress"] : null;
    $ward = isset($_POST["ward"]) ? $_POST["ward"] : null;
    $contact = isset($_POST["contact"]) ? $_POST["contact"] : null;
    $email = isset($_POST["email"]) ? $_POST["email"] : null;
    $hash_pword = password_hash($pword, PASSWORD_DEFAULT);


    // Residents are validated by default; all other roles require admin approval.
    $validate = strtolower(trim((string) $userrole)) === 'resident' ? 1 : 0;
    $stmt = $conn->prepare("INSERT INTO mykhandatech.theusers(idnumber, firstname, surname, gender, username, pword, userrole, phyAddress, ward, contact, email, validated)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt == false) {
        die("Prepare failed: " . $conn->error);
    }
// bind the parameters to the prepared statement
    $stmt->bind_param("sssssssssssi", $idnumber, $firstname, $surname, $gender, $username, $hash_pword, $userrole, $physicaladdress, $ward, $contact, $email, $validate);

// execute the prepared statement
    if ($stmt->execute()) {
    if ($validate === 0) {
        echo "<p>Your account was created and is pending administrator validation.</p>";
    } else {
        echo "<p>Record for $firstname $surname successfully created.</p>";
    }
    header("location: login.html"); // redirect to login page
    } else {
    echo "<a href='register.html'>Back to Signup</a><br>"; //Navigation
    die("Record could not be created: ". $stmt->error); // outputs message
    }
    $stmt->close();
    $conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <style>
body{
    background-color: #f6efe6;
    font-family: sf pro display, sans-serif;
    }

h1 {
    font-size: 24px;
    font-weight: 700;
}

h2 {
    font-size: 18px;
    font-weight: 600;
}

p {
    font-size: 12px;
    font-weight: 400;
}

/*Header*/
header{
    margin: 0;
    padding: 10px 20px;
    box-sizing: border-box;
    width: 100%;
    align-items: center;
}

.banner {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    height: 120px;
    width: 100%;
    padding: 20px 60px;
    box-sizing: border-box;
}

/* Logo */

.left-logo img {
    width: 190px;
    height: auto;
    display: block;
}

/*Navigation*/

nav {
    background: #4b1f2a;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 20px;
}


nav ul {
    margin: 0;
    padding: 0;
    display: flex;
    list-style: none;
}


nav ul li {
    margin: 0;
}


nav ul li a {
    display: block;
    align-items: center;
    padding: 18px 15px;
    color: white;
    text-decoration: none;
    font-weight: bold;
}


nav ul li a:hover, .authentication a:hover {
    background: #e0d5ca;
    color: #FFFFFF;
}


/* Login Section*/
.login_section {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 50px 20px;
}

.login { 
    background-color: #ffffff; 
    width: 500px; 
    padding: 35px 40px; 
    border-radius: 10px; 
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15); 
    text-align: center; 
}

.login h2{
    color:#4b1f2a;
    margin-bottom: 10px;
}

.login h3 { 
    color: #4b1f2a; 
} 

.login p { 
    color: #555555; 
}

.login-table { 
    width: 100%; 
    margin-top: 20px; 
    border-spacing: 10px; 
}

.login-table td:first-child { 
    text-align: right; 
    font-weight: bold; 
    color: #4b1f2a; 
    width: 35%; 
}

.login-table td:last-child { 
    text-align: left; 
}

.login-button, .reset-button { 
    background-color: #4b1f2a; 
    color: white; 
    align-items: center;
    padding: 8px 20px; 
    border-radius: 10px; 
    font-size: 16px; 
    font-weight: bold; 
    display: block;
    margin: 0;
}

.login-links { 
    margin-top: 20px; 
}

.login-links a { 
    color: #4b1f2a; 
    text-decoration: none; 
    font-weight: bold; 
} 

.login-links a:hover { 
    color: #b08d57; 
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
    </style>
</head>

<body>
<!-- Header -->
<header>

    <!-- Banner -->
    <div class="banner">

        <div class="left-logo">
            <img src="Makana-Municipality logo.png" alt="Makana Municipality Logo">
        </div>

    </div>

    <!-- Navigation bar -->
    <nav>
        <ul>
            <li><a href="Home Landing Page.html">Home</a></li>
            <li><a href="about.html">About Municipality</a></li>
            <li><a href="reportfault.html">Report Fault</a></li>
            <li><a href="trackfault.html">Track Fault</a></li>
            <li><a href="viewreports.html">View Reports</a></li>
            <li><a href="contactus.html">Contact Us</a></li>
            <li><a href="help.html">Help/FAQs</a></li>
        </ul>
    </nav>


    <section class="login_section">

        <div class="login"> 
            <h2>Welcome to Makhanda Connect</h2> 
            <p> Please enter your details to access your account. </p>

        <form action="login.php" method="POST">
            <table class="login-table">
                <tr>
                    <td>USERNAME:</td>
                    <td><input type="text" name="username" maxlength="50 " required></td>
                </tr>
                <tr>
                    <td>PASSWORD:</td>
                    <td><input type="password" name="pword" maxlength="50" required></td>
                </tr>
                <tr> 
                    <td><button type="submit" class="login-button">Login</button></td>
                    <td><button type="reset" class="reset-button">Reset</button></td>
                </tr>
            </table>
        </form>

        <div class="login-links"> 
            <a href="forgotPassword.html">Forgot your password?</a> 
            <br> <span>Don't have an account?</span> 
            <a href="Register.html">Register</a> 
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

    <!--JavaScript for the password-->
    <script> 
        function showPassword() { 
            const password = document.getElementById("pword"); 
            if (password.type === "password") { 
                password.type = "text"; 
            } else { 
                password.type = "password"; } } 
    </script>
    <script src="home 1.php"></script>
</body>
</html>