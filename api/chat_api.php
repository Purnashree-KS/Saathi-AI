<?php
// api/chat_api.php

// 1. PREVENT OUTPUT CORRUPTION
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// 2. SMART DATABASE CONNECTION
$db_paths = ['db_connect.php', '../db_connect.php', '../../db_connect.php'];
$conn = null;
$path_found = false;

foreach ($db_paths as $path) {
    if (file_exists($path)) {
        include $path;
        $path_found = true;
        if (isset($conn) && $conn instanceof mysqli) {
            break;
        }
    }
}

if (!$path_found || !$conn) {
    ob_clean();
    echo json_encode(['error' => 'Database connection failed in chat_api.php']);
    exit;
}

// Helper to get user ID safely
if (!function_exists('get_user_id')) {
    function get_user_id() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['user_id'] ?? 0;
    }
}

$user_id = get_user_id();
if ($user_id === 0) {
    ob_clean();
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ==========================================
// 3. WEB SEARCH FEATURE (SerpApi)
// ==========================================
if ($action === 'web_search') {
    $input = json_decode(file_get_contents('php://input'), true);
    $query = $input['query'] ?? '';

    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'No query provided']);
        exit;
    }

    // *** YOUR SERPAPI KEY ***
    // We removed the "if" check that was causing the issue.
    $serp_api_key = "YOUR_SERP_API_KEY"; // Replace with your actual key 

    $url = "https://serpapi.com/search.json?engine=google&q=" . urlencode($query) . "&api_key=" . $serp_api_key;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        
        // Extract concise summary to save tokens
        $summary = "";
        
        // 1. Check for "Answer Box"
        if (isset($data['answer_box'])) {
            $box = $data['answer_box'];
            if (isset($box['answer'])) $summary .= "Quick Answer: " . $box['answer'] . "\n";
            if (isset($box['snippet'])) $summary .= "Details: " . $box['snippet'] . "\n";
            if (isset($box['date'])) $summary .= "Date: " . $box['date'] . "\n";
        }

        // 2. Check for "Sports Results"
        if (isset($data['sports_results'])) {
            $sports = $data['sports_results'];
            if (isset($sports['game_spotlight'])) $summary .= "Game Info: " . $sports['game_spotlight'] . "\n";
        }

        // 3. Organic Results (Top 5)
        if (isset($data['organic_results'])) {
            $summary .= "\nSearch Results:\n";
            $count = 0;
            foreach ($data['organic_results'] as $result) {
                if ($count >= 5) break; 
                $title = $result['title'] ?? '';
                $snippet = $result['snippet'] ?? '';
                $date = $result['date'] ?? '';
                $summary .= "- $title ($date): $snippet\n";
                $count++;
            }
        }

        ob_clean();
        echo json_encode(['success' => true, 'result' => $summary]);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Failed to contact search API']);
    }
    exit;
}

// ==========================================
// 4. SECURITY CHECK FEATURE
// ==========================================
if ($action === 'check_message') {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_prompt_text = $input['text'] ?? '';
    $text_to_check = strtolower(trim($user_prompt_text));

    $rate_limit = 30; 
    $banned_keywords_raw = ''; 

    $checkTable = $conn->query("SHOW TABLES LIKE 'app_settings'");
    if ($checkTable->num_rows > 0) {
        $result = $conn->query("SELECT setting_key, setting_value FROM app_settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $key = trim($row['setting_key']);
                if ($key === 'rate_limit') $rate_limit = (int)$row['setting_value'];
                if ($key === 'banned_keywords') $banned_keywords_raw = $row['setting_value'];
            }
        }
    }

    if (!empty($banned_keywords_raw) && !empty($text_to_check)) {
        $keywords = explode(',', $banned_keywords_raw);
        foreach ($keywords as $keyword) {
            $trimmed_keyword = strtolower(trim($keyword));
            if (empty($trimmed_keyword)) continue;
            if (strpos($text_to_check, $trimmed_keyword) !== false) {
                ob_clean();
                echo json_encode([
                    'success' => false, 
                    'error' => "Restricted word detected: '$trimmed_keyword'."
                ]);
                exit; 
            }
        }
    }

    // Rate Limit Check
    if ($rate_limit > 0) {
        $checkTableReq = $conn->query("SHOW TABLES LIKE 'user_requests'");
        if ($checkTableReq->num_rows > 0) {
            $rl_stmt = $conn->prepare("SELECT COUNT(*) as c FROM user_requests WHERE user_id = ? AND timestamp > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
            $rl_stmt->bind_param("i", $user_id);
            $rl_stmt->execute();
            $rl_count = $rl_stmt->get_result()->fetch_assoc()['c'];
            
            if ($rl_count >= $rate_limit) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Rate limit exceeded.']);
                exit;
            }
            $log_stmt = $conn->prepare("INSERT INTO user_requests (user_id) VALUES (?)");
            $log_stmt->bind_param("i", $user_id);
            $log_stmt->execute();
        }
    }

    ob_clean();
    echo json_encode(['success' => true]);
    exit;
}

// ==========================================
// 5. STANDARD CHAT FEATURES
// ==========================================

if ($action === 'load_history') {
    $checkTable = $conn->query("SHOW TABLES LIKE 'chats'");
    if ($checkTable->num_rows == 0) { echo json_encode([]); exit; }

    $stmt = $conn->prepare("SELECT id, title, messages FROM chats WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $chatHistory = [];
    while ($row = $result->fetch_assoc()) {
        $chatObj = json_decode($row['messages'], true);
        if (is_array($chatObj)) {
            if (!isset($chatObj['id']))    $chatObj['id']    = $row['id'];
            if (!isset($chatObj['title'])) $chatObj['title'] = $row['title'];
            $chatHistory[] = $chatObj;
        }
    }
    ob_clean();
    echo json_encode($chatHistory);
    exit;
}

if ($action === 'save_history') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $conn->query("CREATE TABLE IF NOT EXISTS chats (
        id VARCHAR(50) PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255),
        messages JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $conn->begin_transaction();
    try {
        $stmt_delete = $conn->prepare("DELETE FROM chats WHERE user_id = ?");
        $stmt_delete->bind_param("i", $user_id);
        $stmt_delete->execute();

        $stmt_insert = $conn->prepare("INSERT INTO chats (id, user_id, title, messages) VALUES (?, ?, ?, ?)");
        foreach ($data as $chat) {
            $chat_id = $chat['id'];
            $title   = $chat['title'] ?? 'Untitled';
            $messages_json = json_encode($chat); 
            $stmt_insert->bind_param("siss", $chat_id, $user_id, $title, $messages_json);
            $stmt_insert->execute();
        }
        $conn->commit();
        ob_clean();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        ob_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'load_chat') {
    $chat_id = $_GET['chat_id'] ?? '';

    $stmt = $conn->prepare("SELECT messages FROM chats WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("si", $chat_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    $messages = [];
    if ($row) {
        $chatObj = json_decode($row['messages'], true);
        if (is_array($chatObj) && isset($chatObj['messages']) && is_array($chatObj['messages'])) {
            foreach ($chatObj['messages'] as $m) {
                if (!empty($m['message_type']) && $m['message_type'] === 'image') {
                    $content = $m['content'] ?? '';
                    if ($content && strpos($content, 'uploads/') !== 0) {
                        $content = 'uploads/chat_images/' . $content;
                    }
                    $m['content'] = $content;
                }
                $messages[] = $m;
            }
        }
    }
    ob_clean();
    echo json_encode(['messages' => $messages]);
    exit;
}

if ($action === 'upload_image') {
    $uploadDir = __DIR__ . '/../uploads/chat_images';
    if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }

    if (!isset($_FILES['image'])) {
        echo json_encode(['error' => 'No file sent']); exit;
    }

    $file = $_FILES['image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['error'=>'Invalid file type']); exit;
    }

    $newName = 'img_' . time() . '_' . mt_rand(10000, 99999) . '.' . $ext;
    $dstPath = $uploadDir . '/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $dstPath)) {
        echo json_encode(['error' => 'Upload failed']); exit;
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'url'     => 'uploads/chat_images/' . $newName 
    ]);
    exit;
}

ob_clean();
echo json_encode(['error' => 'Unknown action']);
exit;
?>