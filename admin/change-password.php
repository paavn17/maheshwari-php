<?php
session_start();

// Security headers to prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Require DB connection
require_once '../database.php';

// If user not logged in, redirect
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: /maheshwari/login.php");
    exit;
}

// Fetch the single super admin
$stmt = $conn->prepare("SELECT id, email, password FROM super_admins LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    die("Admin user not found.");
}

$status = "";
$status_class = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$current_password || !$new_password || !$confirm_password) {
        $status = "❌ All fields are required.";
        $status_class = "error";
    } else if ($new_password !== $confirm_password) {
        $status = "❌ New password and confirm password do not match.";
        $status_class = "error";
    } else if ($current_password !== $admin['password']) {
        // Plain text comparison
        $status = "❌ Current password is incorrect.";
        $status_class = "error";
    } else {
        // Hash the new password for future security
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE super_admins SET password=? WHERE id=?");
        $update->bind_param("si", $hashed, $admin['id']);
        if ($update->execute()) {
            $status = "✅ Password updated successfully.";
            $status_class = "success";
        } else {
            $status = "❌ Failed to update password.";
            $status_class = "error";
        }
        $update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Change Password</title>
<style>
body {
    display: flex;
    min-height: 100vh;
    margin: 0;
    font-family: Arial, sans-serif;
    background: #fffaf0;
}
aside.sidebar {
    width: 15rem;
    position: fixed;
    left: 0;
    top: 0;
    height: 100%;
    overflow-y: auto;
    padding-top: 1rem;
}
main.content {
    margin-left: 16rem;
    flex-grow: 1;
    padding: 2rem;
    box-sizing: border-box;
    min-height: 100vh;
    border-radius: 8px;
    box-shadow: 0 1px 6px rgba(0,0,0,0.1);
    max-width: 480px;
    margin-right: auto;
    margin-left: auto;
    background: #fff9f1;
}
h1.page-header {
    font-size: 1.875rem;
    font-weight: 700;
    color: #c2410c;
    margin-bottom: 1.5rem;
    text-align: center;
}
form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
label {
    font-weight: 600;
    color: #c2410c;
}
input[type="password"] {
    padding: 10px 14px;
    font-size: 1rem;
    border-radius: 8px;
    border: 1px solid #f97316;
    color: #374151;
    width: 100%;
    box-sizing: border-box;
}
button {
    background: #c55f11;
    color: white;
    font-weight: 700;
    padding: 12px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s;
}
button:hover {
    background-color: #a43f0a;
}
.status {
    font-weight: 600;
    padding: 12px;
    border-radius: 8px;
    text-align: center;
}
.status.error {
    background: #fee2e2;
    color: #b91c1c;
}
.status.success {
    background: #d1fae5;
    color: #065f46;
}
</style>
</head>
<body>
<aside class="sidebar">
<?php include 'sidebar.php'; ?>
</aside>

<main class="content">
<h1 class="page-header">Change Password</h1>

<?php if ($status): ?>
    <div class="status <?= htmlspecialchars($status_class) ?>"><?= htmlspecialchars($status) ?></div>
<?php endif; ?>

<form method="post" novalidate>
    <label for="current_password">Current Password</label>
    <input type="password" id="current_password" name="current_password" required autocomplete="current-password" />

    <label for="new_password">New Password</label>
    <input type="password" id="new_password" name="new_password" required autocomplete="new-password" />

    <label for="confirm_password">Confirm New Password</label>
    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" />

    <button type="submit">Update Password</button>
</form>
</main>
</body>
</html>
