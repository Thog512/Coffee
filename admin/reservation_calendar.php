<?php 
$page_title = 'Lịch Đặt Bàn';
include 'includes/header.php';

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Reservation.php';

// Manager only
Auth::requireManager();

$reservation_manager = new Reservation();

// Get current week or specified date
$current_date = $_GET['date'] ?? date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($current_date)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($current_date)));

// Get reservations for the week
$all_reservations = $reservation_manager->getByDateRange($week_start, $week_end);

// Filter only confirmed reservations for calendar display
$reservations = array_filter($all_reservations, function($r) {
    return isset($r['status']) && $r['status'] === 'confirmed';
});

// Organize reservations by date and time
$calendar_data = [];
for ($i = 0; $i < 7; $i++) {
    $day = date('Y-m-d', strtotime($week_start . " +$i days"));
    $calendar_data[$day] = [];
}

foreach ($reservations as $reservation) {
    $date = $reservation['reservation_date'];
    $time = substr($reservation['reservation_time'], 0, 5); // HH:MM
    
    if (!isset($calendar_data[$date][$time])) {
        $calendar_data[$date][$time] = [];
    }
    
    $calendar_data[$date][$time][] = $reservation;
}

// Time slots (9 AM to 10 PM)
$time_slots = [];
for ($hour = 9; $hour <= 22; $hour++) {
    $time_slots[] = sprintf('%02d:00', $hour);
    $time_slots[] = sprintf('%02d:30', $hour);
}

function get_status_color($status) {
    switch ($status) {
        case 'pending': return '#17a2b8';
        case 'confirmed': return '#28a745';
        case 'arrived': return '#667eea';
        case 'completed': return '#6c757d';
        case 'cancelled': return '#dc3545';
        case 'no_show': return '#fd7e14';
        default: return '#6c757d';
    }
}
?>

<style>
.calendar-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
}

.calendar-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.week-navigation {
    display: flex;
    gap: 15px;
    align-items: center;
}

.week-navigation button {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.week-navigation button:hover {
    background: rgba(255,255,255,0.3);
}

.current-week {
    font-size: 1.2rem;
    font-weight: 600;
}

.calendar-grid {
    display: grid;
    grid-template-columns: 80px repeat(7, 1fr);
    gap: 1px;
    background: #e0e0e0;
}

.calendar-cell {
    background: white;
    min-height: 60px;
    position: relative;
}

.time-label {
    padding: 10px;
    text-align: center;
    font-weight: 600;
    color: #6c757d;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.day-header {
    padding: 15px 10px;
    text-align: center;
    font-weight: 600;
    background: #f8f9fa;
    border-bottom: 3px solid #667eea;
}

.day-header .day-name {
    font-size: 0.9rem;
    color: #6c757d;
    text-transform: uppercase;
}

.day-header .day-date {
    font-size: 1.2rem;
    color: #2c3e50;
    margin-top: 5px;
}

.day-header.today {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.day-header.today .day-name,
.day-header.today .day-date {
    color: white;
}

.time-slot {
    padding: 5px;
    min-height: 60px;
}

.reservation-block {
    background: #667eea;
    color: white;
    padding: 6px 8px;
    margin: 2px 0;
    border-radius: 4px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border-left: 4px solid rgba(0,0,0,0.2);
}

.reservation-block:hover {
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.reservation-block .customer-name {
    font-weight: 600;
    display: block;
    margin-bottom: 2px;
}

.reservation-block .table-info {
    font-size: 0.7rem;
    opacity: 0.9;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-box {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    text-align: center;
}

.stat-box .stat-label {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 5px;
}

.stat-box .stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #2c3e50;
}

.legend {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 20px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
}

@media (max-width: 1200px) {
    .calendar-grid {
        grid-template-columns: 60px repeat(7, 1fr);
        font-size: 0.85rem;
    }
    
    .reservation-block {
        font-size: 0.65rem;
        padding: 4px 6px;
    }
}
</style>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-calendar-alt"></i> Lịch Đặt Bàn</h1>
            <p style="margin: 5px 0 0 0; color: #6c757d; font-size: 0.9rem;">
                <i class="fas fa-check-circle" style="color: #28a745;"></i> 
                Hiển thị các đơn đã xác nhận
            </p>
        </div>
        <div>
            <a href="reservation_form_new.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tạo Đơn Mới
            </a>
            <a href="reservations.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> Dạng Danh Sách
            </a>
        </div>
    </div>
</div>

<?php display_flash_message(); ?>

<!-- Quick Stats -->
<div class="quick-stats">
    <?php 
    $stats = $reservation_manager->getStatistics($week_start, $week_end);
    $confirmed_count = count($reservations); // Number of confirmed reservations being displayed
    ?>
    <div class="stat-box" style="border: 2px solid #28a745;">
        <div class="stat-label">Hiển Thị Trên Lịch</div>
        <div class="stat-value" style="color: #28a745;"><?php echo $confirmed_count; ?></div>
        <div style="font-size: 0.75rem; color: #6c757d; margin-top: 4px;">Đơn đã xác nhận</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Tổng Đơn Tuần Này</div>
        <div class="stat-value"><?php echo $stats['total']; ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Chờ Xác Nhận</div>
        <div class="stat-value" style="color: #17a2b8;"><?php echo $stats['pending']; ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Đã Đến</div>
        <div class="stat-value" style="color: #667eea;"><?php echo $stats['arrived']; ?></div>
    </div>
</div>

<!-- Legend -->
<div class="legend">
    <div class="legend-item" style="background: #e8f5e9; padding: 8px 12px; border-radius: 6px; border: 2px solid #28a745;">
        <i class="fas fa-info-circle" style="color: #28a745;"></i>
        <strong>Lịch chỉ hiển thị các đơn đã xác nhận</strong>
    </div>
    <div class="legend-item">
        <div class="legend-color" style="background: <?php echo get_status_color('confirmed'); ?>;"></div>
        <span>Đơn Đã Xác Nhận</span>
    </div>
    <div class="legend-item" style="color: #6c757d; font-size: 0.9rem;">
        <i class="fas fa-eye-slash"></i>
        <span>Đơn chờ xác nhận, đã hủy, và hoàn thành không hiển thị trên lịch</span>
    </div>
</div>

<!-- Calendar -->
<div class="calendar-container">
    <div class="calendar-header">
        <h2><i class="fas fa-calendar-week"></i> Tuần: <?php echo date('d/m', strtotime($week_start)) . ' - ' . date('d/m/Y', strtotime($week_end)); ?></h2>
        <div class="week-navigation">
            <button onclick="navigateWeek(-1)">
                <i class="fas fa-chevron-left"></i> Tuần Trước
            </button>
            <button onclick="navigateWeek(0)" class="current-week">
                Tuần Này
            </button>
            <button onclick="navigateWeek(1)">
                Tuần Sau <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    
    <div class="calendar-grid">
        <!-- Header row -->
        <div class="time-label"></div>
        <?php 
        $days = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ Nhật'];
        for ($i = 0; $i < 7; $i++):
            $day_date = date('Y-m-d', strtotime($week_start . " +$i days"));
            $is_today = $day_date === date('Y-m-d');
        ?>
            <div class="day-header <?php echo $is_today ? 'today' : ''; ?>">
                <div class="day-name"><?php echo $days[$i]; ?></div>
                <div class="day-date"><?php echo date('d/m', strtotime($day_date)); ?></div>
            </div>
        <?php endfor; ?>
        
        <!-- Time slots -->
        <?php foreach ($time_slots as $time): ?>
            <div class="time-label"><?php echo $time; ?></div>
            
            <?php for ($i = 0; $i < 7; $i++): 
                $day_date = date('Y-m-d', strtotime($week_start . " +$i days"));
                $reservations_at_time = $calendar_data[$day_date][$time] ?? [];
            ?>
                <div class="time-slot">
                    <?php foreach ($reservations_at_time as $reservation): ?>
                        <div class="reservation-block" 
                             style="background: <?php echo get_status_color($reservation['status'] ?? 'pending'); ?>;"
                             onclick="location.href='reservation_form_new.php?id=<?php echo $reservation['reservation_id']; ?>'">
                            <span class="customer-name"><?php echo htmlspecialchars($reservation['customer_name'] ?? 'N/A'); ?></span>
                            <span class="table-info">
                                <i class="fas fa-users"></i> <?php echo $reservation['guest_count'] ?? 0; ?> người
                                <?php if (!empty($reservation['table_number'])): ?>
                                    | <i class="fas fa-chair"></i> <?php echo htmlspecialchars($reservation['table_number']); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endfor; ?>
        <?php endforeach; ?>
    </div>
</div>

<script>
function navigateWeek(offset) {
    const currentDate = '<?php echo $current_date; ?>';
    const date = new Date(currentDate);
    
    if (offset === 0) {
        // Go to current week
        window.location.href = 'reservation_calendar.php';
    } else {
        // Navigate by offset
        date.setDate(date.getDate() + (offset * 7));
        const newDate = date.toISOString().split('T')[0];
        window.location.href = 'reservation_calendar.php?date=' + newDate;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
