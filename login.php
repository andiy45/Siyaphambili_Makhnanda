<?php
session_start();
require_once 'dbconnection.php';
// Function to handle login errors and redirect to the login page with an error message
function loginError(string $message)
{
    header('Location: login.html?error=' . urlencode($message));
    exit;
}
// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    loginError('Please submit the login form.');
}
// Check if username and password are provided
$username = trim($_POST['username'] ?? '');
$password = $_POST['pword'] ?? '';
// Validate the input
if ($username === '' || $password === '') {
    loginError('Please provide username and password.');
}
// log trails 
$comment1 = "Failed login attempt";
$comment2 = "Successful login attempt";
$timestamp = date('Y-m-d H:i:s');
$reason1 = "Username not found";
$reason2 = "Valid login details";
$reason3 = "Used wrong password";

// Prepare and execute the SQL statement to fetch user data
$stmt = $conn->prepare('SELECT idnumber, firstname, surname, username, pword, userrole, ward, phyAddress, contact, email, validated FROM theusers WHERE username = ? LIMIT 1');
if ($stmt === false) {
    loginError('Unable to prepare the login query.');
}
// Bind the username parameter and execute the statement
$stmt->bind_param('s', $username);
if (!$stmt->execute()) {
    $stmt->close();
    loginError('Unable to validate the login details.');
}
// Fetch the result and check if a user was found
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();
// Verify the password and handle login errors

// the prepared statement
$sql = "SELECT * FROM theusers WHERE username = ?";
$stmt = $conn->prepare($sql); 
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  // log the failed login attempt
  $log_sql = "INSERT INTO logtrails (username, attempttime, comment, reason) VALUES (?, ?, ?, ?)";
  $log_stmt = $conn->prepare($log_sql);
  $log_stmt->bind_param("ssss", $username, $timestamp, $comment1, $reason1);
  $log_stmt->execute();
  // redirect to signin.html with an error message
  header('location: signin.html?error=username_not_found');
  exit();
}
if (!$user || !password_verify($password, (string) $user['pword'])) {
    loginError('Incorrect username or password.');
    
}
if ((int) $user['validated'] !== 1) {
    loginError('Your account is waiting for administrator validation.');
}
// Successful login: regenerate session ID and set session variables
session_regenerate_id(true);
$_SESSION['access'] = true;
$_SESSION['loggedin'] = true;
$_SESSION['idnumber'] = $user['idnumber'];
$_SESSION['username'] = $user['username'];
$_SESSION['firstname'] = $user['firstname'];
$_SESSION['surname'] = $user['surname'];
$_SESSION['userrole'] = $user['userrole'];
$_SESSION['ward'] = $user['ward'];
$_SESSION['physAddress'] = $user['phyAddress'];
$_SESSION['contact'] = $user['contact'];
$_SESSION['email'] = $user['email'];
// Determine the appropriate dashboard based on user role
$role = strtolower(trim((string) $_SESSION['userrole']));
$role = str_replace([' ', '_'], '', $role);
$dashboardByRole = [
    'administrator' => 'adminDashboard.php',
    'manager' => 'manager_dashboard.php',
    'resident' => 'HomeAfterLogin.php',
    'councillor' => 'councillor.php',
    'municipalworker' => 'municipal_dashboard.php'
];
// Redirect to the appropriate dashboard or fallback to the home page
$dashboard = $dashboardByRole[$role] ?? 'home 1.php';
if (!is_file(__DIR__ . DIRECTORY_SEPARATOR . $dashboard)) {
    $dashboard = 'home 1.php';
}
// Redirect to the determined dashboard
header('Location: ' . $dashboard);
exit;
?>