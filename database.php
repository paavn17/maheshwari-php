<?php
$servername = "127.0.0.1";
$username = "root";
$password = ""; 
$database = "id_card_system";

// Create connection 
$conn = new mysqli($servername, $username, $password, $database);

// Check connection and handle errors gracefully
if ($conn->connect_error) {
    // For production, better to log errors than echo
    die("Database connection failed: " . $conn->connect_error);
}

// Set character set to UTF8 for proper encoding
$conn->set_charset("utf8mb4");

// No echo in connection files; let calling scripts handle status
// echo "Connected successfully";

// Note: Do NOT close the connection here; leave open for scripts to use

?>
