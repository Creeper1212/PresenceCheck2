<?php
// filepath: /c:/xampp/htdocs/PRESENCE CHECK/config.php
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "presencecheck";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    error_log("Connection failed: " . mysqli_connect_error());
    echo "We are experiencing technical difficulties. Please try again later.";
    exit;
}
?>