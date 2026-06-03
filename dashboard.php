<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get all tasks
$sql = "SELECT * FROM tasks WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$tasks = $stmt->fetchAll();

// Add new task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (!empty($title)) {
        $sql = "INSERT INTO tasks (user_id, title, description) VALUES (:user_id, :title, :description)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':title' => htmlspecialchars($title),
            ':description' => htmlspecialchars($description)
        ]);
        header('Location: dashboard.php');
        exit();
    }
}

// Toggle status
if (isset($_GET['toggle'])) {
    $task_id = $_GET['toggle'];
    $sql = "UPDATE tasks SET status = IF(status = 'pending', 'completed', 'pending') WHERE id = :id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $task_id, ':user_id' => $_SESSION['user_id']]);
    header('Location: dashboard.php');
    exit();
}

// Delete task
if (isset($_GET['delete'])) {
    $task_id = $_GET['delete'];
    $sql = "DELETE FROM tasks WHERE id = :id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $task_id, ':user_id' => $_SESSION['user_id']]);
    header('Location: dashboard.php');
    exit();
}

// Statistics
$sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM tasks WHERE user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$stats = $stmt->fetch();
$pending = $stats['total'] - $stats['completed'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Task Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-tasks text-indigo-500 mr-2"></i>
                        Task Manager
                    </h1>
                    <p class="text-gray-600 mt-1">
                        Welcome, <span
                            class="font-semibold text-indigo-600"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </p>
                </div>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-blue-500">
                <p class="text-gray-500 text-sm">Total Tasks</p>
                <p class="text-2xl font-bold"><?php echo $stats['total']; ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-green-500">
                <p class="text-gray-500 text-sm">Completed</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $stats['completed']; ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-yellow-500">
                <p class="text-gray-500 text-sm">Pending</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $pending; ?></p>
            </div>
        </div>

        <!-- Add Task Form -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <form method="POST" class="space-y-3">
                <input type="text" name="title" placeholder="Task title..." required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <textarea name="description" rows="2" placeholder="Description (optional)..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                <button type="submit" name="add_task"
                    class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-plus mr-1"></i> Add Task
                </button>
            </form>
        </div>

        <!-- Tasks List -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                <h2 class="font-semibold text-gray-700"><i class="fas fa-list mr-2"></i>Your Tasks</h2>
            </div>

            <?php if (empty($tasks)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-clipboard-list text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No tasks yet. Add your first task above!</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($tasks as $task): ?>
                        <div
                            class="p-4 hover:bg-gray-50 transition <?php echo $task['status'] == 'completed' ? 'bg-green-50' : ''; ?>">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <a href="?toggle=<?php echo $task['id']; ?>" class="cursor-pointer">
                                            <?php if ($task['status'] == 'completed'): ?>
                                                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                                            <?php else: ?>
                                                <i class="far fa-circle text-gray-400 text-xl"></i>
                                            <?php endif; ?>
                                        </a>
                                        <div>
                                            <p
                                                class="<?php echo $task['status'] == 'completed' ? 'line-through text-gray-400' : 'text-gray-800'; ?> font-medium">
                                                <?php echo htmlspecialchars($task['title']); ?>
                                            </p>
                                            <?php if ($task['description']): ?>
                                                <p class="text-sm text-gray-500 mt-1">
                                                    <?php echo htmlspecialchars($task['description']); ?></p>
                                            <?php endif; ?>
                                            <p class="text-xs text-gray-400 mt-1">
                                                <i class="far fa-calendar-alt mr-1"></i>
                                                <?php echo date('M j, Y g:i A', strtotime($task['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="edit_task.php?id=<?php echo $task['id']; ?>"
                                        class="text-blue-400 hover:text-blue-600 transition" title="Edit Task">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $task['id']; ?>"
                                        class="text-red-400 hover:text-red-600 transition"
                                        onclick="return confirm('Delete this task?')" title="Delete Task">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>