<?php 
$page_title = 'Bán Hàng (POS)';
$body_class = 'pos-page'; // Add a specific class for POS page styling
include 'includes/header.php';

require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/Table.php';

$product_manager = new Product();
$products = $product_manager->getAll();

$category_manager = new Category();
$categories = $category_manager->getAll();

$table_manager = new Table();
// Only get available tables for POS
$tables = $table_manager->getAvailableTables();
?>

<style>
.pos-container {
    display: flex;
    gap: 20px;
    height: calc(100vh - 100px);
    padding: 0;
}

/* Left side - Products */
.pos-products {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #f8f9fa;
    border-radius: 12px;
    overflow: hidden;
}

.pos-products-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 20px;
    overflow-y: auto;
    padding: 20px;
    background: #fafafa;
}

.pos-products-wrapper::-webkit-scrollbar {
    width: 10px;
}

.pos-products-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.pos-products-wrapper::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #FF69B4, #C71585);
    border-radius: 10px;
}

.pos-products-wrapper::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #C71585, #FF69B4);
}

.products-main {
    display: flex;
    flex-direction: column;
}

.recommendations-sidebar {
    display: flex;
    flex-direction: column;
}

/* Section Title */
.section-title {
    background: linear-gradient(135deg, #FF69B4 0%, #C71585 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 12px 12px 0 0;
    font-size: 18px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 12px rgba(255, 105, 180, 0.3);
    margin-bottom: -1px;
}

.section-title i {
    font-size: 20px;
}

/* Search bar */
.product-search-bar {
    background: white;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    position: relative;
}

.product-search-bar i {
    position: absolute;
    left: 35px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 18px;
}

.product-search-bar input {
    width: 100%;
    padding: 14px 20px 14px 50px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
}

.product-search-bar input:focus {
    outline: none;
    border-color: #FF69B4;
    box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
}

/* Categories */
.product-categories {
    background: white;
    padding: 15px 20px;
    display: flex;
    gap: 10px;
    overflow-x: auto;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    border-top: 1px solid #f0f0f0;
}

.product-categories::-webkit-scrollbar {
    height: 6px;
}

.product-categories::-webkit-scrollbar-thumb {
    background: #FF69B4;
    border-radius: 3px;
}

.category-btn {
    padding: 10px 24px;
    border: 2px solid #e0e0e0;
    background: white;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    color: #666;
    transition: all 0.3s ease;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
}

.category-btn:hover {
    border-color: #FF69B4;
    color: #FF69B4;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(255, 105, 180, 0.2);
}

.category-btn.active {
    background: linear-gradient(135deg, #FF69B4 0%, #C71585 100%);
    color: white;
    border-color: #FF69B4;
    box-shadow: 0 4px 12px rgba(255, 105, 180, 0.3);
}

.category-btn i {
    font-size: 16px;
}

/* Product Grid - Fixed Height (2 Rows) */
.product-grid-pos {
    padding: 20px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    grid-auto-rows: auto;
    gap: 15px;
    background: white;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    max-height: 500px;
}

/* Limit to approximately 2 rows (12 items for 6 columns) */
.product-grid-pos .product-card-pos:nth-child(n+13) {
    display: none;
}

.product-card-pos {
    background: white;
    border-radius: 12px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.product-card-pos:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(255, 105, 180, 0.25);
    border-color: #FF69B4;
}

.product-card-pos img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 12px;
}

.product-name-pos {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 14px;
    height: 40px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-price-pos {
    color: #FF69B4;
    font-weight: 700;
    font-size: 16px;
}

/* AI Recommendations Section - Below Products */
.ai-recommendations {
    background: linear-gradient(135deg, #FFF0F7 0%, #FFE0ED 100%);
    padding: 25px;
    border-radius: 16px;
    border: 3px solid #FF69B4;
    box-shadow: 0 6px 20px rgba(255, 105, 180, 0.25);
    position: relative;
    overflow: hidden;
}

.ai-recommendations::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #FF69B4 0%, #C71585 50%, #FF69B4 100%);
    background-size: 200% 100%;
    animation: shimmer 3s linear infinite;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

.ai-recommendations h4 {
    font-size: 18px;
    position: relative;
    z-index: 1;
}

.ai-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.ai-header i {
    font-size: 24px;
    color: #FF69B4;
    animation: sparkle 2s ease-in-out infinite;
}

@keyframes sparkle {
    0%, 100% { transform: scale(1) rotate(0deg); }
    50% { transform: scale(1.1) rotate(10deg); }
}

.ai-header h4 {
    margin: 0;
    color: #C71585;
    font-size: 18px;
    font-weight: 700;
}

.ai-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 15px;
    position: relative;
    z-index: 1;
}

.ai-product-card {
    background: white;
    border-radius: 10px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid #FFD6E8;
    position: relative;
    overflow: hidden;
}

.ai-product-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #FF69B4, #C71585);
}

.ai-product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 16px rgba(255, 105, 180, 0.3);
    border-color: #FF69B4;
}

.recommendation-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: linear-gradient(135deg, #FF69B4, #C71585);
    color: white;
    font-size: 10px;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 600;
    z-index: 1;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.ai-product-card img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 6px;
    margin-bottom: 10px;
}

.ai-product-name {
    font-weight: 600;
    color: #333;
    font-size: 13px;
    margin-bottom: 6px;
    height: 35px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.ai-product-price {
    color: #FF69B4;
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 8px;
}

.btn-add-ai {
    width: 100%;
    padding: 8px;
    background: linear-gradient(135deg, #FF69B4, #C71585);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 12px;
    transition: all 0.3s ease;
}

.btn-add-ai:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(255, 105, 180, 0.4);
}

/* Right side - Cart */
.pos-cart {
    width: 400px;
    background: white;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.cart-header {
    background: linear-gradient(135deg, #FF69B4 0%, #C71585 100%);
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
}

.cart-header h3 {
    margin: 0 0 15px 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.order-type-selector {
    display: flex;
    gap: 10px;
}

.btn-order-type {
    flex: 1;
    padding: 10px 15px;
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.3);
    color: white;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-order-type:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.02);
}

.btn-order-type.active {
    background: white;
    color: #FF69B4;
    border-color: white;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

#table-selector {
    padding: 15px 20px;
    border-bottom: 2px solid #f0f0f0;
}

#table-selector label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

#table-selector .badge {
    background: #28a745;
    color: white;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 11px;
    margin-left: 5px;
}

#table-selector select {
    width: 100%;
    padding: 10px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

#table-selector select:focus {
    outline: none;
    border-color: #FF69B4;
    box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
}

.cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 15px 20px;
    background: #fafafa;
}

.cart-empty-placeholder {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.cart-empty-placeholder i {
    font-size: 60px;
    color: #ddd;
    margin-bottom: 15px;
}

.cart-empty-placeholder p {
    font-size: 15px;
}

.cart-item {
    background: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 12px;
    border: 2px solid #f0f0f0;
    transition: all 0.3s ease;
}

.cart-item:hover {
    border-color: #FF69B4;
    box-shadow: 0 4px 12px rgba(255, 105, 180, 0.15);
}

.item-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.item-name {
    font-weight: 600;
    color: #333;
    flex: 1;
}

.item-price {
    color: #666;
    font-size: 14px;
}

.item-quantity-controls {
    display: flex;
    align-items: center;
    gap: 15px;
    justify-content: space-between;
}

.btn-quantity {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: 2px solid #FF69B4;
    background: white;
    color: #FF69B4;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-quantity:hover {
    background: #FF69B4;
    color: white;
    transform: scale(1.1);
}

.item-quantity {
    font-weight: 700;
    font-size: 16px;
    color: #333;
}

.item-total {
    color: #FF69B4;
    font-weight: 700;
    font-size: 16px;
}

.cart-summary {
    padding: 20px;
    background: #f8f9fa;
    border-top: 2px solid #e0e0e0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 15px;
    color: #666;
}

.summary-row.total {
    font-size: 20px;
    color: #333;
    padding-top: 12px;
    border-top: 2px solid #e0e0e0;
    margin-top: 8px;
}

.summary-row.total span {
    color: #FF69B4;
}

.cart-actions {
    padding: 20px;
    display: flex;
    gap: 12px;
}

.cart-actions .btn {
    flex: 1;
    padding: 15px;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

@media (max-width: 1400px) {
    .pos-cart {
        width: 350px;
    }
    
    .product-grid-pos {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    }
    
    .ai-products-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    }
}

@media (max-width: 1200px) {
    .pos-container {
        flex-direction: column;
        height: auto;
    }
    
    .pos-cart {
        width: 100%;
    }
    
    .product-grid-pos {
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        max-height: 450px;
    }
}

@media (max-width: 768px) {
    .pos-products-wrapper {
        padding: 15px;
        gap: 15px;
    }
    
    .product-grid-pos {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        padding: 15px;
        gap: 12px;
        max-height: 400px;
    }
    
    .section-title {
        font-size: 16px;
        padding: 12px 15px;
    }
    
    .section-title i {
        font-size: 18px;
    }
    
    .ai-recommendations {
        padding: 20px;
    }
    
    .ai-header h4 {
        font-size: 16px;
    }
    
    .ai-products-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    }
}

</style>

<div class="pos-container">
    <!-- Product Selection Area -->
    <div class="pos-products">
        <div class="product-search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="product-search" placeholder="Tìm kiếm sản phẩm...">
        </div>
        <div class="product-categories">
            <button class="category-btn active" data-category="all"><i class="fas fa-th"></i> Tất cả</button>
            <?php 
            $category_icons = [
                'Coffee' => 'fa-coffee',
                'Tea' => 'fa-mug-hot',
                'Smoothies' => 'fa-blender',
                'Pastries' => 'fa-cookie-bite'
            ];
            foreach ($categories as $category): 
                $icon = $category_icons[$category['category_name']] ?? 'fa-utensils';
            ?>
                <button class="category-btn" data-category="<?php echo strtolower(htmlspecialchars($category['category_name'])); ?>">
                    <i class="fas <?php echo $icon; ?>"></i>
                    <?php echo htmlspecialchars($category['category_name']); ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Wrapper for Products and Recommendations -->
        <div class="pos-products-wrapper">
            <!-- Main Products Area -->
            <div class="products-main">
                <div class="section-title">
                    <i class="fas fa-coffee"></i>
                    <span>Sản Phẩm Nổi Bật</span>
                </div>
                <div class="product-grid-pos">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card-pos" data-id="<?php echo $product['product_id']; ?>" data-name="<?php echo htmlspecialchars($product['product_name']); ?>" data-price="<?php echo $product['price']; ?>" data-category="<?php echo strtolower(htmlspecialchars($product['category_name'])); ?>">
                            <img src="<?php echo APP_URL . '/assets/images/products/' . htmlspecialchars($product['image'] ?? 'logo.png'); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" onerror="this.src='<?php echo APP_URL; ?>/assets/images/logo.png'">
                            <div class="product-name-pos"><?php echo htmlspecialchars($product['product_name']); ?></div>
                            <div class="product-price-pos"><?php echo format_currency($product['price']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recommendations Sidebar -->
            <div class="recommendations-sidebar">
                <!-- Combined Recommendations Section -->
                <div class="ai-recommendations" id="combined-recommendations">
                    <div class="ai-header">
                        <i class="fas fa-magic"></i>
                        <h4>Gợi Ý Cho Bạn</h4>
                    </div>
                    <div class="ai-products-grid" id="combined-products-grid">
                        <!-- Combined recommendations will be loaded here -->
                        <div style="text-align: center; padding: 40px 20px; color: #999;">
                            <i class="fas fa-sparkles" style="font-size: 40px; margin-bottom: 10px;"></i>
                            <p>Thêm sản phẩm vào đơn để xem gợi ý...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart and Checkout Area -->
    <div class="pos-cart">
        <div class="cart-header">
            <h3><i class="fas fa-shopping-cart"></i> Đơn Hàng</h3>
            <div class="order-type-selector">
                <button class="btn-order-type active" data-type="dine_in">
                    <i class="fas fa-utensils"></i> Tại Bàn
                </button>
                <button class="btn-order-type" data-type="takeaway">
                    <i class="fas fa-shopping-bag"></i> Mang Đi
                </button>
            </div>
        </div>

        <div id="table-selector" class="form-group mb-3">
            <label for="table_id">Chọn Bàn <span class="badge badge-success">Chỉ bàn trống</span></label>
            <select id="table_id" class="form-control">
                <option value="">-- Chọn bàn cho khách --</option>
                <?php foreach ($tables as $table): ?>
                    <option value="<?php echo $table['table_id']; ?>">
                        <?php echo htmlspecialchars($table['table_number']); ?> 
                        (<?php echo $table['capacity']; ?> chỗ)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($tables)): ?>
                <small class="text-danger">⚠️ Hiện tại không có bàn trống</small>
            <?php endif; ?>
        </div>

        <div class="cart-items">
            <!-- Cart items will be injected here by JavaScript -->
            <div class="cart-empty-placeholder">
                <i class="fas fa-shopping-basket"></i>
                <p>Chưa có sản phẩm nào</p>
            </div>
        </div>

        <div class="cart-summary">
            <div class="summary-row">
                <span>Tạm tính:</span>
                <span id="cart-subtotal">0 ₫</span>
            </div>
            <div class="summary-row">
                <span>Giảm giá:</span>
                <span id="cart-discount">0 ₫</span>
            </div>
            <div class="summary-row total">
                <span><strong>Tổng cộng:</strong></span>
                <span id="cart-total"><strong>0 ₫</strong></span>
            </div>
        </div>

        <div class="cart-actions">
            <button class="btn btn-danger btn-block" id="clear-cart-btn">
                <i class="fas fa-times-circle"></i> Hủy Đơn
            </button>
            <button class="btn btn-success btn-block" id="checkout-btn">
                <i class="fas fa-check-circle"></i> Thanh Toán
            </button>
        </div>
    </div>
</div>

<script>
// POS JavaScript will go here
document.addEventListener('DOMContentLoaded', function() {
    const cart = [];
    const productCards = document.querySelectorAll('.product-card-pos');
    const cartItemsContainer = document.querySelector('.cart-items');
    const cartSubtotalEl = document.getElementById('cart-subtotal');
    const cartTotalEl = document.getElementById('cart-total');
    const checkoutBtn = document.getElementById('checkout-btn');
    const clearCartBtn = document.getElementById('clear-cart-btn');
    const categoryBtns = document.querySelectorAll('.category-btn');
    const searchInput = document.getElementById('product-search');

    // Load combined recommendations on page load
    loadCombinedRecommendations();

    productCards.forEach(card => {
        card.addEventListener('click', () => {
            const productId = card.dataset.id;
            const productName = card.dataset.name;
            const productPrice = parseFloat(card.dataset.price);

            const existingItem = cart.find(item => item.id === productId);

            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({ id: productId, name: productName, price: productPrice, quantity: 1 });
            }
            updateCart();
        });
    });

    function updateCart() {
        cartItemsContainer.innerHTML = '';
        let subtotal = 0;

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = `<div class="cart-empty-placeholder"><i class="fas fa-shopping-basket"></i><p>Chưa có sản phẩm nào</p></div>`;
        } else {
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;

                const cartItemEl = document.createElement('div');
                cartItemEl.classList.add('cart-item');
                cartItemEl.innerHTML = `
                    <div class="item-info">
                        <div class="item-name">${item.name}</div>
                        <div class="item-price">${item.price.toLocaleString('vi-VN')} ₫</div>
                    </div>
                    <div class="item-quantity-controls">
                        <button class="btn-quantity" data-index="${index}" data-action="decrease">-</button>
                        <span class="item-quantity">${item.quantity}</span>
                        <button class="btn-quantity" data-index="${index}" data-action="increase">+</button>
                    </div>
                    <div class="item-total">${itemTotal.toLocaleString('vi-VN')} ₫</div>
                `;
                cartItemsContainer.appendChild(cartItemEl);
            });
        }

        cartSubtotalEl.textContent = subtotal.toLocaleString('vi-VN') + ' ₫';
        cartTotalEl.textContent = subtotal.toLocaleString('vi-VN') + ' ₫';
        
        // Reload combined recommendations when cart is updated
        loadCombinedRecommendations();
    }

    // Load combined recommendations (2 cart-based + 2 time-based)
    function loadCombinedRecommendations() {
        const hour = new Date().getHours();
        const combinedGrid = document.getElementById('combined-products-grid');
        const combinedSection = document.getElementById('combined-recommendations');
        
        // Prepare cart data for cart-based recommendations
        const cartData = cart.map(item => ({
            product_id: item.id,
            quantity: item.quantity
        }));
        
        // Fetch both cart-based and time-based recommendations
        Promise.all([
            // Get 2 cart-based recommendations
            fetch('<?php echo APP_URL; ?>/api/get_recommendations.php?type=cart&limit=2', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart: cartData })
            }).then(res => res.json()),
            
            // Get 2 time-based recommendations
            fetch('<?php echo APP_URL; ?>/api/get_recommendations.php?type=time&hour=' + hour + '&limit=2')
                .then(res => res.json())
        ])
        .then(([cartData, timeData]) => {
            const cartRecs = cartData.success ? cartData.recommendations : [];
            const timeRecs = timeData.success ? timeData.recommendations : [];
            
            // Combine: 2 cart + 2 time
            const combined = [...cartRecs, ...timeRecs];
            
            if (combined.length > 0) {
                displayCombinedRecommendations(combined, cartRecs.length, timeRecs.length);
            }
        })
        .catch(error => console.error('Combined recommendations error:', error));
    }

    // Load time-based recommendations
    function loadTimeBasedRecommendations() {
        const hour = new Date().getHours();
        let message = '';
        let sectionTitle = '';
        
        if (hour >= 6 && hour < 12) {
            message = '☕ Buổi sáng tốt lành! Gợi ý món sáng phù hợp';
            sectionTitle = 'Gợi Ý Cho Buổi Sáng';
        } else if (hour >= 12 && hour < 18) {
            message = '🥗 Buổi trưa năng lượng! Gợi ý món trưa tuyệt vời';
            sectionTitle = 'Gợi Ý Cho Buổi Trưa';
        } else {
            message = '🍰 Buổi tối thư giãn! Gợi ý món tối nhẹ nhàng';
            sectionTitle = 'Gợi Ý Cho Buổi Tối';
        }
        
        document.getElementById('time-message').textContent = message;
        document.getElementById('time-section-title').textContent = sectionTitle;
        
        // Load time-based products
        fetch('<?php echo APP_URL; ?>/api/get_recommendations.php?type=time&hour=' + hour + '&limit=4')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.recommendations.length > 0) {
                    displayTimeRecommendations(data.recommendations);
                }
            })
            .catch(error => console.error('Time recommendations error:', error));
    }

    // Display time-based recommendations
    function displayTimeRecommendations(recommendations) {
        const timeGrid = document.getElementById('time-products-grid');
        const timeSection = document.getElementById('time-based-section');
        
        if (!timeGrid || !timeSection) {
            console.warn('Time-based recommendation elements not found');
            return;
        }
        
        timeGrid.innerHTML = '';
        
        recommendations.forEach(product => {
            // Track when shown
            trackRecommendation(product.product_id, 'time', 'shown');
            
            const productCard = document.createElement('div');
            productCard.classList.add('time-product-card');
            
            // Track when card is clicked
            productCard.addEventListener('click', (e) => {
                if (!e.target.closest('.btn-add-time')) {
                    trackRecommendation(product.product_id, 'time', 'clicked');
                }
            });
            
            productCard.innerHTML = `
                <img src="<?php echo APP_URL; ?>/assets/images/products/${product.image || 'logo.png'}" 
                     alt="${product.product_name}"
                     onerror="this.src='<?php echo APP_URL; ?>/assets/images/logo.png'">
                <div class="time-product-name">${product.product_name}</div>
                <div class="time-product-price">${parseFloat(product.price).toLocaleString('vi-VN')} ₫</div>
                <button class="btn-add-time" data-id="${product.product_id}" 
                        data-name="${product.product_name}" 
                        data-price="${product.price}">
                    <i class="fas fa-plus"></i> Thêm
                </button>
            `;
            timeGrid.appendChild(productCard);
        });
        
        timeSection.style.display = 'block';
        
        // Add click handlers for time-based cards
        document.querySelectorAll('.btn-add-time').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const productId = btn.dataset.id;
                const productName = btn.dataset.name;
                const productPrice = parseFloat(btn.dataset.price);
                
                // Track when added to cart
                trackRecommendation(productId, 'time', 'added_to_cart');
                
                const existingItem = cart.find(item => item.id === productId);
                if (existingItem) {
                    existingItem.quantity++;
                } else {
                    cart.push({ id: productId, name: productName, price: productPrice, quantity: 1 });
                }
                updateCart();
            });
        });
    }

    // Track recommendation events
    function trackRecommendation(productId, type, eventType) {
        fetch('<?php echo APP_URL; ?>/api/track_recommendation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                type: type,
                event_type: eventType
            })
        }).catch(error => console.error('Tracking error:', error));
    }

    // Display combined recommendations (2 cart + 2 time)
    function displayCombinedRecommendations(recommendations, cartCount, timeCount) {
        const combinedGrid = document.getElementById('combined-products-grid');
        const combinedSection = document.getElementById('combined-recommendations');
        
        combinedGrid.innerHTML = '';
        
        recommendations.forEach((product, index) => {
            // Determine type: first cartCount items are cart-based, rest are time-based
            const type = index < cartCount ? 'cart' : 'time';
            const label = index < cartCount ? 'Theo thói quen' : 'Theo thời gian';
            
            // Track when shown
            trackRecommendation(product.product_id, type, 'shown');
            
            const productCard = document.createElement('div');
            productCard.classList.add('ai-product-card');
            
            // Track when card is clicked
            productCard.addEventListener('click', (e) => {
                if (!e.target.closest('.btn-add-ai')) {
                    trackRecommendation(product.product_id, type, 'clicked');
                }
            });
            
            productCard.innerHTML = `
                <div class="recommendation-badge">${label}</div>
                <img src="<?php echo APP_URL; ?>/assets/images/products/${product.image || 'logo.png'}" 
                     alt="${product.product_name}"
                     onerror="this.src='<?php echo APP_URL; ?>/assets/images/logo.png'">
                <div class="ai-product-name">${product.product_name}</div>
                <div class="ai-product-price">${parseFloat(product.price).toLocaleString('vi-VN')} ₫</div>
                <button class="btn-add-ai" data-id="${product.product_id}" 
                        data-name="${product.product_name}" 
                        data-price="${product.price}"
                        data-type="${type}">
                    <i class="fas fa-plus"></i> Thêm
                </button>
            `;
            combinedGrid.appendChild(productCard);
        });
        
        // Add click handlers
        document.querySelectorAll('.btn-add-ai').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const productId = btn.dataset.id;
                const productName = btn.dataset.name;
                const productPrice = parseFloat(btn.dataset.price);
                const type = btn.dataset.type;
                
                // Track when added to cart
                trackRecommendation(productId, type, 'added_to_cart');
                
                const existingItem = cart.find(item => item.id === productId);
                if (existingItem) {
                    existingItem.quantity++;
                } else {
                    cart.push({ id: productId, name: productName, price: productPrice, quantity: 1 });
                }
                updateCart();
            });
        });
    }

    // Quantity buttons handler
    cartItemsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-quantity')) {
            const index = parseInt(e.target.dataset.index);
            const action = e.target.dataset.action;
            
            if (action === 'increase') {
                cart[index].quantity++;
            } else if (action === 'decrease') {
                cart[index].quantity--;
                if (cart[index].quantity === 0) {
                    cart.splice(index, 1);
                }
            }
            updateCart();
        }
    });

    clearCartBtn.addEventListener('click', () => {
        if (confirm('Bạn có chắc muốn hủy đơn hàng này?')) {
            cart.length = 0;
            updateCart();
        }
    });

    // Category filtering
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            categoryBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const selectedCategory = btn.dataset.category;

            productCards.forEach(card => {
                if (selectedCategory === 'all' || card.dataset.category === selectedCategory) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Search filtering
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        productCards.forEach(card => {
            const productName = card.dataset.name.toLowerCase();
            if (productName.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Order type selection
    document.querySelectorAll('.btn-order-type').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.btn-order-type').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const tableSelector = document.getElementById('table-selector');
            if (btn.dataset.type === 'dine_in') {
                tableSelector.style.display = 'block';
            } else {
                tableSelector.style.display = 'none';
            }
        });
    });

    // Checkout
    checkoutBtn.addEventListener('click', () => {
        if (cart.length === 0) {
            alert('Vui lòng thêm sản phẩm vào đơn hàng!');
            return;
        }

        const orderType = document.querySelector('.btn-order-type.active').dataset.type;
        const tableId = document.getElementById('table_id').value;

        if (orderType === 'dine_in' && !tableId) {
            alert('Vui lòng chọn bàn cho khách!');
            return;
        }

        const orderData = {
            cart: cart,
            order_type: orderType,
            table_id: tableId,
            // You can add customer info fields here later
            customer_name: 'Khách lẻ',
            customer_phone: ''
        };

        fetch('<?php echo APP_URL; ?>/api/create_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.message);
                cart.length = 0; // Clear the cart
                updateCart();
                // Optionally, redirect to an order success page
                // window.location.href = 'order_success.php?id=' + data.order_id;
            } else {
                alert('Lỗi: ' + (data.message || data.error || 'Không xác định'));
                console.error('Order error:', data);
            }
        })
        .catch(error => {
            console.error('Checkout error:', error);
            alert('Đã xảy ra lỗi: ' + error.message);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
