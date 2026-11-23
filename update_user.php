<?php
session_start();
$role = strtolower(trim((string) ($_SESSION['userrole'] ?? '')));
if (!isset($_SESSION['access']) || !in_array($role, ['administrator', 'admin'], true)) {
    header('Location: login.html');
    exit;
}
require_once 'dbconnection.php';

$error = '';

// determine id
$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
if ($id <= 0) {
    header('Location: displayAll Admin.php?msg=' . urlencode('Invalid user ID'));
    exit;
}

// fetch columns available in theusers
$columns = [];
$colRes = $conn->query("SHOW COLUMNS FROM theusers");
if ($colRes) {
    while ($c = $colRes->fetch_assoc()) {
        $columns[] = $c['Field'];
    }
}

// Known updatable fields (fallback if SHOW COLUMNS fails)
$known_fields = ['username','firstname','surname','idnumber','ward','email','contact','userrole','access'];
if (empty($columns)) {
    $allowed = $known_fields;
} else {
    $allowed = array_values(array_intersect($columns, $known_fields));
}

$debug = (isset($_GET['debug']) && $_GET['debug'] == '1');

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [];
    $values = [];
    $types = '';
    foreach ($allowed as $field) {
        if (array_key_exists($field, $_POST)) {
            $updates[] = "`$field` = ?";
            $values[] = $_POST[$field];
            $types .= 's';
        }
    }

    if (count($updates) > 0) {
        $sql = "UPDATE theusers SET " . implode(', ', $updates) . " WHERE id = ?";
        $types .= 'i';
        $values[] = $id;

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $error = 'Prepare failed: ' . $conn->error;
        } else {
            // bind params dynamically (need references)
            $bind_params = [];
            $bind_params[] = $types;
            for ($i = 0; $i < count($values); $i++) {
                $bind_params[] = &$values[$i];
            }
            call_user_func_array([$stmt, 'bind_param'], $bind_params);
            if ($stmt->execute()) {
                // check affected rows to ensure something changed
                if ($stmt->affected_rows > 0) {
                    $stmt->close();
                    header('Location: displayAll Admin.php?msg=' . urlencode('User updated successfully'));
                    exit;
                } else {
                    // no rows changed (maybe values identical)
                    $error = 'No rows updated. The submitted values may be identical to existing values.';
                    // append debug info for troubleshooting
                    $debugVals = array_map(function($v){ return is_scalar($v) ? (string)$v : json_encode($v); }, $values);
                    $error .= ' SQL: ' . htmlspecialchars($sql ?? '') . ' Types: ' . htmlspecialchars($types) . ' Values: ' . htmlspecialchars(json_encode($debugVals));
                    error_log('update_user debug: SQL=' . $sql . ' types=' . $types . ' values=' . json_encode($debugVals) . ' stmt_error=' . $stmt->error);
                }
            } else {
                $error = 'Execute failed: ' . $stmt->error;
                error_log('update_user error: ' . $stmt->error . ' SQL=' . $sql);
            }
            $stmt->close();
        }
    } else {
        $error = 'No updatable fields present.';
    }
}

// fetch current user data (support environments without mysqlnd)
$stmt = $conn->prepare('SELECT * FROM theusers WHERE id = ? LIMIT 1');
if ($stmt === false) {
    header('Location: displayAll Admin.php?msg=' . urlencode('DB error preparing user lookup: ' . $conn->error));
    exit;
}
$stmt->bind_param('i', $id);
$stmt->execute();
if (method_exists($stmt, 'get_result')) {
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
} else {
    // fallback: bind result dynamically
    $meta = $stmt->result_metadata();
    $fields = [];
    $row = [];
    while ($field = $meta->fetch_field()) {
        $fields[] = $field->name;
        $row[$field->name] = null;
    }
    $meta->free();
    $bindVars = [];
    foreach ($fields as $f) {
        $bindVars[] = &$row[$f];
    }
    call_user_func_array([$stmt, 'bind_result'], $bindVars);
            if ($stmt->execute()) {
        $user = $row;
    } else {
        $user = null;
    }
}
if (!$user) {
    header('Location: displayAll Admin.php?msg=' . urlencode('User not found'));
                    $debugVals = array_map(function($v){ return is_scalar($v) ? (string)$v : json_encode($v); }, $values);
                    $error = 'No rows updated. The submitted values may be identical to existing values.';
                    if ($debug) {
                        $error .= ' SQL: ' . htmlspecialchars($sql ?? '') . ' Types: ' . htmlspecialchars($types) . ' Values: ' . htmlspecialchars(json_encode($debugVals));
                    }
                    error_log('update_user debug: SQL=' . $sql . ' types=' . $types . ' values=' . json_encode($debugVals) . ' stmt_error=' . $stmt->error);
?>
<!DOCTYPE html>
                $debugVals = array_map(function($v){ return is_scalar($v) ? (string)$v : json_encode($v); }, $values);
                $error = 'Execute failed: ' . $stmt->error;
                if ($debug) {
                    $error .= ' SQL: ' . htmlspecialchars($sql ?? '') . ' Types: ' . htmlspecialchars($types) . ' Values: ' . htmlspecialchars(json_encode($debugVals));
                }
                error_log('update_user error: ' . $stmt->error . ' SQL=' . $sql);
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Update User</title>
    <style>
        body { font-family: Arial, sans-serif; background:#fff; padding:20px; }
        .card { max-width:700px; margin:20px auto; border:1px solid #eee; padding:20px; border-radius:8px; box-shadow:0 4px 18px rgba(0,0,0,0.04); }
        label { display:block; font-weight:700; margin-top:12px; color:#333; }
        input[type=text], select { width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; margin-top:6px; }
        .actions { margin-top:18px; display:flex; gap:10px; }
        .btn { background:#4b1f2a; color:#fff; padding:10px 14px; border-radius:6px; text-decoration:none; border:none; cursor:pointer; }
        .btn.secondary { background:#e0d5ca; color:#4b1f2a; }
        .error { background:#f8d7da; color:#721c24; padding:10px; border-radius:6px; margin-bottom:10px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Update User</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $id . ($debug ? '&debug=1' : ''), ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($debug): ?><input type="hidden" name="debug" value="1"><?php endif; ?>
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
            <?php foreach ($allowed as $field): ?>
                <label for="<?php echo $field; ?>"><?php echo ucfirst($field); ?></label>
                <?php if ($field === 'userrole' || $field === 'access'): ?>
                    <select name="<?php echo $field; ?>" id="<?php echo $field; ?>">
                        <?php
                        // provide some reasonable options for roles/access
                        $options = [];
                        if ($field === 'userrole') { $options = ['resident','councillor','manager','administrator','admin']; }
                        if ($field === 'access') { $options = ['yes','no']; }
                        foreach ($options as $opt) {
                            $sel = (isset($user[$field]) && $user[$field] == $opt) ? 'selected' : '';
                            echo "<option value='".htmlspecialchars($opt)."' $sel>".htmlspecialchars(ucfirst($opt))."</option>";
                        }
                        ?>
                    </select>
                <?php else: ?>
                    <input type="text" name="<?php echo $field; ?>" id="<?php echo $field; ?>" value="<?php echo isset($user[$field]) ? htmlspecialchars($user[$field]) : ''; ?>">
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="actions">
                <button class="btn" type="submit">Save changes</button>
                <a class="btn secondary" href="displayAll Admin.php">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
