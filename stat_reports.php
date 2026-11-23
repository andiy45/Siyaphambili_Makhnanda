<?php
// reports-councillor.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once('dbconnection.php');
} catch (Exception $e) {
    die("<b>Database Connection Error:</b> " . $e->getMessage());
}

// ── Councillor guard ──
$role = strtolower(trim((string) ($_SESSION['userrole'] ?? '')));
if (!isset($_SESSION['access']) || $role !== 'councillor') {
    header('Location: login.php');
    exit;
}

//Security check
$councillorWard = $_SESSION['ward'] ?? null;
//if ($councillorWard === null || $councillorWard === '') {
//    die('<p style="font-family:Arial;padding:40px;text-align:center;">Your account has no ward assigned. Contact an administrator.</p>');
//}
$councillorWard = (int) $councillorWard;

// ── Inputs ──
$action    = $_GET['action']     ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-365 days'));
$endDate   = $_GET['end_date']   ?? date('Y-m-d');

// ── Reference lists ──
$ALL_CATEGORIES = [
    'Water & Sanitation',
    'Electricity / Power Outage',
    'Roads & Potholes',
    'Refuse / Waste Management',
    'Street Lighting',
    'Other'];

$ALL_STATUSES = ['Open', 'Pending', 'Resolved'];

// ── Helper: month range ──
function monthRange(string $start, string $end): array
{
    $months = [];
    $s = new DateTime(date('Y-m-01', strtotime($start)));
    $e = new DateTime(date('Y-m-01', strtotime($end)));
    $e->modify('+1 month');
    $period = new DatePeriod($s, new DateInterval('P1M'), $e);
    foreach ($period as $dt) {
        $months[] = $dt->format('Y-m');
    }
    return $months;
}

// ── Escape helper ──
function e($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}


//API MODE — return JSON when ?action= is present

if ($action !== '') {

    header('Content-Type: application/json; charset=utf-8');

    $data = [];

    try {

        switch ($action) {

            /* ── 1. Status counts for this ward ── */
            case 'status_stats':
                $sql = "SELECT status, COUNT(*) AS statuscounts FROM faultticket
                        WHERE ward_number = ?
                          AND created_at >= ?
                          AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                        GROUP BY status";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iss', $councillorWard, $startDate, $endDate);
                $stmt->execute();
                $res = $stmt->get_result();

                $found = [];
                while ($row = $res->fetch_assoc()) {
                    $key = ucfirst(strtolower(trim($row['status'])));
                    $found[$key] = (int) $row['statuscounts'];
                }
                $stmt->close();

                foreach ($ALL_STATUSES as $s) {
                    $data['labels'][] = $s;
                    $data['values'][] = $found[$s] ?? 0;
                }
                break;

            /* ── 2. Faults per category in this ward ── */
            case 'category_stats':
                $sql = "SELECT incident_type, COUNT(*) AS categorycounts
                        FROM faultticket
                        WHERE ward_number = ?
                          AND created_at >= ?
                          AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                        GROUP BY incident_type";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iss', $councillorWard, $startDate, $endDate);
                $stmt->execute();
                $res = $stmt->get_result();

                $found = [];
                while ($row = $res->fetch_assoc()) {
                    $found[$row['incident_type']] = (int) $row['categorycounts'];
                }
                $stmt->close();

                foreach ($ALL_CATEGORIES as $c) {
                    $data['labels'][] = $c;
                    $data['values'][] = $found[$c] ?? 0;
                }
                break;

            /* ── 3. Monthly history per category ── */
            case 'history_stats':
                $months = monthRange($startDate, $endDate);
                $monthIndex = array_flip($months);

                $categories = [];
                foreach ($ALL_CATEGORIES as $c) {
                    $categories[$c] = array_fill(0, count($months), 0);
                }

                $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                               incident_type,
                               COUNT(*) AS cnt
                        FROM faultticket
                        WHERE ward_number = ?
                          AND created_at >= ?
                          AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                        GROUP BY month, incident_type
                        ORDER BY month ASC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iss', $councillorWard, $startDate, $endDate);
                $stmt->execute();
                $res = $stmt->get_result();

                while ($row = $res->fetch_assoc()) {
                    $m = $row['month'];
                    $c = $row['incident_type'];
                    if (isset($monthIndex[$m]) && isset($categories[$c])) {
                        $categories[$c][$monthIndex[$m]] = (int) $row['cnt'];
                    }
                }
                $stmt->close();

                $data['months']     = $months;
                $data['categories'] = $categories;
                break;

            /* ── 4. Top hotspot addresses in this ward ── */
            case 'hotspot_stats':
                $sql = "SELECT COALESCE(NULLIF(TRIM(incident_address), ''), 'Unknown') AS address,
                               COUNT(*) AS cnt
                        FROM faultticket
                        WHERE ward_number = ?
                          AND created_at >= ?
                          AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                        GROUP BY address
                        ORDER BY cnt DESC
                        LIMIT 10";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iss', $councillorWard, $startDate, $endDate);
                $stmt->execute();
                $res = $stmt->get_result();

                while ($row = $res->fetch_assoc()) {
                    $data['labels'][] = $row['address'];
                    $data['values'][] = (int) $row['cnt'];
                }
                $stmt->close();
                break;

            /* ── 5. Longest turnaround times ── */
            case 'turnaround_stats':
                $sql = "SELECT f.ticket_id, f.title,
                               DATEDIFF(ts.changed_at, f.created_at) AS days_taken
                        FROM faultticket f
                        JOIN ticketstatus ts ON f.ticket_id = ts.ticket_id
                        WHERE f.ward_number = ?
                          AND ts.status_type = 'Resolved'
                          AND f.created_at >= ?
                          AND f.created_at < DATE_ADD(?, INTERVAL 1 DAY)
                        ORDER BY days_taken DESC
                        LIMIT 10";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iss', $councillorWard, $startDate, $endDate);
                $stmt->execute();
                $res = $stmt->get_result();

                while ($row = $res->fetch_assoc()) {
                    $data['labels'][] = 'Ticket #' . $row['ticket_id'];
                    $data['values'][] = (int) $row['days_taken'];
                }
                $stmt->close();
                break;

            /* ── 6. Ward summary stats (for the info banner) ── */
            case 'summary':
                $sql = "SELECT COUNT(*) AS total,
                               SUM(CASE WHEN status = 'Open'     THEN 1 ELSE 0 END) AS open_count,
                               SUM(CASE WHEN status = 'Pending'  THEN 1 ELSE 0 END) AS pending_count,
                               SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_count
                        FROM faultticket
                        WHERE ward_number = ?
                          AND created_at >= ?
                          AND created_at < DATE_ADD(?, INTERVAL 1 DAY)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iss', $councillorWard, $startDate, $endDate);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                $data = [
                    'total'    => (int) ($row['total'] ?? 0),
                    'open'     => (int) ($row['open_count'] ?? 0),
                    'pending'  => (int) ($row['pending_count'] ?? 0),
                    'resolved' => (int) ($row['resolved_count'] ?? 0),
                ];
                break;

            default:
                $data['error'] = 'Unknown action: ' . $action;
                break;
        }

    } catch (Exception $e) {
        // Any SQL error becomes a JSON message instead of a blank page
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* =============================================================
 * HTML MODE — render the dashboard
 * ============================================================= */

// Fetch ward label for the heading
$wardLabel = 'Ward ' . $councillorWard;
$wStmt = $conn->prepare("SELECT ward_name FROM ward WHERE ward_id = ?");
$wStmt->bind_param('i', $councillorWard);
$wStmt->execute();
$wRow = $wStmt->get_result()->fetch_assoc();
$wStmt->close();
if ($wRow) {
    $wardLabel = $wRow['ward_name'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Dashboard — <?php echo e($wardLabel); ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }

        button,.button {
            border: 0;
            padding: 9px 13px;
            background: #4b1f2a;
            color: #ffffff;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
            border-radius: 5px;
            display: inline-block;
        }

        button:hover,.button:hover {
            opacity: 0.9;
        }

        .back-button {
            margin-bottom: 15px;
        }
    </style>
</head>

<body class="bg-gray-100 p-6">

<div class="back-button">

    <a class="button" href="councillor.php"> Back to dashboard</a>
</div>


<div class="max-w-7xl mx-auto">

    <!-- ═══════ PAGE TITLE ═══════ -->
    <h1 class="text-3xl font-bold text-gray-800 mb-2">
        📊 Reports Dashboard — <?php echo e($wardLabel); ?>
    </h1>
    <p class="text-sm text-gray-600 mb-6">
        Logged in as <strong><?php echo e($_SESSION['username'] ?? 'Councillor'); ?></strong>
        · Showing data for your ward only
    </p>

    <!-- ═══════ DATE FILTER ═══════ -->
    <div class="bg-white p-4 rounded-lg shadow-md mb-6 flex flex-wrap items-center gap-4">
        <label class="font-semibold text-gray-700">Date Range:</label>

        <input type="date" id="startDate" class="border rounded p-2">
        <span class="text-gray-500">to</span>
        <input type="date" id="endDate" class="border rounded p-2">

        <button onclick="loadAllCharts()"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
            Apply Filter
        </button>

        <span class="text-xs text-gray-500 ml-auto">
            Incident categories:
            Water &amp; Sanitation, Electricity, Roads,
            Refuse, Street Lighting, Other
        </span>
    </div>

    <!-- ═══════ ERROR BANNER (hidden by default) ═══════ -->
    <div id="errorBanner"
         class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
    </div>

    <!-- ═══════ REPORT GRID ═══════ -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Ward Summary Card -->
        <div class="bg-white p-4 rounded-lg shadow-md md:col-span-2">
            <h2 class="text-lg font-semibold mb-3 text-gray-700">
                📌 Ward Summary
            </h2>
            <div id="summaryGrid" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded">
                    <div class="text-xs uppercase text-red-700 font-bold">Open</div>
                    <div class="text-2xl font-bold text-red-800" id="sumOpen">—</div>
                </div>
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-3 rounded">
                    <div class="text-xs uppercase text-yellow-700 font-bold">Pending</div>
                    <div class="text-2xl font-bold text-yellow-800" id="sumPending">—</div>
                </div>
                <div class="bg-green-50 border-l-4 border-green-500 p-3 rounded">
                    <div class="text-xs uppercase text-green-700 font-bold">Resolved</div>
                    <div class="text-2xl font-bold text-green-800" id="sumResolved">—</div>
                </div>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
                    <div class="text-xs uppercase text-blue-700 font-bold">Total</div>
                    <div class="text-2xl font-bold text-blue-800" id="sumTotal">—</div>
                </div>
            </div>
        </div>

        <!-- Status Doughnut -->
        <div class="bg-white p-4 rounded-lg shadow-md">
            <h2 class="text-lg font-semibold mb-2 text-gray-700">
                🍩 Fault Ticket Status
            </h2>
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Category Bar -->
        <div class="bg-white p-4 rounded-lg shadow-md">
            <h2 class="text-lg font-semibold mb-2 text-gray-700">
                📊 Faults by Category
            </h2>
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <!-- Turnaround Chart -->
        <div class="bg-white p-4 rounded-lg shadow-md">
            <h2 class="text-lg font-semibold mb-2 text-gray-700">
                ⏱ Top 10 Longest Turnaround Times
            </h2>
            <div class="chart-container">
                <canvas id="turnaroundChart"></canvas>
            </div>
        </div>

        <!-- Hotspot Chart -->
        <div class="bg-white p-4 rounded-lg shadow-md">
            <h2 class="text-lg font-semibold mb-2 text-gray-700">
                📍 Fault Hot Spots (Top Addresses)
            </h2>
            <div class="chart-container">
                <canvas id="hotspotChart"></canvas>
            </div>
        </div>

        <!-- History Line Chart -->
        <div class="bg-white p-4 rounded-lg shadow-md md:col-span-2">
            <h2 class="text-lg font-semibold mb-2 text-gray-700">
                📈 Monthly History of Complaint Categories
            </h2>
            <div class="chart-container">
                <canvas id="historyChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script>
/* ═══════════════════════════════════════════════════════════════
   Constants
   ═══════════════════════════════════════════════════════════════ */
const INCIDENT_CATEGORIES = [
    "Water & Sanitation",
    "Electricity / Power Outage",
    "Roads & Potholes",
    "Refuse / Waste Management",
    "Street Lighting",
    "Other"
];

const chartStore = {};

/* ═══════════════════════════════════════════════════════════════
   Helpers
   ═══════════════════════════════════════════════════════════════ */
function destroyChart(id) {
    if (chartStore[id]) {
        chartStore[id].destroy();
        delete chartStore[id];
    }
}

function showError(msg) {
    const banner = document.getElementById('errorBanner');
    banner.textContent = 'Error: ' + msg;
    banner.classList.remove('hidden');
}

function hideError() {
    const banner = document.getElementById('errorBanner');
    banner.classList.add('hidden');
}

async function fetchData(action) {
    const startDate = document.getElementById('startDate').value;
    const endDate   = document.getElementById('endDate').value;

    const url = `?action=${encodeURIComponent(action)}` +
                `&start_date=${encodeURIComponent(startDate)}` +
                `&end_date=${encodeURIComponent(endDate)}`;

    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });

    if (!res.ok) {
        throw new Error(`HTTP ${res.status} for ${action}`);
    }

    const json = await res.json();

    if (json.error) {
        throw new Error(`${action}: ${json.error}`);
    }

    return json;
}

/* ═══════════════════════════════════════════════════════════════
   Summary Card
   ═══════════════════════════════════════════════════════════════ */
async function loadSummary() {
    const data = await fetchData('summary');
    document.getElementById('sumOpen').textContent     = data.open;
    document.getElementById('sumPending').textContent  = data.pending;
    document.getElementById('sumResolved').textContent = data.resolved;
    document.getElementById('sumTotal').textContent    = data.total;
}

/* ═══════════════════════════════════════════════════════════════
   Status Doughnut
   ═══════════════════════════════════════════════════════════════ */
async function loadStatusChart() {
    const data = await fetchData('status_stats');

    destroyChart('statusChart');

    const colors = data.labels.map(label => {
        const l = label.toLowerCase();
        if (l === 'open')     return '#EF4444';
        if (l === 'pending')  return '#F59E0B';
        if (l === 'resolved') return '#10B981';
        return '#3B82F6';
    });

    chartStore['statusChart'] = new Chart(
        document.getElementById('statusChart'),
        {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        }
    );
}

/* ═══════════════════════════════════════════════════════════════
   Category Bar
   ═══════════════════════════════════════════════════════════════ */
async function loadCategoryChart() {
    const data = await fetchData('category_stats');

    destroyChart('categoryChart');

    chartStore['categoryChart'] = new Chart(
        document.getElementById('categoryChart'),
        {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Faults',
                    data: data.values,
                    backgroundColor: 'rgba(59,130,246,0.8)',
                    borderColor:     'rgba(59,130,246,1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => `${ctx.raw} faults` } }
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Faults' } },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45,
                            font: { size: 10 }
                        }
                    }
                }
            }
        }
    );
}

/* ═══════════════════════════════════════════════════════════════
   Turnaround Bar
   ═══════════════════════════════════════════════════════════════ */
async function loadTurnaroundChart() {
    const data = await fetchData('turnaround_stats');

    destroyChart('turnaroundChart');

    chartStore['turnaroundChart'] = new Chart(
        document.getElementById('turnaroundChart'),
        {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Days taken',
                    data: data.values,
                    backgroundColor: 'rgba(139,92,246,0.8)',
                    borderColor:     'rgba(139,92,246,1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: { display: true, text: 'Days' }
                    }
                }
            }
        }
    );
}

/* ═══════════════════════════════════════════════════════════════
   Hotspot Bar
   ═══════════════════════════════════════════════════════════════ */
async function loadHotspotChart() {
    const data = await fetchData('hotspot_stats');

    destroyChart('hotspotChart');

    chartStore['hotspotChart'] = new Chart(
        document.getElementById('hotspotChart'),
        {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Faults',
                    data: data.values,
                    backgroundColor: 'rgba(249,115,22,0.8)',
                    borderColor:     'rgba(249,115,22,1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: { display: true, text: 'Faults' }
                    }
                }
            }
        }
    );
}

/* ═══════════════════════════════════════════════════════════════
   History Line Chart
   ═══════════════════════════════════════════════════════════════ */
async function loadHistoryChart() {
    const data = await fetchData('history_stats');

    const months = data.months || [];
    const datasets = [];

    const lineColors = [
        '#3B82F6', '#EF4444', '#10B981',
        '#F59E0B', '#8B5CF6', '#EC4899'
    ];

    if (data.categories) {
        let i = 0;
        INCIDENT_CATEGORIES.forEach(cat => {
            const counts = data.categories[cat] || new Array(months.length).fill(0);
            datasets.push({
                label: cat,
                data: counts,
                borderColor: lineColors[i % lineColors.length],
                backgroundColor: 'transparent',
                borderWidth: 2,
                tension: 0.3,
                fill: false,
                pointRadius: 3
            });
            i++;
        });
    }

    destroyChart('historyChart');

    chartStore['historyChart'] = new Chart(
        document.getElementById('historyChart'),
        {
            type: 'line',
            data: { labels: months, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Complaints' }
                    }
                }
            }
        }
    );
}

/* ═══════════════════════════════════════════════════════════════
   Load Everything
   ═══════════════════════════════════════════════════════════════ */
async function loadAllCharts() {
    hideError();

    try {
        await loadSummary();
        await loadStatusChart();
        await loadCategoryChart();
        await loadTurnaroundChart();
        await loadHotspotChart();
        await loadHistoryChart();
    } catch (err) {
        console.error(err);
        showError(err.message);
    }
}

/* ═══════════════════════════════════════════════════════════════
   Bootstrap — default to last 365 days
   ═══════════════════════════════════════════════════════════════ */
window.addEventListener('DOMContentLoaded', function () {
    const today = new Date();
    const oneYearAgo = new Date();
    oneYearAgo.setFullYear(today.getFullYear() - 1);

    document.getElementById('startDate').value = oneYearAgo.toISOString().slice(0, 10);
    document.getElementById('endDate').value   = today.toISOString().slice(0, 10);

    loadAllCharts();
});
</script>

</body>
</html>