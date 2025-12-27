/**
 * HospiLink Chatbot JavaScript
 * Handles chatbot UI and API communication
 */

let chatbotOpen = false;
let chatbotLoading = false;

/**
 * Toggle chatbot visibility
 */
function toggleChatbot() {
    const widget = document.getElementById('chatbotWidget');
    const fab = document.getElementById('chatbotFab');
    
    if (chatbotOpen) {
        widget.classList.remove('active');
        fab.style.display = 'flex';
        chatbotOpen = false;
    } else {
        widget.classList.add('active');
        fab.style.display = 'none';
        document.getElementById('chatbotInput').focus();
        chatbotOpen = true;
    }
}

/**
 * Handle keyboard input
 */
function handleChatbotKeypress(event) {
    if (event.key === 'Enter' && !chatbotLoading) {
        sendChatbotMessage();
    }
}

/**
 * Send message to chatbot API
 */
async function sendChatbotMessage() {
    const inputField = document.getElementById('chatbotInput');
    const messagesContainer = document.getElementById('chatbotMessages');
    const message = inputField.value.trim();
    
    if (!message || chatbotLoading) return;
    
    // Reset input field
    inputField.value = '';
    
    // Show user message
    appendMessage(message, 'user');
    
    // Show typing indicator
    chatbotLoading = true;
    const typingId = showTypingIndicator();
    
    try {
        // Send to API
        const response = await fetch('php/chatbot_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: message
            })
        });
        
        const data = await response.json();
        
        // Remove typing indicator
        removeElement(typingId);
        
        if (data.success) {
            // Add bot response
            appendMessage(data.message, 'bot', data.is_emergency);
            
            // Add suggestions if available
            if (data.suggestions) {
                appendSuggestions(data.suggestions);
            }
        } else {
            // Show error message
            appendMessage(
                data.error || 'Sorry, I couldn\'t process your request. Please try again.',
                'bot',
                false,
                true
            );
        }
    } catch (error) {
        console.error('Chatbot error:', error);
        removeElement(typingId);
        appendMessage(
            'Sorry, I\'m having trouble connecting. Please try again later or contact us at +91-6353439877',
            'bot',
            false,
            true
        );
    } finally {
        chatbotLoading = false;
        inputField.focus();
    }
}

/**
 * Append message to chat
 */
function appendMessage(text, sender, isEmergency = false, isError = false) {
    const messagesContainer = document.getElementById('chatbotMessages');
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `chatbot-message ${sender}-message`;
    
    let html = `<div class="message-content${isError ? ' error-message' : ''}">`;
    
    // Format bot messages with proper HTML
    if (sender === 'bot') {
        html += formatBotMessage(text);
    } else {
        html += `<p>${escapeHtml(text)}</p>`;
    }
    
    // Add emergency alert if needed
    if (isEmergency) {
        html += `<div class="emergency-alert">
                    <p><i class="ri-alert-fill"></i> 🚨 This sounds urgent. Please seek immediate medical help or visit our emergency department!</p>
                </div>`;
    }
    
    html += `</div>`;
    messageDiv.innerHTML = html;
    
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

/**
 * Format bot message with proper HTML rendering
 */
function formatBotMessage(text) {
    // Escape HTML first
    text = escapeHtml(text);
    
    // Convert markdown-style formatting to HTML
    // Bold text: **text** or __text__
    text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/__(.+?)__/g, '<strong>$1</strong>');
    
    // Italic text: *text* or _text_
    text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
    text = text.replace(/_(.+?)_/g, '<em>$1</em>');
    
    // Headers: ## Header or ### Header
    text = text.replace(/###\s(.+?)(?:\n|$)/g, '<h4>$1</h4>');
    text = text.replace(/##\s(.+?)(?:\n|$)/g, '<h3>$1</h3>');
    
    // Bullet points: - item or * item
    const lines = text.split('\n');
    let inList = false;
    let formatted = [];
    
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        
        // Check for bullet points
        if (line.match(/^[-•*]\s/)) {
            if (!inList) {
                formatted.push('<ul>');
                inList = true;
            }
            formatted.push(`<li>${line.replace(/^[-•*]\s/, '')}</li>`);
        } else if (line.match(/^\d+\.\s/)) {
            // Numbered lists
            if (!inList) {
                formatted.push('<ol>');
                inList = true;
            }
            formatted.push(`<li>${line.replace(/^\d+\.\s/, '')}</li>`);
        } else {
            if (inList) {
                // Check if previous list was ul or ol
                if (formatted[formatted.length - 1] && formatted[formatted.length - 1].indexOf('<li>') !== -1) {
                    // Find the opening tag
                    for (let j = formatted.length - 1; j >= 0; j--) {
                        if (formatted[j] === '<ul>' || formatted[j] === '<ol>') {
                            formatted.push(formatted[j] === '<ul>' ? '</ul>' : '</ol>');
                            break;
                        }
                    }
                }
                inList = false;
            }
            
            if (line.length > 0) {
                formatted.push(`<p>${line}</p>`);
            }
        }
    }
    
    // Close any open list
    if (inList) {
        for (let j = formatted.length - 1; j >= 0; j--) {
            if (formatted[j] === '<ul>') {
                formatted.push('</ul>');
                break;
            } else if (formatted[j] === '<ol>') {
                formatted.push('</ol>');
                break;
            }
        }
    }
    
    return formatted.join('\n');
}

/**
 * Show typing indicator
 */
function showTypingIndicator() {
    const messagesContainer = document.getElementById('chatbotMessages');
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chatbot-message bot-message';
    messageDiv.id = 'typing-indicator';
    
    messageDiv.innerHTML = `
        <div class="message-content">
            <div class="typing-indicator">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        </div>
    `;
    
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    return 'typing-indicator';
}

/**
 * Remove element by ID
 */
function removeElement(id) {
    const element = document.getElementById(id);
    if (element) {
        element.remove();
    }
}

/**
 * Append suggestion buttons
 */
function appendSuggestions(suggestions) {
    const messagesContainer = document.getElementById('chatbotMessages');
    
    const suggestionsDiv = document.createElement('div');
    suggestionsDiv.className = 'chatbot-message bot-message';
    
    let html = '<div class="message-content"><div class="suggestions-container">';
    
    for (const [label, link] of Object.entries(suggestions)) {
        html += `<a href="${escapeHtml(link)}" class="suggestion-btn">${escapeHtml(label)}</a>`;
    }
    
    html += '</div></div>';
    suggestionsDiv.innerHTML = html;
    
    messagesContainer.appendChild(suggestionsDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Load chat suggestions on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.getElementById('chatbotMessages');
    
    // Add initial suggestions
    const initialSuggestions = {
        'Symptoms & Diseases': '#',
        'Health Tips': '#',
        'When to See a Doctor': '#',
        'Book an Appointment': 'appointment.html'
    };
    
    // Add quick action buttons after initial message
    if (messagesContainer.children.length > 0) {
        const wrapper = document.createElement('div');
        wrapper.className = 'chatbot-message bot-message';
        wrapper.innerHTML = `
            <div class="message-content">
                <div class="suggestions-container">
                    <p style="margin: 0 0 10px 0; font-weight: 600; font-size: 12px;">Quick Topics:</p>
                    <button class="suggestion-btn" onclick="sendChatbotMessage('Tell me about common cold symptoms')">
                        <i class="ri-virus-line"></i> Common Cold
                    </button>
                    <button class="suggestion-btn" onclick="suggestQuery('fever')">
                        <i class="ri-temp-cold-line"></i> Fever Treatment
                    </button>
                    <button class="suggestion-btn" onclick="suggestQuery('headache')">
                        <i class="ri-brain-line"></i> Headache Relief
                    </button>
                    <button class="suggestion-btn" onclick="suggestQuery('when should I see a doctor')">
                        <i class="ri-hospital-line"></i> When to See Doctor
                    </button>
                </div>
            </div>
        `;
        messagesContainer.appendChild(wrapper);
    }
});

/**
 * Send suggested query
 */
function suggestQuery(query) {
    document.getElementById('chatbotInput').value = query;
    sendChatbotMessage();
}

// Initialize chatbot on page load
window.addEventListener('load', function() {
    // Chatbot is ready to use
    console.log('HospiLink Chatbot loaded successfully');
});
