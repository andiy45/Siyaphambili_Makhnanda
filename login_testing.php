<?php
session_start();
require_once 'dbconnection.php';


// Audit logging helper
function logAttempt(mysqli $conn, string $username, string $comment, string $reason): void
{
    $sql = "INSERT INTO logtrails (username, attempttime, comment, reason)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // Don't crash login if logging fails just record the error
        error_log('Log prepare failed: ' . $conn->error);
        return;
    }

    $attempttime = date('Y-m-d H:i:s');
    $stmt->bind_param('ssss', $username, $attempttime, $comment, $reason);

    if (!$stmt->execute()) {
        // Only log the error; do NOT die, so login flow continues
        error_log('Log execute failed: [' . $stmt->errno . '] ' . $stmt->error);
    }

    $stmt->close();
}


// Constants
const LOG_FAIL    = 'Failed login attempt';
const LOG_SUCCESS = 'Successful login attempt';

const REASON_USER_NOT_FOUND = 'Username not found';
const REASON_VALID          = 'Valid login details';
const REASON_WRONG_PASSWORD = 'Used wrong password';
const REASON_NOT_VALIDATED  = 'Account awaiting administrator validation';
const REASON_INVALID_INPUT  = 'Invalid input';


// Redirect helper
function loginError(string $message): void
{
    header('Location: login.html?error=' . urlencode($message));
    exit;
}

// Method guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    loginError('Please submit the login form.');
}


// Read inputs
$username = trim((string) ($_POST['username'] ?? ''));
$password = $_POST['pword'] ?? '';

if ($username === '' || $password === '') {
    logAttempt($conn, $username !== '' ? $username : '(blank)', LOG_FAIL, REASON_INVALID_INPUT);
    loginError('Please provide username and password.');
}

if (strlen($username) < 3 || strlen($username) > 50) {
    logAttempt($conn, $username, LOG_FAIL, REASON_INVALID_INPUT);
    loginError('Username must be between 3 and 50 characters long.');
}


// Get user details from database
// Get user details from database
$stmt = $conn->prepare(
    'SELECT UserID, idnumber, firstname, surname, username, pword,
            userrole, ward, phyAddress, contact, email, validated, accountstatus
     FROM theusers WHERE username = ? LIMIT 1');

if ($stmt === false) {
    loginError('Unable to prepare the login query.');
}

$stmt->bind_param('s', $username);

if (!$stmt->execute()) {
    $stmt->close();
    loginError('Unable to validate the login details.');
}

$result = $stmt->get_result();
$user   = $result ? $result->fetch_assoc() : null;
$stmt->close();

// HARD GATE: username must exist AND password must verify.
// If either fails, we ALWAYS go back to login. No exceptions.
if ($user === null || !password_verify($password, (string) $user['pword'])) {
    logAttempt($conn, $username, LOG_FAIL,
        $user === null ? REASON_USER_NOT_FOUND : REASON_WRONG_PASSWORD);
    $conn->close();
    loginError('Username and password do not match. Please try again.');
}

// Not validated
if ((int) $user['validated'] !== 1) {
    logAttempt($conn, $username, LOG_FAIL, REASON_NOT_VALIDATED);
    $conn->close();
    loginError('Your account is waiting for administrator validation.');
}

$accountStatus = strtolower(trim((string) ($user['accountstatus'] ?? '')));
if (in_array($accountStatus, ['0', 'inactive', 'disabled', 'off'], true)) {
    logAttempt($conn, $username, LOG_FAIL, 'Account switched off');
    $conn->close();
    loginError('Your account has been switched off. Please contact an administrator.');
}

// SUCCESS
logAttempt($conn, $username, LOG_SUCCESS, REASON_VALID);

// Regenerate the session ID to prevent fixation
session_regenerate_id(true);

// Populate the session with everything other pages need
$_SESSION['access']      = true;
$_SESSION['loggedin']    = true;
$_SESSION['user_id']     = (int) $user['UserID'];
$_SESSION['idnumber']    = $user['idnumber'];
$_SESSION['username']    = $user['username'];
$_SESSION['firstname']   = $user['firstname'];
$_SESSION['surname']     = $user['surname'];
$_SESSION['userrole']    = $user['userrole'];
$_SESSION['ward']        = $user['ward'];      // legacy alias
$_SESSION['physAddress'] = $user['phyAddress'];
$_SESSION['contact']     = $user['contact'];
$_SESSION['email']       = $user['email'];

// Clear any stale error from a previous attempt
unset($_SESSION['error']);


// Route by role
$role = strtolower(trim((string) $_SESSION['userrole']));
$role = str_replace([' ', '_'], '', $role);

$dashboardByRole = [
    'administrator'   => 'adminDashboard.php',
    'admin'           => 'adminDashboard.php',
    'manager'         => 'manager_dashboard.php',
    'municipalitymanager' => 'manager_dashboard.php',
    'communicationsofficer' => 'manager_dashboard.php',
    'resident'        => 'HomeAfterLogIn.php',
    'councillor'      => 'councillor.php',
    'councilor'       => 'councillor.php',
    'municipalworker' => 'municipal_dashboard.php',
    'municipalityworker' => 'municipal_dashboard.php',
    'worker'          => 'municipal_dashboard.php',
    'communityorganisation' => 'community_organisation.php',
    'communityorganization' => 'community_organisation.php',
];

$dashboard = $dashboardByRole[$role] ?? 'HomeAfterLogin.php';

if (!is_file(__DIR__ . DIRECTORY_SEPARATOR . $dashboard)) {
    $dashboard = 'HomeAfterLogin.php';
}

$conn->close();
header('Location: ' . $dashboard);
exit;
