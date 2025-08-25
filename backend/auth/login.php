<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include(__DIR__ . "/../../config/config.php"); // DB connection

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role     = trim($_POST['role']);

    if (empty($role)) {
        $error = "Please select role!";
    } else {
        // 👇 Query with username and role check
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
        $stmt->bind_param("ss", $username, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // 👇 Password verify
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                // ✅ Role-wise redirection
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/admin_dashboard.php");
                } elseif ($user['role'] === 'faculty') {
                    header("Location: ../faculty/faculty_dashboard.php");
                } elseif ($user['role'] === 'student') {
                    header("Location: ../student/student_dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "User not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - College Management</title>
<style>
/* Universal styles */
* { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

body {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background: linear-gradient(135deg, #74ebd5, #ACB6E5);
}

.login-container {
    background-color: #fff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0px 8px 20px rgba(0,0,0,0.2);
    width: 400px;
    max-width: 90%;
    transition: all 0.3s ease;
}

.login-container h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #333;
    font-size: 28px;
}

.login-container input, .login-container select {
    width: 100%;
    padding: 14px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.2s ease;
}

.login-container input:focus, .login-container select:focus {
    border-color: #4CAF50;
    outline: none;
}

.login-container button {
    width: 100%;
    padding: 14px;
    margin-top: 15px;
    border: none;
    border-radius: 8px;
    background-color: #4CAF50;
    color: white;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.login-container button:hover {
    background-color: #45a049;
}

.error {
    color: red;
    margin-bottom: 15px;
    text-align: center;
}

/* Responsive */
@media (max-width: 1199px) and (min-width: 992px){ .login-container { width: 350px; padding: 35px; } }
@media (max-width: 991px) and (min-width: 768px){ .login-container { width: 300px; padding: 30px; } .login-container h2 { font-size: 24px; } }
@media (max-width: 767px) and (min-width: 600px){ .login-container { width: 280px; padding: 25px; } .login-container h2 { font-size: 22px; } }
@media (max-width: 599px){ .login-container { width: 95%; padding: 20px; } .login-container h2 { font-size: 20px; } .login-container input, .login-container select, .login-container button { padding: 12px; font-size: 15px; } }
</style>
</head>
<body>
<div class="login-container">
    <h2>Login</h2>
    <?php if($error) echo "<p class='error'>$error</p>"; ?>
    <form action="" method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role" required>
            <option value="">Select Role</option>
            <option value="admin">Admin</option>
            <option value="faculty">Faculty</option>
            <option value="student">Student</option>
        </select>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
