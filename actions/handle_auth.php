<?php
require_once '../includes/db_connect.php';

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    // REGISTER LOGIC
    if ($_POST['action'] == 'register') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Simple validation
        if (empty($name) || empty($email) || empty($password)) {
            die("Please fill all required fields.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die("Invalid email format.");
        }
        
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Prepare an insert statement
        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sss", $name, $email, $password_hash);
            
            if ($stmt->execute()) {
                // Redirect to login page
                header("location: ../login.php");
                exit();
            } else {
                echo "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }

    // LOGIN LOGIC
    if ($_POST['action'] == 'login') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        
        // Prepare a select statement
        $sql = "SELECT id, name, email, password, role, profile_image_url FROM users WHERE email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $name, $email, $hashed_password, $role, $profile_image_url);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["name"] = $name;
                            $_SESSION["role"] = $role;                            
                            $_SESSION["profile_image_url"] = $profile_image_url;
                            // Redirect user to dashboard
                            header("location: ../dashboard.php");
                            exit();
                        } else {
                            // Display an error message if password is not valid
                            echo "The password you entered was not valid.";
                        }
                    }
                } else {
                    // Display an error message if email doesn't exist
                    echo "No account found with that email.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>