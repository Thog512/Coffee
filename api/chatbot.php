<?php
/**
 * Chatbot API Endpoint
 * 
 * UPGRADED MODE: Hybrid AI Chatbot
 * - Gemini AI (primary)
 * - SimpleChatbot (fallback)
 * - Order integration
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../classes/Chatbot.php';
require_once __DIR__ . '/../classes/SimpleChatbot.php';
require_once __DIR__ . '/../classes/ChatbotOrder.php';

// Initialize chatbots
$chatbot = new Chatbot();
$simpleChatbot = new SimpleChatbot();
$chatbotOrder = new ChatbotOrder();

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? 'send';

try {
    switch ($action) {
        case 'send':
            // Send message
            $message = $_POST['message'] ?? '';
            
            if (empty(trim($message))) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Tin nhắn không được để trống'
                ]);
                exit;
            }

            $user_id = $_SESSION['user_id'] ?? null;
            
            // Check if this is an order intent
            if ($chatbotOrder->detectOrderIntent($message)) {
                $products = $chatbotOrder->extractProductsFromMessage($message);
                
                if (!empty($products)) {
                    $result = $chatbotOrder->createDraftOrder(
                        $_SESSION['chatbot_session_id'] ?? session_id(), 
                        $products
                    );
                    
                    $summary = $chatbotOrder->generateOrderSummary($result['cart']);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => $summary,
                        'timestamp' => date('H:i'),
                        'order_data' => $result,
                        'has_order' => true
                    ]);
                    exit;
                }
            }
            
            // Check for order confirmation
            if (preg_match('/(xác nhận|confirm|đồng ý|ok|đặt luôn)/i', $message)) {
                $cart = $chatbotOrder->getCart();
                if (!empty($cart)) {
                    // Request customer info if not available
                    if (!isset($_SESSION['customer_name'])) {
                        echo json_encode([
                            'success' => true,
                            'message' => "Để hoàn tất đơn hàng, bạn vui lòng cung cấp:\n\n" .
                                       "📝 Họ tên:\n" .
                                       "📱 Số điện thoại:\n" .
                                       "📍 Địa chỉ giao hàng (nếu ship):\n\n" .
                                       "Hoặc gõ 'Mang về' nếu đến lấy tại quán nhé! 😊",
                            'timestamp' => date('H:i'),
                            'requires_info' => true
                        ]);
                        exit;
                    }
                }
            }
            
            // Try Gemini AI first, fallback to Simple Chatbot
            $response = $chatbot->sendMessage($message, $user_id);
            
            // If Gemini fails, use Simple Chatbot
            if (!$response['success'] && strpos($response['error'], 'API') !== false) {
                $bot_response = $simpleChatbot->getResponse($message);
                $response = [
                    'success' => true,
                    'message' => $bot_response,
                    'timestamp' => date('H:i'),
                    'fallback' => true
                ];
            }
            
            echo json_encode($response);
            break;

        case 'history':
            // Get chat history
            $history = $chatbot->getChatHistory();
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            break;

        case 'clear':
            // Clear chat history
            $chatbot->clearHistory();
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa lịch sử chat'
            ]);
            break;

        case 'welcome':
            // Get welcome message from chatbot
            echo json_encode([
                'success' => true,
                'message' => $chatbot->getWelcomeMessage()
            ]);
            break;
            
        case 'view_cart':
            // View current cart
            $cart = $chatbotOrder->getCart();
            $summary = $chatbotOrder->generateOrderSummary($cart);
            echo json_encode([
                'success' => true,
                'message' => $summary,
                'cart' => $cart
            ]);
            break;
            
        case 'clear_cart':
            // Clear cart
            $chatbotOrder->clearCart();
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa giỏ hàng! 🗑️'
            ]);
            break;
            
        case 'confirm_order':
            // Confirm and create real order
            $customer_name = $_POST['customer_name'] ?? $_SESSION['customer_name'] ?? '';
            $phone = $_POST['phone'] ?? $_SESSION['customer_phone'] ?? '';
            $address = $_POST['address'] ?? null;
            $notes = $_POST['notes'] ?? null;
            
            if (empty($customer_name) || empty($phone)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Vui lòng cung cấp tên và số điện thoại'
                ]);
                break;
            }
            
            $result = $chatbotOrder->confirmOrder($customer_name, $phone, $address, $notes);
            echo json_encode($result);
            break;

        default:
            echo json_encode([
                'success' => false,
                'error' => 'Action không hợp lệ'
            ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}
?>
