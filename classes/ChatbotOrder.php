<?php
/**
 * Chatbot Order Integration
 * Xử lý đặt hàng qua chatbot
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/Order.php';

class ChatbotOrder {
    private $conn;
    private $product;
    private $order;
    
    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
        $this->product = new Product();
        $this->order = new Order();
    }
    
    /**
     * Phát hiện intent đặt hàng từ message
     */
    public function detectOrderIntent($message) {
        $message = strtolower($message);
        
        // Patterns for ordering
        $order_patterns = [
            'gọi',
            'đặt',
            'order',
            'mua',
            'cho tôi',
            'lấy',
            'thêm'
        ];
        
        foreach ($order_patterns as $pattern) {
            if (strpos($message, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Trích xuất sản phẩm từ message
     */
    public function extractProductsFromMessage($message) {
        $products = $this->product->getAll();
        $found_products = [];
        $message_lower = strtolower($message);
        
        foreach ($products as $product) {
            $product_name_lower = strtolower($product['product_name']);
            
            // Check exact match or partial match
            if (strpos($message_lower, $product_name_lower) !== false) {
                // Try to extract quantity
                $quantity = $this->extractQuantity($message, $product_name_lower);
                
                $found_products[] = [
                    'product_id' => $product['product_id'],
                    'product_name' => $product['product_name'],
                    'price' => $product['price'],
                    'quantity' => $quantity
                ];
            }
        }
        
        return $found_products;
    }
    
    /**
     * Trích xuất số lượng từ message
     */
    private function extractQuantity($message, $product_name) {
        $message_lower = strtolower($message);
        
        // Remove product name to focus on quantity
        $text = str_replace($product_name, '', $message_lower);
        
        // Check for number patterns
        preg_match('/(\d+)\s*(ly|cái|phần|chai|lon)?/', $text, $matches);
        
        if (!empty($matches[1])) {
            return (int)$matches[1];
        }
        
        // Check for word numbers
        $word_numbers = [
            'một' => 1, 'hai' => 2, 'ba' => 3, 'bốn' => 4, 'năm' => 5,
            'sáu' => 6, 'bảy' => 7, 'tám' => 8, 'chín' => 9, 'mười' => 10
        ];
        
        foreach ($word_numbers as $word => $number) {
            if (strpos($text, $word) !== false) {
                return $number;
            }
        }
        
        return 1; // Default quantity
    }
    
    /**
     * Tạo draft order cho session
     */
    public function createDraftOrder($session_id, $products, $customer_info = null) {
        // Store in session or temporary table
        if (!isset($_SESSION['chatbot_cart'])) {
            $_SESSION['chatbot_cart'] = [];
        }
        
        foreach ($products as $product) {
            // Check if product already in cart
            $found = false;
            foreach ($_SESSION['chatbot_cart'] as &$item) {
                if ($item['product_id'] == $product['product_id']) {
                    $item['quantity'] += $product['quantity'];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $_SESSION['chatbot_cart'][] = $product;
            }
        }
        
        return [
            'success' => true,
            'cart' => $_SESSION['chatbot_cart'],
            'total' => $this->calculateTotal($_SESSION['chatbot_cart'])
        ];
    }
    
    /**
     * Tính tổng tiền
     */
    private function calculateTotal($cart) {
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }
    
    /**
     * Generate order summary message
     */
    public function generateOrderSummary($cart) {
        if (empty($cart)) {
            return "Giỏ hàng trống. Bạn muốn gọi món gì ạ? 🛒";
        }
        
        $message = "📋 ĐƠN HÀNG CỦA BẠN:\n\n";
        $total = 0;
        
        foreach ($cart as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
            
            $message .= sprintf(
                "%s x%d - %s\n",
                $item['product_name'],
                $item['quantity'],
                number_format($subtotal, 0, ',', '.') . 'đ'
            );
        }
        
        $message .= "\n💰 TỔNG CỘNG: " . number_format($total, 0, ',', '.') . "đ\n\n";
        $message .= "Bạn muốn:\n";
        $message .= "1️⃣ Xác nhận đơn hàng\n";
        $message .= "2️⃣ Thêm món khác\n";
        $message .= "3️⃣ Hủy đơn\n\n";
        $message .= "Gõ số hoặc nói rõ lựa chọn nhé! 😊";
        
        return $message;
    }
    
    /**
     * Get cart for current session
     */
    public function getCart() {
        return $_SESSION['chatbot_cart'] ?? [];
    }
    
    /**
     * Clear cart
     */
    public function clearCart() {
        $_SESSION['chatbot_cart'] = [];
        return true;
    }
    
    /**
     * Confirm and create real order
     */
    public function confirmOrder($customer_name, $phone, $delivery_address = null, $notes = null) {
        $cart = $this->getCart();
        
        if (empty($cart)) {
            return [
                'success' => false,
                'error' => 'Giỏ hàng trống'
            ];
        }
        
        try {
            // Create order
            $order_data = [
                'customer_name' => $customer_name,
                'customer_phone' => $phone,
                'order_type' => $delivery_address ? 'delivery' : 'pickup',
                'delivery_address' => $delivery_address,
                'notes' => $notes,
                'payment_method' => 'cash', // Default, can be changed
                'payment_status' => 'pending'
            ];
            
            $order_id = $this->order->create($order_data, $cart);
            
            if ($order_id) {
                $this->clearCart();
                
                return [
                    'success' => true,
                    'order_id' => $order_id,
                    'message' => "✅ Đơn hàng #{$order_id} đã được tạo thành công!\n\n" .
                                "📱 Quán sẽ liên hệ xác nhận trong 5 phút.\n" .
                                "🕐 Thời gian chuẩn bị: 15-20 phút\n\n" .
                                "Cảm ơn bạn đã đặt hàng! ❤️"
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Không thể tạo đơn hàng'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
