<?php
session_start();
include 'dbconnection.php';
// check the request method used to access the page.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  die('Invalid request method.');
}
// check if both fields exist.
if (isset($_POST['username']) && isset($_POST['pword'])) {
  $username = $_POST['username'];
  $password = $_POST['pword'];
} else {
  die('username and password must be provided.');
}
// validate the input given by the user.
if (empty($username) || empty($password)) {
  die('username and password are empty.');
}
// sanitize the input to prevent SQL injection.
$username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
// validate username length.
if (strlen($username) < 3 ) {
  die('username must be at least 3 characters long.');
}
// log trails 
$comment1 = "Failed login attempt";
$comment2 = "Successful login attempt";
$timestamp = date('Y-m-d H:i:s');
$reason1 = "Username not found";
$reason2 = "Valid login details";
$reason3 = "Used wrong password";
// the prepared statement
$sql = "SELECT * FROM myusers WHERE username = ?";
$stmt = $conn->prepare($sql); 
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  // log the failed login attempt
  $log_sql = "INSERT INTO logtrails (username, comment, reason, timestamp) VALUES (?, ?, ?, ?)";
  $log_stmt = $conn->prepare($log_sql);
  $log_stmt->bind_param("ssss", $username, $comment1, $reason1, $timestamp);
  $log_stmt->execute();
  // redirect to signin.html with an error message
  header('location: signin.html?error=username_not_found');
  exit();
} else {
  // user found - fetch the user data and verify the password
  $row = $result->fetch_assoc();
  // verify the password using password_verify()
  if (password_verify($password, $row['pword'])) {
    // prepared statement for a successful login.
    $sqllogt = "INSERT INTO logtrails (username, comment, reason, timestamp) VALUES (?, ?, ?, ?)";
    $stmt2 = $conn->prepare($sqllogt);
    $stmt2 -> bind_param("ssss", $username, $comment2, $reason2, $timestamp);
    $stmt2->execute();

    // set session variables for the logged-in user
    $_SESSION['loggedin'] = true;
    $_SESSION['idnumber'] = $row['idnumber'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['userrole'] = $row['userrole'];

    // display Welcome message and user details.
    echo "<h1>Welcome, " . htmlspecialchars($row['firstName']) . " " . htmlspecialchars($row['surname']) . "!</h1>";
    // display menu based on user role
    switch ($row['userrole']) {
      case 'admin':
        echo "<p>You are logged in as an admin.</p>";
        echo "<a href='adminDashboard.php'>Go to Admin Dashboard</a>";
       
        break;

      case 'resident':
        echo "<p>You are logged in as a resident.</p>";
        echo "<a href='resident_dashboard.php'>Go to Resident Dashboard</a>";
        echo "<p><a href='report.html'><button>Report Incident </button></a></p>";
        break;

      case 'councilor':
        echo "<p>You are logged in as a councillor.</p>";
        echo "<a href='councillor_dashboard.php'>Go to Councillor Dashboard</a>";
        echo "<p><a href=\'report.html'\><button>Report Incident </button></a></p>";
        break;

      case 'Municipal Worker':
        echo "<p>You are logged in as a Municipal Worker.</p>";
        echo "<a href='municipal_dashboard.php'>Go to Municipal Dashboard</a>";
        echo "<p><a href=\'report.html'\><button>Report Incident </button></a></p>";
        break;

      case 'guest':
        echo "<p>You are logged in as a guest.</p>";
        echo "<a href='guest_dashboard.php'>Go to Guest Dashboard</a>";
        echo "<p><a href=\'report.html'\><button>Report Incident </button></a></p>";
        break;
        
      case 'manager':
        echo "<p>You are logged in as a manager.</p>";
        echo "<a href='manager_dashboard.php'>Go to Manager Dashboard</a>";
        echo "<p><a href=\'report.html'\><button>Report Incident </button></a></p>";
        break;

      default:
        echo "<p>Unknown user role.</p>";
        echo "<p><a href=\'signin.html'\><button>Back to Sign In</button></a></p>";
        echo "<p><a href=\'report.html'\><button>Report Incident </button></a></p>";
        break;
    }
} else {
    // log the failed login attempt due to wrong password
    $sqllogt = "INSERT INTO logtrails (username, comment, reason, timestamp) VALUES (?, ?, ?, ?)";
    $stmt1 = $conn->prepare($sqllogt);
    $stmt1->bind_param("ssss", $username, $comment1, $reason3, $timestamp);
    $stmt1->execute();
    $stmt1->close();
    // redirect to signin.html with an error message
    die('Incorrect password.');
  }
}
$conn->close();
?>