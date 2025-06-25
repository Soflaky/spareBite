<?php
// AJAX handler for adding/removing favorites
session_start();

// Only recipients can manage favorites
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'recipient') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include 'db.php';

// Get the request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$donor_id = $input['donor_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if ($action === 'add') {
    // Add to favorites
    $stmt = $conn->prepare("INSERT IGNORE INTO favorites (user_id, donor_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $donor_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Added to favorites!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to favorites']);
    }
    $stmt->close();
    
} elseif ($action === 'remove') {
    // Remove from favorites
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND donor_id = ?");
    $stmt->bind_param("ii", $user_id, $donor_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove from favorites']);
    }
    $stmt->close();
    
} elseif ($action === 'check') {
    // Check if donor is favorited
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND donor_id = ?");
    $stmt->bind_param("ii", $user_id, $donor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode(['is_favorite' => $result->num_rows > 0]);
    $stmt->close();
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?> 