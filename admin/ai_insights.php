<?php 
$page_title = 'AI Insights - Phân Tích Thông Minh';
include 'includes/header.php';

require_once __DIR__ . '/../classes/AIRecommendation.php';
require_once __DIR__ . '/../classes/Statistics.php';

$ai = new AIRecommendation();
$stats = new Statistics();

// Get AI insights
$insights = $ai->getAIInsights();
$trending = $ai->getTrendingProducts(10);
$popular = $ai->getPopularProducts(10);
$time_based = $ai->getProductsByTimeOfDay(null, 8);
?>

<div class="page-header">
    <h1><i class="fas fa-brain"></i> AI Insights - Phân Tích Thông Minh</h1>
    <p>Khám phá xu hướng và cơ hội kinh doanh từ dữ liệu</p>
</div>

<!-- Key Insights Cards -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card insight-card">
            <div class="card-header bg-gradient-purple">
                <h5 class="card-title text-white mb-0">
                    <i class="fas fa-clock"></i> Giờ Cao Điểm
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($insights['peak_hours'])): ?>
                    <p class="insight-description">Dựa trên dữ liệu 30 ngày gần đây, đây là các khung giờ bận rộn nhất:</p>
                    <div class="peak-hours-list">
                        <?php foreach ($insights['peak_hours'] as $index => $hour): ?>
                            <div class="peak-hour-item">
                                <div class="rank-badge">#<?php echo $index + 1; ?></div>
                                <div class="hour-info">
                                    <strong><?php echo $hour['hour']; ?>:00 - <?php echo $hour['hour'] + 1; ?>:00</strong>
                                    <div class="hour-stats">
                                        <span class="badge bg-primary"><?php echo $hour['order_count']; ?> đơn</span>
                                        <span class="badge bg-success"><?php echo format_currency($hour['revenue']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="ai-suggestion">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Gợi ý:</strong> Tăng cường nhân sự và chuẩn bị nguyên liệu trong các khung giờ này.
                    </div>
                <?php else: ?>
                    <p class="text-muted">Chưa có đủ dữ liệu để phân tích.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card insight-card">
            <div class="card-header bg-gradient-orange">
                <h5 class="card-title text-white mb-0">
                    <i class="fas fa-users"></i> Hành Vi Khách Hàng
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($insights['customer_behavior'])): ?>
                    <div class="behavior-stats">
                        <div class="behavior-item">
                            <div class="behavior-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="behavior-data">
                                <h3><?php echo format_currency($insights['customer_behavior']['avg_order_value']); ?></h3>
                                <p>Giá trị đơn hàng trung bình</p>
                            </div>
                        </div>
                        <div class="behavior-item">
                            <div class="behavior-icon">
                                <i class="fas fa-shopping-basket"></i>
                            </div>
                            <div class="behavior-data">
                                <h3><?php echo number_format($insights['customer_behavior']['avg_items_per_order'], 1); ?></h3>
                                <p>Số sản phẩm trung bình/đơn</p>
                            </div>
                        </div>
                    </div>
                    <div class="ai-suggestion">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Gợi ý:</strong> 
                        <?php if ($insights['customer_behavior']['avg_items_per_order'] < 2): ?>
                            Khuyến khích combo hoặc upselling để tăng số sản phẩm/đơn.
                        <?php else: ?>
                            Khách hàng đang mua nhiều sản phẩm. Hãy duy trì chất lượng dịch vụ!
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Chưa có đủ dữ liệu để phân tích.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Category Performance -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-chart-pie"></i> Hiệu Suất Danh Mục</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($insights['category_performance'])): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Danh Mục</th>
                            <th>Số Đơn</th>
                            <th>Sản Phẩm Bán</th>
                            <th>Doanh Thu</th>
                            <th>Hiệu Suất</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $max_revenue = max(array_column($insights['category_performance'], 'revenue'));
                        foreach ($insights['category_performance'] as $cat): 
                            $performance = ($cat['revenue'] / $max_revenue) * 100;
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cat['category_name']); ?></strong></td>
                                <td><?php echo $cat['orders']; ?></td>
                                <td><?php echo $cat['items_sold']; ?></td>
                                <td><?php echo format_currency($cat['revenue']); ?></td>
                                <td>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $performance; ?>%"
                                             aria-valuenow="<?php echo $performance; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            <?php echo number_format($performance, 0); ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">Chưa có dữ liệu danh mục.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Product Recommendations -->
<div class="row mb-4">
    <!-- Trending Products -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-gradient-pink">
                <h5 class="card-title text-white mb-0">
                    <i class="fas fa-fire"></i> Sản Phẩm Đang Hot
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Bán chạy nhất trong 7 ngày qua</p>
                <?php if (!empty($trending)): ?>
                    <div class="trending-products">
                        <?php foreach (array_slice($trending, 0, 5) as $index => $product): ?>
                            <div class="trending-item">
                                <div class="trending-rank"><?php echo $index + 1; ?></div>
                                <div class="trending-info">
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                    <div class="trending-stats">
                                        <span class="badge bg-warning"><?php echo $product['recent_quantity']; ?> đã bán</span>
                                        <span><?php echo format_currency($product['price']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Chưa có dữ liệu.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Underperforming Products -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-gradient-warning">
                <h5 class="card-title text-white mb-0">
                    <i class="fas fa-exclamation-triangle"></i> Cần Chú Ý
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Sản phẩm bán chậm - cần marketing</p>
                <?php if (!empty($insights['underperforming_products'])): ?>
                    <div class="underperforming-products">
                        <?php foreach ($insights['underperforming_products'] as $product): ?>
                            <div class="underperform-item">
                                <div class="underperform-info">
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                    <div class="underperform-stats">
                                        <span class="badge bg-danger"><?php echo $product['order_count'] ?? 0; ?> đơn</span>
                                        <span><?php echo format_currency($product['price']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="ai-suggestion mt-3">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Gợi ý:</strong> Tạo combo, giảm giá hoặc quảng bá các sản phẩm này trên mạng xã hội.
                    </div>
                <?php else: ?>
                    <p class="text-muted">Tất cả sản phẩm đều bán tốt!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Time-based Recommendations -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-clock"></i> Gợi Ý Theo Thời Gian
        </h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">Sản phẩm phù hợp với khung giờ hiện tại (<?php echo date('H:i'); ?>)</p>
        <?php if (!empty($time_based)): ?>
            <div class="time-products-grid">
                <?php foreach ($time_based as $product): ?>
                    <div class="time-product-card">
                        <img src="<?php echo APP_URL . '/assets/images/products/' . htmlspecialchars($product['image'] ?? 'logo.png'); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                             onerror="this.src='<?php echo APP_URL; ?>/assets/images/logo.png'">
                        <div class="time-product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                        <div class="time-product-price"><?php echo format_currency($product['price']); ?></div>
                        <div class="time-product-orders">
                            <i class="fas fa-chart-line"></i> <?php echo $product['orders_in_period']; ?> đơn trong khung giờ này
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">Chưa có dữ liệu cho khung giờ này.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.bg-gradient-purple {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-orange {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.bg-gradient-pink {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
}

.insight-card {
    height: 100%;
}

.insight-description {
    color: var(--medium-gray);
    margin-bottom: 20px;
}

.peak-hours-list {
    margin-bottom: 20px;
}

.peak-hour-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 10px;
}

.rank-badge {
    width: 40px;
    height: 40px;
    background: var(--primary-pink);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.1rem;
}

.hour-info {
    flex: 1;
}

.hour-stats {
    margin-top: 5px;
    display: flex;
    gap: 10px;
}

.behavior-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.behavior-item {
    text-align: center;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 10px;
}

.behavior-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-pink);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 1.5rem;
}

.behavior-data h3 {
    color: var(--dark-gray);
    margin-bottom: 5px;
}

.behavior-data p {
    color: var(--medium-gray);
    margin: 0;
    font-size: 0.9rem;
}

.ai-suggestion {
    padding: 15px;
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    border-radius: 5px;
}

.ai-suggestion i {
    color: #ffc107;
    margin-right: 8px;
}

.trending-products, .underperforming-products {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.trending-item, .underperform-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    background-color: #f8f9fa;
    border-radius: 8px;
    transition: transform 0.2s;
}

.trending-item:hover, .underperform-item:hover {
    transform: translateX(5px);
}

.trending-rank {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

.trending-info, .underperform-info {
    flex: 1;
}

.trending-stats, .underperform-stats {
    display: flex;
    gap: 10px;
    margin-top: 5px;
    align-items: center;
}

.time-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 20px;
}

.time-product-card {
    background-color: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    transition: transform 0.3s;
}

.time-product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.time-product-card img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 10px;
}

.time-product-name {
    font-weight: 600;
    color: var(--dark-gray);
    margin-bottom: 5px;
    min-height: 40px;
}

.time-product-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary-pink);
    margin-bottom: 8px;
}

.time-product-orders {
    font-size: 0.85rem;
    color: var(--medium-gray);
}

.time-product-orders i {
    color: var(--primary-pink);
}

.progress {
    background-color: #e9ecef;
}

.progress-bar {
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<?php include 'includes/footer.php'; ?>
