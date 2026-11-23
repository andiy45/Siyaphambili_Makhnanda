<?php

session_start();

require_once "dbconnection.php";



//ACCESS CONTROL

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], ['administrator', 'manager'], true)) {
    header("Location: login.php");
    exit();
}

//REQUEST METHOD CHECK

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: notice_manager.php");
    exit();
}

// GET FORM DATA

$notice_id = filter_input(
    INPUT_POST,
    'notice_id',
    FILTER_VALIDATE_INT
);

$title = trim($_POST['title'] ?? '');
$messagenotice = trim($_POST['message'] ?? '');
$notice_date = trim($_POST['notice_date'] ?? '');
$priority = trim($_POST['priority'] ?? 'Normal');
$statusnotice = trim($_POST['status'] ?? 'Active');

$created_at = date('Y-m-d H:i:s');

// VALIDATE PRIORITY AND STATUS

$allowedPriorities = ['Normal','Important','Urgent'];

$allowedStatuses = ['Active','Inactive'];

if (!in_array($priority, $allowedPriorities, true)) {
    $priority = 'Normal';
}

if (!in_array($statusnotice, $allowedStatuses, true)) {
    $statusnotice = 'Active';
}

//REQUIRED FIELDS

if ($title === '' || $messagenotice === '' || $notice_date === '') {
    $_SESSION['error_message'] = "All required fields must be filled out.";
    header("Location: notice_manager.php");
    exit();
}

//UPDATE EXISTING NOTICE

if ($notice_id !== false && $notice_id !== null) {

    $sql = "
        UPDATE notices
        SET
            title = ?,
            messagenotice = ?,
            notice_date = ?,
            priority = ?,
            statusnotice = ?
        WHERE notice_id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {

        $_SESSION['error_message'] =
            "Failed to prepare update statement: " .
            mysqli_error($conn);

    } else {

        mysqli_stmt_bind_param(
            $stmt,
            "sssssi",
            $title,
            $messagenotice,
            $notice_date,
            $priority,
            $statusnotice,
            $notice_id
        );

        if (mysqli_stmt_execute($stmt)) {

            $_SESSION['success_message'] =
                "Notice updated successfully!";

        } else {

            $_SESSION['error_message'] =
                "Failed to update notice: " .
                mysqli_stmt_error($stmt);
        }

        mysqli_stmt_close($stmt);
    }


// INSERT NEW NOTICE

} else {

    $sql = "INSERT INTO notices (title, messagenotice, notice_date, priority, statusnotice, created_at) VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {

        $_SESSION['error_message'] =
            "Failed to prepare insert statement: " .
            mysqli_error($conn);

    } else {

        mysqli_stmt_bind_param(
            $stmt,
            "ssssss",
            $title,
            $messagenotice,
            $notice_date,
            $priority,
            $statusnotice,
            $created_at
        );

        if (mysqli_stmt_execute($stmt)) {

            $_SESSION['success_message'] =
                "New notice added successfully!";

        } else {

            $_SESSION['error_message'] =
                "Failed to create notice: " .
                mysqli_stmt_error($stmt);
        }

        mysqli_stmt_close($stmt);
    }
}

//CLOSE DATABASE

mysqli_close($conn);

//REDIRECT

header("Location: notice_manager.php");
exit();

?>