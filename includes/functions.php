<?php
// Common utility functions

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function generate_csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

function format_date($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
}

function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        $type = $flash['type'] ?? 'info'; // e.g., 'success', 'danger', 'info'
        $message = $flash['message'] ?? '';
        
        echo "<div class='alert alert-{$type}' role='alert'>{$message}</div>";
        
        // Unset the flash message so it doesn't show again
        unset($_SESSION['flash_message']);
    }
}
function get_order_status_badge($status) {
    switch ($status) {
        case 'completed': return 'badge-success';
        case 'pending': return 'badge-info';
        case 'confirmed': return 'badge-primary';
        case 'preparing': return 'badge-warning';
        case 'delivering': return 'badge-info';
        case 'cancelled': return 'badge-danger';
        default: return 'badge-secondary';
    }
}

// Note: require_permission() and has_permission() are defined in session.php

?>
