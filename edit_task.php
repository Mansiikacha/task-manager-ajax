<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if task ID is provided
if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$task_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch task details
$sql = "SELECT * FROM tasks WHERE id = :id AND user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $task_id, ':user_id' => $user_id]);
$task = $stmt->fetch();

// If task not found or doesn't belong to user
if (!$task) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    
    // Validation
    if (empty($title)) {
        $error = "❌ Task title is required!";
    } else {
        try {
            // Update task in database
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
            
            $message = "✅ Task updated successfully!";
            
            // Refresh task data
            $task['title'] = $title;
            $task['description'] = $description;
            $task['status'] = $status;
            
            // Redirect after 2 seconds
            header("refresh:2;url=dashboard.php");
            
        } catch(PDOException $e) {
            $error = "❌ Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task - Task Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-12 max-w-2xl">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-edit text-indigo-500 mr-2"></i>
                    Edit Task
                </h1>
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if ($message): ?>
                <div class="mb-4 p-4 rounded-lg bg-green-100 text-green-700 border border-green-200">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="mb-4 p-4 rounded-lg bg-red-100 text-red-700 border border-red-200">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Edit Form -->
            <form method="POST" class="space-y-6">
                <!-- Task Title -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-heading text-indigo-500 mr-1"></i>
                        Task Title *
                    </label>
                    <input type="text" 
                           name="title" 
                           value="<?php echo htmlspecialchars($task['title']); ?>"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <!-- Task Description -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-align-left text-indigo-500 mr-1"></i>
                        Description (Optional)
                    </label>
                    <textarea name="description" 
                              rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($task['description']); ?></textarea>
                    <p class="text-sm text-gray-500 mt-1">
                        <i class="fas fa-info-circle"></i>
                        You can add detailed instructions or notes here.
                    </p>
                </div>
                
                <!-- Task Status -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-flag-checkered text-indigo-500 mr-1"></i>
                        Status
                    </label>
                    <select name="status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="pending" <?php echo $task['status'] == 'pending' ? 'selected' : ''; ?>>
                            ⏳ Pending
                        </option>
                        <option value="completed" <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>
                            ✅ Completed
                        </option>
                    </select>
                </div>
                
                <!-- Task Info (Read-only) -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        Created: <?php echo date('F j, Y g:i A', strtotime($task['created_at'])); ?>
                    </p>
                    <?php if ($task['updated_at']): ?>
                        <p class="text-sm text-gray-600 mt-1">
                            <i class="fas fa-edit mr-1"></i>
                            Last updated: <?php echo date('F j, Y g:i A', strtotime($task['updated_at'])); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Form Buttons -->
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            name="update_task"
                            class="flex-1 bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition">
                        <i class="fas fa-save mr-2"></i>
                        Update Task
                    </button>
                    <a href="dashboard.php" 
                       class="flex-1 bg-gray-300 text-gray-700 py-2 rounded-lg text-center hover:bg-gray-400 transition">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>