<?php
session_start();
// If admin is already logged in, redirect to admin dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] === 'admin') {
    header("location: index.php");
    exit;
}
require_once '../includes/db_connect.php';

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = ? AND role = 'admin'";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $name, $email, $hashed_password, $role);
                if ($stmt->fetch()) {
                    if (password_verify($password, $hashed_password)) {
                        session_start();
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["name"] = $name;
                        $_SESSION["role"] = $role;
                        header("location: index.php");
                        exit();
                    } else {
                        $error = 'Invalid password.';
                    }
                }
            } else {
                $error = 'No admin account found with that email.';
            }
        } else {
            $error = 'Oops! Something went wrong.';
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="w-full max-w-xs">
        <form action="login.php" method="post" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <h2 class="text-2xl mb-6 text-center font-bold">Admin Login</h2>
            <?php if(!empty($error)): ?>
                <p class="text-red-500 text-xs italic mb-4"><?= $error; ?></p>
            <?php endif; ?>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" id="email" name="email" type="email" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" id="password" name="password" type="password" required>
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" type="submit">
                    Sign In
                </button>
            </div>
        </form>
    </div>
</body>
</html>