<?php 
$page_title = 'Báo Cáo & Thống Kê';
include 'includes/header.php';

require_once __DIR__ . '/../classes/Statistics.php';

$stats = new Statistics();

// Get filter parameters
$period = $_GET['period'] ?? '7days';
$year = $_GET['year'] ?? date('Y');

// Get data based on period
if ($period == '7days') {
    $revenue_data = $stats->getRevenueLastDays(7);
} elseif ($period == '30days') {
    $revenue_data = $stats->getRevenueLastDays(30);
} elseif ($period == 'month') {
    $revenue_data = $stats->getRevenueByMonth($year);
} else {
    $revenue_data = $stats->getRevenueLastDays(7);
}

// Get other statistics
$order_status = $stats->getOrderStatusDistribution();
$order_types = $stats->getOrderTypeDistribution();
$category_sales = $stats->getCategorySales();
$top_products = $stats->getTopSellingProducts(10);

// Prepare data for charts
$dates = [];
$revenues = [];
$order_counts = [];

if ($period == 'month') {
    // For monthly data
    $months = ['Th1', 'Th2', 'Th3', 'Th4', 'Th5', 'Th6', 'Th7', 'Th8', 'Th9', 'Th10', 'Th11', 'Th12'];
    for ($i = 1; $i <= 12; $i++) {
        $dates[] = $months[$i - 1];
        $revenues[] = 0;
        $order_counts[] = 0;
    }
    foreach ($revenue_data as $row) {
        $month_index = (int)$row['month'] - 1;
        $revenues[$month_index] = (float)$row['revenue'];
        $order_counts[$month_index] = (int)$row['orders'];
    }
} else {
    // For daily data
    foreach ($revenue_data as $row) {
        $dates[] = date('d/m', strtotime($row['date']));
        $revenues[] = (float)$row['revenue'];
        $order_counts[] = (int)$row['orders'];
    }
}

// Summary stats
$total_revenue = $stats->getTotalRevenue();
$today_revenue = $stats->getTodayRevenue();
$total_orders = $stats->getTotalOrders();
$today_orders = $stats->getTodayOrders();
?>

<div class="page-header">
    <h1><i class="fas fa-chart-bar"></i> Báo Cáo & Thống Kê</h1>
    <p>Phân tích doanh thu và hiệu suất kinh doanh</p>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Khoảng thời gian</label>
                <select name="period" class="form-select" onchange="this.form.submit()">
                    <option value="7days" <?php echo $period == '7days' ? 'selected' : ''; ?>>7 ngày gần đây</option>
                    <option value="30days" <?php echo $period == '30days' ? 'selected' : ''; ?>>30 ngày gần đây</option>
                    <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>Theo tháng</option>
                </select>
            </div>
            <?php if ($period == 'month'): ?>
            <div class="col-md-4">
                <label class="form-label">Năm</label>
                <select name="year" class="form-select" onchange="this.form.submit()">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card pink">
            <div class="icon-container">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <h3>Tổng Doanh Thu</h3>
                <p class="stat-number"><?php echo format_currency($total_revenue); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card blue">
            <div class="icon-container">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3>Tổng Đơn Hàng</h3>
                <p class="stat-number"><?php echo $total_orders; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card green">
            <div class="icon-container">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-info">
                <h3>Doanh Thu Hôm Nay</h3>
                <p class="stat-number"><?php echo format_currency($today_revenue); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card orange">
            <div class="icon-container">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="stat-info">
                <h3>Đơn Hàng Hôm Nay</h3>
                <p class="stat-number"><?php echo $today_orders; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Revenue Chart -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-chart-line"></i> Biểu Đồ Doanh Thu</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="80"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Order Type Distribution -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-chart-pie"></i> Loại Đơn Hàng</h5>
            </div>
            <div class="card-body">
                <canvas id="orderTypeChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Second Charts Row -->
<div class="row mb-4">
    <!-- Category Sales -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-chart-bar"></i> Doanh Thu Theo Danh Mục</h5>
            </div>
            <div class="card-body">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Order Status -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-tasks"></i> Trạng Thái Đơn Hàng</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Products Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-trophy"></i> Top 10 Sản Phẩm Bán Chạy</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tên Sản Phẩm</th>
                        <th>Số Lượng Bán</th>
                        <th>Doanh Thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (!empty($top_products)):
                        $rank = 1;
                        foreach ($top_products as $product):
                    ?>
                        <tr>
                            <td><strong><?php echo $rank++; ?></strong></td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><span class="badge bg-info"><?php echo $product['total_sold']; ?></span></td>
                            <td><strong><?php echo format_currency($product['revenue']); ?></strong></td>
                        </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <tr><td colspan="4" class="text-center">Chưa có dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dates); ?>,
        datasets: [{
            label: 'Doanh Thu (₫)',
            data: <?php echo json_encode($revenues); ?>,
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Số Đơn Hàng',
            data: <?php echo json_encode($order_counts); ?>,
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('vi-VN') + '₫';
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                }
            }
        }
    }
});

// Order Type Chart
const orderTypeCtx = document.getElementById('orderTypeChart').getContext('2d');
const orderTypeChart = new Chart(orderTypeCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php 
            $labels = [];
            foreach ($order_types as $type) {
                $label = $type['order_type'] == 'dine_in' ? 'Tại chỗ' : ($type['order_type'] == 'takeaway' ? 'Mang đi' : 'Giao hàng');
                $labels[] = "'" . $label . "'";
            }
            echo implode(', ', $labels);
        ?>],
        datasets: [{
            data: [<?php echo implode(', ', array_column($order_types, 'count')); ?>],
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Category Sales Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(categoryCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo "'" . implode("', '", array_column($category_sales, 'category_name')) . "'"; ?>],
        datasets: [{
            label: 'Doanh Thu (₫)',
            data: [<?php echo implode(', ', array_column($category_sales, 'revenue')); ?>],
            backgroundColor: 'rgba(75, 192, 192, 0.8)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('vi-VN') + '₫';
                    }
                }
            }
        }
    }
});

// Order Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'bar',
    data: {
        labels: [<?php 
            $status_labels = [];
            foreach ($order_status as $status) {
                $status_labels[] = "'" . ucfirst($status['order_status']) . "'";
            }
            echo implode(', ', $status_labels);
        ?>],
        datasets: [{
            label: 'Số Đơn',
            data: [<?php echo implode(', ', array_column($order_status, 'count')); ?>],
            backgroundColor: [
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)',
                'rgba(255, 99, 132, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        indexAxis: 'y',
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
