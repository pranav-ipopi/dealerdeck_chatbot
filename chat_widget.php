<!-- DealerDeck AI Chat Widget -->
<!-- Include marked.js for markdown rendering -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<style>
    /* Scoped variables to avoid conflicts with your website's CSS */
    :root {
        --dd-primary: #3b82f6;
        --dd-primary-hover: #2563eb;
        --dd-bg: #ffffff;
        --dd-text: #1e293b;
        --dd-border: #e2e8f0;
        --dd-bot-msg: #f1f5f9;
        --dd-user-msg: #3b82f6;
    }

    /* Floating Button */
    .dd-chat-btn {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: var(--dd-primary);
        color: white;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: transform 0.2s, background-color 0.2s;
    }
    
    .dd-chat-btn:hover {
        background-color: var(--dd-primary-hover);
        transform: scale(1.05);
    }

    /* Chat Window */
    .dd-chat-window {
        position: fixed;
        bottom: 100px;
        right: 24px;
        width: 360px;
        height: 520px;
        max-height: 80vh;
        background-color: var(--dd-bg);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 9999;
        border: 1px solid var(--dd-border);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        opacity: 0;
        pointer-events: none;
        transform: translateY(20px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .dd-chat-window.open {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0);
    }

    /* Header */
    .dd-chat-header {
        background-color: var(--dd-bg);
        padding: 16px 20px;
        border-bottom: 1px solid var(--dd-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .dd-chat-header h3 {
        margin: 0;
        font-size: 16px;
        color: var(--dd-text);
        font-weight: 600;
    }

    .dd-close-btn {
        background: none;
        border: none;
        font-size: 24px;
        line-height: 1;
        color: #64748b;
        cursor: pointer;
        transition: color 0.2s;
    }

    .dd-close-btn:hover {
        color: #0f172a;
    }

    /* Messages Area */
    .dd-chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 16px;
        background-color: #f8fafc;
    }

    .dd-message {
        max-width: 85%;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 14.5px;
        line-height: 1.5;
        color: var(--dd-text);
    }

    .dd-message.bot {
        background-color: var(--dd-bot-msg);
        align-self: flex-start;
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .dd-message.bot p { margin: 0 0 8px 0; }
    .dd-message.bot p:last-child { margin: 0; }
    .dd-message.bot ul { margin: 0 0 8px 20px; padding: 0; }
    .dd-message.bot strong { color: var(--dd-primary); }

    .dd-message.user {
        background-color: var(--dd-user-msg);
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    /* Input Area */
    .dd-chat-input-area {
        padding: 16px;
        background-color: var(--dd-bg);
        border-top: 1px solid var(--dd-border);
        display: flex;
        gap: 10px;
    }

    .dd-chat-input-area input {
        flex: 1;
        padding: 12px 16px;
        border: 1px solid var(--dd-border);
        border-radius: 24px;
        outline: none;
        font-size: 14.5px;
        transition: border-color 0.2s;
    }

    .dd-chat-input-area input:focus {
        border-color: var(--dd-primary);
    }

    .dd-chat-input-area button {
        background-color: var(--dd-primary);
        color: white;
        border: none;
        border-radius: 50%;
        width: 44px;
        height: 44px;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: background-color 0.2s;
    }

    .dd-chat-input-area button:hover {
        background-color: var(--dd-primary-hover);
    }

    /* Typing indicator */
    .dd-typing {
        display: none;
        align-self: flex-start;
        background-color: var(--dd-bot-msg);
        padding: 14px 16px;
        border-radius: 12px;
        border-bottom-left-radius: 4px;
        gap: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .dd-typing.visible { display: flex; }
    
    .dd-dot {
        width: 6px;
        height: 6px;
        background-color: #94a3b8;
        border-radius: 50%;
        animation: dd-bounce 1.4s infinite ease-in-out both;
    }
    .dd-dot:nth-child(1) { animation-delay: -0.32s; }
    .dd-dot:nth-child(2) { animation-delay: -0.16s; }
    
    @keyframes dd-bounce {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }
</style>

<!-- Widget HTML -->
<button id="dd-chat-btn" class="dd-chat-btn">
    <!-- Chat Icon -->
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
</button>

<div id="dd-chat-window" class="dd-chat-window">
    <div class="dd-chat-header">
        <h3>DealerDeck Support</h3>
        <button id="dd-close-btn" class="dd-close-btn">&times;</button>
    </div>
    
    <div id="dd-chat-messages" class="dd-chat-messages">
        <div class="dd-message bot">Hello! I'm your DealerDeck AI Support assistant. How can I help you today?</div>
        
        <div id="dd-typing" class="dd-typing">
            <div class="dd-dot"></div>
            <div class="dd-dot"></div>
            <div class="dd-dot"></div>
        </div>
    </div>
    
    <div class="dd-chat-input-area">
        <input type="text" id="dd-user-input" placeholder="Ask a question..." autocomplete="off">
        <button id="dd-send-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
        </button>
    </div>
</div>

<!-- Widget Logic -->
<script>
    (function() {
        const chatBtn = document.getElementById('dd-chat-btn');
        const closeBtn = document.getElementById('dd-close-btn');
        const chatWindow = document.getElementById('dd-chat-window');
        const chatMessages = document.getElementById('dd-chat-messages');
        const userInput = document.getElementById('dd-user-input');
        const sendBtn = document.getElementById('dd-send-btn');
        const typingIndicator = document.getElementById('dd-typing');
        
        let sessionId = null;

        // Toggle window
        chatBtn.addEventListener('click', () => chatWindow.classList.add('open'));
        closeBtn.addEventListener('click', () => chatWindow.classList.remove('open'));

        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function addMessage(text, sender) {
            const msgDiv = document.createElement('div');
            msgDiv.className = `dd-message ${sender}`;
            
            if (sender === 'bot') {
                msgDiv.innerHTML = marked.parse(text);
            } else {
                msgDiv.textContent = text;
            }
            
            chatMessages.insertBefore(msgDiv, typingIndicator);
            scrollToBottom();
        }

        async function sendMessage() {
            const text = userInput.value.trim();
            if (!text) return;

            addMessage(text, 'user');
            userInput.value = '';
            
            typingIndicator.classList.add('visible');
            scrollToBottom();

            try {
                const response = await fetch('http://localhost:8001/chat', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_id: sessionId, message: text })
                });

                if (!response.ok) throw new Error('Network error');

                const data = await response.json();
                sessionId = data.session_id; 
                
                typingIndicator.classList.remove('visible');
                addMessage(data.response, 'bot');
                
            } catch (error) {
                console.error(error);
                typingIndicator.classList.remove('visible');
                addMessage('Sorry, the AI server is currently unreachable. Is python main.py running?', 'bot');
            }
        }

        sendBtn.addEventListener('click', sendMessage);
        userInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    })();
</script>
