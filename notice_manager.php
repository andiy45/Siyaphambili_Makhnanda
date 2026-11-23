<?php
session_start();

require_once "dbconnection.php";

/*
|--------------------------------------------------------------------------
| ACCESS CONTROL
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], ['administrator', 'manager'], true)) {
    header("Location: login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| STATE & DATA INITIALIZATION
|--------------------------------------------------------------------------
*/
$editMode = false;
$editNotice = null;

// Fetch Notice for Editing (Prepared Statement)
if (isset($_GET['edit'])) {
    $notice_id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

    if ($notice_id) {
        $sql = "SELECT * FROM notices WHERE notice_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $notice_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                $editNotice = $row;
                $editMode = true;
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch All Notices
$sqlNotices = "SELECT * FROM notices ORDER BY notice_date DESC, created_at DESC";
$resultNotices = mysqli_query($conn, $sqlNotices);

// Statistics Queries
$activeResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM notices WHERE statusnotice = 'Active'");
$activeRow = mysqli_fetch_assoc($activeResult);
$activeNotices = (int)($activeRow['total'] ?? 0);

$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM notices");
$totalRow = mysqli_fetch_assoc($totalResult);
$totalNotices = (int)($totalRow['total'] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Notices | Makana Municipality</title>
    <link rel="stylesheet" href="notice_manager.css">
</head>
<body>

<header class="manager-header">
    <div class="header-left">
        <img src="Makana-Municipality logo.png" alt="Makana Municipality Logo" class="municipality-logo">
        <div>
            <h1>Notice Management</h1>
            <p>Makana Municipality</p>
        </div>
    </div>
    <div class="header-right">
        <a href="HomeAfterLogIn.php" class="home-button">Home</a>
        <a href="logout.php" class="logout-button">Logout</a>
    </div>
</header>

<main class="manager-container">

    <div class="page-heading">
        <div>
            <h2>Manage Municipal Notices</h2>
            <p>Add, edit, activate or remove notices displayed on the municipality website.</p>
        </div>
    </div>

    <!-- STATISTICS -->
    <section class="statistics">
        <div class="stat-card">
            <div class="stat-number"><?php echo $totalNotices; ?></div>
            <div class="stat-label">Total Notices</div>
        </div>
        <div class="stat-card active-stat">
            <div class="stat-number"><?php echo $activeNotices; ?></div>
            <div class="stat-label">Active Notices</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo max(0, $totalNotices - $activeNotices); ?></div>
            <div class="stat-label">Inactive Notices</div>
        </div>
    </section>

    <!-- NOTICE FORM -->
    <section class="notice-form-section">
        <div class="section-title">
            <h2><?php echo $editMode ? "Edit Notice" : "Add New Notice"; ?></h2>
        </div>

        <form action="savenotices.php" method="POST" class="notice-form">

    <?php if ($editMode): ?>
        <input type="hidden"
               name="notice_id"
               value="<?php echo htmlspecialchars((string)$editNotice['notice_id']); ?>">
    <?php endif; ?>

    <div class="form-group">
        <label for="title">Notice Title</label>
        <input
            type="text", 
            id="title"
            name="title"
            placeholder="Enter notice title"
            required
            value="<?php echo $editMode ? htmlspecialchars($editNotice['title']) : ''; ?>"
        >
    </div>

    <div class="form-group">
        <label for="message">Notice Message</label>
        <textarea
            id="message"
            name="message"
            rows="6"
            placeholder="Enter the full notice..."
            required
        ><?php echo $editMode ? htmlspecialchars($editNotice['messagenotice']) : ''; ?></textarea>
    </div>

    <div class="form-group">
        <label for="notice_date">Date Issued</label>
        <input
            type="date"
            id="notice_date"
            name="notice_date"
            required
            value="<?php echo $editMode ? htmlspecialchars($editNotice['notice_date']) : date('Y-m-d'); ?>"
        >
    </div>

    <div class="form-group">
        <label for="priority">Priority</label>
        <select id="priority" name="priority">
            <option value="Normal">Normal</option>
            <option value="Important">Important</option>
            <option value="Urgent">Urgent</option>
        </select>
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>
    </div>

    <div class="form-buttons">
        <button type="submit" class="save-button">
            Add Notice
        </button>
    </div>

</form>
    </section>

    <!-- EXISTING NOTICES -->
    <section class="existing-notices">
        <div class="section-title">
            <h2>Existing Notices</h2>
            <p>These are the notices currently stored in the system.</p>
        </div>

        <?php if ($resultNotices && mysqli_num_rows($resultNotices) > 0): ?>
            <div class="notice-list">
                <?php while ($notice = mysqli_fetch_assoc($resultNotices)): ?>
                    <article class="notice-card">
                        <div class="notice-card-top">
                            <div>
                                <h3><?php echo htmlspecialchars($notice['title']); ?></h3>
                                <p class="notice-date">
                                    <?php echo date('d F Y', strtotime($notice['notice_date'])); ?>
                                </p>
                            </div>
                            <span class="status-badge <?php echo htmlspecialchars(strtolower($notice['statusnotice'])); ?>">
                                <?php echo htmlspecialchars($notice['statusnotice']); ?>
                            </span>
                        </div>

                        <p class="notice-message">
                            <?php echo nl2br(htmlspecialchars($notice['messagenotice'])); ?>
                        </p>

                        <div class="notice-card-footer">
                            <div class="notice-info">
                                <span class="priority-badge <?php echo htmlspecialchars(strtolower($notice['priority'])); ?>">
                                    <?php echo htmlspecialchars($notice['priority']); ?>
                                </span>
                            </div>

                            <div class="notice-actions">
                                <a href="notice_manager.php?edit=<?php echo urlencode($notice['notice_id']); ?>" class="edit-button">Edit</a>
                                <a href="delete_notice.php?id=<?php echo urlencode($notice['notice_id']); ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this notice?');">Delete</a>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-notices">
                <h3>No Notices Yet</h3>
                <p>There are currently no municipal notices in the system.</p>
            </div>
        <?php endif; ?>
    </section>

</main>

<footer class="manager-footer">
    <p>Brought to you by Makana Municipality in collaboration with MyMakhanda Tech.</p>
    <p>&copy; <?php echo date('Y'); ?> Makana Municipality</p>
</footer>

</body>
</html>