<?php
/**
 * Enhanced Simple Chatbot (No API needed)
 * Features:
 * - Fuzzy matching for better understanding
 * - Context awareness
 * - Product search from database
 * - Personalized responses
 * - Smart recommendations
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/Promotion.php';

class SimpleChatbot {
    private $conn;
    private $product;
    private $promotion;
    private $context = [];
    private $businessInfo = null;
    
    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
        $this->product = new Product();
        $this->promotion = new Promotion();
        
        // Initialize context from session
        if (!isset($_SESSION['chatbot_context'])) {
            $_SESSION['chatbot_context'] = [];
        }
        $this->context = &$_SESSION['chatbot_context'];
        
        // Load business info from training data
        $this->loadBusinessInfo();
    }
    
    /**
     * Load business info from training data JSON
     */
    private function loadBusinessInfo() {
        $training_file = __DIR__ . '/../config/chatbot_training.json';
        if (file_exists($training_file)) {
            $data = json_decode(file_get_contents($training_file), true);
            if ($data && isset($data['business_info'])) {
                $this->businessInfo = $data['business_info'];
            }
        }
    }
    
    /**
     * Get response based on user message with context awareness
     */
    public function getResponse($message) {
        $originalMessage = trim($message);
        $message = $this->normalizeText($message);
        
        // DEBUG: Log normalized message
        error_log("SimpleChatbot DEBUG - Original: [$originalMessage] → Normalized: [$message]");
        
        // Save message to context
        $this->addToContext('last_message', $originalMessage);
        
        // PRIORITY CHECK: Opening hours (check BEFORE normalization to avoid issues)
        if (mb_stripos($originalMessage, 'giờ') !== false || mb_stripos($originalMessage, 'gio') !== false) {
            if (mb_stripos($originalMessage, 'mở') !== false || mb_stripos($originalMessage, 'mo') !== false ||
                mb_stripos($originalMessage, 'đóng') !== false || mb_stripos($originalMessage, 'dong') !== false ||
                mb_stripos($originalMessage, 'làm việc') !== false || mb_stripos($originalMessage, 'lam viec') !== false ||
                mb_stripos($originalMessage, 'mấy') !== false || mb_stripos($originalMessage, 'may') !== false) {
                
                $weekday = $this->businessInfo['hours']['weekday'] ?? '7:00 - 22:00';
                $weekend = $this->businessInfo['hours']['weekend'] ?? '7:00 - 23:00';
                
                return "⏰ Giờ mở cửa của Hiniu Coffee:\n\n" .
                       "🌅 Thứ 2 - Thứ 6: " . $weekday . "\n" .
                       "🎉 Thứ 7 - Chủ Nhật: " . $weekend . "\n\n" .
                       "Chúng tôi luôn sẵn sàng phục vụ bạn! ☕";
            }
        }
        
        // Greetings with personalization
        if ($this->matchPattern($message, ['xin chao', 'hello', 'hi', 'chao', 'hey'])) {
            $greeting = $this->getPersonalizedGreeting();
            return $greeting . " 😊 Tôi là Hiniu Bot, trợ lý ảo của quán cà phê.\n\n" .
                   "Tôi có thể giúp bạn:\n" .
                   "🍵 Xem menu và giá cả\n" .
                   "🔍 Tìm món ăn/uống\n" .
                   "📅 Đặt bàn\n" .
                   "💡 Tư vấn đồ uống phù hợp\n" .
                   "⏰ Giờ mở cửa & địa chỉ\n" .
                   "🎁 Khuyến mãi hiện tại\n\n" .
                   "Bạn muốn biết gì ạ?";
        }
        
        // Location - CHECK EARLY
        if ($this->matchPattern($message, ['dia chi', 'o dau', 'location', 'address', 'cho nao'])) {
            $address = $this->businessInfo['address'] ?? '[Chưa cập nhật địa chỉ]';
            $parking = $this->businessInfo['amenities']['parking'] ?? 'Có chỗ đậu xe';
            
            return "📍 Địa chỉ Hiniu Coffee:\n\n" .
                   $address . "\n\n" .
                   "🚗 " . $parking . "\n\n" .
                   "Hẹn gặp bạn tại quán! 😊";
        }
        
        // Contact - CHECK EARLY
        if ($this->matchPattern($message, ['lien he', 'contact', 'hotline', 'so dien thoai', 'sdt'])) {
            $phone = $this->businessInfo['phone'] ?? '[Chưa cập nhật]';
            
            return "📞 Liên hệ Hiniu Coffee:\n\n" .
                   "☎️ Hotline: " . $phone . "\n" .
                   "📧 Email: info@hiniucoffee.com\n" .
                   "📱 Facebook: Hiniu Coffee\n\n" .
                   "Chúng tôi luôn sẵn sàng hỗ trợ bạn! ❤️";
        }
        
        // Search for specific product
        if ($this->matchPattern($message, ['tim', 'co', 'ban'])) {
            $searchResult = $this->searchProduct($originalMessage);
            if ($searchResult) {
                return $searchResult;
            }
        }
        
        // Menu
        if ($this->matchPattern($message, ['menu', 'thuc don', 'mon', 'do uong', 'danh sach'])) {
            return $this->getMenuResponse();
        }
        
        // Prices
        if ($this->matchPattern($message, ['gia', 'bao nhieu', 'tien', 'cost', 'price'])) {
            // Check if asking about specific product
            $productPrice = $this->getProductPrice($originalMessage);
            if ($productPrice) {
                return $productPrice;
            }
            return $this->getPriceResponse();
        }
        
        // Promotions
        if ($this->matchPattern($message, ['khuyen mai', 'giam gia', 'promotion', 'discount', 'sale'])) {
            return $this->getPromotions();
        }
        
        // Best sellers
        if ($this->matchPattern($message, ['ban chay', 'noi tieng', 'popular', 'best seller', 'hot'])) {
            return $this->getBestSellers();
        }
        
        // Coffee recommendations
        if ($this->matchPattern($message, ['ca phe', 'coffee', 'cafe'])) {
            return $this->getCoffeeRecommendation($message);
        }
        
        // Tea recommendations
        if ($this->matchPattern($message, ['tra', 'tea'])) {
            return $this->getTeaRecommendation();
        }
        
        // Sweet drinks
        if ($this->matchPattern($message, ['ngot', 'sweet', 'duong'])) {
            return "Nếu bạn thích vị ngọt, tôi gợi ý:\n\n" .
                   "☕ Bạc Xỉu - Nhiều sữa, ít cà phê, rất ngọt\n" .
                   "🥤 Trà Sữa - Ngọt ngào, béo ngậy\n" .
                   "🍫 Chocolate - Đậm đà, ngọt dịu\n" .
                   "🧋 Smoothie - Tươi mát, ngọt tự nhiên\n\n" .
                   "Bạn muốn thử món nào ạ? 😊";
        }
        
        // Bitter/Strong
        if ($this->matchPattern($message, ['dang', 'manh', 'dam', 'bitter', 'strong'])) {
            return "Với khẩu vị đậm đà, tôi gợi ý:\n\n" .
                   "☕ Espresso - Đậm đà, mạnh mẽ\n" .
                   "☕ Americano - Cà phê nguyên chất\n" .
                   "☕ Cà Phê Đen - Truyền thống Việt Nam\n\n" .
                   "Bạn thích loại nào ạ?";
        }
        
        // Reservation
        if ($this->matchPattern($message, ['dat ban', 'book', 'reservation', 'reserve'])) {
            $phone = $this->businessInfo['phone'] ?? '0586159466';
            return "Để đặt bàn, bạn có thể:\n\n" .
                   "📞 Gọi hotline: " . $phone . "\n" .
                   "💻 Đặt online tại website\n" .
                   "📱 Nhắn tin Facebook\n\n" .
                   "Hoặc cho tôi biết:\n" .
                   "- Số người\n" .
                   "- Ngày giờ\n" .
                   "- Yêu cầu đặc biệt (nếu có)\n\n" .
                   "Tôi sẽ ghi nhận và liên hệ lại cho bạn! 😊";
        }
        
        // Help
        if ($this->matchPattern($message, ['giup', 'help', 'huong dan', 'lam sao'])) {
            return $this->getHelpResponse();
        }
        
        // Thanks
        if ($this->matchPattern($message, ['cam on', 'thank', 'thanks'])) {
            return "Rất vui được giúp bạn! 😊\n\n" .
                   "Nếu có thắc mắc gì khác, đừng ngại hỏi nhé!\n" .
                   "Chúc bạn một ngày tuyệt vời! ☕❤️";
        }
        
        // Goodbye
        if ($this->matchPattern($message, ['tam biet', 'bye', 'goodbye', 'hen gap lai'])) {
            return "Tạm biệt! Hẹn gặp lại bạn tại Hiniu Coffee! 👋☕\n\n" .
                   "Chúc bạn một ngày tuyệt vời! 😊";
        }
        
        // Default response
        return "Xin lỗi, tôi chưa hiểu rõ câu hỏi của bạn. 😅\n\n" .
               "Bạn có thể hỏi tôi về:\n" .
               "🍵 Menu và giá cả\n" .
               "📅 Đặt bàn\n" .
               "💡 Tư vấn đồ uống\n" .
               "⏰ Giờ mở cửa\n" .
               "📍 Địa chỉ quán\n\n" .
               "Hoặc gọi hotline [Số điện thoại] để được hỗ trợ trực tiếp nhé! 😊";
    }
    
    /**
     * Normalize Vietnamese text (remove accents)
     */
    private function normalizeText($text) {
        $text = strtolower($text);
        $vietnamese = ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
                       'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
                       'ì','í','ị','ỉ','ĩ',
                       'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
                       'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
                       'ỳ','ý','ỵ','ỷ','ỹ',
                       'đ'];
        $normalized = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
                       'e','e','e','e','e','e','e','e','e','e','e',
                       'i','i','i','i','i',
                       'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
                       'u','u','u','u','u','u','u','u','u','u','u',
                       'y','y','y','y','y',
                       'd'];
        return str_replace($vietnamese, $normalized, $text);
    }
    
    /**
     * Match pattern with fuzzy matching
     */
    private function matchPattern($message, $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
            // Fuzzy match (allow 1 character difference)
            if (levenshtein($keyword, substr($message, 0, strlen($keyword))) <= 1) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Add to context
     */
    private function addToContext($key, $value) {
        $this->context[$key] = $value;
        $_SESSION['chatbot_context'] = $this->context;
    }
    
    /**
     * Get personalized greeting based on time
     */
    private function getPersonalizedGreeting() {
        $hour = date('G');
        if ($hour >= 5 && $hour < 12) {
            return "Chào buổi sáng!";
        } elseif ($hour >= 12 && $hour < 18) {
            return "Chào buổi chiều!";
        } else {
            return "Chào buổi tối!";
        }
    }
    
    /**
     * Search for product
     */
    private function searchProduct($query) {
        $query = strtolower($query);
        $products = $this->product->getAll();
        
        $found = [];
        foreach ($products as $product) {
            $name = strtolower($product['product_name']);
            if (strpos($name, $query) !== false || strpos($query, $name) !== false) {
                $found[] = $product;
            }
        }
        
        if (empty($found)) {
            return null;
        }
        
        if (count($found) == 1) {
            $p = $found[0];
            return "✨ " . $p['product_name'] . "\n\n" .
                   "💰 Giá: " . number_format($p['price'], 0, ',', '.') . "đ\n" .
                   "📝 Mô tả: " . ($p['description'] ?: 'Đang cập nhật') . "\n\n" .
                   "Bạn muốn đặt món này không? 😊";
        }
        
        $response = "🔍 Tôi tìm thấy " . count($found) . " món:\n\n";
        foreach ($found as $p) {
            $response .= "• " . $p['product_name'] . " - " . number_format($p['price'], 0, ',', '.') . "đ\n";
        }
        $response .= "\nBạn muốn biết thêm về món nào? 😊";
        
        return $response;
    }
    
    /**
     * Get product price
     */
    private function getProductPrice($query) {
        return $this->searchProduct($query);
    }
    
    /**
     * Get menu response
     */
    private function getMenuResponse() {
        $products = $this->product->getAll();
        
        if (empty($products)) {
            return "Menu đang được cập nhật. Vui lòng liên hệ quán để biết thêm chi tiết! 😊";
        }
        
        $response = "📋 MENU HINIU COFFEE:\n\n";
        $current_category = "";
        
        foreach ($products as $product) {
            if ($current_category !== $product['category_name']) {
                $current_category = $product['category_name'];
                $response .= "\n🔸 " . strtoupper($current_category) . ":\n";
            }
            $price = number_format($product['price'], 0, ',', '.') . 'đ';
            $response .= "• " . $product['product_name'] . " - " . $price . "\n";
        }
        
        $response .= "\n💡 Bạn muốn biết thêm về món nào không? 😊";
        
        return $response;
    }
    
    /**
     * Get price response
     */
    private function getPriceResponse() {
        return "💰 Giá tại Hiniu Coffee rất hợp lý:\n\n" .
               "☕ Cà phê: 35,000đ - 65,000đ\n" .
               "🧋 Trà sữa: 40,000đ - 60,000đ\n" .
               "🥤 Smoothie: 45,000đ - 70,000đ\n" .
               "🍰 Bánh ngọt: 25,000đ - 50,000đ\n\n" .
               "Gõ 'menu' để xem chi tiết từng món nhé! 😊";
    }
    
    /**
     * Get coffee recommendation
     */
    private function getCoffeeRecommendation($message) {
        if ($this->matchPattern($message, ['da', 'lanh', 'ice', 'cold'])) {
            return "☕ Cà phê đá tuyệt vời:\n\n" .
                   "• Cà Phê Đen Đá - Đậm đà truyền thống\n" .
                   "• Bạc Xỉu Đá - Ngọt ngào, mát lạnh\n" .
                   "• Cà Phê Sữa Đá - Hài hòa, thơm ngon\n" .
                   "• Cold Brew - Mượt mà, ít acid\n\n" .
                   "Bạn thích loại nào ạ? 😊";
        } else {
            return "☕ Cà phê đặc biệt tại Hiniu:\n\n" .
                   "• Espresso - Đậm đà, mạnh mẽ (45k)\n" .
                   "• Cappuccino - Bọt sữa mịn màng (55k)\n" .
                   "• Latte - Sữa tươi thơm béo (55k)\n" .
                   "• Cà Phê Trứng - Độc đáo Hà Nội (60k)\n" .
                   "• Cà Phê Muối - Vị mặn ngọt hài hòa (55k)\n\n" .
                   "Bạn muốn thử món nào? 😊";
        }
    }
    
    /**
     * Get tea recommendation
     */
    private function getTeaRecommendation() {
        return "🍵 Trà thơm ngon tại Hiniu:\n\n" .
               "• Trà Xanh - Thanh mát, giải nhiệt (40k)\n" .
               "• Trà Đen - Đậm đà, thơm lừng (40k)\n" .
               "• Matcha Latte - Nhật Bản chính hiệu (60k)\n" .
               "• Trà Sữa Trân Châu - Ngọt ngào, béo ngậy (50k)\n" .
               "• Oolong Tea - Hảo hạng, quý phái (45k)\n\n" .
               "Bạn thích loại nào? 😊";
    }
    
    /**
     * Get promotions from database
     */
    private function getPromotions() {
        $activePromotions = $this->promotion->getActive();
        
        if (empty($activePromotions)) {
            return "🎁 KHUYẾN MÃI HIỆN TẠI:\n\n" .
                   "Hiện tại chưa có chương trình khuyến mãi nào.\n" .
                   "Vui lòng theo dõi fanpage để cập nhật ưu đãi mới nhất! 😊";
        }
        
        $response = "🎁 KHUYẾN MÃI HIỆN TẠI:\n\n";
        
        foreach ($activePromotions as $promo) {
            $response .= "🔥 " . $promo['promotion_name'] . "\n";
            
            // Add discount info
            if ($promo['promotion_type'] == 'percentage') {
                $response .= "   Giảm " . $promo['discount_value'] . "%";
                if ($promo['max_discount']) {
                    $response .= " (tối đa " . number_format($promo['max_discount'], 0, ',', '.') . "đ)";
                }
            } else if ($promo['promotion_type'] == 'fixed_amount') {
                $response .= "   Giảm " . number_format($promo['discount_value'], 0, ',', '.') . "đ";
            } else if ($promo['promotion_type'] == 'buy_x_get_y') {
                $response .= "   Mua " . $promo['buy_quantity'] . " tặng " . $promo['get_quantity'];
            }
            
            // Add minimum order
            if ($promo['min_order_value'] > 0) {
                $response .= "\n   Đơn tối thiểu: " . number_format($promo['min_order_value'], 0, ',', '.') . "đ";
            }
            
            // Add time restriction
            if ($promo['start_time'] && $promo['end_time']) {
                $response .= "\n   ⏰ " . substr($promo['start_time'], 0, 5) . " - " . substr($promo['end_time'], 0, 5);
            }
            
            // Add voucher code
            if ($promo['voucher_code']) {
                $response .= "\n   💳 Mã: " . $promo['voucher_code'];
            }
            
            $response .= "\n\n";
        }
        
        $response .= "Đến ngay để nhận ưu đãi nhé! 😊";
        
        return $response;
    }
    
    /**
     * Get best sellers
     */
    private function getBestSellers() {
        return "🔥 TOP MÓN BÁN CHẠY NHẤT:\n\n" .
               "1️⃣ Cappuccino - Bọt sữa mịn màng (55k)\n" .
               "2️⃣ Trà Sữa Trân Châu - Ngọt ngào (50k)\n" .
               "3️⃣ Latte - Sữa tươi thơm béo (55k)\n" .
               "4️⃣ Cà Phê Muối - Độc đáo (55k)\n" .
               "5️⃣ Matcha Latte - Nhật Bản (60k)\n\n" .
               "Đây là những món khách yêu thích nhất! 😊\n" .
               "Bạn muốn thử món nào?";
    }
    
    /**
     * Get help response
     */
    private function getHelpResponse() {
        return "💡 TÔI CÓ THỂ GIÚP BẠN:\n\n" .
               "🔍 Tìm món: 'Tìm cappuccino', 'Có trà sữa không?'\n" .
               "📋 Xem menu: 'Menu', 'Thực đơn'\n" .
               "💰 Hỏi giá: 'Giá latte bao nhiêu?'\n" .
               "🎁 Khuyến mãi: 'Có giảm giá không?'\n" .
               "🔥 Món hot: 'Món nào bán chạy?'\n" .
               "📅 Đặt bàn: 'Đặt bàn cho 4 người'\n" .
               "⏰ Giờ mở cửa: 'Mấy giờ mở cửa?'\n" .
               "📍 Địa chỉ: 'Quán ở đâu?'\n\n" .
               "Cứ hỏi thoải mái nhé! 😊";
    }
}
?>
