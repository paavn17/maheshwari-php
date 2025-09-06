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

// Function to process profile picture
function processProfilePicture($profilePicData) {
    if (empty($profilePicData)) {
        return null;
    }

    // If it's already a data URL, return it
    if (strpos($profilePicData, 'data:') === 0) {
        return $profilePicData;
    }

    // If it's a file path, try to read the file
    if (strlen($profilePicData) < 500 && (strpos($profilePicData, '/') !== false || strpos($profilePicData, '\\') !== false)) {
        // It looks like a file path
        if (file_exists($profilePicData)) {
            $imageData = file_get_contents($profilePicData);
            if ($imageData !== false) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($imageData);
                if (strpos($mimeType, 'image/') === 0) {
                    return "data:$mimeType;base64," . base64_encode($imageData);
                }
            }
        }
        return null;
    }

    // If it's binary data, process it
    if (strlen($profilePicData) > 100) {
        // Try to detect if it's valid image data
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($profilePicData);
        
        if (strpos($mimeType, 'image/') === 0) {
            return "data:$mimeType;base64," . base64_encode($profilePicData);
        }
    }

    return null;
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
        // Process profile picture
        $row['profile_pic'] = processProfilePicture($row['profile_pic']);
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
.table-container {
    max-width: 900px;
    margin: 0 auto;
    overflow-x: auto;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(234,88,12,0.2);
}
table {
    border-collapse: collapse;
    width: 100%;
    min-width: 1200px;
}
th, td {
    border: 1px solid #ccc;
    padding: 12px 16px;
    vertical-align: middle;
    word-wrap: break-word;
    text-align: left;
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
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #f97316;
    display: block;
    margin: 0 auto;
}
.no-image {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #f3f4f6;
    border: 2px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 12px;
    margin: 0 auto;
}
.message {
    max-width: 900px;
    margin: 5px auto 10px;
    font-weight: 600;
    color: #c2410c;
    text-align: center;
}
.photo-col {
    width: 80px;
    text-align: center;
}
.name-col {
    min-width: 150px;
}
.roll-col {
    width: 100px;
}
.class-col {
    width: 80px;
}
.section-col {
    width: 80px;
}
.year-col {
    width: 100px;
}
.mobile-col {
    width: 120px;
}
.email-col {
    min-width: 200px;
}
.gender-col {
    width: 80px;
}
.dob-col {
    width: 120px;
}
.blood-col {
    width: 100px;
}
.adhaar-col {
    width: 150px;
}
.address-col {
    min-width: 200px;
}

/* Image loading error handling */
.student-pic[src=""], .student-pic:not([src]) {
    display: none;
}

@media (max-width: 768px) {
    aside.sidebar { display: none; }
    main.content { margin-left: 0; padding: 1rem; }
    form { flex-direction: column; align-items: stretch; }
    select, input[type=text], button { flex: none; width: 100%; }
    .table-container { margin: 0 -1rem; }
    table { min-width: 800px; }
    th, td { padding: 8px; font-size: 14px; }
    .student-pic, .no-image { width: 40px; height: 40px; }
}
</style>
<script>
// Handle image loading errors
function handleImageError(img) {
    img.style.display = 'none';
    var noImageDiv = img.nextElementSibling;
    if (noImageDiv && noImageDiv.classList.contains('no-image')) {
        noImageDiv.style.display = 'flex';
    } else {
        var newNoImageDiv = document.createElement('div');
        newNoImageDiv.className = 'no-image';
        newNoImageDiv.innerHTML = 'No Image';
        newNoImageDiv.style.display = 'flex';
        img.parentNode.appendChild(newNoImageDiv);
    }
}

// Show loading placeholder while image loads
function handleImageLoad(img) {
    img.style.display = 'block';
    var noImageDiv = img.nextElementSibling;
    if (noImageDiv && noImageDiv.classList.contains('no-image')) {
        noImageDiv.style.display = 'none';
    }
}
</script>
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
                
                <?php if ($selectedBatch): ?>
                    <input type="text" name="class" value="<?= htmlspecialchars($searchClass) ?>" placeholder="Search by Class (optional)" />
                    <button type="submit">Filter Results</button>
                <?php endif; ?>
            <?php endif; ?>
        <?php else: ?>
            <select name="batch" disabled>
                <option>-- Select Batch --</option>
            </select>
            <input type="text" disabled placeholder="Search by Class (optional)" />
            <button type="submit" disabled>Filter Results</button>
        <?php endif; ?>

        <?php if ($selectedInstitution && $selectedBatch && !empty($batches)): ?>
            <button type="submit" name="download_csv" value="1">Download CSV</button>
        <?php else: ?>
            <button type="submit" disabled>Download CSV</button>
        <?php endif; ?>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedInstitution && $selectedBatch && !isset($_POST['download_csv'])): ?>
        <?php if (empty($students)): ?>
            <p class="message">No students found for selected filters.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th class="photo-col">Photo</th>
                            <th class="name-col">Name</th>
                            <th class="name-col">Father Name</th>
                            <th class="roll-col">Roll No</th>
                            <th class="class-col">Class</th>
                            <th class="section-col">Section</th>
                            <th class="year-col">Start Year</th>
                            <th class="year-col">End Year</th>
                            <th class="mobile-col">Mobile</th>
                            <th class="email-col">Email</th>
                            <th class="gender-col">Gender</th>
                            <th class="dob-col">Date of Birth</th>
                            <th class="blood-col">Blood Group</th>
                            <th class="adhaar-col">Adhaar No</th>
                            <th class="address-col">Address</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($students as $stu): ?>
                        <tr>
                            <td class="photo-col">
                                <?php if (!empty($stu['profile_pic'])): ?>
                                    <img src="<?= htmlspecialchars($stu['profile_pic']) ?>" 
                                         alt="Student Photo" 
                                         class="student-pic" 
                                         onload="handleImageLoad(this)"
                                         onerror="handleImageError(this)" />
                                    <div class="no-image" style="display: none;">No Image</div>
                                <?php else: ?>
                                    <div class="no-image">No Image</div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($stu['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($stu['father_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($stu['roll_no'] ?? '') ?></td>
                            <td><?= htmlspecialchars($stu['class'] ?? '') ?></td>
                            <td><?= htmlspecialchars($stu['section'] ?? '') ?></td>
                            <td><?= htmlspecialchars($stu['start_year'] ?? '') ?></td>
                            <td><?= htmlspecialchars($stu['end_year'] ?? '') ?></td>
                            <td><?= htmlspecialchars($stu['mobile'] ?? '') ?></td>
                            <td><?= htmlspecialchars($stu['email'] ?? '') ?></td>
                            <td><?= htmlspecialchars($stu['gender'] ?? '') ?></td>
                            <td><?= htmlspecialchars($stu['dob'] ?? '') ?></td>
                            <td><?= htmlspecialchars($stu['blood_group'] ?? '') ?></td>
                            <td><?= htmlspecialchars($stu['adhaar_no'] ?? '') ?></td>
                            <td><?= nl2br(htmlspecialchars($stu['address'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>
</body>
</html>