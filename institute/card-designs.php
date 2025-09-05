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

// Fetch default card designs
$stmt = $conn->prepare("SELECT id, name, front_img, back_img 
                        FROM card_designs 
                        WHERE institution_id = 0 AND deleted = 'No'");
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
<title>ID Card Designs</title>
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
}

main.content {
    margin-left: 16rem;
    flex-grow: 1;
    padding: 2rem;
    box-sizing: border-box;
    min-height: 100vh;
}

h1.page-header {
    font-size: 1.875rem;
    font-weight: 700;
    color: #c2410c;
    margin-bottom: 2rem;
    text-align: center;
}

.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    align-items: start;
}

.card {
    background: #fff;
    border: 1px solid #fed7aa;
    border-radius: 1rem;
    box-shadow: 0 1px 5px rgba(0,0,0,0.1);
    padding: 1rem;
    transition: box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

.card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.card-images {
    display: flex;
    flex-wrap: wrap; /* allows images to wrap to next line */
    justify-content: center;
    gap: 1rem;
    width: 100%;
}

.card-image {
    flex: 1 1 140px; /* grow, shrink, base width */
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
