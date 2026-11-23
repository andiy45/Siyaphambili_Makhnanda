<?php
session_start();

$role = strtolower(trim((string) ($_SESSION['userrole'] ?? '')));

if (!isset($_SESSION['access']) || !in_array($role, ['manager', 'municipalitymanager', 'communicationsofficer', 'administrator', 'admin'], true)) {
    header('Location: login.html');
    exit;
}

require_once 'dbconnection.php';

function communityNoticeImageSrc(?string $imageData): ?string
{
    if ($imageData === null || $imageData === '') {
        return null;
    }

    $imageInfo = @getimagesizefromstring($imageData);
    if (!$imageInfo || empty($imageInfo['mime'])) {
        return null;
    }

    return 'data:' . $imageInfo['mime'] . ';base64,' . base64_encode($imageData);
}
$view = $_GET['view'] ?? 'dashboard';

$totalUsers = 0;
$totalFaults = 0;

$userCountResult = $conn->query("SELECT COUNT(*) AS total FROM theusers");
if ($userCountResult) {
    $row = $userCountResult->fetch_assoc();
    $totalUsers = (int) ($row['total'] ?? 0);
}

$faultResult = $conn->query("SELECT COUNT(*) AS total FROM faultticket");
if ($faultResult) {
    $row = $faultResult->fetch_assoc();
    $totalFaults = (int) ($row['total'] ?? 0);
}

$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? 'All'));
$categoryFilter = trim((string) ($_GET['category'] ?? 'All'));

$allowedStatuses = ['All', 'Open', 'Pending', 'Resolved', 'Closed', 'In Progress', 'In-Progress'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'All';
}

$categoryOptions = ['All'];
$categoryResult = $conn->query("SELECT DISTINCT incident_type FROM faultticket WHERE incident_type IS NOT NULL AND incident_type <> '' ORDER BY incident_type ASC");
if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categoryValue = trim((string) ($row['incident_type'] ?? ''));
        if ($categoryValue !== '') {
            $categoryOptions[] = $categoryValue;
        }
    }
}
if (!in_array($categoryFilter, $categoryOptions, true)) {
    $categoryFilter = 'All';
}

$all_issues = [];
$sqlError = "";
$hasStatus = false;

$colCheck = $conn->query("SHOW COLUMNS FROM faultticket LIKE 'status'");

if ($colCheck && $colCheck->num_rows > 0) {
    $hasStatus = true;
}

$sql = 'SELECT ticket_id, incident_type, incident_address, priority, created_at';
$params = [];
$types = '';

if ($hasStatus) {
    $sql .= ', status';
}

$sql .= ' FROM faultticket WHERE 1=1';

if ($search !== '') {
    $sql .= ' AND (CAST(ticket_id AS CHAR) LIKE ? OR incident_type LIKE ? OR incident_address LIKE ? OR title LIKE ? OR incident_description LIKE ?)';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like, $like];
    $types = 'sssss';
}

if ($categoryFilter !== 'All') {
    $sql .= ' AND incident_type = ?';
    $params[] = $categoryFilter;
    $types .= 's';
}

if ($hasStatus && $statusFilter !== 'All') {
    $sql .= ' AND status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

$sql .= ' ORDER BY created_at DESC';

$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($params !== []) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $all_issues[] = $row;
        }
    }
    $stmt->close();
} else {
    $sqlError = 'SQL ERROR: ' . $conn->error;
}

$displayName = trim(
    (string) ($_SESSION['firstname'] ?? '') . ' ' .
    (string) ($_SESSION['surname'] ?? '')
);

if ($displayName === '') {
    $displayName = (string) ($_SESSION['username'] ?? 'Manager');
}

$self = basename($_SERVER['PHP_SELF']);

$messagenotice = "";
$messageType = "success";

 //HANDLE COMMUNITY NOTICE APPROVAL / REJECTION//
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_notice'])) {
    $noticeId = intval($_POST['notice_id'] ?? 0);
    $newStatus = trim($_POST['new_status'] ?? '');

    if ($noticeId > 0 && in_array($newStatus, ['Approved', 'Rejected'])) {
        $stmt = $conn->prepare(
            "SELECT organisation_name, title, content, status
             FROM community_notices WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param("i", $noticeId);
        $stmt->execute();
        $communityNotice = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$communityNotice) {
            $messagenotice = "Community notice not found.";
            $messageType = "error";
        } elseif ($communityNotice['status'] !== 'Pending') {
            $messagenotice = "This community notice has already been reviewed.";
            $messageType = "error";
        } elseif ($newStatus === 'Approved') {
            $noticeDate = date('Y-m-d');
            $createdAt = date('Y-m-d H:i:s');
            $priority = 0;
            $publishedStatus = 'Open';
            $publishedTitle = $communityNotice['title'];
            $publishedContent = $communityNotice['content'];

            $publishStmt = $conn->prepare(
                "INSERT INTO notices
                 (title, messagenotice, notice_date, priority, statusnotice, created_at)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $publishStmt->bind_param(
                "sssiss",
                $publishedTitle,
                $publishedContent,
                $noticeDate,
                $priority,
                $publishedStatus,
                $createdAt
            );

            if ($publishStmt->execute()) {
                $publishStmt->close();
                $statusStmt = $conn->prepare(
                    "UPDATE community_notices SET status = 'Approved' WHERE id = ?"
                );
                $statusStmt->bind_param("i", $noticeId);
                $statusStmt->execute();
                $statusStmt->close();
                $messagenotice = "Notice #{$noticeId} approved and published.";
                $messageType = "success";
            } else {
                $messagenotice = "Error publishing notice: " . $publishStmt->error;
                $messageType = "error";
                $publishStmt->close();
            }
        } else {
            $statusStmt = $conn->prepare(
                "UPDATE community_notices SET status = 'Rejected' WHERE id = ?"
            );
            $statusStmt->bind_param("i", $noticeId);

            if ($statusStmt->execute()) {
                $messagenotice = "Notice #{$noticeId} has been rejected.";
                $messageType = "success";
            } else {
                $messagenotice = "Error updating notice: " . $statusStmt->error;
                $messageType = "error";
            }
            $statusStmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* UPDATE ACCOUNT */
    if (isset($_POST['update_account'])) {
        $idnumber = trim((string) ($_SESSION['idnumber'] ?? ''));
        $firstname = trim($_POST['firstname'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact'] ?? '');

        if ($idnumber === '' || $firstname === '' || $surname === '' || $email === '' || $contact === '') {
            $messagenotice = "Please complete all account details.";
            $messageType = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $messagenotice = "Please enter a valid email address.";
            $messageType = "error";
        } else {
            $stmt = $conn->prepare("UPDATE theusers SET firstname = ?, surname = ?, email = ?, contact = ? WHERE idnumber = ?");

            if ($stmt) {
                $stmt->bind_param("sssss", $firstname, $surname, $email, $contact, $idnumber);

                if ($stmt->execute()) {
                    $_SESSION['firstname'] = $firstname;
                    $_SESSION['surname'] = $surname;
                    $_SESSION['email'] = $email;
                    $_SESSION['contact'] = $contact;

                    $displayName = $firstname . ' ' . $surname;
                    $messagenotice = "Account details updated successfully.";
                    $messageType = "success";
                } else {
                    $messagenotice = "Error updating account details.";
                    $messageType = "error";
                }

                $stmt->close();
            } else {
                $messagenotice = "Database error: " . $conn->error;
                $messageType = "error";
            }
        }
    }

    /* POST NOTICE */
    if (isset($_POST['post_notice'])) {
        $noticeTitle = trim($_POST['notice_title'] ?? '');
        $noticemessage = trim($_POST['messagenotice'] ?? '');
        $noticedate = trim($_POST['notice_date'] ?? '');
        $priority = trim($_POST['priority'] ?? '');
        $statusnotice = trim($_POST['statusnotice'] ?? '');
        $created_at = date('Y-m-d H:i:s');

        if ($noticeTitle === '' || $noticemessage === '' || $noticedate === '') {
            $messagenotice = "Please fill in all notice fields.";
            $messageType = "error";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO notices (title, messagenotice, notice_date, priority, statusnotice, created_at) VALUES (?, ?, ?, ?, ?, ?)"
            );

            if ($stmt === false) {
                $messagenotice = "Database error: " . $conn->error;
                $messageType = "error";
            } else {
                $stmt->bind_param(
                    "ssisss",
                    $noticeTitle,
                    $noticemessage,
                    $noticedate,
                    $priority,
                    $statusnotice,
                    $created_at
                );

                if ($stmt->execute()) {
                    $messagenotice = "Notice posted successfully.";
                    $messageType = "success";
                } else {
                    $messagenotice = "Error posting notice: " . $stmt->error;
                    $messageType = "error";
                }

                $stmt->close();
            }
        }
    }

    /* UPDATE CALENDAR */
    if (isset($_POST['update_calendar'])) {
        $eventTitle = trim($_POST['event_title'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $eventLocation = trim($_POST['event_location'] ?? '');

        if ($eventTitle === '' || $eventDate === '' || $eventLocation === '') {
            $messagenotice = "Please fill in all calendar fields.";
            $messageType = "error";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO calender_events (event_title, event_date, event_location, created_by) VALUES (?, ?, ?, ?)"
            );

            if ($stmt === false) {
                $messagenotice = "Database error: " . $conn->error;
                $messageType = "error";
            } else {
                $createdBy = $_SESSION['username'] ?? 'Manager';

                $stmt->bind_param(
                    "ssss",
                    $eventTitle,
                    $eventDate,
                    $eventLocation,
                    $createdBy
                );

                if ($stmt->execute()) {
                    $messagenotice = "Event added to the calendar successfully.";
                    $messageType = "success";
                } else {
                    $messagenotice = "Error saving event: " . $stmt->error;
                    $messageType = "error";
                }

                $stmt->close();
            }
        }
    }

    /* ASSIGN WORKER / UPDATE FAULT */
    if (isset($_POST['assign_worker'])) {
        $selectedFault = trim($_POST['fault_category'] ?? '');
        $selectedPriority = trim($_POST['priority_level'] ?? '');
        $selectedWorker = trim($_POST['worker_id'] ?? '');
        $selectedStatus = trim($_POST['status'] ?? '');

        if ($selectedFault === '' || $selectedPriority === '' || $selectedWorker === '' || $selectedStatus === '') {
            $messagenotice = "Please complete all fault update fields.";
            $messageType = "error";
        } else {
            $messagenotice = "Fault update submitted successfully.";
            $messageType = "success";
        }
    }
}
 // FETCH COMMUNITY NOTICES//
$community_notices = [];
$noticeQuery = $conn->query("SELECT id, organisation_name, title, content, photo, status, date_created FROM community_notices ORDER BY date_created DESC");
if ($noticeQuery) {
    while ($row = $noticeQuery->fetch_assoc()) {
        $community_notices[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title> Manager Dashboard | Makana Municipality </title>
<head>

 <link rel="stylesheet" href="manager_dashboard.css">
<style>
:root{--ink:#242329;--muted:#706d73;--paper:#f8f4ee;--panel:#fff;--wine:#4b1f2a;--gold:#b08d57;--line:#e6ddd2;--green:#2f7463}

*{box-sizing:border-box}

body{margin:0;color:var(--ink);background:var(--paper);font-family:Georgia,'Times New Roman',serif}

.topbar{background:var(--wine);color:#fff;padding:16px clamp(20px,5vw,72px);display:flex;align-items:center;justify-content:space-between;gap:20px;min-height:75px}

.brand{display:flex;align-items:center;gap:14px}
.brand img{width:52px;height:52px;object-fit:contain;background:#fff;border-radius:50%}
.brand strong{display:block;font-size:1.05rem;letter-spacing:.04em}
.brand span{color:#e9d9c1;font:.78rem Arial,sans-serif}

.logout{color:#fff;border:1px solid #d5b987;padding:9px 14px;text-decoration:none;font:700 .8rem Arial,sans-serif}
.logout:hover{background:#fff;color:var(--wine)}

main{width:min(1180px,calc(100% - 40px));margin:42px auto 70px}

.eyebrow{color:var(--gold);font:700 .74rem Arial,sans-serif;letter-spacing:.16em;text-transform:uppercase}

h1{margin:8px 0;font-size:clamp(2rem,4vw,3.5rem);font-weight:400}

.intro{color:var(--muted);margin:0 0 30px;font:1rem/1.6 Arial,sans-serif}

.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px}
.stat{background:var(--panel);border:1px solid var(--line);padding:24px;border-top:4px solid var(--gold)}
.stat label{display:block;color:var(--muted);font:.75rem Arial,sans-serif;text-transform:uppercase;letter-spacing:.08em}
.stat strong{display:block;margin-top:10px;font-size:2.2rem;font-weight:400}

.card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:30px}
.card{background:var(--panel);border:1px solid var(--line);border-radius:8px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,.04);transition:transform .2s,box-shadow .2s;cursor:pointer;display:flex;flex-direction:column;text-decoration:none;color:inherit}
.card:hover{transform:translateY(-4px);box-shadow:0 8px 12px rgba(0,0,0,.08)}
.card-banner{height:120px;background:linear-gradient(135deg,var(--wine),#7a3244)}
.card-banner.gold{background:linear-gradient(135deg,var(--gold),#d4b88c)}
.card-banner.green{background:linear-gradient(135deg,var(--green),#4a9c87)}
.card-banner.blue{background:linear-gradient(135deg,#2c3e50,#4ca1af)}
.card-banner.grey{background:linear-gradient(135deg,#555,#999)}
.card-content{padding:20px;flex-grow:1;display:flex;flex-direction:column}
.card-tag{display:inline-block;background:var(--paper);color:var(--wine);font:700 .7rem Arial,sans-serif;padding:4px 8px;border-radius:4px;margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;width:fit-content}
.card h3{margin:0 0 10px;font-size:1.2rem;font-weight:400}
.card p{color:var(--muted);font:.85rem/1.5 Arial,sans-serif;margin:0 0 20px;flex-grow:1}
.card-btn{display:inline-block;text-align:center;background:transparent;color:var(--wine);border:1px solid var(--wine);padding:8px 16px;border-radius:4px;font:700 .8rem Arial,sans-serif;text-decoration:none;width:fit-content}
.card:hover .card-btn{background:var(--wine);color:#fff}

.modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.5)}
.modal-content{background:var(--paper);margin:5% auto;padding:30px;border:1px solid var(--line);width:min(600px,90%);border-radius:8px;max-height:85vh;overflow-y:auto;position:relative}
.close-btn{position:absolute;top:15px;right:20px;font-size:28px;font-weight:bold;color:var(--muted);cursor:pointer;border:none;background:none}
.close-btn:hover{color:var(--wine)}

.manager-form{margin-top:15px;display:grid;gap:12px}
.manager-form label{font:700 .8rem Arial,sans-serif;color:var(--muted);margin-bottom:-5px}
.manager-form input,.manager-form select,.manager-form textarea{width:100%;padding:12px;border:1px solid var(--line);font-family:Arial,sans-serif;font-size:.9rem;background:var(--panel);border-radius:4px}
.manager-form input:focus,.manager-form select:focus,.manager-form textarea:focus{outline:none;border-color:var(--gold)}
.manager-form input[readonly]{background:#eee;color:#777;cursor:not-allowed}
.manager-form button{background:var(--wine);color:#fff;border:none;padding:12px 20px;font-family:Arial,sans-serif;font-weight:bold;cursor:pointer;border-radius:4px;margin-top:10px}
.manager-form button:hover{background:#3a1720}

.alert{background:#d4edda;color:#155724;padding:15px;border:1px solid #c3e6cb;margin-bottom:20px;font-family:Arial,sans-serif;border-radius:4px}
.alert-error{background:#f8d7da;color:#721c24;border-color:#f5c6cb}

.back-btn{display:inline-block;padding:10px 18px;background:var(--wine);color:#fff;text-decoration:none;border-radius:6px;font-size:.9rem;margin-bottom:20px;font-family:Arial,sans-serif}
.back-btn:hover{opacity:.9}
.table-card{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:22px;box-shadow:0 4px 10px rgba(0,0,0,.04);overflow-x:auto}
table{width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:.9rem}
thead th{background:var(--wine);color:#fff;text-align:left;padding:12px 14px;font-size:.75rem;letter-spacing:.08em;text-transform:uppercase;white-space:nowrap}
thead th:first-child{border-top-left-radius:6px}
thead th:last-child{border-top-right-radius:6px}
tbody td{padding:12px 14px;border-bottom:1px solid var(--line);vertical-align:middle}
tbody tr:hover{background:#faf5ec}
tbody tr:last-child td{border-bottom:none}
.ticket-id{font-weight:700;color:var(--wine)}
.muted{color:var(--muted);font-size:.82rem}

.badge{display:inline-block;padding:3px 9px;border-radius:999px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.badge-Low{background:#e3f3ea;color:#2f7463}
.badge-Medium{background:#fff3d6;color:#a87800}
.badge-High{background:#fbe2e2;color:#b33}
.badge-Urgent{background:#f5c2c2;color:#7a1010}

.status-badge{display:inline-block;padding:3px 9px;border-radius:999px;font-size:.72rem;font-weight:700;text-transform:uppercase}
.status-Pending{background:#f1f1f1;color:#666}
.status-In-Progress{background:#e0ecff;color:#2b5fab}
.status-Resolved{background:#e3f3ea;color:#2f7463}
.status-Open{background:#fbe2e2;color:#b33}

.empty-row td{text-align:center;padding:40px;color:var(--muted);font-style:italic}
.profile{display:grid;gap:15px;font-family:Arial,sans-serif;font-size:.95rem;margin-top:20px}
.profile-row{display:flex;justify-content:space-between;gap:20px;border-bottom:1px solid var(--line);padding-bottom:10px}
.profile-row dt{color:var(--muted)}
.profile-row dd{margin:0;font-weight:bold;text-align:right}

@media(max-width:720px){
.stats{grid-template-columns:1fr}
.topbar{align-items:flex-start;flex-direction:column;height:auto}
.logout{white-space:nowrap}
main{width:min(calc(100% - 28px),560px);margin-top:28px}
thead{display:none}
tbody tr{display:block;border:1px solid var(--line);border-radius:8px;margin-bottom:12px;padding:10px}
tbody td{display:flex;justify-content:space-between;border:none;padding:6px 4px}
tbody td::before{content:attr(data-label);font-weight:700;color:var(--muted);font-size:.75rem;text-transform:uppercase;margin-right:12px}
.profile-row{flex-direction:column;gap:5px}
.profile-row dd{text-align:left}
}

/* --- COMMUNITY NOTICE STATUS BADGES --- */
.notice-badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.notice-Pending{background:#fff3d6;color:#a87800}
.notice-Approved{background:#e3f3ea;color:#2f7463}
.notice-Rejected{background:#fbe2e2;color:#b33}

/* --- COMMUNITY NOTICE ACTION BUTTONS --- */
.approve-btn{background:#2f7463;color:#fff;border:none;padding:6px 12px;border-radius:4px;font-size:.75rem;font-weight:bold;cursor:pointer;margin-right:5px}
.approve-btn:hover{background:#245a4e}
.reject-btn{background:#b33;color:#fff;border:none;padding:6px 12px;border-radius:4px;font-size:.75rem;font-weight:bold;cursor:pointer}
.reject-btn:hover{background:#8a2626}
</style>

</head>
<body>

<!--TOP BAR-->

<header class="topbar">
    <div class="brand">
        <img src="Makana-Municipality logo.png" alt="Makana Municipality logo">
        <div>
            <strong>Makana Municipality</strong>
            <span>Manager workspace</span>
        </div>
    </div>
    <a class="logout" href="logout.php">Sign out</a>
</header>

<!-- MAIN -->
<main>
<?php if ($view === 'all_faults'): ?>
    <!-- ALL FAULTS -->
    <a href="<?php echo htmlspecialchars($self); ?>" class="back-btn" > ← Back to Dashboard </a>
    <div class="eyebrow"> Operations centre </div>
    <h1> All Reported Faults </h1>
    <p class="intro">
        A complete list of every fault ticket submitted
        through the system.
    </p>
    <?php if ($sqlError): ?>
        <div class="alert alert-error">
            <?php
            echo htmlspecialchars(
                $sqlError,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
        </div>
    <?php endif; ?>

    <form method="get" class="filter-bar" style="display:flex; flex-wrap:wrap; gap:12px; margin:18px 0; background:#f9f4ef; border:1px solid #e2d9cd; border-radius:8px; padding:12px;">
        <input type="hidden" name="view" value="all_faults">

        <div style="display:flex; flex-direction:column; gap:6px; min-width:200px; flex:1;">
            <label for="search" style="font-size:12px; color:#5a4f50; font-weight:bold;">Search</label>
            <input id="search" type="text" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ticket, type, address..." style="padding:10px 12px; border:1px solid #d8cfc6; border-radius:6px; font-size:14px;">
        </div>

        <div style="display:flex; flex-direction:column; gap:6px; min-width:170px; flex:1;">
            <label for="status" style="font-size:12px; color:#5a4f50; font-weight:bold;">Status</label>
            <select id="status" name="status" style="padding:10px 12px; border:1px solid #d8cfc6; border-radius:6px; font-size:14px;">
                <?php foreach ($allowedStatuses as $statusOption): ?>
                    <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $statusFilter === $statusOption ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex; flex-direction:column; gap:6px; min-width:220px; flex:1;">
            <label for="category" style="font-size:12px; color:#5a4f50; font-weight:bold;">Fault Category</label>
            <select id="category" name="category" style="padding:10px 12px; border:1px solid #d8cfc6; border-radius:6px; font-size:14px;">
                <?php foreach ($categoryOptions as $categoryOption): ?>
                    <option value="<?php echo htmlspecialchars($categoryOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $categoryFilter === $categoryOption ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoryOption, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex; align-items:end; gap:8px;">
            <button type="submit" style="background:#4b1f2a; color:#fff; border:none; border-radius:6px; padding:10px 16px; font-weight:bold; cursor:pointer;">Filter</button>
            <a href="<?php echo htmlspecialchars($self . '?view=all_faults', ENT_QUOTES, 'UTF-8'); ?>" style="background:#e8ded2; color:#3d1b1f; border:none; border-radius:6px; padding:10px 16px; font-weight:bold; text-decoration:none;">Reset</a>
        </div>
    </form>

    <section class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Ticket #</th>
                    <th>Fault Type</th>
                    <th>Address</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Reported</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($all_issues) > 0): ?>
                <?php foreach ($all_issues as $issue): ?>
                    <?php
                    $priority = htmlspecialchars(
                        $issue['priority'] ?? 'Low',
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $status = htmlspecialchars(
                        $issue['status'] ?? 'Open',
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $statusClass =
                        'status-' .
                        str_replace(
                            ' ',
                            '-',
                            $status
                        );
                    ?>

                    <tr>
                        <td
                            data-label="Ticket #"
                            class="ticket-id"
                        >
                            #
                            <?php
                            echo htmlspecialchars(
                                $issue['ticket_id'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>
                        <td data-label="Fault Type">
                            <?php
                            echo htmlspecialchars(
                                $issue['incident_type'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>
                        <td data-label="Address">
                            <?php
                            echo htmlspecialchars(
                                $issue['incident_address'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>
                        <td data-label="Priority">
                            <span
                                class="badge badge-<?php
                                echo $priority;
                                ?>"
                            >
                                <?php
                                echo $priority;
                                ?>
                            </span>
                        </td>
                        <td data-label="Status">
                            <span
                                class="status-badge <?php
                                echo $statusClass;
                                ?>"
                            >
                                <?php
                                echo $status;
                                ?>
                            </span>
                        </td>
                        <td
                            data-label="Reported"
                            class="muted"
                        >
                            <?php
                            echo date(
                                'd M Y, H:i',
                                strtotime(
                                    $issue['created_at']
                                )
                            );
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>


                <tr class="empty-row">

                    <td colspan="6">

                        No faults have been
                        reported yet.

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </section>


<?php else: ?>


<!-- main dashborad -->
<div class="eyebrow">
    Operations centre
</div>

<h1>
    Welcome, <?php echo htmlspecialchars( $displayName, ENT_QUOTES,'UTF-8' );?>
</h1>

<p class="intro">
    Manage faults, assign workers,
    and update municipal notices.
</p>

<?php if ($messagenotice): ?>


    <div
        class="alert
        <?php echo $messageType === 'error'  ? 'alert-error' : ''; ?>">
        <?php echo htmlspecialchars( $messagenotice, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>


    <!-- Statistics -->

    <section
        class="stats"
        aria-label="Platform summary"
    >


        <article class="stat">

            <label>
                Registered users
            </label>

            <strong>
                <?php echo $totalUsers; ?>
            </strong>

        </article>


        <article class="stat">

            <label>
                Total Fault Reports
            </label>

            <strong>
                <?php echo $totalFaults; ?>
            </strong>

        </article>


        <article class="stat">

            <label>
                Your role
            </label>

            <strong>

                <?php

                echo htmlspecialchars(
                    ucfirst(
                        (string)
                        ($_SESSION['userrole'] ?? 'Manager')
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                );

                ?>

            </strong>

        </article>


    </section>


    <!-- ========================================================
         ACTION CARDS
         ======================================================== -->

    <section class="card-grid">

        <!-- UPDATE DAM LEVELS -->

        <div
            class="card"
            onclick="openModal('damModal')"
        >
            <div class="card-banner blue"></div>

            <div class="card-content">
                <span class="card-tag"> Water Management </span>

                <h3> Update Dam Levels </h3>

                <p>
                    Update the current water level of
                    municipal dams. Values appear on
                    the public homepage.
                </p>

                <span class="card-btn"> Update Levels </span>
            </div>
        </div>

        <!-- UPDATE FAULT -->

        <div
            class="card" onclick="openModal('faultModal')">

            <div class="card-banner gold"></div>


            <div class="card-content">

                <span class="card-tag">
                    Action Required
                </span>


                <h3>
                    Update Fault Reports
                </h3>


                <p>

                    Assign workers, set priority
                    levels, and update the status
                    of reported municipal faults.

                </p>


                <span class="card-btn">
                    View Options
                </span>

            </div>

        </div>



        <!-- POST NOTICE -->

        <div class="card" onclick="openModal('noticeModal')" >
            <div class="card-banner green"></div>
            <div class="card-content">
                <span class="card-tag"> Communication </span>
                <h3> Post a Notice </h3>
                <p>  Create and send public notices to Everyone, Residents, or Councillors. </p>
                <span class="card-btn"> Add a Notice </span>
            </div>
        </div>

        <!-- CALENDAR -->
        <div class="card" onclick="openModal('calendarModal')" >
            <div class="card-banner blue"></div>
            <div class="card-content">
                <span class="card-tag">
                    Scheduling
                </span>


                <h3>
                    Update Calendar
                </h3>


                <p>

                    Add municipal events,
                    maintenance schedules,
                    and community meetings.

                </p>


                <span class="card-btn">
                    View Options
                </span>

            </div>

        </div>


        <!-- VIEW ALL FAULTS -->

        <a
            href="<?php
            echo htmlspecialchars($self);
            ?>?view=all_faults"
            class="card"
        >

            <div class="card-banner grey"></div>


            <div class="card-content">

                <span class="card-tag">
                    Database
                </span>


                <h3>
                    View All Faults
                </h3>


                <p>

                    See a complete list of all
                    reported faults currently
                    in the system database.

                </p>


                <span class="card-btn">
                    View Table
                </span>

            </div>

        </a>


        <!-- ACCOUNT -->

        <div
            class="card"
            onclick="openModal('accountModal')"
        >

            <div class="card-banner"></div>


            <div class="card-content">

                <span class="card-tag">
                    Profile
                </span>


                <h3>
                    Account Details
                </h3>


                <p>

                    View your personal manager
                    account information and
                    contact details.

                </p>


                <span class="card-btn">
                    View Details
                </span>

            </div>

        </div>


    </section>
 
       
    <section style="margin-top:50px;">
        <div class="eyebrow">Community Submissions</div>
        <h2 style="font-family:Georgia, serif; font-weight:400; font-size:2rem; margin:8px 0 10px;">Community Notices Review</h2>
        <p class="intro">Review notices submitted by Community Organisations. Approve them to publish on the public homepage, or reject them.</p>

        <div class="table-card">
           <table>
    <thead>
        <tr>
            <th>Organisation</th>
            <th>Notice Title</th>
            <th>Details</th>
            <th>Photo</th>
            <th>Submitted</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($community_notices) > 0): ?>
            <?php foreach ($community_notices as $notice): ?>
                <?php 
                    $statusClass = 'notice-' . strtolower($notice['status']); 
                    $details = htmlspecialchars(substr($notice['content'], 0, 80), ENT_QUOTES, 'UTF-8');
                    if (strlen($notice['content']) > 80) $details .= '...';
                ?>
                <tr>
                    <td data-label="Organisation">
                        <strong style="color:var(--wine);"><?php echo htmlspecialchars($notice['organisation_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    </td>
                    <td data-label="Notice Title">
                        <?php echo htmlspecialchars($notice['title'], ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td data-label="Details" class="muted">
                        <?php echo $details; ?>
                    </td>
                    <td data-label="Photo">
                        <?php $src = communityNoticeImageSrc($notice['photo'] ?? null); ?>
                        <?php if ($src !== null): ?>
                            <img src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>" alt="Notice Photo" class="notice-thumb" onclick="openPhotoModal(<?php echo htmlspecialchars(json_encode($src, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>)">
                        <?php else: ?>
                            <span class="muted">No photo</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Submitted" class="muted">
                        <?php echo date('d M Y, H:i', strtotime($notice['date_created'])); ?>
                    </td>
                    <td data-label="Status">
                        <span class="notice-badge <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($notice['status'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td data-label="Action">
                        <?php if (strtolower($notice['status']) === 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="notice_id" value="<?php echo $notice['id']; ?>">
                                <input type="hidden" name="new_status" value="Approved">
                                <button type="submit" name="action_notice" class="approve-btn">Approve</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="notice_id" value="<?php echo $notice['id']; ?>">
                                <input type="hidden" name="new_status" value="Rejected">
                                <button type="submit" name="action_notice" class="reject-btn">Reject</button>
                            </form>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr class="empty-row">
                <td colspan="7">No community notices have been submitted yet.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
        </div>
    </section>

<?php endif; ?>
</main>


<!-- MODAL: UPDATE DAM LEVELS -->

<div id="damModal" class="modal">

    <div class="modal-content">

        <button
            class="close-btn"
            onclick="closeModal('damModal')"
        >
            &times;
        </button>

        <h2> Update Dam Level </h2>

        <p>
            Select a dam and enter its new
            water level percentage (0–100).
        </p>

        <form
            class="manager-form"
            method="POST"
            action="update_dam.php"
        >

            <label for="dam_id"> Select Dam </label>

            <select name="dam_id" id="dam_id" required>
                <option value=""> -- Select a dam -- </option>

                <?php
                // Load dams fresh for the modal
                $damOptions = [];
                $damQuery = mysqli_query(
                    $conn,
                    "SELECT id, dam_name, level FROM dam_levels ORDER BY dam_name ASC"
                );
                if ($damQuery) {
                    while ($d = mysqli_fetch_assoc($damQuery)) {
                        $damOptions[] = $d;
                    }
                }
                ?>

                <?php foreach ($damOptions as $d): ?>
                    <option value="<?php echo (int) $d['id']; ?>">
                        <?php echo htmlspecialchars($d['dam_name'], ENT_QUOTES, 'UTF-8'); ?>
                        - <?php echo htmlspecialchars($d['level'], ENT_QUOTES, 'UTF-8'); ?>%
                    </option>
                <?php endforeach; ?>

            </select>

            <label for="level"> New Dam Level (%) </label>

            <input
                type="number"
                name="level"
                id="level"
                min="0"
                max="100"
                step="0.01"
                placeholder="e.g. 85"
                required
            >

            <button type="submit" name="update_dam">
                Update Dam Level
            </button>

        </form>

    </div>

</div>

<!-- MODAL: UPDATE FAULT -->

<div id="faultModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal('faultModal')" > &times; </button>
        <h2> Update Fault Report </h2>
        <p>
            Select a reported fault category,
            set the priority, and assign it
            to a worker.
        </p>

        <form class="manager-form" method="POST" action="" >
            <label> Select Fault Category </label>
            <select name="fault_category" required >
                <option value=""> Select category... </option>
                <option value="Water & Sanitation"> Water & Sanitation </option>
                <option value="Electricity / Power Outage"> Electricity / Power Outage </option>
                <option value="Roads & Potholes"> Roads & Potholes </option>
                <option value="Refuse / Waste Management"> Refuse / Waste Management </option>
                <option value="Street Lighting"> Street Lighting </option>
                <option value="Other"> Other </option>
            </select>

            <label>
                Priority Level *
            </label>


            <select
                name="priority_level"
                required
            >

                <option value="">
                    Select priority...
                </option>

                <option value="Low">
                    Low
                </option>

                <option value="Medium">
                    Medium
                </option>

                <option value="High">
                    High
                </option>

                <option value="Urgent">
                    Urgent / Emergency
                </option>

            </select>


            <label>
                Assign Worker
            </label>


            <select
                name="worker_id"
                required
            >

                <option value="">
                    -- Choose a worker --
                </option>

                <option value="101">
                    John Doe (Plumbing)
                </option>

                <option value="102">
                    Jane Smith (Roads)
                </option>

                <option value="103">
                    Mike Johnson (Electrical)
                </option>

            </select>


            <label>
                Update Status
            </label>


            <select
                name="status"
                required
            >

                <option value="Pending">
                    Pending
                </option>

                <option value="In Progress">
                    In Progress
                </option>

                <option value="Resolved">
                    Resolved
                </option>

            </select>


            <button
                type="submit"
                name="assign_worker"
            >
                Submit Update
            </button>


        </form>

    </div>

</div>

<!-- MODAL: POST NOTICE -->
<div id="noticeModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal('noticeModal')">  &times;</button>
        <h2> Post a Notice </h2>
        <p> Create a public notice and select who should receive it. </p>
        <form class="manager-form" method="POST" action="manager_dashboard.php">
            <label> Notice Title </label>
            <input type="text" name="notice_title" placeholder="Notice Title" required>
            <label> Notice Content </label>
            <textarea name="messagenotice" rows="5" placeholder="Write your notice here..."></textarea>
            <label> Notice date </label>
            <input type="date" name="notice_date">
            <label> Date Issued </label>
            <input type="date" name="created_at">
            <label> Priority </label>
            <select name="priority" required>
                <option value="">Select a Priority level</option>
                <option value="low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
            </select>
            <label> Status </label>
            <select name="statusnotice" required> 
                <option value=""> Select a status...</option>
                <option value="open"> Open </option>
                <option value="closed"> Closed </option>
            </select>
            <button type="submit" name="post_notice"> Publish Notice </button>
        </form>
    </div>
</div>

<!-- MODAL: CALENDAR -->

<div id="calendarModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal('calendarModal')"> &times; </button>
        <h2>
            Update Calendar
        </h2>
        <p>
            Add municipal events,
            maintenance schedules,
            and community meetings.
        </p>
        <form class="manager-form" method="POST" action="" >
            <label> Event Title </label>
            <input type="text" name="event_title" placeholder="Event Title (e.g., Water Maintenance)" required>
            <label> Event Date </label>
            <input type="date" name="event_date" required >
            <label> Location / Area </label>
            <input type="text" name="event_location" placeholder="Location / Area" required >
            <button type="submit" name="update_calendar"> Add to Calendar </button>
        </form>
    </div>
</div>

<!-- MODAL: ACCOUNT -->
<div id="accountModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal('accountModal')">&times;</button>

        <h2>Account Details</h2>
        <p>Edit your personal manager account information below.</p>

        <form class="manager-form" method="POST" action="">

            <label for="firstname">First Name</label>
            <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($_SESSION['firstname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="surname">Surname</label>
            <input type="text" id="surname" name="surname" value="<?php echo htmlspecialchars($_SESSION['surname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="username">Username</label>
            <input type="text" id="username" value="<?php echo htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly>

            <label for="idnumber">ID Number</label>
            <input type="text" id="idnumber" value="<?php echo htmlspecialchars($_SESSION['idnumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="contact">Contact</label>
            <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($_SESSION['contact'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

            <button type="submit" name="update_account">Save Changes</button>

        </form>
    </div>
</div>

<!-- Javascript -->
<script>
function openModal(modalId) {
    const modal =
        document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeModal(modalId) {
    const modal =
        document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

window.onclick = function(event) {
    if (
        event.target.classList.contains('modal')
    ) {
        event.target.style.display = 'none';
    }
};

document.addEventListener(
    'keydown',
    function(event) {
        if (event.key === 'Escape') {
            document
                .querySelectorAll('.modal')
                .forEach(function(modal) {
                    modal.style.display = 'none';
                });
        }
    }
);

</script>
</body>
</html>