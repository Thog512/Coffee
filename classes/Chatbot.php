<?php
require_once __DIR__ . '/../config/gemini_config.php';
require_once __DIR__ . '/Database.php';

class Chatbot {
    private $conn;
    private $session_id;

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
        
        // Generate or retrieve session ID
        if (!isset($_SESSION['chatbot_session_id'])) {
            $_SESSION['chatbot_session_id'] = uniqid('chat_', true);
        }
        $this->session_id = $_SESSION['chatbot_session_id'];
    }

    /**
     * Send message to Gemini AI and get response
     */
    public function sendMessage($user_message, $user_id = null) {
        // Rate limiting check
        if (!$this->checkRateLimit()) {
            return [
                'success' => false,
                'error' => 'Bạn đang gửi tin nhắn quá nhanh. Vui lòng đợi ' . CHAT_COOLDOWN . ' giây.'
            ];
        }

        // Get conversation history
        $history = $this->getConversationHistory();

        // Build context with system prompt and history
        $context = $this->buildContext($history, $user_message);

        // Call Gemini API
        $ai_response = $this->callGeminiAPI($context);

        if ($ai_response['success']) {
            // Save conversation to database
            $this->saveMessage($user_message, 'user', $user_id);
            $this->saveMessage($ai_response['message'], 'bot', $user_id);

            return [
                'success' => true,
                'message' => $ai_response['message'],
                'timestamp' => date('H:i')
            ];
        } else {
            return [
                'success' => false,
                'error' => $ai_response['error']
            ];
        }
    }

    /**
     * Call Google Gemini API
     */
    private function callGeminiAPI($prompt) {
        $api_key = GEMINI_API_KEY;
        
        if ($api_key === 'YOUR_GEMINI_API_KEY_HERE') {
            return [
                'success' => false,
                'error' => 'Chưa cấu hình API key. Vui lòng cập nhật GEMINI_API_KEY trong config/gemini_config.php'
            ];
        }

        $url = GEMINI_API_URL . '?key=' . $api_key;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => CHAT_TEMPERATURE,
                'maxOutputTokens' => CHAT_MAX_TOKENS,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200) {
            $result = json_decode($response, true);
            
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                return [
                    'success' => true,
                    'message' => trim($result['candidates'][0]['content']['parts'][0]['text'])
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Không thể xử lý phản hồi từ AI'
                ];
            }
        } else {
            // Decode error response for more details
            $error_detail = '';
            if ($response) {
                $error_data = json_decode($response, true);
                if (isset($error_data['error']['message'])) {
                    $error_detail = ': ' . $error_data['error']['message'];
                }
            }
            
            return [
                'success' => false,
                'error' => 'Lỗi kết nối API (HTTP ' . $http_code . ')' . $error_detail . ($curl_error ? ' - ' . $curl_error : '')
            ];
        }
    }

    /**
     * Build context with system prompt and conversation history
     */
    private function buildContext($history, $current_message) {
        // Load training data
        $training_file = __DIR__ . '/../config/chatbot_training.json';
        $context = CHATBOT_SYSTEM_PROMPT . "\n\n";
        
        if (file_exists($training_file)) {
            $training_data = json_decode(file_get_contents($training_file), true);
            
            if ($training_data) {
                // Add comprehensive business info
                $context .= "THÔNG TIN QUÁN CHI TIẾT:\n";
                $context .= "- Tên: " . $training_data['business_info']['name'] . "\n";
                $context .= "- Địa chỉ: " . $training_data['business_info']['address'] . "\n";
                $context .= "- Hotline: " . $training_data['business_info']['phone'] . "\n";
                $context .= "- Giờ mở cửa T2-T6: " . $training_data['business_info']['hours']['weekday'] . "\n";
                $context .= "- Giờ mở cửa T7-CN: " . $training_data['business_info']['hours']['weekend'] . "\n\n";
                
                // Add amenities
                if (isset($training_data['business_info']['amenities'])) {
                    $context .= "TIỆN NGHI:\n";
                    foreach ($training_data['business_info']['amenities'] as $key => $value) {
                        $context .= "- " . ucfirst($key) . ": " . $value . "\n";
                    }
                    $context .= "\n";
                }
                
                // Add FAQ for better responses
                if (isset($training_data['faq']) && !empty($training_data['faq'])) {
                    $context .= "CÂU HỎI THƯỜNG GẶP & CÂU TRẢ LỜI MẪU:\n";
                    foreach (array_slice($training_data['faq'], 0, 10) as $faq) {
                        $context .= "Q: " . $faq['question'] . "\n";
                        $context .= "A: " . $faq['answer'] . "\n\n";
                    }
                }
            }
        }
        
        // Add product information from database
        $products_info = $this->getProductsInfo();
        if (!empty($products_info)) {
            $context .= "MENU HIỆN TẠI:\n" . $products_info . "\n\n";
        }

        // Add conversation history
        if (!empty($history)) {
            $context .= "LỊCH SỬ HỘI THOẠI:\n";
            foreach ($history as $msg) {
                $role = $msg['message_type'] === 'user' ? 'Khách' : 'Bot';
                $context .= $role . ": " . $msg['message'] . "\n";
            }
            $context .= "\n";
        }

        // Add current message
        $context .= "Khách hỏi: " . $current_message . "\n\n";
        $context .= "Hãy trả lời một cách thân thiện, chuyên nghiệp và hữu ích. ";
        $context .= "Nếu thông tin có trong FAQ, hãy trả lời y hệt như trong FAQ.";

        return $context;
    }

    /**
     * Get products information for AI context
     */
    private function getProductsInfo() {
        try {
            $query = "SELECT p.product_name, p.price, p.description, c.category_name 
                      FROM products p 
                      JOIN categories c ON p.category_id = c.category_id 
                      WHERE p.status = 'active' 
                      ORDER BY c.category_name, p.product_name 
                      LIMIT 30";
            
            $stmt = $this->conn->query($query);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $info = "";
            $current_category = "";
            
            foreach ($products as $product) {
                if ($current_category !== $product['category_name']) {
                    $current_category = $product['category_name'];
                    $info .= "\n" . $current_category . ":\n";
                }
                $price = number_format($product['price'], 0, ',', '.') . 'đ';
                $info .= "- " . $product['product_name'] . ": " . $price;
                if (!empty($product['description'])) {
                    $info .= " (" . substr($product['description'], 0, 50) . "...)";
                }
                $info .= "\n";
            }
            
            return $info;
        } catch (Exception $e) {
            return "";
        }
    }

    /**
     * Get conversation history
     */
    private function getConversationHistory($limit = null) {
        if ($limit === null) {
            $limit = CHAT_MAX_HISTORY;
        }

        $query = "SELECT message, message_type, created_at 
                  FROM chat_logs 
                  WHERE session_id = :session_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':session_id', $this->session_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_reverse($messages); // Oldest first
    }

    /**
     * Save message to database
     */
    private function saveMessage($message, $type, $user_id = null) {
        $query = "INSERT INTO chat_logs (user_id, session_id, message_type, message) 
                  VALUES (:user_id, :session_id, :message_type, :message)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':session_id', $this->session_id);
        $stmt->bindParam(':message_type', $type);
        $stmt->bindParam(':message', $message);
        $stmt->execute();
    }

    /**
     * Check rate limiting
     */
    private function checkRateLimit() {
        // Check message count
        if (!isset($_SESSION['chat_message_count'])) {
            $_SESSION['chat_message_count'] = 0;
            $_SESSION['chat_last_message_time'] = time();
        }

        // Reset counter if session is old
        if (time() - $_SESSION['chat_last_message_time'] > 3600) {
            $_SESSION['chat_message_count'] = 0;
        }

        // Check if exceeded limit
        if ($_SESSION['chat_message_count'] >= CHAT_RATE_LIMIT) {
            return false;
        }

        // Check cooldown
        if (time() - $_SESSION['chat_last_message_time'] < CHAT_COOLDOWN) {
            return false;
        }

        // Update counters
        $_SESSION['chat_message_count']++;
        $_SESSION['chat_last_message_time'] = time();

        return true;
    }

    /**
     * Get chat history for display
     */
    public function getChatHistory($limit = 50) {
        $query = "SELECT message, message_type, created_at 
                  FROM chat_logs 
                  WHERE session_id = :session_id 
                  ORDER BY created_at ASC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':session_id', $this->session_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Clear chat history
     */
    public function clearHistory() {
        $query = "DELETE FROM chat_logs WHERE session_id = :session_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':session_id', $this->session_id);
        $stmt->execute();

        // Reset session counters
        $_SESSION['chat_message_count'] = 0;
        
        return true;
    }

    /**
     * Get welcome message
     */
    public function getWelcomeMessage() {
        return CHATBOT_WELCOME_MESSAGE;
    }
}
?>
