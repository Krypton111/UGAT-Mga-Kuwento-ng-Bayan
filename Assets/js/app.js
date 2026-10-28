// ============================================================
// ᜂᜄᜆ᜔: MGA KUWENTO NG BAYAN – APPLICATION SCRIPT
// ============================================================
(function () {
    'use strict';

    /* ---------- SITE-WIDE SYSTEM MUSIC ---------- */
    function initMusic() {
        let audio = document.getElementById('interface-music') ||
                    document.getElementById('settings-music');

        // Pages without their own audio element still receive the same soundtrack.
        if (!audio) {
            audio = document.createElement('audio');
            audio.id = 'interface-music';
            audio.loop = true;
            audio.preload = 'auto';
            audio.setAttribute('aria-hidden', 'true');
            audio.style.display = 'none';
            document.body.appendChild(audio);
        }

        const musicUrl = new URL('Assets/mp3/system%20music.mp3', document.baseURI).href;

        // Use one track throughout the PHP site instead of changing tracks per page.
        const source = audio.querySelector('source');
        if (source) {
            if (source.getAttribute('src') !== 'Assets/mp3/system%20music.mp3') {
                source.src = 'Assets/mp3/system%20music.mp3';
                audio.load();
            }
        } else if (!audio.currentSrc || !audio.currentSrc.includes('system%20music.mp3')) {
            audio.src = musicUrl;
            audio.load();
        }

        const saved = localStorage.getItem('musicVolume');
        const volume = saved !== null && Number.isFinite(Number(saved))
            ? Math.min(1, Math.max(0, Number(saved))) : 0.35;
        audio.volume = volume;
        audio.muted = false;
        audio.setAttribute('playsinline', '');

        let playing = false;
        const setIcon = on => { playing = on; };

        // Save the playback position between normal page navigations.
        // sessionStorage keeps it scoped to this browser tab/session.
        let skipRestoreForGameReturn = false;
        try {
            skipRestoreForGameReturn = sessionStorage.getItem('systemMusicGameLaunch') === '1';
            if (skipRestoreForGameReturn) sessionStorage.removeItem('systemMusicGameLaunch');
        } catch (_) {}

        const restorePosition = () => {
            if (skipRestoreForGameReturn) return;
            try {
                const savedTime = Number(sessionStorage.getItem('systemMusicTime'));
                if (Number.isFinite(savedTime) && savedTime >= 0 && Number.isFinite(audio.duration)) {
                    audio.currentTime = savedTime % audio.duration;
                }
            } catch (_) {}
        };

        audio.addEventListener('loadedmetadata', restorePosition, { once: true });
        if (audio.readyState >= 1) restorePosition();

        const savePosition = () => {
            if (Number.isFinite(audio.currentTime)) {
                try { sessionStorage.setItem('systemMusicTime', String(audio.currentTime)); } catch (_) {}
            }
        };
        audio.addEventListener('timeupdate', savePosition);
        window.addEventListener('pagehide', savePosition);

        audio.addEventListener('play', () => setIcon(true));
        audio.addEventListener('pause', () => setIcon(false));
        const tryStartMusic = () => {
            if (!audio || !audio.paused) return;
            audio.play().then(() => setIcon(true)).catch(() => setIcon(false));
        };

        // Browsers may block autoplay until a real user gesture. Retry on the
        // first pointer/keyboard interaction, independently of the music toggle.
        audio.play().then(() => setIcon(true)).catch(() => {
            setIcon(false);
            const unlockMusic = () => {
                tryStartMusic();
                document.removeEventListener('pointerdown', unlockMusic, true);
                document.removeEventListener('keydown', unlockMusic, true);
                document.removeEventListener('click', unlockMusic, true);
            };
            document.addEventListener('pointerdown', unlockMusic, true);
            document.addEventListener('keydown', unlockMusic, true);
            document.addEventListener('click', unlockMusic, true);
        });

        // Fade the system track out before any game/export page begins loading.
        document.addEventListener('click', function (event) {
            const link = event.target.closest && event.target.closest('a[href]');
            if (!link || event.button !== 0 || event.metaKey || event.ctrlKey ||
                event.shiftKey || event.altKey || link.target === '_blank') return;

            const destination = new URL(link.href, window.location.href);
            if (destination.origin !== window.location.origin ||
                !/\/Assets\/games\//i.test(destination.pathname)) return;

            try { sessionStorage.setItem('systemMusicGameLaunch', '1'); } catch (_) {}
            const startVolume = audio.volume;
            const fadeDuration = 900;
            const startTime = performance.now();
            const fade = now => {
                const progress = Math.min(1, (now - startTime) / fadeDuration);
                audio.volume = startVolume * (1 - progress);
                if (progress < 1) requestAnimationFrame(fade);
            };
            requestAnimationFrame(fade);
        }, true);

        window.musicPlayer = {
            audio: audio,
            play: () => audio.play().catch(() => {}),
            pause: () => audio.pause(),
            toggle: () => audio.paused ? audio.play().catch(() => {}) : audio.pause(),
            setVolume: value => {
                audio.volume = Math.min(1, Math.max(0, Number(value)));
                localStorage.setItem('musicVolume', String(audio.volume));
            }
        };
    }

    /* ---------- GLOBAL INTERACTION SOUNDS ---------- */
    function initInteractionSounds() {
        let audioContext = null;
        let lastTypingSound = 0;

        function getContext() {
            if (!audioContext) {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioContextClass) return null;
                audioContext = new AudioContextClass();
            }
            if (audioContext.state === 'suspended') audioContext.resume().catch(() => {});
            return audioContext;
        }

        function playTone(type) {
            const ctx = getContext();
            if (!ctx) return;

            const savedSoundVolume = localStorage.getItem('soundVolume');
            const storedVolume = savedSoundVolume === null ? 0.8 : Number(savedSoundVolume);
            const volume = Number.isFinite(storedVolume)
                ? Math.min(1, Math.max(0, storedVolume)) : 0.8;
            if (volume <= 0) return;

            const now = ctx.currentTime;
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();

            if (type === 'hover') {
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(560, now);
                oscillator.frequency.exponentialRampToValueAtTime(760, now + 0.045);
                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.exponentialRampToValueAtTime(0.07 * volume, now + 0.008);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.065);
            } else if (type === 'click') {
                oscillator.type = 'triangle';
                oscillator.frequency.setValueAtTime(210, now);
                oscillator.frequency.exponentialRampToValueAtTime(105, now + 0.075);
                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.exponentialRampToValueAtTime(0.22 * volume, now + 0.006);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.095);
            } else {
                oscillator.type = 'square';
                oscillator.frequency.setValueAtTime(920, now);
                oscillator.frequency.exponentialRampToValueAtTime(660, now + 0.018);
                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.exponentialRampToValueAtTime(0.045 * volume, now + 0.002);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.026);
            }

            oscillator.connect(gain);
            gain.connect(ctx.destination);
            oscillator.start(now);
            oscillator.stop(now + (type === 'typing' ? 0.03 : type === 'hover' ? 0.07 : 0.1));
        }

        const interactiveSelector = [
            'button', 'a[href]', '[role="button"]', 'input[type="button"]',
            'input[type="submit"]', 'input[type="reset"]', 'select', 'summary',
            '[role="tab"]', '[role="menuitem"]'
        ].join(',');

        // A light cue when entering a button or other interactive control.
        document.addEventListener('pointerover', event => {
            const target = event.target.closest && event.target.closest(interactiveSelector);
            if (!target || (event.relatedTarget && target.contains(event.relatedTarget))) return;
            if (target.matches(':disabled, [aria-disabled="true"]')) return;
            playTone('hover');
        }, true);

        // Capture clicks early so the click cue begins before navigation or UI updates.
        document.addEventListener('click', event => {
            const target = event.target.closest && event.target.closest(interactiveSelector);
            if (!target || target.matches(':disabled, [aria-disabled="true"]')) return;
            playTone('click');
        }, true);

        // Typing feedback is throttled so holding a key or rapid input events do not
        // create an unpleasant continuous tone. No typed text is read or stored.
        document.addEventListener('input', event => {
            const target = event.target;
            if (!target.matches('input:not([type="range"]):not([type="checkbox"]):not([type="radio"]):not([type="button"]):not([type="submit"]):not([type="reset"]), textarea, [contenteditable="true"]')) return;
            const now = performance.now();
            if (now - lastTypingSound < 38) return;
            lastTypingSound = now;
            playTone('typing');
        }, true);
    }

    /* ---------- PERSISTENT SETTINGS ---------- */
    function initPersistentSettings() {
        const readNumber = (key, fallback, min, max) => {
            const raw = localStorage.getItem(key);
            const parsed = raw === null ? fallback : Number(raw);
            return Number.isFinite(parsed) ? Math.min(max, Math.max(min, parsed)) : fallback;
        };

        const brightness = readNumber('settingsBrightness', 1, 0.35, 1);
        const soundVolume = readNumber('soundVolume', 0.8, 0, 1);
        const musicVolume = readNumber('musicVolume', 0.35, 0, 1);

        // Use a dark overlay instead of body filters, which can break fixed UI.
        let overlay = document.getElementById('saved-brightness-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'saved-brightness-overlay';
            overlay.setAttribute('aria-hidden', 'true');
            Object.assign(overlay.style, {
                position: 'fixed', inset: '0', background: '#000',
                pointerEvents: 'none', zIndex: '99990',
                transition: 'opacity 100ms linear'
            });
            document.body.appendChild(overlay);
        }
        overlay.style.opacity = String(1 - brightness);

        // Apply saved volumes to existing media and media created later.
        const applyMediaVolume = media => {
            media.volume = (media.id === 'interface-music' || media.id === 'settings-music')
                ? musicVolume : soundVolume;
        };
        document.querySelectorAll('audio, video').forEach(applyMediaVolume);

        if (!window.__persistentVolumeObserver) {
            window.__persistentVolumeObserver = new MutationObserver(records => {
                records.forEach(record => record.addedNodes.forEach(node => {
                    if (node.nodeType !== 1) return;
                    if (node.matches && node.matches('audio, video')) applyMediaVolume(node);
                    if (node.querySelectorAll) node.querySelectorAll('audio, video').forEach(applyMediaVolume);
                }));
            });
            window.__persistentVolumeObserver.observe(document.documentElement, { childList: true, subtree: true });
        }
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

    /* ---------- WIFI / INTERNET CONNECTIVITY INDICATOR ---------- */
    function initWifi() {
        if (document.getElementById('wifi-indicator')) return;

        const el = document.createElement('div');
        el.id = 'wifi-indicator';
        el.innerHTML = '<span class="wifi-dot"></span><span class="wifi-text">Checking...</span>';
        document.body.appendChild(el);

        let checkInProgress = false;
        let lastCheckId = 0;

        const setStatus = (online, label) => {
            const t = el.querySelector('.wifi-text');
            el.className = online ? 'online' : 'offline';
            if (t) t.textContent = label || (online ? 'Online' : 'No Connection');
        };

        const check = async () => {
            const checkId = ++lastCheckId;

            // navigator.onLine only reports the browser's network-interface state.
            // It can still be true when Wi-Fi or the internet is unavailable, so
            // verify that an external connectivity-check endpoint can be reached.
            if (!navigator.onLine) {
                setStatus(false, 'No Connection');
                return;
            }
            if (checkInProgress) return;
            checkInProgress = true;
            setStatus(false, 'Checking...');

            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 5000);
            try {
                await fetch('https://www.gstatic.com/generate_204?check=' + Date.now(), {
                    method: 'GET',
                    mode: 'no-cors',
                    cache: 'no-store',
                    signal: controller.signal
                });
                if (checkId === lastCheckId && navigator.onLine) {
                    setStatus(true, 'Online');
                }
            } catch (_) {
                if (checkId === lastCheckId) setStatus(false, 'No Connection');
            } finally {
                clearTimeout(timeout);
                checkInProgress = false;
            }
        };

        check();
        window.addEventListener('online', check);
        window.addEventListener('offline', () => {
            lastCheckId++;
            setStatus(false, 'No Connection');
        });
        setInterval(check, 15000);
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
        initPersistentSettings();
        initMusic();
        initInteractionSounds();
        initPasswordToggles();
        initWifi();
        ensureLoading();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else { init(); }
})();