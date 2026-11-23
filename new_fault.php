<?php
// Enable temporary error display to capture HTTP 500 causes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Enable MySQLi exception mode to catch database SQL errors cleanly
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once('dbconnection.php');
} catch (Exception $e) {
    die("<b>Database Connection Error:</b> " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reportfault.html');
    exit;
}

// 1. Inputs
$title           = trim($_POST['issuetitle'] ?? '');
$incidentType    = trim($_POST['incident_type'] ?? '');
$description     = trim($_POST['incident_description'] ?? '');
$priority        = trim($_POST['priority'] ?? '');
$wardNumber      = trim($_POST['ward'] ?? '');
$physicalAddress = trim($_POST['physAddress'] ?? '');

if ($title === '' || $incidentType === '' || $description === '' || $priority === '' || $wardNumber === '' || $physicalAddress === '') {
    $_SESSION['fault_error'] = 'Please complete all required fields.';
    header('Location: reportfault.html');
    exit;
}

$reportedBy     = trim((string) ($_SESSION['username'] ?? 'Public user'));
$reportedAccess = trim((string) ($_SESSION['userrole'] ?? 'Public'));
$createdAt      = date('Y-m-d H:i:s');

// 2. Image Handling (BLOB upload)
$attachment = null; // null = no image, stored as NULL in DB

if (isset($_FILES['picture']) && $_FILES['picture']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['picture']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['fault_error'] = 'Image upload failed (error code ' . $_FILES['picture']['error'] . ').';
        header('Location: reportfault.html');
        exit;
    }

    // Size limit: 5 MB
    if ($_FILES['picture']['size'] > 5 * 1024 * 1024) {
        $_SESSION['fault_error'] = 'Image is too large (max 5 MB).';
        header('Location: reportfault.html');
        exit;
    }

    // Validate real MIME type (never trust the browser's type)
        // Validate the image using getimagesize (works without fileinfo extension)
    $imageInfo = @getimagesize($_FILES['picture']['tmp_name']);
    $allowed   = ['image/jpeg' => 'jpg', 'image/png' => 'png',
                  'image/gif' => 'gif', 'image/webp' => 'webp'];

    if ($imageInfo === false || !isset($allowed[$imageInfo['mime']])) {
        $_SESSION['fault_error'] = 'Only JPG, PNG, WEBP or GIF images are allowed.';
        header('Location: reportfault.html');
        exit;
    }

    // Read the actual binary content — THIS is what was missing
    $attachment = file_get_contents($_FILES['picture']['tmp_name']);

    if ($attachment === false) {
        $_SESSION['fault_error'] = 'Could not read the uploaded image.';
        header('Location: reportfault.html');
        exit;
    }
}

// 3. Dynamic Database Query Check & Insertion
try {
    $sql1 = 'INSERT INTO faultticket 
            (title, incident_type, incident_description, priority, ward_number, incident_address, picture, reported_by, reported_access, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt1 = $conn->prepare($sql1);

    $nullBlob = null;
    $stmt1->bind_param(
        'ssssisbsss',
        $title,
        $incidentType,
        $description,
        $priority,
        $wardNumber,
        $physicalAddress,
        $nullBlob,          // placeholder for BLOB
        $reportedBy,
        $reportedAccess,
        $createdAt
    );

    // Send the real binary data only if an image was uploaded
    if ($attachment !== null) {
        $stmt1->send_long_data(6, $attachment);
    }

    $stmt1->execute();
    $newTicketId = $conn->insert_id;
    $stmt1->close();

    // ... rest of your ticketstatus insert stays the same
     
    //INSERT the initial "Open" status into ticket_status
    $initialStatus = 'Open';
    $changedAt     = date('Y-m-d H:i:s');

    $sql2 = 'INSERT INTO ticketstatus 
               (status_type, updated_by, changed_at, ticket_id)
               VALUES (?, ?, ?, ?)';

    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param(
        'sssi',
        $initialStatus,
        $reportedBy,
        $changedAt,
        $newTicketId 
    );
    $stmt2->execute();
    $stmt2->close();
    
    // Set a success message in the session and redirect to the report fault page
    $_SESSION['fault_success'] = 'Fault reported successfully.';
    $stmt2->close();
    header('Location: reportfault.php');
    exit;

} catch (mysqli_sql_exception $e) {
    // Displays exact SQL error message on screen instead of blank 500 error
    die("<h3>MySQL Database Error:</h3> " . $e->getMessage());
} catch (Exception $e) {
    // Displays any other unexpected error message on screen instead of blank 500 error
    die("<h3>System Error:</h3> " . $e->getMessage());
}
?>