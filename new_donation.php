<?php
// start session to check user login status
session_start();

// only donors can create donations - check if user is logged in and is a donor
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'donor') {
  header("Location: login.php");
  exit();
}

// get database connection
include 'db.php';

// check if the donation form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // get all the form data
  $food_item = $_POST['food_item'];
  $quantity = $_POST['quantity'];
  $description = $_POST['description'];
  $location = $_POST['location'];
  $contact = $_POST['contact'];
  $expiry_date = $_POST['expiry_date'];
  $donor_id = $_SESSION['user_id']; // who's making this donation
  $status = 'pending'; // new donations start as pending
  
  // insert the donation into database
  $stmt = $conn->prepare("INSERT INTO donations (donor_id, food, quantity, description, location, contact, expiry_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
  $stmt->bind_param("isssssss", $donor_id, $food_item, $quantity, $description, $location, $contact, $expiry_date, $status);
  
  // try to save the donation
  if ($stmt->execute()) {
    $success = "Donation posted successfully! Thank you for helping reduce food waste.";
  } else {
    $error = "Error posting donation. Please try again.";
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Donation - SpareBite</title>
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
    <!-- donation form section -->
    <section class="form-container">
      <div class="form-wrapper" style="max-width: 600px;">
        <h1 class="form-title">New Food Donation</h1>
        
        <!-- show error message if something went wrong -->
        <?php if (isset($error)): ?>
          <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- show success message if donation was created -->
        <?php if (isset($success)): ?>
          <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <!-- the donation form -->
        <form method="post">
          <div class="form-group">
            <!-- what kind of food is being donated -->
            <label for="food_item" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2e7d32;">Food Item</label>
            <input type="text" name="food_item" id="food_item" class="form-input" required placeholder="e.g. Fresh vegetables, Bread, Cooked rice">
          </div>
          
          <div class="form-group">
            <!-- how much food is available -->
            <label for="quantity" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2e7d32;">Quantity</label>
            <input type="text" name="quantity" id="quantity" class="form-input" required placeholder="e.g. 5 kg, 20 portions, 10 loaves">
          </div>
          
          <div class="form-group">
            <!-- detailed description of the food -->
            <label for="description" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2e7d32;">Description</label>
            <textarea name="description" id="description" class="form-input" rows="3" required placeholder="Describe the food condition, preparation method, etc."></textarea>
          </div>
          
          <div class="form-group">
            <!-- where to pick up the food -->
            <label for="location" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2e7d32;">Location (Johor Bahru)</label>
            <input type="text" name="location" id="location" class="form-input" required placeholder="e.g. Taman Daya, Skudai, JB City Centre">
          </div>
          
          <div class="form-group">
            <!-- how to contact the donor -->
            <label for="contact" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2e7d32;">Contact Information</label>
            <input type="text" name="contact" id="contact" class="form-input" required placeholder="Phone number or WhatsApp">
          </div>
          
          <div class="form-group">
            <!-- when the food expires -->
            <label for="expiry_date" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2e7d32;">Best Before / Expiry Date</label>
            <input type="date" name="expiry_date" id="expiry_date" class="form-input" required>
          </div>
          
          <button type="submit" class="form-button">Post Donation</button>
        </form>
        
        <!-- link back to dashboard -->
        <p style="text-align: center; margin-top: 1rem; color: #666;">
          <a href="dashboard.php" style="color: #4caf50; text-decoration: none;">← Back to Dashboard</a>
        </p>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 SpareBite - Reducing food waste, one donation at a time!</p>
  </footer>

  <script src="script.js"></script>
</body>
</html> 