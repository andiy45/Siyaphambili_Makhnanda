<?php
session_start();
require_once 'dbconnection.php';

$error_message = '';

/* Process Form Submission (POST) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idnumber = trim($_POST['idnumber'] ?? '');

    if ($idnumber === '') {
        $error_message = 'Please enter your ID number.';
    } else {
        /* Find the user in the database */
        $stmt = $conn->prepare(
            'SELECT idnumber, firstname, surname, username, email
             FROM theusers
             WHERE idnumber = ?
             LIMIT 1'
        );

        if ($stmt === false) {
            $error_message = 'Unable to process your request. Please try again.';
        } else {
            $stmt->bind_param('s', $idnumber);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if (!$user) {
                $error_message = 'No account was found with that ID number.';
            } else {
                /* Regenerate session ID for security & store temporary reset information */
                session_regenerate_id(true);
                $_SESSION['reset_idnumber']  = $user['idnumber'];
                $_SESSION['reset_username']  = $user['username'];
                $_SESSION['reset_firstname'] = $user['firstname'];
                $_SESSION['reset_email']     = $user['email'];

                /* Redirect directly to resetPassword.php */
                header('Location: resetPassword.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Makhanda Connect</title>

    <style>
    /* GLOBAL RESETS */
    *, *::before, *::after {
        box-sizing: border-box;
    }

    html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text",
                     "Helvetica Neue", Helvetica, Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
        color: #1d1d1f;
        background: #f5f5f7;
    }

    /* NAVIGATION */
    .floating-nav {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        margin: 0 auto;
        height: 65px;
        background: white;
        -webkit-backdrop-filter: saturate(180%) blur(20px);
        backdrop-filter: saturate(180%) blur(20px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 25px;
        z-index: 9999;
    }

    .brand {
        display: inline-flex;
        align-items: center;
        text-decoration: none;
    }

    .brand-mark {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
        display: block;
        transition: transform 0.25s ease;
    }

    .main-nav {
        display: flex;
        align-items: center;
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

    .main-nav > li > a {
        text-decoration: none;
        color: #4b1f2a;
        font-size: 15px;
        font-weight: 500;
    }

    /* FORGOT PASSWORD SECTION */
    .forgot_section {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 120px 20px 70px;
    }

    .forgot-box {
        background-color: #ffffff;
        width: 100%;
        max-width: 500px;
        padding: 35px 40px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        text-align: center;
    }

    .forgot-box h2 {
        color: #4b1f2a;
        margin-bottom: 10px;
    }

    .forgot-box p {
        color: #555555;
        font-size: 14px;
        line-height: 1.6;
    }

    .forgot-table {
        width: 100%;
        margin-top: 20px;
        border-spacing: 0 12px;
    }

    .forgot-table td:first-child {
        text-align: right;
        font-weight: bold;
        color: #4b1f2a;
        width: 35%;
        padding-right: 10px;
    }

    .forgot-table td:last-child {
        text-align: left;
    }

    input[type="text"] {
        width: 100%;
        padding: 10px;
        box-sizing: border-box;
        border: 1px solid #ccc;
        border-radius: 6px;
    }

    .forgot-button {
        background-color: #4b1f2a;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: bold;
        cursor: pointer;
    }

    .forgot-button:hover {
        background-color: #6b2f3a;
    }

    .login-link {
        margin-top: 20px;
    }

    .login-link a {
        color: #4b1f2a;
        text-decoration: none;
        font-weight: bold;
        color: white;
    }

    /* FOOTER */
    footer {
        background-color: #4b1f2a;
        color: white;
        padding: 30px 50px 20px;
    }

    .footer-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 30px;
    }

    .footer-sec {
        flex: 1;
        min-width: 200px;
    }

    .footer-sec a {
        color: white;
        text-decoration: none;
    }

    .bottom-footer {
        background-color: #6B2F3A;
        text-align: center;
        padding: 15px;
        color: white;
    }
    </style>
</head>

<body>

<nav class="floating-nav">
    <a href="home 1.php" class="brand" aria-label="Makhanda Connect Home">
        <svg class="brand-mark" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="20" fill="#4b1f2a"/>
            <path d="M10 28 L10 14 L15 22 L20 14 L25 22 L30 14 L30 28"
                  fill="none" stroke="#ffffff" stroke-width="2.5"
                  stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </a>

    <ul class="main-nav">
        <li><a href="home 1.php">Home</a></li>
        <li><a href="contact_us.php">Support</a></li>
        <li><a href="login.html">Sign In</a></li>
    </ul>
</nav>

<main class="page-content">
    <section class="forgot_section">
        <div class="forgot-box">
            <h2>Forgot Your Password?</h2>
            <p>
                Enter your identity details below. We will verify your account against our records so you can reset your password.
            </p>

            <!-- Form submits directly to this same script -->
            <form action="forgotpassword.php" method="POST">
                <table class="forgot-table">
                    <tr>
                        <td><label for="idnumber">ID Number</label></td>
                        <td>
                            <input type="text" id="idnumber" name="idnumber" maxlength="13" required 
                                   value="<?= htmlspecialchars($_POST['idnumber'] ?? '') ?>">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align:center;">
                            <button type="submit" class="forgot-button">Continue</button>
                        </td>
                    </tr>
                </table>
            </form>

            <div class="login-link">
                <a href="login.html">&larr; Back to Login</a>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="footer-container">
        <div class="footer-sec">
            <h3>CONTACT US</h3>
            <p>Tel: <a href="tel:0466036731">046 603 6731</a></p>
            <p>Email: <a href="mailto:info@makanamunicipality.gov.za">info@makanamunicipality.gov.za</a></p>
        </div>
    </div>
</footer>

<section class="bottom-footer">
    <p>Makana Municipality Copyright &copy; 2026, All Rights Reserved.</p>
</section>

<!-- Trigger JS alert if error exists -->
<?php if (!empty($error_message)): ?>
    <script>
        alert(<?= json_encode($error_message) ?>);
    </script>
<?php endif; ?>

</body>
</html>