<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

include 'db.php';

// Handle settings update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['update_settings'])) {
    $updated_count = 0;
    
    foreach ($_POST as $key => $value) {
      if ($key !== 'update_settings') {
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
          $updated_count++;
        }
        $stmt->close();
      }
    }
    
    if ($updated_count > 0) {
      $success = "Settings updated successfully!";
    } else {
      $info = "No changes were made.";
    }
  }
  
  if (isset($_POST['add_setting'])) {
    $new_key = trim($_POST['new_setting_key']);
    $new_value = trim($_POST['new_setting_value']);
    $new_description = trim($_POST['new_setting_description']);
    
    if (!empty($new_key) && !empty($new_value)) {
      $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $new_key, $new_value, $new_description);
      if ($stmt->execute()) {
        $success = "New setting added successfully!";
      } else {
        $error = "Error adding new setting. The setting key might already exist.";
      }
      $stmt->close();
    } else {
      $error = "Setting key and value are required.";
    }
  }
  
  if (isset($_POST['delete_setting'])) {
    $setting_key = $_POST['setting_key'];
    $stmt = $conn->prepare("DELETE FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $setting_key);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
      $success = "Setting deleted successfully!";
    } else {
      $error = "Error deleting setting.";
    }
    $stmt->close();
  }
}

// Get all system settings
$query = "SELECT * FROM system_settings ORDER BY setting_key";
$result = $conn->query($query);

// Get system statistics
$stats_query = "SELECT 
  (SELECT COUNT(*) FROM users) as total_users,
  (SELECT COUNT(*) FROM donations) as total_donations,
  (SELECT COUNT(*) FROM donations WHERE status = 'completed') as completed_donations,
  (SELECT COUNT(*) FROM notifications) as total_notifications,
  (SELECT COUNT(*) FROM system_settings) as total_settings";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>System Settings - SpareBite</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .settings-grid {
      display: grid;
      gap: 1rem;
    }
    
    .setting-item {
      background: #f9f9f9;
      padding: 1.5rem;
      border-radius: 8px;
      border-left: 4px solid #4caf50;
    }
    
    .setting-item h4 {
      margin: 0 0 0.5rem 0;
      color: #2e7d32;
      font-size: 1.1rem;
    }
    
    .setting-item p {
      margin: 0 0 1rem 0;
      color: #666;
      font-size: 0.9rem;
    }
    
    .setting-input {
      width: 100%;
      padding: 0.8rem;
      border: 2px solid #c8e6c9;
      border-radius: 5px;
      font-size: 1rem;
    }
    
    .setting-input:focus {
      outline: none;
      border-color: #4caf50;
    }
    
    .stats-overview {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }
    
    .stat-card {
      background: linear-gradient(135deg, #4caf50, #66bb6a);
      color: white;
      padding: 1.5rem;
      border-radius: 10px;
      text-align: center;
    }
    
    .stat-card h3 {
      font-size: 2rem;
      margin: 0;
    }
    
    .stat-card p {
      margin: 0.5rem 0 0 0;
      opacity: 0.9;
    }
    
    .add-setting-form {
      background: #e8f5e8;
      padding: 1.5rem;
      border-radius: 8px;
      margin-top: 2rem;
    }
    
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    
    .delete-btn {
      background: #f44336;
      color: white;
      border: none;
      padding: 0.3rem 0.8rem;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.8rem;
      margin-top: 0.5rem;
    }
    
    .delete-btn:hover {
      background: #da190b;
    }
    
    .system-info {
      background: #fff3cd;
      border: 1px solid #ffeaa7;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 2rem;
    }
    
    .system-info h4 {
      margin: 0 0 0.5rem 0;
      color: #856404;
    }
    
    .system-info p {
      margin: 0;
      color: #856404;
      font-size: 0.9rem;
    }
    
    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
      }
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
        <a href="report.php">Reports</a>
        <a href="logout.php">Logout</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="dashboard-container">
      <div class="dashboard-header">
        <h1 class="dashboard-title">⚙️ System Settings</h1>
        <p class="dashboard-role">Configure system-wide settings and monitor platform health</p>
      </div>
      
      <!-- System Overview -->
      <div class="dashboard-content">
        <h3>📊 System Overview</h3>
        <div class="stats-overview">
          <div class="stat-card">
            <h3><?php echo $stats['total_users']; ?></h3>
            <p>Total Users</p>
          </div>
          <div class="stat-card">
            <h3><?php echo $stats['total_donations']; ?></h3>
            <p>Total Donations</p>
          </div>
          <div class="stat-card">
            <h3><?php echo $stats['completed_donations']; ?></h3>
            <p>Completed Donations</p>
          </div>
          <div class="stat-card">
            <h3><?php echo $stats['total_settings']; ?></h3>
            <p>System Settings</p>
          </div>
        </div>
      </div>
      
      <!-- System Information -->
      <div class="dashboard-content">
        <div class="system-info">
          <h4>🔧 System Information</h4>
          <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
          <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
          <p><strong>Database:</strong> MySQL</p>
          <p><strong>Last Settings Update:</strong> 
            <?php 
            $last_update_query = "SELECT MAX(updated_at) as last_update FROM system_settings";
            $last_update_result = $conn->query($last_update_query);
            $last_update = $last_update_result->fetch_assoc()['last_update'];
            echo $last_update ? date('M j, Y H:i:s', strtotime($last_update)) : 'Never';
            ?>
          </p>
        </div>
      </div>

      <!-- Settings Management -->
      <div class="dashboard-content">
        <?php if (isset($error)): ?>
          <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
          <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($info)): ?>
          <div class="message" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;">
            <?php echo $info; ?>
          </div>
        <?php endif; ?>
        
        <h3>⚙️ Current Settings</h3>
        
        <form method="post">
          <div class="settings-grid">
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while ($setting = $result->fetch_assoc()): ?>
                <div class="setting-item">
                  <h4><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $setting['setting_key']))); ?></h4>
                  <?php if ($setting['description']): ?>
                    <p><?php echo htmlspecialchars($setting['description']); ?></p>
                  <?php endif; ?>
                  
                  <?php if ($setting['setting_key'] === 'notification_enabled'): ?>
                    <select name="<?php echo htmlspecialchars($setting['setting_key']); ?>" class="setting-input">
                      <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Enabled</option>
                      <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                  <?php else: ?>
                    <input type="text" 
                           name="<?php echo htmlspecialchars($setting['setting_key']); ?>" 
                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                           class="setting-input"
                           <?php echo in_array($setting['setting_key'], ['max_donations_per_user', 'auto_expire_days']) ? 'pattern="[0-9]+" title="Please enter a valid number"' : ''; ?>>
                  <?php endif; ?>
                  
                  <?php if (!in_array($setting['setting_key'], ['site_name', 'site_tagline', 'admin_email'])): ?>
                    <form method="post" style="display: inline;">
                      <input type="hidden" name="setting_key" value="<?php echo htmlspecialchars($setting['setting_key']); ?>">
                      <button type="submit" name="delete_setting" class="delete-btn" 
                              onclick="return confirm('Are you sure you want to delete this setting?')">
                        Delete Setting
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              <?php endwhile; ?>
            <?php endif; ?>
          </div>
          
          <div style="text-align: center; margin-top: 2rem;">
            <button type="submit" name="update_settings" class="form-button" style="width: auto; padding: 1rem 2rem;">
              💾 Save All Settings
            </button>
          </div>
        </form>
        
        <!-- Add New Setting -->
        <div class="add-setting-form">
          <h3>➕ Add New Setting</h3>
          <form method="post">
            <div class="form-row">
              <div class="form-group">
                <label>Setting Key:</label>
                <input type="text" name="new_setting_key" class="setting-input" 
                       placeholder="e.g., max_file_size" required>
              </div>
              <div class="form-group">
                <label>Setting Value:</label>
                <input type="text" name="new_setting_value" class="setting-input" 
                       placeholder="e.g., 10" required>
              </div>
            </div>
            <div class="form-group">
              <label>Description (Optional):</label>
              <input type="text" name="new_setting_description" class="setting-input" 
                     placeholder="Brief description of what this setting does">
            </div>
            <button type="submit" name="add_setting" class="form-button" style="width: auto; padding: 0.8rem 1.5rem;">
              Add Setting
            </button>
          </form>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 SpareBite - Reducing food waste, one donation at a time!</p>
  </footer>

  <script>
    // Auto-save notification
    let saveTimeout;
    const inputs = document.querySelectorAll('.setting-input');
    
    inputs.forEach(input => {
      input.addEventListener('input', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(function() {
          // Optional: Add auto-save functionality here
        }, 2000);
      });
    });
    
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
      const numberInputs = document.querySelectorAll('input[pattern="[0-9]+"]');
      numberInputs.forEach(input => {
        if (input.value && !input.value.match(/^[0-9]+$/)) {
          e.preventDefault();
          alert('Please enter valid numbers for numeric settings.');
          input.focus();
          return false;
        }
      });
    });
  </script>
</body>
</html> 