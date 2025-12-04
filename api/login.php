<?php
include 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'];
$password = $data['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'No account found with this email.']);
    exit;
}

$user = $result->fetch_assoc();

// Verify the password
if (password_verify($password, $user['password'])) {
    // Password is correct!
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    // Record login event
    recordAuthEvent($conn, $user['email'], $user['name'], $user['role'], 'login');

    echo json_encode(['success' => true, 'role' => $user['role'], 'user' => [
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ]]);
} else {
    echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
}

$stmt->close();
$conn->close();
?>