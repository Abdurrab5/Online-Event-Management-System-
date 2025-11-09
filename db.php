<?php
// db.php - Database connection file

$host = "localhost";       // or your server name
$user = "root";            // default XAMPP/WAMP username
$pass = "";                // default XAMPP/WAMP password is empty
$dbname = "oems";          // your database name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set charset
$conn->set_charset("utf8");

?>
