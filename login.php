<?php
// start the session - this lets us track if user is logged in across pages
session_start();
// include database connection
include 'db.php';

// check if the form was submitted (user clicked login button)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // get the username and password from the form
  $username = $_POST['username'];
  $password = $_POST['password'];
  
  // prepare a secure query to find user in database
  // using prepared statements prevents sql injection attacks
  $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->store_result();
  
  // check if we found a user with that username
  if ($stmt->num_rows > 0) {
    // get the user data from database
    $stmt->bind_result($id, $hashed_password, $role);
    $stmt->fetch();
    
    // check if the password is correct using password_verify
    // this compares the plain text password with the hashed one in database
    if (password_verify($password, $hashed_password)) {
      // password is correct! save user info in session
      $_SESSION['user_id'] = $id;
      $_SESSION['username'] = $username;
      $_SESSION['role'] = $role;
      
      // also save username in a cookie for the welcome message
      setcookie("username", $username, time()+3600);
      
      // redirect to dashboard - login successful!
      header("Location: dashboard.php");
      exit();
    } else {
      // password is wrong
      $error = "Invalid username or password";
    }
  } else {
    // username not found
    $error = "Invalid username or password";
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - SpareBite</title>
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
        <h1 class="form-title">Login</h1>
        
        <?php if (isset($error)): ?>
          <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" name="auth" onsubmit="return validateForm()">
          <div class="form-group">
            <input type="text" name="username" class="form-input" required placeholder="Enter your username">
          </div>
          <div class="form-group">
            <input type="password" name="password" class="form-input" required placeholder="Enter your password">
          </div>
          <button type="submit" class="form-button">Login</button>
        </form>
        
        <p style="text-align: center; margin-top: 1rem; color: #666;">
          Don't have an account? <a href="register.php" style="color: #4caf50; text-decoration: none;">Register here</a>
        </p>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 SpareBite - Reducing food waste, one donation at a time! </p>
  </footer>

  <script src="script.js"></script>
</body>
</html>