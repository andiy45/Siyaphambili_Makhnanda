<?php
session_start();
require_once 'dbconnection.php';

/* Security Guard: Ensure user has completed the ID verification step */
if (!isset($_SESSION['reset_idnumber'])) {
    header('Location: forgotpassword.php');
    exit;
}

$idnumber  = $_SESSION['reset_idnumber'];
$firstname = $_SESSION['reset_firstname'] ?? 'User';
$username  = $_SESSION['reset_username'] ?? '';

$error_message   = '';
$success_message = '';

/* Process Form Submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error_message = 'Please fill in both password fields.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Passwords do not match. Please try again.';
    } elseif (strlen($new_password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } else {
        /* Hash the new password securely */
        $pword = password_hash($new_password, PASSWORD_DEFAULT);

        /* Update password in database */
        $stmt = $conn->prepare(
            'UPDATE theusers 
             SET pword = ? 
             WHERE idnumber = ?'
        );

        if ($stmt) {
            $stmt->bind_param('ss', $pword, $idnumber);
            
            if ($stmt->execute()) {
                $success_message = 'Your password has been reset successfully!';
                
                /* Clear reset session variables */
                unset($_SESSION['reset_idnumber']);
                unset($_SESSION['reset_username']);
                unset($_SESSION['reset_firstname']);
                unset($_SESSION['reset_email']);
            } else {
                $error_message = 'Failed to update password. Please try again.';
            }
            $stmt->close();
        } else {
            $error_message = 'A system error occurred. Please try again later.';
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Makhanda Connect</title>

    <style>
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

    .floating-nav {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 65px;
        background: white;
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
    }

    .main-nav {
        display: flex;
        gap: 25px;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .main-nav a {
        text-decoration: none;
        color: #4b1f2a;
        font-size: 15px;
        font-weight: 500;
    }

    .reset-section {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 120px 20px 70px;
    }

    .reset-box {
        background-color: #ffffff;
        width: 100%;
        max-width: 500px;
        padding: 35px 40px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        text-align: center;
    }

    .reset-box h2 {
        color: #4b1f2a;
        margin-bottom: 10px;
    }

    .reset-box p {
        color: #555555;
        font-size: 14px;
        line-height: 1.6;
    }

    .user-badge {
        background-color: #f5f5f7;
        padding: 8px 14px;
        border-radius: 6px;
        font-size: 13px;
        color: #4b1f2a;
        font-weight: bold;
        display: inline-block;
        margin-bottom: 15px;
    }

    .error-alert {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 15px;
        font-size: 14px;
    }

    .success-alert {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 15px;
        font-size: 14px;
    }

    .reset-table {
        width: 100%;
        margin-top: 15px;
        border-spacing: 0 12px;
    }

    .reset-table td:first-child {
        text-align: right;
        font-weight: bold;
        color: #4b1f2a;
        width: 40%;
        padding-right: 10px;
    }

    .reset-table td:last-child {
        text-align: left;
    }

    input[type="password"] {
        width: 100%;
        padding: 10px;
        box-sizing: border-box;
        border: 1px solid #ccc;
        border-radius: 6px;
    }

    .reset-button {
        background-color: #4b1f2a;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: bold;
        cursor: pointer;
        width: 100%;
    }

    .reset-button:hover {
        background-color: #6b2f3a;
    }

    .login-link {
        margin-top: 20px;
    }

    .login-link a {
        color: #4b1f2a;
        text-decoration: none;
        font-weight: bold;
    }

    footer {
        background-color: #4b1f2a;
        color: white;
        padding: 30px 50px 20px;
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
    <a href="newhead.html" class="brand" aria-label="Makhanda Connect Home">
        <svg class="brand-mark" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="20" fill="#4b1f2a"/>
            <path d="M10 28 L10 14 L15 22 L20 14 L25 22 L30 14 L30 28"
                  fill="none" stroke="#ffffff" stroke-width="2.5"
                  stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </a>

    <ul class="main-nav">
        <li><a href="newhead.html">Home</a></li>
        <li><a href="login.html">Sign In</a></li>
    </ul>
</nav>

<main class="page-content">
    <section class="reset-section">
        <div class="reset-box">
            <h2>Create New Password</h2>

            <?php if (!empty($success_message)): ?>
                <div class="success-alert">
                    <?= htmlspecialchars($success_message) ?>
                </div>
                <div class="login-link" style="margin-top: 10px;">
                    <a href="login.html" class="reset-button" style="display:inline-block; text-decoration:none; padding:12px;">Proceed to Login</a>
                </div>
            <?php else: ?>
                <div class="user-badge">
                    Resetting password for: <?= htmlspecialchars($firstname) ?> (<?= htmlspecialchars($username) ?>)
                </div>

                <p>Please enter and confirm your new password below.</p>

                <?php if (!empty($error_message)): ?>
                    <div class="error-alert">
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <form action="resetPassword.php" method="POST" id="resetForm">
                    <table class="reset-table">
                        <tr>
                            <td><label for="new_password">New Password</label></td>
                            <td><input type="password" id="new_password" name="new_password" required minlength="8"></td>
                        </tr>
                        <tr>
                            <td><label for="confirm_password">Confirm Password</label></td>
                            <td><input type="password" id="confirm_password" name="confirm_password" required minlength="8"></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top: 15px;">
                                <button type="submit" class="reset-button">Update Password</button>
                            </td>
                        </tr>
                    </table>
                </form>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer>
    <p style="text-align: center; margin:0;">Makana Municipality Copyright &copy; 2026</p>
</footer>

<section class="bottom-footer">
    <p>Brought to you by Makana Municipality in collaboration with MyMakhanda Tech.</p>
</section>

</body>
</html>