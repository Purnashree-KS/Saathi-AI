# Saathi AI - Context-Aware Chatbot 🤖

**Saathi AI** is a secure, full-stack intelligent chatbot designed to enhance human-AI interaction through adaptive, context-aware communication. Unlike static models, it utilizes a custom **Model Control Protocol (MCP)** to dynamically switch between specialized logical modules—**Sentiment Analysis**, **Q/A**, and **Summarization**—based on user intent.

## 🚀 Key Features

### 🧠 Context-Aware Intelligence (MCP)
* **Dynamic Model Switching:** Automatically detects if a user is emotional, asking a fact, or analyzing a document, and switches the AI persona accordingly.
* **Proactive Suggestions:** Intelligently predicts follow-up questions and actions (e.g., "Tell me more", "Related topics").
* **Multi-Model Chaining:** Handles complex requests by combining logic (e.g., acknowledging emotion while performing a task).

### 🗣️ Multimodal Interaction
* **Voice-to-Text & Read Aloud:** Integrated Web Speech API for hands-free interaction.
* **Visual Query:** Upload images for analysis using **Tesseract.js** (Client-side OCR).
* **Document Analysis:** Parse and summarize PDF and Word documents locally using **PDF.js** and **Mammoth.js**.

### 🌐 Real-Time Knowledge
* **Live Web Search:** Integrates **SerpApi** to fetch real-time data (news, stock prices, weather), bridging the knowledge gap of static LLMs.

### 🛡️ Admin & Security
* **Robust Security:** Dynamic **Rate Limiting** and **Content Filtering** (Banned Keywords) to prevent abuse.
* **Admin Dashboard:** A comprehensive panel to monitor chat logs, manage users, view analytics, and configure security settings in real-time.
* **Secure Auth:** Role-based authentication (User/Admin) with encrypted sessions and password hashing.

---

## 🛠️ Tech Stack

* **Frontend:** HTML5, Tailwind CSS, Vanilla JavaScript
* **Backend:** Native PHP
* **Database:** MySQL (Relational)
* **AI Engine:** Google Gemini API (`gemini-2.5-flash-lite`)
* **Search Engine:** Google Search via SerpApi
* **Libraries:** Tesseract.js (OCR), PDF.js (PDF Parsing), Mammoth.js (Docx), Chart.js (Analytics)

---

## ⚙️ Installation & Setup
### Prerequisites
* **XAMPP** (or any PHP/Apache/MySQL environment).
* **Composer** (Optional, if adding more PHP packages).
* API Keys for **Google Gemini** and **SerpApi**.

## Step 1: Clone the Repository
git clone [https://github.com/your-username/saathi-ai.git](https://github.com/your-username/saathi-ai.git)
cd saathi-ai

## Step 2: Database Setup
Open phpMyAdmin (http://localhost/phpmyadmin).
Create a new database named saathi_db.
Import the database.sql file located in the root directory of this repo.

## Step 3: Configure Database Connection
Open db_connect.php.

Update the credentials if your local setup differs:

PHP

$servername = "localhost";

$username = "root";

$password = ""; // Your MySQL password

$dbname = "saathi_db";

## Step 4: Add API Keys 🔑
Note: Never commit your actual API keys to GitHub.

Frontend (Gemini API):

Open index.php.

Find const API_KEY = "YOUR_GEMINI_API_KEY"; and replace it with your key from Google AI Studio.

Backend (SerpApi):

Open api/chat_api.php.

Find $serp_api_key = "YOUR_SERP_API_KEY"; and replace it with your key from SerpApi.

## Step 5: Run the Application
Move the project folder to your htdocs directory (e.g., C:\xampp\htdocs\saathi-ai).

Start Apache and MySQL in XAMPP Control Panel.

Open your browser and go to: http://localhost/saathi-ai/login.html
