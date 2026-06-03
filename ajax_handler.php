<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle different actions
switch($action) {
    case 'add_task':
        addTask($pdo, $user_id);
        break;
    case 'delete_task':
        deleteTask($pdo, $user_id);
        break;
    case 'toggle_task':
        toggleTask($pdo, $user_id);
        break;
    case 'get_tasks':
        getTasks($pdo, $user_id);
        break;
          case 'edit_task':      
        editTask($pdo, $user_id);
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}


function editTask($pdo, $user_id) {
    $task_id = $_POST['task_id'] ?? 0;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'pending';
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        return;
    }
    
    try {
        $sql = "UPDATE tasks 
                SET title = :title, 
                    description = :description, 
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => htmlspecialchars($title),
            ':description' => htmlspecialchars($description),
            ':status' => $status,
            ':id' => $task_id,
            ':user_id' => $user_id
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Task updated successfully'
        ]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
}
function addTask($pdo, $user_id) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        return;
    }
    
    try {
        $sql = "INSERT INTO tasks (user_id, title, description) VALUES (:user_id, :title, :description)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':title' => htmlspecialchars($title),
            ':description' => htmlspecialchars($description)
        ]);
        
        $task_id = $pdo->lastInsertId();
        
        // Get the newly created task
        $sql = "SELECT * FROM tasks WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $task_id]);
        $task = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Task added successfully',
            'task' => $task
        ]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function deleteTask($pdo, $user_id) {
    $task_id = $_POST['task_id'] ?? $_GET['task_id'] ?? 0;
    
    try {
        $sql = "DELETE FROM tasks WHERE id = :id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $task_id, ':user_id' => $user_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Task deleted successfully'
        ]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Delete failed']);
    }
}

function toggleTask($pdo, $user_id) {
    $task_id = $_POST['task_id'] ?? $_GET['task_id'] ?? 0;
    
    try {
        $sql = "UPDATE tasks SET status = IF(status = 'pending', 'completed', 'pending') 
                WHERE id = :id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $task_id, ':user_id' => $user_id]);
        
        // Get updated status
        $sql = "SELECT status FROM tasks WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $task_id]);
        $task = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Status toggled',
            'status' => $task['status']
        ]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Toggle failed']);
    }
}

function getTasks($pdo, $user_id) {
    $sql = "SELECT * FROM tasks WHERE user_id = :user_id ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $tasks = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);
}
?>