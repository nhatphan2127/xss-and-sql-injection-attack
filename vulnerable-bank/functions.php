<?php
require_once 'config.php';
require_once 'db.php';
session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit();
    }
}

function get_user_data($conn, $user_id) {
    if (SECURE_MODE) {
        // SECURE: Using prepared statements
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } else {
        // VULNERABLE: Direct SQL injection possible if $user_id is not properly handled
        $sql = "SELECT * FROM users WHERE id = $user_id";
        $result = $conn->query($sql);
        return $result->fetch_assoc();
    }
}

function get_transactions($conn, $user_id) {
    if (SECURE_MODE) {
        // SECURE: Using prepared statements
        $sql = "SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    } else {
        // VULNERABLE: Direct string interpolation
        $sql = "SELECT * FROM transactions WHERE user_id = $user_id ORDER BY created_at DESC";
        return $conn->query($sql);
    }
}

function log_transaction($conn, $user_id, $type, $description, $amount) {
    if (SECURE_MODE) {
        // SECURE: Using prepared statements with proper escaping
        $sql = "INSERT INTO transactions (user_id, type, description, amount) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issd", $user_id, $type, $description, $amount);
        return $stmt->execute();
    } else {
        // VULNERABLE: Direct SQL injection possible in $type and $description
        $sql = "INSERT INTO transactions (user_id, type, description, amount) VALUES ($user_id, '$type', '$description', $amount)";
        return $conn->query($sql);
    }
}
?>
