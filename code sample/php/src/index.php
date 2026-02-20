
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Assistant</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .chat-container {
            width: 100%;
            max-width: 600px;
            height: 90vh;
            max-height: 700px;
            background: white;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-header::before {
            content: '💬';
            font-size: 24px;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .message {
            display: flex;
            margin-bottom: 12px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.user {
            justify-content: flex-end;
        }

        .message.assistant {
            justify-content: flex-start;
        }

        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 12px;
            word-wrap: break-word;
            line-height: 1.4;
        }

        .message.user .message-content {
            background: #667eea;
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.assistant .message-content {
            background: #f0f0f0;
            color: #333;
            border-bottom-left-radius: 4px;
        }

        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 12px 16px;
            background: #f0f0f0;
            border-radius: 12px;
            width: fit-content;
        }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #999;
            animation: bounce 1.4s infinite;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes bounce {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.6;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }

        .chat-input-area {
            border-top: 1px solid #e0e0e0;
            padding: 16px;
            background: #fafafa;
            display: flex;
            gap: 10px;
        }

        .input-wrapper {
            flex: 1;
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 24px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.3s;
        }

        .chat-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .send-button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 24px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s, transform 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .send-button:hover:not(:disabled) {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .send-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .send-button::after {
            content: '➤';
        }

        .user-input-area {
            display: flex;
            gap: 10px;
            align-items: center;
        }



        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #ccc transparent;
        }

        .scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .scrollbar::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }

        .scrollbar::-webkit-scrollbar-thumb:hover {
            background: #999;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            Chat Assistant
        </div>
        
        <div class="chat-messages scrollbar" id="chatMessages"></div>
        
        <div class="chat-input-area">
            <div class="input-wrapper">
                <input 
                    type="text" 
                    class="chat-input" 
                    id="messageInput" 
                    placeholder="Ask something... (e.g., What is AI?)"
                    autocomplete="off"
                />
                <button class="send-button" id="sendButton">Send</button>
            </div>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        
        let isLoading = false;

        const scrollToBottom = () => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        };

        const addMessage = (text, isUser = false) => {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user' : 'assistant'}`;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            contentDiv.textContent = text;
            
            messageDiv.appendChild(contentDiv);
            chatMessages.appendChild(messageDiv);
            scrollToBottom();
            
            return contentDiv;
        };

        const showTypingIndicator = () => {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message assistant';
            
            const indicatorDiv = document.createElement('div');
            indicatorDiv.className = 'typing-indicator';
            indicatorDiv.innerHTML = '<span></span><span></span><span></span>';
            
            messageDiv.appendChild(indicatorDiv);
            messageDiv.id = 'typingIndicator';
            chatMessages.appendChild(messageDiv);
            scrollToBottom();
        };

        const removeTypingIndicator = () => {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) {
                indicator.remove();
            }
        };

        const sendMessage = async () => {
            const message = messageInput.value.trim();
            
            if (!message) {
                alert('Please enter a message');
                return;
            }
            
            isLoading = true;
            sendButton.disabled = true;
            messageInput.disabled = true;
            
            // Add user message
            addMessage(message, true);
            messageInput.value = '';
            
            showTypingIndicator();

            try {
                const response = await fetch('/api/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        querySearch: message
                    })
                });

                removeTypingIndicator();

                if (!response.ok) {
                    throw new Error(`API error: ${response.status}`);
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let assistantMessage = '';
                const contentDiv = addMessage('', false);

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    
                    const chunk = decoder.decode(value, { stream: true });
                    assistantMessage += chunk;
                    contentDiv.textContent = assistantMessage;
                    scrollToBottom();
                }

            } catch (error) {
                removeTypingIndicator();
                console.error('Error:', error);
                addMessage(`Error: ${error.message}`, false);
            } finally {
                isLoading = false;
                sendButton.disabled = false;
                messageInput.disabled = false;
                messageInput.focus();
            }
        };

        sendButton.addEventListener('click', sendMessage);
        
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !isLoading) {
                sendMessage();
            }
        });

        messageInput.focus();

        // Add welcome message
        addMessage('👋 Hello! I\'m your chat assistant. Ask me anything and I\'ll provide streaming responses.', false);
    </script>
</body>
</html>
