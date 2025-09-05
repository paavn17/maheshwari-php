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

include 'sidebar.php'; // Sidebar is fixed on left with scrolling

// Fetch dashboard data
$totalInstitutions = 0;
$totalStudents = 0;
$totalWithNoImage = 0;
$institutions = [];

try {
    // Total institutions count
    $res = $conn->query("SELECT COUNT(*) AS count FROM institutions");
    if ($row = $res->fetch_assoc()) {
        $totalInstitutions = (int)$row['count'];
    }

    // Total students count
    $res = $conn->query("SELECT COUNT(*) AS count FROM students");
    if ($row = $res->fetch_assoc()) {
        $totalStudents = (int)$row['count'];
    }

    // Students without image (profile_pic is NULL or empty)
    $res = $conn->query("SELECT COUNT(*) AS count FROM students WHERE (profile_pic IS NULL OR LENGTH(profile_pic) = 0)");
    if ($row = $res->fetch_assoc()) {
        $totalWithNoImage = (int)$row['count'];
    }

    // Fetch institution list (id, name, code)
    $res = $conn->query("SELECT id, name, code FROM institutions ORDER BY name ASC");
    while ($inst = $res->fetch_assoc()) {
        $institutions[] = $inst;
    }
} catch (Exception $e) {
    // Handle error (in production, log error)
    echo "Error fetching dashboard data: " . htmlspecialchars($e->getMessage());
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Super Admin Dashboard - Maheshwari ID Cards</title>
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
  .stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
  }
  @media(min-width: 640px) {
    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  @media(min-width: 1024px) {
    .stats-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }
  .card {
    padding: 16px;
    border-radius: 12px;
    color: white;
    font-weight: 700;
  }
  .bg-orange-500 { background-color: #f97316; }
  .bg-orange-400 { background-color: #fb923c; }
  .bg-red-400 { background-color: #f87171; }
  .label {
    font-size: 0.75rem;
    text-transform: uppercase;
    margin-bottom: 8px;
  }
  .value {
    font-size: 2rem;
  }
  /* Institutions list */
  .institutions {
    margin-top: 48px;
  }
  .institutions h3 {
    color: #c2410c;
    font-size: 1.25rem;
    margin-bottom: 12px;
  }
  .institutions ul {
    list-style-type: disc;
    padding-left: 1.5rem;
    color: #374151;
  }
  .institutions ul li {
    margin-bottom: 6px;
  }
  .code {
    color: #6b7280;
    font-size: 0.875rem;
    margin-left: 8px;
  }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="content">
  <h1>Super Admin Dashboard</h1>

  <div class="stats-grid">
    <div class="card bg-orange-500">
      <div class="label">Total Institutions</div>
      <div class="value"><?= $totalInstitutions ?></div>
    </div>
    <div class="card bg-orange-400">
      <div class="label">Total Students</div>
      <div class="value"><?= $totalStudents ?></div>
    </div>
    <div class="card bg-red-400">
      <div class="label">Students Without Image</div>
      <div class="value"><?= $totalWithNoImage ?></div>
    </div>
  </div>

  <section class="institutions">
    <h3>Institutions</h3>
    <ul>
      <?php foreach ($institutions as $inst): ?>
        <li><?= htmlspecialchars($inst['name']) ?> <span class="code">(<?= htmlspecialchars($inst['code']) ?>)</span></li>
      <?php endforeach; ?>
    </ul>
  </section>
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
