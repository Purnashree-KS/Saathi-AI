<?php
ini_set('display_errors', 0); // Don't output PHP errors to the browser
error_reporting(0); // Stop warnings from breaking our JSON
header('Content-Type: application/json'); // We will ALWAYS return JSON

$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password
$dbname = "saathi_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Explicitly die with JSON. This will now be caught by your JS.
    die(json_encode([
        'success' => false, 
        'message' => 'Database connection error: ' . $conn->connect_error
    ]));
}

// Start the session
session_start();

// Function to safely get user ID
function get_user_id() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

// Function to record auth events
function recordAuthEvent($conn, $email, $name, $role, $type) {
    $stmt = $conn->prepare("INSERT INTO auth_events (user_email, name, role, event_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email, $name, $role, $type);
    $stmt->execute();
}
?>