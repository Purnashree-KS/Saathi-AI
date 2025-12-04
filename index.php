<?php
session_start();

// If user is not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

// Check if admin user somehow landed on user page
if ($_SESSION['role'] === 'admin') {
    header('Location: admin.php');
    exit;
}

// Inject session data into JavaScript
$user_name_js = json_encode($_SESSION['user_name']);
$user_email_js = json_encode($_SESSION['user_email']);
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saathi AI</title>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/mammoth@1.6.0/mammoth.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/azure-storage/azure-storage-js@latest/azure-storage.blob.min.js"></script>
    <!-- Tesseract.js for on-device OCR extraction -->
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@2.1.5/dist/tesseract.min.js"></script>
    <script src="https://unpkg.com/mammoth@1.6.0/mammoth.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/azure-storage/azure-storage-js@latest/azure-storage.blob.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@2.1.5/dist/tesseract.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        .dark ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background: #4b5563; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #6b7280; }
        .typing-indicator span {
            height: 8px; width: 8px; background-color: #4b5563; border-radius: 50%;
            display: inline-block; animation: bounce 1.4s infinite ease-in-out both;
        }
        .dark .typing-indicator span { background-color: #9ca3af; }
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        @keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1.0); } }
        .voice-active { color: #ef4444; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.2); opacity: 0.7; } }
        .chat-history-item.active { background-color: #e2e8f0; }
        .dark .chat-history-item.active { background-color: #374151; }
          /* Dropdown visibility is controlled by the Tailwind 'hidden' class and JS toggles.
              Remove legacy CSS rules that forced these menus to always be hidden. */
        #toast-notification {
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
            transform: translateY(100%);
            opacity: 0;
        }
        #toast-notification.show {
            transform: translateY(0);
            opacity: 1;
        }
    </style>
</head>
<body class="bg-white text-gray-800 dark:bg-gray-900 dark:text-white flex h-screen overflow-hidden transition-colors duration-300">

    <!-- Sidebar -->
    <aside class="w-1/5 bg-gray-50 dark:bg-gray-950 p-4 flex flex-col min-w-[250px] border-r border-gray-200 dark:border-gray-800">
        <div class="flex-shrink-0 mb-6">
            <button id="new-chat-button" class="w-full bg-blue-600 hover:bg-blue-700 transition-colors duration-200 text-white font-semibold py-2 px-4 rounded-lg flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                New Chat
            </button>
        </div>
        <div class="flex-1 overflow-y-auto pr-2">
            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3">Chat History</h2>
            <nav id="chat-history-list" class="space-y-1">
                <!-- Chat history will be dynamically added here -->
            </nav>
        </div>
        <!-- User Profile -->
<div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800 mt-4">
    <div id="profile-button" class="flex items-center w-full cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-2">
        <div id="sidebar-avatar-initial" class="w-8 h-8 rounded-full mr-3 flex items-center justify-center bg-purple-600 text-white font-bold text-lg">U</div>
        <span id="sidebar-username" class="font-semibold text-gray-800 dark:text-white">User</span>
    </div>
</div>

<!-- Profile Modal -->
<div id="profile-dropdown" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-80 transform transition-all ease-in-out duration-300 scale-95 opacity-0">
        <!-- Profile Header -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-full bg-purple-600 flex items-center justify-center">
                    <span id="modal-avatar-initial" class="text-2xl font-bold text-white"></span>
                </div>
                <div>
                    <h3 id="modal-username" class="text-xl font-semibold text-gray-800 dark:text-white"></h3>
                </div>
            </div>
        </div>
        <!-- Actions -->
        <div class="p-4 space-y-3">
            <button id="profile-logout-btn" class="w-full flex items-center space-x-3 px-4 py-3 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Logout</span>
            </button>
            <button id="profile-close-btn" class="w-full flex items-center space-x-3 px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>Close</span>
            </button>
        </div>
    </div>
</div>


    </aside>

    <!-- Main Chat Area -->
    <main class="flex-1 flex flex-col bg-white dark:bg-gray-900">
        <header class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex-shrink-0">
            <h1 id="chat-title" class="text-xl font-bold text-gray-900 dark:text-white">New Chat</h1>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <select id="language-select" class="bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md py-1 px-2 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="en-US">English (US)</option>
                        <option value="en-GB">English (UK)</option>
                        <option value="hi-IN">हिन्दी (Hindi)</option>
                        <option value="kn-IN">ಕನ್ನಡ (Kannada)</option>
                        <option value="te-IN">తెలుగు (Telugu)</option>
                        <option value="ml-IN">മലയാളം (Malayalam)</option>
                        <option value="mr-IN">मराठी (Marathi)</option>
                        <option value="es-ES">Español (España)</option>
                        <option value="fr-FR">Français</option>
                        <option value="de-DE">Deutsch</option>
                        <option value="ja-JP">日本語</option>
                        <option value="ko-KR">한국어</option>
                    </select>
                </div>
                 <button id="theme-toggle-button" class="p-2 rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-200" title="Toggle Dark Mode">
                     <svg id="theme-icon-sun" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                     <svg id="theme-icon-moon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                 </button>
               <!-- More (three-dots) -->
<div class="relative" id="more-wrapper">
  <button id="more-btn" aria-haspopup="true" aria-expanded="false" title="More Options"
          class="p-2 rounded-full hover:bg-gray-800/60 focus:outline-none">
    <!-- three dots icon -->
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-200" viewBox="0 0 24 24" fill="currentColor">
      <circle cx="5" cy="12" r="1.75"></circle>
      <circle cx="12" cy="12" r="1.75"></circle>
      <circle cx="19" cy="12" r="1.75"></circle>
    </svg>
  </button>

  <!-- Dropdown -->
  <div id="more-menu"
       class="hidden absolute right-0 mt-2 w-56 rounded-md border border-gray-700 bg-[#14181f] shadow-lg z-50"
       role="menu" aria-labelledby="more-btn">
    <a href="#" id="menu-settings"
       class="block px-4 py-2 text-sm text-gray-200 hover:bg-gray-800/70" role="menuitem">
      Settings
    </a>
    <a href="#" id="menu-clear"
       class="block px-4 py-2 text-sm text-red-400 hover:bg-red-600 hover:text-white" role="menuitem">
      Clear Conversation History
    </a>
  </div>
</div>
  
        </header>
        <div id="chat-messages" class="flex-1 p-6 space-y-6 overflow-y-auto">
            <!-- Messages will be dynamically added here -->
        </div>
        <div class="p-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex-shrink-0">
            <div class="relative">
                <textarea id="chat-input" class="w-full bg-gray-100 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-lg py-3 pr-28 pl-20 text-gray-800 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:border-blue-500 transition-colors duration-200 resize-none" placeholder="Type your message or click the mic to speak..." rows="1" style="line-height: 1.5rem;"></textarea>
                <div class="absolute left-4 top-1/2 -translate-y-1/2 flex space-x-2">
                    <button id="voice-button" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white" title="Voice Input">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line></svg>
                    </button>
                    <label class="cursor-pointer text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white" title="Attach file">
                        <input type="file" class="hidden" id="file-input" accept="image/*,.pdf,.doc,.docx,.ppt,.pptx,.txt,.rtf" />
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"></path></svg>
                    </label>
                </div>
                <!-- Attach image -->


<input type="file" id="file-input" accept="image/*" class="hidden">

                <button id="send-button" class="absolute right-4 top-1/2 -translate-y-1/2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg p-2 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
            </div>

            <!-- File Preview Container -->
            <div id="file-preview" class="hidden mt-2 p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div id="file-icon" class="w-8 h-8 flex items-center justify-center">
                            <!-- Icons will be dynamically inserted here -->
                        </div>
                        <div class="flex flex-col">
                            <span id="file-name" class="text-sm text-gray-600 dark:text-gray-400 truncate"></span>
                            <span id="file-size" class="text-xs text-gray-500 dark:text-gray-500"></span>
                        </div>
                    </div>
                    <button id="remove-file" class="text-gray-500 hover:text-red-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <div id="preview-container" class="mt-2">
                    <div id="processing-indicator" class="hidden"></div>
                    <img id="image-preview" class="hidden max-h-48 rounded object-contain mx-auto" />
                    <div id="document-preview" class="hidden mt-2 p-4 bg-white dark:bg-gray-700 rounded-lg">
                        <div id="progress-container" class="mb-4 hidden">
                            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 mb-2">
                                <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <p id="progress-text" class="text-sm text-gray-600 dark:text-gray-400 text-center"></p>
                        </div>
                        <pre id="text-content" class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap"></pre>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Custom Modal -->
    <div id="custom-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-sm">
            <h3 id="modal-title" class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Confirm Action</h3>
            <p id="modal-message" class="text-gray-600 dark:text-gray-300 mb-6">Are you sure?</p>
            <div class="flex justify-end space-x-4">
                <button id="modal-cancel-btn" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-700 rounded-lg font-semibold text-gray-800 dark:text-white">Cancel</button>
                <button id="modal-confirm-btn" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg font-semibold text-white">Confirm</button>
            </div>
        </div>
    </div>
        <!-- Logout Confirm Modal -->
<div id="logout-confirm-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
  <div class="w-full max-w-sm rounded-xl bg-[#141a22] text-gray-200 shadow-xl">
    <div class="px-5 py-4 border-b border-gray-700 flex items-center justify-between">
      <h3 class="text-base font-semibold">Confirm</h3>
      <button id="logout-confirm-close"
              class="px-2 py-1 rounded hover:bg-gray-800">✕</button>
    </div>

    <div class="px-5 py-4 text-sm">
      Do you want to <strong>Logout</strong> of your account?
    </div>

    <div class="px-5 pb-5 pt-2 flex gap-2 justify-end">
      <button id="logout-cancel-btn"
              class="px-3 py-2 rounded-md border border-gray-600 hover:bg-gray-800">
        Close
      </button>
      <button id="logout-yes-btn"
              class="px-3 py-2 rounded-md bg-red-600 hover:bg-red-700 text-white">
        Logout
      </button>
    </div>
  </div>
</div>

    <!-- Settings Modal -->
    <div id="settings-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl p-6 w-full max-w-3xl overflow-y-auto max-h-[80vh]">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Settings</h2>
                <button id="settings-close-btn" class="px-3 py-1 rounded bg-gray-200 dark:bg-gray-800">Close</button>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <!-- Appearance -->
                <section class="p-4 bg-gray-50 dark:bg-gray-800 rounded">
                    <h3 class="font-semibold mb-2 text-gray-800 dark:text-gray-200">Appearance & Display</h3>
                    <div class="space-y-2">
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Theme</span>
                            <select id="setting-theme" class="ml-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded px-2 py-1 text-sm">
                                <option value="light">Light</option>
                                <option value="dark">Dark</option>
                                <option value="system">System</option>
                            </select>
                        </label>

                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Font Size</span>
                            <select id="setting-font-size" class="ml-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded px-2 py-1 text-sm">
                                <option value="small">Small</option>
                                <option value="medium">Medium</option>
                                <option value="large">Large</option>
                            </select>
                        </label>

                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Accent Color</span>
                            <input id="setting-accent" type="color" value="#2563eb" class="ml-4 w-10 h-8 p-0 border-0" />
                        </label>

                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Message Style</span>
                            <select id="setting-message-style" class="ml-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded px-2 py-1 text-sm">
                                <option value="bubbles">Bubbles</option>
                                <option value="blocks">Blocks</option>
                            </select>
                        </label>

                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">High-Contrast Mode</span>
                            <input id="setting-high-contrast" type="checkbox" />
                        </label>
                    </div>
                </section>

                <!-- Privacy & Data -->
                <section class="p-4 bg-gray-50 dark:bg-gray-800 rounded">
                    <h3 class="font-semibold mb-2 text-gray-800 dark:text-gray-200">Privacy & Data</h3>
                    <div class="space-y-2">
                        <button id="setting-clear-history" class="w-full text-left px-3 py-2 bg-red-50 dark:bg-red-900/10 rounded text-red-600 dark:text-red-400">Clear Conversation History</button>
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Turn off Chat History (Incognito)</span>
                            <input id="setting-incognito" type="checkbox" />
                        </label>
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Personalization</span>
                            <input id="setting-personalization" type="checkbox" />
                        </label>
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Allow Model Training</span>
                            <input id="setting-model-training" type="checkbox" />
                        </label>
                        <div class="flex space-x-2">
                            <button id="setting-export-data" class="px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded">Export Data</button>
                            <button id="setting-delete-account" class="px-3 py-2 bg-red-600 text-white rounded">Delete Account</button>
                        </div>
                    </div>
                </section>

                <!-- Behavior & Personality -->
                <section class="p-4 bg-gray-50 dark:bg-gray-800 rounded">
                    <h3 class="font-semibold mb-2 text-gray-800 dark:text-gray-200">Behavior & Personality</h3>
                    <div class="space-y-2">
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Response Tone</span>
                            <select id="setting-tone" class="ml-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded px-2 py-1 text-sm">
                                <option value="helpful">Helpful</option>
                                <option value="formal">Formal</option>
                                <option value="casual">Casual</option>
                                <option value="humorous">Humorous</option>
                            </select>
                        </label>

                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Response Length</span>
                            <select id="setting-length" class="ml-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded px-2 py-1 text-sm">
                                <option value="concise">Concise</option>
                                <option value="detailed">Detailed</option>
                            </select>
                        </label>

                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Interface Language</span>
                            <select id="setting-language-ui" class="ml-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded px-2 py-1 text-sm">
                                <option value="en-US">English (US)</option>
                                <option value="en-GB">English (UK)</option>
                                <option value="es-ES">Español</option>
                            </select>
                        </label>
                    </div>
                </section>

                <!-- Notifications & Accessibility -->
                <section class="p-4 bg-gray-50 dark:bg-gray-800 rounded">
                    <h3 class="font-semibold mb-2 text-gray-800 dark:text-gray-200">Notifications & Accessibility</h3>
                    <div class="space-y-2">
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Push Notifications</span>
                            <input id="setting-notifications" type="checkbox" />
                        </label>
                        <div class="text-sm text-gray-600 dark:text-gray-300">Notification Types</div>
                        <label class="flex items-center space-x-2"><input id="notify-sound" type="checkbox" /> <span class="text-sm text-gray-700 dark:text-gray-300">Sound</span></label>
                        <label class="flex items-center space-x-2"><input id="notify-popup" type="checkbox" /> <span class="text-sm text-gray-700 dark:text-gray-300">Pop-up</span></label>
                        <label class="flex items-center space-x-2"><input id="notify-email" type="checkbox" /> <span class="text-sm text-gray-700 dark:text-gray-300">Email</span></label>

                        <hr class="my-2 border-gray-200 dark:border-gray-700" />

                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Text-to-Speech (TTS)</span>
                            <input id="setting-tts" type="checkbox" />
                        </label>
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Voice Input (microphone)</span>
                            <input id="setting-voice-input" type="checkbox" />
                        </label>
                    </div>
                </section>

                <!-- General -->
                <section class="p-4 bg-gray-50 dark:bg-gray-800 rounded col-span-2">
                    <h3 class="font-semibold mb-2 text-gray-800 dark:text-gray-200">General</h3>
                    <div class="flex items-center space-x-3">
                        <label class="flex items-center space-x-2">
                            <input id="setting-enable-claude" type="checkbox" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Enable Claude Sonnet 3.5 for all clients</span>
                        </label>
                        <button id="setting-reset" class="px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded">Reset All Settings</button>
                        <button id="setting-help" class="px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded">Help & FAQ</button>
                        <button id="setting-feedback" class="px-3 py-2 bg-blue-600 text-white rounded">Send Feedback</button>
                        <div class="ml-auto text-sm text-gray-600 dark:text-gray-400">Version 1.0</div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast-notification" class="fixed bottom-5 right-5 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg">
        Chat copied to clipboard!
    </div>

    <script>
        // Check if user is logged in
        //local Storage

        const API_KEY = "YOUR_GEMINI_API_KEY"; // Replace with your actual key

        // --- DOM Elements ---
        
        const chatMessages = document.getElementById('chat-messages');
        const chatInput = document.getElementById('chat-input');
        const sendButton = document.getElementById('send-button');
        const voiceButton = document.getElementById('voice-button');
        const newChatButton = document.getElementById('new-chat-button');
        const chatHistoryList = document.getElementById('chat-history-list');
        const chatTitle = document.getElementById('chat-title');
        const languageSelect = document.getElementById('language-select');
        const modal = document.getElementById('custom-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalMessage = document.getElementById('modal-message');
        const modalConfirmBtn = document.getElementById('modal-confirm-btn');
        const modalCancelBtn = document.getElementById('modal-cancel-btn');
        const toast = document.getElementById('toast-notification');
        const themeToggleButton = document.getElementById('theme-toggle-button');
        const themeIconSun = document.getElementById('theme-icon-sun');
        const themeIconMoon = document.getElementById('theme-icon-moon');
        const moreOptionsButton = document.getElementById('more-options-button');
        const moreOptionsMenu = document.getElementById('more-options-menu');
        const clearHistoryButton = document.getElementById('clear-history-button');

        // --- State Management ---
        let chatHistory = [];
        let currentChatId = null;
    let pendingBotMsgIndex = null;
        let currentLang = 'en-US';

        // Return the current logged-in user's email (prefers sessionStorage then localStorage)
        //function getCurrentUserEmail() {
            

        //function getHistoryKey() {
            

        // --- UI Helper Functions ---
        function showToast(message) {
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        // Return the first uppercase initial of the stored username or 'U'
        //function getUsernameInitial() {
            

        function showModal(title, message, onConfirm) {
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            const newConfirmBtn = modalConfirmBtn.cloneNode(true);
            modalConfirmBtn.parentNode.replaceChild(newConfirmBtn, modalConfirmBtn);
            newConfirmBtn.onclick = () => { onConfirm(); hideModal(); };
            modalCancelBtn.onclick = hideModal;
        }

        function hideModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // --- Chat Management Functions ---
        function shareChat(chatId) {
            const chat = chatHistory.find(c => c.id === chatId);
            if (!chat) return;
            let chatContent = `Chat History: ${chat.title}\n\n`;
            chat.messages.forEach(msg => {
                const prefix = msg.sender === 'user' ? 'You' : 'AI Assistant';
                chatContent += `${prefix}: ${msg.text}\n`;
            });
            navigator.clipboard.writeText(chatContent).then(() => {
                showToast('Chat copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy chat: ', err);
                showToast('Failed to copy chat.');
            });
        }

        function deleteChat(chatId) {
             showModal('Delete Chat', 'Are you sure you want to delete this chat permanently?', () => {
                chatHistory = chatHistory.filter(c => c.id !== chatId);
                saveChatHistory();
                if (currentChatId === chatId) {
                    if (chatHistory.length > 0) { loadChat(chatHistory[0].id); } 
                    else { startNewChat(); }
                } else { renderChatHistory(); }
            });
        }
        
        function renameChat(chatId) {
            const chat = chatHistory.find(c => c.id === chatId);
            const newTitle = prompt('Enter new chat title:', chat.title);
            if (newTitle && newTitle.trim() !== '') {
                chat.title = newTitle.trim();
                saveChatHistory();
                renderChatHistory();
                if (chat.id === currentChatId) { chatTitle.textContent = chat.title; }
            }
        }

        //function clearAllHistory() {
         function clearAllHistory() {
            showModal('Clear All History', 'Are you sure you want to permanently delete your entire chat history?', () => {
                fetch('api/chat_api.php?action=clear_history', { method: 'POST' })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            chatHistory = [];
                            startNewChat(); // This will show the welcome message
                            showToast('Chat history has been cleared');
                        } else {
                            showToast('Error clearing history: ' + data.message);
                        }
                    })
                    .catch(err => showToast('A network error occurred.'));
            });
        }   
        // --- Chat History UI ---
        //function saveChatHistory() {
            
        //function loadChatHistory() {
        function saveChatHistory() {
            try {
                // ... (your incognito check is good, keep it) ...

                if (!chatHistory || !Array.isArray(chatHistory)) {
                    return;
                }

                // Create a deep copy and strip image data
                const historyToSave = JSON.parse(JSON.stringify(chatHistory));
                historyToSave.forEach(chat => {
                    chat.messages.forEach(msg => {
                        // --- START MODIFICATION ---
                        if (msg.attachment) {
                            // Always delete 'data' (base64) before saving
                            if (msg.attachment.data) {
                                delete msg.attachment.data;
                            }
                            // Also remove the 'dataStripped' flag if it exists
                            if (msg.attachment.dataStripped) {
                                delete msg.attachment.dataStripped;
                            }
                            // 'msg.attachment.url' will be KEPT, which is what we want
                        }
                        // --- END MODIFICATION ---
                    });
                });

                // NEW: Send to server instead of localStorage
                fetch('api/chat_api.php?action=save_history', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(historyToSave) // Send the copy with URL, without base64
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to save chat history to server:', data.message);
                    }
                })
                .catch(err => console.error('Error saving chat history:', err));
                
            } catch (e) { 
                console.error('Error in saveChatHistory:', e); 
            }
        }
        
        async function loadChatHistory() {
            try {
                // NEW: Load from server
                const response = await fetch('api/chat_api.php?action=load_history');
                if (!response.ok) {
                    throw new Error('Failed to fetch history');
                }
                
                chatHistory = await response.json();
                
                if (!Array.isArray(chatHistory)) {
                    chatHistory = [];
                }
                
                renderChatHistory();
                if (chatHistory.length > 0) { 
                    loadChat(chatHistory[0].id); 
                } else { 
                    startNewChat(); 
                }
            } catch (e) {
                console.error('Error loading chat history:', e);
                chatHistory = [];
                startNewChat();
            }
        }    
        
        function renderChatHistory() {
            chatHistoryList.innerHTML = '';
            chatHistory.forEach(chat => {
                const container = document.createElement('div');
                container.classList.add('relative', 'rounded-lg', 'group', 'history-item-container');
                const chatLink = document.createElement('a');
                chatLink.href = '#';
                chatLink.textContent = chat.title;
                chatLink.classList.add('block', 'w-full', 'p-2', 'pr-10', 'rounded-lg', 'hover:bg-gray-100', 'dark:hover:bg-gray-800', 'transition-colors', 'duration-200', 'truncate', 'chat-history-item', 'text-gray-900', 'dark:text-gray-300');
                chatLink.dataset.chatId = chat.id;
                if (chat.id === currentChatId) { container.classList.add('active'); }
                chatLink.addEventListener('click', (e) => { e.preventDefault(); loadChat(chat.id); });

                const menuButton = document.createElement('button');
                menuButton.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>`;
                // Note: do NOT add a legacy 'history-menu' class here — visibility is controlled
                // via the dropdown-menu element and the 'hidden' class. Keep the button simple.
                menuButton.classList.add('absolute', 'right-2', 'top-1/2', '-translate-y-1/2', 'p-1', 'rounded-full', 'text-gray-600', 'dark:text-gray-400', 'hover:bg-gray-200', 'dark:hover:bg-gray-700');
                
                const menuDropdown = document.createElement('div');
                menuDropdown.classList.add('absolute', 'right-0', 'top-full', 'mt-1', 'w-32', 'bg-white', 'dark:bg-gray-950', 'border', 'border-gray-200', 'dark:border-gray-700', 'rounded-lg', 'shadow-lg', 'z-10', 'hidden', 'dropdown-menu');
                menuDropdown.innerHTML = `
                    <button class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800">Rename</button>
                    <button class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800">Share</button>
                    <button class="w-full text-left px-3 py-2 text-sm text-red-500 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-800">Delete</button>`;

                menuButton.addEventListener('click', (e) => {
                    e.stopPropagation();
                    document.querySelectorAll('.dropdown-menu').forEach(m => { if (m !== menuDropdown) m.classList.add('hidden'); });
                    menuDropdown.classList.toggle('hidden');
                });
                
                menuDropdown.children[0].addEventListener('click', () => { renameChat(chat.id); menuDropdown.classList.add('hidden'); });
                menuDropdown.children[1].addEventListener('click', () => { shareChat(chat.id); menuDropdown.classList.add('hidden'); });
                menuDropdown.children[2].addEventListener('click', () => { deleteChat(chat.id); menuDropdown.classList.add('hidden'); });
                
                container.append(chatLink, menuButton, menuDropdown);
                chatHistoryList.appendChild(container);
            });
        }
        
        // Close dropdowns when clicking outside. Use bubble-phase listener so
        // clicks on menu buttons (which may call stopPropagation) are allowed to run first.
        document.body.addEventListener('click', (e) => {
            // If the click happened inside an open dropdown, a menu button, or the more-options button, do nothing
            if (e.target.closest('.dropdown-menu') || e.target.closest('.history-item-container') || e.target.closest('#more-options-button')) return;
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        }, false);

        function startNewChat() {
            const newChat = {
                id: Date.now().toString(),
                title: 'New Chat',
                messages: [{ sender: 'bot', text: "Hello! I'm Saathi , your AI assistant. How can I help you today?", model: 'System' }]
            };
            chatHistory.unshift(newChat);
            currentChatId = newChat.id;
            saveChatHistory();
            loadChat(currentChatId);
        }

        function loadChat(chatId) {
    currentChatId = chatId;
    const chat = chatHistory.find(c => c.id === chatId);
    if (!chat) { startNewChat(); return; }
    
    chatMessages.innerHTML = '';
    
    // Render all messages
    chat.messages.forEach((msg, idx) => {
        addMessageToDOM(msg.text, msg.sender, msg.model, msg.text === '', idx, msg.attachment);
    });
    
    chatTitle.textContent = chat.title;
    renderChatHistory();

    // --- NEW: Restore Suggestions ---
    // Check if the very last message has saved suggestions
    if (chat.messages.length > 0) {
        const lastMsg = chat.messages[chat.messages.length - 1];
        if (lastMsg.suggestions && Array.isArray(lastMsg.suggestions) && lastMsg.suggestions.length > 0) {
            renderSuggestions(lastMsg.suggestions);
        }
    }
}

        // --- File Handling ---
        let currentFile = null;
        const fileInput = document.getElementById('file-input');
        const filePreview = document.getElementById('file-preview');
        const fileName = document.getElementById('file-name');
        const fileSize = document.getElementById('file-size');
        const imagePreview = document.getElementById('image-preview');
        const removeFile = document.getElementById('remove-file');

        // File type icons for different file types
        const fileIcons = {
            'image': '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
            'pdf': '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
            'doc': '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
            'txt': '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
            'ppt': '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 13v-1m4 1v-3m4 3V8M12 20.5c4.142 0 7.5-3.358 7.5-7.5S16.142 5.5 12 5.5 4.5 8.858 4.5 13s3.358 7.5 7.5 7.5z"/></svg>'
        };

        // Function to process different types of files
        async function initializeFileProcessingUI() {
            // Create or get the preview container
            const previewContainer = createElementIfNotExists('preview-container', 'file-preview', 'mt-2');
            
            // Create or get the processing indicator
            const processingIndicator = createElementIfNotExists('processing-indicator', 'preview-container', 'hidden');
            
            // Create or get the image preview
            const imagePreview = createElementIfNotExists('image-preview', 'preview-container', 'hidden max-h-48 rounded object-contain mx-auto');
            imagePreview.tagName !== 'IMG' && (imagePreview.outerHTML = '<img id="image-preview" class="hidden max-h-48 rounded object-contain mx-auto" />');
            
            // Create or get the document preview
            const documentPreview = createElementIfNotExists('document-preview', 'preview-container', 'hidden mt-2 p-4 bg-white dark:bg-gray-700 rounded-lg');
            
            // Create or get the progress container
            const progressContainer = createElementIfNotExists('progress-container', 'document-preview', 'mb-4 hidden');
            progressContainer.innerHTML = `
                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 mb-2">
                    <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p id="progress-text" class="text-sm text-gray-600 dark:text-gray-400 text-center"></p>
            `;
            
            // Create or get the text content container
            const textContent = createElementIfNotExists('text-content', 'document-preview', 'text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap');

            return {
                previewContainer,
                processingIndicator,
                imagePreview,
                documentPreview,
                progressContainer,
                textContent
            };
        }

        async function updateProgress(progress, message) {
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const progressContainer = document.getElementById('progress-container');

            if (progressContainer) progressContainer.classList.remove('hidden');
            if (progressBar) progressBar.style.width = `${progress}%`;
            if (progressText) progressText.textContent = message;
        }

        async function processFile(file) {
            // Initialize UI elements
            const ui = await initializeFileProcessingUI();
            let extractedText = '';
            let fileIcon = '';
            
            // Determine file type and set icon
            if (file.type.startsWith('image/')) {
                fileIcon = fileIcons.image;
            } else if (file.type === 'application/pdf') {
                fileIcon = fileIcons.pdf;
            } else if (file.type.includes('word')) {
                fileIcon = fileIcons.doc;
            } else if (file.type.includes('powerpoint')) {
                fileIcon = fileIcons.ppt;
            } else {
                fileIcon = fileIcons.txt;
            }
            document.getElementById('file-icon').innerHTML = fileIcon;

            // Process different file types
            try {
                // Show initial processing state
                ui.progressContainer.classList.remove('hidden');
                await updateProgress(0, 'Starting file analysis...');

                if (file.type.startsWith('image/')) {
                    // Handle images
                    const imageUrl = await readFileAsDataURL(file);
                    if (ui.imagePreview instanceof HTMLImageElement) {
                        ui.imagePreview.src = imageUrl;
                        ui.imagePreview.classList.remove('hidden');
                    }
                    ui.documentPreview.classList.add('hidden');

                    // Analyze image content
                    await updateProgress(20, 'Analyzing image content...');
                    extractedText = await analyzePicture(file);
                } else if (file.type === 'application/pdf') {
                    // Handle PDFs
                    ui.documentPreview.classList.remove('hidden');
                    ui.imagePreview.classList.add('hidden');
                    await updateProgress(20, 'Processing PDF document...');
                    extractedText = await extractPdfText(file);
                } else if (file.type.includes('word')) {
                    // Handle Word documents
                    ui.documentPreview.classList.remove('hidden');
                    ui.imagePreview.classList.add('hidden');
                    await updateProgress(20, 'Processing Word document...');
                    extractedText = await extractWordText(file);
                } else if (file.type.includes('powerpoint')) {
                    // Handle PowerPoint files
                    ui.documentPreview.classList.remove('hidden');
                    ui.imagePreview.classList.add('hidden');
                    await updateProgress(20, 'Processing PowerPoint presentation...');
                    extractedText = await extractPowerPointText(file);
                } else {
                    // Handle text files
                    ui.documentPreview.classList.remove('hidden');
                    ui.imagePreview.classList.add('hidden');
                    await updateProgress(20, 'Reading text file...');
                    extractedText = await readFileAsText(file);
                }

                // Update progress to complete
                await updateProgress(100, 'Processing complete!');

                // Display extracted text in preview with full content
                if (extractedText && ui.textContent) {
                    try {
                        // Show full content in the preview with scrolling
                        ui.textContent.textContent = extractedText;
                        
                        // Ensure the container is scrollable for long content
                        const documentPreview = document.getElementById('document-preview');
                        if (documentPreview) {
                            documentPreview.style.maxHeight = '400px'; // Set maximum height
                            documentPreview.style.overflowY = 'auto';  // Enable vertical scrolling
                        }
                        
                        const chatInputElement = document.getElementById('chat-input');
                        if (chatInputElement instanceof HTMLTextAreaElement) {
                            // Store full content in a hidden input for reference
                            const hiddenInput = createElementIfNotExists('full-content-input', 'chat-messages', 'hidden');
                            hiddenInput.value = extractedText;
                            
                            // Set up the chat input with the full content
                            chatInputElement.value = "Please analyze the following content from my uploaded file:\n\n" + extractedText;
                            
                            // Adjust textarea height to show more content
                            chatInputElement.style.height = 'auto';
                            chatInputElement.style.height = Math.min(chatInputElement.scrollHeight, 300) + 'px';
                        }
                        
                        // Add a "Copy full text" button
                        const copyButton = document.createElement('button');
                        copyButton.className = 'mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm';
                        copyButton.textContent = 'Copy full text';
                        copyButton.onclick = () => {
                            navigator.clipboard.writeText(extractedText)
                                .then(() => showToast('Full text copied to clipboard'))
                                .catch(err => showToast('Failed to copy text: ' + err.message));
                        };
                        ui.textContent.parentElement.appendChild(copyButton);
                        
                    } catch (err) {
                        console.error('Error updating display:', err);
                        showToast('Error displaying content: ' + err.message);
                    }
                }

                // Hide progress after a short delay
                setTimeout(() => {
                    const progressContainer = document.getElementById('progress-container');
                    if (progressContainer) {
                        progressContainer.classList.add('hidden');
                    }
                }, 2000);

                return extractedText;
            } catch (error) {
                console.error('Error processing file:', error);
                await updateProgress(100, 'Error: ' + error.message);
                showToast('Error processing file: ' + error.message);
                return null;
            }
        }

        // File reading utilities
        function readFileAsDataURL(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = e => resolve(e.target.result);
                reader.onerror = e => reject(e);
                reader.readAsDataURL(file);
            });
        }

        // Fixed Code
        function readFileAsText(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = e => resolve(e.target.result);
                reader.onerror = e => reject(e);
                // Explicitly read as UTF-8 to prevent encoding errors
                reader.readAsText(file, 'UTF-8'); 
            });
        }

        async function analyzePicture(file) {
            const imageUrl = await readFileAsDataURL(file);
            
            // Ensure text content element exists
            const progressIndicator = createElementIfNotExists('text-content', 'document-preview', 'text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap');

            // helper: create image element and wait load
            const loadImage = (src) => new Promise((resolve, reject) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = src;
            });

            // Simple edge-density detector to guess if image is a diagram
            const detectDiagram = async (img) => {
                try {
                    const maxDim = 800; // downscale for performance
                    const ratio = Math.min(1, maxDim / Math.max(img.width, img.height));
                    const w = Math.max(100, Math.round(img.width * ratio));
                    const h = Math.max(100, Math.round(img.height * ratio));
                    const canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, w, h);
                    const data = ctx.getImageData(0, 0, w, h).data;

                    // compute simple gradient magnitude (Sobel-like) to detect many straight lines
                    const getGray = (x,y) => {
                        const i = (y * w + x) * 4;
                        return (data[i] + data[i+1] + data[i+2]) / 3;
                    };
                    let edgeCount = 0;
                    let total = 0;
                    for (let y = 1; y < h-1; y += 2) {
                        for (let x = 1; x < w-1; x += 2) {
                            const gx = -getGray(x-1,y-1) -2*getGray(x-1,y) - getGray(x-1,y+1)
                                     + getGray(x+1,y-1) +2*getGray(x+1,y) + getGray(x+1,y+1);
                            const gy = -getGray(x-1,y-1) -2*getGray(x,y-1) - getGray(x+1,y-1)
                                     + getGray(x-1,y+1) +2*getGray(x,y+1) + getGray(x+1,y+1);
                            const mag = Math.sqrt(gx*gx + gy*gy);
                            if (mag > 60) edgeCount++;
                            total++;
                        }
                    }
                    const lineDensity = total ? (edgeCount / total) : 0;
                    return { isDiagram: lineDensity > 0.06, lineDensity, width: img.width, height: img.height };
                } catch (e) { console.warn('Diagram detection failed', e); return { isDiagram: false, lineDensity: 0, width: 0, height: 0 }; }
            };

            try {
                // Create progress bar if it doesn't exist
                if (!document.getElementById('progress-bar')) {
                    const progressBarContainer = document.createElement('div');
                    progressBarContainer.className = 'w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 overflow-hidden';
                    progressBarContainer.innerHTML = '<div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>';
                    progressIndicator.parentElement.insertBefore(progressBarContainer, progressIndicator);
                }

                progressIndicator.innerHTML = `
                    <div class="flex flex-col items-center space-y-2">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Analyzing image...</p>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 overflow-hidden">
                            <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>`;

                const progressBar = document.getElementById('progress-bar');
                const updateProgress = (status, progress) => {
                    if (progressBar) {
                        progressBar.style.width = `${Math.round(progress * 100)}%`;
                        if (progressIndicator.querySelector('p')) {
                            progressIndicator.querySelector('p').textContent = `${status}... ${Math.round(progress * 100)}%`;
                        }
                    }
                };

                // Load image and run a cheap diagram detector
                const img = await loadImage(imageUrl);
                updateProgress('Inspecting image structure', 0.05);
                const diagInfo = await detectDiagram(img);
                updateProgress('Running OCR', 0.1);

                // Run OCR (primary pass)
                let result = await Tesseract.recognize(imageUrl, 'eng', {
                    logger: m => updateProgress(m.status, m.progress)
                });

                // If OCR found very little text but diagram detector indicates diagram
                // try alternative OCR passes and allow hOCR for bounding boxes
                if ((!result || !result.data || (result.data.text || '').trim().length < 20) && diagInfo.isDiagram) {
                    updateProgress('Performing diagram-focused OCR', 0.35);
                    try {
                        result = await Tesseract.recognize(imageUrl, 'eng+osd', {
                            logger: m => updateProgress(m.status, m.progress),
                            tessedit_pageseg_mode: '1'
                        });
                    } catch (e) { console.warn('Secondary OCR failed', e); }
                }

                // Build description: OCR text, diagram detection and keywords-based summary
                let description = '';
                const ocrText = (result && result.data && result.data.text) ? result.data.text.trim() : '';
                const cleaned = ocrText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
                if (cleaned) description += 'Extracted Text:\n' + cleaned + '\n\n';

                // Use OCR word positions (if available) to extract top title and label list
                const words = (result && result.data && result.data.words) ? result.data.words : [];
                let title = '';
                try {
                    if (words && words.length) {
                        // compute top-most y using bbox (defensive checks)
                        const getY = w => (w.bbox && typeof w.bbox.y0 === 'number') ? w.bbox.y0 : (w.y || w.y0 || 0);
                        const sorted = words.slice().sort((a,b) => getY(a) - getY(b));
                        const topY = getY(sorted[0]);
                        const topWords = words.filter(w => getY(w) <= topY + Math.max(10, Math.round((result.data.height || img.height) * 0.05)));
                        title = topWords.map(w => w.text).join(' ').replace(/\s+/g,' ').trim();
                    }
                } catch (e) { console.warn('Title extraction failed', e); }

                // keyword-based detection
                const textLower = cleaned.toLowerCase();
                const keywords = ['provider', 'backbone', 'peering', 'customer', 'network', 'internet', 'structure', 'figure', 'router', 'switch', 'peering point', 'backbones'];
                const foundKeywords = keywords.filter(k => textLower.includes(k));

                // Build diagram summary when diagram-like image or keywords found
                if (diagInfo.isDiagram || foundKeywords.length > 0) {
                    description += 'Diagram Analysis:\n';
                    if (title) description += `- Title (detected near top): ${title}\n`;
                    description += `- Detected as diagram-like (line density ${Math.round(diagInfo.lineDensity*100)}%)\n`;

                    // infer common network diagram elements if keywords exist
                    if (textLower.includes('backbone')) description += `- Central element: Backbones (core links connecting provider networks)\n`;
                    if (textLower.includes('provider')) description += `- Provider networks: One or more provider networks connected to backbones\n`;
                    if (textLower.includes('customer')) description += `- Customer networks: End networks attached to provider networks\n`;
                    if (textLower.includes('peering')) description += `- Peering points: Locations where providers interconnect\n`;

                    // create a short human-friendly summary from keywords
                    if (textLower.includes('internet') || textLower.includes('network')) {
                        description += '\nSummary:\nThe image appears to be a network/Internet-structure diagram illustrating how multiple provider networks connect via backbones and peering points, with customer networks attached to providers. It likely shows hierarchical interconnection and points where traffic exchanges occur.';
                    }
                } else if (cleaned.length > 0) {
                    // general scanned page
                    description += 'Document/Image Analysis:\n';
                    description += `- Looks like a scanned page or photo with text content.\n`;
                    description += `- OCR found approximately ${cleaned.split(' ').length} words.\n`;
                } else {
                    description += 'Image Analysis:\n- No significant text detected. Detected as ' + (diagInfo.isDiagram ? 'diagram-like' : 'image/photo') + '.\n';
                }

                // Add image metrics
                description += `\nImage Metrics:\n- Size: ${(file.size/1024).toFixed(1)} KB\n- Dimensions: ${diagInfo.width}x${diagInfo.height}\n`;
                if (result && result.data && typeof result.data.confidence === 'number') description += `- OCR Confidence (approx): ${Math.round(result.data.confidence)}%\n`;

                return description;
            } catch (error) {
                console.error('Image analysis error:', error);
                if (progressIndicator) progressIndicator.innerHTML = `<div class="text-red-500 dark:text-red-400">Error analyzing image: ${error.message}</div>`;
                throw new Error('Failed to analyze image: ' + (error && error.message ? error.message : 'unknown'));
            }
        }

        async function extractPdfText(file) {
            // Ensure text content element exists
            const progressIndicator = createElementIfNotExists('text-content', 'document-preview', 'text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap');
            
            // Create progress bar if it doesn't exist
            if (!document.getElementById('pdf-progress')) {
                const progressBarContainer = document.createElement('div');
                progressBarContainer.className = 'w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700';
                progressBarContainer.innerHTML = '<div id="pdf-progress" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>';
                progressIndicator.parentElement.insertBefore(progressBarContainer, progressIndicator);
            }

            progressIndicator.innerHTML = `
                <div class="flex flex-col items-center space-y-2">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Loading PDF...</p>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div id="pdf-progress" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>`;

            try {
                const arrayBuffer = await file.arrayBuffer();
                const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                let fullText = `PDF Analysis:\nTotal Pages: ${pdf.numPages}\n\nContent:\n`;
                const progressBar = document.getElementById('pdf-progress');
                
                // Enhanced PDF processing
                for (let i = 1; i <= pdf.numPages; i++) {
                    // Update progress
                    const progress = Math.round((i / pdf.numPages) * 100);
                    progressBar.style.width = `${progress}%`;
                    progressIndicator.querySelector('p').textContent = `Processing page ${i} of ${pdf.numPages}...`;

                    const page = await pdf.getPage(i);
                    
                    // Get text content
                    const textContent = await page.getTextContent();
                    const pageText = textContent.items.map(item => item.str).join(' ');
                    
                    // Get page metadata
                    const viewport = page.getViewport({ scale: 1.0 });
                    
                    fullText += `\n--- Page ${i} ---\n`;
                    fullText += `Dimensions: ${Math.round(viewport.width)}x${Math.round(viewport.height)}\n`;
                    fullText += pageText + '\n';

                    // Extract any images on the page
                    try {
                        const operatorList = await page.getOperatorList();
                        let imageCount = 0;
                        for (let j = 0; j < operatorList.fnArray.length; j++) {
                            if (operatorList.fnArray[j] === pdfjsLib.OPS.paintImageXObject) {
                                imageCount++;
                            }
                        }
                        if (imageCount > 0) {
                            fullText += `[Page ${i} contains ${imageCount} image(s)]\n`;
                        }
                    } catch (e) {
                        console.warn('Could not extract image info:', e);
                    }
                }

                // Add PDF metadata if available
                try {
                    const metadata = await pdf.getMetadata();
                    if (metadata && metadata.info) {
                        fullText += '\nDocument Information:\n';
                        const info = metadata.info;
                        if (info.Title) fullText += `Title: ${info.Title}\n`;
                        if (info.Author) fullText += `Author: ${info.Author}\n`;
                        if (info.Subject) fullText += `Subject: ${info.Subject}\n`;
                        if (info.Keywords) fullText += `Keywords: ${info.Keywords}\n`;
                        if (info.CreationDate) fullText += `Created: ${info.CreationDate}\n`;
                        if (info.ModDate) fullText += `Modified: ${info.ModDate}\n`;
                    }
                } catch (e) {
                    console.warn('Could not extract PDF metadata:', e);
                }

                // Add summary information
                fullText += `\nSummary:\n`;
                fullText += `- Total Pages: ${pdf.numPages}\n`;
                fullText += `- File Size: ${(file.size / 1024).toFixed(1)} KB\n`;
                fullText += `- Format: PDF Document\n`;

                return fullText;

            } catch (error) {
                console.error('PDF extraction error:', error);
                progressIndicator.innerHTML = `
                    <div class="text-red-500 dark:text-red-400">
                        Error processing PDF: ${error.message}
                    </div>`;
                throw new Error('Failed to process PDF');
            }
        }

        async function extractWordText(file) {
            try {
                const arrayBuffer = await file.arrayBuffer();
                const result = await mammoth.extractRawText({ arrayBuffer });
                return 'Word Document Content:\n\n' + result.value;
            } catch (error) {
                console.error('Word document extraction error:', error);
                throw new Error('Failed to extract Word document content');
            }
        }

        async function extractPowerPointText(file) {
            if (typeof JSZip === 'undefined') {
                console.error('JSZip library is not loaded. Please add it to your HTML.');
                throw new Error('PowerPoint processing library (JSZip) is missing.');
            }

            try {
                const arrayBuffer = await file.arrayBuffer();
                const zip = await JSZip.loadAsync(arrayBuffer);
                let fullText = 'PowerPoint Content:\n\n';

                const slideFiles = [];
                // Find all slide XML files
                zip.folder('ppt/slides').forEach((relativePath, fileEntry) => {
                    if (fileEntry.name.endsWith('.xml') && fileEntry.name.startsWith('ppt/slides/slide')) {
                        slideFiles.push(fileEntry);
                    }
                });

                // Sort files by slide number (e.g., slide1.xml, slide2.xml ... slide10.xml)
                slideFiles.sort((a, b) => {
                    const numA = parseInt(a.name.match(/slide(\d+)\.xml/)[1], 10);
                    const numB = parseInt(b.name.match(/slide(\d+)\.xml/)[1], 10);
                    return numA - numB;
                });

                // Use DOMParser to read XML text
                const parser = new DOMParser();

                for (let i = 0; i < slideFiles.length; i++) {
                    const slideFile = slideFiles[i];
                    const xmlText = await slideFile.async('string');
                    
                    // Parse the XML content of the slide
                    const xmlDoc = parser.parseFromString(xmlText, 'application/xml');
                    
                    // Find all <a:t> tags, which contain the text
                    const textNodes = xmlDoc.getElementsByTagName('a:t');
                    let slideText = '';
                    for (let j = 0; j < textNodes.length; j++) {
                        slideText += (textNodes[j].textContent || '') + ' ';
                    }
                    
                    // Clean up extra whitespace
                    slideText = slideText.replace(/\s+/g, ' ').trim();

                    if (slideText) {
                        fullText += `--- Slide ${i + 1} ---\n`;
                        fullText += slideText + '\n\n';
                    }
                }

                if (slideFiles.length === 0) {
                   throw new Error('No text slides found in the PPTX file.');
                }

                return fullText;

            } catch (error) {
                console.error('PowerPoint extraction error:', error);
                throw new Error('Failed to extract PowerPoint content: ' + error.message);
            }
        }

        function createElementIfNotExists(id, parentId, className = '') {
            let element = document.getElementById(id);
            if (!element) {
                element = document.createElement('div');
                element.id = id;
                if (className) element.className = className;
                const parent = document.getElementById(parentId);
                if (parent) parent.appendChild(element);
            }
            return element;
        }

        function showProcessingState(state) {
            // Ensure preview container exists
            const previewContainer = document.getElementById('preview-container');
            if (!previewContainer) return;

            // Ensure document preview exists
            const documentPreview = createElementIfNotExists('document-preview', 'preview-container', 'mt-2 p-4 bg-white dark:bg-gray-700 rounded-lg');
            documentPreview.classList.remove('hidden');

            // Create progress content
            const content = `
                <div class="flex flex-col items-center space-y-4 p-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 text-center">
                        <p class="font-semibold">${state}</p>
                        <p class="text-xs mt-1">This might take a moment depending on the file size...</p>
                    </div>
                </div>
            `;

            try {
                documentPreview.innerHTML = content;
            } catch (error) {
                console.error('Error updating preview:', error);
            }
        }

        fileInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 50 * 1024 * 1024) { // 50MB limit
                showToast('File size must be less than 50MB');
                return;
            }

            currentFile = file; // <-- Store the file for later
            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
            filePreview.classList.remove('hidden');

            // --- This is the new, fast preview part ---
            
            // Clear any old previews
            const ui = await initializeFileProcessingUI();
            ui.imagePreview.classList.add('hidden');
            ui.documentPreview.classList.add('hidden');
            ui.textContent.textContent = '';
            
            let fileIcon = fileIcons.txt; // default

            if (file.type.startsWith('image/')) {
                fileIcon = fileIcons.image;
                // Show image preview immediately
                const imageUrl = await readFileAsDataURL(file);
                if (ui.imagePreview instanceof HTMLImageElement) {
                    ui.imagePreview.src = imageUrl;
                    ui.imagePreview.classList.remove('hidden');
                }
                // Set a simple prompt for the user
                chatInput.value = `[Image attached: ${file.name}] Please analyze this image.`;

            } else {
                // For documents, just show the icon and a simple prompt
                if (file.type === 'application/pdf') {
                    fileIcon = fileIcons.pdf;
                } else if (file.type.includes('word')) {
                    fileIcon = fileIcons.doc;
                } else if (file.type.includes('powerpoint') || file.type.includes('presentationml')) {
                    fileIcon = fileIcons.ppt;
                }
                
                ui.documentPreview.classList.remove('hidden');
                ui.textContent.textContent = `File "${file.name}" is ready to send.`;
                chatInput.value = `[File attached: ${file.name}] Please summarize this document.`;
            }
            
            document.getElementById('file-icon').innerHTML = fileIcon;
            
            // Auto-adjust textarea
            chatInput.style.height = 'auto';
            chatInput.style.height = Math.min(chatInput.scrollHeight, 300) + 'px';
        });

        removeFile.addEventListener('click', () => {
            currentFile = null;
            fileInput.value = '';
            filePreview.classList.add('hidden');
            imagePreview.classList.add('hidden');
            imagePreview.src = '';
        });

        async function handleFileUpload() {
            if (!currentFile) return null;

            // Save file info to user's storage
            const user = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
            const filesKey = `userFiles_${user.email}`;
            const userFiles = JSON.parse(localStorage.getItem(filesKey) || '[]');

            const fileInfo = {
                name: currentFile.name,
                size: currentFile.size,
                type: currentFile.type,
                uploadTime: new Date().toISOString(),
                content: null // Content is NOT re-processed here.
            };

            if (currentFile.type.match('image.*')) {
                // Create a base64 string for images to display in chat
                const base64Data = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = (e) => resolve(e.target.result);
                    reader.onerror = reject;
                    reader.readAsDataURL(currentFile);
                });
                fileInfo.data = base64Data;
            }

            // Save file info to user's storage
            userFiles.push(fileInfo);
            localStorage.setItem(filesKey, JSON.stringify(userFiles));

            return fileInfo;
        }

        // --- Core Message Functions ---
        // Push a message into the current chat's history and return its index
        function addMessageToHistory(text, sender, model = null, attachment = null) {
            const chat = chatHistory.find(c => c.id === currentChatId);
            if (!chat) return -1;
            const msgObj = { text, sender, model, attachment };
            chat.messages.push(msgObj);
            const idx = chat.messages.length - 1;
            if (chat.title === 'New Chat' && sender === 'user') {
                chat.title = text.substring(0, 30) + (text.length > 30 ? '...' : '');
                chatTitle.textContent = chat.title;
            }
            saveChatHistory();
            renderChatHistory();
            return idx;
        }
        
        function copyMessage(chatId, msgIndex) {
            const chat = chatHistory.find(c => c.id === chatId);
            if (!chat || !chat.messages[msgIndex]) return;
            navigator.clipboard.writeText(chat.messages[msgIndex].text).then(() => showToast('Message copied')).catch(() => showToast('Copy failed'));
        }

        function enableInlineEdit(buttonEl, chatId, msgIndex) {
            const chat = chatHistory.find(c => c.id === chatId);
            if (!chat || !chat.messages[msgIndex]) return;
            const messageWrap = buttonEl.closest('.message-wrapper');
            const bubble = messageWrap.querySelector('.message-bubble');
            const original = chat.messages[msgIndex].text;
            const textarea = document.createElement('textarea');
            // Tailwind-like classes so text is visible in both light and dark themes
            textarea.className = 'w-full p-2 rounded border bg-white text-gray-800 dark:bg-gray-800 dark:text-white';
            textarea.rows = 3;
            textarea.value = original;
            bubble.replaceWith(textarea);

            const saveBtn = document.createElement('button');
            saveBtn.className = 'ml-2 px-2 py-1 bg-green-600 text-white rounded text-sm';
            saveBtn.textContent = 'Save';
            const cancelBtn = document.createElement('button');
            cancelBtn.className = 'ml-2 px-2 py-1 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded text-sm';
            cancelBtn.textContent = 'Cancel';
            const controls = document.createElement('div');
            controls.className = 'mt-2 flex justify-end';
            controls.append(saveBtn, cancelBtn);
            textarea.after(controls);

            // Focus and move cursor to end for better UX
            textarea.focus();
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);

            saveBtn.addEventListener('click', () => {
                const newText = textarea.value.trim();
                if (newText === '') { showToast('Message cannot be empty'); return; }
                chat.messages[msgIndex].text = newText;
                saveChatHistory();
                // re-render chat
                loadChat(chatId);
                showToast('Message updated');

                // If the next message exists and is a bot reply, regenerate it
                const nextIndex = msgIndex + 1;
                if (chat.messages[nextIndex] && chat.messages[nextIndex].sender === 'bot') {
                    // clear the bot reply and show loading state
                    chat.messages[nextIndex].text = '';
                    chat.messages[nextIndex].model = null;
                    saveChatHistory();
                    loadChat(chatId);
                    // regenerate in background
                    regenerateBotResponse(chatId, msgIndex, nextIndex).catch(err => { console.error(err); showToast('Failed to regenerate reply'); });
                }
            });
            cancelBtn.addEventListener('click', () => loadChat(chatId));
        }

        // 1. Helper function to format text (Bold, Lists, Newlines)
function formatMessage(text) {
    if (!text) return '';

    // Sanitize HTML to prevent XSS
    let clean = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

    // Bold: **text** -> <strong>text</strong>
    clean = clean.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    // Headers: ### Text -> <h3>Text</h3>
    clean = clean.replace(/###\s*(.*)/g, '<h3 class="text-lg font-bold mt-2 mb-1">$1</h3>');

    // Bullet Lists: - Item -> Bullet point
    clean = clean.replace(/^\s*[\-\*]\s+(.*)/gm, '<div class="flex items-start ml-2"><span class="mr-2">•</span><span>$1</span></div>');

    // Numbered Lists: 1. Item -> Numbered item
    clean = clean.replace(/^\s*(\d+)\.\s+(.*)/gm, '<div class="flex items-start ml-2"><span class="mr-2 font-bold">$1.</span><span>$2</span></div>');

    // Newlines: Convert remaining line breaks to <br>
    clean = clean.replace(/\n/g, '<br>');

    return clean;
}

    function checkForReminders(botResponse) {
    // 1. Clean the response (Remove JSON block AND Markdown stars)
    let cleanResponse = botResponse;
    
    // Remove the JSON block at the end
    const jsonMatch = botResponse.match(/```json\s*(\[.*?\])\s*```$/s);
    if (jsonMatch) {
        cleanResponse = botResponse.replace(jsonMatch[0], '');
    }

    // NEW: Remove "extra stars" (Markdown bolding like **Time**) and extra spaces
    cleanResponse = cleanResponse.replace(/\*\*/g, '').trim();

    // 2. Check if the bot confirmed a reminder
    if (/set a reminder|remind you/i.test(cleanResponse)) {
        
        // 3. Simple regex to find time (e.g., 11:30 PM, 10:00 am)
        const timeMatch = cleanResponse.match(/(\d{1,2}:\d{2})\s*(AM|PM|am|pm)?/);
        
        if (timeMatch) {
            let timeString = timeMatch[0];
            
            // Create a Date object for the reminder
            const now = new Date();
            const reminderTime = new Date();
            
            // Parse 12-hour format to 24-hour
            let [hours, minutes] = timeMatch[1].split(':');
            const modifier = timeMatch[2] ? timeMatch[2].toUpperCase() : null;
            
            let h = parseInt(hours);
            const m = parseInt(minutes);
            
            if (modifier === 'PM' && h < 12) h += 12;
            if (modifier === 'AM' && h === 12) h = 0;
            
            reminderTime.setHours(h, m, 0, 0);
            
            // If time has already passed today, assume it's for tomorrow
            if (reminderTime < now) {
                reminderTime.setDate(reminderTime.getDate() + 1);
            }
            
            const delay = reminderTime.getTime() - now.getTime();
            
            if (delay > 0) {
                console.log(`Reminder set for ${timeString} (in ${Math.round(delay/1000/60)} mins)`);
                showToast(`Timer set for ${timeString}`);
                
                // 4. Set the browser timeout
                setTimeout(() => {
                    // A. Play Sound FIRST
                    const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/933/933.wav');
                    audio.play().catch(e => console.log('Audio play failed:', e));
                    
                    // B. Show Browser Notification (Simultaneous)
                    if (Notification.permission === "granted") {
                        new Notification("Saathi Reminder", { body: cleanResponse });
                    } else if (Notification.permission !== "denied") {
                        Notification.requestPermission().then(permission => {
                            if (permission === "granted") {
                                new Notification("Saathi Reminder", { body: cleanResponse });
                            }
                        });
                    }
                    
                    // C. Show Pop-up Alert (Small delay to ensure sound starts first)
                    setTimeout(() => {
                         alert(`⏰ REMINDER: ${cleanResponse}`);
                    }, 100);
                    
                }, delay);
            }
        }
    }
}

// 2. Updated addMessageToDOM function
// 2. Updated addMessageToDOM function (Fixes font size issue)
function addMessageToDOM(message, sender, model = null, isLoading = false, msgIndex = null, attachment = null) {
    const messageElement = document.createElement('div');
    messageElement.classList.add('w-full', 'message-wrapper', 'mb-2');
    if (isLoading) { messageElement.dataset.id = 'loading-indicator'; }

    // --- Attachment Logic (Untouched) ---
    let attachmentHTML = '';
    if (attachment) {
        if (attachment.type.startsWith('image/')) {
            let imageUrl = null;
            if (attachment.url) {
                imageUrl = attachment.url;
            } else if (attachment.data) {
                imageUrl = attachment.data;
            }

            if (imageUrl) {
                attachmentHTML = `
                    <div class="mt-2 max-w-sm">
                        <img src="${imageUrl}" alt="${attachment.name}" class="rounded-lg max-h-64 object-contain" />
                        <div class="text-xs text-white/70 mt-1">${attachment.name} (${(attachment.size / 1024).toFixed(1)} KB)</div>
                    </div>`;
            } else {
                attachmentHTML = `
                    <div class="mt-2 max-w-sm p-2 bg-white/10 rounded-lg">
                        <p class="text-sm font-medium text-white/90">[Image: ${attachment.name}]</p>
                        <p class="text-xs text-white/70 italic">(Image preview not available)</p>
                    </div>`;
            }
        } else {
            let icon = fileIcons.txt;
            if (attachment.type === 'application/pdf') icon = fileIcons.pdf;
            else if (attachment.type.includes('word')) icon = fileIcons.doc;
            else if (attachment.type.includes('powerpoint') || attachment.type.includes('presentationml')) icon = fileIcons.ppt;

            attachmentHTML = `
                <div class="mt-2 flex items-center space-x-2 p-2 bg-white/10 rounded-lg">
                    <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center text-white">
                        ${icon}
                    </div>
                    <span class="text-sm font-medium text-white truncate">
                        ${attachment.name} (${(attachment.size / 1024).toFixed(1)} KB)
                    </span>
                </div>`;
        }
    }
    // --- End Attachment Logic ---

    // --- NEW RENDERING LOGIC (Fixed Font Size) ---
    if (sender === 'user') {
        const controls = msgIndex !== null ? `<div class="flex items-center space-x-2 ml-2"><button title="Edit" class="p-1 text-white/90 hover:bg-white/10 rounded" onclick="enableInlineEdit(this, '${currentChatId}', ${msgIndex})">✏️</button><button title="Copy" class="p-1 text-white/90 hover:bg-white/10 rounded" onclick="copyMessage('${currentChatId}', ${msgIndex})">📋</button></div>` : '';
        
        messageElement.innerHTML = `<div class="flex items-start gap-3 justify-end">
            <div class="message-bubble bg-blue-600 text-white p-4 rounded-lg rounded-br-none max-w-xl">
                <div class="flex items-center justify-between"><div></div>${controls}</div>
                
                ${message ? `<div class="text-base leading-relaxed">${formatMessage(message)}</div>` : ''}
                
                ${attachmentHTML}
            </div>
            <div class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center flex-shrink-0">
                <span class="font-bold text-lg">U</span>
            </div>
        </div>`;
    } else {
        const modelTag = model ? `<span class="text-xs font-semibold bg-green-500/20 text-green-400 px-2 py-1 rounded-md">[${model}]</span>` : '';
        const loadingIndicator = isLoading ? `<div class="typing-indicator"><span></span><span></span><span></span></div>` : '';
        const controls = (!isLoading && model !== 'Error' && model !== 'System') ? `<div class="flex items-center space-x-2 ml-2"><button title="Play" class="p-1 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white" onclick="speakMessage(this)">🔊</button><button title="Copy" class="p-1 text-gray-500 hover:bg-gray-200 rounded" onclick="copyMessage('${currentChatId}', ${msgIndex})">📋</button></div>` : '';

        messageElement.innerHTML = `<div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5a3 3 0 1 0-5.997.125 4 4 0 0 0-2.526 5.77 4 4 0 0 0 .556 6.588A4 4 0 0 0 8 22a3 3 0 0 0 5.998-.082 4 4 0 0 0 2.526-5.77 4 4 0 0 0-.556-6.588A4 4 0 0 0 16 2a3 3 0 0 0-4-1.125V2z"/><path d="M16 13.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/><path d="M9.5 13a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/><path d="M12 2v2.5"/><path d="M12 22v-2.5"/><path d="m4.2 4.2 1.4 1.4"/><path d="m18.4 18.4 1.4 1.4"/><path d="m4.2 19.8 1.4-1.4"/><path d="m19.8 4.2-1.4 1.4"/></svg>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 rounded-lg rounded-tl-none max-w-xl shadow-sm">
                <div class="flex items-center justify-between">
                    ${model ? `<div class="flex items-center space-x-2 mb-2">${modelTag}</div>` : ''}
                    ${controls}
                </div>
                
                <div class="text-gray-800 dark:text-gray-200 text-base leading-relaxed">
                    ${isLoading ? loadingIndicator : formatMessage(message)}
                </div>
                
            </div>
        </div>`;
    }

    chatMessages.appendChild(messageElement);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
        
        async function sendMessage() {
            const messageText = chatInput.value.trim();
            const fileToProcess = currentFile; // <-- Grab the file
            
            // Clear the file input *immediately*
            currentFile = null; 
            fileInput.value = '';
            filePreview.classList.add('hidden');
            imagePreview.classList.add('hidden');
            imagePreview.src = '';

            if (messageText || fileToProcess) {
                // We'll create the attachment object *inside* getBotResponse
                // For now, just add the user's text message to the DOM
                const idx = addMessageToHistory(messageText, 'user'); // Add simple message
                addMessageToDOM(messageText, 'user', null, false, idx);
                chatInput.value = '';
                chatInput.style.height = 'auto';
                
                // Pass the message and the file to getBotResponse
                getBotResponse(messageText, fileToProcess);
            }
        }
        
        // --- Speech and Language ---
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        let recognition;
        let voices = [];
        const synth = window.speechSynthesis;

        const voicesPromise = new Promise(resolve => {
            const getAndResolveVoices = () => {
                const voiceList = synth.getVoices();
                if (voiceList.length) {
                    voices = voiceList;
                    resolve(voices);
                }
            };
            getAndResolveVoices();
            if (synth.onvoiceschanged !== undefined) {
                synth.onvoiceschanged = getAndResolveVoices;
            }
        });

        async function findVoice(lang) {
            await voicesPromise;
            if (voices.length === 0) {
                console.warn("Speech synthesis voices not available.");
                return null;
            }
            let voice = voices.find(v => v.lang === lang);
            if (voice) return voice;

            const langPrefix = lang.split('-')[0];
            voice = voices.find(v => v.lang.startsWith(langPrefix));
            if (voice) return voice;
            
            try {
                const langName = new Intl.DisplayNames(['en'], { type: 'language' }).of(langPrefix);
                if (langName) {
                     voice = voices.find(v => v.name.includes(langName));
                }
            } catch (e) { console.error("Could not get language name for fallback search.", e); }
            
            return voice;
        }
        
        async function speakText(text, lang) {
            if (!text || !synth) return;
            synth.cancel();
            
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = lang;
            const selectedVoice = await findVoice(lang);
            
            if (selectedVoice) {
                utterance.voice = selectedVoice;
            } else {
                console.warn(`No specific voice found for language ${lang}. Using browser default.`);
            }
            
            if (synth.paused) {
               synth.resume();
            }
            synth.speak(utterance);
        }

        function speakMessage(buttonElement) {
            try {
                const settings = window.__chatSettings || loadSettings();
                if (!settings.ttsEnabled) { showToast('Text-to-Speech is disabled in settings'); return; }
            } catch (e) { /* ignore */ }
            
            // Find the closest message wrapper
            const messageWrapper = buttonElement.closest('.message-wrapper');
            
            // Find the element containing the text (it has class 'leading-relaxed')
            const textElement = messageWrapper.querySelector('.leading-relaxed');
            
            if (textElement) {
                // Get clean text (removing HTML tags used for formatting)
                const messageText = textElement.innerText || textElement.textContent;
                speakText(messageText, currentLang);
            } else {
                console.error("Could not find text to speak.");
            }
        }

        if (SpeechRecognition) {
            recognition = new SpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.onstart = () => { voiceButton.classList.add('voice-active'); chatInput.placeholder = "Listening..."; };
            recognition.onend = () => { voiceButton.classList.remove('voice-active'); chatInput.placeholder = "Type your message or click the mic to speak..."; };
            recognition.onresult = (event) => { chatInput.value = event.results[0][0].transcript; };
            recognition.onerror = (event) => { console.error('Speech recognition error:', event.error); };
            voiceButton.addEventListener('click', () => {
                recognition.lang = currentLang;
                try { recognition.start(); } catch(e) { console.error("Could not start recognition:", e); }
            });
        } else { voiceButton.style.display = 'none'; }


        async function translateText(text, targetLang) {
    if (!text) return '';
    if (targetLang === 'en-US') return text;

    // STRICT System Prompt for Translation
    const prompt = `You are a professional translator. Translate the following text to the language code '${targetLang}'.
    
    CRITICAL RULES:
    1. Return ONLY the translated text.
    2. Do NOT add explanations, quotes, or words like "Here is the translation".
    3. Do NOT translate technical tags like [Saathi], [Error], or JSON blocks.
    
    Text to translate:
    "${text}"`;

    // Call API with: image=null, securityText=null, skipSecurity=true, skipHistory=true
    const translation = await callGeminiAPI(prompt, null, null, true, true);
    
    if (typeof translation === 'object' && translation.error) return text;
    
    return translation || text;
}
        
        async function callGeminiAPI(prompt, imagePart = null, securityCheckText = null, skipSecurity = false, skipHistory = false) {
    // ... rest of the function ...
    
    // In index.php, inside callGeminiAPI function:

        // --- Step 1: Check Security Rules with your Server ---
        try {
            const checkUrl = 'api/chat_api.php?action=check_message';
            // Send only the text part for checking
            const checkPayload = { text: prompt }; 
            
            const checkResponse = await fetch(checkUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(checkPayload)
            });

            if (!checkResponse.ok) {
                // If the server crashes (500 error), assume it's unsafe or broken
                throw new Error(`Server validation unavailable (Status ${checkResponse.status})`);
            }

            const checkResult = await checkResponse.json();
            
            // If the backend explicitly says success: false, STOP HERE.
            if (checkResult.success === false) {
                // Return the specific error from the server (e.g., "Restricted word: test")
                // We format it with a specific tag so the UI renders it cleanly
                return `⚠️ **System Alert:** ${checkResult.error || "Message blocked by security policy."}`;
            }

        } catch (error) {
            console.error("Server rule check failed:", error);
            // If we can't reach the server, we block the message to be safe, or you can choose to allow it.
            return `⚠️ **System Error:** Unable to validate message security. Details: ${error.message}`; 
        }
    // --- Step 2: Call Google with FULL HISTORY ---
    
    // FIX 1: Use a valid model name (gemini-1.5-flash)
    // FIX: Use 'gemini-2.5-flash-lite' which is more stable/less overloaded than standard Flash
const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=${API_KEY}`;
    
    // FIX 2: Build the history array
    const contents = [];

    // Get previous messages from the current chat
    const currentChat = chatHistory.find(c => c.id === currentChatId);
    if (currentChat && currentChat.messages) {
        // Take the last 10 messages to give the AI context
        // (We slice -10 to avoid sending too much data/tokens)
        const recentMessages = currentChat.messages.slice(-10); 

        recentMessages.forEach(msg => {
            // Skip system messages or errors
            if (!msg.text || msg.model === 'Error' || msg.model === 'System') return;
            
            // Google expects 'user' or 'model' roles
            const role = msg.sender === 'user' ? 'user' : 'model';
            
            contents.push({
                role: role,
                parts: [{ text: msg.text }]
            });
        });
    }

    // Build the CURRENT message part
    const currentParts = [];
    if (imagePart) {
        currentParts.push(imagePart);
    }
    currentParts.push({ text: prompt });

    // Add the CURRENT message to the history
    contents.push({
        role: "user",
        parts: currentParts
    });

    // Send the WHOLE conversation to Google
    const payload = { contents: contents }; 

    try {
        const response = await fetch(apiUrl, { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify(payload) 
        });

        if (!response.ok) {
            const errorBody = await response.text();
            console.error("API Error Body:", errorBody);
            throw new Error(`API Error: ${response.status}`);
        }
        
        const result = await response.json();
        let responseText = result.candidates?.[0]?.content?.parts?.[0]?.text;
        
        if (responseText) {
            // STOP deleting the symbols! We need them for formatting.
return responseText.trim();
        } else {
            if (result.candidates && result.candidates[0].finishReason === 'SAFETY') {
                throw new Error("Response blocked by safety settings.");
            }
            throw new Error("No content received from API.");
        }
    } catch (error) { 
        console.error("API call failed:", error); 
        return `[Error] API call failed: ${error.message}`; 
    }
}

        // --- Helper to detect if web search is needed ---
        function isWebSearchNeeded(text) {
            const triggers = [
                /current|latest|news|update|today|yesterday|tomorrow|live/i,
                /weather|temperature|forecast|rain/i,
                /who won|score|match|game result|standings/i,
                /price of|stock|bitcoin|gold|dollar/i,
                /when is|release date|upcoming/i,
                /google this|search for/i
            ];
            return triggers.some(regex => regex.test(text));
        }

        async function getBotResponse(userMessage, file = null) {
            // Insert a placeholder bot message
            const botIdx = addMessageToHistory('', 'bot', null);
            pendingBotMsgIndex = botIdx;
            addMessageToDOM('', 'bot', null, true, botIdx);

            let prompt = userMessage;
            let imagePart = null;
            let attachmentForHistory = null;
            
            // --- NEW: Flag to track if web search happened ---
            let performedWebSearch = false; 

            // --- 1. HANDLE WEB SEARCH ---
            // Only search if:
            // A. User text matches triggers (weather, news, etc)
            // B. NO file is attached (searching with file analysis is complex)
            if (!file && isWebSearchNeeded(userMessage)) {
                
                // Update UI to show we are searching
                const loadingBubble = document.querySelector('[data-id="loading-indicator"] .message-bubble');
                if (loadingBubble) {
                    loadingBubble.innerHTML = `
                        <div class="flex items-center space-x-2 text-sm text-gray-500">
                            <span class="animate-spin">🌍</span>
                            <span>Searching live web...</span>
                        </div>`;
                }

                try {
                    const searchRes = await fetch('api/chat_api.php?action=web_search', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ query: userMessage })
                    });
                    
                    const searchData = await searchRes.json();

                    if (searchData.success && searchData.result) {
                        // Append search results to the prompt for Gemini
                        prompt += "\n\n[SYSTEM: The user asked for real-time information. Here are the web search results. Use them to answer the question.]\n\n" + searchData.result;
                        
                        // --- MARK SEARCH AS PERFORMED ---
                        performedWebSearch = true; 
                    } else {
                        console.warn("Web search returned no results or failed.");
                    }
                } catch (e) {
                    console.error("Search API Error:", e);
                }
                
                // Restore loading indicator for Gemini phase
                if (loadingBubble) loadingBubble.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
            }

            // --- 2. HANDLE FILES (EXISTING) ---
            if (file) {
                try {
                    let persistentUrl = null;
                    if (file.type.startsWith('image/')) {
                        const fd = new FormData();
                        fd.append('image', file);
                        
                        const tempMsg = document.querySelector('[data-id="loading-indicator"] .message-bubble');
                        if (tempMsg) tempMsg.innerHTML = '<p>Uploading image...</p>';
                        
                        const uploadRes = await fetch('api/chat_api.php?action=upload_image', {
                            method: 'POST',
                            body: fd
                        });
                        const uploadData = await uploadRes.json();
                        if (uploadData.success) {
                            persistentUrl = uploadData.url; 
                        }
                    }
                    
                    const loadingBubble = document.querySelector('[data-id="loading-indicator"] .message-bubble');
                    if (loadingBubble) loadingBubble.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';

                    if (file.type.startsWith('image/')) {
                        const base64Data = await readFileAsDataURL(file);
                        imagePart = { 
                            inlineData: {
                                mimeType: file.type,
                                data: base64Data.split(',')[1] 
                            }
                        };
                        attachmentForHistory = {
                            name: file.name,
                            size: file.size,
                            type: file.type,
                            data: base64Data, 
                            url: persistentUrl 
                        };

                    } else {
                        let extractedText = '';
                        if (file.type === 'application/pdf') {
                            extractedText = await extractPdfText(file);
                        } else if (file.type.includes('word')) {
                            extractedText = await extractWordText(file);
                        } else if (file.type.includes('powerpoint') || file.type.includes('presentationml')) {
                            extractedText = await extractPowerPointText(file);
                        } else {
                            extractedText = await readFileAsText(file);
                        }

                        const MAX_TEXT_LENGTH = 30000; 
                        let isTruncated = false;
                        if (extractedText) {
                            extractedText = extractedText.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/g, '');
                            if (extractedText.length > MAX_TEXT_LENGTH) {
                                extractedText = extractedText.substring(0, MAX_TEXT_LENGTH);
                                isTruncated = true;
                            }
                        }
                        
                        prompt += "\n\n--- Attached File Content ---\n" + extractedText;
                        if (isTruncated) {
                             prompt += "\n\n[Note: The attached file content was too long and has been truncated.]";
                        }
                        prompt += "\n--- End of File ---";
                        attachmentForHistory = { name: file.name, size: file.size, type: file.type, url: null };
                    }

                    const chat = chatHistory.find(c => c.id === currentChatId);
                    if(chat) {
                        const lastMessage = chat.messages[chat.messages.length - 2]; 
                        if (lastMessage && lastMessage.sender === 'user') {
                            lastMessage.attachment = attachmentForHistory;
                            saveChatHistory(); 
                            loadChat(currentChatId); 
                        }
                    }

                } catch (err) {
                    console.error("File processing error:", err);
                    handleBotResponse(`[Error] I failed to process your file: ${err.message}`, 'Error', botIdx);
                    return; 
                }
            }

            // --- 3. CALL GEMINI ---
            const englishMessage = await translateText(prompt, 'en-US');
            
            let botResponse = null;
            // Check for story shortcuts
            const chat = chatHistory.find(c => c.id === currentChatId);
            let prevBotMsg = '';
            if (chat && chat.messages && chat.messages.length > 0) {
                for (let i = chat.messages.length - 1; i >= 0; i--) {
                    if (chat.messages[i].sender === 'bot') { prevBotMsg = chat.messages[i].text || ''; break; }
                }
            }
            const shortReply = (englishMessage || '').trim();
            const isShortSelection = shortReply.length > 0 && shortReply.length < 60 && /^[\w\s\-\\&]+$/.test(shortReply);
            const askedForStory = /what kind of story|what kind of adventure|which (?:story|type).*would you like|do you prefer/i.test(prevBotMsg);

            if (isShortSelection && askedForStory && !file) { 
                 const directPrompt = `You are Echo, an AI storyteller...`; 
                 botResponse = await callGeminiAPI(directPrompt, imagePart, userMessage); 
            } else {
                const comprehensivePrompt = buildComprehensivePrompt(englishMessage);
                botResponse = await callGeminiAPI(comprehensivePrompt, imagePart, userMessage); 
            }

            if (botResponse) {
                const match = botResponse.match(/^\s*[`']?\[(.*?)\][`']?\s*(.*)/s);
                let model = 'Saathi'; // Default name
                let message = botResponse;

                if (match) {
                    model = match[1];
                    message = match[2];
                }

                // --- UPDATE LABEL IF WEB SEARCH WAS USED ---
                if (performedWebSearch) {
                    // Appends to existing model tag (e.g. "[Q/A Model - Live Web Search]")
                    model = `${model} - Live Web Search`;
                }

                const translatedMessage = await translateText(message, currentLang);
                handleBotResponse(translatedMessage, model, botIdx);
            } else {
                handleBotResponse("I encountered an error. Please try again.", 'Error', botIdx);
            }
        }

       function handleBotResponse(message, model, msgIndex = null) {
    // 1. Parse out the Suggestions JSON
    let cleanMessage = message;
    let suggestions = [];

    // Regex to find the JSON block at the end
    // Removed '$' anchor to find JSON even if there's whitespace after it
const jsonMatch = message.match(/```json\s*(\[.*?\])\s*```/s);
    if (jsonMatch) {
        try {
            suggestions = JSON.parse(jsonMatch[1]);
            // Remove the JSON block from the visible text
            cleanMessage = message.replace(jsonMatch[0], '').trim();
            
            // Rename model if suggestions are found
            if (model && model.trim() === 'Saathi') {
                model = 'Saathi - Proactive Suggestions';
            }
        } catch (e) {
            console.warn("Failed to parse suggestions:", e);
        }
    }

    // 2. Update history (Save text AND suggestions)
    const chat = chatHistory.find(c => c.id === currentChatId);
    if (chat) {
        if (typeof msgIndex === 'number' && chat.messages[msgIndex]) {
            chat.messages[msgIndex].text = cleanMessage;
            chat.messages[msgIndex].model = model;
            chat.messages[msgIndex].suggestions = suggestions; // <--- NEW: Save suggestions
        } else {
            chat.messages.push({ 
                text: cleanMessage, 
                sender: 'bot', 
                model: model,
                suggestions: suggestions // <--- NEW: Save suggestions
            });
        }
        saveChatHistory();
    }

    // 3. Re-render the chat (This will now call renderSuggestions via loadChat)
    loadChat(currentChatId);
    pendingBotMsgIndex = null;
    checkForReminders(message);
}

// Helper function to create suggestion buttons
// Helper function to create suggestion buttons
function renderSuggestions(suggestions) {
    const chatContainer = document.getElementById('chat-messages');
    
    const suggestionsDiv = document.createElement('div');
    suggestionsDiv.className = 'flex flex-wrap gap-2 mt-2 mb-4 ml-12 animate-fade-in';
    
    suggestions.forEach(s => {
        const btn = document.createElement('button');
        btn.className = 'px-3 py-1 text-sm bg-white border border-gray-300 rounded-full text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors shadow-sm';
        btn.textContent = s.text;
        
        // --- CLICK HANDLER ---
        btn.onclick = () => {
            // 1. Populate the input field
            const chatInput = document.getElementById('chat-input');
            chatInput.value = s.action;
            
            // 2. Focus the input so user can type/edit immediately
            chatInput.focus();
            
            // 3. Resize the textarea to fit the text
            chatInput.style.height = 'auto';
            chatInput.style.height = Math.min(chatInput.scrollHeight, 200) + 'px';

            // 4. REMOVED: sendMessage(); 
            // This prevents the message from being sent automatically.

            // 5. Remove the suggestion buttons to declutter
            suggestionsDiv.remove();
        };
        
        suggestionsDiv.appendChild(btn);
    });

    chatContainer.appendChild(suggestionsDiv);
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

        // Regenerate a bot reply for a given user message and bot index.
        async function regenerateBotResponse(chatId, userIndex, botIndex) {
            const chat = chatHistory.find(c => c.id === chatId);
            if (!chat) throw new Error('Chat not found');
            const userMessage = chat.messages[userIndex]?.text;
            if (!userMessage) throw new Error('User message missing');

            // Prepare prompt using the same comprehensive prompt builder
            const englishMessage = await translateText(userMessage, 'en-US');
            const comprehensivePrompt = buildComprehensivePrompt(englishMessage);
            // call the API
            const botResponse = await callGeminiAPI(comprehensivePrompt);
            if (!botResponse) {
                chat.messages[botIndex].text = "I encountered an error. Please try again.";
                chat.messages[botIndex].model = 'Error';
                saveChatHistory();
                loadChat(chatId);
                return;
            }
            const match = botResponse.match(/^\s*[`']?\[(.*?)\][`']?\s*(.*)/s);
            let model = 'Saathi ';
            let message = botResponse;
            if (match) { model = match[1]; message = match[2]; }
            const translatedMessage = await translateText(message, currentLang);
            chat.messages[botIndex].text = translatedMessage;
            chat.messages[botIndex].model = model;
            saveChatHistory();
            loadChat(chatId);
        }

        function buildComprehensivePrompt(englishMessage) {
            // Include user-configured tone and length preferences when available
            let s = defaultSettings();
            try { s = loadSettings() || s; } catch (e) { /* ignore */ }

            const toneInstr = s.tone === 'formal' ? 'Use a formal, professional tone.' : s.tone === 'casual' ? 'Use a casual, friendly tone.' : 'Be helpful and straightforward.';
            const lengthInstr = s.length === 'concise' ? 'Keep answers short.' : 'Provide thorough explanations.';
            
            // --- LANGUAGE INSTRUCTION ---
            const targetLangCode = (typeof currentLang !== 'undefined') ? currentLang : 'en-US';
            
            const langInstruction = `
            CRITICAL LANGUAGE RULE:
            - You MUST respond ENTIRELY in the language: '${targetLangCode}'.
            - Do NOT include any English text if the target language is not English.
            - Do NOT explain what you are doing (e.g., don't say "Here is the translation"). Just give the answer directly.
            `;

            return `
        You are Saathi, an advanced AI assistant.

        ${langInstruction}

        **CRITICAL INSTRUCTION - YOU MUST START YOUR RESPONSE WITH EXACTLY ONE OF THESE TAGS:**

        1. \`[Saathi - Chained]\`
           - USE THIS IF: The user expresses a personal emotion (e.g. "I am sad") AND asks for a distinct task (e.g. "play music").
           - OR IF: The user asks for two completely different tasks (e.g. "Summarize this AND tell me a joke").
           - **DO NOT** use this for simple image analysis requests.

        2. \`[Sentiment Analyzer]\`
           - USE THIS IF: The user expresses a feeling/emotion in text.
           - OR IF: The user provides an image of a **human face** and asks to analyze the expression/emotion.

        3. \`[Image Analyst]\`
           - USE THIS IF: The user provides an image (animal, object, scene) and asks to analyze, describe, or explain it.

        4. \`[Summarizer]\`
           - USE THIS IF: The user asks to summarize a text document, PDF, or long article.

        5. \`[Q/A Model]\`
           - USE THIS IF: The user asks a specific factual question (e.g. "Who is the president?", "What is the capital?").

        6. \`[Saathi]\`
           - USE THIS IF: None of the above apply (general chat, greetings).

        **FORMATTING RULES:**
        - Use **bold** for key terms.
        - Use headings (###) and bullet points.
        - Do NOT output big blocks of text.

        **PROACTIVE SUGGESTIONS:**
        At the very end, strictly on a new line, provide 2-3 suggestions in this JSON format:
        \`\`\`json
        [{"text": "Label", "action": "Prompt"}]
        \`\`\`

        Additional preferences: ${toneInstr} ${lengthInstr}

        User's message: "${englishMessage}"
        `;
        }
        
        // --- Theme Management ---
        function applyTheme(theme) {
            // Support system preference and explicit theme choices
            document.documentElement.style.transition = 'background-color 0.25s ease, color 0.25s ease';
            const applyDark = () => {
                document.documentElement.classList.add('dark');
                themeIconMoon.classList.remove('hidden');
                themeIconSun.classList.add('hidden');
                document.body.style.backgroundColor = '#0b1220';
                document.body.style.color = '#E5E7EB';
            };
            const applyLight = () => {
                document.documentElement.classList.remove('dark');
                themeIconSun.classList.remove('hidden');
                themeIconMoon.classList.add('hidden');
                document.body.style.backgroundColor = '#ffffff';
                document.body.style.color = '#0f172a';
            };

            if (theme === 'system') {
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (prefersDark) applyDark(); else applyLight();
            } else if (theme === 'dark') applyDark(); else applyLight();

            setTimeout(() => { document.documentElement.style.transition = ''; }, 300);
        }

        themeToggleButton.addEventListener('click', () => {
            const settings = loadSettings();
            const currentTheme = settings.theme || 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            settings.theme = newTheme;
            saveSettings(settings);
            applyTheme(newTheme);
        });

        // Settings persistence and application helpers
        function defaultSettings() {
            return {
                theme: 'dark',
                fontSize: 'medium',
                accent: '#2563eb',
                messageStyle: 'bubbles',
                highContrast: false,
                // defaultModel may be 'gemini' or 'claude-sonnet-3.5'
                defaultModel: 'gemini',
                incognito: false,
                personalization: true,
                modelTraining: false,
                tone: 'helpful',
                length: 'detailed',
                uiLanguage: 'en-US',
                notifications: false,
                notifySound: false,
                notifyPopup: false,
                notifyEmail: false,
                ttsEnabled: true,
                voiceInput: true
            };
        }

        function loadSettings() {
            try {
                const raw = localStorage.getItem('chatAppSettings');
                if (!raw) return defaultSettings();
                return Object.assign(defaultSettings(), JSON.parse(raw));
            } catch (e) { return defaultSettings(); }
        }

        function saveSettings(settings) {
            localStorage.setItem('chatAppSettings', JSON.stringify(settings));
            applySettings(settings);
        }

        function applySettings(settings) {
            // Apply theme and other visual settings
            applyTheme(settings.theme || 'dark');
            // Font size
            if (settings.fontSize === 'small') document.documentElement.style.fontSize = '13px';
            else if (settings.fontSize === 'large') document.documentElement.style.fontSize = '18px';
            else document.documentElement.style.fontSize = '16px';
            // Accent color
            document.documentElement.style.setProperty('--accent', settings.accent || '#2563eb');
            // High contrast
            if (settings.highContrast) document.documentElement.classList.add('high-contrast'); else document.documentElement.classList.remove('high-contrast');
            // Message style class
            if (settings.messageStyle === 'blocks') document.body.classList.add('message-blocks'); else document.body.classList.remove('message-blocks');
            // expose to global for other functions
            window.__chatSettings = settings;
        }

        function openSettingsModal() {
            const modal = document.getElementById('settings-modal');
            const s = loadSettings();
            // populate controls
            ['theme','font-size','message-style','high-contrast','incognito','personalization','model-training','tone','length','language-ui','notifications','tts','voice-input'].forEach(() => {});
            const setIfExists = (id, value) => { const el = document.getElementById(id); if (!el) return; if (el.type === 'checkbox') el.checked = !!value; else el.value = value; };
            setIfExists('setting-theme', s.theme);
            setIfExists('setting-font-size', s.fontSize);
            setIfExists('setting-accent', s.accent);
            setIfExists('setting-message-style', s.messageStyle);
            setIfExists('setting-high-contrast', s.highContrast);
            setIfExists('setting-incognito', s.incognito);
            setIfExists('setting-personalization', s.personalization);
            setIfExists('setting-model-training', s.modelTraining);
            // Enable Claude checkbox maps to defaultModel
            const enableClaudeEl = document.getElementById('setting-enable-claude');
            if (enableClaudeEl) enableClaudeEl.checked = (s.defaultModel === 'claude-sonnet-3.5');
            setIfExists('setting-tone', s.tone);
            setIfExists('setting-length', s.length);
            setIfExists('setting-language-ui', s.uiLanguage);
            setIfExists('setting-notifications', s.notifications);
            setIfExists('notify-sound', s.notifySound);
            setIfExists('notify-popup', s.notifyPopup);
            setIfExists('notify-email', s.notifyEmail);
            setIfExists('setting-tts', s.ttsEnabled);
            setIfExists('setting-voice-input', s.voiceInput);

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeSettingsModal() { const modal = document.getElementById('settings-modal'); modal.classList.add('hidden'); modal.classList.remove('flex'); }

        function addImageToChat(sender, url) {
  const chat = document.getElementById('chat-container'); // same container you append messages to
  const wrapper = document.createElement('div');
  wrapper.className = sender === 'user'
    ? 'my-2 flex justify-end'
    : 'my-2 flex justify-start';

  const bubble = document.createElement('div');
  bubble.className = 'rounded-xl overflow-hidden border border-gray-700 bg-[#1b2230] p-1 max-w-xs';

  const img = document.createElement('img');
  img.src = url;
  img.alt = 'uploaded image';
  img.className = 'rounded-lg w-full h-auto';

  bubble.appendChild(img);
  wrapper.appendChild(bubble);
  chat.appendChild(wrapper);
  chat.scrollTop = chat.scrollHeight;
}


        // Wire settings controls and actions after DOM ready
        // --- THIS IS THE ONLY DOMContentLoaded LISTENER YOU NEED ---
       


document.addEventListener('DOMContentLoaded', () => {
  /* =========================
     1) Three-dots dropdown
     ========================= */
  const moreBtn        = document.getElementById('more-btn');      // the three-dots button
  const moreMenu       = document.getElementById('more-menu');     // the dropdown panel
  const menuSettings   = document.getElementById('menu-settings'); // "Settings" inside dropdown
  const menuClear      = document.getElementById('menu-clear');    // "Clear Conversation History" inside dropdown
  const settingsGear   = document.getElementById('settings-open-btn'); // if you still have a gear somewhere

  if ("Notification" in window) {
    Notification.requestPermission();
}

  function toggleMenu(forceHide = false) {
    if (!moreMenu) return;
    const isHidden = moreMenu.classList.contains('hidden');
    if (forceHide || !isHidden) {
      moreMenu.classList.add('hidden');
      moreBtn?.setAttribute('aria-expanded', 'false');
    } else {
      moreMenu.classList.remove('hidden');
      moreBtn?.setAttribute('aria-expanded', 'true');
    }
  }

  function showSettingsModal() {
    if (typeof openSettingsModal === 'function') {
      openSettingsModal();
      return;
    }
    // Fallback if you don't have that function
    const sm = document.getElementById('settings-modal');
    if (sm) {
      sm.classList.remove('hidden');
      sm.classList.add('flex');
    } else {
      console.warn('settings-modal not found');
    }
  }

  // open/close dropdown
  moreBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleMenu();
  });
  document.addEventListener('click', () => toggleMenu(true));

  // Settings from dropdown
  menuSettings?.addEventListener('click', (e) => {
    e.preventDefault();
    toggleMenu(true);
    showSettingsModal();
  });

  // Clear from dropdown
  menuClear?.addEventListener('click', (e) => {
    e.preventDefault();
    toggleMenu(true);
    if (typeof clearAllHistory === 'function') {
      clearAllHistory();
    } else {
      // local fallback
      localStorage.clear();
      sessionStorage.clear();
      alert('History cleared locally.');
    }
  });

  // (Optional) if a lone gear still exists
  settingsGear?.addEventListener('click', (e) => {
    e.preventDefault();
    showSettingsModal();
  });

  /* =========================
     2) Initial setup
     ========================= */
  const savedTheme = localStorage.getItem('theme') || 'dark';
  if (typeof applyTheme === 'function') applyTheme(savedTheme);

  if (typeof loadChatHistory === 'function') loadChatHistory();

  // Username
  const storedUsername = <?php echo $user_name_js; ?>;
  const avatarInit = document.getElementById('sidebar-avatar-initial');
  const sidebarUsername = document.getElementById('sidebar-username');
  if (avatarInit && storedUsername && storedUsername.length) {
    avatarInit.innerText = storedUsername[0].toUpperCase();
  }
  if (sidebarUsername && storedUsername) {
    sidebarUsername.innerText = storedUsername;
  }

  /* =========================
     3) Main chat controls
     ========================= */
  const sendButton         = document.getElementById('send-button');
  const newChatButton      = document.getElementById('new-chat-button');
  const clearHistoryButton = document.getElementById('clear-history-button'); // if you keep a separate button
  const chatInput          = document.getElementById('chat-input');
  const languageSelect     = document.getElementById('language-select');

  sendButton?.addEventListener('click', () => { if (typeof sendMessage === 'function') sendMessage(); });
  newChatButton?.addEventListener('click', () => { if (typeof startNewChat === 'function') startNewChat(); });

  clearHistoryButton?.addEventListener('click', () => {
    if (typeof clearAllHistory === 'function') clearAllHistory();
    toggleMenu(true);
  });

  chatInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); if (typeof sendMessage === 'function') sendMessage(); }
  });
  chatInput?.addEventListener('input', () => {
    chatInput.style.height = 'auto';
    const maxHeight = 200;
    chatInput.style.height = `${Math.min(chatInput.scrollHeight, maxHeight)}px`;
  });
  languageSelect?.addEventListener('change', (e) => {
    if (typeof currentLang !== 'undefined') currentLang = e.target.value;
  });

  /* =========================
     3b) Image upload wiring (NEW)
     ========================= */
  const attachButton = document.getElementById('attach-button'); // 📎 button in the composer
  const fileInput    = document.getElementById('file-input');    // single hidden input

  // Click 📎 → open file chooser
  attachButton?.addEventListener('click', () => fileInput?.click());

  // File chosen → upload to server (persists in DB)
  // Fixed Code
  fileInput?.addEventListener('change', (e) => {
    const file = e.target.files && e.target.files[0];
    // ONLY run the uploadImage function if the file is an image
    if (file && file.type.startsWith('image/')) {
        uploadImage(file); 
    } else if (file) {
        // For non-image files, do nothing here. 
        // The *other* fileInput listener will handle it when the user clicks 'Send'.
    }
    
    // Do NOT clear the value here, as the other listener needs it.
    // The value will be cleared by the sendMessage() function.
    // e.target.value = ''; // <-- REMOVE THIS LINE
  });
  // Drag & drop (optional; keep if you want)
  const chatArea = document.getElementById('chat-messages');
  chatArea?.addEventListener('dragover', (e) => e.preventDefault());
  chatArea?.addEventListener('drop', (e) => {
    e.preventDefault();
    const file = e.dataTransfer.files && e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) uploadImage(file);
  });

  /* =========================
     4) Profile chip → Logout/Close confirm
     ========================= */
  // Helpers
  function openLogoutConfirm() {
    const m = document.getElementById('logout-confirm-modal');
    if (!m) return;
    m.classList.remove('hidden');
    m.classList.add('flex');
  }
  function closeLogoutConfirm() {
    const m = document.getElementById('logout-confirm-modal');
    if (!m) return;
    m.classList.add('hidden');
    m.classList.remove('flex');
  }

  // Clicking the profile chip opens the confirm
  const profileButton =
      document.getElementById('profile-button')
   || document.getElementById('sidebar-user-card')
   || document.getElementById('sidebar-username'); // use whichever exists
  profileButton?.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    openLogoutConfirm();
  });

  // Modal buttons
  document.getElementById('logout-confirm-close')?.addEventListener('click', closeLogoutConfirm);
  document.getElementById('logout-cancel-btn')?.addEventListener('click', closeLogoutConfirm);

  // Background click closes (optional)
  document.getElementById('logout-confirm-modal')?.addEventListener('click', (e) => {
    if (e.target.id === 'logout-confirm-modal') closeLogoutConfirm();
  });

  // ESC key closes modal
  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape') closeLogoutConfirm();
  });

  // Logout → API then go to login
  document.getElementById('logout-yes-btn')?.addEventListener('click', () => {
    fetch('api/logout.php', { credentials: 'include' })
      .then(() => {
        sessionStorage.removeItem('loggedInUser');
        window.location.href = 'login.html';
      })
      .catch(() => {
        // even if API fails, navigate to login
        window.location.href = 'login.html';
      });
  });

  /* =========================
     5) Settings modal wiring
     ========================= */
  const settingsCloseBtn = document.getElementById('settings-close-btn');
  settingsCloseBtn?.addEventListener('click', () => {
    if (typeof closeSettingsModal === 'function') closeSettingsModal();
    else {
      const sm = document.getElementById('settings-modal');
      sm?.classList.add('hidden');
      sm?.classList.remove('flex');
    }
  });

  // Controls → settings save
  const updateFromControls = () => {
    if (typeof loadSettings !== 'function' || typeof saveSettings !== 'function') return;
    const s = loadSettings();
    const byId = (id) => document.getElementById(id);
    s.theme          = byId('setting-theme')?.value ?? s.theme;
    s.fontSize       = byId('setting-font-size')?.value ?? s.fontSize;
    s.accent         = byId('setting-accent')?.value ?? s.accent;
    s.messageStyle   = byId('setting-message-style')?.value ?? s.messageStyle;
    s.highContrast   = byId('setting-high-contrast')?.checked ?? s.highContrast;
    s.incognito      = byId('setting-incognito')?.checked ?? s.incognito;
    s.personalization= byId('setting-personalization')?.checked ?? s.personalization;
    s.modelTraining  = byId('setting-model-training')?.checked ?? s.modelTraining;
    s.tone           = byId('setting-tone')?.value ?? s.tone;
    s.length         = byId('setting-length')?.value ?? s.length;
    s.uiLanguage     = byId('setting-language-ui')?.value ?? s.uiLanguage;
    s.notifications  = byId('setting-notifications')?.checked ?? s.notifications;
    s.notifySound    = byId('notify-sound')?.checked ?? s.notifySound;
    s.notifyPopup    = byId('notify-popup')?.checked ?? s.notifyPopup;
    s.notifyEmail    = byId('notify-email')?.checked ?? s.notifyEmail;
    s.ttsEnabled     = byId('setting-tts')?.checked ?? s.ttsEnabled;
    s.voiceInput     = byId('setting-voice-input')?.checked ?? s.voiceInput;
    saveSettings(s);
  };

  [
    'setting-theme','setting-font-size','setting-accent','setting-message-style','setting-high-contrast',
    'setting-incognito','setting-personalization','setting-model-training','setting-tone','setting-length',
    'setting-language-ui','setting-notifications','notify-sound','notify-popup','notify-email',
    'setting-tts','setting-voice-input'
  ].forEach(id => document.getElementById(id)?.addEventListener('change', updateFromControls));

  // Buttons inside Settings modal
  const settingsClearBtn = document.getElementById('setting-clear-history'); // avoid duplicate const name
  settingsClearBtn?.addEventListener('click', () => {
    if (typeof clearAllHistory === 'function') clearAllHistory();
    if (typeof showToast === 'function') showToast('Conversation history cleared');
  });

  const exportBtn = document.getElementById('setting-export-data');
  exportBtn?.addEventListener('click', () => {
    fetch('api/chat_api.php?action=export_data')
      .then(res => res.blob())
      .then(blob => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'chat-history.json'; a.click();
        URL.revokeObjectURL(url);
        if (typeof showToast === 'function') showToast('Export started');
      })
      .catch(() => typeof showToast === 'function' && showToast('A network error occurred.'));
  });

  const delBtn = document.getElementById('setting-delete-account');
  delBtn?.addEventListener('click', () => {
    if (typeof showModal !== 'function') return;
    showModal('Delete Account','This will permanently delete your account and all data. This cannot be undone. Continue?', () => {
      fetch('api/delete_account.php', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            sessionStorage.removeItem('loggedInUser');
            if (typeof showToast === 'function') showToast('Account deleted');
            window.location.href='login.html';
          } else {
            if (typeof showToast === 'function') showToast('Error deleting account: ' + data.message);
          }
        })
        .catch(() => typeof showToast === 'function' && showToast('A network error occurred.'));
    });
  });

  const resetBtn = document.getElementById('setting-reset');
  resetBtn?.addEventListener('click', () => {
    if (typeof defaultSettings !== 'function' || typeof saveSettings !== 'function') return;
    localStorage.removeItem('chatAppSettings');
    saveSettings(defaultSettings());
    if (typeof showToast === 'function') showToast('Settings reset');
  });

  const helpBtn = document.getElementById('setting-help');
  helpBtn?.addEventListener('click', () => { window.open('https://example.com/help','_blank'); });

  const feedbackBtn = document.getElementById('setting-feedback');
  feedbackBtn?.addEventListener('click', () => {
    const feedback = prompt('Please enter feedback:');
    if (!feedback) return;
    fetch('api/feedback_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: feedback })
    })
    .then(() => typeof showToast === 'function' && showToast('Thanks for your feedback'))
    .catch((err) => { console.error('Failed to save feedback', err); if (typeof showToast === 'function') showToast('Failed to save feedback'); });
  });

  /* =========================
     6) Upload image helper (NEW)
     ========================= */
  async function uploadImage(file) {
    // currentChatId must be set globally by your app (it is in your code)
    const fd = new FormData();
    fd.append('image', file);

    const res = await fetch(
      'api/chat_api.php?action=upload_image&chat_id=' + encodeURIComponent(window.currentChatId),
      { method: 'POST', body: fd }
    );
    const data = await res.json();

    if (data.success) {
      // immediate UI feedback; message is also persisted in DB by the API
      if (typeof addImageToChat === 'function') {
        addImageToChat('user', data.url);
      }
    } else {
      if (typeof showToast === 'function') showToast(data.error || 'Image upload failed');
      else alert(data.error || 'Image upload failed');
    }
  }
});
</script>

</body>
</html>