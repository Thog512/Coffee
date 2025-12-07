<?php
/**
 * API: Check table availability
 * Returns available tables for a specific date, time and guest count
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Reservation.php';

$reservation = new Reservation();

// Get parameters
$date = $_GET['date'] ?? date('Y-m-d');
$time = $_GET['time'] ?? '12:00';
$guest_count = (int)($_GET['guests'] ?? 2);
$exclude_id = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

// Validate date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid date format'
    ]);
    exit;
}

// Validate time
if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid time format'
    ]);
    exit;
}

try {
    // Get available tables
    $available_tables = $reservation->getAvailableTables($date, $time, $guest_count);
    
    // Get all reservations for this date to show schedule
    $reservations = $reservation->getByDate($date);
    
    echo json_encode([
        'success' => true,
        'date' => $date,
        'time' => $time,
        'guest_count' => $guest_count,
        'available_tables' => $available_tables,
        'available_count' => count($available_tables),
        'reservations' => $reservations,
        'message' => count($available_tables) > 0 
            ? 'Có ' . count($available_tables) . ' bàn trống phù hợp' 
            : 'Không có bàn trống trong khung giờ này'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
