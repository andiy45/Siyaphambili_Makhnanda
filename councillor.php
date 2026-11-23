<?php

session_start();
require_once 'dbconnection.php';

if (!$conn) {
    die("Database connection failed.");
}

$role = strtolower(trim((string)($_SESSION['userrole'] ?? '')));

if (!isset($_SESSION['access']) || !in_array($role, ['councillor', 'ward councillor'], true)) {
    header('Location: login.html');
    exit;
}

/* COUNCILLOR ACCOUNT DETAILS */

$idnumber = $_SESSION['idnumber'] ?? '';

$sql_user = "SELECT idnumber, firstname, surname, username, email, contact, ward, userrole
             FROM theusers
             WHERE idnumber = ?
             LIMIT 1";

$stmt_user = mysqli_prepare($conn, $sql_user);

if ($stmt_user) {
    mysqli_stmt_bind_param($stmt_user, "s", $idnumber);
    mysqli_stmt_execute($stmt_user);

    mysqli_stmt_bind_result(
        $stmt_user,
        $user_idnumber,
        $user_firstname,
        $user_surname,
        $user_username,
        $user_email,
        $user_contact,
        $user_ward,
        $user_role
    );

    $user = null;

    if (mysqli_stmt_fetch($stmt_user)) {
        $user = [
            'idnumber' => $user_idnumber,
            'firstname' => $user_firstname,
            'surname' => $user_surname,
            'username' => $user_username,
            'email' => $user_email,
            'contact' => $user_contact,
            'ward' => $user_ward,
            'userrole' => $user_role
        ];
    }

    mysqli_stmt_close($stmt_user);
}

if (!$user) {
    $user = [
        'idnumber' => $idnumber,
        'firstname' => $_SESSION['firstname'] ?? '',
        'surname' => $_SESSION['surname'] ?? '',
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'contact' => $_SESSION['contact'] ?? '',
        'ward' => $_SESSION['ward'] ?? '',
        'userrole' => 'Councillor'
    ];
}

$councillor_ward_id = $_SESSION['ward_id'] ?? $user['ward'] ?? 1;

$first_name = $user['firstname'];
$surname = $user['surname'];
$username = $user['username'];
$email = $user['email'];
$contact = $user['contact'];
$ward = $user['ward'];

/* UPDATE ACCOUNT */

$account_message = '';
$account_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {

    $new_firstname = trim($_POST['firstname'] ?? '');
    $new_surname = trim($_POST['surname'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_contact = trim($_POST['contact'] ?? '');

    if (
        $new_firstname === '' ||
        $new_surname === '' ||
        $new_email === '' ||
        $new_contact === ''
    ) {
        $account_error = "Please complete all fields.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $account_error = "Please enter a valid email address.";
    } else {

        $sql_update = "UPDATE theusers
                       SET firstname = ?, surname = ?, email = ?, contact = ?
                       WHERE idnumber = ?";

        $stmt_update = mysqli_prepare($conn, $sql_update);

        if ($stmt_update) {

            mysqli_stmt_bind_param(
                $stmt_update,
                "sssss",
                $new_firstname,
                $new_surname,
                $new_email,
                $new_contact,
                $idnumber
            );

            if (mysqli_stmt_execute($stmt_update)) {

                $first_name = $new_firstname;
                $surname = $new_surname;
                $email = $new_email;
                $contact = $new_contact;

                $_SESSION['firstname'] = $new_firstname;
                $_SESSION['surname'] = $new_surname;
                $_SESSION['email'] = $new_email;
                $_SESSION['contact'] = $new_contact;

                $account_message = "Account details updated successfully.";

            } else {
                $account_error = "Unable to update your account details.";
            }

            mysqli_stmt_close($stmt_update);

        } else {
            $account_error = "Unable to prepare the account update.";
        }
    }
}

/* STATISTICS */

$stats = [
    'open' => 0,
    'resolved' => 0,
    'pending' => 0
];

$sql_stats = "SELECT status, COUNT(*) AS total
              FROM faultticket
              WHERE ward_id = ?
              GROUP BY status";

$stmt_stats = mysqli_prepare($conn, $sql_stats);

if ($stmt_stats) {

    mysqli_stmt_bind_param($stmt_stats, "i", $councillor_ward_id);
    mysqli_stmt_execute($stmt_stats);

    $result_stats = mysqli_stmt_get_result($stmt_stats);

    while ($row = mysqli_fetch_assoc($result_stats)) {

        $status = strtolower(trim($row['status']));
        $total = (int)$row['total'];

        if ($status === 'open') {
            $stats['open'] += $total;
        } elseif ($status === 'resolved' || $status === 'closed') {
            $stats['resolved'] += $total;
        } else {
            $stats['pending'] += $total;
        }
    }

    mysqli_stmt_close($stmt_stats);
}

/* RECENT ISSUES */

$recent_issues = [];

$sql_issues = "SELECT ticket_id, incident_type, incident_address, created_at, status
               FROM faultticket
               WHERE ward_id = ?
               ORDER BY created_at DESC
               LIMIT 5";

$stmt_issues = mysqli_prepare($conn, $sql_issues);

if ($stmt_issues) {

    mysqli_stmt_bind_param($stmt_issues, "i", $councillor_ward_id);
    mysqli_stmt_execute($stmt_issues);

    $result_issues = mysqli_stmt_get_result($stmt_issues);

    while ($row = mysqli_fetch_assoc($result_issues)) {
        $recent_issues[] = $row;
    }

    mysqli_stmt_close($stmt_issues);
}

/* NOTICES */

$notices = [];

$sql_notices = "SELECT title, messagenotice, notice_date, priority
                FROM notices
                ORDER BY notice_date DESC
                LIMIT 10";

$result_notices = mysqli_query($conn, $sql_notices);

if ($result_notices) {

    while ($row = mysqli_fetch_assoc($result_notices)) {
        $notices[] = $row;
    }
}

/* UPCOMING MEETINGS */

$meetings = [];

$sql_meetings = "SELECT event_title, event_date, event_location
                 FROM calender_events
                 WHERE event_date >= CURDATE()
                 ORDER BY event_date ASC
                 LIMIT 10";

$result_meetings = mysqli_query($conn, $sql_meetings);

if ($result_meetings) {

    while ($row = mysqli_fetch_assoc($result_meetings)) {
        $meetings[] = $row;
    }
}

/*Validate workers*/
$workerResult = $conn->query("
    SELECT username, firstname, surname, idnumber, ward, contact, email
    FROM theusers
    WHERE validated = 0
    AND LOWER(userrole) IN ('worker', 'municipal worker', 'municipal_worker')
    ORDER BY firstname, surname
");

$workers = [];

if ($workerResult) {
    while ($worker = $workerResult->fetch_assoc()) {
        $workers[] = $worker;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Councillor Dashboard</title>

    <style>
*{margin:0;padding:0;box-sizing:border-box}

:root{
    --background:#f6efe6;
    --white:#ffffff;
    --wine:#4b1f2a;
    --wine-dark:#4a1521;
    --gold:#b08d57;
    --gold-light:#d8bd91;
    --blue-dark:#2e4b5d;
    --blue:#4c9cab;
    --green-dark:#2f7d6d;
    --green:#4ca28e;
    --text:#333333;
    --muted:#777777;
    --line:#ddd5ca;
}

body{font-family:Arial,sans-serif;background:var(--background);color:var(--text)}

.topbar{height:75px;width:100%;background:var(--wine);color:white;display:flex;align-items:center;justify-content:space-between;padding:0 35px}

.brand{display:flex;align-items:center;gap:14px}
.brand img{width:52px;height:52px;object-fit:contain;background:white;border-radius:50%}
.brand strong{display:block;font-size:1.05rem;letter-spacing:.04em}
.brand span{display:block;color:#e9d9c1;font-size:.78rem;margin-top:3px}

.logout a{color:white;text-decoration:none;font-size:.875rem;padding:8px 16px;border-radius:6px;border:2px solid var(--gold)}
.logout a:hover{background:var(--gold)}
.main-content{width:100%;max-width:1390px;margin:auto;padding:35px 40px 60px}

.welcome{margin-bottom:25px}
.welcome h1{font-family:Georgia,serif;font-size:30px;font-weight:normal;color:var(--wine);margin-bottom:8px}
.welcome p{color:#52677a;font-size:17px}

.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:38px}
.stat-card{background:var(--white);border:1px solid var(--line);border-top:5px solid var(--gold);min-height:145px;padding:30px}
.stat-card h3{font-size:14px;font-weight:normal;color:#52677a;letter-spacing:.08em;margin-bottom:16px}
.stat-number{font-family:Georgia,serif;font-size:48px;color:#111111}
.stat-role{font-family:Georgia,serif;font-size:38px;color:#111111}

.middle-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:25px;margin-bottom:38px}

.dashboard-card{background:var(--white);border:1px solid var(--line);border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);height:100%}
.card-top{height:100px;display:flex;align-items:center;padding:25px}
.card-top.gold{background:linear-gradient(135deg,#b99862,#d5b982)}
.card-top.blue{background:linear-gradient(135deg,var(--blue-dark),var(--blue))}
.card-top.green{background:linear-gradient(135deg,var(--green-dark),var(--green))}
.card-body{padding:25px;min-height:220px}
.card-label{display:inline-block;background:#f6f1ea;color:var(--wine);font-size:12px;font-weight:bold;letter-spacing:.06em;padding:6px 10px;border-radius:5px;margin-bottom:12px}
.card-body h2{font-family:Georgia,serif;font-size:25px;font-weight:normal;color:#111111;margin-bottom:12px}
.card-body p{color:#52677a;font-size:16px;line-height:1.5;margin-bottom:25px}

.view-btn{display:inline-block;background:white;color:var(--wine);border:1px solid var(--wine);border-radius:5px;padding:10px 20px;font-size:14px;font-weight:bold;text-decoration:none;cursor:pointer}

.view-btn:hover{background:var(--wine);color:white}

.bottom-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:25px}

.modal{display:none;position:fixed;z-index:1000;inset:0;background:rgba(30,20,20,.55);align-items:center;justify-content:center;padding:25px}
.modal-content{width:100%;max-width:720px;max-height:85vh;overflow-y:auto;background:white;border-radius:10px;box-shadow:0 15px 40px rgba(0,0,0,.25)}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:20px 25px;border-bottom:1px solid var(--line)}
.modal-header h2{font-family:Georgia,serif;font-size:24px;font-weight:normal;color:var(--wine)}
.close{font-size:28px;color:#777;cursor:pointer}
.close:hover{color:var(--wine)}
.modal-body{padding:25px}

.account-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:15px}
.account-item{background:#faf8f5;border:1px solid var(--line);border-radius:6px;padding:14px}
.account-item label{display:block;font-size:11px;color:var(--muted);text-transform:uppercase;font-weight:bold;margin-bottom:6px}
.account-item span{font-size:14px;color:#222;word-break:break-word}
.account-item.full{grid-column:span 2}

.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:15px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group label{font-size:12px;color:var(--muted);font-weight:bold}
.form-group input{width:100%;padding:11px 12px;border:1px solid var(--line);border-radius:5px;font-size:14px;outline:none}
.form-group input:focus{border-color:var(--gold)}
.form-group input[readonly]{background:#f3f1ee;color:#777}

.save-btn{margin-top:20px;background:var(--wine);color:white;border:0;border-radius:5px;padding:11px 20px;font-weight:bold;cursor:pointer}
.save-btn:hover{background:var(--wine-dark)}

.issue-item{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:16px 5px;border-bottom:1px solid #eeeeee}
.issue-item:last-child{border-bottom:0}
.issue-info strong{display:block;color:var(--wine);font-size:15px;margin-bottom:5px}
.issue-info span{display:block;color:var(--muted);font-size:13px;margin-bottom:3px}
.issue-info small{color:#999;font-size:11px}

.status{font-size:12px;font-weight:bold;white-space:nowrap}
.status.open{color:#b33}
.status.resolved{color:#388e3c}
.status.pending{color:#a87800}

.notice-item{padding:15px 5px;border-bottom:1px solid #eeeeee}
.notice-item:last-child{border-bottom:0}
.notice-item strong{display:block;color:var(--wine);font-size:15px;margin-bottom:6px}
.notice-item p{color:#555;font-size:13px;line-height:1.5;margin-bottom:5px}
.notice-item small{color:#999;font-size:11px}

.meeting-item{padding:15px 5px;border-bottom:1px solid #eeeeee}
.meeting-item:last-child{border-bottom:0}
.meeting-item strong{display:block;color:var(--wine);font-size:15px;margin-bottom:6px}
.meeting-item span{display:block;color:#555;font-size:13px;margin-bottom:4px}
.meeting-item small{color:#999;font-size:11px}
.message{padding:12px 15px;background:#edf7ed;color:#287a2f;border-radius:5px;font-size:13px;margin-bottom:20px}
.error{padding:12px 15px;background:#fbeaea;color:#a33;border-radius:5px;font-size:13px;margin-bottom:20px}
.empty{padding:25px 5px;color:var(--muted);font-size:14px}

.worker-list{display:grid;gap:12px}
.worker-card{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:16px;background:#faf8f5;border:1px solid var(--line);border-radius:6px}
.worker-info h4{margin:0 0 6px;color:var(--wine);font-size:16px;font-weight:bold}
.worker-info p{margin:4px 0;color:var(--muted);font-size:13px}
.worker-status{display:inline-block;margin-top:8px;padding:5px 9px;background:#f4e4c8;color:#7a5720;border-radius:4px;font-size:11px;font-weight:bold;text-transform:uppercase}
.worker-action{flex-shrink:0; width: 220px;}

.assign-menu{position:relative; display:flex; flex-direction:column; gap:8px; width:100%;}

.validate-worker{padding:9px 15px;background:var(--green-dark);color:#fff;border:0;border-radius:5px;font-size:13px;font-weight:bold;cursor:pointer;width:100%}
.validate-worker:hover{background:#255d50}

.role-toggle{padding:9px 12px;background:var(--wine);color:#fff;border:0;border-radius:5px;font-size:12px;font-weight:bold;cursor:pointer;width:100%;display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:8px}
.role-toggle:hover{background:var(--wine-dark)}
.role-toggle::after{content:'▾'; font-size:12px;}

.department-select{display:none; width:100%; padding:9px 10px; border:1px solid var(--line); border-radius:5px; background:#fff; color:#222; font-size:13px; margin-top:4px;}
.department-select.show{display:block;}

.no-workers{padding:30px 20px;text-align:center}
.no-workers h4{margin-bottom:8px;color:var(--wine);font-size:16px}
.no-workers p{color:var(--muted);font-size:13px}
.worker-description{margin:0 0 20px;color:var(--muted);font-size:13px}

@media(max-width:900px){
    .stats-grid{grid-template-columns:1fr}
    .middle-grid{grid-template-columns:1fr}
    .bottom-grid{grid-template-columns:1fr}
}

@media(max-width:600px){
    .topbar{padding:0 18px}
    .brand span{display:none}
    .main-content{padding:25px 18px 40px}
    .account-grid{grid-template-columns:1fr}
    .form-grid{grid-template-columns:1fr}
    .account-item.full{grid-column:span 1}
    .issue-item{align-items:flex-start;flex-direction:column}
    .worker-card{align-items:flex-start;flex-direction:column}
    .worker-action{width:100%}
    .validate-worker{width:100%}
}

    </style>

</head>

<body>

<header class="topbar">

    <div class="brand">
        <img src="Makana-Municipality logo.png" alt="Makana Municipality logo">
        <div>
            <strong>Makana Municipality</strong>
            <span>Councillor Workspace</span>
        </div>
    </div>

    <div class="logout">
        <a href="login.php">Sign out</a>
    </div>

</header>

<main class="main-content">
    <section class="welcome">
        <h1> Welcome, Councillor <?php echo htmlspecialchars($surname); ?> </h1>
        <p> Review your ward information and councillor account details. </p>
    </section>

    <!-- Statisctics -->
    <section class="stats-grid">
        <div class="stat-card">
            <h3>OPEN ISSUES</h3>
            <div class="stat-number">
                <?php echo $stats['open']; ?>
            </div>
        </div>

        <div class="stat-card">
            <h3>RESOLVED ISSUES</h3>
            <div class="stat-number">
                <?php echo $stats['resolved']; ?>
            </div>
        </div>

        <div class="stat-card">
            <h3>PENDING / IN PROGRESS</h3>
            <div class="stat-number">
                <?php echo $stats['pending']; ?>
            </div>
        </div>
    </section>

    <!-- Middle cards -->
    <section class="middle-grid">

        <!-- Recent issues -->
        <div class="dashboard-card">
            <div class="card-top gold"></div>
            <div class="card-body">
                <span class="card-label">ISSUES</span>
                <h2>Recent ward issues</h2>
                <p>  Review the latest fault reports submitted in your ward.</p>
                <button class="view-btn" onclick="openModal('issuesModal')">  View </button>
            </div>
        </div>

        <!-- Account -->
        <div class="dashboard-card">
            <div class="card-top blue"></div>
            <div class="card-body">
                <span class="card-label">ACCOUNT</span>
                <h2>Account details</h2>
                <p>  Signed in as <strong><?php echo htmlspecialchars($username); ?></strong>.  View or edit your account information. </p>
                <button class="view-btn" onclick="openModal('accountModal')"> View </button>

            </div>
        </div>

        <!-- Worker validation -->
        <div class="dashboard-card">
            <div class="card-top green"></div>
            <div class="card-body">
                <span class="card-label">MUNICIPAL WORKERS</span>
                <h2>Worker validation</h2>
                <p>Review and validate municipal workers before they can access the system.</p>
                <button class="view-btn" id="view-workers" type="button">View workers</button>
            </div>
        </div>
    </section>

    <!-- bottom cards -->
    <section class="bottom-grid">
        <!-- Manage faults -->
        <div class="dashboard-card">
            <div class="card-top gold"></div>
            <div class="card-body">
                <span class="card-label">FAULTS</span>
                <h2>Manage fault reports</h2>
                <p> Review, manage and monitor reported faults in your ward. </p>
                <a href="Councillor_managing_faults.php" class="view-btn"> View </a>
            </div>
        </div>

        <!-- reports -->
        <div class="dashboard-card">
            <div class="card-top blue"></div>
            <div class="card-body">
                <span class="card-label">REPORTS</span>
                <h2>Ward reports</h2>
                <p> View reports and information relating to your ward. </p>
                <a href="stat_reports.php" class="view-btn">  View </a>
            </div>
        </div>

        <!-- Municipal Information -->
        <div class="dashboard-card">
            <div class="card-top green"></div>
            <div class="card-body">
                <span class="card-label">MUNICIPAL</span>
                <h2>Municipal information</h2>
                <p>  View current notices and upcoming municipal meetings. </p>
                <button class="view-btn" onclick="openModal('municipalModal')"> View </button>
            </div>

        </div>

    </section>

</main>

<!-- Recent modal issues -->
<div class="modal" id="issuesModal">

    <div class="modal-content">
        <div class="modal-header">
            <h2>Recent Ward Issues</h2>
            <span class="close" onclick="closeModal('issuesModal')">
                &times;
            </span>
        </div>

        <div class="modal-body">
            <?php if (count($recent_issues) > 0): ?>
                <?php foreach ($recent_issues as $issue): ?>
                    <?php
                    $status = strtolower(trim($issue['status']));

                    if ($status === 'open') {
                        $status_class = 'open';
                    } elseif ($status === 'resolved' || $status === 'closed') {
                        $status_class = 'resolved';
                    } else {
                        $status_class = 'pending';
                    }

                    ?>

                    <div class="issue-item">
                        <div class="issue-info">
                            <strong>
                                <?php echo htmlspecialchars($issue['incident_type']); ?>
                            </strong>

                            <span>
                                <?php echo htmlspecialchars($issue['incident_address']); ?>
                            </span>

                            <small>
                                Ticket #<?php echo htmlspecialchars($issue['ticket_id']); ?>
                                | <?php echo date('d M Y', strtotime($issue['created_at'])); ?>
                            </small>
                        </div>

                        <span class="status <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($issue['status']); ?>
                        </span>
                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty">
                    No recent issues have been reported in your ward.
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Account modal -->
<div class="modal" id="accountModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Account Details</h2>
            <span class="close" onclick="closeModal('accountModal')">
                &times;
            </span>
        </div>

        <div class="modal-body">

            <?php if ($account_message !== ''): ?>

                <div class="message">
                    <?php echo htmlspecialchars($account_message); ?>
                </div>

            <?php endif; ?>


            <?php if ($account_error !== ''): ?>

                <div class="error">
                    <?php echo htmlspecialchars($account_error); ?>
                </div>

            <?php endif; ?>

            <div class="account-grid">
                <div class="account-item">
                    <label>First Name</label>
                    <span>
                        <?php echo htmlspecialchars($first_name); ?>
                    </span>
                </div>

                <div class="account-item">
                    <label>Surname</label>
                    <span>
                        <?php echo htmlspecialchars($surname); ?>
                    </span>
                </div>

                <div class="account-item">
                    <label>Username</label>
                    <span>
                        <?php echo htmlspecialchars($username); ?>
                    </span>
                </div>

                <div class="account-item">
                    <label>ID Number</label>
                    <span>
                        <?php echo htmlspecialchars($idnumber); ?>
                    </span>
                </div>

                <div class="account-item">
                    <label>Email</label>
                    <span>
                        <?php echo htmlspecialchars($email); ?>
                    </span>
                </div>

                <div class="account-item">
                    <label>Contact</label>
                    <span>
                        <?php echo htmlspecialchars($contact); ?>
                    </span>
                </div>


                <div class="account-item">
                    <label>Ward</label>
                    <span>
                        <?php echo htmlspecialchars($ward); ?>
                    </span>
                </div>

                <div class="account-item">
                    <label>Role</label>
                    <span> Councillor  </span>
                </div>
            </div>

            <br>
            <button class="view-btn" onclick="openModal('editAccountModal'); closeModal('accountModal');"> Edit Account</button>
        </div>
    </div>
</div>

/*Municipal workers validation modal*/
<div class="modal" id="worker-modal">
    <div class="modal-content">

        <div class="modal-header">
            <h2>Municipal Workers</h2>
            <span class="close" id="worker-close">&times;</span>
        </div>

        <div class="modal-body">
            <p class="worker-description">Review workers waiting for account validation.</p>

            <div class="worker-list">
                <?php if (count($workers) > 0): ?>

                    <?php foreach ($workers as $worker): ?>

                        <div class="worker-card">

                            <div class="worker-info">
                                <h4>
                                    <?php echo htmlspecialchars($worker['firstname'] . ' ' . $worker['surname']); ?>
                                </h4>

                                <p>
                                    Username: <?php echo htmlspecialchars($worker['username']); ?>
                                </p>

                                <p>
                                    Ward: <?php echo htmlspecialchars($worker['ward'] ?: 'Not assigned'); ?>
                                </p>

                                <span class="worker-status">Pending</span>
                            </div>

                            <div class="worker-action">
                                <div class="assign-menu">
                                    <button
                                        type="button"
                                        class="validate-worker"
                                        data-id="<?php echo htmlspecialchars($worker['idnumber']); ?>"
                                        data-name="<?php echo htmlspecialchars($worker['firstname'] . ' ' . $worker['surname']); ?>">
                                        Validate
                                    </button>

                                    <button
                                        type="button"
                                        class="role-toggle"
                                        data-id="<?php echo htmlspecialchars($worker['idnumber']); ?>"
                                        data-name="<?php echo htmlspecialchars($worker['firstname'] . ' ' . $worker['surname']); ?>">
                                        Assign Role
                                    </button>

                                    <select class="department-select" aria-label="Assign department to worker" data-id="<?php echo htmlspecialchars($worker['idnumber']); ?>">
                                        <option value="">Select department</option>
                                        <option value="Water &amp; Sanitation">Water &amp; Sanitation</option>
                                        <option value="Electricity / Power Outage">Electricity / Power Outage</option>
                                        <option value="Roads &amp; Potholes">Roads &amp; Potholes</option>
                                        <option value="Refuse / Waste Management">Refuse / Waste Management</option>
                                        <option value="Street Lighting">Street Lighting</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="no-workers">
                        <h4>No workers awaiting validation</h4>
                        <p>All municipal worker accounts have been reviewed.</p>
                    </div>

                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- edit account modal -->
<div class="modal" id="editAccountModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Account</h2>
            <span class="close" onclick="closeModal('editAccountModal')">
                &times;
            </span>
        </div>

        <div class="modal-body">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="firstname" value="<?php echo htmlspecialchars($first_name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Surname</label>
                        <input type="text" name="surname" value="<?php echo htmlspecialchars($surname); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($username); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>ID Number</label>
                        <input type="text" value="<?php echo htmlspecialchars($idnumber); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Contact</label>
                        <input type="text" name="contact" value="<?php echo htmlspecialchars($contact); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Ward</label>
                        <input type="text" value="<?php echo htmlspecialchars($ward); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="Councillor" readonly>
                    </div>
                </div>

                <button type="submit" name="update_account" class="save-btn"> Save Changes </button>
            </form>
        </div>
    </div>
</div>

<!-- Municiapl Information Modal -->
<div class="modal" id="municipalModal">

<div class="modal-content">

    <div class="modal-header">

        <h2>Municipal Information</h2>

        <span class="close" onclick="closeModal('municipalModal')">
            &times;
        </span>

    </div>

    <div class="modal-body">

        <h3 style="color:#4b1f2a;margin-bottom:10px;">
            Notices
        </h3>

        <?php if (count($notices) > 0): ?>
            <?php foreach ($notices as $notice): ?>

                <div class="notice-item">

                    <strong>
                        <?php echo htmlspecialchars($notice['title']); ?>
                    </strong>

                    <p>
                        <?php echo htmlspecialchars($notice['messagenotice']); ?>
                    </p>

                    <small>
                        Posted:
                        <?php echo date('d M Y', strtotime($notice['notice_date'])); ?>

                        <?php if (!empty($notice['priority'])): ?>
                            |
                            Priority:
                            <?php echo htmlspecialchars($notice['priority']); ?>
                        <?php endif; ?>
                    </small>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">
                No current municipal notices.
            </div>
        <?php endif; ?>

        <h3 style="color:#4b1f2a;margin:25px 0 10px;"> Upcoming Meetings</h3>
        <?php if (count($meetings) > 0): ?>
            <?php foreach ($meetings as $meeting): ?>
                <div class="meeting-item">
                    <strong>
                        <?php echo htmlspecialchars($meeting['event_title']); ?>
                    </strong>

                    <span>
                        <?php echo htmlspecialchars($meeting['event_location']); ?>
                    </span>

                    <small>
                        <?php echo date('d M Y, H:i', strtotime($meeting['event_date'])); ?>
                    </small>
                </div>

            <?php endforeach; ?>

        <?php else: ?>
            <div class="empty">
                No upcoming meetings scheduled.
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<script>

    function openModal(id) {
        document.getElementById(id).style.display = "flex";
    }

    function closeModal(id) {
        document.getElementById(id).style.display = "none";
    }

    window.addEventListener("click", function(event) {

        document.querySelectorAll(".modal").forEach(function(modal) {

            if (event.target === modal) {
                modal.style.display = "none";
            }

        });

    });

const workerModal = document.getElementById('worker-modal');
const workerClose = document.getElementById('worker-close');
const workerButton = document.getElementById('view-workers');

workerButton.addEventListener('click', function(event) {

    event.preventDefault();

    workerModal.style.display = 'block';

});

workerClose.addEventListener('click', function() {

    workerModal.style.display = 'none';

});

workerModal.addEventListener('click', function(event) {

    if (event.target === workerModal) {
        workerModal.style.display = 'none';
    }

});

document.querySelectorAll('.validate-worker').forEach(function(button) {

    button.addEventListener('click', function() {

        const idnumber = this.dataset.id;
        const workerName = this.dataset.name;

        const confirmed = confirm(
            'Validate ' + workerName + ' as a municipal worker?'
        );

        if (!confirmed) {
            return;
        }

        const form = document.createElement('form');

        form.method = 'POST';
        form.action = 'displayAll Councillor.php';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'approve_id';
        idInput.value = idnumber;
        form.appendChild(idInput);

        const departmentInput = document.createElement('input');
        departmentInput.type = 'hidden';
        departmentInput.name = 'department';
        departmentInput.value = 'Other';
        form.appendChild(departmentInput);

        document.body.appendChild(form);
        form.submit();

    });

});

document.querySelectorAll('.role-toggle').forEach(function(button) {

    button.addEventListener('click', function() {
        const idnumber = this.dataset.id;
        const select = document.querySelector('.department-select[data-id="' + idnumber + '"]');

        if (!select) {
            return;
        }

        select.classList.toggle('show');
        const shouldShow = select.classList.contains('show');
        this.setAttribute('aria-expanded', shouldShow ? 'true' : 'false');
    });

});

document.querySelectorAll('.department-select').forEach(function(select) {
    select.addEventListener('change', function() {
        const idnumber = this.dataset.id;
        const workerName = document.querySelector('.role-toggle[data-id="' + idnumber + '"]')?.dataset.name || 'worker';
        const department = this.value;

        if (!department) {
            return;
        }

        const confirmed = confirm('Assign ' + workerName + ' to ' + department + '?');

        if (!confirmed) {
            this.value = '';
            this.classList.remove('show');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'displayAll Councillor.php';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'approve_id';
        idInput.value = idnumber;
        form.appendChild(idInput);

        const departmentInput = document.createElement('input');
        departmentInput.type = 'hidden';
        departmentInput.name = 'department';
        departmentInput.value = department;
        form.appendChild(departmentInput);

        document.body.appendChild(form);
        form.submit();
    });
});

</script>

</body>
</html>