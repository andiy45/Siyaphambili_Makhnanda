<?php
session_start();
require_once 'dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $idnumber = $_SESSION['idnumber'] ?? '';
    $firstname = trim($_POST['firstname'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $phyAddress = trim($_POST['phyAddress'] ?? '');

    if ($idnumber !== '' && $firstname !== '' && $surname !== '' && $email !== '' && $contact !== '') {
        $stmt = $conn->prepare("UPDATE theusers SET firstname = ?, surname = ?, email = ?, contact = ?, phyAddress = ? WHERE idnumber = ?");
        $stmt->bind_param("ssssss", $firstname, $surname, $email, $contact, $phyAddress, $idnumber);

        if ($stmt->execute()) {
            $_SESSION['firstname'] = $firstname;
            $_SESSION['surname'] = $surname;
            $_SESSION['email'] = $email;
            $_SESSION['contact'] = $contact;
            $_SESSION['phyAddress'] = $phyAddress;
            $accountMessage = "Account details updated successfully.";
        } else {
            $accountMessage = "Unable to update account details.";
        }

        $stmt->close();
    } else {
        $accountMessage = "Please complete all required fields.";
    }
}

$role = strtolower(trim((string) ($_SESSION['userrole'] ?? '')));
if (!isset($_SESSION['access']) || !in_array($role, ['administrator', 'admin'], true)) {
    header('Location: login.html');
    exit;
}

require_once 'dbconnection.php';

$totalUsers = 0;
$roleCounts = [];
$totalFaults = null;

$userCountResult = $conn->query('SELECT COUNT(*) AS total FROM theusers');
if ($userCountResult) {
    $totalUsers = (int) $userCountResult->fetch_assoc()['total'];
}

$roleResult = $conn->query('SELECT userrole, COUNT(*) AS total FROM theusers GROUP BY userrole ORDER BY total DESC');
if ($roleResult) {
    while ($roleRow = $roleResult->fetch_assoc()) {
        $roleCounts[$roleRow['userrole'] ?: 'Unassigned'] = (int) $roleRow['total'];
    }
}

$faultResult = $conn->query('SELECT COUNT(*) AS total FROM faultticket');
if ($faultResult) {
    $totalFaults = (int) $faultResult->fetch_assoc()['total'];
}

$displayName = trim((string) ($_SESSION['firstname'] ?? '') . ' ' . (string) ($_SESSION['surname'] ?? ''));
if ($displayName === '') {
    $displayName = (string) ($_SESSION['username'] ?? 'Administrator');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard | Makana Municipality</title>
    <style>
        /* Adopted manager dashboard styling for consistent grids and buttons */
        :root {
            --ink: #242329;
            --muted: #706d73;
            --paper: #f8f4ee;
            --panel: #ffffff;
            --wine: #4b1f2a;
            --gold: #b08d57;
            --line: #e6ddd2;
            --green: #2f7463;
        }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--paper); font-family: Georgia, 'Times New Roman', serif; }
        .topbar { background: var(--wine); color: #fff; padding: 16px clamp(20px, 5vw, 72px); display: flex; align-items: center; justify-content: space-between; gap: 20px; min-height: 75px; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand img { width: 52px; height: 52px; object-fit: contain; background: #fff; border-radius: 50%; }
        .brand strong { display: block; font-size: 1.05rem; letter-spacing: .04em; }
        .brand span { color: #e9d9c1; font: .78rem Arial, sans-serif; }
        .logout { color: #fff; border: 1px solid #d5b987; padding: 9px 14px; text-decoration: none; font: 700 .8rem Arial, sans-serif; }
        .logout:hover { background: #ffffff; color: var(--wine); }
        main { width: min(1180px, calc(100% - 40px)); margin: 42px auto 70px; }
        .eyebrow { color: var(--gold); font: 700 .74rem Arial, sans-serif; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 8px 0 8px; font-size: clamp(2rem, 4vw, 3.5rem); font-weight: 400; }
        .intro { color: var(--muted); margin: 0 0 30px; font: 1rem/1.6 Arial, sans-serif; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
        .stat, .panel { background: var(--panel); border: 1px solid var(--line); }
        .stat { padding: 24px; border-top: 4px solid var(--gold); }
        .stat label { display: block; color: var(--muted); font: .75rem Arial, sans-serif; text-transform: uppercase; letter-spacing: .08em; }
        .stat strong { display: block; margin-top: 10px; font-size: 2.2rem; font-weight: 400; }
        .workspace { display: grid; grid-template-columns: 1.2fr .8fr; gap: 20px; }
        .panel { padding: 26px; }
        h2 { margin: 0 0 20px; font-size: 1.35rem; font-weight: 400; }
        .role-row { display: flex; justify-content: space-between; border-bottom: 1px solid var(--line); padding: 13px 0; font: .95rem Arial, sans-serif; }
        .role-row:last-child { border-bottom: 0; }
        .role-row b { color: var(--green); }
        .profile { display: grid; gap: 13px; margin: 0; font: .92rem Arial, sans-serif; }
        .profile div { display: flex; justify-content: space-between; gap: 16px; border-bottom: 1px solid var(--line); padding-bottom: 10px; }
        .profile dt { color: var(--muted); }
        .profile dd { margin: 0; text-align: right; overflow-wrap: anywhere; }

        /* Modal (copy from manager styles) */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,.5); }
        .modal-content { background-color: var(--paper); margin: 5% auto; padding: 20px; border: 1px solid var(--line); width: min(820px, 95%); border-radius: 8px; max-height: 85vh; overflow-y: auto; position: relative; }
        .modal-header { margin-bottom: 12px; }
        .modal-header h3 { margin: 0; font-size: 1.25rem; color: var(--wine); }
        .modal-body-content { margin-top: 6px; }
        .modal-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .modal-table th, .modal-table td { border: 1px solid var(--line); padding: 8px 10px; text-align: left; }
        .modal-table th { background: var(--panel); color: var(--muted); font-weight: 700; }
        .modal-actions { margin-top: 16px; display:flex; gap:10px; justify-content:flex-end; }
        .modal .btn { padding: 8px 14px; border-radius:6px; font-weight:700; text-decoration:none; display:inline-block; }
        .modal .btn.primary { background: var(--wine); color: #fff; border: none; }
        .modal .btn.secondary { background: #e0d5ca; color: var(--wine); border: none; }
        .close-btn { position: absolute; top: 12px; right: 16px; font-size: 28px; font-weight: bold; color: var(--muted); cursor: pointer; border: none; background: none; }
        .close-btn:hover { color: var(--wine); }
        .notice { color: var(--muted); font: .88rem/1.5 Arial, sans-serif; }

        /* Card grid and card button styles copied from manager layout */
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 30px; grid-auto-rows: 1fr; }
        .card { background: var(--panel); border: 1px solid var(--line); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.04); transition: transform .2s, box-shadow .2s; cursor: pointer; display: flex; flex-direction: column; text-decoration: none; color: inherit; height: 260px; }
        .card:hover { transform: translateY(-4px); box-shadow: 0 8px 12px rgba(0,0,0,0.08); }
        .card-banner { height: 80px; background: linear-gradient(135deg, var(--wine), #7a3244); }
        .card-banner.gold { background: linear-gradient(135deg, var(--gold), #d4b88c); }
        .card-banner.green { background: linear-gradient(135deg, var(--green), #4a9c87); }
        .card-banner.blue { background: linear-gradient(135deg, #2c3e50, #4ca1af); }
        .card-banner.grey { background: linear-gradient(135deg, #555, #999); }
        .card-content { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; overflow: auto; }
        .card-tag { display: inline-block; background: var(--paper); color: var(--wine); font: 700 .7rem Arial, sans-serif; padding: 4px 8px; border-radius: 4px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: .05em; width: fit-content; }
        .card h3 { margin: 0 0 10px; font-size: 1.2rem; font-weight: 400; }
        .card p { color: var(--muted); font: .85rem/1.5 Arial, sans-serif; margin: 0 0 20px; flex-grow: 1; }
        .card-btn { display: inline-block; text-align: center; background: transparent; color: var(--wine); border: 1px solid var(--wine); padding: 8px 16px; border-radius: 4px; font: 700 .8rem Arial, sans-serif; text-decoration: none; width: fit-content; }
        .card:hover .card-btn { background: var(--wine); color: white; }

        /* Form button styles */
        .btn { background: var(--wine); color: white; border: none; padding: 10px 16px; font-family: Arial, sans-serif; font-weight: 700; cursor: pointer; border-radius: 6px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #3a1720; }

        @media (max-width: 720px) {
            .topbar { align-items: flex-start; }
            .stats, .workspace { grid-template-columns: 1fr; }
            .logout { white-space: nowrap; }
            main { width: min(100% - 28px, 560px); margin-top: 28px; }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="brand">
        <img src="Makana-Municipality logo.png" alt="Makana Municipality logo">
        <div><strong>Makana Municipality</strong><span>Administrator workspace</span></div>
    </div>
    <a class="logout" href="logout.php">Sign out</a>
</header>

<main>
    <div class="eyebrow">Control centre</div>
    <h1>Welcome, <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="intro">Review the current platform overview and administrator account details.</p>

    <section class="stats" aria-label="Platform summary">
        <article class="stat"><label>Registered users</label><strong><?php echo $totalUsers; ?></strong></article>
        <article class="stat"><label>Reported faults</label><strong><?php echo $totalFaults === null ? '—' : $totalFaults; ?></strong></article>
        <article class="stat"><label>Your role</label><strong><?php echo htmlspecialchars(ucfirst((string) $_SESSION['userrole']), ENT_QUOTES, 'UTF-8'); ?></strong></article>
    </section>

    <section class="card-grid" aria-label="Admin panels">
        <div class="card">
            <div class="card-banner gold"></div>
            <div class="card-content">
                <span class="card-tag">Users</span>
                <h3>Users by role</h3>
                <div class="notice" id="roles-summary">
                    <p class="notice">Total users: <strong><?php echo $totalUsers; ?></strong>. Click "View" for full breakdown.</p>
                </div>
                <div style="margin-top:auto"><a href="#" class="card-btn" id="view-roles">View</a></div>
            </div>
        </div>

        <div class="card">
            <div class="card-banner blue"></div>
            <div class="card-content">
                <span class="card-tag">Account</span>
                <h3>Account details</h3>
                <div id="account-summary">
                    <p class="notice">Signed in as <strong><?php echo htmlspecialchars((string) $_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></strong>. Click "View" for full account details.</p>
                </div>
                <div style="margin-top:auto"><a href="#" class="card-btn" id="view-account">View</a></div>
            </div>
        </div>
    </section>

    <!-- Modal for viewing details -->
    <div id="detail-modal" class="modal" role="dialog" aria-hidden="true">
        <div class="modal-content" role="document">
            <button class="close-btn" id="modal-close">&times;</button>
            <div id="modal-body"></div>
        </div>
    </div>

    <script>
        const roleCounts = <?php echo json_encode($roleCounts ?: new stdClass()); ?>;
        const account = {
            username: <?php echo json_encode((string)($_SESSION['username'] ?? '')); ?>,
            idnumber: <?php echo json_encode((string)($_SESSION['idnumber'] ?? '')); ?>,
            firstname: <?php echo json_encode((string)($_SESSION['firstname'] ?? '')); ?>,
            surname: <?php echo json_encode((string)($_SESSION['surname'] ?? '')); ?>,
            email: <?php echo json_encode((string)($_SESSION['email'] ?? '')); ?>,
            contact: <?php echo json_encode((string)($_SESSION['contact'] ?? '')); ?>,
            phyAddress: <?php echo json_encode((string)($_SESSION['phyAddress'] ?? '')); ?>
        };

        const modal = document.getElementById('detail-modal');
        const modalBody = document.getElementById('modal-body');
        const closeBtn = document.getElementById('modal-close');

        function escapeHtml(str){
            return String(str).replace(/[&<>\"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]; });
        }

        function openModal(html) {
            modalBody.innerHTML = html;
            modal.style.display = 'block';
            modal.setAttribute('aria-hidden', 'false');
        }
        function closeModal() {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            modalBody.innerHTML = '';
        }

        document.getElementById('view-roles').addEventListener('click', function(e){
            e.preventDefault();
            if (!roleCounts || Object.keys(roleCounts).length === 0) {
                openModal('<div class="modal-header"><h3>Users by role</h3></div><div class="modal-body-content"><p>No role data available.</p></div>');
                return;
            }
            let html = '<div class="modal-header"><h3>Users by role</h3></div>';
            html += '<div class="modal-body-content">';
            html += '<table class="modal-table"><thead><tr><th>Role</th><th>Count</th></tr></thead><tbody>';
            let total = 0;
            for (const [r, c] of Object.entries(roleCounts)) {
                html += '<tr><td>' + escapeHtml(r) + '</td><td>' + c + '</td></tr>';
                total += Number(c) || 0;
            }
            html += '<tr><td><strong>Total</strong></td><td><strong>' + total + '</strong></td></tr>';
            html += '</tbody></table>';
            html += '</div>';
            html += '<div class="modal-actions"><a href="#" class="btn secondary" id="modal-close-btn">Close</a></div>';
            openModal(html);
            // attach close handler for inner button
            setTimeout(()=>{ const btn = document.getElementById('modal-close-btn'); if(btn) btn.addEventListener('click', function(ev){ ev.preventDefault(); closeModal(); }); }, 10);
        });

        document.getElementById('view-account').addEventListener('click', function(e){
            e.preventDefault();
            // build editable form that POSTS to editprofile.php
            let html = '<div class="modal-header"><h3>Edit Account</h3></div>';
            html += '<div class="modal-body-content">';
            html += '<form method="post" action="" id="modal-edit-form">';
            html += '<input type="hidden" name="update_account" value="1">';
            html += '<div style="display:grid; gap:10px;">';
            html += '<label style="font-weight:700;">First name</label><input name="firstname" type="text" value="' + (escapeHtml(account.firstname || '')) + '" required style="padding:8px; border:1px solid var(--line); border-radius:4px;">';
            html += '<label style="font-weight:700;">Surname</label><input name="surname" type="text" value="' + (escapeHtml(account.surname || '')) + '" required style="padding:8px; border:1px solid var(--line); border-radius:4px;">';
            html += '<label style="font-weight:700;">Email</label><input name="email" type="email" value="' + (escapeHtml(account.email || '')) + '" required style="padding:8px; border:1px solid var(--line); border-radius:4px;">';
            html += '<label style="font-weight:700;">Contact</label><input name="contact" type="text" value="' + (escapeHtml(account.contact || '')) + '" required style="padding:8px; border:1px solid var(--line); border-radius:4px;">';
            html += '<label style="font-weight:700;">Physical address</label><input name="phyAddress" type="text" value="' + (escapeHtml(account.phyAddress || '')) + '" style="padding:8px; border:1px solid var(--line); border-radius:4px;">';
            html += '</div>';
            html += '</form>';
            html += '</div>';
            html += '<div class="modal-actions"><button class="btn primary" id="modal-save">Save changes</button><a href="#" class="btn secondary" id="modal-cancel">Cancel</a></div>';
            openModal(html);
            // wire buttons
            setTimeout(()=>{
                const save = document.getElementById('modal-save');
                const cancel = document.getElementById('modal-cancel');
                if (save) save.addEventListener('click', function(ev){ ev.preventDefault(); document.getElementById('modal-edit-form').submit(); });
                if (cancel) cancel.addEventListener('click', function(ev){ ev.preventDefault(); closeModal(); });
            }, 10);
        });

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeModal(); });
    </script>
    
    <section class="card-grid" aria-label="Administrator actions">
        <a class="card" href="displayAll Admin.php">
            <div class="card-banner gold"></div>
            <div class="card-content">
                <span class="card-tag">Users</span>
                <h3>Validate users</h3>
                <p class="notice"> Validate user accounts for system access</p>
                <div style="margin-top:auto"><span class="card-btn">Manage users</span></div>
            </div>
        </a>

        <a class="card" href="Display_All_Users_Admin.php">
            <div class="card-banner blue"></div>
            <div class="card-content">
                <span class="card-tag">Users</span>
                <h3>View and manage users </h3>
                <p class="notice">Browse user accounts, edit roles, and manage access.</p>
                <div style="margin-top:auto"><span class="card-btn">View users</span></div>
            </div>
        </a>

        <a class="card" href="managereports_Admin.php">
            <div class="card-banner blue"></div>
            <div class="card-content">
                <span class="card-tag">Faults</span>
                <h3>Manage fault reports</h3>
                <p class="notice">Assign, review, and resolve reported faults.</p>
                <div style="margin-top:auto"><span class="card-btn">Manage faults</span></div>
            </div>
        </a>

        <a class="card" href="reportfault.php">
            <div class="card-banner green"></div>
            <div class="card-content">
                <span class="card-tag">Reports</span>
                <h3>Report a fault</h3>
                <p class="notice">Submit a new fault on behalf of a resident.</p>
                <div style="margin-top:auto"><span class="card-btn">Report fault</span></div>
            </div>
        </a>
    </section>
</main>
</body>
</html>
