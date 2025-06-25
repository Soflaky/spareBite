<?php
// start session to check user login status
session_start();

// only recipients can view favorites
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'recipient') {
  header("Location: login.php");
  exit();
}

// get database connection
include 'db.php';

// get all favorite donors with their latest donations
$user_id = $_SESSION['user_id'];
$query = "SELECT u.id as donor_id, u.username as donor_name, u.full_name, f.created_at as favorited_at,
          COUNT(d.id) as total_donations,
          MAX(d.created_at) as last_donation_date
          FROM favorites f
          JOIN users u ON f.donor_id = u.id
          LEFT JOIN donations d ON u.id = d.donor_id
          WHERE f.user_id = ?
          GROUP BY u.id, u.username, u.full_name, f.created_at
          ORDER BY f.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Favorites - SpareBite</title>
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
        <a href="browse_donations.php">Browse</a>
        <a href="my_favorites.php">Favorites</a>
        <a href="report.php">Reports</a>
        <a href="logout.php">Logout</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="dashboard-container">
      <div class="dashboard-header">
        <h1 class="dashboard-title">⭐ My Favorite Donors</h1>
        <p class="dashboard-role">Donors you've bookmarked for easy access</p>
      </div>
      
      <div class="dashboard-content">
        <?php if ($result && $result->num_rows > 0): ?>
          <div class="favorites-grid">
            <?php while ($row = $result->fetch_assoc()): ?>
              <div class="favorite-card">
                <div class="favorite-header">
                  <h3><?php echo htmlspecialchars($row['donor_name']); ?></h3>
                  <button class="remove-favorite-btn" onclick="removeFavorite(<?php echo $row['donor_id']; ?>)">
                    ❌ Remove
                  </button>
                </div>
                
                <div class="favorite-details">
                  <?php if ($row['full_name']): ?>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($row['full_name']); ?></p>
                  <?php endif; ?>
                  <p><strong>Total Donations:</strong> <?php echo $row['total_donations']; ?></p>
                  <?php if ($row['last_donation_date']): ?>
                    <p><strong>Last Donation:</strong> <?php echo date('M j, Y', strtotime($row['last_donation_date'])); ?></p>
                  <?php endif; ?>
                  <p><strong>Favorited:</strong> <?php echo date('M j, Y', strtotime($row['favorited_at'])); ?></p>
                </div>
                
                <div class="favorite-actions">
                  <a href="browse_donations.php?donor=<?php echo $row['donor_id']; ?>" class="dashboard-link">
                    View Their Donations
                  </a>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
          
        <?php else: ?>
          <div style="text-align: center; padding: 2rem; color: #666;">
            <h3>⭐ No Favorites Yet</h3>
            <p>You haven't bookmarked any donors yet.</p>
            <p>When browsing donations, click the ⭐ button to add donors to your favorites!</p>
            <a href="browse_donations.php" class="dashboard-link" style="margin-top: 1rem; display: inline-block;">
              Browse Donations
            </a>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 SpareBite - Reducing food waste, one donation at a time!</p>
  </footer>

  <script>
    function removeFavorite(donorId) {
      if (confirm('Are you sure you want to remove this donor from your favorites?')) {
        fetch('manage_favorites.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'remove',
            donor_id: donorId
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            location.reload(); // Refresh page to show updated list
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while removing the favorite.');
        });
      }
    }
  </script>
</body>
</html> 