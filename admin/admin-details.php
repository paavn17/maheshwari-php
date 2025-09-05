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


include 'sidebar.php'; // Sidebar is fixed with scrolling

// Initialize variables
$admins = [];
$error = '';

// Fetch admin details with institution info (no update/delete yet)
try {
    $sql = "SELECT ia.id, ia.name, ia.email, ia.phone, ia.password, ia.department,
                   i.code AS college_code,
                   i.logo AS institution_logo
            FROM institution_admins ia
            LEFT JOIN institutions i ON ia.institution_id = i.id
            ORDER BY i.code, ia.name";
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        // Convert logo blob to base64 or null if no logo
        $row['institution_logo'] = !empty($row['institution_logo'])
            ? 'data:image/png;base64,' . base64_encode($row['institution_logo'])
            : null;
        $admins[] = $row;
    }
} catch (Exception $e) {
    $error = "Error loading admin data: " . htmlspecialchars($e->getMessage());
}

// Group admins by college code
$groupedAdmins = [];
foreach ($admins as $admin) {
    $code = $admin['college_code'] ?? 'N/A';
    if (!isset($groupedAdmins[$code])) {
        $groupedAdmins[$code] = [];
    }
    $groupedAdmins[$code][] = $admin;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Details - Super Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
  body {
    margin: 0;
    font-family: Arial, sans-serif;
    display: flex;
  }
  /* Sidebar CSS is in sidebar.php */

  main.content {
    margin-left: 240px; /* Width of sidebar */
    padding: 24px;
    flex: 1;
    min-height: 100vh;
    background: #fff7ed;
  }

  h1 {
    color: #c2410c;
    font-size: 2rem;
    margin-bottom: 24px;
  }

  h2 {
    color: #c2410c;
    font-size: 1.25rem;
    margin-bottom: 12px;
  }

  .error {
    color: #dc2626;
    margin-bottom: 16px;
  }

  .admin-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
    border-radius: 12px;
    overflow: hidden;
  }

  .admin-table thead {
    background-color: #fed7aa;
    color: #7c2d12;
  }

  .admin-table th, .admin-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #f3e9db;
    text-align: left;
  }

  .admin-table tbody tr:hover {
    background: #ffe8cc;
  }

  .logo-img {
    height: 32px;
    width: 32px;
    object-fit: contain;
    border-radius: 4px;
  }

  .no-logo {
    color: #9ca3af;
    font-style: italic;
  }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="content">
  <h1>Admin Details</h1>

  <?php if ($error): ?>
    <p class="error"><?= $error ?></p>
  <?php endif; ?>

  <?php foreach ($groupedAdmins as $collegeCode => $adminsList): ?>
    <section>
      <h2>College Code: <?= htmlspecialchars($collegeCode) ?></h2>
      <div style="overflow-x:auto;">
        <table class="admin-table" role="grid" aria-label="Admin Details Table">
          <thead>
            <tr>
              <th>Logo</th>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Password</th>
              <th>Phone</th>
              <th>Department</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($adminsList as $index => $admin): ?>
              <tr>
                <td>
                  <?php if ($admin['institution_logo']): ?>
                    <img src="<?= $admin['institution_logo'] ?>" alt="Institution Logo" class="logo-img" />
                  <?php else: ?>
                    <span class="no-logo">No Logo</span>
                  <?php endif; ?>
                </td>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($admin['name']) ?></td>
                <td><?= htmlspecialchars($admin['email']) ?></td>
                <td><code><?= htmlspecialchars($admin['password']) ?></code></td>
                <td><?= htmlspecialchars($admin['phone']) ?></td>
                <td><?= !empty($admin['department']) ? htmlspecialchars($admin['department']) : '<span style="color:#9ca3af;">N/A</span>' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endforeach; ?>

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
