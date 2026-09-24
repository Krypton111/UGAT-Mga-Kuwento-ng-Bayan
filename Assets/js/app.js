// ============================================================
// ᜂᜄᜆ᜔: MGA KUWENTO NG BAYAN – APPLICATION SCRIPT
// ============================================================
(function () {
    'use strict';

    /* ---------- MUSIC ---------- */
    function initMusic() {
        const audio = document.getElementById('interface-music');
        const toggle = document.getElementById('music-toggle');
        const slider = document.getElementById('volume-slider');
        if (!audio || !toggle || !slider) return;

        let playing = false;
        const saved = localStorage.getItem('musicVolume');
        const vol = saved !== null ? parseFloat(saved) : 0.35;
        audio.volume = vol;
        slider.value = vol;

        const setIcon = on => {
            toggle.textContent = on ? '🔊' : '🔇';
            toggle.classList.toggle('muted', !on);
            playing = on;
        };

        toggle.addEventListener('click', () => {
            if (audio.paused) audio.play().then(() => setIcon(true)).catch(() => setIcon(false));
            else { audio.pause(); setIcon(false); }
        });

        slider.addEventListener('input', function () {
            const v = parseFloat(this.value);
            audio.volume = v;
            localStorage.setItem('musicVolume', v);
            if (v === 0) { toggle.textContent = '🔇'; toggle.classList.add('muted'); }
            else if (playing) { toggle.textContent = '🔊'; toggle.classList.remove('muted'); }
        });

        audio.play().then(() => setIcon(true)).catch(() => {
            setIcon(false);
            const once = () => {
                audio.play().then(() => setIcon(true)).catch(() => {});
                document.removeEventListener('click', once);
                document.removeEventListener('touchstart', once);
            };
            document.addEventListener('click', once);
            document.addEventListener('touchstart', once);
        });

        audio.addEventListener('play',  () => setIcon(true));
        audio.addEventListener('pause', () => setIcon(false));
    }

    /* ---------- PASSWORD TOGGLE ---------- */
    function initPasswordToggles() {
        document.querySelectorAll('.toggle-password').forEach(btn => {
            const input = document.getElementById(btn.dataset.target);
            if (!input) return;
            btn.addEventListener('click', () => {
                const isPw = input.type === 'password';
                input.type = isPw ? 'text' : 'password';
                btn.textContent = isPw ? '🙈' : '👁';
            });
        });
    }

    /* ---------- WIFI INDICATOR ---------- */
    function initWifi() {
        if (document.getElementById('wifi-indicator')) return;
        const el = document.createElement('div');
        el.id = 'wifi-indicator';
        el.innerHTML = '<span class="wifi-dot"></span><span class="wifi-text">Checking…</span>';
        document.body.appendChild(el);

        const check = () => {
            const t = el.querySelector('.wifi-text');
            if (navigator.onLine) { el.className = 'online';  t.textContent = 'Online'; }
            else                  { el.className = 'offline'; t.textContent = 'No Connection'; }
        };
        check();
        window.addEventListener('online',  check);
        window.addEventListener('offline', check);
        setInterval(check, 30000);
    }

    /* ---------- MODAL ---------- */
    function ensureModal() {
        if (document.getElementById('customModalOverlay')) return;
        const o = document.createElement('div');
        o.id = 'customModalOverlay';
        o.className = 'custom-modal-overlay';
        o.innerHTML = `
            <div class="custom-modal">
                <h2 id="modalTitle">Kuwento</h2>
                <p id="modalMessage"></p>
                <div class="modal-buttons">
                    <button class="btn" id="modalOK">OK</button>
                    <button class="btn" id="modalCancel" style="display:none;">Cancel</button>
                </div>
            </div>`;
        document.body.appendChild(o);
    }

    const alertModal = (msg, title = 'Kuwento') => new Promise(res => {
        ensureModal();
        const o = document.getElementById('customModalOverlay');
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalMessage').textContent = msg;
        const ok = document.getElementById('modalOK');
        const c  = document.getElementById('modalCancel');
        ok.textContent = 'OK'; c.style.display = 'none';
        o.classList.add('active');
        ok.onclick = () => { o.classList.remove('active'); res(true); };
        o.onclick = e => { if (e.target === o) { o.classList.remove('active'); res(true); } };
    });

    const confirmModal = (msg, title = 'Confirm') => new Promise(res => {
        ensureModal();
        const o = document.getElementById('customModalOverlay');
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalMessage').textContent = msg;
        const ok = document.getElementById('modalOK');
        const c  = document.getElementById('modalCancel');
        ok.textContent = 'Confirm'; c.style.display = 'inline-block'; c.textContent = 'Cancel';
        o.classList.add('active');
        ok.onclick = () => { o.classList.remove('active'); res(true); };
        c.onclick  = () => { o.classList.remove('active'); res(false); };
        o.onclick  = e => { if (e.target === o) { o.classList.remove('active'); res(false); } };
    });

    /* ---------- LOADING ---------- */
    function ensureLoading() {
        if (document.getElementById('loadingOverlay')) return;
        const o = document.createElement('div');
        o.id = 'loadingOverlay';
        o.className = 'loading-overlay';
        o.innerHTML = `
            <div class="loading-box">
                <div class="loading-skull">📜</div>
                <div class="loading-title">Kuwento</div>
                <div class="loading-text" id="loadingText">Sending the letter…</div>
                <div class="loading-dots"><span></span><span></span><span></span></div>
                <div class="loading-subtitle">Please wait a moment</div>
            </div>
        `;
        document.body.appendChild(o);
    }

    /* ---------- TOAST ---------- */
    function toast(msg, type = 'success') {
        const t = document.createElement('div');
        t.className = 'toast ' + type;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 4000);
    }

    window.ui = { alert: alertModal, confirm: confirmModal, toast: toast };
    window.Loading = {
        show: (text) => {
            ensureLoading();
            const t = document.getElementById('loadingText');
            if (text) t.textContent = text;
            document.getElementById('loadingOverlay').classList.add('active');
        },
        hide: () => {
            ensureLoading();
            document.getElementById('loadingOverlay').classList.remove('active');
        },
        showResult: (ok, msg) => {
            ensureLoading();
            document.getElementById('loadingOverlay').classList.remove('active');
            alertModal(msg, ok ? 'Success' : 'Error');
        }
    };

    /* ---------- INIT ---------- */
    function init() {
        initMusic();
        initPasswordToggles();
        initWifi();
        ensureLoading();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else { init(); }
})();