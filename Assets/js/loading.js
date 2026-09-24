// assets/js/loading.js
(function() {
    'use strict';

    // ===== INJECT CSS STYLES =====
    function injectStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(10, 5, 8, 0.92);
                backdrop-filter: blur(8px);
                z-index: 99999;
                display: none;
                justify-content: center;
                align-items: center;
                flex-direction: column;
                animation: fadeInOverlay 0.3s ease forwards;
            }
            .loading-overlay.active { display: flex; }
            @keyframes fadeInOverlay {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            .loading-container {
                text-align: center;
                animation: pulseContainer 2s ease-in-out infinite;
            }
            @keyframes pulseContainer {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.02); }
            }
            .loading-skull {
                font-size: 72px;
                animation: spinSkull 3s linear infinite, floatSkullLoading 2s ease-in-out infinite;
                display: inline-block;
                filter: drop-shadow(0 0 40px rgba(180, 20, 40, 0.9));
            }
            @keyframes spinSkull {
                0% { transform: rotate(0deg) scale(1); }
                25% { transform: rotate(5deg) scale(1.05); }
                75% { transform: rotate(-5deg) scale(0.95); }
                100% { transform: rotate(0deg) scale(1); }
            }
            @keyframes floatSkullLoading {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-15px); }
            }
            .loading-text {
                color: #d4af37;
                font-family: 'Press Start 2P', cursive;
                font-size: 18px;
                margin-top: 30px;
                text-shadow: 0 0 20px #8b0000, 0 0 40px #4a0000;
                animation: glowPulseText 1.5s ease-in-out infinite;
            }
            @keyframes glowPulseText {
                0%, 100% { text-shadow: 0 0 20px #8b0000, 0 0 40px #4a0000; }
                50% { text-shadow: 0 0 40px #b22222, 0 0 80px #8b0000, 0 0 120px #4a0000; }
            }
            .loading-dots {
                display: inline-block;
                margin-top: 20px;
            }
            .loading-dots span {
                display: inline-block;
                width: 12px;
                height: 12px;
                border-radius: 50%;
                background: #d4af37;
                margin: 0 5px;
                animation: dotBounce 1.4s ease-in-out infinite;
            }
            .loading-dots span:nth-child(1) { animation-delay: 0s; }
            .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
            .loading-dots span:nth-child(3) { animation-delay: 0.4s; }
            @keyframes dotBounce {
                0%, 80%, 100% { transform: scale(0); opacity: 0.3; }
                40% { transform: scale(1); opacity: 1; }
            }
            .loading-subtitle {
                color: #8b5a6b;
                font-family: 'Press Start 2P', cursive;
                font-size: 11px;
                margin-top: 15px;
                opacity: 0.7;
            }
            .loading-result {
                margin-top: 30px;
                padding: 15px 30px;
                border: 3px solid;
                font-family: 'Press Start 2P', cursive;
                font-size: 14px;
                display: none;
                animation: slideInResult 0.5s ease forwards;
            }
            .loading-result.success {
                display: block;
                border-color: #2a8a2a;
                color: #8aff8a;
                background: rgba(10, 42, 10, 0.8);
                box-shadow: 0 0 40px rgba(42, 138, 42, 0.3);
            }
            .loading-result.error {
                display: block;
                border-color: #8b0000;
                color: #ff6b6b;
                background: rgba(42, 10, 10, 0.8);
                box-shadow: 0 0 40px rgba(139, 0, 0, 0.3);
            }
            @keyframes slideInResult {
                from { opacity: 0; transform: translateY(20px) scale(0.95); }
                to { opacity: 1; transform: translateY(0) scale(1); }
            }
            .loading-close-btn {
                margin-top: 20px;
                padding: 12px 30px;
                background: transparent;
                border: 3px solid #d4af37;
                color: #d4af37;
                font-family: 'Press Start 2P', cursive;
                font-size: 12px;
                cursor: pointer;
                transition: all 0.3s ease;
                display: none;
            }
            .loading-close-btn:hover {
                background: #d4af37;
                color: #0a0508;
                transform: scale(1.05);
                box-shadow: 0 0 30px rgba(212, 175, 55, 0.4);
            }
            .loading-close-btn.show {
                display: inline-block;
            }
        `;
        document.head.appendChild(style);
    }

    // ===== CREATE LOADING OVERLAY =====
    function createLoadingOverlay() {
        if (document.getElementById('loadingOverlay')) {
            return;
        }
        
        // Inject styles first
        injectStyles();
        
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.id = 'loadingOverlay';
        
        overlay.innerHTML = `
            <div class="loading-container">
                <div class="loading-skull">💀</div>
                <div class="loading-text">Summoning Darkness</div>
                <div class="loading-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="loading-subtitle">Please wait...</div>
                <div class="loading-result" id="loadingResult"></div>
                <button class="loading-close-btn" id="loadingCloseBtn">⚔️ CONTINUE ⚔️</button>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        const closeBtn = document.getElementById('loadingCloseBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                hideLoading();
            });
        }
    }

    // ===== SHOW LOADING =====
    function showLoading(message, subtitle) {
        let overlay = document.getElementById('loadingOverlay');
        if (!overlay) {
            createLoadingOverlay();
            overlay = document.getElementById('loadingOverlay');
            if (!overlay) {
                setTimeout(function() {
                    showLoading(message, subtitle);
                }, 50);
                return;
            }
        }
        
        const result = document.getElementById('loadingResult');
        const closeBtn = document.getElementById('loadingCloseBtn');
        const loadingText = overlay.querySelector('.loading-text');
        const loadingSubtitle = overlay.querySelector('.loading-subtitle');
        
        if (result) {
            result.className = 'loading-result';
            result.style.display = 'none';
        }
        if (closeBtn) {
            closeBtn.className = 'loading-close-btn';
        }
        if (loadingText) {
            loadingText.textContent = message || 'Summoning Darkness';
        }
        if (loadingSubtitle) {
            loadingSubtitle.textContent = subtitle || 'Please wait...';
        }
        
        overlay.classList.add('active');
    }

    // ===== SHOW LOADING RESULT =====
    function showLoadingResult(success, message, redirectUrl) {
        const overlay = document.getElementById('loadingOverlay');
        if (!overlay) return;
        
        const result = document.getElementById('loadingResult');
        const closeBtn = document.getElementById('loadingCloseBtn');
        const loadingText = overlay.querySelector('.loading-text');
        const loadingSubtitle = overlay.querySelector('.loading-subtitle');
        
        if (loadingText) {
            loadingText.textContent = success ? '✅ Success!' : '❌ Error!';
        }
        if (loadingSubtitle) {
            loadingSubtitle.textContent = '';
        }
        
        if (result) {
            result.className = `loading-result ${success ? 'success' : 'error'}`;
            result.innerHTML = message;
            result.style.display = 'block';
        }
        
        if (closeBtn) {
            closeBtn.className = 'loading-close-btn show';
        }
        
        if (success && redirectUrl) {
            setTimeout(function() {
                window.location.href = redirectUrl;
            }, 3000);
        }
    }

    // ===== HIDE LOADING =====
    function hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.remove('active');
        }
    }

    // ===== INTERCEPT FORM SUBMISSIONS =====
    function initLoadingIntercept() {
        document.addEventListener('submit', function(e) {
            const form = e.target;
            
            if (!form.hasAttribute('data-loading')) {
                return;
            }
            
            if (form.querySelector('input[name="verify_code"]')) {
                return;
            }
            
            if (form.querySelector('input[name="verification_code"]')) {
                return;
            }
            
            const message = form.dataset.loadingMessage || 'Summoning Darkness';
            const subtitle = form.dataset.loadingSubtitle || 'Please wait...';
            showLoading(message, subtitle);
        });
    }

    // ===== EXPOSE GLOBALLY =====
    window.Loading = {
        show: showLoading,
        showResult: showLoadingResult,
        hide: hideLoading,
        init: initLoadingIntercept
    };

    // ===== INITIALIZE =====
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            createLoadingOverlay();
            initLoadingIntercept();
        });
    } else {
        createLoadingOverlay();
        initLoadingIntercept();
    }

})();