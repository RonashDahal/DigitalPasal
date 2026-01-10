<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Must be a logged-in user and a POST request.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';
$user_id = $_SESSION['id'];
$action = $_POST['action'] ?? '';

// ** THE FIX IS HERE: Use a switch statement to isolate the actions **
switch ($action) {
    
    // --- CASE 1: UPDATE USER DETAILS (Name, Email, Profile Pic) ---
    case 'update_details':
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);

        // Handle profile picture upload
        $new_image_path = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            // Basic validation
            $check = getimagesize($file["tmp_name"]);
            if ($check !== false) {
                $target_dir = "../uploads/profiles/";
                if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
                
                $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
                // Create a unique filename to prevent conflicts
                $target_file = $target_dir . $user_id . '_' . time() . '.' . $imageFileType;
                
                if (move_uploaded_file($file["tmp_name"], $target_file)) {
                    $new_image_path = str_replace('../', '', $target_file);
                }
            }
        }

        // Build the query dynamically based on whether a new image was uploaded
        if ($new_image_path) {
            $sql = "UPDATE users SET name = ?, email = ?, profile_image_url = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $name, $email, $new_image_path, $user_id);
        } else {
            $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $name, $email, $user_id);
        }

        if ($stmt->execute()) {
            // Update session variables immediately for a seamless experience
            $_SESSION['name'] = $name; 
            if ($new_image_path) {
                // You might also want to delete the old profile picture file from the server here
                $_SESSION['profile_image_url'] = $new_image_path;
            }
            $_SESSION['message'] = "Profile details updated successfully.";
        } else {
            // Provide a more specific error if the email is a duplicate
            $_SESSION['message'] = "Error updating details. The email address might already be in use by another account.";
        }
        $stmt->close();
        break; // End of 'update_details' case


    // --- CASE 2: CHANGE PASSWORD ---
    case 'change_password':
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['message'] = "Please fill in all password fields.";
            break;
        }
        if (strlen($new_password) < 6) { // Example: enforce minimum password length
            $_SESSION['message'] = "New password must be at least 6 characters long.";
            break;
        }
        if ($new_password !== $confirm_password) {
            $_SESSION['message'] = "The new passwords do not match.";
            break;
        }
        
        // Fetch the user's current hashed password from the DB
        $stmt_pass = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt_pass->bind_param("i", $user_id);
        $stmt_pass->execute();
        $result = $stmt_pass->get_result();
        $user = $result->fetch_assoc();
        $stmt_pass->close();

        // Verify the current password is correct
        if ($user && password_verify($current_password, $user['password'])) {
            // Hash the new password and update it in the database
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_password_hash, $user_id);
            if ($stmt_update->execute()) {
                $_SESSION['message'] = "Password changed successfully.";
            } else {
                $_SESSION['message'] = "Error: Could not change password.";
            }
            $stmt_update->close();
        } else {
            $_SESSION['message'] = "The current password you entered is incorrect.";
        }
        break; // End of 'change_password' case
    
    
    default:
        $_SESSION['message'] = "Invalid action.";
        break;
}

$conn->close();
// Always redirect back to the profile page
header("location: ../dashboard.php?view=profile");
exit();