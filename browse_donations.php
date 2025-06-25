<?php
// start session to check user login status
session_start();

// only recipients can browse donations - check if user is logged in and is a recipient
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'recipient') {
  header("Location: login.php");
  exit();
}

// get database connection
include 'db.php';

// check if someone clicked "request donation" button
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_donation'])) {
  $donation_id = $_POST['donation_id']; // which donation they want
  $recipient_id = $_SESSION['user_id']; // who is requesting it
  
  // update the donation to show someone requested it
  $stmt = $conn->prepare("UPDATE donations SET recipient_id = ?, status = 'requested' WHERE id = ? AND status = 'pending'");
  
  if ($stmt === false) {
    $_SESSION['error'] = "Database error occurred. Please try again later.";
  } else {
    $stmt->bind_param("ii", $recipient_id, $donation_id);
    
    if ($stmt->execute()) {
      if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "Request sent successfully! Please wait for the donor to approve your request.";
      } else {
        $_SESSION['error'] = "This donation is no longer available or has already been claimed.";
      }
    } else {
      $_SESSION['error'] = "An error occurred while processing your request. Please try again.";
    }
    $stmt->close();
  }
  
  // redirect to prevent form resubmission (stops double-clicking issues)
  header("Location: browse_donations.php");
  exit();
}

// get any messages from the session and then clear them
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
$success = isset($_SESSION['success']) ? $_SESSION['success'] : null;
unset($_SESSION['error']);
unset($_SESSION['success']);

// check if filtering by specific donor
$filter_donor = isset($_GET['donor']) ? (int)$_GET['donor'] : 0;

// get all available donations (pending status and not expired)
// Also check if each donor is in user's favorites
$user_id = $_SESSION['user_id'];
$where_clause = "d.status = 'pending' AND d.expiry_date >= CURDATE()";
$params = [$user_id];
$param_types = "i";

// add donor filter if specified
if ($filter_donor > 0) {
  $where_clause .= " AND d.donor_id = ?";
  $params[] = $filter_donor;
  $param_types .= "i";
}

$query = "SELECT d.*, u.username as donor_name, u.full_name as donor_full_name,
          IF(f.id IS NOT NULL, 1, 0) as is_favorite
          FROM donations d 
          LEFT JOIN users u ON d.donor_id = u.id 
          LEFT JOIN favorites f ON d.donor_id = f.donor_id AND f.user_id = ?
          WHERE $where_clause 
          ORDER BY d.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// get donor name if filtering by specific donor
$donor_name = '';
if ($filter_donor > 0) {
  $donor_query = "SELECT username, full_name FROM users WHERE id = ? AND role = 'donor'";
  $donor_stmt = $conn->prepare($donor_query);
  $donor_stmt->bind_param("i", $filter_donor);
  $donor_stmt->execute();
  $donor_result = $donor_stmt->get_result();
  if ($donor_row = $donor_result->fetch_assoc()) {
    $donor_name = $donor_row['full_name'] ?: $donor_row['username'];
  }
  $donor_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Browse Donations - SpareBite</title>
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
        <a href="my_favorites.php">Favorites</a>
        <a href="report.php">Reports</a>
        <a href="logout.php">Logout</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="dashboard-container">
      <div class="dashboard-header">
        <?php if ($filter_donor > 0 && $donor_name): ?>
          <h1 class="dashboard-title">Donations from <?php echo htmlspecialchars($donor_name); ?></h1>
          <p class="dashboard-role">All available donations from this donor</p>
          <a href="browse_donations.php" class="dashboard-link" style="margin-top: 1rem; display: inline-block;">
            ← Back to All Donations
          </a>
        <?php else: ?>
          <h1 class="dashboard-title">Browse Available Donations</h1>
          <p class="dashboard-role">Find food donations available in your area</p>
        <?php endif; ?>
      </div>
      
      <div class="dashboard-content">
        <!-- show error message if something went wrong -->
        <?php if ($error): ?>
          <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- show success message if request was sent -->
        <?php if ($success): ?>
          <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- check if there are any donations available -->
        <?php if ($result && $result->num_rows > 0): ?>
          <div class="donations-grid">
            <!-- loop through each available donation -->
            <?php while ($row = $result->fetch_assoc()): ?>
              <div class="donation-card">
                <!-- donation header with food name and quantity -->
                <div class="donation-header">
                  <h3><?php echo htmlspecialchars($row['food']); ?></h3>
                  <div class="donation-header-right">
                    <span class="donation-quantity"><?php echo htmlspecialchars($row['quantity']); ?></span>
                    <button class="favorite-btn <?php echo $row['is_favorite'] ? 'favorited' : ''; ?>" 
                            onclick="toggleFavorite(<?php echo $row['donor_id']; ?>, this)"
                            title="<?php echo $row['is_favorite'] ? 'Remove from favorites' : 'Add to favorites'; ?>">
                      <?php echo $row['is_favorite'] ? '⭐' : '☆'; ?>
                    </button>
                  </div>
                </div>
                
                <!-- all the donation details -->
                <div class="donation-details">
                  <p><strong>Description:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
                  <p><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                  <p><strong>Contact:</strong> <?php echo htmlspecialchars($row['contact']); ?></p>
                  <p><strong>Best Before:</strong> <?php echo date('M j, Y', strtotime($row['expiry_date'])); ?></p>
                  <p><strong>Donor:</strong> <?php echo htmlspecialchars($row['donor_name']); ?></p>
                  <p><strong>Posted:</strong> <?php echo date('M j, Y', strtotime($row['created_at'])); ?></p>
                </div>
                
                <!-- request button for this donation -->
                <form method="post" style="margin-top: 1rem;">
                  <input type="hidden" name="donation_id" value="<?php echo $row['id']; ?>">
                  <button type="submit" name="request_donation" class="form-button" 
                          onclick="return confirm('Are you sure you want to request this donation?');">
                    Request This Donation
                  </button>
                </form>
              </div>
            <?php endwhile; ?>
          </div>
          
        <?php else: ?>
          <!-- message when no donations are available -->
          <div style="text-align: center; padding: 2rem; color: #666;">
            <h3>🍽️ No Available Donations</h3>
            <p>There are currently no food donations available in your area.</p>
            <p>Please check back later or contact local restaurants and shops about food donations.</p>
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
  <script>
    function toggleFavorite(donorId, button) {
      const isFavorited = button.classList.contains('favorited');
      const action = isFavorited ? 'remove' : 'add';
      
      fetch('manage_favorites.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: action,
          donor_id: donorId
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          if (action === 'add') {
            button.classList.add('favorited');
            button.innerHTML = '⭐';
            button.title = 'Remove from favorites';
          } else {
            button.classList.remove('favorited');
            button.innerHTML = '☆';
            button.title = 'Add to favorites';
          }
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating favorites.');
      });
    }
  </script>
</body>
</html> 