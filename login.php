<?php
// Use the centralized init file for robust session handling.
require_once 'includes/init.php';

// This page now correctly establishes a database connection, which is needed by the footer.
require_once 'includes/db_connect.php';

// Security check: If a user who is already logged in tries to visit this page,
// redirect them to their dashboard. This logic runs BEFORE any HTML is output.
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

// Now that all logic is complete, we can safely include the header.
require_once 'includes/header.php';
?>

<!-- NEW, MODERN LOGIN PAGE DESIGN -->
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-xl">
        
        <div class="text-center mb-8">
            <a href="index.php" class="inline-block text-3xl font-bold text-gray-800">MyStore</a>
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">
                Sign in to your Account
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Or
                <a href="register.php" class="font-medium text-indigo-600 hover:text-indigo-500">create a new account</a>
            </p>
        </div>
        
        <form action="actions/handle_auth.php" method="post" class="space-y-6">
            <input type="hidden" name="action" value="login">

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                <div class="mt-1">
                    <input id="email" name="email" type="email" autocomplete="email" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="text-sm">
                        <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">Forgot your password?</a>
                    </div>
                </div>
                <div class="mt-1">
                    <input id="password" name="password" type="password" autocomplete="current-password" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    Sign In
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// First, we include the footer, which needs the open $conn variable.
require_once 'includes/footer.php'; 

// NOW, it is safe to close the connection for this page request.
if (isset($conn)) {
    $conn->close();
}
?>