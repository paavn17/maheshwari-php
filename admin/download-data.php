<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: /maheshwari/login.php");
    exit;
}

require_once '../database.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

// Handle CSV download request before any output
if (isset($_POST['download_csv']) && $_POST['download_csv'] === '1') {
    // Get posted filters
    $inst = $_POST['institution_id'] ?? '';
    $batch = $_POST['batch'] ?? '';
    $cls = trim($_POST['class'] ?? '');

    if (!$inst || !$batch) {
        exit("Institution and batch are required for download.");
    }

    [$startYear, $endYear] = explode("-", $batch);

    $query = "SELECT name, father_name, roll_no, class, section, start_year, end_year, mobile, email, gender, dob, blood_group, adhaar_no, address FROM students WHERE institution_id=? AND start_year=? AND end_year=?";
    $types = "iss";
    $params = [$inst, $startYear, $endYear];

    if ($cls !== '') {
        $query .= " AND class LIKE ?";
        $types .= "s";
        $params[] = "%$cls%";
    }

    $query .= " ORDER BY roll_no ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // Prepare CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_export.csv');

    $output = fopen('php://output', 'w');

    // Column headers (excluding photo)
    fputcsv($output, ['Name','Father Name','Roll No','Class','Section','Start Year','End Year','Mobile','Email','Gender','DOB','Blood Group','Adhaar No','Address']);

    while ($row = $result->fetch_assoc()) {
        // Sanitize line values
        $line = [
            $row['name'],
            $row['father_name'],
            $row['roll_no'],
            $row['class'],
            $row['section'],
            $row['start_year'],
            $row['end_year'],
            $row['mobile'],
            $row['email'],
            $row['gender'],
            $row['dob'],
            $row['blood_group'],
            $row['adhaar_no'],
            $row['address'],
        ];
        fputcsv($output, $line);
    }
    fclose($output);
    exit;
}

// Fetch all institutions for dropdown
$institutions = [];
$res = $conn->query("SELECT id, name FROM institutions ORDER BY name ASC");
while ($row = $res->fetch_assoc()) {
    $institutions[] = $row;
}

// Read POST or default values
$selectedInstitution = $_POST['institution_id'] ?? '';
$selectedBatch = $_POST['batch'] ?? '';
$searchClass = trim($_POST['class'] ?? '');

// Fetch batches for chosen institution
$batches = [];
if ($selectedInstitution) {
    $stmtBatches = $conn->prepare("SELECT DISTINCT start_year, end_year FROM students WHERE institution_id=? ORDER BY start_year, end_year");
    $stmtBatches->bind_param('i', $selectedInstitution);
    $stmtBatches->execute();
    $resultBatches = $stmtBatches->get_result();
    while ($row = $resultBatches->fetch_assoc()) {
        if ($row['start_year']) {
            $batches[] = $row['start_year']."-".$row['end_year'];
        }
    }
}

// Fetch students according to filters
$students = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedInstitution && $selectedBatch) {
    $query = "SELECT name, father_name, roll_no, class, section, start_year, end_year, mobile, email, gender, dob, blood_group, adhaar_no, address, profile_pic 
              FROM students WHERE institution_id=? AND start_year=? AND end_year=?";
    $params = [];
    $types = "iss";
    $params[] = $selectedInstitution;
    [$startYear, $endYear] = explode("-", $selectedBatch);
    $params[] = $startYear;
    $params[] = $endYear;

    if ($searchClass !== '') {
        $query .= " AND class LIKE ?";
        $types .= "s";
        $params[] = "%".$searchClass."%";
    }

    $query .= " ORDER BY roll_no ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (!empty($row['profile_pic'])) {
            if (strlen($row['profile_pic']) > 20) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($row['profile_pic']);
                if (strpos($mimeType, 'image/') === 0) {
                    $row['profile_pic'] = "data:$mimeType;base64," . base64_encode($row['profile_pic']);
                } else {
                    $row['profile_pic'] = null;
                }
            } else {
                $row['profile_pic'] = null;
            }
        } else {
            $row['profile_pic'] = null;
        }
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Download Students Data</title>
<style>
body {
    display: flex;
    min-height: 100vh;
    background-color: #f3f4f6;
    margin: 0;
    font-family: Arial, sans-serif;
}
aside.sidebar {
    width: 15rem;
    position: fixed;
    left: 0;
    top: 0;
    height: 100%;
    background-color: #fff;
    border-right: 1px solid #e5e7eb;
    overflow-y: auto;
    padding-top: 1rem;
}
main.content {
    margin-left: 16rem;
    flex-grow: 1;
    padding: 2rem;
    box-sizing: border-box;
    min-height: 100vh;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 6px rgba(0,0,0,0.1);
}
h1.page-header {
    font-size: 1.875rem;
    font-weight: 700;
    color: #c2410c;
    margin-bottom: 1.5rem;
    text-align: center;
}
form {
    max-width: 900px;
    margin: 0 auto 1rem auto;
    background:#fff9f1;
    padding:20px;
    border-radius:10px;
    box-shadow:0 4px 10px rgba(234,88,12,0.2);
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: flex-end;
}
select, input[type=text], button {
    flex: 1 1 180px;
    font-size: 1rem;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid #f97316;
}
button {
    background:#c55f11;
    color:white;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.3s;
}
button:hover {
    background-color: #a43f0a;
}
table {
    border-collapse: collapse;
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
    table-layout: fixed;
    overflow-x: auto;
    display: block;
    white-space: nowrap;
}
th, td {
    border: 1px solid #ccc;
    padding: 12px 16px;
    vertical-align: middle;
    word-wrap: break-word;
    min-width: 140px;
}
th {
    background:#f97316;
    color:white;
    text-transform: uppercase;
    font-weight: 700;
    position: sticky;
    top: 0;
    z-index: 5;
}
.student-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #ddd;
}
.message {
    max-width: 900px;
    margin: 5px auto 10px;
    font-weight: 600;
    color: #c2410c;
    text-align: center;
}
@media (max-width: 768px) {
    aside.sidebar { display: none; }
    main.content { margin-left: 0; padding: 1rem; }
    form { flex-direction: column; align-items: stretch; }
    select, input[type=text], button { flex: none; width: 100%; }
    table { display: block; white-space: nowrap; overflow-x: auto; }
}
</style>
</head>
<body>
<aside class="sidebar">
    <?php include 'sidebar.php'; ?>
</aside>
<main class="content">
    <h1 class="page-header">Download Students Data</h1>
    <form method="post" id="filterForm">
        <select name="institution_id" id="institutionSelect" onchange="document.getElementById('filterForm').submit()" required>
            <option value="">-- Select Institution --</option>
            <?php foreach ($institutions as $inst): ?>
                <option value="<?= htmlspecialchars($inst['id']) ?>" <?= $selectedInstitution == $inst['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($inst['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($selectedInstitution): ?>
            <?php if (empty($batches)): ?>
                <div class="message">No batches available for this institution.</div>
            <?php else: ?>
                <select name="batch" id="batchSelect" onchange="document.getElementById('filterForm').submit()" required>
                    <option value="">-- Select Batch --</option>
                    <?php foreach ($batches as $batch): ?>
                        <option value="<?= htmlspecialchars($batch) ?>" <?= $selectedBatch == $batch ? 'selected' : '' ?>>
                            <?= htmlspecialchars($batch) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        <?php else: ?>
            <select name="batch" disabled>
                <option>-- Select Batch --</option>
            </select>
        <?php endif; ?>

        <?php if ($selectedInstitution && $selectedBatch && !empty($batches)): ?>
            <button type="submit" name="download_csv" value="1">Download CSV</button>
        <?php else: ?>
            <input type="text" disabled placeholder="Search by Class (optional)" />
            <button type="submit" disabled>Go</button>
            <button type="submit" disabled>Download CSV</button>
        <?php endif; ?>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedInstitution && $selectedBatch): ?>
        <?php if (empty($students)): ?>
            <p class="message">No students found for selected filters.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Father Name</th>
                        <th>Roll No</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Start Year</th>
                        <th>End Year</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Date of Birth</th>
                        <th>Blood Group</th>
                        <th>Adhaar No</th>
                        <th>Address</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($students as $stu): ?>
                    <tr>
                        <td>
                            <?php if ($stu['profile_pic']): ?>
                                <img src="<?= $stu['profile_pic'] ?>" alt="Photo" class="student-pic" />
                            <?php else: ?>
                                <span style="color:#ccc;">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($stu['name']) ?></td>
                        <td><?= htmlspecialchars($stu['father_name']) ?></td>
                        <td><?= htmlspecialchars($stu['roll_no']) ?></td>
                        <td><?= htmlspecialchars($stu['class']) ?></td>
                        <td><?= htmlspecialchars($stu['section']) ?></td>
                        <td><?= htmlspecialchars($stu['start_year']) ?></td>
                        <td><?= htmlspecialchars($stu['end_year']) ?></td>
                        <td><?= htmlspecialchars($stu['mobile']) ?></td>
                        <td><?= htmlspecialchars($stu['email']) ?></td>
                        <td><?= htmlspecialchars($stu['gender']) ?></td>
                        <td><?= htmlspecialchars($stu['dob']) ?></td>
                        <td><?= htmlspecialchars($stu['blood_group']) ?></td>
                        <td><?= htmlspecialchars($stu['adhaar_no']) ?></td>
                        <td><?= nl2br(htmlspecialchars($stu['address'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</main>
</body>
</html>
