<?php
session_start();

// Security headers to prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: /maheshwari/login.php");
    exit;
}

require_once '../database.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

// Get admin info
$admin_email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT id, institution_id, name FROM institution_admins WHERE email = ?");
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
if (!$admin) die("Admin not found.");

$institution_id = $admin['institution_id'];
$admin_name = $admin['name'];

// Handle POST: save edited students
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['update_students'])) {
    $updatedStudents = json_decode($_POST['update_students'], true);
    $errors = [];

    $updateStmt = $conn->prepare("UPDATE students SET name=?, father_name=?, roll_no=?, class=?, section=?, start_year=?, end_year=?, mobile=?, email=?, gender=?, dob=?, blood_group=?, adhaar_no=?, address=?, profile_pic=? WHERE id=?");
    if (!$updateStmt) die("Prepare failed: ".$conn->error);

    foreach ($updatedStudents as $s) {
        $profile_pic_data = null;
        if (!empty($s['profile_pic']) && strpos($s['profile_pic'], 'base64,') !== false) {
            $profile_pic_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $s['profile_pic']));
        }

        // Bind params except blob
        // profile_pic is 15th parameter (index 14)
        $nullBlob = null;
        $updateStmt->bind_param(
            "sssssssssssssssi",
            $s['name'],
            $s['father_name'],
            $s['roll_no'],
            $s['class'],
            $s['section'],
            $s['start_year'],
            $s['end_year'],
            $s['mobile'],
            $s['email'],
            $s['gender'],
            $s['dob'],
            $s['blood_group'],
            $s['adhaar_no'],
            $s['address'],
            $nullBlob,
            $s['id']
        );

        if ($profile_pic_data !== null) {
            // Send blob data, parameter 14 (0-based)
            $updateStmt->send_long_data(14, $profile_pic_data);
        }

        if (!$updateStmt->execute()) {
            $errors[] = "Student ID ".$s['id']." update failed: ".$updateStmt->error;
        }
    }

    echo json_encode(['success' => empty($errors), 'errors' => $errors]);
    exit;
}

// Fetch distinct batches (start_year + end_year)
$stmt_batches = $conn->prepare("SELECT DISTINCT start_year, end_year FROM students WHERE institution_id=?");
$stmt_batches->bind_param("i", $institution_id);
$stmt_batches->execute();
$result_batches = $stmt_batches->get_result();

$batches = [];
while ($row = $result_batches->fetch_assoc()) {
    if ($row['start_year']) {
        $batches[] = [
            'start_year' => $row['start_year'],
            'end_year' => $row['end_year']
        ];
    }
}

// Selected batch
$selected_batch = $_GET['batch'] ?? (isset($batches[0]) ? $batches[0]['start_year']."-".$batches[0]['end_year'] : null);

// Fetch students filtered by batch
$query = "SELECT * FROM students WHERE institution_id=?";
$params = [$institution_id];
$types = "i";

if (!empty($selected_batch)) {
    [$sel_start, $sel_end] = explode("-", $selected_batch);
    $query .= " AND start_year=? AND end_year=?";
    $params[] = $sel_start;
    $params[] = $sel_end;
    $types .= "ss";
}

$stmt = $conn->prepare($query);
if(!$stmt) die("Prepare failed: ".$conn->error);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['profile_pic'])) {
        $row['profile_pic'] = "data:image/jpeg;base64," . base64_encode($row['profile_pic']);
    }
    $students[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Update Students</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
let students = <?php echo json_encode($students); ?>;
let editMode = false;

function updateToggleLabel() {
    const toggleBtn = document.getElementById("toggleEditBtn");
    const saveBtn = document.getElementById("saveChangesBtn");
    if (editMode) {
        toggleBtn.textContent = "Cancel";
        saveBtn.disabled = false;
    } else {
        toggleBtn.textContent = "Edit";
        saveBtn.disabled = true;
    }
}

function toggleEdit() {
    editMode = !editMode;
    updateToggleLabel();
    renderTable();
}

function handleChange(idx, field, value) {
    students[idx][field] = value;
}

function handleImage(idx, file) {
    const reader = new FileReader();
    reader.onload = () => {
        students[idx]['profile_pic'] = reader.result;
        renderTable();
    };
    reader.readAsDataURL(file);
}

function saveChanges() {
    fetch("", {
        method: "POST",
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ update_students: JSON.stringify(students) })
    })
    .then(r => r.json())
    .then(d => {
        if(d.success) {
            alert("Changes saved successfully!");

            // After save, exit edit mode
            editMode = false;
            updateToggleLabel();
            renderTable();
        } else {
            alert("Errors:\n" + d.errors.join("\n"));
        }
    });
}

function renderTable() {
    const container = document.getElementById("studentTable");
    if(students.length === 0) {
        container.innerHTML = "<p class='text-orange-600'>No students found.</p>";
        return;
    }
    const headers = Object.keys(students[0]).filter(f => !['institution_id', 'password', 'student_type', 'admin_id'].includes(f));
    let html = "<table class='min-w-full text-sm border-collapse border border-orange-300'>";
    html += "<thead class='bg-orange-100 sticky top-0 z-10'><tr>";
    headers.forEach(h => html += `<th class='px-3 py-2 border border-orange-300 text-left'>${h}</th>`);
    html += "</tr></thead><tbody>";

    students.forEach((s, i) => {
        html += "<tr class='even:bg-orange-50 odd:bg-white'>";
        headers.forEach(h => {
            if(h === 'profile_pic') {
                html += "<td class='px-3 py-2 border'>";
                if(s[h]) html += `<img src="${s[h]}" class="h-10 w-10 object-cover rounded-full border mb-1" alt="Profile Pic">`;
                if(editMode) html += `<input type='file' accept='image/*' onchange='handleImage(${i}, this.files[0])'>`;
                html += "</td>";
            } else if(editMode) {
                let type = (h === 'dob') ? 'date' : 'text';
                let val = s[h] || "";
                val = val.toString().replace(/"/g, "&quot;").replace(/'/g, "&#39;");

                html += `<td class='px-3 py-2 border'><input type='${type}' value="${val}" class='w-full border border-orange-300 rounded px-1 py-1 text-sm' onchange='handleChange(${i},"${h}",this.value)'></td>`;
            } else {
                html += `<td class='px-3 py-2 border'>${s[h] || ""}</td>`;
            }
        });
        html += "</tr>";
    });
    html += "</tbody></table>";
    container.innerHTML = html;
}

window.onload = () => {
    updateToggleLabel();
    renderTable();
    document.getElementById("batchSelect").addEventListener('change', function() {
        const selectedBatch = this.value;
        window.location.search = new URLSearchParams({batch: selectedBatch}).toString();
    });
};
</script>
</head>
<body class="flex bg-gray-100 min-h-screen">

<!-- Sidebar -->
<aside class="fixed left-0 top-0 h-full bg-white text-gray-900 shadow-lg w-64 overflow-auto">
    <?php include 'sidebar.php'; ?>
</aside>

<!-- Main content -->
<main class="ml-64 flex-1 p-6 space-y-6 overflow-auto">
    <h1 class="text-3xl font-bold text-orange-700">Update Student Records</h1>

    <label for="batchSelect" class="block font-semibold text-orange-700 mt-4 mb-2">Select Batch</label>
    <select id="batchSelect" class="border border-orange-300 rounded px-3 py-1 text-sm">
        <?php foreach($batches as $batch):
            $batchKey = $batch['start_year']."-".$batch['end_year'];
        ?>
            <option value="<?php echo htmlspecialchars($batchKey); ?>" <?php if($batchKey==$selected_batch) echo 'selected'; ?>>
                <?php echo htmlspecialchars($batch['start_year']." - ".$batch['end_year']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <div class="flex gap-4 mt-4">
        <button id="toggleEditBtn" onclick="toggleEdit()" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">Edit</button>
        <button id="saveChangesBtn" onclick="saveChanges()" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600" disabled>Save Changes</button>
    </div>

    <div id="studentTable" class="overflow-auto border border-orange-300 rounded shadow-sm mt-4 bg-white max-h-[600px]"></div>
</main>

<script>
window.addEventListener("pageshow", function (event) {
    if (event.persisted) window.location.reload();
});
</script>
</body>
</html>
