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

$uploadError = '';
$uploadSuccess = '';

// For reporting
$insertedCount = 0;
$notInsertedCount = 0;
$notInsertedDetails = [];

// Get all institutions for dropdown
$institutionsStmt = $conn->prepare("SELECT id, name, code FROM institutions ORDER BY name");
if (!$institutionsStmt) {
    die("Prepare failed for institutions query: " . $conn->error);
}
$institutionsStmt->execute();
$institutionsResult = $institutionsStmt->get_result();
if (!$institutionsResult) {
    die("Execute failed for institutions query: " . $institutionsStmt->error);
}
$institutions = $institutionsResult->fetch_all(MYSQLI_ASSOC);

// Handle POST: receive student data and uploaded files per row
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['final_data']) && !empty($_POST['selected_institution'])) {
    $data = json_decode($_POST['final_data'], true);
    $selected_institution_id = (int)$_POST['selected_institution'];
    
    if (!$data) {
        $uploadError = "Invalid data received.";
    } elseif (!$selected_institution_id) {
        $uploadError = "Please select an institution.";
    } else {
        // Verify selected institution exists
        $verifyStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM institutions WHERE id=?");
        $verifyStmt->bind_param('i', $selected_institution_id);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result()->fetch_assoc();
        
        if (!empty($uploadError)) {
            // Error already set above
        } elseif ($verifyResult['cnt'] == 0) {
            $uploadError = "Selected institution not found.";
        } else {
            $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM students WHERE institution_id=? AND roll_no=? AND class=? AND section=?");
            $insertStmt = $conn->prepare("
                INSERT INTO students
                (studid, admin_id, institution_id, name, father_name, roll_no, class, section, start_year, end_year, mobile, email, gender, dob, blood_group, adhaar_no, address, profile_pic)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$checkStmt) {
                $uploadError = "Prepare failed for duplicate check: " . $conn->error;
            } elseif (!$insertStmt) {
                $uploadError = "Prepare failed for insert: " . $conn->error;
            } else {
                foreach ($data as $i => $row) {
                    $name       = $row['name'] ?? '';
                    $fatherName = $row['father_name'] ?? '';
                    $rollNo     = $row['roll_no'] ?? '';
                    $class      = $row['class'] ?? '';
                    $section    = $row['section'] ?? null;
                    $startYear  = $row['start_year'] ?? null;
                    $endYear    = $row['end_year'] ?? null;
                    $mobile     = $row['mobile'] ?? '';
                    $email      = $row['email'] ?? '';
                    $gender     = $row['gender'] ?? '';
                    $dob        = $row['dob'] ?? null;
                    $bloodGroup = $row['blood_group'] ?? '';
                    $adhaarNo   = $row['adhaar_no'] ?? '';
                    $address    = $row['address'] ?? '';

                    // Skip if essential fields are empty
                    if (empty($name) || empty($rollNo) || empty($class)) {
                        $notInsertedCount++;
                        $notInsertedDetails[] = [
                            'roll_no' => $rollNo ?: 'Empty',
                            'reason' => "Missing required fields (name, roll_no, or class)"
                        ];
                        continue;
                    }

                    // Handle uploaded photo per row - store in database
                    $profilePic = null;
                    $fileKey = 'photo_row_' . ($i + 1);
                    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES[$fileKey]['tmp_name'];
                        $imageData = file_get_contents($tmpName);
                        if ($imageData !== false) {
                            $profilePic = base64_encode($imageData);
                        }
                    }

                    // Check duplicate in DB
                    $checkStmt->bind_param('isss', $selected_institution_id, $rollNo, $class, $section);
                    if (!$checkStmt->execute()) {
                        $notInsertedCount++;
                        $notInsertedDetails[] = [
                            'roll_no' => $rollNo,
                            'reason' => "Duplicate check failed: " . $checkStmt->error
                        ];
                        continue;
                    }
                    
                    $checkResult = $checkStmt->get_result();
                    if (!$checkResult) {
                        $notInsertedCount++;
                        $notInsertedDetails[] = [
                            'roll_no' => $rollNo,
                            'reason' => "Duplicate check result failed: " . $checkStmt->error
                        ];
                        continue;
                    }
                    
                    $res = $checkResult->fetch_assoc();
                    if (!$res) {
                        $notInsertedCount++;
                        $notInsertedDetails[] = [
                            'roll_no' => $rollNo,
                            'reason' => "Duplicate check fetch failed"
                        ];
                        continue;
                    }
                    
                    if ($res['cnt'] > 0) {
                        $notInsertedCount++;
                        $notInsertedDetails[] = [
                            'roll_no' => $rollNo,
                            'reason' => "Duplicate roll_no/class/section exists"
                        ];
                        continue;
                    }

                    // Insert row with admin_id = 0 (superadmin)
                    $studid = ''; // temp, update later
                    $admin_id = 0; // Superadmin

                    $insertStmt->bind_param(
                        'siisssssssssssssss',
                        $studid,
                        $admin_id,
                        $selected_institution_id,
                        $name,
                        $fatherName,
                        $rollNo,
                        $class,
                        $section,
                        $startYear,
                        $endYear,
                        $mobile,
                        $email,
                        $gender,
                        $dob,
                        $bloodGroup,
                        $adhaarNo,
                        $address,
                        $profilePic
                    );

                    if (!$insertStmt->execute()) {
                        $notInsertedCount++;
                        $notInsertedDetails[] = [
                            'roll_no' => $rollNo,
                            'reason' => "Insert failed: " . $insertStmt->error
                        ];
                    } else {
                        $insertedCount++;
                        $lastId = $insertStmt->insert_id;
                        $studid = "stud" . $lastId;
                        $updateStmt = $conn->prepare("UPDATE students SET studid=? WHERE id=?");
                        if ($updateStmt) {
                            $updateStmt->bind_param('si', $studid, $lastId);
                            $updateStmt->execute();
                        }
                    }
                }

                // Summary report
                $uploadSuccess = "Students Inserted: $insertedCount. Students Not Inserted: $notInsertedCount.";
                if (!empty($notInsertedDetails)) {
                    $uploadSuccess .= "<br><br>Details of Students Not Inserted:<br><table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; max-width: 900px; margin: 10px auto;'><tr><th>Roll No</th><th>Reason</th></tr>";
                    foreach ($notInsertedDetails as $detail) {
                        $uploadSuccess .= "<tr><td>" . htmlspecialchars($detail['roll_no']) . "</td><td>" . htmlspecialchars($detail['reason']) . "</td></tr>";
                    }
                    $uploadSuccess .= "</table>";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Upload Students Excel + Images (Superadmin)</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
    background:#fff9f1;
    padding:20px;
    border-radius:10px;
    box-shadow:0 4px 10px rgba(234,88,12,0.2);
    max-width: 900px;
    margin: 0 auto 2rem auto;
}
input[type=file], button, select {
    margin: 0; /* Remove bottom margin for file inputs inside table */
    font-size: 1rem;
}
select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    background: white;
    margin-bottom: 15px;
}
button {
    background:#c55f11;
    color:white;
    padding:8px 20px;
    border-radius:8px;
    border:none;
    cursor:pointer;
    font-weight: 700;
    margin-top: 14px; /* Add margin top for submit button */
}
button:disabled {
    background:#ccc;
    cursor:not-allowed;
}
.error {
    color:red;
    margin-bottom:12px;
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
}
.success {
    color:green;
    margin-bottom:12px;
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
}
table {
    border-collapse: collapse;
    width: 100%;
    max-width: 900px;
    margin: 0 auto 1rem auto;
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
    text-align: center;
}
th {
    background:#f97316;
    color:white;
    text-transform: none;
    font-weight: 700;
}
td input[type="text"] {
    width:100%;
    border:none;
    background:transparent;
    font-size: 0.9rem;
}
.file-photo-input {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}
.file-photo-input img {
    width: 60px;
    height: 66px;
    object-fit: contain;
    border-radius: 6px;
    border: 1px solid #ddd;
    display: none; /* Hide initially */
}
.institution-section {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    max-width: 900px;
    margin: 0 auto 2rem auto;
}
.institution-section h3 {
    color: #c2410c;
    margin-bottom: 15px;
    font-size: 1.2rem;
}
@media (max-width: 768px) {
    aside.sidebar {
        display: none;
    }
    main.content {
        margin-left: 0;
        padding: 1rem;
    }
    form, table, .error, .success, .institution-section {
        max-width: 100%;
        margin-left: 0;
        margin-right: 0;
    }
}
</style>
</head>
<body>
<aside class="sidebar">
    <?php include 'sidebar.php'; ?>
</aside>
<main class="content">
    <h1 class="page-header">Upload Students Excel and Images (Superadmin)</h1>

    <?php if($uploadError): ?><div class="error"><?= $uploadError ?></div><?php endif; ?>
    <?php if($uploadSuccess): ?><div class="success"><?= $uploadSuccess ?></div><?php endif; ?>

    <div class="institution-section">
        <h3>Select Institution</h3>
        <select id="institutionSelect" name="institution_id" required>
            <option value="">-- Select Institution --</option>
            <?php if (empty($institutions)): ?>
                <option value="" disabled>No institutions found</option>
            <?php else: ?>
                <?php foreach ($institutions as $institution): ?>
                    <option value="<?= $institution['id'] ?>" <?= (isset($_POST['selected_institution']) && $_POST['selected_institution'] == $institution['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($institution['name']) ?> (<?= htmlspecialchars($institution['code']) ?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <form id="excelForm" method="post" enctype="multipart/form-data" action="" style="display:none;">
        <label for="fileInput">Select Excel File (.xlsx)</label><br>
        <input type="file" id="fileInput" accept=".xlsx, .xls" required><br>
        <input type="hidden" name="final_data" id="final_data">
        <input type="hidden" name="selected_institution" id="selected_institution">
        <button type="submit" id="submitBtn" disabled>Insert Students</button>
    </form>

    <div id="previewDiv"></div>
</main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
const institutionSelect = document.getElementById('institutionSelect');
const fileInput = document.getElementById('fileInput');
const previewDiv = document.getElementById('previewDiv');
const finalDataInput = document.getElementById('final_data');
const selectedInstitutionInput = document.getElementById('selected_institution');
const form = document.getElementById('excelForm');
const submitBtn = document.getElementById('submitBtn');

let previewData = [];

function formatHeader(text) {
    return text.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

// Show/hide form based on institution selection
institutionSelect.addEventListener('change', function(){
    if (this.value) {
        form.style.display = 'block';
        selectedInstitutionInput.value = this.value;
    } else {
        form.style.display = 'none';
        previewDiv.innerHTML = '';
        fileInput.value = '';
        submitBtn.disabled = true;
    }
});

fileInput.addEventListener('change', function(){
    const file = this.files[0];
    if (!file) {
        submitBtn.disabled = true;
        return;
    }

    const reader = new FileReader();
    reader.onload = function(evt){
        const data = evt.target.result;
        const workbook = XLSX.read(data, {type:'binary'});
        const sheetName = workbook.SheetNames[0];
        const sheet = workbook.Sheets[sheetName];
        const rows = XLSX.utils.sheet_to_json(sheet, {header:1});
        if (rows.length < 2) {
            alert("Excel file is empty.");
            submitBtn.disabled = true;
            return;
        }

        let header = rows[0].map(h => h.toString().toLowerCase().trim());
        const required = ['name', 'roll_no', 'class'];
        for(let r of required){
            if (!header.includes(r)) {
                alert("Missing required column: "+r);
                submitBtn.disabled = true;
                return;
            }
        }

        const rollIndex = header.indexOf('roll_no');
        let headerWithoutRoll = header.filter((h,i) => i !== rollIndex);

        previewData = [];
        let html = "<table><thead><tr>";
        html += "<th>Photo</th>";
        html += `<th>${formatHeader('roll_no')}</th>`;
        headerWithoutRoll.forEach(h => html += `<th>${formatHeader(h)}</th>`);
        html += "</tr></thead><tbody>";

        for(let i=1; i<rows.length; i++){
            const row = rows[i] || [];
            const obj = {};
            html += "<tr>";

            html += `<td class="file-photo-input">
                        <img id="photo_preview_${i}" alt="Preview" />
                        <input type="file" name="photo_row_${i}" accept="image/*" style="width: 160px;">
                    </td>`;

            let rollNoVal = row[rollIndex] ?? '';
            obj['roll_no'] = rollNoVal;
            html += `<td><input type="text" data-col="roll_no" value="${rollNoVal}"></td>`;

            headerWithoutRoll.forEach(h => {
                let idx = header.indexOf(h);
                let val = row[idx] ?? '';
                obj[h] = val;
                html += `<td><input type="text" data-col="${h}" value="${val}"></td>`;
            });

            previewData.push(obj);
            html += "</tr>";
        }
        html += "</tbody></table>";
        previewDiv.innerHTML = html;

        // Bind file input change event for image preview
        for (let i=1; i<rows.length; i++) {
            const fileInput = document.querySelector(`input[name="photo_row_${i}"]`);
            const imgPreview = document.getElementById(`photo_preview_${i}`);
            if (fileInput && imgPreview) {
                fileInput.addEventListener('change', function(){
                    const file = this.files[0];
                    if (file) {
                        const url = URL.createObjectURL(file);
                        imgPreview.style.display = 'block';
                        imgPreview.src = url;
                    } else {
                        imgPreview.style.display = 'none';
                        imgPreview.src = '';
                    }
                });
            }
        }

        submitBtn.disabled = false;
    };
    reader.readAsBinaryString(file);
});

form.addEventListener('submit', function(e){
    e.preventDefault();

    if (!institutionSelect.value) {
        alert('Please select an institution first.');
        return;
    }

    const tableInputs = previewDiv.querySelectorAll('td input[type="text"]');
    tableInputs.forEach(input => {
        const col = input.dataset.col;
        const tr = input.closest('tr');
        const rows = Array.from(tr.parentNode.children);
        const idx = rows.indexOf(tr);
        if (previewData[idx]) {
            previewData[idx][col] = input.value.trim();
        }
    });

    const formData = new FormData();

    formData.append('final_data', JSON.stringify(previewData));
    formData.append('selected_institution', institutionSelect.value);

    previewData.forEach((row, index) => {
        const fileInput = previewDiv.querySelector(`input[name="photo_row_${index + 1}"]`);
        if (fileInput && fileInput.files.length) {
            formData.append(`photo_row_${index + 1}`, fileInput.files[0]);
        }
    });

    fetch(form.action || window.location.href, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(res => res.text())
    .then(html => {
        document.open();
        document.write(html);
        document.close();
    })
    .catch(err => alert('Upload failed: ' + err));
});
</script>
</body>
</html>