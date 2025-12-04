<?php
session_start();

// If user is not logged in, or is not an admin, redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Saathi AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .dark ::-webkit-scrollbar-track { background: #1f2937; }
        .dark ::-webkit-scrollbar-thumb { background: #4b5563; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #6b7280; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <nav class="bg-white dark:bg-gray-800 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-gray-900 dark:text-white">Saathi AI Admin</span>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="theme-toggle" class="p-2 rounded-lg text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                        <svg id="theme-toggle-dark-icon" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                        <svg id="theme-toggle-light-icon" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path></svg>
                    </button>
                    <button id="logout-button" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-white" id="total-users">0</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Chats</dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-white" id="total-chats">0</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Users (24h)</dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-white" id="active-users">0</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Success Rate</dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-white" id="success-rate">0%</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Activity Log</h3>
                </div>
                <div class="p-4">
                    <div class="space-y-4 max-h-96 overflow-y-auto" id="activity-log">
                        </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">User Feedback</h3>
                </div>
                <div class="p-4">
                    <div class="space-y-4 max-h-96 overflow-y-auto" id="feedback-list">
                        </div>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">User Management</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="user-list-table">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                Loading user data...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <form id="security-settings-form">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Security & Privacy Settings</h3>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Save Settings
                    </button>
                </div>
                <div class="p-6 space-y-6">
                    <div>
                        <label for="setting-rate-limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rate Limit (Requests per Minute)</label>
                        <input type="number" id="setting-rate-limit" name="rate_limit" class="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm text-gray-900 dark:text-white" placeholder="e.g., 30">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Max number of messages a user can send per minute. Set to 0 to disable.</p>
                    </div>
                    <div>
                        <label for="setting-banned-keywords" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Content Filtering (Banned Keywords)</label>
                        <textarea id="setting-banned-keywords" name="banned_keywords" rows="4" class="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm text-gray-900 dark:text-white" placeholder="word1,word2,another word"></textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Comma-separated list of words. If a user's prompt contains any of these, the request will be blocked.</p>
                    </div>
                    </div>
            </form>
        </div>
        <div class="mt-8 bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Chat Monitoring & Logs</h3>
                <button onclick="deleteAllOldLogs()" class="px-3 py-1 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                    Delete Logs (Older than 30 days)
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Chat Session ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Message Count</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Activity</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="chat-log-table">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                Loading chat logs...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="chat-view-modal" class="fixed inset-0 bg-gray-600 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-3xl max-h-[80vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-chat-title">Conversation Details</h3>
                <button id="modal-close-button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto space-y-4" id="modal-chat-content">
                </div>
        </div>
    </div>

    <div id="toast-notification" class="fixed bottom-5 right-5 hidden max-w-xs bg-gray-900 dark:bg-white text-white dark:text-gray-900 px-4 py-3 rounded-lg shadow-lg">
        <span id="toast-message"></span>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Check admin access
            const user = <?php echo json_encode($_SESSION ?? null); ?>;
            if (!user || !user.user_id || user.role !== 'admin') {
                window.location.href = 'login.html';
                return;
            }

            // Initialize UI
            updateStats();
            loadActivityLog();
            loadFeedback();
            loadUserManagement();
            loadChatLogs();
            setupThemeToggle();
            setupLogout();
            setupChatLogModal();

            // *** NEW: Init Security Settings ***
            loadSecuritySettings();
            setupSettingsForm();
            // *** END OF NEW ***
        });

        function updateStats() {
            fetch('api/admin_api.php?action=stats')
                .then(response => response.json())
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    
                    document.getElementById('total-users').textContent = data.totalUsers;
                    document.getElementById('total-chats').textContent = data.totalChats;
                    document.getElementById('active-users').textContent = data.activeUsers;
                    
                    // --- FIX: Use (Active Users / Total Users) * 100 ---
                    // This creates a real percentage that cannot exceed 100%
                    let successRate = 0;
                    if (data.totalUsers > 0) {
                        successRate = Math.round((data.activeUsers / data.totalUsers) * 100);
                    }
                    
                    document.getElementById('success-rate').textContent = `${successRate}%`;
                })
                .catch(err => console.error('Error loading stats:', err));
        }

        function loadActivityLog() {
            // ... (no changes)
            const logContainer = document.getElementById('activity-log');
            if (!logContainer) return;
            fetch('api/admin_api.php?action=activity')
                .then(response => response.json())
                .then(events => {
                    if (events.error) throw new Error(events.error);
                    logContainer.innerHTML = events.map(event => {
                        const date = new Date(event.timestamp).toLocaleString();
                        const type = event.event_type === 'login' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20';
                        const icon = event.event_type === 'login' ? '🟢' : '🔴';
                        return `
                            <div class="flex items-center p-3 ${type} rounded-lg">
                                <span class="mr-2">${icon}</span>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        ${event.name} (${event.user_email})
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        ${event.event_type} at ${date}
                                    </p>
                                </div>
                                <span class="text-xs font-medium px-2 py-1 rounded-full ${
                                    event.role === 'admin' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
                                }">${event.role}</span>
                            </div>
                        `;
                    }).join('') || '<p class="text-gray-500 dark:text-gray-400 text-center py-4">No activity logged yet</p>';
                })
                .catch(err => {
                    console.error('Error loading activity log:', err);
                    logContainer.innerHTML = '<p class="text-red-500 text-center py-4">Error loading activity log</p>';
                });
        }

        function loadFeedback() {
            // ... (no changes)
            const feedbackContainer = document.getElementById('feedback-list');
            if (!feedbackContainer) return;
             fetch('api/admin_api.php?action=feedback')
                .then(response => response.json())
                .then(feedbacks => {
                    if (feedbacks.error) throw new Error(feedbacks.error);
                    feedbackContainer.innerHTML = feedbacks.map(fb => {
                        const date = new Date(fb.timestamp).toLocaleString();
                        return `
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">${fb.user_name}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">${fb.user_email}</p>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">${date}</span>
                                </div>
                                <p class="text-gray-600 dark:text-gray-300">${fb.message}</p>
                            </div>
                        `;
                    }).join('') || '<p class="text-gray-500 dark:text-gray-400 text-center py-4">No feedback received yet</p>';
                })
                .catch(err => {
                    console.error('Error loading feedback:', err);
                    feedbackContainer.innerHTML = '<p class="text-red-500 text-center py-4">Error loading feedback</p>';
                });
        }    

        // --- Chat Monitoring Functions (no changes) ---
        function loadChatLogs() {
            // ... (no changes)
            const tableBody = document.getElementById('chat-log-table');
            fetch('api/admin_api.php?action=get_chat_logs')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP status ${response.status}`);
                    }
                    return response.text(); 
                })
                .then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error("Server returned invalid JSON: " + text.substring(0, 100));
                    }
                })
                .then(logs => {
                    if (logs.error) throw new Error(logs.error);
                    if (!Array.isArray(logs) || logs.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No chat logs found.</td></tr>`;
                        return;
                    }
                    tableBody.innerHTML = logs.map(log => {
                        const lastActivity = new Date(log.last_activity).toLocaleString();
                        return `
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${log.user_name || 'Guest'} <br> <span class="text-xs text-gray-500">${log.user_email}</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 font-mono">${log.chat_session_id}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${log.message_count}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${lastActivity}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button onclick="viewChat('${log.chat_session_id}')" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200" title="View Chat">View</button>
                                    <button onclick="deleteChat('${log.chat_session_id}')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200" title="Delete Chat">Delete</button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                })
                .catch(err => {
                    console.error('Error loading chat logs:', err); 
                    tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-red-500 font-bold">Error loading logs: ${err.message}</td></tr>`;
                });
        }
        function viewChat(chatSessionId) {
            // ... (no changes)
            const modal = document.getElementById('chat-view-modal');
            const title = document.getElementById('modal-chat-title');
            const content = document.getElementById('modal-chat-content');
            title.textContent = `Conversation: ${chatSessionId}`;
            if (!chatSessionId) {
                console.error('Missing chatSessionId');
                content.innerHTML = '<p class="text-red-500">Invalid chat session ID.</p>';
                return;
            }
            content.innerHTML = '<p class="text-gray-500 dark:text-gray-400">Loading chat details...</p>';
            modal.classList.remove('hidden');
            fetch(`api/admin_api.php?action=get_chat_details&chatid=${chatSessionId}`)
                .then(response => response.json())
                .then(messages => {
                    if (messages.error) {
                    content.innerHTML = `<p class="text-red-500">Error: ${messages.error}</p>`;
                    return;
                    }
                    if (!Array.isArray(messages)) {
                    content.innerHTML = "<p class='text-red-500'>Error: Chat data is not a valid array.</p>";
                    return;
                    }
                    if (messages.length === 0) {
                    content.innerHTML = "<p class='text-gray-500'>No messages in this chat.</p>";
                    return;
                    }
                    content.innerHTML = messages.map(msg => {
                        const timestamp = new Date(msg.timestamp).toLocaleString();
                        const isUser = msg.sender_role === 'user';
                        return `
                            <div class="p-3 rounded-lg ${isUser ? 'bg-blue-50 dark:bg-blue-900/30' : 'bg-gray-50 dark:bg-gray-700/50'}">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-medium text-sm ${isUser ? 'text-blue-800 dark:text-blue-300' : 'text-gray-800 dark:text-gray-300'}">${isUser ? 'User' : 'Saathi AI'}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">${timestamp}</span>
                                </div>
                                <p class="text-gray-700 dark:text-gray-200 whitespace-pre-wrap">${msg.message}</p>
                            </div>
                        `;
                    }).join('');
                })
                .catch(err => {
                    content.innerHTML = `<p class="text-red-500 font-bold">Error: ${err.message}</p>`;
                });
        }
        function deleteChat(chatSessionId) {
            // ... (no changes)
            if (!confirm(`Are you sure you want to delete this entire chat session (${chatSessionId})? This cannot be undone.`)) {
                return;
            }
            fetch('api/admin_api.php?action=delete_chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ chat_session_id: chatSessionId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Chat session deleted successfully.');
                    loadChatLogs();
                } else {
                    throw new Error(data.error || 'Failed to delete chat');
                }
            })
            .catch(err => {
                console.error('Error deleting chat:', err);
                showToast(`Error: ${err.message}`, true);
            });
        }
        function deleteAllOldLogs() {
            // ... (no changes)
            if (!confirm(`Are you sure you want to delete all chat logs older than 30 days? This cannot be undone.`)) {
                return;
            }
            fetch('api/admin_api.php?action=delete_old_logs', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(`${data.deleted_count} old chat logs deleted.`);
                    loadChatLogs();
                } else {
                    throw new Error(data.error || 'Failed to delete old logs');
                }
            })
            .catch(err => {
                console.error('Error deleting old logs:', err);
                showToast(`Error: ${err.message}`, true);
            });
        }
        function setupChatLogModal() {
            // ... (no changes)
            const modal = document.getElementById('chat-view-modal');
            const closeButton = document.getElementById('modal-close-button');
            closeButton.addEventListener('click', () => {
                modal.classList.add('hidden');
            });
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        }

        // --- User Management Functions (no changes) ---
        function loadUserManagement() {
            // ... (no changes)
            const tableBody = document.getElementById('user-list-table');
            fetch('api/admin_api.php?action=get_users')
                .then(response => response.json())
                .then(users => {
                    if (users.error) throw new Error(users.error);
                    if (users.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No users found.</td></tr>`;
                        return;
                    }
                    tableBody.innerHTML = users.map(user => {
                        const isVerified = user.is_verified || 0;
                        const statusClass = isVerified == 1 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                        const statusText = isVerified == 1 ? 'Verified' : 'Not Verified';
                        return `
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${user.name}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${user.email}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${user.role}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                                        ${statusText}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button onclick="changeUserRole('${user.id}', '${user.email}', '${user.role}')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200" title="Change Role">Roles</button>
                                    <button onclick="resetUserPassword('${user.id}', '${user.email}')" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-200" title="Reset Password">Reset Pass</button>
                                    <button onclick="deleteUser('${user.id}', '${user.email}')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200" title="Delete User">Delete</button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                })
                .catch(err => {
                    console.error('Error loading users:', err);
                    tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Error loading user data.</td></tr>`;
                });
        }
        function deleteUser(userId, userEmail) {
            // ... (no changes)
            if (!confirm(`Are you sure you want to delete the user: ${userEmail}? This cannot be undone.`)) {
                return;
            }
            fetch('api/admin_api.php?action=delete_user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('User deleted successfully.');
                    loadUserManagement();
                    updateStats();
                } else {
                    throw new Error(data.error || 'Failed to delete user');
                }
            })
            .catch(err => {
                console.error('Error deleting user:', err);
                showToast(`Error: ${err.message}`, true);
            });
        }
        function changeUserRole(userId, userEmail, currentRole) {
            // ... (no changes)
            const newRole = prompt(`Enter new role for ${userEmail} (e.g., user, premium, developer):`, currentRole);
            if (!newRole || newRole === currentRole) {
                return;
            }
            fetch('api/admin_api.php?action=update_role', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, new_role: newRole })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('User role updated.');
                    loadUserManagement();
                } else {
                    throw new Error(data.error || 'Failed to update role');
                }
            })
            .catch(err => {
                console.error('Error updating role:', err);
                showToast(`Error: ${err.message}`, true);
            });
        }
        function resetUserPassword(userId, userEmail) {
            // ... (no changes)
            const newPassword = prompt(`Enter a new temporary password for ${userEmail}:`);
            if (!newPassword) {
                showToast('Password reset cancelled.');
                return;
            }
            if (newPassword.length < 6) {
                showToast('Password must be at least 6 characters.', true);
                return;
            }
            fetch('api/admin_api.php?action=reset_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, new_password: newPassword })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(`Password for ${userEmail} has been reset.`);
                } else {
                    throw new Error(data.error || 'Failed to reset password');
                }
            })
            .catch(err => {
                console.error('Error resetting password:', err);
                showToast(`Error: ${err.message}`, true);
            });
        }
        
        // *** NEW: SECURITY SETTINGS FUNCTIONS ***
        function loadSecuritySettings() {
            fetch('api/admin_api.php?action=get_app_settings')
                .then(response => response.json())
                .then(settings => {
                    if (settings.error) {
                        throw new Error(settings.error);
                    }
                    document.getElementById('setting-rate-limit').value = settings.rate_limit || 30;
                    document.getElementById('setting-banned-keywords').value = settings.banned_keywords || '';
                })
                .catch(err => {
                    console.error('Error loading settings:', err);
                    showToast('Could not load app settings.', true);
                });
        }

        function setupSettingsForm() {
            const form = document.getElementById('security-settings-form');
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const rateLimit = document.getElementById('setting-rate-limit').value;
                const bannedKeywords = document.getElementById('setting-banned-keywords').value;

                fetch('api/admin_api.php?action=save_app_settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        rate_limit: rateLimit,
                        banned_keywords: bannedKeywords
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Settings saved successfully.');
                    } else {
                        throw new Error(data.error || 'Failed to save settings');
                    }
                })
                .catch(err => {
                    console.error('Error saving settings:', err);
                    showToast(`Error: ${err.message}`, true);
                });
            });
        }
        // *** END OF NEW FUNCTIONS ***


        function showToast(message, isError = false) {
            // ... (no changes)
            const toast = document.getElementById('toast-notification');
            const toastMessage = document.getElementById('toast-message');
            toastMessage.textContent = message;
            toast.classList.remove('bg-red-600', 'dark:bg-red-500', 'text-white', 'bg-gray-900', 'dark:bg-white', 'text-white', 'dark:text-gray-900');
            if (isError) {
                toast.classList.add('bg-red-600', 'dark:bg-red-500', 'text-white');
            } else {
                toast.classList.add('bg-gray-900', 'dark:bg-white', 'text-white', 'dark:text-gray-900');
            }
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }

        // --- Theme and Logout Functions (no changes) ---
        function setupThemeToggle() {
            // ... (no changes)
            const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
            const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                themeToggleLightIcon.classList.remove('hidden');
                document.documentElement.classList.add('dark');
            } else {
                themeToggleDarkIcon.classList.remove('hidden');
                document.documentElement.classList.remove('dark');
            }
            const themeToggleBtn = document.getElementById('theme-toggle');
            themeToggleBtn.addEventListener('click', function() {
                themeToggleDarkIcon.classList.toggle('hidden');
                themeToggleLightIcon.classList.toggle('hidden');
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                }
            });
        }
        function setupLogout() {
            // ... (no changes)
            document.getElementById('logout-button').addEventListener('click', () => {
                fetch('api/logout.php')
                    .then(() => {
                        sessionStorage.removeItem('loggedInUser');
                        window.location.href = 'login.html';
                    });
            });
        }    

        // Auto-refresh stats and logs every 30 seconds
        setInterval(() => {
            updateStats();
            loadActivityLog();
            loadFeedback();
            loadUserManagement();
            loadChatLogs();
            // Note: We don't auto-refresh the settings, as the admin might be editing them.
        }, 30000);
    </script>
</body>
</html>