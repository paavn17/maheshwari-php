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

// ---- Get admin info ----
$adminEmail = $_SESSION['email'] ?? '';
$stmt = $conn->prepare("SELECT id, institution_id FROM institution_admins WHERE email=?");
$stmt->bind_param('s', $adminEmail);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
if (!$admin) die("Admin not found.");

$institution_id = $admin['institution_id'];
$admin_id       = $admin['id'];

$uploadError = '';
$uploadSuccess = '';

// For reporting
$insertedCount = 0;
$notInsertedCount = 0;
$notInsertedDetails = [];

// Handle POST: receive student data and images base64 map
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['final_data'])) {
    $data = json_decode($_POST['final_data'], true);
    $imagesBase64 = json_decode($_POST['images_base64'] ?? '{}', true);

    if (!$data) {
        $uploadError = "Invalid data received.";
    } else {
        $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM students WHERE institution_id=? AND roll_no=? AND class=? AND section=?");
        $insertStmt = $conn->prepare("
            INSERT INTO students
            (studid, admin_id, institution_id, name, father_name, roll_no, class, section, start_year, end_year, mobile, email, gender, dob, blood_group, adhaar_no, address, profile_pic)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$checkStmt || !$insertStmt) {
            die("Prepare failed: " . $conn->error);
        }

        foreach ($data as $i => $row) {
            $name       = $row['name'];
            $fatherName = $row['father_name'] ?? '';
            $rollNo     = $row['roll_no'];
            $class      = $row['class'];
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

            // Try to get base64 image data by roll_no (case insensitive)
            $profilePic = null;
            $rollNoKey = strtolower($rollNo);
            if ($rollNoKey && isset($imagesBase64[$rollNoKey])) {
                $profilePic = $imagesBase64[$rollNoKey];
                if (strpos($profilePic, 'base64,') === false) {
                    // Unsafe or missing mime fix (assume jpeg)
                    $profilePic = 'data:image/jpeg;base64,' . $profilePic;
                }
            }

            // Check duplicate in DB
            $checkStmt->bind_param('isss', $institution_id, $rollNo, $class, $section);
            $checkStmt->execute();
            $res = $checkStmt->get_result()->fetch_assoc();
            if ($res['cnt'] > 0) {
                $notInsertedCount++;
                $notInsertedDetails[] = [
                    'roll_no' => $rollNo,
                    'reason' => "Duplicate roll_no/class/section exists"
                ];
                continue;
            }

            // Insert row
            $studid = ''; // temporary, will update after insert

            $insertStmt->bind_param('siisssssssssssssss',
                $studid,
                $admin_id,
                $institution_id,
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
                // Update studid to "stud" + id
                $lastId = $insertStmt->insert_id;
                $studid = "stud" . $lastId;
                $updateStmt = $conn->prepare("UPDATE students SET studid=? WHERE id=?");
                $updateStmt->bind_param('si', $studid, $lastId);
                $updateStmt->execute();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Upload Students Excel + Images</title>
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
input[type=file], button {
    margin-bottom:16px;
    font-size: 1rem;
}
button {
    background:#c55f11;
    color:white;
    padding:8px 20px;
    border-radius:8px;
    border:none;
    cursor:pointer;
    font-weight: 700;
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
}
th {
    background:#f97316;
    color:white;
    text-transform: uppercase;
    font-weight: 700;
}
td input {
    width:100%;
    border:none;
    background:transparent;
    font-size: 0.9rem;
}
.image-preview {
    width: 80px;
    height: 88px;
    object-fit: contain;
    border-radius: 6px;
    border: 1px solid #ddd;
}
#imageInput {
    display: block;
    margin-bottom: 20px;
}
@media (max-width: 768px) {
    aside.sidebar {
        display: none;
    }
    main.content {
        margin-left: 0;
        padding: 1rem;
    }
    form, table, .error, .success {
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
    <h1 class="page-header">Upload Students Excel and Images</h1>

    <?php if($uploadError): ?><div class="error"><?= $uploadError ?></div><?php endif; ?>
    <?php if($uploadSuccess): ?><div class="success"><?= $uploadSuccess ?></div><?php endif; ?>

    <form id="excelForm" method="post" enctype="multipart/form-data">
        <label for="fileInput">Select Excel File (.xlsx)</label><br>
        <input type="file" id="fileInput" accept=".xlsx, .xls" required><br>

        <label for="imageInput">Select Images (multiple, named by roll_no or studid, .jpg/.png)</label><br>
        <input type="file" id="imageInput" accept="image/*" multiple><br>

        <input type="hidden" name="final_data" id="final_data">
        <input type="hidden" name="images_base64" id="images_base64">

        <button type="submit">Insert Students</button>
    </form>

    <div id="previewDiv"></div>
</main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
const fileInput = document.getElementById('fileInput');
const imageInput = document.getElementById('imageInput');
const previewDiv = document.getElementById('previewDiv');
const finalDataInput = document.getElementById('final_data');
const imagesBase64Input = document.getElementById('images_base64');
const form = document.getElementById('excelForm');

let previewData = [];
let imagesBase64Map = {};

function base64FromFile(file) {
    return new Promise((res, rej) => {
        const reader = new FileReader();
        reader.onload = () => res(reader.result);
        reader.onerror = () => rej("Failed to read file: " + file.name);
        reader.readAsDataURL(file);
    });
}

fileInput.addEventListener('change', function(){
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(evt){
        const data = evt.target.result;
        const workbook = XLSX.read(data, {type:'binary'});
        const sheetName = workbook.SheetNames[0];
        const sheet = workbook.Sheets[sheetName];
        const rows = XLSX.utils.sheet_to_json(sheet, {header:1});
        if (rows.length < 2) return alert("Excel file is empty.");

        const header = rows[0].map(h=>h.toString().toLowerCase().trim());
        const required = ['name','roll_no','class'];
        for(let r of required){
            if (!header.includes(r)) { alert("Missing required column: "+r); return; }
        }

        previewData = [];
        let html = "<table><tr>";
        // Add image column header
        html += "<th>Image</th>";
        header.forEach(h=>html+="<th>"+h+"</th>");
        html += "</tr>";

        for(let i=1;i<rows.length;i++){
            const row = rows[i];
            const obj = {};
            html += "<tr>";

            let roll = '';
            const rollIndex = header.indexOf('roll_no');
            if(rollIndex > -1){
                roll = row[rollIndex] ? row[rollIndex].toString().toLowerCase() : '';
            }
            let imgSrc = imagesBase64Map[roll] || '';
            if(imgSrc){
                html += `<td><img src="${imgSrc}" alt="Student Image" class="image-preview"></td>`;
            } else {
                html += `<td></td>`;
            }
            header.forEach((col,j)=>{
                obj[col] = row[j] ?? '';
                html += "<td><input type='text' data-col='"+col+"' value='"+(row[j] ?? '')+"'></td>";
            });
            previewData.push(obj);
            html += "</tr>";
        }
        html += "</table>";
        previewDiv.innerHTML = html;
    };
    reader.readAsBinaryString(file);
});

imageInput.addEventListener('change', async function(){
    imagesBase64Map = {};
    const files = Array.from(this.files);

    for(const file of files){
        const baseName = file.name.split('.').slice(0,-1).join('.').toLowerCase();
        try {
            const base64 = await base64FromFile(file);
            imagesBase64Map[baseName] = base64;
        } catch(err){
            console.error(err);
        }
    }

    if(previewData.length > 0){
        regeneratePreviewTable();
    }
});

function regeneratePreviewTable(){
    const header = Object.keys(previewData[0]);
    let html = "<table><tr>";
    html += "<th>Image</th>";
    header.forEach(h=>html+="<th>"+h+"</th>");
    html += "</tr>";
    previewData.forEach(row=>{
        let roll = (row['roll_no'] ?? '').toString().toLowerCase();
        let imgSrc = imagesBase64Map[roll] || '';
        html += "<tr>";
        if(imgSrc){
            html += `<td><img src="${imgSrc}" alt="Student Image" class="image-preview"></td>`;
        } else {
            html += `<td></td>`;
        }
        header.forEach(col=>{
            html += `<td><input type='text' data-col='${col}' value='${row[col] ?? ''}'></td>`;
        });
        html += "</tr>";
    });
    html += "</table>";
    previewDiv.innerHTML = html;
}

form.addEventListener('submit', function(e){
    e.preventDefault();

    const tableInputs = previewDiv.querySelectorAll('td input');
    tableInputs.forEach(input=>{
        const col = input.dataset.col;
        const tr = input.closest('tr');
        const idx = Array.from(tr.parentNode.children).indexOf(tr)-1;
        if(previewData[idx]) previewData[idx][col] = input.value.trim();
    });

    finalDataInput.value = JSON.stringify(previewData);
    imagesBase64Input.value = JSON.stringify(imagesBase64Map);

    form.submit();
});
</script>
</body>
</html>
