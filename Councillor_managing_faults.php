<?php
//DATABASE CONNECTION
require_once 'dbconnection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


//USER ROLE AND ACCESS

$role = strtolower(trim((string) ($_SESSION['userrole'] ?? '')));
$allowedRoles = ['councillor'];

if (!isset($_SESSION['access']) || !in_array($role, $allowedRoles, true)) {
    header('Location: login.html');
    exit;
}

$isCouncillor = true;

//Ward guard: councillors must have a ward
$councillorWard = $_SESSION['ward'] ?? $_SESSION['ward'] ?? null;

if ($councillorWard === null || $councillorWard === '') {
    die('<p style="font-family:Arial;padding:40px;text-align:center;">
        Your account has no ward assigned.<br>Please contact an administrator.
    </p>');
}
$councillorWard = (int) $councillorWard;

//Resolve ward label for display
$wardLabel = 'Ward ' . $councillorWard;
$wStmt = $conn->prepare("SELECT ward_name FROM ward WHERE ward_id = ?");
$wStmt->bind_param('i', $councillorWard);
$wStmt->execute();
$wRow = $wStmt->get_result()->fetch_assoc();
$wStmt->close();
if ($wRow) $wardLabel = $wRow['ward_name'];

//Permission flags
$isCouncillor = in_array($role,['councillor'],true);


//Security function
function e($value): string {
    return htmlspecialchars (
        (string) $value, ENT_QUOTES, 'UTF-8');
}


//Variables
$message = '';
$search = trim((string) ($_GET['search'] ?? ''));


//Get faultticket columns
$columns = [];
$columnResult = $conn->query('SHOW COLUMNS FROM faultticket');

if ($columnResult) {
    while ($column = $columnResult->fetch_assoc()) {
        $columns[$column['Field']] = $column;
    }
}


// Find primary key

$idColumn = null;

foreach ($columns as $columnName => $column) {
    if ($column['Key'] === 'PRI') {
        $idColumn = $columnName;
        break;
    }
}


// POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idColumn !== null) {
    $action   = $_POST['action'] ?? '';
    $recordId = trim((string) ($_POST['record_id'] ?? ''));

    if ($recordId === '') {
        $message = 'A fault record must be selected.';
    } else {
        //Fetch the ticket's ward to enforce access
        $check = $conn->prepare(
            "SELECT ward_number FROM faultticket WHERE {$idColumn} = ?"
        );
        $check->bind_param('s', $recordId);
        $check->execute();
        $ticketRow = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$ticketRow) {
            $message = 'Ticket not found.';
        } elseif ((int) $ticketRow['ward_number'] !== $councillorWard) {
            $message = 'Access denied: this ticket is not in your ward.';
        } else {
            //Allowed: the ticket IS in this councillor's ward

            if ($action === 'delete') {
                $del = $conn->prepare(
                    "DELETE FROM faultticket WHERE {$idColumn} = ?"
                );
                $del->bind_param('s', $recordId);
                $del->execute();
                $message = $del->affected_rows > 0
                    ? 'Fault deleted successfully.'
                    : 'Fault record was not found.';
                $del->close();

            } elseif ($action === 'update') {
                $title           = trim((string) ($_POST['title'] ?? ''));
                $incidentType    = trim((string) ($_POST['incident_type'] ?? ''));
                $description     = trim((string) ($_POST['incident_description'] ?? ''));
                $priority        = trim((string) ($_POST['priority'] ?? ''));
                $incidentAddress = trim((string) ($_POST['incident_address'] ?? ''));

                //Ignore the ward_number POST value. force the ticket's existing ward
                $wardValue = (int) $ticketRow['ward'];

                if ($title === '' || $incidentType === '' || $description === ''
                    || $priority === '' || $incidentAddress === '') {
                    $message = 'Complete all fault fields before updating.';
                } else {
                    $stmt2 = $conn->prepare("UPDATE faultticket SET title = ?, incident_type = ?, incident_description = ?, priority = ?, ward = ?, incident_address = ?
                    WHERE {$idColumn} = ?");
                    $stmt2->bind_param('ssssisi', $title, $incidentType, $description, $priority, $wardValue, $incidentAddress, $recordId);
                    $stmt2->execute();
                    $message = 'Fault updated successfully.';
                    $stmt2->close();
                }

            } elseif ($action === 'status') {
                $newStatus = trim((string) ($_POST['status'] ?? ''));
                $validStatuses = ['Open', 'Pending', 'Resolved', 'Closed'];

                if (!in_array($newStatus, $validStatuses, true)) {
                    $message = 'Invalid status value.';
                } else {
                    $lookup = $conn->prepare("SELECT status FROM faultticket WHERE {$idColumn} = ?");
                    $lookup->bind_param('s', $recordId);
                    $lookup->execute();
                    $current = $lookup->get_result()->fetch_assoc();
                    $lookup->close();

                    if (!$current) {
                        $message = 'Fault record was not found.';
                    } elseif ($current['status'] === $newStatus) {
                        $message = "Ticket is already marked as {$newStatus}.";
                    } else {
                        $upd = $conn->prepare(
                            "UPDATE faultticket SET status = ? WHERE {$idColumn} = ?");
                        $upd->bind_param('ss', $newStatus, $recordId);
                        $upd->execute();
                        $upd->close();

                        $now       = date('Y-m-d H:i:s');
                        $changedBy = $_SESSION['username'] ?? $role;

                        $log = $conn->prepare(
                            "INSERT INTO ticketstatus (status_type, updated_by, changed_at, ticket_id)
                             VALUES (?, ?, ?, ?)");
                        $log->bind_param('ssss', $newStatus, $changedBy, $now, $recordId);
                        $log->execute();
                        $log->close();

                        $message = "Status changed from " . e($current['status'])
                                 . " to " . e($newStatus) . ".";
                    }
                }
            }
        }
    }
}


//Get Users

$users = $conn->query(
    'SELECT username, surname, firstname, idnumber, ward FROM theusers ORDER BY username');


//Get Faults

$faults = false;
if ($idColumn !== null) {
    $searchableColumns = array_values(array_intersect(
        ['title', 'incident_type', 'incident_description', 'priority',
         'incident_address', 'reported_by', 'reported_access'],
        array_keys($columns)
    ));

    // Base query. always restrict to this councillor's ward
    $sql    = "SELECT * FROM faultticket WHERE ward_number = ?";
    $params = [$councillorWard];
    $types  = 'i';

    // Search filter
    if ($search !== '' && !empty($searchableColumns)) {
        $conditions = implode(' OR ', array_map(
            static fn ($col) => "{$col} LIKE ?",
            $searchableColumns
        ));
        $sql .= " AND ({$conditions})";

        $term = "%{$search}%";
        foreach ($searchableColumns as $_) {
            $params[] = $term;
            $types   .= 's';
        }
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $faults = $stmt->get_result();
    $stmt->close();
}


    //Show All Faults

    else {

        $faults = $conn->query( "SELECT * FROM faultticket ORDER BY created_at DESC");
    }


?>


<!DOCTYPE html>

<html lang="en">

<head>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Records | Makana Municipality</title>


  <style>

  * {
    box-sizing: border-box;
    }


  body {
    margin: 0;
    padding: 28px;
    background: #f6efe6;
    color: #242329;
    font-family: Arial,sans-serif;
    }

  main {
    max-width: 1280px;
    margin: 0 auto;
    }


  h1,h2 {
      color: #4b1f2a;
  }


  h1 {
      margin-bottom: 10px;
  }


  .panel {
    background: #ffffff;
    padding: 24px;
    margin: 24px 0;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    overflow-x: auto;
  }


  .notice {
    padding: 12px 15px;
    background: #e8f2ed;
    color: #205b47;
    border-radius: 6px;
  }


.search {
    display: flex;
    gap: 10px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}


input,
textarea,
select {
  width: 100%;
  padding: 8px;
  border: 1px solid #cfc5bb;
  border-radius: 5px;
  font-family: inherit;
}

textarea {
    min-width: 180px;
    min-height: 80px;
    resize: vertical;
}

button,.button {
    border: 0;
    padding: 9px 13px;
    background: #4b1f2a;
    color: #ffffff;
    cursor: pointer;
    text-decoration: none;
    white-space: nowrap;
    border-radius: 5px;
    display: inline-block;
}

button:hover,.button:hover {
    opacity: 0.9;
}

.delete {
    background: #9b2c2c;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1100px;
}

th,td {
    border: 1px solid #ddd3c8;
    padding: 9px;
    text-align: left;
    vertical-align: top;
}


th {
    background: #4b1f2a;
    color: #ffffff;
}

td {
    background: #ffffff;
}

.actions {
    min-width: 190px;
}

.fault-picture {
    width: 96px;
    height: 72px;
    object-fit: cover;
    border-radius: 5px;
    border: 1px solid #ddd3c8;
}

.no-picture {
    color: #666;
    font-size: 13px;
    white-space: nowrap;
}

.actions form {
    margin-bottom: 7px;
}

.actions button {
    width: 100%;
    margin-bottom: 5px;
}

.status-form {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.status-form select {
    min-width: 130px;
}

.edit-form {
    display: contents;
}

.back-button {
    margin-bottom: 15px;
}

.role-info {
font-size: 14px;
color: #666;
margin-bottom: 20px;
}

@media (max-width: 700px) {

    body {
        padding: 15px;
    }

    .panel {
        padding: 15px;
    }

    .search {
        flex-direction: column;
    }

    .search input,.search button,.search .button {
        width: 100%;
    }

    }

    </style>

</head>


<body>


<main>


  <!-- BACK TO DASHBOARD -->
<div class="back-button">

    <a class="button" href="councillor.php"> Back to dashboard</a>
</div>

  <!-- PAGE TITLE -->
 <h1>Manage Records <?php echo e($wardLabel); ?></h1>
<p class="role-info">
    Logged in as: <strong><?php echo e($_SESSION['username'] ?? $role); ?></strong>
    | Role: <strong><?php echo e($role); ?></strong>
    | Ward: <strong><?php echo e($wardLabel); ?></strong>
</p>


  <!-- MESSAGE -->
  <?php if ($message !== ''): ?>
      <p class="notice">
          <?php echo e($message); ?>
      </p>
  <?php endif; ?>


<!-- FAULT REPORTS -->

<section class="panel">

    <h2>
        Fault Reports
    </h2>


    <!-- SEARCH -->
    <form class="search" method="get">

        <input type="search" name="search" value="<?php echo e($search); ?>" placeholder="Search title, type, priority, ward or reporter">
        <button type="submit">Search </button>
        <a class="button" href="Councillor_managing_faults.php">Clear</a>
    </form>


    <?php if ($faults && $faults->num_rows > 0): ?>
<table>
    <div style="background:#f0e8dd;padding:10px 14px;border-radius:6px;margin-bottom:15px;font-size:14px;">
    Showing fault reports for <strong><?php echo e($wardLabel); ?></strong> only.
    </div>
    <thead>
        <tr>
            <th>Title</th>
            <th>Type</th>
            <th>Picture</th>
            <th>Description</th>
            <th>Priority</th>
            <th> Address</th>
            <th>Status</th>
            <th>Access</th>
            <th>Actions</th>
        </tr>

    </thead>


<tbody>
<?php while ($fault = $faults->fetch_assoc()): ?>
    <tr>
        <!-- TITLE -->
        <td>
            <?php if ($isCouncillor): ?>
                <input form="update_<?php echo e($fault[$idColumn]); ?>" name="title" value="<?php echo e($fault['title'] ?? ''); ?>" required>
            <?php else: ?>
            <?php echo e($fault['title'] ?? ''); ?>
            <?php endif; ?>
        </td>


        <!-- INCIDENT TYPE -->
        <td>
            <?php if ($isCouncillor): ?>
                <input form="update_<?php echo e($fault[$idColumn]); ?>" name="incident_type" value="<?php echo e($fault['incident_type'] ?? ''); ?>" required>
            <?php else: ?>
                <?php echo e($fault['incident_type'] ?? ''); ?>
            <?php endif; ?>
        </td>


        <!-- PICTURE -->
        <td>
            <?php
            $picture = $fault['picture'] ?? null;
            $pictureInfo = is_string($picture) && $picture !== ''
                ? @getimagesizefromstring($picture)
                : false;
            ?>
            <?php if ($pictureInfo && isset($pictureInfo['mime'])): ?>
                <img
                    class="fault-picture"
                    src="data:<?php echo e($pictureInfo['mime']); ?>;base64,<?php echo base64_encode($picture); ?>"
                    alt="Fault report picture"
                >
            <?php else: ?>
                <span class="no-picture">No picture</span>
            <?php endif; ?>
        </td>


<!-- DESCRIPTION -->
<td>
    <?php if ($isCouncillor): ?>
        <textarea
            form="update_<?php echo e($fault[$idColumn]); ?>"
            name="incident_description"
            required
        ><?php echo e($fault['incident_description'] ?? ''); ?></textarea>
    <?php else: ?>
        <?php echo nl2br(e($fault['incident_description'] ?? '')); ?>
    <?php endif; ?>
</td>


<!--PRIORITY -->
<td>
  <?php if ($isCouncillor): ?>
      <input form="update_<?php echo e($fault[$idColumn]); ?>" name="priority" value="<?php echo e($fault['priority'] ?? ''); ?>" required >
  <?php else: ?>
      <?php echo e($fault['priority'] ?? ''); ?>
  <?php endif; ?>
</td>


<!-- ADDRESS -->
<td>
    <?php if ($isCouncillor): ?>
        <textarea form="update_<?php echo e($fault[$idColumn]); ?>" name="incident_address" required>
        <?php echo e($fault['incident_address'] ?? ''); ?></textarea>
    <?php else: ?>
        <?php echo nl2br( e($fault['incident_address'] ?? '') ); ?> 
      <?php endif; ?>
</td>

<!-- STATUS -->
<td>
  <?php $statuses = [ 'Open','Pending', 'Resolved', 'Closed']; $currentStatus = $fault['status'] ?? 'Open';?>

<form class="status-form" method="post">
  <input type="hidden" name="record_id" value="<?php echo e($fault[$idColumn]); ?>">
  <select name="status">
      <?php foreach ($statuses as $s): ?>
          <option value="<?php echo e($s); ?>" <?php echo $s === $currentStatus ? 'selected' : ''; ?> >
              <?php echo e($s); ?>
          </option>
      <?php endforeach; ?>
  </select>

  <button type="submit" name="action" value="status" >Save Status</button>
</form>
</td>

<!-- ACCESS -->
<td>
  <?php echo e(($fault['reported_access'] ?? 'Public')  . ' / ' . ($fault['reported_by'] ?? 'Public user') ); ?>
</td>

<!-- ACTIONS -->
<td class="actions">
<?php if ($isCouncillor): ?>

<!-- UPDATE FORM -->
<form id="update_<?php echo e($fault[$idColumn]); ?>" method="post">                            
    <input type="hidden" name="record_id" value="<?php echo e($fault[$idColumn]); ?>" >
    <button type="submit" name="action" value="update" > Update </button>
</form>


<!-- DELETE FORM -->
<form method="post" onsubmit="return confirm('Delete this fault report?');">
    <input type="hidden" name="record_id" value="<?php echo e($fault[$idColumn]); ?>" >
    <button class="delete" type="submit" name="action" value="delete" >Delete </button>
</form>

        <?php endif; ?>
    </td>
</tr>

<?php endwhile; ?>
</tbody>

</table>

<?php else: ?>
    <p>No fault reports found.</p>
<?php endif; ?>
  </section>


</main>


</body>

</html>