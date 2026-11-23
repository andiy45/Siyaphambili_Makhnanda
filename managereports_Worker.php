<?php
session_start();
require_once 'dbconnection.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$role = strtolower(trim($_SESSION['userrole'] ?? ''));

if (!isset($_SESSION['access']) || !in_array($role, ['worker', 'workers'], true)) {
    header('Location: login.html');
    exit;
}

$username = $_SESSION['username'] ?? '';

$stmt = $conn->prepare("SELECT firstname, surname, category FROM theusers WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$worker = $stmt->get_result()->fetch_assoc();
$stmt->close();

$workerName = trim(($worker['firstname'] ?? '') . ' ' . ($worker['surname'] ?? ''));
$category = trim($worker['category'] ?? '');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve'])) {
    $ticketId = $_POST['ticket_id'] ?? '';

    if ($ticketId !== '' && $category !== '') {
        $stmt = $conn->prepare("UPDATE faultticket SET status = 'Resolved' WHERE ticket_id = ? AND incident_type = ?");
        $stmt->bind_param("ss", $ticketId, $category);
        $stmt->execute();

        $message = $stmt->affected_rows > 0 ? 'Fault marked as resolved.' : 'Fault could not be updated.';
        $stmt->close();
    }
}

$faults = [];

if ($category !== '') {
    $stmt = $conn->prepare("SELECT * FROM faultticket WHERE incident_type = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $faults[] = $row;
    }

    $stmt->close();
}

$total = count($faults);
$resolved = 0;

foreach ($faults as $fault) {
    if (strtolower($fault['status'] ?? '') === 'resolved') {
        $resolved++;
    }
}

$pending = $total - $resolved;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Worker Faults | Makana Municipality</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:#f6efe6;color:#2b2022}
header{background:#4b1f2a;color:#fff;padding:22px 40px;display:flex;justify-content:space-between;align-items:center}
header h1{font-size:25px}
header p{margin-top:5px;opacity:.8}
header a{background:#fff;color:#4b1f2a;text-decoration:none;padding:10px 16px;border-radius:7px;font-weight:bold}
main{max-width:1200px;margin:30px auto;padding:0 20px}
.message{background:#e4f2e9;color:#21643f;padding:14px 18px;border-radius:8px;margin-bottom:20px}
.worker-info{background:#fff;padding:25px;border-radius:12px;margin-bottom:20px;box-shadow:0 3px 10px rgba(0,0,0,.06)}
.worker-info h2{color:#4b1f2a;margin-bottom:7px}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:25px}
.stat{background:#fff;padding:22px;border-radius:12px;box-shadow:0 3px 10px rgba(0,0,0,.06)}
.stat h3{font-size:14px;color:#777;text-transform:uppercase}
.stat strong{display:block;font-size:30px;color:#4b1f2a;margin-top:8px}
.faults{background:#fff;padding:25px;border-radius:12px;box-shadow:0 3px 10px rgba(0,0,0,.06)}
.section-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
.section-heading h2{color:#4b1f2a}
.section-heading span{background:#f1e4d8;color:#4b1f2a;padding:8px 14px;border-radius:20px;font-weight:bold}
.fault-card{border:1px solid #ddd3c8;border-radius:10px;padding:20px;margin-bottom:18px}
.fault-header{display:flex;justify-content:space-between;align-items:flex-start;gap:15px}
.fault-header h3{color:#4b1f2a;font-size:20px}
.fault-header p{color:#777;margin-top:5px}
.status{padding:7px 12px;border-radius:20px;font-size:13px;font-weight:bold}
.status.pending{background:#fff0d2;color:#8a5a00}
.status.resolved{background:#dff2e6;color:#237047}
.details{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-top:18px}
.details div{background:#f8f4ef;padding:12px;border-radius:7px}
.details strong{display:block;color:#4b1f2a;margin-bottom:5px}
.description{background:#faf8f5;padding:15px;border-radius:7px;margin:15px 0}
.description strong{color:#4b1f2a}
.description p{margin-top:6px;line-height:1.5}
form button{border:0;background:#237047;color:#fff;padding:11px 18px;border-radius:7px;font-weight:bold;cursor:pointer}
form button:hover{background:#1b5b39}
.resolved-text{color:#237047;font-weight:bold}
.empty{text-align:center;padding:40px 20px;color:#777}
.empty h3{color:#4b1f2a;margin-bottom:8px}
@media(max-width:700px){header{padding:18px 20px;flex-direction:column;align-items:flex-start;gap:15px}.stats{grid-template-columns:1fr}.section-heading{flex-direction:column;align-items:flex-start;gap:10px}.fault-header{flex-direction:column}.details{grid-template-columns:1fr}}
</style>
</head>

<body>

<header>
    <div>
        <h1>Worker Faults</h1>
        <p>Welcome, <?php echo e($workerName); ?></p>
    </div>
    <a href="WorkersDashboard.php">Back to Dashboard</a>
</header>

<main>

    <?php if ($message): ?>
        <div class="message"><?php echo e($message); ?></div>
    <?php endif; ?>

    <section class="worker-info">
        <h2><?php echo e($category ?: 'No Category Assigned'); ?></h2>
        <p>Fault reports assigned to your work category.</p>
    </section>

    <?php if ($category): ?>

        <section class="stats">
            <div class="stat">
                <h3>Total Faults</h3>
                <strong><?php echo $total; ?></strong>
            </div>

            <div class="stat">
                <h3>Pending</h3>
                <strong><?php echo $pending; ?></strong>
            </div>

            <div class="stat">
                <h3>Resolved</h3>
                <strong><?php echo $resolved; ?></strong>
            </div>
        </section>

        <section class="faults">
            <div class="section-heading">
                <h2>Fault Reports</h2>
                <span><?php echo e($category); ?></span>
            </div>

            <?php if ($faults): ?>

                <?php foreach ($faults as $fault): ?>

                    <?php $status = $fault['status'] ?? 'Pending'; ?>

                    <div class="fault-card">

                        <div class="fault-header">
                            <div>
                                <h3><?php echo e($fault['title']); ?></h3>
                                <p><?php echo e($fault['incident_type']); ?></p>
                            </div>

                            <span class="status <?php echo strtolower($status) === 'resolved' ? 'resolved' : 'pending'; ?>">
                                <?php echo e($status); ?>
                            </span>
                        </div>

                        <div class="details">
                            <div><strong>Ward</strong><?php echo e($fault['ward_number']); ?></div>
                            <div><strong>Priority</strong><?php echo e($fault['priority']); ?></div>
                            <div><strong>Address</strong><?php echo e($fault['incident_address']); ?></div>
                            <div><strong>Reported By</strong><?php echo e($fault['reported_by'] ?? 'Public user'); ?></div>
                        </div>

                        <div class="description">
                            <strong>Description</strong>
                            <p><?php echo nl2br(e($fault['incident_description'])); ?></p>
                        </div>

                        <?php if (strtolower($status) !== 'resolved'): ?>
                            <form method="post">
                                <input type="hidden" name="ticket_id" value="<?php echo e($fault['ticket_id']); ?>">
                                <button type="submit" name="resolve">✓ Mark as Resolved</button>
                            </form>
                        <?php else: ?>
                            <div class="resolved-text">✓ This fault has been resolved.</div>
                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty">
                    <h3>No Fault Reports</h3>
                    <p>There are currently no faults in your category.</p>
                </div>

            <?php endif; ?>

        </section>

    <?php else: ?>

        <div class="empty">
            <h3>No Category Assigned</h3>
            <p>Please contact the administrator to assign your work category.</p>
        </div>

    <?php endif; ?>

</main>

</body>
</html>