<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // <-- Change to your DB username
define('DB_PASSWORD', ''); // <-- Change to your DB password
define('DB_NAME', 'ecommerce_db'); // <-- Change to your DB name

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . $conn->connect_error);
}
?>