<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

include 'db.php';

// Handle user actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'toggle_status') {
      $new_status = $_POST['current_status'] == '1' ? 0 : 1;
      $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role != 'admin'");
      $stmt->bind_param("ii", $new_status, $user_id);
      if ($stmt->execute()) {
        $success = "User status updated successfully!";
      } else {
        $error = "Error updating user status.";
      }
      $stmt->close();
    } elseif ($action === 'delete_user') {
      $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
      $stmt->bind_param("i", $user_id);
      if ($stmt->execute() && $stmt->affected_rows > 0) {
        $success = "User deleted successfully!";
      } else {
        $error = "Error deleting user or cannot delete admin users.";
      }
      $stmt->close();
    }
  }
}

// Get all users
$query = "SELECT * FROM users ORDER BY role, created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users - SpareBite</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .users-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }
    
    .users-table th, .users-table td {
      padding: 0.8rem;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    
    .users-table th {
      background-color: #4caf50;
      color: white;
    }
    
    .users-table tr:hover {
      background-color: #f5f5f5;
    }
    
    .status-active {
      color: #4caf50;
      font-weight: bold;
    }
    
    .status-inactive {
      color: #f44336;
      font-weight: bold;
    }
    
    .role-badge {
      padding: 0.3rem 0.8rem;
      border-radius: 15px;
      font-size: 0.8rem;
      font-weight: bold;
    }
    
    .role-admin {
      background-color: #ff9800;
      color: white;
    }
    
    .role-donor {
      background-color: #2196f3;
      color: white;
    }
    
    .role-recipient {
      background-color: #9c27b0;
      color: white;
    }
    
    .action-buttons {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    
    .btn-small {
      padding: 0.3rem 0.6rem;
      font-size: 0.8rem;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }
    
    .btn-edit {
      background-color: #2196f3;
      color: white;
    }
    
    .btn-toggle {
      background-color: #ff9800;
      color: white;
    }
    
    .btn-delete {
      background-color: #f44336;
      color: white;
    }
    
    .btn-small:hover {
      opacity: 0.8;
    }
    
    .user-stats {
      font-size: 0.8rem;
      color: #666;
    }
    
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
      background-color: white;
      margin: 5% auto;
      padding: 2rem;
      border-radius: 10px;
      width: 90%;
      max-width: 500px;
      position: relative;
    }
    
    .close {
      position: absolute;
      right: 1rem;
      top: 1rem;
      font-size: 2rem;
      cursor: pointer;
      color: #999;
    }
    
    .close:hover {
      color: #333;
    }
    
    .stats-grid {
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
        <h1 class="dashboard-title">👥 Manage Users</h1>
        <p class="dashboard-role">Manage user accounts and monitor system activity</p>
      </div>
      
      <!-- User Statistics -->
      <div class="dashboard-content">
        <h3>📊 User Statistics</h3>
        <div class="stats-grid">
          <?php
          $stats_query = "SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN role = 'donor' THEN 1 END) as total_donors,
            COUNT(CASE WHEN role = 'recipient' THEN 1 END) as total_recipients,
            COUNT(CASE WHEN role = 'admin' THEN 1 END) as total_admins,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_users,
            COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_users
            FROM users";
          $stats_result = $conn->query($stats_query);
          $stats = $stats_result->fetch_assoc();
          ?>
          <div class="stat-card">
            <h3><?php echo $stats['total_users']; ?></h3>
            <p>Total Users</p>
          </div>
          <div class="stat-card">
            <h3><?php echo $stats['total_donors']; ?></h3>
            <p>Donors</p>
          </div>
          <div class="stat-card">
            <h3><?php echo $stats['total_recipients']; ?></h3>
            <p>Recipients</p>
          </div>
          <div class="stat-card">
            <h3><?php echo $stats['active_users']; ?></h3>
            <p>Active Users</p>
          </div>
        </div>
      </div>
      
      <div class="dashboard-content">
        <?php if (isset($error)): ?>
          <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
          <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <h3>All Users</h3>
        <?php if ($result && $result->num_rows > 0): ?>
          <table class="users-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Full Name</th>
                <th>Role</th>
                <th>Status</th>
                <th>Donations</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($user = $result->fetch_assoc()): ?>
                <tr>
                  <td>#<?php echo $user['id']; ?></td>
                  <td><?php echo htmlspecialchars($user['username']); ?></td>
                  <td><?php echo htmlspecialchars($user['email']); ?></td>
                  <td><?php echo htmlspecialchars($user['full_name'] ?? 'Not provided'); ?></td>
                  <td>
                    <span class="role-badge role-<?php echo $user['role']; ?>">
                      <?php echo ucfirst($user['role']); ?>
                    </span>
                  </td>
                  <td>
                    <span class="<?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                      <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                  </td>
                  <td>
                    <div class="user-stats">
                      Total: <?php echo $user['total_donations']; ?><br>
                      Completed: <?php echo $user['completed_donations']; ?>
                    </div>
                  </td>
                  <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                  <td>
                    <div class="action-buttons">
                      <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="btn-small btn-edit">
                        Edit
                      </button>
                      
                      <?php if ($user['role'] !== 'admin'): ?>
                        <form method="post" style="display: inline;">
                          <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                          <input type="hidden" name="action" value="toggle_status">
                          <input type="hidden" name="current_status" value="<?php echo $user['is_active']; ?>">
                          <button type="submit" class="btn-small btn-toggle" 
                                  onclick="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?')">
                            <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                          </button>
                        </form>
                        
                        <form method="post" style="display: inline;">
                          <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                          <input type="hidden" name="action" value="delete_user">
                          <button type="submit" class="btn-small btn-delete" 
                                  onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone!')">
                            Delete
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No users found.</p>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <!-- Edit User Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>
      <h2>Edit User</h2>
      <form method="post" id="editForm">
        <input type="hidden" name="action" value="update_user">
        <input type="hidden" name="user_id" id="edit_user_id">
        
        <div class="form-group">
          <label>Username:</label>
          <input type="text" name="username" id="edit_username" class="form-input" required>
        </div>
        
        <div class="form-group">
          <label>Email:</label>
          <input type="email" name="email" id="edit_email" class="form-input" required>
        </div>
        
        <div class="form-group">
          <label>Full Name:</label>
          <input type="text" name="full_name" id="edit_full_name" class="form-input">
        </div>
        
        <div class="form-group">
          <label>Phone:</label>
          <input type="text" name="phone" id="edit_phone" class="form-input">
        </div>
        
        <div class="form-group">
          <label>Address:</label>
          <textarea name="address" id="edit_address" class="form-input" rows="3"></textarea>
        </div>
        
        <div class="form-group">
          <label>Role:</label>
          <select name="role" id="edit_role" class="form-select" required>
            <option value="donor">Donor</option>
            <option value="recipient">Recipient</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
          <button type="button" onclick="closeModal()" style="background: #666; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 5px; cursor: pointer;">
            Cancel
          </button>
          <button type="submit" class="form-button" style="width: auto; padding: 0.8rem 1.5rem;">
            Update User
          </button>
        </div>
      </form>
    </div>
  </div>

  <footer>
    <p>&copy; 2025 SpareBite - Reducing food waste, one donation at a time!</p>
  </footer>

  <script>
    function editUser(user) {
      document.getElementById('edit_user_id').value = user.id;
      document.getElementById('edit_username').value = user.username;
      document.getElementById('edit_email').value = user.email;
      document.getElementById('edit_full_name').value = user.full_name || '';
      document.getElementById('edit_phone').value = user.phone || '';
      document.getElementById('edit_address').value = user.address || '';
      document.getElementById('edit_role').value = user.role;
      
      document.getElementById('editModal').style.display = 'block';
    }
    
    function closeModal() {
      document.getElementById('editModal').style.display = 'none';
    }
    
    // Close modal when clicking outside of it
    window.onclick = function(event) {
      var modal = document.getElementById('editModal');
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    }
  </script>
</body>
</html> 