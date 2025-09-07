<?php
// sidebar.php

$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
  .sidebar {
    width: 240px;
    background-color: #ffffff;
    border-right: 1px solid #fed7aa;
    position: fixed;
    height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    font-family: Arial, sans-serif;
    overflow-y: auto;
    padding-bottom: 1rem;
  }
  .sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid #fed7aa;
    text-align: center;
    flex-shrink: 0;
  }
  .sidebar-header img {
    width: 100px;
    height: 100px;
    object-fit: contain;
    margin-bottom: 0.5rem;
  }
  .sidebar-header .title {
    font-weight: bold;
    color: #374151;
  }
  nav.sidebar-nav {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex-grow: 1;
  }
  nav.sidebar-nav a {
    display: block;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    color: #374151;
    text-decoration: none;
    transition: background-color 0.3s, color 0.3s;
    font-weight: 600;
  }
  nav.sidebar-nav a:hover {
    color: #f97316;
    background-color: #ffedd5;
  }
  nav.sidebar-nav a.active {
    background-color: #fbbf24;
    font-weight: 700;
    color: #374151;
  }
  nav.sidebar-nav a.danger {
    color: #dc2626;
  }
  nav.sidebar-nav a.danger:hover {
    background-color: #fee2e2;
    color: #b91c1c;
  }
  /* Locked menu item */
  nav.sidebar-nav span.locked {
    display: block;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    color: #9ca3af;
    font-weight: 600;
    cursor: not-allowed;
    user-select: none;
  }
  nav.sidebar-nav span.locked:hover {
    background-color: transparent;
    color: #9ca3af;
  }
</style>

<aside class="sidebar">
  <div class="sidebar-header">
    <img src="../public/images/logo.png" alt="Maheshwari ID Cards Logo" />
    <div class="title">Maheshwari ID Cards</div>
  </div>

  <nav class="sidebar-nav" role="navigation" aria-label="Admin navigation">
    <?php
    $menu_items = [
      ['title'=>'Dashboard', 'href'=>'dashboard.php'],
      ['title'=>'Upload Students Data', 'href'=>'upload-students.php'],
      // ['title'=>'Upload Employee Data', 'href'=>'upload-employees.php', 'locked'=>true],
      ['title'=>'Manual Update Students Data', 'href'=>'manual-upload.php'],
      ['title'=>'Update Students Data', 'href'=>'update-students.php'],
      ['title'=>'Update Profile', 'href'=>'update-profile.php'],
      // ['title'=>'Payment', 'href'=>'payment.php', 'locked'=>true],
      ['title'=>'Card Designs', 'href'=>'card-designs.php'],
      ['title'=>'Upload Card Design', 'href'=>'upload-card.php', 'locked'=>true],
      // ['title'=>'Requested Changes', 'href'=>'requested-changes.php','locked'=>true],
      ['title'=>'Logout', 'href'=>'/maheshwari/logout.php', 'danger'=>true],
    ];

    foreach ($menu_items as $item) {
      $isActive = ($current_page === $item['href']) ? 'active' : '';
      $dangerClass = !empty($item['danger']) ? 'danger' : '';

      if (!empty($item['locked'])) {
        echo '<span class="locked ' . $isActive . ' ' . $dangerClass . '">' . htmlspecialchars($item['title']) . '</span>';
      } else {
        echo '<a href="' . htmlspecialchars($item['href']) . '" class="' . $isActive . ' ' . $dangerClass . '">';
        echo htmlspecialchars($item['title']);
        echo '</a>';
      }
    }
    ?>
  </nav>
</aside>
