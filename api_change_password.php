<?php
require_once 'db.php';
session_start();
setJsonHeaders();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON, TRUE);
    
    $currentPassword = $data['current_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required']);
        exit;
    }
    
    if ($newPassword !== $confirmPassword) {
        http_response_code(400);
        echo json_encode(['error' => 'New passwords do not match']);
        exit;
    }
    
    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'New password must be at least 6 characters']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Current password is incorrect']);
            exit;
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE admin_users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->execute([$hashedPassword, $_SESSION['admin_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid Request']);
?>
