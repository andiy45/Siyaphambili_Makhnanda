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
$columnResult = $conn->query('SHOW COLUMNS FROM theusers');
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

$accountStatusColumn = $columns['accountstatus'] ?? null;
$inactiveAccountStatus = 'Inactive';
if ($accountStatusColumn !== null) {
  $accountStatusType = strtolower((string) ($accountStatusColumn['Type'] ?? ''));
  if (preg_match('/tinyint|smallint|mediumint|int|decimal|float|double|bit/', $accountStatusType)) {
    $inactiveAccountStatus = '0';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idColumn !== null) {
  $action = $_POST['action'] ?? '';
  $recordId = trim((string) ($_POST['record_id'] ?? ''));

  if ($recordId === '') {
    $message = 'A user record must be selected.';
  } elseif ($action === 'delete') {
    if ($accountStatusColumn === null) {
      $message = 'The accountstatus field is not available, so the account could not be switched off.';
    } else {
      $stmt = $conn->prepare("UPDATE theusers SET accountstatus = ? WHERE {$idColumn} = ?");
      $stmt->bind_param('ss', $inactiveAccountStatus, $recordId);
      $stmt->execute();
      $message = $stmt->affected_rows > 0 ? 'User account switched off successfully.' : 'User record was not found or is already switched off.';
      $stmt->close();
    }
  } elseif ($action === 'update') {
    $firstname = trim((string) ($_POST['firstname'] ?? ''));
    $surname = trim((string) ($_POST['surname'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $idnumber = trim((string) ($_POST['idnumber'] ?? ''));
    $ward = trim((string) ($_POST['ward'] ?? ''));
    $userrole = trim((string) ($_POST['userrole'] ?? ''));

    if ($firstname === '' || $surname === '' || $username === '' || $idnumber === '' || $ward === '' || $userrole === '') {
      $message = 'Complete all user fields before updating.';
    } else {
      $stmt = $conn->prepare("UPDATE theusers SET firstname = ?, surname = ?, username = ?, idnumber = ?, ward = ?, userrole = ? WHERE {$idColumn} = ?");
      $stmt->bind_param('sssssss', $firstname, $surname, $username, $idnumber, $ward, $userrole, $recordId);
      $stmt->execute();
      $message = $stmt->affected_rows >= 0 ? 'User updated successfully.' : 'User could not be updated.';
      $stmt->close();
    }
  }
}

$users = false;
if ($idColumn !== null) {
  $userSql = "SELECT {$idColumn}, username, surname, firstname, idnumber, ward, userrole";
  if ($accountStatusColumn !== null) {
    $userSql .= ", accountstatus";
  }
  $userSql .= " FROM theusers";

  if ($search !== '') {
    $searchableColumns = array_values(array_intersect(
      ['username', 'surname', 'firstname', 'idnumber', 'ward', 'userrole'],
      array_keys($columns)
    ));
    if ($searchableColumns) {
      $conditions = implode(' OR ', array_map(static fn ($column) => "{$column} LIKE ?", $searchableColumns));
      $stmt = $conn->prepare($userSql . " WHERE {$conditions} ORDER BY username");
      $term = "%{$search}%";
      $types = str_repeat('s', count($searchableColumns));
      $values = array_fill(0, count($searchableColumns), $term);
      $stmt->bind_param($types, ...$values);
      $stmt->execute();
      $users = $stmt->get_result();
    }
  }
  if ($users === false) {
    $users = $conn->query($userSql . ' ORDER BY username');
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
    <h2>User records</h2>
    <form class="search" method="get">
      <input type="search" name="search" value="<?php echo e($search); ?>" placeholder="Search username, name, ID, ward or role">
      <button type="submit">Search</button>
      <a class="button" href="displayAll%20Admin.php">Clear</a>
    </form>
    <?php if ($users && $users->num_rows > 0): ?>
      <table>
        <thead><tr><th>First name</th><th>Surname</th><th>Username</th><th>ID number</th><th>Ward</th><th>Role</th><th>Account status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($user = $users->fetch_assoc()): ?>
          <tr>
            <form method="post">
              <td><input name="firstname" value="<?php echo e($user['firstname'] ?? ''); ?>" required></td>
              <td><input name="surname" value="<?php echo e($user['surname'] ?? ''); ?>" required></td>
              <td><input name="username" value="<?php echo e($user['username'] ?? ''); ?>" required></td>
              <td><input name="idnumber" value="<?php echo e($user['idnumber'] ?? ''); ?>" required></td>
              <td><input name="ward" value="<?php echo e($user['ward'] ?? ''); ?>" required></td>
              <td><input name="userrole" value="<?php echo e($user['userrole'] ?? ''); ?>" required></td>
              <td><?php echo e($user['accountstatus'] ?? 'Active'); ?></td>
              <td class="actions">
                <input type="hidden" name="record_id" value="<?php echo e($user[$idColumn]); ?>">
                <button type="submit" name="action" value="update">Update</button>
                <button class="delete" type="submit" name="action" value="delete" formnovalidate onclick="return confirm('Switch off this user account?');">Switch off</button>
              </td>
            </form>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No user records found.</p>
    <?php endif; ?>
  </section>
  </main>
</body> 
</html>