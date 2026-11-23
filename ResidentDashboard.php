<?php


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Dashboard | Makana Municipality</title>
    <style>
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
        .topbar { background: var(--wine); color: #fff; padding: 16px clamp(20px, 5vw, 72px); display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand img { width: 52px; height: 52px; object-fit: contain; background: #fff; border-radius: 50%; }
        .brand strong { display: block; font-size: 1.05rem; letter-spacing: .04em; }
        .brand span { color: #e9d9c1; font: .78rem Arial, sans-serif; }
        .logout { color: #fff; border: 1px solid #d5b987; padding: 9px 14px; text-decoration: none; font: 700 .8rem Arial, sans-serif; }
        main { width: min(1180px, calc(100% - 40px)); margin: 42px auto 70px; }
        .eyebrow { color: var(--gold); font: 700 .74rem Arial, sans-serif; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 8px 0 8px; font-size: clamp(2rem, 4vw, 3.5rem); font-weight: 400; }
        .intro { color: var(--muted); margin: 0 0 30px; font: 1rem/1.6 Arial, sans-serif; }

    </style>
</head>
<body>
<header class="topbar">
    <div class="brand">
        <img src="Makana-Municipality logo.png" alt="Makana Municipality logo">
        <div><strong>Makana Municipality</strong><span>Resident profile</span></div>
    </div>
    <a class="logout" href="logout.php">Sign out</a>
</header>
<main>

    <button><a href="reportfault.html">Report Fault</a></button>

    <div class="eyebrow">Control centre</div>
    <h1>Welcome, <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="intro">Review the current platform overview and resident account details.</p>


    <section>
        <article class="panel">
            <h2>Account details</h2>
            <dl class="profile">
                <div><dt>Username</dt><dd><?php echo htmlspecialchars((string) $_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
                <div><dt>ID number</dt><dd><?php echo htmlspecialchars((string) $_SESSION['idnumber'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
                <div><dt>Email</dt><dd><?php echo htmlspecialchars((string) $_SESSION['email'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
                <div><dt>Contact</dt><dd><?php echo htmlspecialchars((string) $_SESSION['contact'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
            </dl>
        </article>
    </section>
</main>
</body>
</html>
