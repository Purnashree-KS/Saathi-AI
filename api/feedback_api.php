<?php
include 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

$user_id = get_user_id() ?: null;
$user_name = $_SESSION['user_name'] ?? 'Anonymous';
$user_email = $_SESSION['user_email'] ?? 'anonymous';
$message = $data['message'];

$stmt = $conn->prepare("INSERT INTO feedback (user_id, user_name, user_email, message) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $user_id, $user_name, $user_email, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>