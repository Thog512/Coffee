<!-- AI Chatbot Widget -->
<div id="chatbot-widget" class="chatbot-widget">
    <!-- Chat Button -->
    <button id="chatbot-toggle" class="chatbot-toggle" title="Chat với AI">
        <i class="fas fa-comments"></i>
        <span class="chatbot-badge" id="chatbot-badge">AI</span>
    </button>

    <!-- Chat Window -->
    <div id="chatbot-window" class="chatbot-window" style="display: none;">
        <!-- Header -->
        <div class="chatbot-header">
            <div class="chatbot-header-info">
                <div class="chatbot-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chatbot-title">
                    <h4><?php echo CHATBOT_NAME; ?></h4>
                    <span class="chatbot-status">
                        <span class="status-dot"></span> Đang hoạt động
                    </span>
                </div>
            </div>
            <div class="chatbot-actions">
                <button id="chatbot-clear" class="chatbot-action-btn" title="Xóa lịch sử">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <button id="chatbot-minimize" class="chatbot-action-btn" title="Thu nhỏ">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>

        <!-- Messages Container -->
        <div id="chatbot-messages" class="chatbot-messages">
            <!-- Welcome message will be inserted here -->
        </div>

        <!-- Typing Indicator -->
        <div id="chatbot-typing" class="chatbot-typing" style="display: none;">
            <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span class="typing-text">AI đang trả lời...</span>
        </div>

        <!-- Input Area -->
        <div class="chatbot-input-area">
            <textarea 
                id="chatbot-input" 
                class="chatbot-input" 
                placeholder="Nhập tin nhắn..." 
                rows="1"
            ></textarea>
            <button id="chatbot-send" class="chatbot-send-btn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>

        <!-- Quick Actions -->
        <div class="chatbot-quick-actions">
            <button class="quick-action-btn" data-message="Xem menu">
                <i class="fas fa-utensils"></i> Menu
            </button>
            <button class="quick-action-btn" data-message="Giá cả như thế nào?">
                <i class="fas fa-tag"></i> Giá
            </button>
            <button class="quick-action-btn" data-message="Đặt bàn">
                <i class="fas fa-calendar"></i> Đặt bàn
            </button>
            <button class="quick-action-btn" data-message="Giờ mở cửa">
                <i class="fas fa-clock"></i> Giờ
            </button>
        </div>
    </div>
</div>

<script>
// Chatbot JavaScript
(function() {
    const chatbotToggle = document.getElementById('chatbot-toggle');
    const chatbotWindow = document.getElementById('chatbot-window');
    const chatbotMinimize = document.getElementById('chatbot-minimize');
    const chatbotClear = document.getElementById('chatbot-clear');
    const chatbotMessages = document.getElementById('chatbot-messages');
    const chatbotInput = document.getElementById('chatbot-input');
    const chatbotSend = document.getElementById('chatbot-send');
    const chatbotTyping = document.getElementById('chatbot-typing');
    const quickActionBtns = document.querySelectorAll('.quick-action-btn');

    let isOpen = false;
    let isWelcomeShown = false;

    // Toggle chat window
    chatbotToggle.addEventListener('click', () => {
        isOpen = !isOpen;
        chatbotWindow.style.display = isOpen ? 'flex' : 'none';
        
        if (isOpen && !isWelcomeShown) {
            showWelcomeMessage();
            isWelcomeShown = true;
        }
        
        if (isOpen) {
            chatbotInput.focus();
        }
    });

    // Minimize chat
    chatbotMinimize.addEventListener('click', () => {
        isOpen = false;
        chatbotWindow.style.display = 'none';
    });

    // Clear chat history
    chatbotClear.addEventListener('click', () => {
        if (confirm('Bạn có chắc muốn xóa lịch sử chat?')) {
            fetch('<?php echo APP_URL; ?>/api/chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=clear'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    chatbotMessages.innerHTML = '';
                    showWelcomeMessage();
                }
            });
        }
    });

    // Show welcome message
    function showWelcomeMessage() {
        fetch('<?php echo APP_URL; ?>/api/chatbot.php?action=welcome')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addMessage(data.message, 'bot');
                }
            });
    }

    // Send message
    function sendMessage() {
        const message = chatbotInput.value.trim();
        
        if (!message) return;

        // Add user message
        addMessage(message, 'user');
        chatbotInput.value = '';
        autoResizeTextarea();

        // Show typing indicator
        chatbotTyping.style.display = 'flex';
        scrollToBottom();

        // Send to API
        fetch('<?php echo APP_URL; ?>/api/chatbot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=send&message=' + encodeURIComponent(message)
        })
        .then(response => response.json())
        .then(data => {
            chatbotTyping.style.display = 'none';
            
            if (data.success) {
                addMessage(data.message, 'bot');
            } else {
                addMessage('Xin lỗi, đã có lỗi xảy ra: ' + (data.error || 'Không xác định'), 'bot error');
            }
        })
        .catch(error => {
            chatbotTyping.style.display = 'none';
            addMessage('Không thể kết nối đến server. Vui lòng thử lại sau.', 'bot error');
        });
    }

    // Add message to chat
    function addMessage(text, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'chatbot-message ' + type;
        
        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';
        messageContent.textContent = text;
        
        const messageTime = document.createElement('div');
        messageTime.className = 'message-time';
        messageTime.textContent = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        
        messageDiv.appendChild(messageContent);
        messageDiv.appendChild(messageTime);
        chatbotMessages.appendChild(messageDiv);
        
        scrollToBottom();
    }

    // Scroll to bottom
    function scrollToBottom() {
        setTimeout(() => {
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }, 100);
    }

    // Auto resize textarea
    function autoResizeTextarea() {
        chatbotInput.style.height = 'auto';
        chatbotInput.style.height = Math.min(chatbotInput.scrollHeight, 100) + 'px';
    }

    // Event listeners
    chatbotSend.addEventListener('click', sendMessage);
    
    chatbotInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    chatbotInput.addEventListener('input', autoResizeTextarea);

    // Quick actions
    quickActionBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            chatbotInput.value = btn.dataset.message;
            sendMessage();
        });
    });

    // Load chat history on page load
    window.addEventListener('load', () => {
        fetch('<?php echo APP_URL; ?>/api/chatbot.php?action=history')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.history.length > 0) {
                    data.history.forEach(msg => {
                        addMessage(msg.message, msg.message_type);
                    });
                    isWelcomeShown = true;
                }
            });
    });
})();
</script>
