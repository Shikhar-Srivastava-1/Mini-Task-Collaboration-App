<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/db.php';

$stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

$_SESSION['user_id'] = $user['id'];
$name = $user['name'];
$message = '';
$message_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'All fields are required';
        $message_class = 'text-red-600';
    } elseif (!password_verify($old_password, $user['password'])) {
        $message = 'Old password is incorrect';
        $message_class = 'text-red-600';
    } elseif (strlen($new_password) < 8) {
        $message = 'New password must be at least 8 characters';
        $message_class = 'text-red-600';
    } elseif ($new_password !== $confirm_password) {
        $message = 'New passwords do not match';
        $message_class = 'text-red-600';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user['id']]);
        $message = 'Password updated successfully';
        $message_class = 'text-teal-600';
        
        // Update user data
        $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$_SESSION['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/heroicons@2.0.18/dist/heroicons.min.css" rel="stylesheet">
    <style>
        .navcontainer {
            transition: transform 0.3s ease-in-out;
        }
        .nav-hidden {
            transform: translateX(-100%);
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        @media (min-width: 640px) {
            .navcontainer {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900">
    <!-- Header -->
    <header class="bg-white shadow-sm h-14 w-full fixed top-0 z-50 flex items-center justify-between px-4">
        <div class="flex items-center space-x-3">
            <button id="hamburger" class="sm:hidden">
                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                </svg>
            </button>
            <div class="text-xl font-semibold text-teal-500"><?php echo $user['role'] === 'admin' ? 'Admin Portal' : 'User Portal'; ?></div>
        </div>
    </header>

    <div class="pt-14 min-h-[calc(100vh-3.5rem)]">
        <!-- Sidebar -->
        <div class="navcontainer w-60 h-[calc(100vh-3.5rem)] bg-white shadow-md fixed top-14 left-0 no-scrollbar overflow-y-auto nav-hidden sm:nav-visible">
            <nav class="flex flex-col h-full p-3">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2 p-2 bg-indigo-600 text-white rounded-md">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zm-4 7c-3.313 0-6 2.687-6 6h12c0-3.313-2.687-6-6-6z" />
                        </svg>
                        <h3 class="text-sm font-medium">Profile</h3>
                    </div>
                    <a href="<?php echo $user['role'] === 'User' ? '../user/index.php' : '../admin/index.php'; ?>" class="flex items-center space-x-2 p-2 hover:bg-gray-100 rounded-md transition-colors">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        <h3 class="text-sm font-medium">Dashboard</h3>
                    </a>
                    <a href="../logout.php" class="flex items-center space-x-2 p-2 hover:bg-gray-100 rounded-md transition-colors">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <h3 class="text-sm font-medium">Log Out</h3>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="sm:ml-60 p-3">
            <div class="max-w-4xl mx-auto bg-white rounded-md shadow-xs p-3">
                <div class="flex items-center justify-between mb-3">
                    <h1 class="text-lg font-semibold text-indigo-700">Profile</h1>
                    <a href="<?php echo $user['role'] === 'User' ? '../user/index.php' : '../admin/index.php'; ?>" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Back to Dashboard</a>
                </div>
                <div class="border-t border-gray-200 pt-3">
                    <dl class="space-y-2 mb-4">
                        <div class="flex items-center justify-between">
                            <dt class="text-xs font-medium text-gray-700">Name</dt>
                            <dd class="text-xs text-gray-900"><?php echo htmlspecialchars($user['name']); ?></dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-xs font-medium text-gray-700">Email</dt>
                            <dd class="text-xs text-gray-900"><?php echo htmlspecialchars($user['email']); ?></dd>
                        </div>
                    </dl>
                    <div>
                        <h2 class="text-sm font-medium text-gray-700 mb-2">Change Password</h2>
                        <?php if ($message): ?>
                            <div class="text-xs mb-2 <?php echo htmlspecialchars($message_class); ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" class="space-y-2">
                            <div>
                                <label for="old_password" class="block text-xs font-medium text-gray-700">Old Password</label>
                                <input type="password" id="old_password" name="old_password" class="mt-0.5 block w-full border-gray-200 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs py-1.5 px-2">
                            </div>
                            <div>
                                <label for="new_password" class="block text-xs font-medium text-gray-700">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="mt-0.5 block w-full border-gray-200 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs py-1.5 px-2">
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-xs font-medium text-gray-700">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="mt-0.5 block w-full border-gray-200 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs py-1.5 px-2">
                            </div>
                            <div>
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1 rounded-md hover:bg-indigo-700 transition-colors text-xs">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle
        const hamburger = document.getElementById('hamburger');
        const nav = document.querySelector('.navcontainer');
        hamburger.addEventListener('click', () => {
            nav.classList.toggle('nav-hidden');
        });

        document.addEventListener('click', (e) => {
            if (window.innerWidth < 640 && !nav.contains(e.target) && !hamburger.contains(e.target)) {
                nav.classList.add('nav-hidden');
            }
        });

        document.addEventListener('contextmenu', (e) => {
            if (e.target.tagName === 'IMG') {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
