<?php
session_start();
include ('dbconnection.php');
include("reportfault.html");
include("login.html");

//check if the user logged in 
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    die("User is not logged in.");
}

//checking if the ward number exists 
if (isset($_SESSION['wardnumber'])) {
$ward = $_SESSION['wardnumber'];
} else {
$ward = "Default ward"; // Provide a default value or handle the missing data case
}

// Checking if connection failed
if ($connection -> connect_error) {
die("<p class=\"error\">Connection failed: Incorrect credentials or Database not available!</p>");
}

// Retrieve the resident ward from the database
$user_id = $_SESSION['user_id'];
$sql1 = "SELECT ward_id FROM users WHERE user_id = '$user_id'";

$result1 = $connection->query($sql1); //executing query

if($result1->num_rows > 0) {   // If ward found
$row = $result1->fetch_assoc(); // Get result row
$wardID = $row['ward_id']; // Assign the ward
} else {
$wardID = "Unknown ward"; // error if ward is not found
}

$fault = $_REQUEST['fault_  category'];
$description = $_REQUEST['description'];
$priority = $_REQUEST['priority'];

$statusQuery = "SELECT statuses_id FROM statuses WHERE status_name = 'Opened'";
$created_at = date("Y-m-d H:i:s");

//insert into database 
$sql=INSERT INTO faultticket( ticket_id,user_id,ward_id,statues_id,category_id,assigned_to,title,description,created_at,attachment,fault_address)
VALUES($ticket_id,'$user_id','$ward_id','$statues_id','$category_id','$assigned_to','$title','$description','$created_at','$attachment','$fault_address')";

$result=$connection->query($sql);//execute


if ($result === TRUE) {
// if fault creation successful, get the inserted fault ID
$fault_value = $connection->insert_id;

// Process file uploads
$countFiles = count($_FILES['picture']['name']); // Get the number of uploaded files
for ($i=0; $i < $countFiles; $i++) {

$picture = time() . "_" . basename($_FILES['picture']['name'][$i]);
$destination = "../pictures/" . $picture;

// Move uploaded file to the destination folder
if (move_uploaded_file($_FILES['picture']['tmp_name'][$i], $destination)) {
// Insert the picture into the attachment table 
$uploadPicture = "INSERT INTO photos (ticket_id, attachment) VALUES ('$ticket_id', '$attachment');";
if ($connection->query($uploadPicture) !== TRUE) {
echo "<p class=\"error\">Failed to upload picture: " . $connection->error . "</p>";
}
} else {
echo "<p class=\"error\">Error uploading file " . basename($_FILES['picture']['name'][$i]) . ".</p>";
}
}
       
