<?php

include "dbconnection.php";

$message = "";
$messageType = "";


/* =========================================================
   UPDATE DAM LEVEL
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $dam_id = isset($_POST["dam_id"])
        ? (int) $_POST["dam_id"]
        : 0;

    $level = isset($_POST["level"])
        ? (float) $_POST["level"]
        : 0;


    /* =====================================================
       VALIDATION
       ===================================================== */

    if ($dam_id <= 0) {

        $message = "Please select a dam.";
        $messageType = "error";

    } elseif ($level < 0 || $level > 100) {

        $message = "Dam level must be between 0% and 100%.";
        $messageType = "error";

    } else {


        /* =================================================
           UPDATE DATABASE
           ================================================= */

        $sql = "
            UPDATE dam_levels
            SET level = ?
            WHERE id = ?
        ";

        $stmt = $conn->prepare($sql);


        if ($stmt === false) {

            $message = "Database error: " . $conn->error;
            $messageType = "error";

        } else {


            /* =============================================
               Bind values

               d = decimal
               i = integer
               ============================================= */

            $stmt->bind_param(
                "di",
                $level,
                $dam_id
            );


            /* =============================================
               Execute
               ============================================= */

            if ($stmt->execute()) {

                if ($stmt->affected_rows > 0) {

                    $message =
                        "Dam level updated successfully to "
                        . $level
                        . "%.";

                    $messageType = "success";

                } else {

                    $message =
                        "The dam level was already "
                        . $level
                        . "%.";

                    $messageType = "success";
                }

            } else {

                $message =
                    "Error updating dam level: "
                    . $stmt->error;

                $messageType = "error";
            }


            $stmt->close();
        }
    }
}


/* =========================================================
   GET ALL DAMS
   ========================================================= */

$dams = [];

$sqlDams = "
    SELECT
        id,
        dam_name,
        level,
        updated_at
    FROM dam_levels
    ORDER BY dam_name ASC
";

$resultDams = mysqli_query(
    $conn,
    $sqlDams
);


if ($resultDams) {

    while ($row = mysqli_fetch_assoc($resultDams)) {

        $dams[] = $row;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Update Dam Levels | Makana Municipality
    </title>


    <style>

        :root {

            --wine: #4b1f2a;

            --wine-dark: #3a1720;

            --gold: #b08d57;

            --paper: #f8f4ee;

            --panel: #ffffff;

            --line: #e6ddd2;

            --muted: #706d73;

            --green: #2f7463;

            --red: #b33;

        }


        * {

            box-sizing: border-box;

        }


        body {

            margin: 0;

            background: var(--paper);

            color: #242329;

            font-family:
                Georgia,
                "Times New Roman",
                serif;

        }


        /* =====================================================
           HEADER
           ===================================================== */

        .topbar {

            background: var(--wine);

            color: white;

            padding:
                16px
                clamp(20px, 5vw, 72px);

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            min-height: 75px;

        }


        .brand {

            display: flex;

            align-items: center;

            gap: 14px;

        }


        .brand img {

            width: 52px;

            height: 52px;

            object-fit: contain;

            background: white;

            border-radius: 50%;

        }


        .brand strong {

            display: block;

            font-size: 1.05rem;

            letter-spacing: .04em;

        }


        .brand span {

            color: #e9d9c1;

            font:
                .78rem Arial,
                sans-serif;

        }


        .back-button {

            color: white;

            border:
                1px solid
                #d5b987;

            padding:
                9px
                14px;

            text-decoration: none;

            font:
                700 .8rem Arial,
                sans-serif;

        }


        .back-button:hover {

            background: white;

            color: var(--wine);

        }


        /* =====================================================
           MAIN
           ===================================================== */

        main {

            width:
                min(
                    1000px,
                    calc(100% - 40px)
                );

            margin:
                45px
                auto
                70px;

        }


        .eyebrow {

            color: var(--gold);

            font:
                700 .74rem Arial,
                sans-serif;

            letter-spacing: .16em;

            text-transform: uppercase;

        }


        h1 {

            margin:
                8px
                0
                8px;

            font-size:
                clamp(
                    2rem,
                    4vw,
                    3rem
                );

            font-weight: 400;

        }


        .intro {

            color: var(--muted);

            margin:
                0
                0
                30px;

            font:
                1rem/1.6 Arial,
                sans-serif;

        }


        /* =====================================================
           MESSAGE
           ===================================================== */

        .alert {

            padding: 15px;

            margin-bottom: 25px;

            border-radius: 6px;

            font:
                .9rem Arial,
                sans-serif;

        }


        .alert-success {

            background: #d4edda;

            color: #155724;

            border:
                1px solid
                #c3e6cb;

        }


        .alert-error {

            background: #f8d7da;

            color: #721c24;

            border:
                1px solid
                #f5c6cb;

        }


        /* =====================================================
           UPDATE FORM
           ===================================================== */

        .update-card {

            background: var(--panel);

            border:
                1px solid
                var(--line);

            border-top:
                4px solid
                var(--gold);

            padding: 30px;

            border-radius: 8px;

            box-shadow:
                0 4px 10px
                rgba(0,0,0,.04);

            margin-bottom: 30px;

        }


        .update-card h2 {

            margin-top: 0;

            font-weight: 400;

        }


        .manager-form {

            display: grid;

            gap: 12px;

            margin-top: 20px;

        }


        .manager-form label {

            font:
                700 .8rem Arial,
                sans-serif;

            color: var(--muted);

            margin-top: 5px;

        }


        .manager-form select,

        .manager-form input {

            width: 100%;

            padding: 13px;

            border:
                1px solid
                var(--line);

            border-radius: 4px;

            background: white;

            font:
                .9rem Arial,
                sans-serif;

        }


        .manager-form select:focus,

        .manager-form input:focus {

            outline: none;

            border-color: var(--gold);

        }


        .manager-form button {

            background: var(--wine);

            color: white;

            border: none;

            padding:
                13px
                20px;

            font:
                700 .85rem Arial,
                sans-serif;

            cursor: pointer;

            border-radius: 4px;

            margin-top: 10px;

        }


        .manager-form button:hover {

            background: var(--wine-dark);

        }


        /* =====================================================
           DAM LIST
           ===================================================== */

        .dam-list {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(
                        220px,
                        1fr
                    )
                );

            gap: 18px;

        }


        .dam-card {

            background: white;

            border:
                1px solid
                var(--line);

            padding: 20px;

            border-radius: 8px;

        }


        .dam-card h3 {

            margin:
                0
                0
                10px;

            font-weight: 400;

        }


        .dam-percentage {

            font-size: 2.3rem;

            color: var(--wine);

            margin-bottom: 12px;

        }


        .progress-container {

            width: 100%;

            height: 15px;

            background: #e9e9e9;

            border-radius: 20px;

            overflow: hidden;

        }


        .progress {

            height: 100%;

            background: var(--gold);

            border-radius: 20px;

        }


        .dam-status {

            display: inline-block;

            margin-top: 12px;

            padding:
                5px
                10px;

            border-radius: 20px;

            background: #f1ebe2;

            color: var(--wine);

            font:
                700 .72rem Arial,
                sans-serif;

        }


        .updated {

            color: var(--muted);

            margin-top: 10px;

            font:
                .75rem Arial,
                sans-serif;

        }


        /* =====================================================
           MOBILE
           ===================================================== */

        @media (max-width: 600px) {

            .topbar {

                flex-direction: column;

                align-items: flex-start;

            }


            main {

                width:
                    calc(100% - 28px);

                margin-top: 30px;

            }


            .update-card {

                padding: 20px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
     ========================================================= -->

<header class="topbar">

    <div class="brand">

        <img
            src="Makana-Municipality logo.png"
            alt="Makana Municipality logo"
        >

        <div>

            <strong>
                Makana Municipality
            </strong>

            <span>
                Dam Management
            </span>

        </div>

    </div>


    <a
        href="manager_dashboard.php"
        class="back-button"
    >
        ← Back to Dashboard
    </a>

</header>


<!-- =========================================================
     MAIN
     ========================================================= -->

<main>


    <div class="eyebrow">
        Water Management
    </div>


    <h1>
        Update Dam Levels
    </h1>


    <p class="intro">

        Update the current water level of
        municipal dams. These values will
        automatically appear on the public
        homepage.

    </p>


    <!-- =====================================================
         MESSAGE
         ===================================================== -->

    <?php if ($message !== ""): ?>

        <div
            class="alert
            <?php
            echo $messageType === "error"
                ? "alert-error"
                : "alert-success";
            ?>"
        >

            <?php

            echo htmlspecialchars(
                $message,
                ENT_QUOTES,
                "UTF-8"
            );

            ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         UPDATE FORM
         ===================================================== -->

    <section class="update-card">

        <h2>
            Change Dam Level
        </h2>


        <form
            class="manager-form"
            method="POST"
            action="update_dam.php"
        >


            <label for="dam_id">
                Select Dam
            </label>


            <select
                name="dam_id"
                id="dam_id"
                required
            >

                <option value="">
                    -- Select a dam --
                </option>


                <?php foreach ($dams as $dam): ?>

                    <option
                        value="<?php
                        echo (int) $dam['id'];
                        ?>"
                    >

                        <?php

                        echo htmlspecialchars(
                            $dam['dam_name'],
                            ENT_QUOTES,
                            "UTF-8"
                        );

                        ?>

                        -
                        <?php

                        echo htmlspecialchars(
                            $dam['level'],
                            ENT_QUOTES,
                            "UTF-8"
                        );

                        ?>%

                    </option>

                <?php endforeach; ?>

            </select>


            <label for="level">
                New Dam Level (%)
            </label>


            <input
                type="number"
                name="level"
                id="level"
                min="0"
                max="100"
                step="0.01"
                placeholder="Enter percentage e.g. 85"
                required
            >


            <button
                type="submit"
            >
                Update Dam Level
            </button>


        </form>

    </section>


    <!-- =====================================================
         CURRENT DAM LEVELS
         ===================================================== -->

    <h2>
        Current Dam Levels
    </h2>


    <section class="dam-list">


        <?php if (count($dams) > 0): ?>


            <?php foreach ($dams as $dam): ?>

                <?php

                $level =
                    (float) $dam['level'];


                /* Determine status */

                if ($level >= 70) {

                    $status = "Stable";

                } elseif ($level >= 40) {

                    $status = "Moderate";

                } else {

                    $status = "Low";

                }

                ?>


                <article class="dam-card">


                    <h3>

                        <?php

                        echo htmlspecialchars(
                            $dam['dam_name'],
                            ENT_QUOTES,
                            "UTF-8"
                        );

                        ?>

                    </h3>


                    <div class="dam-percentage">

                        <?php

                        echo $level;

                        ?>%

                    </div>


                    <div class="progress-container">

                        <div
                            class="progress"
                            style="width:
                                <?php
                                echo $level;
                                ?>%;
                            "
                        ></div>

                    </div>


                    <span class="dam-status">

                        <?php echo $status; ?>

                    </span>


                    <?php if (!empty($dam['updated_at'])): ?>

                        <div class="updated">

                            Last updated:

                            <?php

                            echo date(
                                "d M Y, H:i",
                                strtotime(
                                    $dam['updated_at']
                                )
                            );

                            ?>

                        </div>

                    <?php endif; ?>


                </article>


            <?php endforeach; ?>


        <?php else: ?>


            <p>
                No dams have been added to the
                database yet.
            </p>


        <?php endif; ?>


    </section>


</main>


</body>

</html>