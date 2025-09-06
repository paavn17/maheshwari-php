<?php
session_start();

// Security headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: /maheshwari/login.php");
    exit;
}

require_once '../database.php';

$adminEmail = $_SESSION['email'] ?? '';

// ---- Handle card design save ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_card_design'])) {
    $selected_card_design = intval($_POST['card_design'] ?? 0);
    if ($selected_card_design) {
        $updateStmt = $conn->prepare("UPDATE institution_admins SET card_design = ? WHERE email = ?");
        if (!$updateStmt) { die("Prepare failed (card update): " . $conn->error); }
        $updateStmt->bind_param('is', $selected_card_design, $adminEmail);
        $updateStmt->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ---- Fetch admin info ----
$stmt = $conn->prepare("
    SELECT a.id, a.institution_id, a.department, a.name AS admin_name, a.card_design,
           i.name AS institution_name, i.code AS institution_code, i.logo
    FROM institution_admins a
    JOIN institutions i ON a.institution_id = i.id
    WHERE a.email = ?
");
if (!$stmt) { die("Prepare failed (admin fetch): " . $conn->error); }
$stmt->bind_param('s', $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
if (!$admin) { die("Admin not found."); }

$institution_id   = $admin['institution_id'];
$department       = $admin['department']; // used as class filter for students and shown on dashboard
$current_card     = $admin['card_design'];
$institution_logo = !empty($admin['logo']) ? 'data:image/jpeg;base64,' . base64_encode($admin['logo']) : '';

$error = '';

// ---- Fetch card designs ----
$cardDesigns = [];
$cardStmt = $conn->prepare("SELECT id, name, front_img, back_img FROM card_designs WHERE deleted='No'");
if (!$cardStmt) { die("Prepare failed (card designs): " . $conn->error); }
$cardStmt->execute();
$cardResult = $cardStmt->get_result();
while ($row = $cardResult->fetch_assoc()) {
    $row['front_url'] = !empty($row['front_img']) ? 'data:image/jpeg;base64,' . base64_encode($row['front_img']) : '';
    $row['back_url']  = !empty($row['back_img']) ? 'data:image/jpeg;base64,' . base64_encode($row['back_img']) : '';
    $cardDesigns[] = $row;
}

// ---- Fetch students filtered by institution and class (admin's department) ----
$stmt = $conn->prepare("SELECT * FROM students WHERE institution_id=? AND class=?");
if (!$stmt) {
    $students = [];
    $error = "Failed to fetch students: " . htmlspecialchars($conn->error);
} else {
    $stmt->bind_param('is', $institution_id, $department);
    $stmt->execute();
    $students_result = $stmt->get_result();
    $students = $students_result->fetch_all(MYSQLI_ASSOC);
}

// ---- Compute stats ----
$totalStudents = count($students);
$paid = 0; $noImage = 0;
foreach ($students as $s) {
    if (!empty($s['payment_status']) && strtolower($s['payment_status']) === 'paid') $paid++;
    if (empty($s['profile_pic'])) $noImage++;
}
$unpaid = $totalStudents - $paid;

// ---- Batch counts ----
$batchesMap = [];
foreach ($students as $s) {
    $key = ($s['start_year'] ?? '') . '-' . ($s['end_year'] ?? '');
    if ($key !== '-') $batchesMap[$key] = ($batchesMap[$key] ?? 0) + 1;
}
ksort($batchesMap);

// ---- Gender counts ----
$genderCounts = [];
foreach ($students as $s) {
    $g = $s['gender'] ?? 'Not Specified';
    $genderCounts[$g] = ($genderCounts[$g] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Institute Dashboard</title>
<style>
body { margin:0; font-family:Arial,sans-serif; background:#fefefe; display:flex; height:100vh; overflow:hidden;}
main.content { margin-left:240px; padding:24px; overflow-y:auto; flex-grow:1; background:#fff9f1; min-height:100vh;}
.header { display:flex; align-items:center; margin-bottom:24px; gap:16px;}
.header img { height:56px;width:56px; object-fit:contain;border-radius:8px;box-shadow:0 0 5px rgba(0,0,0,0.1);}
.header h1 { font-size:28px; font-weight:800; color:#c55f11; text-shadow:1px 1px 1px rgba(0,0,0,0.1);}
.header h1 span { font-size:14px; font-weight:500;color:#e98c4a;margin-left:8px;}
.cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:20px; margin-bottom:30px;}
.card { background:#ea953b; color:white; border-radius:12px; padding:20px; box-shadow:0 6px 10px rgba(234,88,12,0.2); transition:transform 0.15s ease-in-out;}
.card:hover {transform:scale(1.05);}
.card .label { font-size:12px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:8px;}
.card .value { font-size:32px; font-weight:900;}
section.stats-section { background:rgba(255,255,255,0.6); border-radius:12px; padding:20px; box-shadow:0 3px 6px rgba(171,108,29,0.1); margin-bottom:24px;}
section.stats-section h3 { font-size:18px; font-weight:700; color:#c55f11; margin-bottom:12px;}
section.stats-section ul { list-style:disc; padding-left:24px; color:#874913; font-size:14px; line-height:1.3;}
section.stats-section ul li { margin-bottom:4px;}
section.stats-section ul li span { font-weight:700;}
#card-design-form {margin-bottom:18px;}
.save-btn {margin-top:16px;background:#c55f11;color:white;padding:8px 20px;font-weight:700;border-radius:8px;border:none; cursor:pointer;}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="content">
  <div class="header">
    <?php if ($institution_logo): ?><img src="<?= htmlspecialchars($institution_logo) ?>" alt="Institution Logo"><?php endif; ?>
    <h1><?= htmlspecialchars($admin['institution_name'] ?? 'Your Institution') ?>
        <span>(<?= htmlspecialchars($admin['institution_code'] ?? 'Code') ?>)</span>
    </h1>
  </div>

  <!-- Card Design Section -->
  <section class="stats-section">
    <h3>Select Card Design</h3>
    <form id="card-design-form" method="post">
      <select name="card_design" id="card_design_select" required>
        <option value="">-- Select Design --</option>
        <?php foreach ($cardDesigns as $d): ?>
          <option value="<?= $d['id'] ?>"<?= ($current_card == $d['id'] ? ' selected' : '') ?>>
            <?= htmlspecialchars($d['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" name="save_card_design" class="save-btn">Save</button>
    </form>
  </section>

  <div class="cards">
    <div class="card" style="background:#f97316;">
      <div class="label">Total Students</div>
      <div class="value"><?= $totalStudents ?></div>
    </div>
    <div class="card" style="background:#fb7185;">
      <div class="label">Missing Photos</div>
      <div class="value"><?= $noImage ?></div>
    </div>
    <div class="card" style="background:#34d399;">
      <div class="label">Paid Students</div>
      <div class="value"><?= $paid ?></div>
    </div>
    <div class="card" style="background:#facc15;">
      <div class="label">Unpaid Students</div>
      <div class="value"><?= $unpaid ?></div>
    </div>
    <div class="card" style="background:#3b82f6;">
      <div class="label">Department</div>
      <div class="value"><?= htmlspecialchars($department ?: 'N/A') ?></div>
    </div>
  </div>

  <section class="stats-section">
    <h3>Batch-wise Student Count</h3>
    <?php if (empty($batchesMap)): ?>
      <p style="color:#c55f11;">No batch data available.</p>
    <?php else: ?>
      <ul>
        <?php foreach ($batchesMap as $b => $c): ?>
          <li><?= htmlspecialchars($b) ?>: <span><?= $c ?></span> student<?= $c != 1 ? 's' : '' ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="stats-section">
    <h3>Gender Distribution</h3>
    <ul>
      <?php foreach ($genderCounts as $g => $c): ?>
        <li><?= htmlspecialchars($g) ?>: <span><?= $c ?></span></li>
      <?php endforeach; ?>
    </ul>
  </section>
</main>
</body>
</html>
