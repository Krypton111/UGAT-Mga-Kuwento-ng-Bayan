// assets/js/wifi-checker.js
(function() {
    'use strict';

    function createWifiIndicator() {
        // Check if indicator already exists
        if (document.getElementById('wifi-indicator')) {
            return document.getElementById('wifi-indicator');
        }
        
        const indicator = document.createElement('div');
        indicator.id = 'wifi-indicator';
        indicator.innerHTML = `
            <span class="wifi-dot"></span>
            <span class="wifi-text">Checking...</span>
        `;
        document.body.appendChild(indicator);
        return indicator;
    }

    function updateIndicator(status, text) {
        const indicator = document.getElementById('wifi-indicator');
        if (!indicator) return;
        
        const textElement = indicator.querySelector('.wifi-text');
        if (textElement) {
            textElement.textContent = text;
        }
        
        // Remove all classes
        indicator.className = '';
        
        if (status === 'online') {
            indicator.classList.add('online');
        } else if (status === 'offline') {
            indicator.classList.add('offline');
        }
    }

    function checkConnection() {
        const indicator = document.getElementById('wifi-indicator');
        if (!indicator) return;

        if (navigator.onLine) {
            updateIndicator('online', 'Online');
        } else {
            updateIndicator('offline', 'No Connection');
        }
    }

    // Initialize
    const indicator = createWifiIndicator();
    
    // Wait for page to fully load before checking
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            checkConnection();
        });
    } else {
        checkConnection();
    }

    // Listen for connection changes
    window.addEventListener('online', function() {
        checkConnection();
        // Show online status for 3 seconds then fade
        const indicator = document.getElementById('wifi-indicator');
        if (indicator) {
            indicator.style.opacity = '1';
            setTimeout(() => {
                indicator.style.opacity = '0.7';
            }, 3000);
        }
    });
    
    window.addEventListener('offline', function() {
        checkConnection();
        const indicator = document.getElementById('wifi-indicator');
        if (indicator) {
            indicator.style.opacity = '1';
        }
    });

    // Check periodically (every 30 seconds)
    setInterval(checkConnection, 30000);
})();