<?php

require_once 'dbconnection.php';

$action    = $_GET['action']     ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate   = $_GET['end_date']   ?? date('Y-m-d');

/* -------------------------------------------------------------
 * Reference lists
 * ------------------------------------------------------------- */

$ALL_WARDS = [
    'Ward 1 - Central',
    'Ward 2 - Riverside',
    'Ward 3 - Northgate',
    'Ward 4 - Eastwood',
    'Ward 5 - Southpark',
    'Ward 6 - Westend',
    'Ward 7 - Highbridge',
    'Ward 8 - Lakeview',
    'Ward 9 - Hillcrest',
    'Ward 10 - Meadowlands',
    'Ward 11 - Oakdale',
    'Ward 12 - Pinehurst',
    'Ward 13 - Sunset Ridge',
    'Ward 14 - Harborview'
];

$ALL_CATEGORIES = [
    'Water & Sanitation',
    'Electricity / Power Outage',
    'Roads & Potholes',
    'Refuse / Waste Management',
    'Street Lighting',
    'Other'
];

/*
 * Fault ticket statuses
 * These are the ONLY three statuses used by the reports.
 */
$ALL_STATUSES = [
    'Open',
    'Pending',
    'Resolved'
];


/* -------------------------------------------------------------
 * Helper function:
 * Create a continuous list of YYYY-MM months between two dates
 * ------------------------------------------------------------- */

function monthRange($start, $end)
{
    $months = [];

    $startDt = new DateTime(date('Y-m-01', strtotime($start)));
    $endDt   = new DateTime(date('Y-m-01', strtotime($end)));

    // Include the end month
    $endDt->modify('+1 month');

    $period = new DatePeriod(
        $startDt,
        new DateInterval('P1M'),
        $endDt
    );

    foreach ($period as $dt) {
        $months[] = $dt->format('Y-m');
    }

    return $months;
}


/* =============================================================
 * API MODE
 * If an action is set, return JSON and stop the page here.
 * ============================================================= */

if ($action !== '') {

    header('Content-Type: application/json; charset=utf-8');

    $data = [];

    switch ($action) {

        /* =====================================================
         * 1. WARD STATISTICS
         * All 14 wards are returned, even if they have 0 faults.
         * ===================================================== */

        case 'ward_stats':

            // Get wards from database
            $wardRows = [];

            $wardQuery = "
                SELECT ward_id, ward_name
                FROM ward
                ORDER BY ward_id ASC
            ";

            if ($res = $conn->query($wardQuery)) {

                while ($r = $res->fetch_assoc()) {

                    $wardRows[(int)$r['ward_id']] = $r['ward_name'];
                }

                $res->free();
            }

            // If database contains no wards,
            // use the fixed list above.
            if (empty($wardRows)) {

                foreach ($ALL_WARDS as $i => $name) {

                    $wardRows[$i + 1] = $name;
                }
            }


            // Count faults for each ward
            $counts = [];

            $sql = "
                SELECT
                    f.ward_id,
                    COUNT(f.ticket_id) AS total_faults
                FROM faultticket f
                WHERE f.created_at >= ?
                  AND f.created_at < DATE_ADD(?, INTERVAL 1 DAY)
                GROUP BY f.ward_id
            ";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ss",
                $startDate,
                $endDate
            );

            $stmt->execute();

            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {

                $counts[(int)$row['ward_id']] =
                    (int)$row['total_faults'];
            }

            $stmt->close();


            // Return all wards
            foreach ($wardRows as $wardId => $wardName) {

                $data['labels'][] = $wardName;

                $data['values'][] =
                    $counts[$wardId] ?? 0;
            }

            break;


        /* =====================================================
         * 2. STATUS STATISTICS
         *
         * Statuses:
         * Open
         * Pending
         * Resolved
         * ===================================================== */

        case 'status_stats':

            $sql = "
                SELECT
                    CASE
                        WHEN LOWER(TRIM(status)) = 'open'
                            THEN 'Open'

                        WHEN LOWER(TRIM(status)) = 'pending'
                            THEN 'Pending'

                        WHEN LOWER(TRIM(status)) = 'resolved'
                            THEN 'Resolved'

                        ELSE NULL
                    END AS status_label,

                    COUNT(ticket_id) AS cnt

                FROM faultticket

                WHERE created_at >= ?
                  AND created_at < DATE_ADD(?, INTERVAL 1 DAY)

                GROUP BY status_label
            ";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ss",
                $startDate,
                $endDate
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $found = [];

            while ($row = $result->fetch_assoc()) {

                if ($row['status_label'] !== null) {

                    $found[$row['status_label']] =
                        (int)$row['cnt'];
                }
            }

            $stmt->close();


            /*
             * Always return statuses in this exact order:
             *
             * Open
             * Pending
             * Resolved
             */

            foreach ($ALL_STATUSES as $label) {

                $data['labels'][] = $label;

                $data['values'][] =
                    $found[$label] ?? 0;
            }

            break;


        /* =====================================================
         * 3. HISTORY STATISTICS
         *
         * Shows every month in selected period
         * and all six fault categories.
         * ===================================================== */

        case 'history_stats':

            $months = monthRange(
                $startDate,
                $endDate
            );


            // Create empty data structure
            $categories = [];

            foreach ($ALL_CATEGORIES as $cat) {

                $categories[$cat] =
                    array_fill(
                        0,
                        count($months),
                        0
                    );
            }


            $sql = "
                SELECT
                    DATE_FORMAT(
                        f.created_at,
                        '%Y-%m'
                    ) AS month,

                    c.category_name,

                    COUNT(f.ticket_id) AS cnt

                FROM faultticket f

                JOIN faultcategory c
                    ON f.category_id = c.category_id

                WHERE f.created_at >= ?
                  AND f.created_at < DATE_ADD(?, INTERVAL 1 DAY)

                GROUP BY
                    month,
                    c.category_name

                ORDER BY month ASC
            ";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ss",
                $startDate,
                $endDate
            );

            $stmt->execute();

            $result = $stmt->get_result();


            $monthIndex = array_flip($months);


            while ($row = $result->fetch_assoc()) {

                $month = $row['month'];
                $cat   = $row['category_name'];

                if (
                    isset($monthIndex[$month]) &&
                    isset($categories[$cat])
                ) {

                    $categories[$cat][$monthIndex[$month]] =
                        (int)$row['cnt'];
                }
            }

            $stmt->close();


            $data['months']     = $months;
            $data['categories'] = $categories;

            break;


        /* =====================================================
         * 4. HOTSPOT STATISTICS
         *
         * Top 10 addresses with the most reported faults.
         * ===================================================== */

        case 'hotspot_stats':

            $sql = "
                SELECT

                    COALESCE(
                        NULLIF(
                            TRIM(incident_address),
                            ''
                        ),
                        'Unknown'
                    ) AS address,

                    COUNT(ticket_id) AS cnt

                FROM faultticket

                WHERE created_at >= ?
                  AND created_at < DATE_ADD(?, INTERVAL 1 DAY)

                GROUP BY address

                ORDER BY cnt DESC

                LIMIT 10
            ";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ss",
                $startDate,
                $endDate
            );

            $stmt->execute();

            $result = $stmt->get_result();


            while ($row = $result->fetch_assoc()) {

                $data['labels'][] =
                    $row['address'];

                $data['values'][] =
                    (int)$row['cnt'];
            }

            $stmt->close();

            break;


        /* =====================================================
         * 5. TURNAROUND STATISTICS
         *
         * Top 10 longest resolved tickets.
         *
         * A ticket is considered resolved when its status
         * history contains:
         *
         * status_type = 'Resolved'
         * ===================================================== */

        case 'turnaround_stats':

            $sql = "
                SELECT
                    f.ticket_id,
                    f.title,

                    DATEDIFF(
                        ts.changed_at,
                        f.created_at
                    ) AS days_taken

                FROM faultticket f

                JOIN ticketstatus ts
                    ON f.ticket_id = ts.ticket_id

                WHERE ts.status_type = 'Resolved'

                  AND f.created_at >= ?
                  AND f.created_at < DATE_ADD(?, INTERVAL 1 DAY)

                ORDER BY days_taken DESC

                LIMIT 10
            ";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ss",
                $startDate,
                $endDate
            );

            $stmt->execute();

            $result = $stmt->get_result();


            while ($row = $result->fetch_assoc()) {

                $data['labels'][] =
                    "Ticket #" . $row['ticket_id'];

                $data['values'][] =
                    (int)$row['days_taken'];
            }

            $stmt->close();

            break;


        /* =====================================================
         * UNKNOWN ACTION
         * ===================================================== */

        default:

            $data['error'] =
                'Unknown action: ' . $action;

            break;
    }


    // Close database connection
    $conn->close();


    // Return JSON
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =============================================================
 * HTML MODE
 *
 * No action was supplied, so display the dashboard.
 * ============================================================= */

$conn->close();

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
        System Reports - MyKhandaTech (Agnes) • 14 Wards
    </title>


    <!-- Tailwind CSS -->

    <script
        src="https://cdn.tailwindcss.com">
    </script>


    <!-- Chart.js -->

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js">
    </script>


    <style>

        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }

    </style>

</head>


<body class="bg-gray-100 p-6">


<div class="max-w-7xl mx-auto">


    <!-- =====================================================
         PAGE TITLE
         ===================================================== -->

    <h1 class="text-3xl font-bold text-gray-800 mb-6">

        📊 System Reports Dashboard
        (Agnes) — 14 Wards

    </h1>


    <!-- =====================================================
         DATE FILTER
         ===================================================== -->

    <div
        class="bg-white p-4 rounded-lg shadow-md mb-6
               flex flex-wrap items-center gap-4"
    >

        <label
            class="font-semibold text-gray-700"
        >
            Date Range:
        </label>


        <input
            type="date"
            id="startDate"
            class="border rounded p-2"
        >


        <span class="text-gray-500">
            to
        </span>


        <input
            type="date"
            id="endDate"
            class="border rounded p-2"
        >


        <button
            onclick="loadAllCharts()"
            class="bg-blue-600 text-white
                   px-4 py-2 rounded
                   hover:bg-blue-700 transition"
        >

            Apply Filter

        </button>


        <span
            class="text-xs text-gray-500 ml-auto"
        >

            Incident categories:
            Water &amp; Sanitation,
            Electricity,
            Roads,
            Refuse,
            Street Lighting,
            Other

        </span>

    </div>


    <!-- =====================================================
         REPORT GRID
         ===================================================== -->

    <div
        class="grid grid-cols-1 md:grid-cols-2 gap-6"
    >


        <!-- =================================================
             WARD REPORT
             ================================================= -->

        <div
            class="bg-white p-4 rounded-lg shadow-md
                   md:col-span-2"
        >

            <h2
                class="text-lg font-semibold mb-2 text-gray-700"
            >

                📊 Histogram:
                Faults per Ward (14 Wards)

            </h2>


            <div class="chart-container">

                <canvas id="wardChart"></canvas>

            </div>

        </div>


        <!-- =================================================
             STATUS REPORT
             ================================================= -->

        <div
            class="bg-white p-4 rounded-lg shadow-md"
        >

            <h2
                class="text-lg font-semibold mb-2 text-gray-700"
            >

                🍩 Doughnut:
                Fault Ticket Status

            </h2>


            <div class="chart-container">

                <canvas id="statusChart"></canvas>

            </div>

        </div>


        <!-- =================================================
             TURNAROUND REPORT
             ================================================= -->

        <div
            class="bg-white p-4 rounded-lg shadow-md"
        >

            <h2
                class="text-lg font-semibold mb-2 text-gray-700"
            >

                📊 Top 10 Longest
                Turnaround Times

            </h2>


            <div class="chart-container">

                <canvas id="turnaroundChart"></canvas>

            </div>

        </div>


        <!-- =================================================
             HISTORY REPORT
             ================================================= -->

        <div
            class="bg-white p-4 rounded-lg shadow-md
                   md:col-span-2"
        >

            <h2
                class="text-lg font-semibold mb-2 text-gray-700"
            >

                📈 Line Chart:
                History of Complaint Categories (Monthly)

            </h2>


            <div class="chart-container">

                <canvas id="historyChart"></canvas>

            </div>

        </div>


        <!-- =================================================
             HOTSPOT REPORT
             ================================================= -->

        <div
            class="bg-white p-4 rounded-lg shadow-md
                   md:col-span-2"
        >

            <h2
                class="text-lg font-semibold mb-2 text-gray-700"
            >

                📊 Fault Hot Spot Visualisation
                (Top Addresses)

            </h2>


            <div class="chart-container">

                <canvas id="hotspotChart"></canvas>

            </div>

        </div>


    </div>

</div>



<script>

/* =============================================================
 * INCIDENT CATEGORIES
 * Must match the PHP categories.
 * ============================================================= */

const INCIDENT_CATEGORIES = [

    "Water & Sanitation",

    "Electricity / Power Outage",

    "Roads & Potholes",

    "Refuse / Waste Management",

    "Street Lighting",

    "Other"

];


/* =============================================================
 * CHART STORAGE
 * ============================================================= */

let charts = {};


/* =============================================================
 * FETCH DATA
 *
 * Gets JSON from this same PHP file.
 * ============================================================= */

async function fetchData(action)
{

    const startDate =
        document.getElementById('startDate').value;

    const endDate =
        document.getElementById('endDate').value;


    const url =
        `?action=${encodeURIComponent(action)}` +
        `&start_date=${encodeURIComponent(startDate)}` +
        `&end_date=${encodeURIComponent(endDate)}`;


    const response =
        await fetch(
            url,
            {
                headers: {
                    'Accept': 'application/json'
                }
            }
        );


    if (!response.ok)
    {

        throw new Error(
            `HTTP ${response.status} for ${action}`
        );

    }


    return await response.json();

}


/* =============================================================
 * DESTROY EXISTING CHART
 * ============================================================= */

function destroyChart(id)
{

    if (charts[id])
    {

        charts[id].destroy();

        delete charts[id];

    }

}


/* =============================================================
 * LOAD ALL CHARTS
 * ============================================================= */

async function loadAllCharts()
{

    try
    {

        /* =====================================================
         * 1. WARD HISTOGRAM
         * ===================================================== */

        const wardData =
            await fetchData('ward_stats');


        destroyChart('wardChart');


        charts['wardChart'] =
            new Chart(
                document.getElementById('wardChart'),
                {

                    type: 'bar',

                    data: {

                        labels:
                            wardData.labels || [],

                        datasets: [

                            {

                                label:
                                    'Total Faults',

                                data:
                                    wardData.values || [],

                                backgroundColor:
                                    'rgba(59, 130, 246, 0.8)',

                                borderColor:
                                    'rgba(59, 130, 246, 1)',

                                borderWidth: 1,

                                borderRadius: 4

                            }

                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        plugins: {

                            tooltip: {

                                callbacks: {

                                    label:
                                        (ctx) =>
                                            `${ctx.raw} faults`

                                }

                            }

                        },

                        scales: {

                            y: {

                                beginAtZero: true,

                                title: {

                                    display: true,

                                    text:
                                        'Number of Faults'

                                }

                            },

                            x: {

                                ticks: {

                                    autoSkip: false,

                                    maxRotation: 45,

                                    minRotation: 45

                                }

                            }

                        }

                    }

                }
            );


        /* =====================================================
         * 2. STATUS DOUGHNUT
         *
         * OPEN     = RED
         * PENDING  = ORANGE
         * RESOLVED = GREEN
         * ===================================================== */

        const statusData =
            await fetchData('status_stats');


        destroyChart('statusChart');


        const statusColors =
            statusData.labels.map(
                function(label)
                {

                    if (
                        label.toLowerCase()
                        === 'open'
                    )
                    {

                        return '#EF4444';

                    }


                    if (
                        label.toLowerCase()
                        === 'pending'
                    )
                    {

                        return '#F59E0B';

                    }


                    if (
                        label.toLowerCase()
                        === 'resolved'
                    )
                    {

                        return '#10B981';

                    }


                    return '#3B82F6';

                }
            );


        charts['statusChart'] =
            new Chart(
                document.getElementById('statusChart'),
                {

                    type: 'doughnut',

                    data: {

                        labels:
                            statusData.labels || [],

                        datasets: [

                            {

                                data:
                                    statusData.values || [],

                                backgroundColor:
                                    statusColors,

                                borderWidth: 1

                            }

                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        plugins: {

                            legend: {

                                position: 'bottom'

                            }

                        }

                    }

                }
            );


        /* =====================================================
         * 3. TURNAROUND CHART
         * ===================================================== */

        const turnaroundData =
            await fetchData('turnaround_stats');


        destroyChart('turnaroundChart');


        charts['turnaroundChart'] =
            new Chart(
                document.getElementById(
                    'turnaroundChart'
                ),
                {

                    type: 'bar',

                    data: {

                        labels:
                            turnaroundData.labels || [],

                        datasets: [

                            {

                                label:
                                    'Days Taken to Resolve',

                                data:
                                    turnaroundData.values || [],

                                backgroundColor:
                                    'rgba(139, 92, 246, 0.8)',

                                borderColor:
                                    'rgba(139, 92, 246, 1)',

                                borderWidth: 1,

                                borderRadius: 4

                            }

                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        indexAxis: 'y',

                        scales: {

                            x: {

                                beginAtZero: true,

                                title: {

                                    display: true,

                                    text: 'Days'

                                }

                            }

                        }

                    }

                }
            );


        /* =====================================================
         * 4. HISTORY LINE CHART
         * ===================================================== */

        const historyData =
            await fetchData('history_stats');


        const historyLabels =
            historyData.months || [];


        const historyDatasets = [];


        const lineColors = [

            '#3B82F6',

            '#EF4444',

            '#10B981',

            '#F59E0B',

            '#8B5CF6',

            '#EC4899'

        ];


        if (historyData.categories)
        {

            let colorIndex = 0;


            INCIDENT_CATEGORIES.forEach(
                function(cat)
                {

                    const counts =
                        historyData.categories[cat]
                        ||
                        new Array(
                            historyLabels.length
                        ).fill(0);


                    historyDatasets.push({

                        label: cat,

                        data: counts,

                        borderColor:
                            lineColors[
                                colorIndex %
                                lineColors.length
                            ],

                        backgroundColor:
                            'transparent',

                        borderWidth: 2,

                        tension: 0.3,

                        fill: false,

                        pointRadius: 3

                    });


                    colorIndex++;

                }
            );

        }


        destroyChart('historyChart');


        charts['historyChart'] =
            new Chart(
                document.getElementById(
                    'historyChart'
                ),
                {

                    type: 'line',

                    data: {

                        labels:
                            historyLabels,

                        datasets:
                            historyDatasets

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        plugins: {

                            legend: {

                                position: 'bottom',

                                labels: {

                                    boxWidth: 12

                                }

                            }

                        },

                        scales: {

                            y: {

                                beginAtZero: true,

                                title: {

                                    display: true,

                                    text:
                                        'Number of Complaints'

                                }

                            }

                        }

                    }

                }
            );


        /* =====================================================
         * 5. HOTSPOT CHART
         * ===================================================== */

        const hotspotData =
            await fetchData('hotspot_stats');


        destroyChart('hotspotChart');


        charts['hotspotChart'] =
            new Chart(
                document.getElementById(
                    'hotspotChart'
                ),
                {

                    type: 'bar',

                    data: {

                        labels:
                            hotspotData.labels || [],

                        datasets: [

                            {

                                label:
                                    'Number of Faults',

                                data:
                                    hotspotData.values || [],

                                backgroundColor:
                                    'rgba(249, 115, 22, 0.8)',

                                borderColor:
                                    'rgba(249, 115, 22, 1)',

                                borderWidth: 1,

                                borderRadius: 4

                            }

                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        indexAxis: 'y',

                        scales: {

                            x: {

                                beginAtZero: true,

                                title: {

                                    display: true,

                                    text:
                                        'Fault Count'

                                }

                            }
                        }
                    }
                }
            );
    }

    catch (error)
    {
        console.error('Error loading charts:', error);

        alert(
            'Failed to load chart data. ' +
            'Check the browser console for details.');
    }
}

 * Default:
 * Show the last 365 days.*/

window.onload = function()
{

    const today =
        new Date();


    const oneYearAgo =
        new Date();


    oneYearAgo.setFullYear(
        today.getFullYear() - 1
    );


    document.getElementById(
        'startDate'
    ).value =
        oneYearAgo
            .toISOString()
            .slice(0, 10);


    document.getElementById(
        'endDate'
    ).value =
        today
            .toISOString()
            .slice(0, 10);


    loadAllCharts();

};

</script>


</body>

</html>