<?php
include(__DIR__ . "/../../config/config.php");

// Users to insert
$users = [
    [
        "username" => "admin",
        "password" => "admin123",
        "role" => "admin",
        "email" => "admin@example.com"
    ],
    [
        "username" => "faculty",
        "password" => "faculty123",
        "role" => "faculty",
        "email" => "faculty@example.com"
    ],
    [
        "username" => "student",
        "password" => "student123",
        "role" => "student",
        "email" => "student@example.com"
    ]
];

// Insert users
foreach ($users as $user) {
    $username = $user["username"];
    $password_plain = $user["password"];
    $password = password_hash($password_plain, PASSWORD_BCRYPT);
    $role = $user["role"];
    $email = $user["email"];

    $sql = "INSERT INTO users (username, password, role, email) 
            VALUES ('$username', '$password', '$role', '$email')";

    if ($conn->query($sql) === TRUE) {
        echo "✅ $role account created successfully!<br>";
        echo "👉 Username: $username<br>";
        echo "👉 Password: $password_plain<br>";
        echo "👉 Role: $role<br><br>";
    } else {
        echo "⚠️ Error creating $role: " . $conn->error . "<br><br>";
    }
}

$conn->close();
?>
