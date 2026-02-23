        </main>
    </div>

    <!-- AI Chat Widget -->
    <div id="aiChatWidget" class="fixed bottom-6 right-6 w-96 bg-white rounded-2xl shadow-2xl z-50 hidden transform transition-all duration-300">
        <div class="gradient-bg text-white p-4 rounded-t-2xl flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="fas fa-robot text-xl"></i>
                <span class="font-bold">Qwen AI Assistant</span>
            </div>
            <button onclick="toggleAIChat()" class="text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="aiChatMessages" class="h-80 overflow-y-auto p-4 space-y-3 bg-gray-50">
            <div class="flex gap-2">
                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-purple-600 text-sm"></i>
                </div>
                <div class="bg-white p-3 rounded-xl rounded-tl-none shadow-sm max-w-[80%]">
                    <p class="text-sm text-gray-700">Hello! I'm your AI canteen assistant. I can help you with:</p>
                    <ul class="text-xs text-gray-600 mt-2 space-y-1">
                        <li>• Menu pricing recommendations</li>
                        <li>• Inventory management tips</li>
                        <li>• Profit/loss analysis</li>
                        <li>• Sales forecasting</li>
                        <li>• Recipe cost calculations</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="p-4 border-t">
            <form id="aiChatForm" class="flex gap-2">
                <input type="text" id="aiChatInput" placeholder="Ask me anything..." 
                    class="flex-1 px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-xl hover:bg-purple-700 transition-colors">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Notification Dropdown Toggle
        function toggleNotifications() {
            const menu = document.getElementById('notificationMenu');
            menu.classList.toggle('hidden');
        }

        // Admin Menu Toggle
        function toggleAdminMenu() {
            const menu = document.getElementById('adminMenu');
            menu.classList.toggle('hidden');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const notifDropdown = document.getElementById('notificationDropdown');
            if (notifDropdown && !notifDropdown.contains(e.target)) {
                document.getElementById('notificationMenu')?.classList.add('hidden');
            }
        });

        // AI Chat Widget Toggle
        function toggleAIChat() {
            const widget = document.getElementById('aiChatWidget');
            widget.classList.toggle('hidden');
            if (!widget.classList.contains('hidden')) {
                document.getElementById('aiChatInput').focus();
            }
        }

        // AI Chat Form Handler
        document.getElementById('aiChatForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const input = document.getElementById('aiChatInput');
            const message = input.value.trim();
            if (!message) return;

            // Add user message
            addMessage(message, 'user');
            input.value = '';

            // Show typing indicator
            showTypingIndicator();

            try {
                const response = await fetch('canteen_ai_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: message })
                });
                const data = await response.json();
                
                removeTypingIndicator();
                addMessage(data.response, 'ai');
            } catch (error) {
                removeTypingIndicator();
                addMessage('Sorry, I encountered an error. Please try again.', 'ai');
            }
        });

        function addMessage(text, sender) {
            const container = document.getElementById('aiChatMessages');
            const div = document.createElement('div');
            div.className = 'flex gap-2 ' + (sender === 'user' ? 'justify-end' : '');
            
            if (sender === 'user') {
                div.innerHTML = `
                    <div class="bg-purple-600 text-white p-3 rounded-xl rounded-tr-none shadow-sm max-w-[80%]">
                        <p class="text-sm">${escapeHtml(text)}</p>
                    </div>
                `;
            } else {
                div.innerHTML = `
                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-robot text-purple-600 text-sm"></i>
                    </div>
                    <div class="bg-white p-3 rounded-xl rounded-tl-none shadow-sm max-w-[80%]">
                        <p class="text-sm text-gray-700">${formatResponse(text)}</p>
                    </div>
                `;
            }
            
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        function showTypingIndicator() {
            const container = document.getElementById('aiChatMessages');
            const div = document.createElement('div');
            div.id = 'typingIndicator';
            div.className = 'flex gap-2';
            div.innerHTML = `
                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-purple-600 text-sm"></i>
                </div>
                <div class="bg-white p-3 rounded-xl rounded-tl-none shadow-sm">
                    <div class="flex gap-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            `;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        function removeTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) indicator.remove();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatResponse(text) {
            // Convert markdown-like formatting to HTML
            return escapeHtml(text)
                .replace(/\n/g, '<br>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>');
        }

        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert-auto-hide').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>
