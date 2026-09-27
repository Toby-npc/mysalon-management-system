<?php

include "db.php";

$name = "MySalon Admin";
$email = "admin@mysalon.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$role = "admin";

$sql = "INSERT INTO users (name, email, password, role)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $name, $email, $password, $role);

if ($stmt->execute()) {
    echo "Admin account created successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>