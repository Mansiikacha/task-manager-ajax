<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get initial tasks
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
    <title>AJAX Task Manager with Edit</title>
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
                        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                        AJAX Task Manager
                    </h1>
                    <p class="text-gray-600 mt-1">
                        Welcome, <span
                            class="font-semibold text-indigo-600"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </p>
                    <p class="text-sm text-green-600 mt-1">
                        <i class="fas fa-sync-alt"></i> No page reloads! Edit, add, delete - all instant!
                    </p>
                </div>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-3 gap-4 mb-6" id="statsContainer">
            <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-blue-500">
                <p class="text-gray-500 text-sm">Total Tasks</p>
                <p class="text-2xl font-bold" id="totalTasks"><?php echo $stats['total']; ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-green-500">
                <p class="text-gray-500 text-sm">Completed</p>
                <p class="text-2xl font-bold text-green-600" id="completedTasks"><?php echo $stats['completed']; ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-yellow-500">
                <p class="text-gray-500 text-sm">Pending</p>
                <p class="text-2xl font-bold text-yellow-600" id="pendingTasks"><?php echo $pending; ?></p>
            </div>
        </div>

        <!-- Add Task Form -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <form id="addTaskForm" class="space-y-3">
                <input type="text" id="taskTitle" placeholder="Task title..." required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">

                <textarea id="taskDescription" rows="2" placeholder="Description (optional)..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                <input type="text" id="taskDuration" placeholder="Task Duration (e.g., 1 hour)" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">

                <button type="submit"
                    class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-plus mr-1"></i> Add Task
                </button>
            </form>
            <div id="addMessage" class="mt-3 hidden"></div>
        </div>

        <!-- Tasks List -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                <h2 class="font-semibold text-gray-700"><i class="fas fa-list mr-2"></i>Your Tasks</h2>
            </div>
            <div id="tasksList" class="divide-y divide-gray-200">
                <div class="text-center py-12">
                    <i class="fas fa-spinner fa-spin text-3xl text-indigo-500"></i>
                    <p class="text-gray-500 mt-2">Loading tasks...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load tasks when page loads
        document.addEventListener('DOMContentLoaded', function () {
            loadTasks();
        });

        // Load all tasks via AJAX
        function loadTasks() {
            fetch('ajax_handler.php?action=get_tasks')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTasks(data.tasks);
                        updateStats(data.tasks);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Display tasks with EDIT button
        function displayTasks(tasks) {
            const tasksList = document.getElementById('tasksList');

            if (tasks.length === 0) {
                tasksList.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-clipboard-list text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No tasks yet. Add your first task above!</p>
                    </div>
                `;
                return;
            }

            let html = '';
            tasks.forEach(task => {
                const statusClass = task.status === 'completed' ? 'bg-green-50' : '';
                const titleClass = task.status === 'completed' ? 'line-through text-gray-400' : 'text-gray-800';
                const checkIcon = task.status === 'completed'
                    ? '<i class="fas fa-check-circle text-green-500 text-xl"></i>'
                    : '<i class="far fa-circle text-gray-400 text-xl"></i>';

                html += `
                    <div class="p-4 hover:bg-gray-50 transition ${statusClass}" id="task-${task.id}">
                        <!-- NORMAL VIEW -->
                        <div id="view-${task.id}" class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <button onclick="toggleTask(${task.id})" class="cursor-pointer">
                                        ${checkIcon}            
                                    </button>
                                    <div>
                                        <p class="${titleClass} font-medium" id="title-text-${task.id}">
                                            ${escapeHtml(task.title)}
                                        </p>
                                        ${task.description ? `<p class="text-sm text-gray-500 mt-1" id="desc-text-${task.id}">${escapeHtml(task.description)}</p>` : ''}
                                        <p class="text-xs text-gray-400 mt-1">
                                            <i class="far fa-calendar-alt mr-1"></i>
                                            ${new Date(task.created_at).toLocaleString()}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <!-- EDIT BUTTON -->
                                <button onclick="showEditForm(${task.id})" 
                                        class="text-blue-400 hover:text-blue-600 transition"
                                        title="Edit Task">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <!-- DELETE BUTTON -->
                                <button onclick="deleteTask(${task.id})" 
                                        class="text-red-400 hover:text-red-600 transition"
                                        title="Delete Task">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- EDIT FORM (Hidden by default) -->
                        <div id="edit-${task.id}" class="hidden mt-3 space-y-3 border-t pt-3">
                            <input type="text" id="edit-title-${task.id}" 
                                   value="${escapeHtml(task.title)}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <textarea id="edit-desc-${task.id}" 
                                      rows="2"
                                      placeholder="Description..."
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">${escapeHtml(task.description || '')}</textarea>
                           
                                      <select id="edit-status-${task.id}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="pending" ${task.status === 'pending' ? 'selected' : ''}>⏳ Pending</option>
                                <option value="completed" ${task.status === 'completed' ? 'selected' : ''}>✅ Completed</option>
                            </select>
                            <div class="flex gap-2">
                                <button onclick="saveEdit(${task.id})" 
                                        class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <button onclick="cancelEdit(${task.id})" 
                                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            tasksList.innerHTML = html;
        }

        // Show edit form, hide normal view
        function showEditForm(taskId) {
            document.getElementById(`view-${taskId}`).style.display = 'none';
            document.getElementById(`edit-${taskId}`).classList.remove('hidden');
        }

        // Cancel edit - hide edit form, show normal view
        function cancelEdit(taskId) {
            document.getElementById(`view-${taskId}`).style.display = 'flex';
            document.getElementById(`edit-${taskId}`).classList.add('hidden');
        }

        // Save edited task via AJAX
        function saveEdit(taskId) {
            const title = document.getElementById(`edit-title-${taskId}`).value;
            const description = document.getElementById(`edit-desc-${taskId}`).value;
            const status = document.getElementById(`edit-status-${taskId}`).value;

            if (!title.trim()) {
                showNotification('❌ Title is required!', 'error');
                return;
            }

            // Show saving state on button
            const saveBtn = event.target;
            const originalHtml = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin" ></i> Saving...';
            saveBtn.disabled = true;

            fetch('ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=edit_task&task_id=${taskId}&title=${encodeURIComponent(title)}&description=${encodeURIComponent(description)}&status=${status}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadTasks(); // Reload to show updated data
                        showNotification('<i class="bi bi-check-lg"></i> Task updated successfully!', 'success');
                    } else {
                        showNotification(data.message, 'error');    
                        cancelEdit(taskId);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('❌ Error updating task', 'error');
                    cancelEdit(taskId);
                })
                .finally(() => {
                    saveBtn.innerHTML = originalHtml;
                    saveBtn.disabled = false;
                });
        }

        // Show notification message
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'
                } text-white`;
            notification.innerHTML = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Update statistics
        function updateStats(tasks) {
            const total = tasks.length;
            const completed = tasks.filter(t => t.status === 'completed').length;
            const pending = total - completed;

            document.getElementById('totalTasks').textContent = total;
            document.getElementById('completedTasks').textContent = completed;
            document.getElementById('pendingTasks').textContent = pending;
        }

        // Add Task via AJAX
        document.getElementById('addTaskForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const title = document.getElementById('taskTitle').value;
            const description = document.getElementById('taskDescription').value;
            const messageDiv = document.getElementById('addMessage');

            if (!title.trim()) {
                showNotification('❌ Title is required!', 'error');
                return;
            }

            messageDiv.innerHTML = '<div class="text-blue-500"><i class="fas fa-spinner fa-spin"></i> Adding task...</div>';
            messageDiv.classList.remove('hidden');

            fetch('ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_task&title=${encodeURIComponent(title)}&description=${encodeURIComponent(description)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageDiv.innerHTML = '<div class="text-green-500"><i class="fas fa-check-circle"></i> Task added!</div>';
                        document.getElementById('taskTitle').value = '';
                        document.getElementById('taskDescription').value = '';
                        loadTasks();
                        showNotification('✅ Task added successfully!', 'success');

                        setTimeout(() => {
                            messageDiv.classList.add('hidden');
                        }, 2000);
                    } else {
                        messageDiv.innerHTML = `<div class="text-red-500"><i class="fas fa-exclamation-circle"></i> ${data.message}</div>`;
                    }
                })
                .catch(error => {
                    messageDiv.innerHTML = '<div class="text-red-500"><i class="fas fa-exclamation-circle"></i> Error adding task</div>';
                });
        });

        // Delete Task via AJAX
        function deleteTask(taskId) {
            if (!confirm('Delete this task?')) return;

            fetch('ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_task&task_id=${taskId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadTasks();
                        showNotification('✅ Task deleted!', 'success');
                    } else {
                        showNotification('Failed to delete task', 'error');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Toggle Task Status via AJAX
        function toggleTask(taskId) {
            fetch('ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_task&task_id=${taskId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadTasks();
                        showNotification('✅ Status updated!', 'success');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Helper function to prevent XSS
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>

</html>