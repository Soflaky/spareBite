<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}

include 'db.php';

// Check if user_id exists in session
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $user_id = $_SESSION['user_id'];
  $action = $_POST['action'];
  
  if ($action === 'update_username') {
    $new_username = trim($_POST['new_username']);
    
    // Validate new username
    if (empty($new_username)) {
      $error = "Username cannot be empty.";
    } elseif (strlen($new_username) < 3) {
      $error = "Username must be at least 3 characters long.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
      $error = "Username can only contain letters, numbers, and underscores.";
    } else {
      // Check if username already exists
      $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
      $check_stmt->bind_param("si", $new_username, $user_id);
      $check_stmt->execute();
      $check_result = $check_stmt->get_result();
      
      if ($check_result->num_rows > 0) {
        $error = "Username is already taken. Please choose another one.";
      } else {
        // Update username
        $update_stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_username, $user_id);
        
        if ($update_stmt->execute()) {
          $_SESSION['username'] = $new_username;
          setcookie("username", $new_username, time()+3600);
          $success = "Username updated successfully!";
        } else {
          $error = "Error updating username. Please try again.";
        }
        $update_stmt->close();
      }
      $check_stmt->close();
    }
  } elseif ($action === 'update_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!password_verify($current_password, $user['password'])) {
      $error = "Current password is incorrect.";
    } elseif (empty($new_password)) {
      $error = "New password cannot be empty.";
    } elseif (strlen($new_password) < 6) {
      $error = "New password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
      $error = "New passwords do not match.";
    } elseif ($current_password === $new_password) {
      $error = "New password must be different from current password.";
    } else {
      // Update password
      $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
      $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
      $update_stmt->bind_param("si", $hashed_password, $user_id);
      
      if ($update_stmt->execute()) {
        $success = "Password updated successfully!";
      } else {
        $error = "Error updating password. Please try again.";
      }
      $update_stmt->close();
    }
    $stmt->close();
  } elseif ($action === 'delete_account') {
    $delete_password = $_POST['delete_password'];
    $confirm_delete = isset($_POST['confirm_delete']) ? $_POST['confirm_delete'] : '';
    
    // Validate password and confirmation
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    
    if (!password_verify($delete_password, $user_data['password'])) {
      $error = "Password is incorrect.";
    } elseif ($confirm_delete !== 'DELETE') {
      $error = "Please type 'DELETE' to confirm account deletion.";
    } else {
      // Check if user has active donations (optional - you might want to handle this differently)
      $check_donations = $conn->prepare("SELECT COUNT(*) as count FROM donations WHERE donor_id = ? AND status IN ('pending', 'requested', 'approved')");
      $check_donations->bind_param("i", $user_id);
      $check_donations->execute();
      $donation_result = $check_donations->get_result();
      $donation_count = $donation_result->fetch_assoc()['count'];
      $check_donations->close();
      
      if ($donation_count > 0) {
        $error = "Cannot delete account. You have $donation_count active donation(s) pending. Please complete or cancel them first.";
      } else {
        // Begin transaction to safely delete user and related data
        $conn->begin_transaction();
        
        try {
          // Delete user's donations (completed/cancelled ones)
          $delete_donations = $conn->prepare("DELETE FROM donations WHERE donor_id = ? OR recipient_id = ?");
          $delete_donations->bind_param("ii", $user_id, $user_id);
          $delete_donations->execute();
          $delete_donations->close();
          
          // Delete user's notifications
          $delete_notifications = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
          $delete_notifications->bind_param("i", $user_id);
          $delete_notifications->execute();
          $delete_notifications->close();
          
          // Delete the user account
          $delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
          $delete_user->bind_param("i", $user_id);
          $delete_user->execute();
          $delete_user->close();
          
          // Commit the transaction
          $conn->commit();
          
          // Destroy session and redirect
          session_destroy();
          setcookie("username", "", time() - 3600); // Clear cookie
          
          // Redirect to home page with message
          header("Location: index.html?message=account_deleted");
          exit();
          
        } catch (Exception $e) {
          // Rollback on error
          $conn->rollback();
          $error = "Error deleting account. Please try again.";
        }
      }
    }
    $stmt->close();
  }
}

// Get current user info
$stmt = $conn->prepare("SELECT username,full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Check if user was found
if (!$user) {
  session_destroy();
  header("Location: login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile - SpareBite</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .profile-container {
      max-width: 800px;
      margin: 2rem auto;
      padding: 0 1rem;
    }
    
    .profile-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .profile-tabs {
      display: flex;
      justify-content: center;
      margin-bottom: 2rem;
      border-bottom: 2px solid #ddd;
    }
    
    .tab-button {
      background: none;
      border: none;
      padding: 1rem 2rem;
      cursor: pointer;
      font-size: 1rem;
      color: #666;
      transition: all 0.3s;
      border-bottom: 3px solid transparent;
    }
    
    .tab-button.active {
      color: #4caf50;
      border-bottom-color: #4caf50;
    }
    
    .tab-button:hover {
      color: #4caf50;
    }
    
    .tab-content {
      display: none;
      background: white;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 1rem;
    }
    
    .tab-content.active {
      display: block;
    }
    
    .form-section {
      margin-bottom: 2rem;
    }
    
    .form-section h3 {
      margin-bottom: 1rem;
      color: #333;
    }
    
    .info-box {
      background: #f0f8ff;
      border-left: 4px solid #2196f3;
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 5px;
    }
    
    .warning-box {
      background: #fff3cd;
      border-left: 4px solid #ff9800;
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 5px;
    }
    
    .current-info {
      background: #f5f5f5;
      padding: 1rem;
      border-radius: 5px;
      margin-bottom: 1rem;
    }
    
    .current-info label {
      font-weight: bold;
      color: #666;
    }
    
    .password-requirements {
      font-size: 0.9rem;
      color: #666;
      margin-top: 0.5rem;
    }
    
    .password-requirements ul {
      margin: 0.5rem 0;
      padding-left: 1.5rem;
    }
    
    .form-actions {
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
      margin-top: 2rem;
    }
    
    .btn-secondary {
      background: #6c757d;
      color: white;
      padding: 0.8rem 1.5rem;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }
    
    .btn-secondary:hover {
      background: #5a6268;
    }
    
    .delete-tab {
      color: #dc3545 !important;
    }
    
    .delete-tab:hover, .delete-tab.active {
      color: #dc3545 !important;
      border-bottom-color: #dc3545 !important;
    }
    
    .danger-box {
      background: #f8d7da;
      border-left: 4px solid #dc3545;
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 5px;
      color: #721c24;
    }
    
    .btn-danger {
      background: #dc3545 !important;
    }
    
    .btn-danger:hover {
      background: #c82333 !important;
    }
  </style>
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
        <a href="dashboard.php">Dashboard</a>
        <a href="edit_profile.php">Profile</a>
        <a href="logout.php">Logout</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="profile-container">
      <div class="profile-header">
        <h1>Edit Profile</h1>
        <p>Welcome, <strong><?php echo htmlspecialchars($user['username']); ?></strong>!</p>
      </div>
      
      <?php if ($success): ?>
        <div class="message success"><?php echo $success; ?></div>
      <?php endif; ?>
      
      <?php if ($error): ?>
        <div class="message error"><?php echo $error; ?></div>
      <?php endif; ?>
      
      <div class="profile-tabs">
        <button class="tab-button active" onclick="showTab('username')">Change Username</button>
        <button class="tab-button" onclick="showTab('password')">Change Password</button>
        <button class="tab-button delete-tab" onclick="showTab('delete')">Delete Account</button>
      </div>
      
      <!-- Username Tab -->
      <div id="username-tab" class="tab-content active">
        <div class="form-section">
          <h3>Change Username</h3>
          
          <div class="current-info">
            <label>Current Username:</label>
            <span><?php echo htmlspecialchars($user['username']); ?></span>
          </div>
          
          <div class="info-box">
            <strong>Important:</strong> Changing your username will update your login credentials. Make sure to remember your new username for future logins.
          </div>
          
          <form method="post">
            <input type="hidden" name="action" value="update_username">
            
            <div class="form-group">
              <label for="new_username">New Username:</label>
              <input type="text" id="new_username" name="new_username" class="form-input" required 
                     placeholder="Enter new username" maxlength="50">
              <div class="password-requirements">
                <strong>Username requirements:</strong>
                <ul>
                  <li>At least 3 characters long</li>
                  <li>Only letters, numbers, and underscores allowed</li>
                  <li>Must be unique (not taken by another user)</li>
                </ul>
              </div>
            </div>
            
            <div class="form-actions">
              <a href="dashboard.php" class="btn-secondary">Cancel</a>
              <button type="submit" class="form-button">Update Username</button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Password Tab -->
      <div id="password-tab" class="tab-content">
        <div class="form-section">
          <h3>Change Password</h3>
          
          <div class="warning-box">
            <strong>Security Notice:</strong> For your security, you must enter your current password to change it.
          </div>
          
          <form method="post">
            <input type="hidden" name="action" value="update_password">
            
            <div class="form-group">
              <label for="current_password">Current Password:</label>
              <input type="password" id="current_password" name="current_password" class="form-input" required 
                     placeholder="Enter your current password">
            </div>
            
            <div class="form-group">
              <label for="new_password">New Password:</label>
              <input type="password" id="new_password" name="new_password" class="form-input" required 
                     placeholder="Enter new password">
              <div class="password-requirements">
                <strong>Password requirements:</strong>
                <ul>
                  <li>At least 6 characters long</li>
                  <li>Must be different from current password</li>
                </ul>
              </div>
            </div>
            
            <div class="form-group">
              <label for="confirm_password">Confirm New Password:</label>
              <input type="password" id="confirm_password" name="confirm_password" class="form-input" required 
                     placeholder="Confirm new password">
            </div>
            
            <div class="form-actions">
              <a href="dashboard.php" class="btn-secondary">Cancel</a>
              <button type="submit" class="form-button">Update Password</button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Delete Account Tab -->
      <div id="delete-tab" class="tab-content">
        <div class="form-section">
          <h3>Delete Account</h3>
          
          <div class="danger-box">
            <strong>⚠️ DANGER ZONE:</strong> This action cannot be undone! Deleting your account will permanently remove all your data, donations, and account information from our system.
          </div>
          
          <div class="warning-box">
            <strong>What will be deleted:</strong>
            <ul>
              <li>Your user account and profile information</li>
              <li>All your donations (completed and cancelled)</li>
              <li>All notifications and messages</li>
              <li>Any requests you've made</li>
            </ul>
            <br>
            <strong>Note:</strong> You cannot delete your account if you have active donations pending. Please complete or cancel them first.
          </div>
          
          <form method="post" onsubmit="return confirmDelete()">
            <input type="hidden" name="action" value="delete_account">
            
            <div class="form-group">
              <label for="delete_password">Enter Your Password to Confirm:</label>
              <input type="password" id="delete_password" name="delete_password" class="form-input" required 
                     placeholder="Enter your current password">
            </div>
            
            <div class="form-group">
              <label for="confirm_delete">Type "DELETE" to confirm:</label>
              <input type="text" id="confirm_delete" name="confirm_delete" class="form-input" required 
                     placeholder="Type DELETE in capital letters" pattern="DELETE">
              <div class="password-requirements">
                <strong>Security requirement:</strong> You must type exactly "DELETE" (in capital letters) to confirm account deletion.
              </div>
            </div>
            
            <div class="form-actions">
              <a href="dashboard.php" class="btn-secondary">Cancel</a>
              <button type="submit" class="form-button btn-danger">Delete My Account Forever</button>
            </div>
          </form>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 SpareBite - Reducing food waste, one donation at a time! </p>
  </footer>

  <script>
    function showTab(tabName) {
      // Hide all tab contents
      const tabContents = document.querySelectorAll('.tab-content');
      tabContents.forEach(content => content.classList.remove('active'));
      
      // Remove active class from all buttons
      const tabButtons = document.querySelectorAll('.tab-button');
      tabButtons.forEach(button => button.classList.remove('active'));
      
      // Show selected tab content
      document.getElementById(tabName + '-tab').classList.add('active');
      
      // Add active class to clicked button
      event.target.classList.add('active');
    }
    
    // Form validation
    document.addEventListener('DOMContentLoaded', function() {
      // Username validation
      const usernameForm = document.querySelector('form[action=""][method="post"]');
      if (usernameForm) {
        const usernameInput = document.getElementById('new_username');
        if (usernameInput) {
          usernameInput.addEventListener('input', function() {
            const value = this.value;
            const regex = /^[a-zA-Z0-9_]+$/;
            
            if (value && !regex.test(value)) {
              this.setCustomValidity('Username can only contain letters, numbers, and underscores.');
            } else if (value && value.length < 3) {
              this.setCustomValidity('Username must be at least 3 characters long.');
            } else {
              this.setCustomValidity('');
            }
          });
        }
      }
      
      // Password confirmation validation
      const passwordForm = document.querySelector('form input[name="action"][value="update_password"]');
      if (passwordForm) {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePasswords() {
          if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match.');
          } else {
            confirmPassword.setCustomValidity('');
          }
        }
        
        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
      }
    });
    
    // Delete account confirmation
    function confirmDelete() {
      const deleteInput = document.getElementById('confirm_delete').value;
      const passwordInput = document.getElementById('delete_password').value;
      
      if (deleteInput !== 'DELETE') {
        alert('Please type "DELETE" exactly as shown to confirm.');
        return false;
      }
      
      if (passwordInput.length === 0) {
        alert('Please enter your password to confirm account deletion.');
        return false;
      }
      
      return confirm('⚠️ FINAL WARNING ⚠️\n\nAre you absolutely sure you want to delete your account?\n\nThis action cannot be undone and will permanently remove:\n- Your account and profile\n- All your donations\n- All your data\n\nClick OK to proceed with permanent deletion, or Cancel to keep your account.');
    }
  </script>
</body>
</html>