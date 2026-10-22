<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ᜂᜄᜆ᜔: Kuwento ng Bayan — Settings</title>
    <link rel="icon" type="image/x-icon" href="Assets/img/favicon.ico">
    <link rel="stylesheet" href="Assets/css/style.css">
    <style>
        html, body {
            width: 100%;
            height: 100%;
            min-height: 100%;
            margin: 0;
            overflow: hidden;
        }

        .settings-stage {
            position: fixed;
            inset: 0;
            overflow: hidden;
            filter: brightness(var(--settings-brightness, 1));
        }

        .settings-stage .bg-image,
        .settings-stage .bg-overlay,
        .settings-stage .page-fog {
            position: fixed;
        }

        .settings-art-wrap {
            position: absolute;
            top: 50%;
            left: 50%;
            width: min(94vw, 1664px, calc((100vh - 24px) * 1.733333));
            aspect-ratio: 1664 / 960;
            transform: translate(-50%, -50%);
            opacity: 0;
            animation: settingsEnter 0.85s cubic-bezier(.22,1,.36,1) 0.08s forwards;
            z-index: 4;
        }

        @keyframes settingsEnter {
            from { opacity: 0; transform: translate(calc(-50% + 90px), -50%); }
            to   { opacity: 1; transform: translate(-50%, -50%); }
        }

        .settings-menu-art {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: fill;
            user-select: none;
            pointer-events: none;
        }

        .settings-slider {
            position: absolute;
            left: 37.05%;
            width: 59.45%;
            height: 7.5%;
            margin: 0;
            padding: 0;
            transform: translateY(-50%);
            appearance: none;
            -webkit-appearance: none;
            background: transparent;
            cursor: pointer;
            z-index: 5;
            touch-action: pan-y;
        }

        .settings-slider:focus-visible,
        .fullscreen-toggle:focus-visible,
        .back-button:focus-visible {
            outline: 3px solid #f4e9d8;
            outline-offset: 4px;
            border-radius: 8px;
        }

        .settings-slider::-webkit-slider-runnable-track {
            height: 2px;
            background: transparent;
        }

        .settings-slider::-moz-range-track {
            height: 2px;
            background: transparent;
        }

        .settings-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: clamp(42px, 4.6vw, 76px);
            height: clamp(42px, 4.6vw, 76px);
            margin-top: calc(clamp(42px, 4.6vw, 76px) / -2);
            border: 0;
            border-radius: 50%;
            background: url("Assets/img/icon%20slider.png") center / contain no-repeat;
            cursor: grab;
        }

        .settings-slider::-moz-range-thumb {
            width: clamp(42px, 4.6vw, 76px);
            height: clamp(42px, 4.6vw, 76px);
            border: 0;
            border-radius: 50%;
            background: url("Assets/img/icon%20slider.png") center / contain no-repeat;
            cursor: grab;
        }

        .settings-slider:active::-webkit-slider-thumb,
        .settings-slider:active::-moz-range-thumb {
            cursor: grabbing;
        }

        #brightness-slider { top: 19.8%; }
        #sound-slider      { top: 60.25%; }
        #music-slider      { top: 76.55%; }

        .fullscreen-toggle {
            position: absolute;
            left: 62.6%;
            top: 28.7%;
            width: 8.2%;
            height: 13.2%;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: pointer;
            z-index: 6;
        }

        .fullscreen-toggle img {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        .fullscreen-toggle[aria-pressed="false"] img {
            display: none;
        }

        .back-button {
            position: fixed;
            left: 18px;
            bottom: 12px;
            width: clamp(76px, 9vw, 138px);
            height: auto;
            z-index: 20;
            opacity: 0;
            cursor: pointer;
            user-select: none;
            animation: backEnter 0.7s cubic-bezier(.22,1,.36,1) 0.25s forwards;
            transition: filter .2s ease, transform .2s ease;
        }

        .back-button:hover {
            filter: brightness(1.12) drop-shadow(0 4px 8px rgba(0,0,0,.25));
            transform: translateX(-4px);
        }

        @keyframes backEnter {
            from { opacity: 0; transform: translateX(-35px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        body.settings-leaving .settings-art-wrap {
            animation: settingsExit 0.65s cubic-bezier(.55,0,.85,.3) forwards;
            pointer-events: none;
        }

        body.settings-leaving .back-button {
            animation: backExit 0.5s ease-in forwards;
            pointer-events: none;
        }

        @keyframes settingsExit {
            from { opacity: 1; transform: translate(-50%, -50%); }
            to   { opacity: 0; transform: translate(calc(-50% + 110px), -50%); }
        }

        @keyframes backExit {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(-45px); }
        }

        @media (max-width: 600px) {
            .settings-art-wrap {
                width: 98vw;
            }
            .settings-slider::-webkit-slider-thumb {
                width: 34px;
                height: 34px;
                margin-top: -16px;
            }
            .settings-slider::-moz-range-thumb {
                width: 34px;
                height: 34px;
            }
            .back-button {
                left: 8px;
                bottom: 6px;
                width: 68px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .settings-art-wrap, .back-button {
                animation-duration: 0.01ms !important;
                animation-delay: 0ms !important;
            }
        }
    </style>
</head>
<body>
<div class="settings-stage" id="settingsStage">
    <div class="bg-image"></div>
    <div class="bg-overlay"></div>
    <div class="page-fog"></div>

    <main class="settings-art-wrap" aria-label="Settings">
        <img class="settings-menu-art" src="Assets/img/settings%20menu.png" alt="Settings menu with brightness, fullscreen, sound volume, and music volume controls">

        <input class="settings-slider" id="brightness-slider" type="range" min="0.35" max="1" step="0.01" value="1" aria-label="Brightness">
        <input class="settings-slider" id="sound-slider" type="range" min="0" max="1" step="0.01" value="0.8" aria-label="Sound volume">
        <input class="settings-slider" id="music-slider" type="range" min="0" max="1" step="0.01" value="0.35" aria-label="Music volume">

        <button class="fullscreen-toggle" id="fullscreen-toggle" type="button" aria-label="Toggle fullscreen" aria-pressed="false">
            <img src="Assets/img/check.png" alt="">
        </button>
    </main>

    <audio id="settings-music" loop preload="auto">
        <source src="Assets/mp3/interface_music.mp3" type="audio/mpeg">
    </audio>
</div>

<a href="dashboard.php" class="back-link" id="back-link" aria-label="Back to dashboard">
    <img class="back-button" src="Assets/img/back%20button.png" alt="Back">
</a>

<script>
(function () {
    'use strict';

    const stage = document.getElementById('settingsStage');
    const brightness = document.getElementById('brightness-slider');
    const sound = document.getElementById('sound-slider');
    const musicSlider = document.getElementById('music-slider');
    const fullscreenButton = document.getElementById('fullscreen-toggle');
    const music = document.getElementById('settings-music');
    const backLink = document.getElementById('back-link');

    function readSetting(key, fallback) {
        const saved = localStorage.getItem(key);
        if (saved === null) return fallback;
        const parsed = Number(saved);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    brightness.value = String(readSetting('settingsBrightness', 1));
    sound.value = String(readSetting('soundVolume', 0.8));
    musicSlider.value = String(readSetting('musicVolume', 0.35));

    function applyBrightness() {
        stage.style.setProperty('--settings-brightness', brightness.value);
        localStorage.setItem('settingsBrightness', brightness.value);
    }

    function applySoundVolume() {
        localStorage.setItem('soundVolume', sound.value);
        document.querySelectorAll('audio, video').forEach(function (media) {
            if (media !== music) media.volume = Number(sound.value);
        });
    }

    function applyMusicVolume() {
        const volume = Number(musicSlider.value);
        music.volume = volume;
        localStorage.setItem('musicVolume', String(volume));
    }

    function syncFullscreenState() {
        // Keep the preference separate from actual fullscreen; browsers exit
        // fullscreen automatically during page navigation.
        const enabled = localStorage.getItem('fullscreenPreference') === 'true';
        fullscreenButton.setAttribute('aria-pressed', enabled ? 'true' : 'false');
    }

    brightness.addEventListener('input', applyBrightness);
    sound.addEventListener('input', applySoundVolume);
    musicSlider.addEventListener('input', applyMusicVolume);

    fullscreenButton.addEventListener('click', async function () {
        // Base the click on the browser's actual fullscreen state, not only the
        // saved preference. This lets the user re-enter fullscreen after a page
        // navigation has caused the browser to leave fullscreen.
        const shouldEnable = !document.fullscreenElement;
        localStorage.setItem('fullscreenPreference', String(shouldEnable));
        syncFullscreenState();

        try {
            if (shouldEnable) {
                await document.documentElement.requestFullscreen();
            } else {
                await document.exitFullscreen();
            }
        } catch (error) {
            // Browsers may reject fullscreen requests; keep the preference
            // visible so the user can try again with another click.
        }
    });

    document.addEventListener('fullscreenchange', syncFullscreenState);

    // Use the same saved music volume as the dashboard. Playback remains subject
    // to the browser's autoplay policy.
    applyBrightness();
    applySoundVolume();
    applyMusicVolume();
    syncFullscreenState();
    music.play().catch(function () {});

    backLink.addEventListener('click', function (event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
        event.preventDefault();
        document.body.classList.add('settings-leaving');
        window.setTimeout(function () {
            window.location.href = backLink.href;
        }, 700);
    });
})();
</script>
</body>
</html>
