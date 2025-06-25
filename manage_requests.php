<?php
// start session to check user login status
session_start();

// only donors can manage requests - check if user is logged in and is a donor
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'donor') {
  header("Location: login.php");
  exit();
}

// get database connection
include 'db.php';

// check if donor clicked approve or refuse button
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $donation_id = $_POST['donation_id']; // which donation
  $action = $_POST['action']; // approve or refuse
  $donor_id = $_SESSION['user_id']; // make sure it's their donation
  
  if ($action === 'approve') {
    // approve the request - change status to approved
    $stmt = $conn->prepare("UPDATE donations SET status = 'approved' WHERE id = ? AND donor_id = ? AND status = 'requested'");
    
    if ($stmt === false) {
      $error = "Database error: " . htmlspecialchars($conn->error);
    } else {
      $stmt->bind_param("ii", $donation_id, $donor_id);
      
      if ($stmt->execute() && $stmt->affected_rows > 0) {
        $success = "Request approved successfully! The recipient will be notified.";
      } else {
        $error = "Error approving request.";
      }
      $stmt->close();
    }
  } elseif ($action === 'refuse') {
    // refuse the request - reset donation to pending and remove recipient
    $stmt = $conn->prepare("UPDATE donations SET status = 'pending', recipient_id = NULL WHERE id = ? AND donor_id = ? AND status = 'requested'");
    
    if ($stmt === false) {
      $error = "Database error: " . htmlspecialchars($conn->error);
    } else {
      $stmt->bind_param("ii", $donation_id, $donor_id);
      
      if ($stmt->execute() && $stmt->affected_rows > 0) {
        $success = "Request declined. The donation is now available for other recipients.";
      } else {
        $error = "Error declining request.";
      }
      $stmt->close();
    }
  }
}

// get all donations that have been requested for this donor
$donor_id = $_SESSION['user_id'];
$query = "SELECT d.*, u.username as recipient_name FROM donations d 
          LEFT JOIN users u ON d.recipient_id = u.id 
          WHERE d.donor_id = ? AND d.status = 'requested' 
          ORDER BY d.created_at DESC";
$stmt = $conn->prepare($query);

if ($stmt === false) {
  die('Prepare failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Requests - SpareBite</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <!-- header with donor navigation -->
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
        <h1 class="dashboard-title">Donation Requests</h1>
        <p class="dashboard-role">Review and manage incoming donation requests</p>
      </div>
      
      <div class="dashboard-content">
        <!-- show error message if something went wrong -->
        <?php if (isset($error)): ?>
          <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- show success message if action was successful -->
        <?php if (isset($success)): ?>
          <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <!-- check if there are any pending requests -->
        <?php if ($result && $result->num_rows > 0): ?>
          <div class="donations-grid">
            <!-- loop through each request -->
            <?php while ($row = $result->fetch_assoc()): ?>
              <div class="donation-card requested">
                <!-- donation header with requested status -->
                <div class="donation-header">
                  <h3><?php echo htmlspecialchars($row['food']); ?></h3>
                  <span class="donation-quantity"><?php echo htmlspecialchars($row['quantity']); ?></span>
                  <span class="status-badge status-requested">Requested</span>
                </div>
                
                <div class="donation-details">
                  <!-- who requested this donation -->
                  <div class="recipient-info">
                    <h4 style="color: #2e7d32; margin: 0 0 0.5rem 0;">📋 Request Details</h4>
                    <p><strong>Requested by:</strong> <?php echo htmlspecialchars($row['recipient_name']); ?></p>
                    <p><strong>Request Date:</strong> <?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?></p>
                  </div>
                  
                  <!-- remind donor of their donation details -->
                  <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                    <h4 style="color: #2e7d32; margin: 0 0 0.5rem 0;">🍽️ Your Donation Details</h4>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($row['contact']); ?></p>
                    <p><strong>Best Before:</strong> <?php echo date('M j, Y', strtotime($row['expiry_date'])); ?></p>
                    <p><strong>Posted:</strong> <?php echo date('M j, Y', strtotime($row['created_at'])); ?></p>
                  </div>
                </div>
                
                <!-- approve and decline buttons -->
                <div class="request-actions">
                  <!-- approve button -->
                  <form method="post" style="display: inline-block; margin-right: 1rem;">
                    <input type="hidden" name="donation_id" value="<?php echo $row['id']; ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="form-button approve-btn" 
                            onclick="return confirm('Are you sure you want to approve this request?')">
                      ✅ Approve Request
                    </button>
                  </form>
                  
                  <!-- decline button -->
                  <form method="post" style="display: inline-block;">
                    <input type="hidden" name="donation_id" value="<?php echo $row['id']; ?>">
                    <input type="hidden" name="action" value="refuse">
                    <button type="submit" class="form-button refuse-btn" 
                            onclick="return confirm('Are you sure you want to decline this request? The donation will become available for other recipients.')">
                      ❌ Decline Request
                    </button>
                  </form>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
          
        <?php else: ?>
          <!-- message when no requests are pending -->
          <div style="text-align: center; padding: 2rem; color: #666;">
            <h3>📬 No Pending Requests</h3>
            <p>You don't have any pending donation requests at the moment.</p>
            <p>When recipients request your donations, they will appear here for your approval.</p>
            <a href="new_donation.php" class="dashboard-link" style="margin-top: 1rem; display: inline-block;">
              ➕ Create New Donation
            </a>
            <a href="dashboard.php" class="dashboard-link" style="margin-top: 1rem; display: inline-block;">
              Back to Dashboard
            </a>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 SpareBite - Reducing food waste, one donation at a time!</p>
  </footer>

  <script src="script.js"></script>
</body>
</html> 