<?php
include(__DIR__ . "/../../config/config.php");

// Check if admin already exists
$stmt = $conn->prepare("SELECT * FROM users WHERE username = 'admin' LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    // Hash password
    $hashed_password = password_hash("admin123", PASSWORD_DEFAULT);

    // Insert admin
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $username = "admin";
    $email = "admin@example.com";
    $role = "admin";
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
    $stmt->execute();

    echo "Admin created successfully!";
} else {
    echo "Admin already exists!";
}
