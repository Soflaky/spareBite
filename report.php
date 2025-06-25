<?php
// start session to check user login status
session_start();
// get database connection
include 'db.php';

// make sure user is logged in
if (!isset($_SESSION['role'])) {
  header("Location: login.php");
  exit();
}

// different reports for different user roles
$role = $_SESSION['role'];

if ($role === 'admin') {
  // admin can see all donations in the system
  $query = "SELECT * FROM donations ORDER BY id DESC";
  $result = $conn->query($query);
} elseif ($role === 'donor') {
  // donors can only see their own donations
  $user_id = $_SESSION['user_id'];
  $query = "SELECT * FROM donations WHERE donor_id = $user_id ORDER BY id DESC";
  $result = $conn->query($query);
} else {
  // recipients can only see donations they requested
  $user_id = $_SESSION['user_id'];
  $query = "SELECT * FROM donations WHERE recipient_id = $user_id ORDER BY id DESC";
  $result = $conn->query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports - SpareBite</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <meta name="description" content="SpareBite Donation Reports">
</head>
<body>
  <!-- header with navigation -->
  <header>
    <div class="header-content">
      <div class="logo-container">
        <img src="spareBiteLogo.png" alt="SpareBite Logo" class="logo">
        <h1 class="brand">SpareBite</h1>
      </div>
      <nav class="nav-links">
        <a href="index.html">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="report.php">Reports</a>
        <a href="logout.php">Logout</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="dashboard-container">
      <div class="dashboard-header">
        <h1 class="dashboard-title">Donation Reports</h1>
        <!-- different descriptions based on user role -->
        <p class="dashboard-role">
          <?php 
            if ($role === 'admin') echo "System-wide donation overview";
            elseif ($role === 'donor') echo "Your donation history";
            else echo "Received donations overview";
          ?>
        </p>
      </div>
      
      <div class="dashboard-content">
        <!-- check if there are any reports to show -->
        <?php if ($result && $result->num_rows > 0): ?>
          <!-- data table showing donation information -->
          <table>
            <tr>
              <!-- table headers -->
              <th>ID</th>
              <th>Food Item</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
            <!-- loop through each donation -->
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <!-- donation id with # prefix -->
                <td>#<?php echo htmlspecialchars($row['id']); ?></td>
                <!-- food item name -->
                <td><?php echo htmlspecialchars($row['food'] ?? 'N/A'); ?></td>
                <td>
                  <?php 
                    // determine status and color
                    $status = htmlspecialchars($row['status'] ?? 'Unknown');
                    $status_class = '';
                    if ($status === 'pending') $status_class = 'status-pending';
                    elseif ($status === 'approved') $status_class = 'status-approved';
                    elseif ($status === 'completed') $status_class = 'status-completed';
                  ?>
                  <!-- status with appropriate color class -->
                  <span class="<?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                </td>
                <!-- formatted date -->
                <td><?php echo isset($row['created_at']) ? date('M j, Y', strtotime($row['created_at'])) : 'N/A'; ?></td>
              </tr>
            <?php endwhile; ?>
          </table>
          
        <?php else: ?>
          <!-- message when no data is available -->
          <div style="text-align: center; padding: 2rem; color: #666;">
            <h3>📝 No Reports Available</h3>
            <p>
              <?php 
                // different messages based on user role
                if ($role === 'donor') echo "You haven't made any donations yet. Start making a difference!";
                elseif ($role === 'recipient') echo "No donations have been received yet.";
                else echo "No donation data available in the system.";
              ?>
            </p>
            <a href="dashboard.php" class="dashboard-link" style="margin-top: 1rem; display: inline-block;">
              Back to Dashboard
            </a>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 SpareBite - Reducing food waste, one donation at a time! </p>
  </footer>

  <script src="script.js"></script>
</body>
</html>