<?php
// start session to track user login status
session_start();
// get database connection 
include 'db.php';

// check if registration form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // get form data
  $username = $_POST['username'];
  // hash the password for security - never store plain text passwords!
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $role = $_POST['role']; // either 'donor' or 'recipient'
  
  // prepare query to insert new user into database
  $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $username, $password, $role);
  
  // try to execute the insert
  if ($stmt->execute()) {
    // success! save username in cookie
    setcookie("username", $username, time()+3600);
    $success = "Account created successfully! You can now login.";
  } else {
    // failed - probably username already exists
    $error = "Error creating account. Username might already exist.";
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - SpareBite</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header>
    <div class="header-content">
      <div class="logo-container">
        <img src="spareBiteLogo.png" alt="SpareBite Logo" class="logo">
        <h1 class="brand">SpareBite</h1>
      </div>
      <nav class="nav-links">
        <a href="index.html">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="form-container">
      <div class="form-wrapper">
        <h1 class="form-title">Register</h1>
        
        <?php if (isset($error)): ?>
          <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
          <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post" name="auth" onsubmit="return validateForm()">
          <div class="form-group">
            <input type="text" name="username" class="form-input" required placeholder="Choose a username">
          </div>
          <div class="form-group">
            <input type="password" name="password" class="form-input" required placeholder="Create a password">
          </div>
          <div class="form-group">
            <select name="role" class="form-select" required>
              <option value="">Select your role</option>
              <option value="donor">Donor (Restaurant/Shop/Individual)</option>
              <option value="recipient">Recipient (Food Bank/Charity)</option>
            </select>
          </div>
          <button type="submit" class="form-button">Register</button>
        </form>
        
        <p style="text-align: center; margin-top: 1rem; color: #666;">
          Already have an account? <a href="login.php" style="color: #4caf50; text-decoration: none;">Login here</a>
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