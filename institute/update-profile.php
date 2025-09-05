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

$admin_email = $_SESSION['email'];

// Get institution_id for logged-in admin
$stmt = $conn->prepare("SELECT institution_id FROM institution_admins WHERE email=?");
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();

if (!$admin) {
    die("Admin not found.");
}
$institution_id = $admin['institution_id'];

// Handle POST update
$status = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = $_POST['email'] ?? '';
    $phone   = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $state   = $_POST['state'] ?? '';
    $city    = $_POST['city'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    $type    = $_POST['type'] ?? '';

    $logoData = null;
    if (!empty($_FILES['logo']['tmp_name'])) {
        if ($_FILES['logo']['size'] > 4 * 1024 * 1024) {
            $status = "❌ Logo must be less than 4MB.";
        } else {
            $logoData = file_get_contents($_FILES['logo']['tmp_name']);
        }
    }

    if (!$status) {
        $query = "UPDATE institutions SET email=?, phone=?, address=?, state=?, city=?, pincode=?, type=?";
        $params = [$email, $phone, $address, $state, $city, $pincode, $type];
        $types = "sssssss";

        if ($logoData !== null) {
            $query .= ", logo=?";
            $params[] = $logoData;
            $types .= "b";
        }

        $query .= " WHERE id=?";
        $params[] = $institution_id;
        $types .= "i";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        if ($logoData !== null) {
            $null = NULL;
            $stmt->send_long_data(count($params)-2, $logoData);
        }
        if ($stmt->execute()) {
            $status = "✅ Profile updated successfully.";
        } else {
            $status = "❌ Failed to update profile.";
        }
    }
}

// Fetch profile
$stmt = $conn->prepare("SELECT name, code, email, phone, address, state, city, pincode, type, logo FROM institutions WHERE id=?");
$stmt->bind_param("i", $institution_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

$logo = null;
if (!empty($profile['logo'])) {
    $logo = "data:image/png;base64," . base64_encode($profile['logo']);
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Update Institute Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function enableEdit() {
      document.querySelectorAll(".editable").forEach(el => el.disabled = false);
      document.getElementById("editButtons").classList.remove("hidden");
      document.getElementById("editBtn").classList.add("hidden");
    }
    function cancelEdit() {
      window.location.reload();
    }
  </script>
</head>
<body class="flex bg-gray-100 min-h-screen">

  <!-- Sidebar -->
  <aside class=" fixed left-0 top-0 h-full text-white shadow-lg">
    <?php include 'sidebar.php'; ?>
  </aside>

  <!-- Main -->
  <main class="ml-64 flex-1 p-6">
    <div class="max-w-3xl bg-white rounded shadow p-6 space-y-6">
      <h1 class="text-2xl font-bold text-orange-700">Update Institute Profile</h1>

      <?php if ($status): ?>
        <p class="text-sm font-semibold text-orange-600"><?= htmlspecialchars($status) ?></p>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <!-- Logo -->
        <div>
          <label class="block text-sm font-medium text-orange-800 mb-1">Logo <span class="text-xs text-gray-500">(Max 4MB)</span></label>
          <input type="file" name="logo" accept="image/*" class="editable block w-full text-sm text-orange-800 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-orange-100 file:text-orange-700 hover:file:bg-orange-200" disabled>
          <?php if ($logo): ?>
            <div class="mt-3">
              <img src="<?= $logo ?>" alt="Institution Logo" class="h-16 w-auto rounded  shadow-sm">
            </div>
          <?php endif; ?>
        </div>

        <!-- Fields -->
        <?php
        function inputRow($label, $name, $value, $editable=true) {
          $val = htmlspecialchars($value ?? "");
          echo "<div class='w-full'>";
          echo "<label class='block text-sm font-medium text-orange-800 mb-1'>$label</label>";
          if ($editable) {
            echo "<input type='text' name='$name' value='$val' class='editable w-full min-w-[200px] px-3 py-2 border border-orange-300 rounded focus:ring-2 focus:ring-orange-400 text-sm' disabled>";
          } else {
            echo "<div class='px-3 py-2 bg-gray-50 border border-orange-200 rounded text-orange-900 text-sm min-w-[200px]'>$val</div>";
          }
          echo "</div>";
        }

        inputRow("Institute Name", "name", $profile['name'], false);
        inputRow("Institute Code", "code", $profile['code'], false);
        inputRow("Email", "email", $profile['email']);
        inputRow("Phone Number", "phone", $profile['phone']);
        inputRow("Address", "address", $profile['address']);
        inputRow("State", "state", $profile['state']);
        inputRow("City", "city", $profile['city']);
        inputRow("Pincode", "pincode", $profile['pincode']);
        inputRow("Type", "type", $profile['type']);
        ?>

        <!-- Buttons -->
        <div class="flex justify-end gap-4">
          <div id="editButtons" class="hidden">
            <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600 transition">Save</button>
            <button type="button" onclick="cancelEdit()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition">Cancel</button>
          </div>
          <button type="button" id="editBtn" onclick="enableEdit()" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600 transition">Edit Profile</button>
        </div>
      </form>
    </div>
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
