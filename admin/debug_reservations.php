<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Reservation.php';

Auth::requireManager();

$reservation_manager = new Reservation();

echo "<h1>🔍 DEBUG: Reservations Data</h1>";
echo "<style>body{font-family:monospace;padding:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#667eea;color:white;}</style>";

// 1. Get ALL reservations
echo "<h2>1️⃣ All Reservations (getAll)</h2>";
$all_reservations = $reservation_manager->getAll();
echo "<p><strong>Total:</strong> " . count($all_reservations) . "</p>";

if (empty($all_reservations)) {
    echo "<p style='color:red;'>❌ NO RESERVATIONS FOUND!</p>";
    echo "<p>Tạo một reservation mới để test.</p>";
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>Customer</th><th>Phone</th><th>Date</th><th>Time</th><th>Guests</th><th>Table</th><th>Status</th><th>Created</th><th>Updated</th></tr>";
    foreach ($all_reservations as $r) {
        echo "<tr>";
        echo "<td>" . ($r['reservation_id'] ?? 'N/A') . "</td>";
        echo "<td>" . ($r['customer_name'] ?? 'N/A') . "</td>";
        echo "<td>" . ($r['customer_phone'] ?? 'N/A') . "</td>";
        echo "<td>" . ($r['reservation_date'] ?? 'N/A') . "</td>";
        echo "<td>" . ($r['reservation_time'] ?? 'N/A') . "</td>";
        echo "<td>" . ($r['guest_count'] ?? 'N/A') . "</td>";
        echo "<td>" . ($r['table_number'] ?? 'No table') . "</td>";
        echo "<td><strong>" . ($r['status'] ?? 'N/A') . "</strong></td>";
        echo "<td>" . ($r['created_at'] ?? 'N/A') . "</td>";
        echo "<td>" . ($r['updated_at'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Get current week
echo "<hr><h2>2️⃣ Current Week (for Calendar)</h2>";
$current_date = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));

echo "<p><strong>Today:</strong> $current_date</p>";
echo "<p><strong>Week Start (Monday):</strong> $week_start</p>";
echo "<p><strong>Week End (Sunday):</strong> $week_end</p>";

$week_reservations = $reservation_manager->getByDateRange($week_start, $week_end);
echo "<p><strong>Reservations this week:</strong> " . count($week_reservations) . "</p>";

if (empty($week_reservations)) {
    echo "<p style='color:orange;'>⚠️ No reservations for current week.</p>";
    echo "<p>💡 Try creating a reservation for today to see it in calendar.</p>";
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>Customer</th><th>Date</th><th>Time</th><th>Status</th></tr>";
    foreach ($week_reservations as $r) {
        echo "<tr>";
        echo "<td>" . ($r['reservation_id'] ?? 'N/A') . "</td>";
        echo "<td>" . ($r['customer_name'] ?? 'N/A') . "</td>";
        echo "<td>" . ($r['reservation_date'] ?? 'N/A') . "</td>";
        echo "<td>" . ($r['reservation_time'] ?? 'N/A') . "</td>";
        echo "<td><strong>" . ($r['status'] ?? 'N/A') . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Test specific date
echo "<hr><h2>3️⃣ Today's Reservations</h2>";
$today_reservations = $reservation_manager->getByDate($current_date);
echo "<p><strong>Count:</strong> " . count($today_reservations) . "</p>";

if (empty($today_reservations)) {
    echo "<p style='color:orange;'>⚠️ No reservations for today.</p>";
} else {
    foreach ($today_reservations as $r) {
        echo "<p>✅ {$r['customer_name']} at {$r['reservation_time']} - {$r['status']}</p>";
    }
}

// 4. Database structure
echo "<hr><h2>4️⃣ Table Structure</h2>";
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->query("DESCRIBE reservations");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "<td>" . $col['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for updated_at
    $has_updated_at = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'updated_at') {
            $has_updated_at = true;
            break;
        }
    }
    
    if ($has_updated_at) {
        echo "<p style='color:green;'>✅ Column 'updated_at' EXISTS</p>";
    } else {
        echo "<p style='color:red;'>❌ Column 'updated_at' MISSING!</p>";
        echo "<p><strong>Solution:</strong> Run: <code>database/fix_reservations_table.sql</code></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='reservations.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>← Back to Reservations</a></p>";
echo "<p><a href='reservation_calendar.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>📅 View Calendar</a></p>";
?>
