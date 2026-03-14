<?php
require_once 'db.php';
session_start();
setJsonHeaders();

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password are required']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        } else if (password_verify($password, $user['password'])) {
            // Success
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Update last login
            $pdo->prepare("UPDATE admin_users SET last_login = datetime('now') WHERE id = ?")->execute([$user['id']]);
            
            echo json_encode([
                'success' => true,
                'user' => [
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
        break;
        
    case 'session':
        if (isset($_SESSION['admin_id'])) {
            echo json_encode([
                'valid' => true,
                'user' => [
                    'username' => $_SESSION['username'],
                    'full_name' => $_SESSION['full_name'],
                    'role' => $_SESSION['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['valid' => false]);
        }
        break;
        
    case 'logout':
        session_destroy();
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
