<?php
// start session to access session variables
session_start();
// destroy all session data - this logs the user out
session_destroy();
// also clear the username cookie by setting it to expire in the past
setcookie('username', '', time() - 3600, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logout - SpareBite</title>
  <link rel="stylesheet" href="styles.css">
  <!-- this automatically redirects to homepage after 3 seconds -->
  <meta http-equiv="refresh" content="3;url=index.html">
</head>
<body>
  <!-- header for logged out user -->
  <header>
    <div class="header-content">
      <div class="logo-container">
        <img src="spareBiteLogo.png" alt="SpareBite Logo" class="logo">
        <h1 class="brand">🌱 SpareBite</h1>
      </div>
      <!-- navigation links for non-logged in users -->
      <nav class="nav-links">
        <a href="index.html">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      </nav>
    </div>
  </header>

  <main>
    <!-- goodbye message section -->
    <section class="form-container">
      <div class="form-wrapper" style="text-align: center;">
        <h1 class="form-title">👋 Goodbye!</h1>
        
        <!-- confirmation message -->
        <p style="color: #666; margin: 2rem 0;">
          Thank you for using SpareBite! You have been successfully logged out.
        </p>
        
        <!-- motivational message -->
        <p style="color: #4caf50; margin-bottom: 2rem;">
          🌱 Thanks for helping reduce food waste!
        </p>
        
        <!-- redirect info -->
        <p style="color: #999; font-size: 0.9rem; margin-bottom: 2rem;">
          Redirecting to homepage in 3 seconds...
        </p>
        
        <!-- manual link in case auto-redirect doesn't work -->
        <a href="index.html" class="cta-button">Go to Homepage</a>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 SpareBite - Reducing food waste, one donation at a time! 🌱</p>
  </footer>
</body>
</html>