<?php
require_once '../database.php'; // Your DB connection providing $conn

session_start();

// Security headers to prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: /maheshwari/login.php");
    exit;
}

include 'sidebar.php'; // Sidebar included for consistent sidebar and styles

// Error and success messages
$error = '';
$success = '';
$mode = 'existing'; // Default mode
$form = [];
$institutions = [];

// Fetch institutions for dropdown
try {
    $res = $conn->query("SELECT id, name FROM institutions ORDER BY name ASC");
    while ($row = $res->fetch_assoc()) {
        $institutions[] = $row;
    }
} catch (Exception $e) {
    $error = "Error loading institutions: " . htmlspecialchars($e->getMessage());
}

// Helper function for inserting admin
function insertAdmin($conn, $institution_id, $name, $email, $phone, $password, $department, &$error) {
    $stmt = $conn->prepare("INSERT INTO institution_admins (institution_id, name, email, phone, password, department, created_at, approved_by) VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)");
    if (!$stmt) {
        $error = "Prepare failed: " . htmlspecialchars($conn->error);
        return false;
    }
    $instId = (int)$institution_id;
    $stmt->bind_param("isssss", $instId, $name, $email, $phone, $password, $department);
    if (!$stmt->execute()) {
        $error = "Execute failed: " . htmlspecialchars($stmt->error);
        $stmt->close();
        return false;
    }
    $stmt->close();
    return true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'existing';
    $form = $_POST;

    if ($mode === 'existing') {
        // Adding admin to existing institution
        $institution_id = $_POST['institution_id'] ?? null;
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if (!$institution_id || !$name || !$email || !$phone || !$password || !$department) {
            $error = "All fields are required for adding an admin to existing institution.";
        } else {
            $successInsert = insertAdmin($conn, $institution_id, $name, $email, $phone, $password, $department, $error);
            if ($successInsert) {
                $success = "Admin added successfully to existing institution.";
                $form = []; // reset form
            }
        }
    } elseif ($mode === 'new-manual') {
        // Adding new institution and admin manually
        $college_name = trim($_POST['name'] ?? '');
        $college_code = trim($_POST['code'] ?? '');
        $admin_name = trim($_POST['admin_name'] ?? '');
        $admin_email = trim($_POST['admin_email'] ?? '');
        $admin_phone = trim($_POST['admin_phone'] ?? '');
        $admin_password = trim($_POST['admin_password'] ?? '');
        $admin_department = trim($_POST['admin_department'] ?? '');

        if (!$college_name || !$college_code || !$admin_name || !$admin_email || !$admin_phone || !$admin_password || !$admin_department) {
            $error = "All fields are required for adding new institution and admin manually.";
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO institutions (name, code, created_at) VALUES (?, ?, NOW())");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("ss", $college_name, $college_code);
                $stmt->execute();
                $institution_id = $stmt->insert_id;
                $stmt->close();

                $successInsert = insertAdmin($conn, $institution_id, $admin_name, $admin_email, $admin_phone, $admin_password, $admin_department, $err);
                if (!$successInsert) {
                    throw new Exception($err);
                }

                $conn->commit();
                $success = "New institution and admin added successfully.";
                $form = []; // reset form
            } catch (Exception $ex) {
                $conn->rollback();
                $error = "Failed to add new institution and admin: " . htmlspecialchars($ex->getMessage());
            }
        }
    } elseif ($mode === 'new-excel') {
        // Process Excel upload using simple CSV fallback if PhpSpreadsheet cannot be used
        if (empty($_POST['name']) || empty($_POST['code']) || !isset($_FILES['excel_file'])) {
            $error = "Institution name, code and Excel file are required.";
        } elseif ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $error = "Error uploading Excel file.";
        } else {
            $college_name = trim($_POST['name']);
            $college_code = trim($_POST['code']);
            $tmpName = $_FILES['excel_file']['tmp_name'];
            $fileType = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);

            // Support CSV as fallback for Excel upload if PhpSpreadsheet not available
            if (in_array(strtolower($fileType), ['csv'])) {
                $handle = fopen($tmpName, 'r');
                if ($handle === false) {
                    $error = "Failed to open uploaded file.";
                } else {
                    $header = fgetcsv($handle);
                    if (!$header) {
                        $error = "CSV file is empty.";
                    } else {
                        $headers = array_map('strtolower', array_map('trim', $header));
                        $expectedHeaders = ['name', 'email', 'phone', 'password', 'department'];
                        foreach ($expectedHeaders as $expect) {
                            if (!in_array($expect, $headers)) {
                                $error = "CSV missing required column: $expect.";
                                break;
                            }
                        }
                        if (!$error) {
                            $colIndex = array_flip($headers);

                            $conn->begin_transaction();
                            try {
                                // Insert institution
                                $stmt = $conn->prepare("INSERT INTO institutions (name, code, created_at) VALUES (?, ?, NOW())");
                                if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
                                $stmt->bind_param("ss", $college_name, $college_code);
                                $stmt->execute();
                                $institution_id = $stmt->insert_id;
                                $stmt->close();

                                if (empty($institution_id) || !$institution_id) {
                                    throw new Exception("Institution was not inserted or ID is missing!");
                                }

                                // Prepare for admin insert once
                                $stmt = $conn->prepare("INSERT INTO institution_admins (institution_id, name, email, phone, password, department, created_at, approved_by) VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)");
                                if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

                                while (($row = fgetcsv($handle)) !== false) {
                                    $name = $row[$colIndex['name']] ?? '';
                                    $email = $row[$colIndex['email']] ?? '';
                                    $phone = $row[$colIndex['phone']] ?? '';
                                    $password = $row[$colIndex['password']] ?? '';
                                    $department = $row[$colIndex['department']] ?? '';

                                    if (!$name || !$email || !$phone || !$password || !$department) {
                                        continue; // skip incomplete row
                                    }
                                    $instId = (int)$institution_id;
                                    $stmt->bind_param("isssss", $instId, $name, $email, $phone, $password, $department);
                                    $stmt->execute();
                                }
                                $stmt->close();
                                $conn->commit();
                                $success = "Institution and admins added successfully from CSV.";
                                $form = [];
                            } catch (Exception $ex) {
                                fclose($handle);
                                $conn->rollback();
                                $error = "Failed to add data from CSV: " . htmlspecialchars($ex->getMessage());
                            }
                            fclose($handle);
                        }
                    }
                }
            } else {
                $error = "Unsupported file type. Please upload a CSV file.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Add Institution / Admin - Super Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    display: flex;
}

/* Sidebar CSS in sidebar.php */

main.content {
    margin-left: 240px;
    padding: 24px;
    flex: 1;
    min-height: 100vh;
    background: #fff7ed;
    color: #374151;
}

h2 {
    color: #c2410c;
    font-size: 1.5rem;
    margin-bottom: 16px;
}

form {
    max-width: 600px;
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 3px 6px rgb(0 0 0 / 0.1);
}

label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 12px;
    cursor: pointer;
    font-weight: 600;
}

input[type="text"], input[type="email"], input[type="password"], select, input[type="file"] {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #f97316;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 1rem;
}

input[type="radio"] {
    width: auto;
    cursor: pointer;
}

button {
    width: 100%;
    background: #f97316;
    color: white;
    font-weight: 700;
    padding: 12px 0;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    transition: background-color 0.3s ease;
}

button:hover {
    background: #c2410c;
}

.alert {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 16px;
}

.alert.error {
    background: #fee2e2;
    color: #b91c1c;
}

.alert.success {
    background: #d1fae5;
    color: #065f46;
}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="content">
<h2>Add Institution / Admin</h2>

<?php if ($error): ?>
<div class="alert error"><?= $error ?></div>
<?php elseif ($success): ?>
<div class="alert success"><?= $success ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<label>
<input type="radio" name="mode" value="existing" <?= $mode === 'existing' ? 'checked' : '' ?> onchange="this.form.submit()" />
Add Admin to Existing Institution
</label>

<label>
<input type="radio" name="mode" value="new-manual" <?= $mode === 'new-manual' ? 'checked' : '' ?> onchange="this.form.submit()" />
Add New Institution & Admin (Manual)
</label>

<label>
<input type="radio" name="mode" value="new-excel" <?= $mode === 'new-excel' ? 'checked' : '' ?> onchange="this.form.submit()" />
Add New Institution & Admins (Excel/CSV Upload)
</label>

<?php if ($mode === 'existing'): ?>
<label for="institution_id">Select Institution</label>
<select name="institution_id" id="institution_id" required>
<option value="">-- Select Institution --</option>
<?php foreach ($institutions as $inst): ?>
<option value="<?= $inst['id'] ?>" <?= (isset($form['institution_id']) && $form['institution_id'] == $inst['id']) ? 'selected' : '' ?>>
<?= htmlspecialchars($inst['name']) ?>
</option>
<?php endforeach; ?>
</select>

<input type="text" name="name" placeholder="Admin Name" value="<?= htmlspecialchars($form['name'] ?? '') ?>" required />
<input type="email" name="email" placeholder="Admin Email" value="<?= htmlspecialchars($form['email'] ?? '') ?>" required />
<input type="text" name="phone" placeholder="Admin Phone" value="<?= htmlspecialchars($form['phone'] ?? '') ?>" required />
<input type="password" name="password" placeholder="Admin Password" required />
<input type="text" name="department" placeholder="Department" value="<?= htmlspecialchars($form['department'] ?? '') ?>" required />

<?php elseif ($mode === 'new-manual'): ?>
<input type="text" name="name" placeholder="Institution Name" value="<?= htmlspecialchars($form['name'] ?? '') ?>" required />
<input type="text" name="code" placeholder="Institution Code" value="<?= htmlspecialchars($form['code'] ?? '') ?>" required />

<input type="text" name="admin_name" placeholder="Admin Name" value="<?= htmlspecialchars($form['admin_name'] ?? '') ?>" required />
<input type="email" name="admin_email" placeholder="Admin Email" value="<?= htmlspecialchars($form['admin_email'] ?? '') ?>" required />
<input type="text" name="admin_phone" placeholder="Admin Phone" value="<?= htmlspecialchars($form['admin_phone'] ?? '') ?>" required />
<input type="password" name="admin_password" placeholder="Admin Password" required />
<input type="text" name="admin_department" placeholder="Department" value="<?= htmlspecialchars($form['admin_department'] ?? '') ?>" required />

<?php elseif ($mode === 'new-excel'): ?>
<input type="text" name="name" placeholder="Institution Name" value="<?= htmlspecialchars($form['name'] ?? '') ?>" required />
<input type="text" name="code" placeholder="Institution Code" value="<?= htmlspecialchars($form['code'] ?? '') ?>" required />
<input type="file" name="excel_file" accept=".xls,.xlsx,.csv" required />

<?php endif; ?>

<button type="submit">Submit</button>
</form>
</main>
<script>
// Prevent showing cached page after logout when pressing Back
window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});
</script>

</body>
</html>
