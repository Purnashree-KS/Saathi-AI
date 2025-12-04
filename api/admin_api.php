<?php
// api/admin_api.php

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
    echo json_encode(['error' => 'Database connection file not found or connection failed.']);
    exit;
}

session_start();

// 3. HELPER FUNCTIONS
function send_json($data) {
    ob_clean();
    echo json_encode($data);
    global $conn;
    if ($conn) {
        $conn->close();
    }
    exit;
}

// --- THE ULTIMATE COLUMN DETECTOR ---
function getValidSessionColumn($conn) {
    // ... (no changes)
    $result = $conn->query("SHOW COLUMNS FROM chats");
    if (!$result) {
        return null; // Table doesn't exist
    }

    $existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }

    $candidates = ['id', 'chatid', 'chat_session_id', 'session_id', 'conversation_id'];
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $existing_columns)) {
            return $candidate; // Found one! Use it.
        }
    }

    throw new Exception("Could not identify a session ID column. Your table has: " . implode(', ', $existing_columns));
}

// 4. AUTHENTICATION CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    send_json(['error' => 'Unauthorized access.']);
}

$action = $_GET['action'] ?? '';

try {
    // --- DASHBOARD STATS ---
    if ($action === 'stats') {
        // ... (no changes)
        $totalUsers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role != 'admin'")->fetch_assoc()['c'];
        $totalChats = $conn->query("SELECT COUNT(*) as c FROM chats")->fetch_assoc()['c'];
        $checkAuth = $conn->query("SHOW TABLES LIKE 'auth_events'");
        $activeUsers = 0;
        if ($checkAuth->num_rows > 0) {
            $res = $conn->query("SELECT COUNT(DISTINCT user_email) as c FROM auth_events WHERE timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $activeUsers = $res ? $res->fetch_assoc()['c'] : 0;
        }
        send_json([
            'totalUsers' => $totalUsers,
            'totalChats' => $totalChats,
            'activeUsers' => $activeUsers
        ]);
    }

    // --- ACTIVITY LOG ---
    if ($action === 'activity') {
        // ... (no changes)
        $checkTable = $conn->query("SHOW TABLES LIKE 'auth_events'");
        if ($checkTable->num_rows > 0) {
            $result = $conn->query("SELECT * FROM auth_events ORDER BY timestamp DESC LIMIT 100");
            send_json($result->fetch_all(MYSQLI_ASSOC));
        } else {
            send_json([]);
        }
    }

    // --- FEEDBACK ---
    if ($action === 'feedback') {
        // ... (no changes)
        $checkTable = $conn->query("SHOW TABLES LIKE 'feedback'");
        if ($checkTable->num_rows > 0) {
            $result = $conn->query("SELECT * FROM feedback ORDER BY timestamp DESC LIMIT 100");
            send_json($result->fetch_all(MYSQLI_ASSOC));
        } else {
            send_json([]);
        }
    }

    // --- USER MANAGEMENT ---
    if ($action === 'get_users') {
        // ... (no changes)
        $res = $conn->query("SELECT id, name, email, role, is_verified FROM users WHERE role != 'admin'");
        send_json($res->fetch_all(MYSQLI_ASSOC));
    }
    if ($action === 'delete_user') {
        // ... (no changes)
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->bind_param("i", $input['user_id']);
        $stmt->execute();
        send_json(['success' => $stmt->affected_rows > 0]);
    }
    if ($action === 'update_role') {
        // ... (no changes)
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ? AND role != 'admin'");
        $stmt->bind_param("si", $input['new_role'], $input['user_id']);
        $stmt->execute();
        send_json(['success' => $stmt->affected_rows > 0]);
    }
    if ($action === 'reset_password') {
        // ... (no changes)
        $input = json_decode(file_get_contents('php://input'), true);
        $hash = password_hash($input['new_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $input['user_id']);
        $stmt->execute();
        send_json(['success' => $stmt->affected_rows > 0]);
    }

    // *** NEW: SECURITY SETTINGS API ***
    if ($action === 'get_app_settings') {
        $checkTable = $conn->query("SHOW TABLES LIKE 'app_settings'");
        $settings = [
            'rate_limit' => 30, // Default
            'banned_keywords' => '' // Default
        ];
        if ($checkTable->num_rows > 0) {
            $result = $conn->query("SELECT setting_key, setting_value FROM app_settings");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    // Overwrite default with DB value
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            }
        }
        send_json($settings);
    }

    if ($action === 'save_app_settings') {
        $checkTable = $conn->query("SHOW TABLES LIKE 'app_settings'");
        if ($checkTable->num_rows == 0) {
            send_json(['error' => 'app_settings table does not exist. Please run the SQL setup.']);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);

        // Use REPLACE INTO or INSERT...ON DUPLICATE KEY UPDATE
        // This query will insert a new row if the key doesn't exist,
        // or update the value if it does.
        $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        $key = 'rate_limit';
        $value = $input['rate_limit'] ?? 30;
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();

        $key = 'banned_keywords';
        $value = $input['banned_keywords'] ?? '';
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();

        $stmt->close();
        send_json(['success' => true]);
    }
    // *** END OF NEW API ***

    // --- (Find this block in your admin_api.php) ---
    if ($action === 'get_chat_logs') {
        $checkTable = $conn->query("SHOW TABLES LIKE 'chats'");
        if ($checkTable->num_rows == 0) {
            send_json([]);
        }
        $sessCol = getValidSessionColumn($conn);
        $checkUserCol = $conn->query("SHOW COLUMNS FROM chats LIKE 'user_id'");
        $hasUserId = ($checkUserCol->num_rows > 0);

        if ($hasUserId) {
            // FIX: Use '$.messages' path to count the actual array items
            $query = "SELECT 
                        stats.$sessCol as chat_session_id,
                        stats.msg_count as message_count,
                        stats.last_activity,
                        COALESCE(u.name, 'Guest') as user_name,
                        COALESCE(u.email, 'N/A') as user_email
                    FROM (
                        SELECT 
                            $sessCol, 
                            MAX(updated_at) as last_activity, 
                            MAX(user_id) as distinct_user_id,
                            JSON_LENGTH(messages, '$.messages') as msg_count 
                        FROM chats GROUP BY $sessCol
                    ) stats
                    LEFT JOIN users u ON stats.distinct_user_id = u.id
                    ORDER BY stats.last_activity DESC LIMIT 50";
        } else {
             // FIX: Use '$.messages' path here too
            $query = "SELECT 
                        $sessCol as chat_session_id, 
                        JSON_LENGTH(messages, '$.messages') as message_count, 
                        MAX(updated_at) as last_activity, 
                        'Unknown' as user_name, 
                        'N/A' as user_email
                      FROM chats 
                      GROUP BY $sessCol 
                      ORDER BY last_activity DESC LIMIT 50";
        }
        $result = $conn->query($query);
        if (!$result) throw new Exception($conn->error);
        send_json($result->fetch_all(MYSQLI_ASSOC));
    }

    if ($action === 'get_chat_details') {
        $chatid = $_GET['chatid'] ?? '';
        if (empty($chatid)) {
            send_json(['error' => 'No Chat ID provided.']);
        }
        $sessCol = getValidSessionColumn($conn); 
        
        // Fetch chat AND its timestamp (updated_at)
        $stmt = $conn->prepare("SELECT messages, updated_at FROM chats WHERE $sessCol = ?");
        $stmt->bind_param("s", $chatid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            send_json(['error' => 'Chat session not found.']);
            return;
        }
        
        $row = $result->fetch_assoc();
        $messages_json = $row['messages'];
        $chat_time = $row['updated_at']; // Get the chat's main timestamp
        
        if (empty($messages_json)) {
            send_json([]);
            return;
        }
        
        $data = json_decode($messages_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            send_json([[
                'sender_role' => 'system',
                'message' => '⚠️ System Note: Data is corrupt. ' . json_last_error_msg(),
                'timestamp' => date('Y-m-d H:i:s')
            ]]);
            return;
        }
        
        // --- DATA EXTRACTION LOGIC ---
        $final_messages = [];

        if (isset($data['messages'])) {
            $inner = $data['messages'];
            if (is_array($inner) && (empty($inner) || array_keys($inner) === range(0, count($inner) - 1))) {
                $final_messages = $inner; 
            } else {
                $final_messages = [$inner];
            }
        } 
        else if (is_array($data) && (empty($data) || array_keys($data) === range(0, count($data) - 1))) {
            $final_messages = $data;
        } 
        else {
            $final_messages = [$data];
        }

        // --- FIX: TRANSLATE DATA KEYS (The Magic Fix) ---
        foreach ($final_messages as &$msg) {
            // 1. Convert 'text' to 'message' (Fixes "undefined")
            if (!isset($msg['message']) && isset($msg['text'])) {
                $msg['message'] = $msg['text'];
            }
            
            // 2. Convert 'sender' to 'sender_role'
            if (!isset($msg['sender_role']) && isset($msg['sender'])) {
                $msg['sender_role'] = $msg['sender'];
            }
            
            // 3. Fix 'timestamp' (Fixes "Invalid Date")
            if (empty($msg['timestamp'])) {
                // If individual message has no time, use the chat's last updated time
                $msg['timestamp'] = $chat_time ?? date('Y-m-d H:i:s');
            }
        }
        unset($msg); // Important: break the reference

        send_json($final_messages);
    }

    if ($action === 'delete_chat') {
        // ... (no changes)
        $input = json_decode(file_get_contents('php://input'), true);
        $sessCol = getValidSessionColumn($conn);
        $stmt = $conn->prepare("DELETE FROM chats WHERE $sessCol = ?");
        $stmt->bind_param("s", $input['chat_session_id']);
        $stmt->execute();
        send_json(['success' => true]);
    }

    if ($action === 'delete_old_logs') {
        // ... (no changes)
        $conn->query("DELETE FROM chats WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        send_json(['success' => true, 'deleted_count' => $conn->affected_rows]);
    }

    // ---- Only ONE fallback inside try ----
    send_json(['error' => 'Invalid action']);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
    exit;
}
?>