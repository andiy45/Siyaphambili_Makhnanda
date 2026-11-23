<html>

<body>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$role = strtolower(trim((string) ($_SESSION['userrole'] ?? '')));
if (!isset($_SESSION['access']) || !in_array($role, ['administrator', 'admin'], true)) {
  header('Location: login.html');
  exit;
}

require_once 'dbconnection.php';

function e($value): string
{
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$search = trim((string) ($_GET['search'] ?? ''));

$columns = [];
$columnResult = $conn->query('SHOW COLUMNS FROM faultticket');
if ($columnResult) {
  while ($column = $columnResult->fetch_assoc()) {
    $columns[$column['Field']] = $column;
  }
}

$idColumn = null;
foreach ($columns as $columnName => $column) {
  if ($column['Key'] === 'PRI') {
    $idColumn = $columnName;
    break;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idColumn !== null) {
  $action = $_POST['action'] ?? '';
  $recordId = trim((string) ($_POST['record_id'] ?? ''));

  if ($recordId === '') {
    $message = 'A fault record must be selected.';
  } elseif ($action === 'delete') {
    $stmt = $conn->prepare("UPDATE FROM faultticket SET accountstatus = NOW() WHERE {$idColumn} = 1");
    $stmt->bind_param('s', $recordId);
    $stmt->execute();
    $message = $stmt->affected_rows > 0 ? 'Fault deleted successfully.' : 'Fault record was not found.';
    $stmt->close();
  } elseif ($action === 'update') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $incidentType = trim((string) ($_POST['incident_type'] ?? ''));
    $description = trim((string) ($_POST['incident_description'] ?? ''));
    $priority = trim((string) ($_POST['priority'] ?? ''));
    $wardNumber = trim((string) ($_POST['ward_number'] ?? ''));
    $incidentAddress = trim((string) ($_POST['incident_address'] ?? ''));

    if ($title === '' || $incidentType === '' || $description === '' || $priority === '' || $wardNumber === '' || $incidentAddress === '') {
      $message = 'Complete all fault fields before updating.';
    } else {
      $stmt = $conn->prepare("UPDATE faultticket SET title = ?, incident_type = ?, incident_description = ?, priority = ?, ward_number = ?, incident_address = ? WHERE {$idColumn} = ?");
      $stmt->bind_param('sssssss', $title, $incidentType, $description, $priority, $wardNumber, $incidentAddress, $recordId);
      $stmt->execute();
      $message = $stmt->affected_rows >= 0 ? 'Fault updated successfully.' : 'Fault could not be updated.';
      $stmt->close();
    }
  }
}

$users = $conn->query('SELECT username, surname, firstname, idnumber, ward FROM theusers ORDER BY username');
$faults = false;
if ($idColumn !== null) {
  $faultSql = "SELECT * FROM faultticket";
  if ($search !== '') {
    $searchableColumns = array_values(array_intersect(
      ['title', 'incident_type', 'incident_description', 'priority', 'ward_number', 'incident_address', 'reported_by', 'reported_access'],
      array_keys($columns)
    ));
    if ($searchableColumns) {
      $conditions = implode(' OR ', array_map(static fn ($column) => "{$column} LIKE ?", $searchableColumns));
      $faultSql .= " WHERE {$conditions}";
      $stmt = $conn->prepare($faultSql . " ORDER BY created_at DESC");
      $term = "%{$search}%";
      $types = str_repeat('s', count($searchableColumns));
      $values = array_fill(0, count($searchableColumns), $term);
      $stmt->bind_param($types, ...$values);
      $stmt->execute();
      $faults = $stmt->get_result();
    }
  }
  if ($faults === false) {
    $faults = $conn->query($faultSql . (isset($conditions) ? '' : ' ORDER BY created_at DESC'));
  }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Records | Makana Municipality</title>
  <style>
    body { margin: 0; padding: 28px; background: #f6efe6; color: #242329; font-family: Arial, sans-serif; }
    main { max-width: 1280px; margin: 0 auto; }
    h1, h2 { color: #4b1f2a; }
    .panel { background: #fff; padding: 24px; margin: 24px 0; overflow-x: auto; }
    .notice { padding: 12px; background: #e8f2ed; color: #205b47; }
    .search { display: flex; gap: 10px; margin-bottom: 18px; }
    input, textarea, select { width: 100%; padding: 8px; border: 1px solid #cfc5bb; box-sizing: border-box; }
    textarea { min-width: 180px; }
    button, .button { border: 0; padding: 9px 13px; background: #4b1f2a; color: #fff; cursor: pointer; text-decoration: none; white-space: nowrap; }
    .delete { background: #9b2c2c; }
    button a, .button a { color: #fff; text-decoration: none; }
    table { width: 100%; border-collapse: collapse; min-width: 900px; }
    th, td { border: 1px solid #ddd3c8; padding: 9px; text-align: left; vertical-align: top; }
    th { background: #4b1f2a; color: #fff; }
    .actions { display: flex; gap: 7px; align-items: flex-start; }
    .actions form { margin: 0; }
  </style>
</head>
<body>
<main>
  <button><a href="adminDashboard.php">Back to dashboard</a></button>
  <h1>Manage Records</h1>
  <?php if ($message !== ''): ?><p class="notice"><?php echo e($message); ?></p><?php endif; ?>

  <section class="panel">
    <h2>Fault reports</h2>
    <form class="search" method="get">
      <input type="search" name="search" value="<?php echo e($search); ?>" placeholder="Search title, type, priority, ward or reporter">
      <button type="submit">Search</button>
      <a class="button" href="displayAll%20Admin.php">Clear</a>
    </form>
    <?php if ($faults && $faults->num_rows > 0): ?>
      <table>
        <thead><tr><th>Title</th><th>Type</th><th>Description</th><th>Priority</th><th>Ward</th><th>Address</th><th>Access</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($fault = $faults->fetch_assoc()): ?>
          <tr>
            <form method="post">
              <td><input name="title" value="<?php echo e($fault['title'] ?? ''); ?>" required></td>
              <td><input name="incident_type" value="<?php echo e($fault['incident_type'] ?? ''); ?>" required></td>
              <td><textarea name="incident_description" required><?php echo e($fault['incident_description'] ?? ''); ?></textarea></td>
              <td><input name="priority" value="<?php echo e($fault['priority'] ?? ''); ?>" required></td>
              <td><input name="ward_number" value="<?php echo e($fault['ward_number'] ?? ''); ?>" required></td>
              <td><textarea name="incident_address" required><?php echo e($fault['incident_address'] ?? ''); ?></textarea></td>
              <td><?php echo e(($fault['reported_access'] ?? 'Public') . ' / ' . ($fault['reported_by'] ?? 'Public user')); ?></td>
              <td class="actions">
                <input type="hidden" name="record_id" value="<?php echo e($fault[$idColumn]); ?>">
                <button type="submit" name="action" value="update">Update</button>
                <button class="delete" type="submit" name="action" value="delete" formnovalidate onclick="return confirm('Delete this fault report?');">Delete</button>
              </td>
            </form>
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