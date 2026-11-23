<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'dbconnection.php';

$departments = [
    'Water & Sanitation',
    'Electricity / Power Outage',
    'Roads & Potholes',
    'Refuse / Waste Management',
    'Street Lighting',
    'Other'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {

    $idnumber = trim($_POST['approve_id']);
    $department = trim($_POST['department'] ?? '');

    if ($idnumber !== '' && in_array($department, $departments, true)) {

        $stmt = $conn->prepare(" 
            UPDATE theusers
            SET validated = 1, category = ?
            WHERE idnumber = ?
            AND validated = 0
            AND LOWER(userrole) IN ('worker', 'municipal worker', 'municipal_worker')
        ");

        if ($stmt && $stmt->bind_param("ss", $department, $idnumber) && $stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();
            header('Location: councillor.php?msg=Worker approved successfully');
            exit;
        }

        if ($stmt) {
            $stmt->close();
        }

        header('Location: councillor.php?msg=Worker approval failed');
        exit;
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=Please select a department');
    exit;
}

if (isset($_GET['delete_id']) && isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {

    $idnumber = trim($_GET['delete_id']);

    if ($idnumber !== '') {

        $stmt = $conn->prepare("
            DELETE FROM theusers
            WHERE idnumber = ?
            AND LOWER(userrole) IN ('worker', 'municipal worker', 'municipal_worker')
        ");

        $stmt->bind_param("s", $idnumber);

        if ($stmt->execute()) {
            $stmt->close();

            header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=Worker deleted successfully');
            exit;
        }

        $stmt->close();
    }
}

if (isset($_GET['delete_id']) && !isset($_GET['confirm'])) {

    $idnumber = trim($_GET['delete_id']);

    $stmt = $conn->prepare("
        SELECT username, firstname, surname
        FROM theusers
        WHERE idnumber = ?
        AND LOWER(userrole) IN ('worker', 'municipal worker', 'municipal_worker')
    ");

    $stmt->bind_param("s", $idnumber);
    $stmt->execute();

    $result = $stmt->get_result();
    $worker = $result->fetch_assoc();

    $stmt->close();

    if ($worker) {
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Confirm Worker Deletion</title>

    <style>
        *{box-sizing:border-box;margin:0;padding:0}

        body{background:#f8f4ee;color:#242329;font-family:Arial,sans-serif;min-height:100vh;padding:40px 20px}

        .confirm-box{width:min(500px,100%);margin:60px auto;padding:30px;background:#fff;border:1px solid #e6ddd2;border-top:5px solid #4b1f2a;border-radius:8px;box-shadow:0 8px 20px rgba(75,31,42,.12)}

        .confirm-box h2{margin-bottom:12px;color:#4b1f2a;font-size:1.5rem;text-align:center}

        .confirm-box>p{margin-bottom:20px;color:#706d73;line-height:1.5;text-align:center}

        .worker-details{margin:20px 0;padding:16px;background:#f8f4ee;border-radius:6px}

        .worker-details p{margin:8px 0;color:#242329}

        .worker-details strong{color:#4b1f2a}

        .warning{margin:20px 0;padding:12px;background:#f8f4ee;color:#4b1f2a;border-radius:5px;font-weight:700;text-align:center}

        .actions{display:flex;justify-content:center;gap:10px;margin-top:20px}

        .btn{display:inline-block;padding:10px 18px;border:0;border-radius:5px;font-size:.85rem;font-weight:700;text-decoration:none;cursor:pointer}

        .btn-danger{background:#4b1f2a;color:#fff}

        .btn-danger:hover{background:#3a1720}

        .btn-cancel{background:#e0d5ca;color:#4b1f2a}

        .btn-cancel:hover{background:#d0c5ba}

        @media(max-width:600px){
            .actions{flex-direction:column}
            .btn{width:100%;text-align:center}
        }
    </style>

</head>

<body>

<div class="confirm-box">

    <h2>Confirm Worker Deletion</h2>

    <p>Are you sure you want to delete this municipal worker?</p>

    <div class="worker-details">

        <p><strong>Username:</strong> <?php echo htmlspecialchars($worker['username']); ?></p>

        <p><strong>Name:</strong> <?php echo htmlspecialchars($worker['firstname'] . ' ' . $worker['surname']); ?></p>

    </div>

    <div class="warning">
        This action cannot be undone.
    </div>

    <div class="actions">

        <a class="btn btn-danger" href="?delete_id=<?php echo urlencode($idnumber); ?>&confirm=yes">
            Delete Worker
        </a>

        <a class="btn btn-cancel" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            Cancel
        </a>

    </div>

</div>

</body>

</html>

<?php
        exit;
    }
}

$workers = $conn->query("
    SELECT username, surname, firstname, idnumber, ward, category, email, contact
    FROM theusers
    WHERE validated = 0
    AND LOWER(userrole) IN ('worker', 'municipal worker', 'municipal_worker')
    ORDER BY firstname, surname
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Validate Municipal Workers</title>

    <style>
        *{box-sizing:border-box;margin:0;padding:0}

        body{background:#f8f4ee;color:#242329;font-family:Arial,sans-serif;min-height:100vh;padding:30px}

        .page{width:min(1400px,100%);margin:0 auto}

        .page-header{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:25px;padding:20px 24px;background:#4b1f2a;color:#fff;border-radius:8px}

        .page-header h1{font-size:1.5rem;font-weight:400}

        .page-header p{margin-top:5px;color:#e9d9c1;font-size:.85rem}

        .back-btn{display:inline-block;padding:10px 16px;background:#fff;color:#4b1f2a;border-radius:5px;font-size:.8rem;font-weight:700;text-decoration:none;white-space:nowrap}

        .back-btn:hover{background:#e9d9c1}

        .message{margin-bottom:20px;padding:12px 15px;background:#dceee8;color:#2f7463;border:1px solid #c4ddd5;border-radius:5px;font-size:.9rem}

        .section-heading{margin-bottom:15px}

        .section-heading h2{color:#4b1f2a;font-size:1.4rem;font-weight:400}

        .section-heading p{margin-top:5px;color:#706d73;font-size:.9rem}

        .table-container{overflow-x:auto;background:#fff;border:1px solid #e6ddd2;border-radius:8px;box-shadow:0 4px 10px rgba(75,31,42,.05)}

        table{width:100%;border-collapse:collapse}

        th{padding:13px 12px;background:#4b1f2a;color:#fff;font-size:.78rem;font-weight:700;text-align:left;white-space:nowrap}

        td{padding:13px 12px;border-bottom:1px solid #e6ddd2;color:#333;font-size:.85rem;vertical-align:middle}

        tbody tr:last-child td{border-bottom:0}

        tbody tr:hover{background:#f9f5f0}

        .worker-name{color:#4b1f2a;font-weight:700}

        .department-select{width:220px;padding:8px 10px;background:#fff;border:1px solid #d8cec3;border-radius:5px;color:#333;font-size:.8rem;outline:none}

        .department-select:focus{border-color:#b08d57}

        .status{display:inline-block;padding:5px 9px;background:#f4e4c8;color:#7a5720;border-radius:4px;font-size:.7rem;font-weight:700;text-transform:uppercase}

        .actions{display:flex;justify-content:center;align-items:center;gap:8px;flex-wrap:wrap}

        .btn{display:inline-block;padding:7px 13px;border:0;border-radius:4px;font-size:.75rem;font-weight:700;text-decoration:none;cursor:pointer}

        .btn-approve{background:#2f7463;color:#fff}

        .btn-approve:hover{background:#255d50}

        .btn-delete{background:#4b1f2a;color:#fff}

        .btn-delete:hover{background:#3a1720}

        .no-records{padding:40px 20px;color:#706d73;text-align:center}

        @media(max-width:900px){
            body{padding:15px}
            .page-header{align-items:flex-start;flex-direction:column}
            .back-btn{width:100%;text-align:center}
        }

        @media(max-width:768px){
            th,td{padding:10px 8px;font-size:.75rem}
            .actions{flex-direction:column}
            .btn{width:100%;text-align:center}
            .department-select{width:100%}
        }
    </style>

</head>

<body>

<div class="page">

    <header class="page-header">

        <div>
            <h1>Municipal Worker Validation</h1>
            <p>Review worker accounts and assign them to a department before approval.</p>
        </div>

        <a class="back-btn" href="councillorDashboard.php">
            Back to Dashboard
        </a>

    </header>

    <?php if (isset($_GET['msg'])): ?>

        <div class="message">
            ✓ <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>

    <?php endif; ?>

    <section class="section-heading">

        <h2>Workers Awaiting Validation</h2>

        <p>
            Assign each worker to the department they will work under before validating their account.
        </p>

    </section>

    <div class="table-container">

        <table>

            <thead>

                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>ID Number</th>
                    <th>Ward</th>
                    <th>Department</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>

            </thead>

            <tbody>

                <?php if ($workers && $workers->num_rows > 0): ?>

                    <?php while ($worker = $workers->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($worker['username']); ?>
                            </td>

                            <td class="worker-name">
                                <?php echo htmlspecialchars($worker['firstname'] . ' ' . $worker['surname']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($worker['idnumber']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($worker['ward'] ?: 'Not assigned'); ?>
                            </td>

                            <td>

                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">

                                    <input type="hidden" name="approve_id" value="<?php echo htmlspecialchars($worker['idnumber']); ?>">

                                    <select class="department-select" name="department" required>

                                        <option value="" disabled selected>
                                            Select department
                                        </option>

                                        <?php foreach ($departments as $department): ?>

                                            <option value="<?php echo htmlspecialchars($department); ?>">
                                                <?php echo htmlspecialchars($department); ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                            </td>

                            <td>
                                <?php echo htmlspecialchars($worker['contact'] ?: 'Not provided'); ?>
                            </td>

                            <td>
                                <span class="status">Pending</span>
                            </td>

                            <td>

                                <div class="actions">

                                    <button
                                        class="btn btn-approve"
                                        type="submit"
                                        onclick="return confirm('Validate this municipal worker and assign the selected department?');">
                                        Validate
                                    </button>

                                    <a
                                        class="btn btn-delete"
                                        href="?delete_id=<?php echo urlencode($worker['idnumber']); ?>">
                                        Delete
                                    </a>

                                </div>

                            </td>

                        </form>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="8" class="no-records">
                            No municipal workers are currently awaiting validation.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>