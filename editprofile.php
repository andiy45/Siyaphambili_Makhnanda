<?php

session_start();
require_once 'dbconnection.php';

/* check if user is logged in */
if (!isset($_SESSION['access']) || empty($_SESSION['username'])) {
    header("Location: login.html");
    exit;
}

$username = $_SESSION['username'];

/*update profile*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $phyAddress = trim($_POST['phyAddress'] ?? '');

    /* Validation*/
    if (
        empty($firstname) ||
        empty($surname) ||
        empty($email) ||
        empty($contact) ||
        empty($phyAddress)
    ) {
        $error = "Please complete all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        /* update user*/
        $sql = "UPDATE theusers
                SET firstname = ?,
                    surname = ?,
                    email = ?,
                    contact = ?,
                    phyAddress = ?
                WHERE username = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param(
                "ssssss",
                $firstname,
                $surname,
                $email,
                $contact,
                $phyAddress,
                $username
            );


            if ($stmt->execute()) {
                $stmt->close();

                 /* Update session information if the
                 * username/session is used elsewhere.*/
                $_SESSION['firstname'] = $firstname;
                $_SESSION['surname'] = $surname;
                $_SESSION['email'] = $email;
                header("Location: profile.php?updated=1");
                exit;
            } else {
                $error = "Unable to update your profile.";
                $stmt->close();
            }
        }
    }
}

/* Current profile information*/
$sql = "SELECT idnumber, firstname, surname, username, userrole,
               ward, phyAddress, contact, email
        FROM theusers
        WHERE username = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

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
<title>Edit Profile | Makana Municipality</title>
<link rel="stylesheet" href="home 1.css">

<style>

    /* edit profile section*/
    .edit-profile-section { display: flex; justify-content: center; align-items: center; padding: 50px 20px; min-height: 70vh;}
    .edit-profile-card { width: 600px; max-width: 100%; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);}
    .edit-profile-card h1 { text-align: center; color: #4b1f2a; margin-bottom: 10px;}
    .edit-profile-card > p { text-align: center;color: #666; margin-bottom: 30px;}

    /* form */
    .form-group { margin-bottom: 20px;}
    .form-group label { display: block;margin-bottom: 7px;font-weight: 600; color: #4b1f2a;}
    .form-group input { width: 100%; padding: 12px 14px; border: 1px solid #d6cbc2; border-radius: 8px; font-size: 15px; outline: none; box-sizing: border-box;}
    .form-group input:focus { border-color: #4b1f2a;}
    .readonly {background-color: #eeeeee;color: #777;cursor: not-allowed;}


    /*error messsage */
    .error-message {background-color: #f8d7da;color: #842029;padding: 12px;border-radius: 8px;margin-bottom: 20px;text-align: center;}

    /* Buttons*/
    .edit-buttons {display: flex;justify-content: center;gap: 12px; margin-top: 30px; flex-wrap: wrap;}
    .save-button, .cancel-button { border: none; text-decoration: none; padding: 12px 25px; border-radius: 8px;font-size: 15px; cursor: pointer;}
    .save-button {background-color: #4b1f2a;color: white;}
    .save-button:hover { background-color: #b08d57;}
    .cancel-button { background-color: #777; color: white;}
    .cancel-button:hover { background-color: #555;}

    @media (max-width: 600px) {
        .edit-profile-card {padding: 25px;}
    }
</style>
</head>

<body>

<header>



</header>



<!--Edit Profile Section-->
<section class="edit-profile-section">

<div class="edit-profile-card">
    <h1> Edit Profile</h1>
    <p>Update your personal account information below. </p>

    <?php if (!empty($error)): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- edit form-->
    <form method="POST"
          action="editprofile.php">

        <!-- First Name -->
        <div class="form-group">
            <label for="firstname"> First Name </label>
            <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname'] ?? ''); ?>" required>
        </div>

        <!-- Surname -->
        <div class="form-group">
            <label for="surname"> Surname </label>
            <input type="text" id="surname" name="surname"  value="<?php echo htmlspecialchars($user['surname'] ?? ''); ?>" required>
        </div>

        <!-- Username -->
        <div class="form-group">
            <label for="username">  Username</label>
            <input type="text" id="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" class="readonly" readonly>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email"> Email Address </label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
        </div>

        <!-- Contact -->
        <div class="form-group">
            <label for="contact"> Contact Number </label>
            <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($user['contact'] ?? ''); ?>" required>
        </div>

        <!-- Physical address -->
        <div class="form-group">
            <label for="phyAddress">Physical Address</label>
            <input type="text" id="phyAddress" name="phyAddress" value="<?php echo htmlspecialchars($user['phyAddress'] ?? ''); ?>" required>
        </div>

        <!-- Ward -->
        <div class="form-group">
            <label for="ward"> Ward </label>
            <input type="text" value="<?php echo htmlspecialchars($user['ward'] ?? ''); ?>" class="readonly" readonly>
        </div>

        <!-- Role -->
        <div class="form-group">
            <label for="userrole"> Account Role</label>
            <input type="text" value="<?php echo htmlspecialchars($user['userrole'] ?? ''); ?>" class="readonly" readonly>
        </div>

        <!-- Buttons -->
        <div class="edit-buttons">
            <button type="submit" class="save-button"> Save Changes</button>
            <a href="profile.php" class="cancel-button">Cancel</a>
        </div>
    </form>
</div>
</section>

</body>
</html>
