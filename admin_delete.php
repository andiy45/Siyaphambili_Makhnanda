<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("dbconnection.php");

// Check if deletion is confirmed via POST
if (isset($_POST['id']) && isset($_POST['confirm']) && $_POST['confirm'] == 'yes') {
    $id = intval($_POST['id']);
    // Delete user
    $delete_query = "UPDATE FROM theusers SET accountstatus WHERE id = 1";

    if ($conn->query($delete_query)) {
        header("Location: users_list.php?msg=User deleted successfully");
        exit;
    } else {
        echo "Error deleting user: " . $conn->error;
    }
} 
// Show confirmation page when ID is from GET
elseif (isset($_GET['id']) && !isset($_GET['confirm'])) {
    $id = intval($_GET['id']);
    
    // Get user details to display
    $user_query = "SELECT * FROM theusers WHERE accountstatus IS NULL";
    $user_result = $conn->query($user_query);
    $user = $user_result->fetch_assoc();
}
    ?>