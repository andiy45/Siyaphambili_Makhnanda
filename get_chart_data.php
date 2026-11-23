<?php
require_once 'dbconnection.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$data = [];

switch ($action) {
    case 'ward_stats':
        // Histogram: Service fault statistics per ward (14 wards)
        // Note: If a ward has 0 faults, it won't appear unless you use a LEFT JOIN, 
        // but this query returns the exact data available.
        $sql = "SELECT w.ward_name, COUNT(f.ticket_id) as total_faults 
                FROM faultticket f 
                JOIN ward w ON f.ward_id = w.ward_id 
                WHERE f.created_at BETWEEN ? AND ? 
                GROUP BY f.ward_id 
                ORDER BY total_faults DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data['labels'][] = $row['ward_name'];
            $data['values'][] = $row['total_faults'];
        }
        break;

    case 'status_stats':
        // Doughnut: Service fault progress (Open, Pending, Closed)
        // We convert to lowercase and capitalize to ensure consistency
        $sql = "SELECT 
                    CONCAT(UPPER(LEFT(status, 1)), LOWER(SUBSTRING(status, 2))) as status_label, 
                    COUNT(ticket_id) as count 
                FROM faultticket 
                WHERE created_at BETWEEN ? AND ? 
                GROUP BY status_label";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data['labels'][] = $row['status_label'];
            $data['values'][] = $row['count'];
        }
        break;

    case 'history_stats':
        // Line Chart: History of categories of complaints (Monthly Trend)
        $sql = "SELECT 
                    DATE_FORMAT(f.created_at, '%Y-%m') as month, 
                    c.category_name, 
                    COUNT(f.ticket_id) as count 
                FROM faultticket f 
                JOIN faultcategory c ON f.category_id = c.category_id 
                WHERE f.created_at BETWEEN ? AND ? 
                GROUP BY month, c.category_name 
                ORDER BY month ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data['months'][] = $row['month'];
            $data['categories'][$row['category_name']][] = $row['count'];
        }
        break;

    case 'hotspot_stats':
        // Tree Diagram (Horizontal Bar): Hot spot visualisation (Top 10 Addresses)
        $sql = "SELECT incident_address, COUNT(ticket_id) as count 
                FROM faultticket 
                WHERE created_at BETWEEN ? AND ? 
                GROUP BY incident_address 
                ORDER BY count DESC 
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data['labels'][] = $row['incident_address'];
            $data['values'][] = $row['count'];
        }
        break;

    case 'turnaround_stats':
        // Tree Diagram (Horizontal Bar): Turnaround times (Top 10 Longest)
        $sql = "SELECT 
                    f.ticket_id, 
                    f.title, 
                    DATEDIFF(ts.changed_at, f.created_at) as days_taken 
                FROM faultticket f 
                JOIN ticketstatus ts ON f.ticket_id = ts.ticket_id 
                WHERE ts.status_type = 'Resolved' 
                AND f.created_at BETWEEN ? AND ? 
                ORDER BY days_taken DESC 
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data['labels'][] = "Ticket #" . $row['ticket_id'];
            $data['values'][] = $row['days_taken'];
        }
        break;
}

$stmt->close();
$conn->close();

echo json_encode($data);
?>