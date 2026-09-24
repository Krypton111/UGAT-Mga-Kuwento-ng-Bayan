// ============================================================
// Subtle floating dust particles for Kuwento ng Bayan
// ============================================================
(function () {
    'use strict';
    const CONFIG = { count: 28, minSize: 1, maxSize: 3, minDur: 15, maxDur: 35, drift: 60 };

    function init() {
        const container = document.createElement('div');
        container.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:0;overflow:hidden;';
        document.body.appendChild(container);

        for (let i = 0; i < CONFIG.count; i++) {
            const p = document.createElement('div');
            const size  = Math.random() * (CONFIG.maxSize - CONFIG.minSize) + CONFIG.minSize;
            const dur   = Math.random() * (CONFIG.maxDur - CONFIG.minDur) + CONFIG.minDur;
            const drift = (Math.random() * 2 - 1) * CONFIG.drift;
            p.style.cssText = `
                position:absolute;
                width:${size}px;height:${size}px;border-radius:50%;
                background:rgba(244,233,216,${Math.random() * 0.4 + 0.2});
                box-shadow:0 0 6px rgba(244,233,216,0.5);
                left:${Math.random() * 100}%;
                animation:floatDust ${dur}s linear ${Math.random() * dur}s infinite;
                --drift:${drift}px;`;
            container.appendChild(p);
        }

        const style = document.createElement('style');
        style.textContent = `
            @keyframes floatDust {
                0%   { transform: translateY(100vh) translateX(0) scale(0); opacity: 0; }
                10%  { opacity: .6; }
                90%  { opacity: .6; }
                100% { transform: translateY(-10vh) translateX(var(--drift)) scale(1); opacity: 0; }
            }`;
        document.head.appendChild(style);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else { init(); }
})();