<?php
// start session to check user login status
session_start();

// only recipients can view their approved items - check if user is logged in and is a recipient
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'recipient') {
  header("Location: login.php");
  exit();
}

// get database connection
include 'db.php';

// check if recipient clicked "mark as completed" button
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_donation'])) {
  $donation_id = $_POST['donation_id'];
  $recipient_id = $_SESSION['user_id'];
  
  // update the donation status to completed
  $stmt = $conn->prepare("UPDATE donations SET status = 'completed' WHERE id = ? AND recipient_id = ? AND status = 'approved'");
  $stmt->bind_param("ii", $donation_id, $recipient_id);
  
  if ($stmt->execute() && $stmt->affected_rows > 0) {
    $success = "Donation marked as completed successfully!";
  } else {
    $error = "Error updating donation status.";
  }
  $stmt->close();
}

// get all donations this recipient has requested (requested, approved, and completed)
$recipient_id = $_SESSION['user_id'];
$query = "SELECT d.*, u.username as donor_name FROM donations d 
          LEFT JOIN users u ON d.donor_id = u.id 
          WHERE d.recipient_id = ? AND d.status IN ('requested', 'approved', 'completed') 
          ORDER BY 
            CASE d.status 
              WHEN 'requested' THEN 1 
              WHEN 'approved' THEN 2 
              WHEN 'completed' THEN 3 
            END, d.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $recipient_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Approved Items - SpareBite</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <!-- header with recipient navigation -->
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
        <h1 class="dashboard-title">Your Donation Requests</h1>
        <p class="dashboard-role">Track your donation requests and manage approved items</p>
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
        
        <!-- check if user has any requests -->
        <?php if ($result && $result->num_rows > 0): ?>
          <div class="donations-grid">
            <!-- loop through each request -->
            <?php while ($row = $result->fetch_assoc()): ?>
              <div class="donation-card <?php echo $row['status']; ?>">
                <!-- donation header with status badge -->
                <div class="donation-header">
                  <h3><?php echo htmlspecialchars($row['food']); ?></h3>
                  <span class="donation-quantity"><?php echo htmlspecialchars($row['quantity']); ?></span>
                  <span class="status-badge status-<?php echo $row['status']; ?>">
                    <?php echo ucfirst($row['status']); ?>
                  </span>
                </div>
                
                <!-- all the donation details -->
                <div class="donation-details">
                  <p><strong>Description:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
                  <p><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                  <p><strong>Contact:</strong> <?php echo htmlspecialchars($row['contact']); ?></p>
                  <p><strong>Best Before:</strong> <?php echo date('M j, Y', strtotime($row['expiry_date'])); ?></p>
                  <p><strong>Donor:</strong> <?php echo htmlspecialchars($row['donor_name']); ?></p>
                  <p><strong>Requested:</strong> <?php echo date('M j, Y', strtotime($row['created_at'])); ?></p>
                  
                  <!-- different messages based on status -->
                  <?php if ($row['status'] === 'requested'): ?>
                    <!-- still waiting for donor approval -->
                    <div class="pickup-info" style="border-left-color: #ff9800; background-color: #fff8e1;">
                      <p style="color: #f57c00;"><strong>Status:</strong> Waiting for donor approval</p>
                      <p style="color: #666; font-size: 0.9rem;">Please wait for the donor to review your request.</p>
                    </div>
                  <?php elseif ($row['status'] === 'approved'): ?>
                    <!-- approved! need to arrange pickup -->
                    <div class="pickup-info">
                      <p><strong>Next Steps:</strong></p>
                      <ul>
                        <li>Contact the donor using the provided contact information</li>
                        <li>Arrange pickup time and location</li>
                        <li>Mark as completed after pickup</li>
                      </ul>
                    </div>
                  <?php else: ?>
                    <!-- completed -->
                    <p><strong>Status:</strong> <span style="color: #4caf50;">✅ Completed</span></p>
                  <?php endif; ?>
                </div>
                
                <!-- complete button only shows for approved donations -->
                <?php if ($row['status'] === 'approved'): ?>
                  <form method="post" style="margin-top: 1rem;">
                    <input type="hidden" name="donation_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" name="complete_donation" class="form-button secondary" 
                            onclick="return confirm('Have you successfully picked up this donation?')">
                      Mark as Completed
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            <?php endwhile; ?>
          </div>
          
        <?php else: ?>
          <!-- message when no requests exist -->
          <div style="text-align: center; padding: 2rem; color: #666;">
            <h3>📋 No Approved Items</h3>
            <p>You haven't requested any donations yet.</p>
            <a href="browse_donations.php" class="dashboard-link" style="margin-top: 1rem; display: inline-block;">
              Browse Available Donations
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