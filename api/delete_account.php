<?php
include 'db_connect.php'; // This already sets header and starts session

$user_id = get_user_id();
if ($user_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// We will delete the user. The database constraints (ON DELETE CASCADE)
// will automatically delete all their chats, settings, etc.
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    session_destroy(); // Log them out
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete account.']);
}

$stmt->close();
$conn->close();
?>