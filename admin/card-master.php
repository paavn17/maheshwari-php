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

// Handle POST Upload
$uploadError = '';
$uploadSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $frontFile = $_FILES['front_img'] ?? null;
    $backFile = $_FILES['back_img'] ?? null;

    if (!$name) {
        $uploadError = "Please enter a card design name.";
    } elseif (!$frontFile || !$frontFile['tmp_name']) {
        $uploadError = "Please upload the front image.";
    } elseif (!$backFile || !$backFile['tmp_name']) {
        $uploadError = "Please upload the back image.";
    } else {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array(mime_content_type($frontFile['tmp_name']), $allowedTypes)) {
            $uploadError = "Front image must be JPG or PNG format.";
        } elseif (!in_array(mime_content_type($backFile['tmp_name']), $allowedTypes)) {
            $uploadError = "Back image must be JPG or PNG format.";
        } elseif ($frontFile['size'] > 5*1024*1024 || $backFile['size'] > 5*1024*1024) {
            $uploadError = "Images must be less than 5MB each.";
        } else {
            $frontData = file_get_contents($frontFile['tmp_name']);
            $backData = file_get_contents($backFile['tmp_name']);

            $stmt = $conn->prepare("INSERT INTO card_designs (institution_id, name, front_img, back_img, uploaded_by, uploaded_at, deleted) VALUES (?, ?, ?, ?, ?, NOW(), 'No')");
            if (!$stmt) {
                $uploadError = 'Database error: ' . $conn->error;
            } else {
                $institution_id = 0; 
                $uploaded_by = $_SESSION['user_id'];
                $stmt->bind_param('isbbi', $institution_id, $name, $null1, $null2, $uploaded_by);

                $stmt->send_long_data(2, $frontData);
                $stmt->send_long_data(3, $backData);

                if ($stmt->execute()) {
                    $uploadSuccess = "Card design uploaded successfully.";
                } else {
                    $uploadError = "Failed to upload card design: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Fetch existing card designs
$stmt = $conn->prepare("SELECT id, name, front_img, back_img FROM card_designs WHERE institution_id = 0 AND deleted = 'No'");
$stmt->execute();
$result = $stmt->get_result();

$cards = [];
while ($row = $result->fetch_assoc()) {
    $cards[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'front_url' => 'data:image/jpeg;base64,' . base64_encode($row['front_img']),
        'back_url'  => 'data:image/jpeg;base64,' . base64_encode($row['back_img']),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>ID Card Designs Upload</title>
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
    background: #fff;
    border-right: 1px solid #e5e7eb;
}

main.content {
    margin-left: 16rem;
    flex-grow: 1;
    padding: 2rem;
    box-sizing: border-box;
    min-height: 100vh;
    background: #fff;
}

h1.page-header {
    font-size: 1.875rem;
    font-weight: 700;
    color: #c2410c;
    margin-bottom: 2rem;
    text-align: center;
}

form.upload-form {
    max-width: 600px;
    margin: 0 auto 3rem;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 1rem;
    padding: 1.5rem 2rem;
    box-shadow: 0 1px 5px rgba(0,0,0,0.1);
}

form.upload-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #c2410c;
}

form.upload-form input[type=text],
form.upload-form input[type=file] {
    width: 100%;
    padding: 0.5rem;
    margin-bottom: 1rem;
    border: 1px solid #fbbf24;
    border-radius: 0.5rem;
    font-size: 1rem;
}

form.upload-form button {
    background: #f97316;
    color: white;
    font-weight: 700;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: background 0.3s ease;
}

form.upload-form button:hover {
    background: #ea580c;
}

.message {
    max-width: 600px;
    margin: 0 auto 1rem;
    padding: 1rem;
    border-radius: 0.5rem;
    font-weight: 600;
    text-align: center;
}

.message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.message.error {
    background: #fecaca;
    color: #991b1b;
    border: 1px solid #dc2626;
}

/* Cards Grid */
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    align-items: start;
    max-width: 1200px;
    margin: 0 auto;
}

.card {
    background: #fff;
    border: 1px solid #fed7aa;
    border-radius: 1rem;
    box-shadow: 0 1px 5px rgba(0,0,0,0.1);
    padding: 1rem;
    width: 100%;
    transition: box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.card-images {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1rem;
    width: 100%;
}

.card-image {
    flex: 1 1 140px;
    max-width: 280px;
    height: auto;
    aspect-ratio: 7/10;
    border-radius: 0.375rem;
    overflow: hidden;
    box-shadow: 0 1px 6px rgba(0,0,0,0.1);
    object-fit: cover;
    transition: transform 0.3s ease;
    cursor: pointer;
}

.card-image:hover {
    transform: scale(1.05);
}

.card-name {
    margin-top: 1rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.875rem;
    color: #9a3412;
    letter-spacing: 0.05em;
}

p.no-cards {
    color: #ea580c;
    font-size: 0.875rem;
    text-align: center;
}
</style>
</head>
<body>

<aside class="sidebar">
  <?php include 'sidebar.php'; ?>
</aside>

<main class="content">
  <h1 class="page-header">Upload New ID Card Design</h1>

  <?php if($uploadError): ?>
    <div class="message error"><?= htmlspecialchars($uploadError) ?></div>
  <?php elseif($uploadSuccess): ?>
    <div class="message success"><?= htmlspecialchars($uploadSuccess) ?></div>
  <?php endif; ?>

  <form class="upload-form" method="post" enctype="multipart/form-data" novalidate>
    <label for="name">Card Design Name</label>
    <input type="text" id="name" name="name" required maxlength="100" />

    <label for="front_img">Front Image (JPG or PNG, max 5MB)</label>
    <input type="file" id="front_img" name="front_img" accept="image/jpeg,image/png" required />

    <label for="back_img">Back Image (JPG or PNG, max 5MB)</label>
    <input type="file" id="back_img" name="back_img" accept="image/jpeg,image/png" required />

    <button type="submit">Upload Card Design</button>
  </form>

  <h1 class="page-header">Default ID Card Designs</h1>

  <?php if (empty($cards)) : ?>
    <p class="no-cards">No default card designs found.</p>
  <?php else: ?>
    <div class="cards-grid">
      <?php foreach ($cards as $card): ?>
        <div class="card">
          <div class="card-images">
            <img src="<?= htmlspecialchars($card['front_url']) ?>" alt="<?= htmlspecialchars($card['name']) ?> Front" class="card-image" />
            <img src="<?= htmlspecialchars($card['back_url']) ?>" alt="<?= htmlspecialchars($card['name']) ?> Back" class="card-image" />
          </div>
          <div class="card-name"><?= htmlspecialchars($card['name']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<script>
window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
      window.location.reload();
    }
});
</script>

</body>
</html>
