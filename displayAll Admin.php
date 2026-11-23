<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("dbconnection.php");

// Approve a pending user.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    $idnumber = trim($_POST['approve_id']);

    if ($idnumber !== '') {
        $stmt = $conn->prepare("UPDATE theusers SET validated = 1 WHERE idnumber = ? AND validated = 0");
        $stmt->bind_param("s", $idnumber);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=User approved successfully');
    exit;
}

// Handle deletion if confirmed
if (isset($_GET['delete_id']) && isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $idnumber = trim($_GET['delete_id']);
    $delete_query = $conn->prepare("DELETE FROM theusers WHERE idnumber = ?");
    $delete_query->bind_param("s", $idnumber);
    
    if ($delete_query->execute()) {
        echo "<script>alert('User deleted successfully!'); window.location.href='".$_SERVER['PHP_SELF']."';</script>";
        exit;
    } else {
        echo "Error deleting user: " . $conn->error;
    }
}

// Handle deletion request - show confirmation
if (isset($_GET['delete_id']) && !isset($_GET['confirm'])) {
    $idnumber = trim($_GET['delete_id']);
    
    // Get user details
    $user_query = $conn->prepare("SELECT username, surname, firstname FROM theusers WHERE idnumber = ?");
    $user_query->bind_param("s", $idnumber);
    $user_query->execute();
    $user_result = $user_query->get_result();
    $user = $user_result->fetch_assoc();
    
    if ($user) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Confirm Deletion</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: Arial, sans-serif; 
                    background-color: #ffffff;
                    padding: 40px;
                }
                .confirm-box { 
                    border: 3px solid #4b1f2a;
                    padding: 30px; 
                    max-width: 500px; 
                    margin: 0 auto;
                    background-color: #ffffff;
                    border-radius: 10px;
                    box-shadow: 0 4px 10px rgba(75, 31, 42, 0.2);
                }
                .warning { 
                    color: #4b1f2a;
                    font-weight: bold;
                    font-size: 24px;
                    margin-bottom: 20px;
                    text-align: center;
                }
                .btn { 
                    padding: 10px 25px; 
                    margin: 5px; 
                    cursor: pointer; 
                    border: none; 
                    border-radius: 5px;
                    font-weight: bold;
                    transition: background 0.3s;
                }
                .btn-danger { 
                    background-color: #4b1f2a;
                    color: white;
                }
                .btn-danger:hover {
                    background-color: #6b2f3a;
                }
                .btn-cancel { 
                    background-color: #e0d5ca;
                    color: #4b1f2a;
                }
                .btn-cancel:hover {
                    background-color: #d0c5ba;
                }
                .user-detail {
                    background-color: #f9f5f0;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 15px 0;
                }
                .user-detail p {
                    margin: 8px 0;
                    color: #333;
                }
                .user-detail strong {
                    color: #4b1f2a;
                }
                .delete-warning {
                    color: #4b1f2a;
                    font-weight: bold;
                    text-align: center;
                    padding: 10px;
                    background-color: #f9f5f0;
                    border-radius: 5px;
                    margin: 15px 0;
                }
                .btn-container {
                    text-align: center;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="confirm-box">
                <h2 class="warning">⚠️ Confirm User Deletion</h2>
                <p style="text-align: center; color: #333; margin-bottom: 15px;">Are you sure you want to delete this user?</p>
                
                <div class="user-detail">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['surname']); ?></p>
                </div>
                
                <p class="delete-warning">⚠️ This action cannot be undone!</p>
                
                <div class="btn-container">
                    <a href="?delete_id=<?php echo urlencode($idnumber); ?>&confirm=yes">
                        <button class="btn btn-danger">Yes, Delete User</button>
                    </a>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <button class="btn btn-cancel">Cancel</button>
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Display user list
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background-color: #ffffff;
            padding: 20px;
        }
        
        h3 {
            color: #4b1f2a;
            font-size: 28px;
            margin: 20px 0;
        }
        
        .page-header {
            background-color: #4b1f2a;
            color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .page-header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .success-msg {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #c3e6cb;
        }
        
        .table-container {
            overflow-x: auto;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
            font-size: 14px;
        }
        
        th {
            background-color: #4b1f2a;
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #4b1f2a;
            white-space: nowrap;
        }
        
        td {
            padding: 10px 10px;
            border: 1px solid #ddd;
            color: #333;
        }
        
        tr:nth-child(even) {
            background-color: #f9f5f0;
        }
        
        tr:hover {
            background-color: #e0d5ca;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-update {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-update:hover {
            background-color: #45a049;
        }
        
        .btn-delete {
            background-color: #4b1f2a;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-delete:hover {
            background-color: #6b2f3a;
        }
        
        .btn-menu {
            padding: 12px 40px;
            background-color: #4b1f2a;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: background 0.3s;
            margin-top: 20px;
        }
        
        .btn-menu:hover {
            background-color: #6b2f3a;
        }
        
        .center {
            text-align: center;
        }
        
        .no-records {
            text-align: center;
            padding: 30px;
            color: #666;
        }
        
        /* Mobile responsiveness */
        @media screen and (max-width: 768px) {
            table {
                font-size: 12px;
            }
            th, td {
                padding: 8px 5px;
            }
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            .btn-update, .btn-delete {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<div class="page-header">
    <h2>Validate User Accounts</h2>
</div>
    <!-- List header: left = back link + title, right = action button -->
    <div class="list-header" style="margin-bottom:18px; display:flex; justify-content:space-between; align-items:center; gap:16px;">
        <div class="list-left" style="display:flex; align-items:center; gap:12px;">
            <h3 style="margin:0; color:#4b1f2a; font-size:28px;">Accounts Awaiting Validation</h3>
        </div>
        <div class="list-right">
            <a class="btn-menu" href="adminDashboard.php">Back to Menu</a>
        </div>
    </div>
<?php
// Check if there's a success message from deletion
if (isset($_GET['msg'])) {
    echo '<div class="success-msg">✓ ' . htmlspecialchars($_GET['msg']) . '</div>';
}
?>

<!-- title moved into header above -->

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Surname</th>
                <th>First Name</th>
                <th>ID Number</th>
                <th>Ward</th>
                <th style="text-align: center; min-width: 180px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $selectAll = $conn->query("SELECT username, surname, firstname, idnumber, ward FROM theusers WHERE validated = 0 ORDER BY username") or die("Error fetching records: " . $conn->error);
            
            if ($selectAll->num_rows > 0) {
                while ($row = $selectAll->fetch_assoc()){ 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['surname']); ?></td>
                        <td><?php echo htmlspecialchars($row['firstname']); ?></td>
                        <td><?php echo htmlspecialchars($row['idnumber']); ?></td>
                        <td><?php echo htmlspecialchars($row['ward']); ?></td>
                        <td style="text-align: center;">
                            <div class="action-buttons">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" onsubmit="return confirm('Validate this user account?');">
                                    <input type="hidden" name="approve_id" value="<?php echo htmlspecialchars($row['idnumber']); ?>">
                                    <button class="btn-update" type="submit">Approve</button>
                                </form>
                                <a class="btn-delete" href="?delete_id=<?php echo urlencode($row['idnumber']); ?>" onclick="return confirm('Are you sure you want to delete this user?');">🗑️ Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo "<tr><td colspan='6' class='no-records'>No registered users found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>


</body>
</html>