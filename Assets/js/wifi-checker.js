// assets/js/wifi-checker.js
(function() {
    'use strict';

    function createWifiIndicator() {
        const existing = document.getElementById('wifi-indicator');
        if (existing) return existing;

        const indicator = document.createElement('div');
        indicator.id = 'wifi-indicator';
        indicator.innerHTML = '<span class="wifi-dot"></span><span class="wifi-text">Checking...</span>';
        document.body.appendChild(indicator);
        return indicator;
    }

    function updateIndicator(online, text) {
        const indicator = document.getElementById('wifi-indicator');
        if (!indicator) return;

        const textElement = indicator.querySelector('.wifi-text');
        if (textElement) textElement.textContent = text || (online ? 'Online' : 'No Connection');

        indicator.className = online ? 'online' : 'offline';
    }

    let checkInProgress = false;
    let latestCheckId = 0;

    async function checkConnection() {
        const checkId = ++latestCheckId;

        // navigator.onLine can remain true when the device has no usable internet.
        if (!navigator.onLine) {
            updateIndicator(false, 'No Connection');
            return;
        }
        if (checkInProgress) return;

        checkInProgress = true;
        updateIndicator(false, 'Checking...');

        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 5000);

        try {
            // A no-cors request is used only as a reachability probe; the response
            // body is not read. A failed request or timeout means no connection.
            await fetch('https://www.gstatic.com/generate_204?check=' + Date.now(), {
                method: 'GET',
                mode: 'no-cors',
                cache: 'no-store',
                signal: controller.signal
            });

            if (checkId === latestCheckId && navigator.onLine) {
                updateIndicator(true, 'Online');
            }
        } catch (_) {
            if (checkId === latestCheckId) updateIndicator(false, 'No Connection');
        } finally {
            clearTimeout(timeout);
            checkInProgress = false;
        }
    }

    createWifiIndicator();
    checkConnection();

    window.addEventListener('online', checkConnection);
    window.addEventListener('offline', function() {
        latestCheckId++;
        updateIndicator(false, 'No Connection');
    });

    // Re-check periodically in case connectivity changes without browser events.
    setInterval(checkConnection, 15000);
})();
