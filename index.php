<?php
session_start();

// Initialize user session if not exists
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = 'Guest_' . rand(1000, 9999);
}

// File to store tasks
$tasksFile = 'tasks_' . md5($_SESSION['user']) . '.json';

// Load existing tasks
if (file_exists($tasksFile)) {
    $tasks = json_decode(file_get_contents($tasksFile), true);
} else {
    $tasks = [];
}

// Handle Add Task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $newTask = trim($_POST['task_name']);
    if (!empty($newTask)) {
        $tasks[] = [
            'id' => time() . rand(100, 999),
            'name' => htmlspecialchars($newTask),
            'done' => false,
            'created_at' => date('Y-m-d H:i:s')
        ];
        file_put_contents($tasksFile, json_encode($tasks));
    }
    header('Location: index.php');
    exit();
}

// Handle Delete Task
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    foreach ($tasks as $key => $task) {
        if ($task['id'] == $id) {
            unset($tasks[$key]);
            break;
        }
    }
    $tasks = array_values($tasks); // Reindex array
    file_put_contents($tasksFile, json_encode($tasks));
    header('Location: index.php');
    exit();
}

// Handle Toggle Task Status
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    foreach ($tasks as $key => $task) {
        if ($task['id'] == $id) {
            $tasks[$key]['done'] = !$task['done'];
            break;
        }
    }
    file_put_contents($tasksFile, json_encode($tasks));
    header('Location: index.php');
    exit();
}

// Handle Clear All Tasks
if (isset($_GET['clear_all'])) {
    $tasks = [];
    file_put_contents($tasksFile, json_encode($tasks));
    header('Location: index.php');
    exit();
}

// Statistics
$totalTasks = count($tasks);
$completedTasks = count(array_filter($tasks, fn($t) => $t['done']));
$pendingTasks = $totalTasks - $completedTasks;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager - PHP + Tailwind CSS</title>
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
                        <i class="fas fa-check-circle text-indigo-500 mr-2"></i>
                        Task Manager
                    </h1>
                    <p class="text-gray-600 mt-1">Welcome, <span class="font-semibold text-indigo-600"><?php echo $_SESSION['user']; ?></span></p>
                </div>
                <div class="text-right">
                    <a href="?clear_all=1" 
                       class="text-red-500 hover:text-red-700 text-sm"
                       onclick="return confirm('Delete all tasks? This cannot be undone!')">
                        <i class="fas fa-trash-alt mr-1"></i> Clear All
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Tasks</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $totalTasks; ?></p>
                    </div>
                    <i class="fas fa-tasks text-3xl text-blue-400"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Completed</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $completedTasks; ?></p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-400"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $pendingTasks; ?></p>
                    </div>
                    <i class="fas fa-clock text-3xl text-yellow-400"></i>
                </div>
            </div>
        </div>

        <!-- Add Task Form -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <form method="POST" class="flex gap-3">
                <input type="text" 
                       name="task_name" 
                       placeholder="Enter a new task..." 
                       class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       required>
                <button type="submit" 
                        name="add_task"
                        class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition duration-200 flex items-center gap-2">
                    <i class="fas fa-plus"></i> Add Task
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
                    <p class="text-gray-500 text-lg">No tasks yet. Add your first task above!</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($tasks as $task): ?>
                        <div class="p-4 hover:bg-gray-50 transition duration-150 <?php echo $task['done'] ? 'bg-green-50' : ''; ?>">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3 flex-1">
                                    <a href="?toggle=<?php echo $task['id']; ?>" 
                                       class="cursor-pointer">
                                        <?php if ($task['done']): ?>
                                            <i class="fas fa-check-circle text-green-500 text-xl hover:text-green-700"></i>
                                        <?php else: ?>
                                            <i class="far fa-circle text-gray-400 text-xl hover:text-gray-600"></i>
                                        <?php endif; ?>
                                    </a>
                                    <div class="flex-1">
                                        <p class="<?php echo $task['done'] ? 'line-through text-gray-400' : 'text-gray-800'; ?> font-medium">
                                            <?php echo htmlspecialchars($task['name']); ?>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            <i class="far fa-calendar-alt mr-1"></i>
                                            <?php echo $task['created_at']; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a href="?delete=<?php echo $task['id']; ?>" 
                                       class="text-red-400 hover:text-red-600 transition"
                                       onclick="return confirm('Delete this task?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer Info -->
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>✅ Tasks are saved per user session • 
               <a href="?logout=1" class="text-indigo-500 hover:text-indigo-700">Change User</a>
            </p>
        </div>
    </div>

    <?php
    // Simple logout/change user
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: index.php');
        exit();
    }
    ?>
</body>
</html>