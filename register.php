<?php
require_once 'config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $message = "❌ All fields are required!";
    } else {
        // Hash password (never store plain text passwords!)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // Insert user into database
            $sql = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $hashed_password
            ]);
            
            $message = "✅ User registered successfully!";
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry error
                $message = "❌ Email already exists!";
            } else {
                $message = "❌ Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Task Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-12 max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-8">📝 Create Account</h1>
            
            <?php if($message): ?>
                <div class="mb-4 p-3 rounded <?php echo strpos($message, '✅') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <button type="submit" 
                        class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition">
                    Register
                </button>
            </form>
            
            <p class="text-center text-gray-600 mt-4">
                Already have an account? <a href="login.php" class="text-indigo-600 hover:underline">Login</a>
            </p>
        </div>
    </div>
</body>
</html>