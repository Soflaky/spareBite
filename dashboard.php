<?php
// start session to check if user is logged in
session_start();

// if user is not logged in, send them to login page
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}

// get real statistics from database to show on dashboard
include 'db.php';

// count total donations in the system
$total_donations_query = "SELECT COUNT(*) as total FROM donations";
$total_donations_result = $conn->query($total_donations_query);
$total_donations = $total_donations_result ? $total_donations_result->fetch_assoc()['total'] : 0;

// count total users (excluding admin users)
$total_users_query = "SELECT COUNT(*) as total FROM users WHERE role != 'admin'";
$total_users_result = $conn->query($total_users_query);
$total_users = $total_users_result ? $total_users_result->fetch_assoc()['total'] : 0;

// calculate success rate (percentage of completed donations)
if ($total_donations > 0) {
  $completed_query = "SELECT COUNT(*) as completed FROM donations WHERE status = 'completed'";
  $completed_result = $conn->query($completed_query);
  $completed = $completed_result ? $completed_result->fetch_assoc()['completed'] : 0;
  $success_rate = round(($completed / $total_donations) * 100);
} else {
  $success_rate = 0;
}

// for donors: count how many requests are waiting for approval
$pending_requests = 0;
if ($_SESSION['role'] === 'donor') {
  $user_id = $_SESSION['user_id'];
  $requests_query = "SELECT COUNT(*) as pending FROM donations WHERE donor_id = $user_id AND status = 'requested'";
  $requests_result = $conn->query($requests_query);
  $pending_requests = $requests_result ? $requests_result->fetch_assoc()['pending'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - SpareBite</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <!-- header with logged-in navigation -->
  <header>
    <div class="header-content">
      <div class="logo-container">
        <img src="spareBiteLogo.png" alt="SpareBite Logo" class="logo">
        <h1 class="brand">SpareBite</h1>
      </div>
      <!-- different nav links for logged in users -->
      <nav class="nav-links">
        <a href="index.html">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="edit_profile.php">Profile</a>
        <a href="report.php">Reports</a>
        <a href="logout.php">Logout</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="dashboard-container">
      <!-- welcome message with user's name -->
      <div class="dashboard-header">
        <h1 class="dashboard-title">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p class="dashboard-role">Your Role: <strong><?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?></strong></p>
      </div>
      
      <div class="dashboard-content">
        <!-- different content based on user role -->
        <?php if ($_SESSION['role'] === 'donor'): ?>
          <!-- content for food donors -->
          <h3>🍽️ Donor Dashboard</h3>
          <p>As a donor, you can make a real difference by sharing surplus food with those in need. Help reduce food waste in your community!</p>
          
        <?php elseif ($_SESSION['role'] === 'recipient'): ?>
          <!-- content for food recipients -->
          <h3>🏠 Recipient Dashboard</h3>
          <p>Welcome! Here you can browse available food donations from local restaurants and shops, and coordinate pickups to help feed your community.</p>
          
        <?php elseif ($_SESSION['role'] === 'admin'): ?>
          <!-- content for admin users -->
          <h3>⚙️ Admin Dashboard</h3>
          <p>Manage the SpareBite platform. Monitor user activity, oversee donation flows, and generate reports.</p>
          
        <?php endif; ?>
        
        <!-- action buttons - different for each role -->
        <div class="dashboard-actions">
          <!-- buttons available to all users -->
          <a href="edit_profile.php" class="dashboard-link">✏️ Edit Profile</a>
          <a href="report.php" class="dashboard-link">View Reports</a>
          
          <?php if ($_SESSION['role'] === 'donor'): ?>
            <!-- buttons only for donors -->
            <a href="new_donation.php" class="dashboard-link secondary">➕ New Donation</a>
            <a href="manage_requests.php" class="dashboard-link">
              📋 Manage Requests
              <!-- show notification badge if there are pending requests -->
              <?php if ($pending_requests > 0): ?>
                <span class="notification-badge"><?php echo $pending_requests; ?></span>
              <?php endif; ?>
            </a>
            
          <?php elseif ($_SESSION['role'] === 'recipient'): ?>
            <!-- buttons only for recipients -->
            <a href="browse_donations.php" class="dashboard-link secondary">🔍 Browse Donations</a>
            <a href="my_favorites.php" class="dashboard-link">⭐ My Favorites</a>
            <a href="approved_items.php" class="dashboard-link">My Requests</a>
            
          <?php elseif ($_SESSION['role'] === 'admin'): ?>
            <!-- buttons only for admin -->
            <a href="manage_users.php" class="dashboard-link secondary">👥 Manage Users</a>
            <a href="system_settings.php" class="dashboard-link">⚙️ System Settings</a>
            
          <?php endif; ?>
        </div>
      </div>
      
      <!-- statistics section showing real data from database -->
      <div class="dashboard-content">
        <h3>Quick Stats</h3>
        <div class="stats-grid">
          <!-- total number of donations posted -->
          <div class="stat-card">
            <h4><?php echo $total_donations; ?></h4>
            <p>Total Donations</p>
          </div>
          <!-- number of registered users -->
          <div class="stat-card">
            <h4><?php echo $total_users; ?></h4>
            <p>Users Registered</p>
          </div>
          <!-- percentage of donations successfully completed -->
          <div class="stat-card">
            <h4><?php echo $success_rate; ?>%</h4>
            <p>Success Rate</p>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 SpareBite - Reducing food waste, one donation at a time! </p>
  </footer>

  <script src="script.js"></script>
</body>
</html>