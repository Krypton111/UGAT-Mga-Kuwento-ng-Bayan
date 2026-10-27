// assets/js/modal.js
(function() {
    'use strict';

    // Create modal HTML
    function createModal() {
        const existing = document.getElementById('customModalOverlay');
        if (existing) return existing;

        const overlay = document.createElement('div');
        overlay.id = 'customModalOverlay';
        overlay.className = 'custom-modal-overlay';
        overlay.innerHTML = `
            <div class="custom-modal">
                <div class="modal-icon" id="modalIcon">💀</div>
                <div class="modal-title" id="modalTitle">Dark Realm</div>
                <div class="modal-message" id="modalMessage">Message here</div>
                <div id="modalInputContainer" style="display:none;">
                    <input type="text" class="modal-input" id="modalInput" placeholder="Enter value...">
                </div>
                <div class="modal-buttons" id="modalButtons">
                    <button class="modal-btn primary" id="modalConfirmBtn">Confirm</button>
                    <button class="modal-btn" id="modalCancelBtn">Cancel</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        return overlay;
    }

    // Show a custom alert
    function showAlert(message, title = 'Dark Realm', icon = '💀') {
        return new Promise((resolve) => {
            const overlay = createModal();
            document.getElementById('modalIcon').textContent = icon;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('modalInputContainer').style.display = 'none';
            
            const confirmBtn = document.getElementById('modalConfirmBtn');
            const cancelBtn = document.getElementById('modalCancelBtn');
            
            confirmBtn.textContent = 'OK';
            confirmBtn.className = 'modal-btn primary';
            cancelBtn.style.display = 'none';
            
            overlay.classList.add('active');
            
            confirmBtn.onclick = function() {
                overlay.classList.remove('active');
                resolve(true);
            };
            
            overlay.onclick = function(e) {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                    resolve(true);
                }
            };
            
            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Enter' && overlay.classList.contains('active')) {
                    overlay.classList.remove('active');
                    document.removeEventListener('keydown', handler);
                    resolve(true);
                }
            });
        });
    }

    // Show a custom confirm dialog
    function showConfirm(message, title = 'Dark Realm', icon = '⚔️', confirmText = 'Confirm', cancelText = 'Cancel') {
        return new Promise((resolve) => {
            const overlay = createModal();
            document.getElementById('modalIcon').textContent = icon;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('modalInputContainer').style.display = 'none';
            
            const confirmBtn = document.getElementById('modalConfirmBtn');
            const cancelBtn = document.getElementById('modalCancelBtn');
            
            confirmBtn.textContent = confirmText;
            confirmBtn.className = 'modal-btn primary';
            cancelBtn.textContent = cancelText;
            cancelBtn.style.display = 'inline-block';
            cancelBtn.className = 'modal-btn';
            
            overlay.classList.add('active');
            
            confirmBtn.onclick = function() {
                overlay.classList.remove('active');
                resolve(true);
            };
            
            cancelBtn.onclick = function() {
                overlay.classList.remove('active');
                resolve(false);
            };
            
            overlay.onclick = function(e) {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                    resolve(false);
                }
            };
            
            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Enter' && overlay.classList.contains('active')) {
                    overlay.classList.remove('active');
                    document.removeEventListener('keydown', handler);
                    resolve(true);
                }
                if (e.key === 'Escape' && overlay.classList.contains('active')) {
                    overlay.classList.remove('active');
                    document.removeEventListener('keydown', handler);
                    resolve(false);
                }
            });
        });
    }

    // Show a custom prompt dialog
    function showPrompt(message, title = 'Dark Realm', icon = '📝', defaultValue = '', placeholder = 'Enter value...') {
        return new Promise((resolve) => {
            const overlay = createModal();
            document.getElementById('modalIcon').textContent = icon;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            
            const inputContainer = document.getElementById('modalInputContainer');
            inputContainer.style.display = 'block';
            const input = document.getElementById('modalInput');
            input.value = defaultValue;
            input.placeholder = placeholder;
            
            const confirmBtn = document.getElementById('modalConfirmBtn');
            const cancelBtn = document.getElementById('modalCancelBtn');
            
            confirmBtn.textContent = 'OK';
            confirmBtn.className = 'modal-btn primary';
            cancelBtn.textContent = 'Cancel';
            cancelBtn.style.display = 'inline-block';
            cancelBtn.className = 'modal-btn';
            
            overlay.classList.add('active');
            
            setTimeout(() => input.focus(), 100);
            
            confirmBtn.onclick = function() {
                overlay.classList.remove('active');
                resolve(input.value);
            };
            
            cancelBtn.onclick = function() {
                overlay.classList.remove('active');
                resolve(null);
            };
            
            overlay.onclick = function(e) {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                    resolve(null);
                }
            };
            
            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Enter' && overlay.classList.contains('active')) {
                    overlay.classList.remove('active');
                    document.removeEventListener('keydown', handler);
                    resolve(input.value);
                }
                if (e.key === 'Escape' && overlay.classList.contains('active')) {
                    overlay.classList.remove('active');
                    document.removeEventListener('keydown', handler);
                    resolve(null);
                }
            });
        });
    }

    // Expose to global
    window.modal = {
        alert: showAlert,
        confirm: showConfirm,
        prompt: showPrompt
    };

})();