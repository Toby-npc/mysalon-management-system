<?php
include "db.php";

$name = "MySalon Staff";
$email = "staff@mysalon.com";
$password = "staff123";
$role = "staff";

// Semak sama ada email sudah wujud
$check = $conn->prepare(
    "SELECT user_id FROM users WHERE email = ?"
);

$check->bind_param("s", $email);
$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {

    echo "Staff account already exists.";

} else {

    // Hash password
    $hashed_password = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // Masukkan staff
    $stmt = $conn->prepare(
        "INSERT INTO users
        (name, email, password, role)
        VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "ssss",
        $name,
        $email,
        $hashed_password,
        $role
    );

    if ($stmt->execute()) {

        echo "Staff account created successfully!";

    } else {

        echo "Error creating staff account.";

    }

    $stmt->close();
}

$check->close();
$conn->close();
?>