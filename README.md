DigiBarangay — Backend API
The PHP REST API backend for DigiBarangay. Handles resident registration, admin approval workflows, PIN generation, email notifications, and database operations.

🔗 Frontend repository: digibaranggay


Tech Stack

PHP
MySQL
PHPMailer (SMTP email via Gmail)
XAMPP / Apache

s
Features

📋 REST API Endpoints — JSON responses consumed by the React frontend
👤 Resident Registration — Handles new resident sign-up and stores records in MySQL
✅ Admin Approval Workflow — Admins can set resident status to Pending, Accepted, or Rejected
📧 Email Notifications — Sends automated emails via PHPMailer on status changes
🔑 PIN Generation — Generates and stores a secure 4-digit PIN upon account approval
🌐 CORS Configured — Accepts requests from the React frontend at localhost:5173 s