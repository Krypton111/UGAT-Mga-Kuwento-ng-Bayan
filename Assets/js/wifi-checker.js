// assets/js/wifi-checker.js
(function() {
    'use strict';

    function createWifiIndicator() {
        let indicator = document.getElementById('wifi-indicator');
        if (indicator) return indicator;

        indicator = document.createElement('div');
        indicator.id = 'wifi-indicator';
        indicator.innerHTML = '<span class="wifi-dot"></span><span class="wifi-text">Checking...</span>';
        document.body.appendChild(indicator);
        return indicator;
    }

    const indicator = createWifiIndicator();
    let checkInProgress = false;
    let latestCheckId = 0;

    function updateIndicator(online) {
        indicator.className = online ? 'online' : 'offline';
        const text = indicator.querySelector('.wifi-text');
        if (text) text.textContent = online ? 'Online' : 'Offline';
    }

    async function checkConnection() {
        if (!navigator.onLine) {
            latestCheckId++;
            updateIndicator(false);
            return;
        }
        if (checkInProgress) return;

        checkInProgress = true;
        const checkId = ++latestCheckId;
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000);

        try {
            await fetch('https://www.gstatic.com/generate_204?check=' + Date.now(), {
                method: 'GET',
                mode: 'no-cors',
                cache: 'no-store',
                signal: controller.signal
            });
            if (checkId === latestCheckId) updateIndicator(navigator.onLine);
        } catch (_) {
            if (checkId === latestCheckId) updateIndicator(false);
        } finally {
            clearTimeout(timeoutId);
            checkInProgress = false;
            if (!navigator.onLine) updateIndicator(false);
        }
    }

    updateIndicator(false);
    checkConnection();
    window.addEventListener('online', checkConnection);
    window.addEventListener('offline', () => {
        latestCheckId++;
        updateIndicator(false);
    });
    setInterval(checkConnection, 5000);
})();
