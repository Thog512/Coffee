<?php 
$page_title = 'Phân Tích Gợi Ý AI';
include 'includes/header.php';

require_once __DIR__ . '/../classes/Database.php';
$db = Database::getInstance()->getConnection();

// Check if table exists
try {
    $check_table = $db->query("SHOW TABLES LIKE 'recommendation_analytics'");
    $table_exists = $check_table->rowCount() > 0;
} catch (Exception $e) {
    $table_exists = false;
}

if (!$table_exists) {
    echo '<div class="alert alert-warning">
            <h4><i class="fas fa-exclamation-triangle"></i> Bảng Analytics Chưa Được Tạo</h4>
            <p>Vui lòng chạy file SQL: <code>database/recommendation_analytics_schema.sql</code></p>
            <p>Hoặc chạy lệnh: <code>mysql -u root -p coffee_shop < database/recommendation_analytics_schema.sql</code></p>
          </div>';
    include 'includes/footer.php';
    exit;
}

// Get date range filter
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$days = min(max($days, 1), 365); // Between 1 and 365 days

// Get overall statistics by type
$query = "SELECT 
    recommendation_type,
    COUNT(CASE WHEN event_type = 'shown' THEN 1 END) as shown_count,
    COUNT(CASE WHEN event_type = 'clicked' THEN 1 END) as clicked_count,
    COUNT(CASE WHEN event_type = 'added_to_cart' THEN 1 END) as added_count,
    ROUND(COUNT(CASE WHEN event_type = 'clicked' THEN 1 END) * 100.0 / 
          NULLIF(COUNT(CASE WHEN event_type = 'shown' THEN 1 END), 0), 2) as ctr,
    ROUND(COUNT(CASE WHEN event_type = 'added_to_cart' THEN 1 END) * 100.0 / 
          NULLIF(COUNT(CASE WHEN event_type = 'shown' THEN 1 END), 0), 2) as conversion_rate
FROM recommendation_analytics
WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
GROUP BY recommendation_type
ORDER BY added_count DESC";
$stmt = $db->prepare($query);
$stmt->execute([':days' => $days]);
$stats_by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top performing products
$query = "SELECT 
    p.product_id,
    p.product_name,
    p.image,
    c.category_name,
    COUNT(CASE WHEN ra.event_type = 'shown' THEN 1 END) as impressions,
    COUNT(CASE WHEN ra.event_type = 'clicked' THEN 1 END) as clicks,
    COUNT(CASE WHEN ra.event_type = 'added_to_cart' THEN 1 END) as conversions,
    ROUND(COUNT(CASE WHEN ra.event_type = 'clicked' THEN 1 END) * 100.0 / 
          NULLIF(COUNT(CASE WHEN ra.event_type = 'shown' THEN 1 END), 0), 2) as ctr,
    ROUND(COUNT(CASE WHEN ra.event_type = 'added_to_cart' THEN 1 END) * 100.0 / 
          NULLIF(COUNT(CASE WHEN ra.event_type = 'shown' THEN 1 END), 0), 2) as conversion_rate
FROM recommendation_analytics ra
JOIN products p ON ra.product_id = p.product_id
JOIN categories c ON p.category_id = c.category_id
WHERE ra.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
GROUP BY ra.product_id
ORDER BY conversions DESC, ctr DESC
LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([':days' => $days]);
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get daily trend data
$query = "SELECT 
    DATE(created_at) as date,
    COUNT(CASE WHEN event_type = 'shown' THEN 1 END) as shown,
    COUNT(CASE WHEN event_type = 'clicked' THEN 1 END) as clicked,
    COUNT(CASE WHEN event_type = 'added_to_cart' THEN 1 END) as converted
FROM recommendation_analytics
WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
GROUP BY DATE(created_at)
ORDER BY date ASC";
$stmt = $db->prepare($query);
$stmt->execute([':days' => min($days, 30)]); // Max 30 days for chart
$daily_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_shown = array_sum(array_column($stats_by_type, 'shown_count'));
$total_clicked = array_sum(array_column($stats_by_type, 'clicked_count'));
$total_converted = array_sum(array_column($stats_by_type, 'added_count'));
$overall_ctr = $total_shown > 0 ? round($total_clicked * 100 / $total_shown, 2) : 0;
$overall_conversion = $total_shown > 0 ? round($total_converted * 100 / $total_shown, 2) : 0;
?>

<div class="analytics-container">
    <!-- Header with filters -->
    <div class="analytics-header">
        <div>
            <h2><i class="fas fa-chart-line"></i> Phân Tích Hiệu Quả Gợi Ý AI</h2>
            <p class="text-muted">Theo dõi hiệu suất của hệ thống gợi ý sản phẩm</p>
        </div>
        <div class="filter-group">
            <label>Khoảng thời gian:</label>
            <select onchange="window.location.href='?days=' + this.value" class="form-control">
                <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>7 ngày</option>
                <option value="14" <?php echo $days == 14 ? 'selected' : ''; ?>>14 ngày</option>
                <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>30 ngày</option>
                <option value="60" <?php echo $days == 60 ? 'selected' : ''; ?>>60 ngày</option>
                <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>90 ngày</option>
            </select>
        </div>
    </div>

    <!-- Overall Stats Cards -->
    <div class="stats-overview">
        <div class="stat-card primary">
            <div class="stat-icon"><i class="fas fa-eye"></i></div>
            <div class="stat-content">
                <h3><?php echo number_format($total_shown); ?></h3>
                <p>Tổng Hiển Thị</p>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"><i class="fas fa-mouse-pointer"></i></div>
            <div class="stat-content">
                <h3><?php echo number_format($total_clicked); ?></h3>
                <p>Tổng Click</p>
                <span class="badge bg-success"><?php echo $overall_ctr; ?>% CTR</span>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-content">
                <h3><?php echo number_format($total_converted); ?></h3>
                <p>Thêm Vào Giỏ</p>
                <span class="badge bg-warning"><?php echo $overall_conversion; ?>% Conv.</span>
            </div>
        </div>
    </div>

    <!-- Performance by Type -->
    <div class="section-card">
        <h3><i class="fas fa-layer-group"></i> Hiệu Quả Theo Loại Gợi Ý</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Loại Gợi Ý</th>
                        <th>Hiển Thị</th>
                        <th>Click</th>
                        <th>Thêm Giỏ</th>
                        <th>CTR</th>
                        <th>Conversion</th>
                        <th>Hiệu Quả</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stats_by_type)): ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                <i class="fas fa-info-circle"></i> Chưa có dữ liệu trong khoảng thời gian này
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stats_by_type as $stat): ?>
                        <tr>
                            <td>
                                <span class="badge-type badge-<?php echo $stat['recommendation_type']; ?>">
                                    <?php 
                                    $type_names = [
                                        'cart' => 'Dựa trên Giỏ',
                                        'customer' => 'Khách Hàng',
                                        'time' => 'Theo Thời Gian',
                                        'trending' => 'Xu Hướng',
                                        'together' => 'Mua Cùng',
                                        'popular' => 'Phổ Biến'
                                    ];
                                    echo $type_names[$stat['recommendation_type']] ?? $stat['recommendation_type']; 
                                    ?>
                                </span>
                            </td>
                            <td><?php echo number_format($stat['shown_count']); ?></td>
                            <td><?php echo number_format($stat['clicked_count']); ?></td>
                            <td><strong><?php echo number_format($stat['added_count']); ?></strong></td>
                            <td>
                                <span class="badge <?php echo $stat['ctr'] >= 5 ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $stat['ctr']; ?>%
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $stat['conversion_rate'] >= 3 ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $stat['conversion_rate']; ?>%
                                </span>
                            </td>
                            <td>
                                <?php 
                                $performance_score = ($stat['ctr'] * 0.4) + ($stat['conversion_rate'] * 0.6);
                                if ($performance_score >= 5) {
                                    echo '<span class="performance-badge excellent">Xuất Sắc</span>';
                                } elseif ($performance_score >= 3) {
                                    echo '<span class="performance-badge good">Tốt</span>';
                                } elseif ($performance_score >= 1) {
                                    echo '<span class="performance-badge average">Trung Bình</span>';
                                } else {
                                    echo '<span class="performance-badge poor">Cần Cải Thiện</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Performing Products -->
    <div class="section-card">
        <h3><i class="fas fa-star"></i> Top 10 Sản Phẩm Được Gợi Ý Hiệu Quả</h3>
        <div class="products-grid">
            <?php if (empty($top_products)): ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>Chưa có dữ liệu sản phẩm</p>
                </div>
            <?php else: ?>
                <?php foreach ($top_products as $product): ?>
                <div class="product-analytics-card">
                    <img src="<?php echo APP_URL . '/assets/images/products/' . ($product['image'] ?? 'logo.png'); ?>" 
                         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                         onerror="this.src='<?php echo APP_URL; ?>/assets/images/logo.png'">
                    <div class="product-info">
                        <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                        <p class="category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                        <div class="product-stats">
                            <div class="stat-item">
                                <i class="fas fa-eye"></i>
                                <span><?php echo number_format($product['impressions']); ?></span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-mouse-pointer"></i>
                                <span><?php echo number_format($product['clicks']); ?></span>
                            </div>
                            <div class="stat-item success">
                                <i class="fas fa-cart-plus"></i>
                                <span><?php echo number_format($product['conversions']); ?></span>
                            </div>
                        </div>
                        <div class="product-metrics">
                            <span class="metric">CTR: <strong><?php echo $product['ctr']; ?>%</strong></span>
                            <span class="metric">Conv: <strong><?php echo $product['conversion_rate']; ?>%</strong></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Trend Chart -->
    <?php if (!empty($daily_trends)): ?>
    <div class="section-card">
        <h3><i class="fas fa-chart-area"></i> Xu Hướng Theo Ngày (<?php echo count($daily_trends); ?> ngày gần nhất)</h3>
        <canvas id="trendChart" style="max-height: 300px;"></canvas>
    </div>
    <?php endif; ?>
</div>

<style>
.analytics-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.analytics-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-group label {
    margin: 0;
    font-weight: 600;
}

.filter-group select {
    min-width: 150px;
}

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card.primary { border-left: 4px solid #667eea; }
.stat-card.success { border-left: 4px solid #28a745; }
.stat-card.warning { border-left: 4px solid #ffc107; }

.stat-icon {
    font-size: 2.5rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(102, 126, 234, 0.1);
}

.stat-card.primary .stat-icon { color: #667eea; }
.stat-card.success .stat-icon { color: #28a745; background: rgba(40, 167, 69, 0.1); }
.stat-card.warning .stat-icon { color: #ffc107; background: rgba(255, 193, 7, 0.1); }

.stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 2rem;
    font-weight: 700;
}

.stat-content p {
    margin: 0;
    color: #6c757d;
}

.section-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.section-card h3 {
    margin-bottom: 20px;
    color: #333;
}

.badge-type {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

.badge-cart { background: #667eea; color: white; }
.badge-customer { background: #f093fb; color: white; }
.badge-time { background: #ffc107; color: #333; }
.badge-trending { background: #ff6b6b; color: white; }
.badge-together { background: #4ecdc4; color: white; }
.badge-popular { background: #95e1d3; color: #333; }

.performance-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.performance-badge.excellent { background: #28a745; color: white; }
.performance-badge.good { background: #17a2b8; color: white; }
.performance-badge.average { background: #ffc107; color: #333; }
.performance-badge.poor { background: #dc3545; color: white; }

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.product-analytics-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s;
    background: white;
}

.product-analytics-card:hover {
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    transform: translateY(-5px);
}

.product-analytics-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
}

.product-info {
    padding: 15px;
}

.product-info h4 {
    margin: 0 0 5px 0;
    font-size: 1.1rem;
    color: #333;
}

.product-info .category {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.product-stats {
    display: flex;
    justify-content: space-around;
    padding: 10px 0;
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
    margin: 10px 0;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #6c757d;
}

.stat-item.success {
    color: #28a745;
    font-weight: 700;
}

.product-metrics {
    display: flex;
    justify-content: space-around;
    margin-top: 10px;
}

.metric {
    font-size: 0.9rem;
    color: #6c757d;
}

.metric strong {
    color: var(--primary-pink);
    font-size: 1.1rem;
}

.no-data {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.no-data i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}
</style>

<?php if (!empty($daily_trends)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const trendData = <?php echo json_encode($daily_trends); ?>;
const ctx = document.getElementById('trendChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: trendData.map(d => d.date),
        datasets: [{
            label: 'Hiển Thị',
            data: trendData.map(d => d.shown),
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4
        }, {
            label: 'Click',
            data: trendData.map(d => d.clicked),
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4
        }, {
            label: 'Thêm Giỏ',
            data: trendData.map(d => d.converted),
            borderColor: '#ffc107',
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                mode: 'index',
                intersect: false,
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
