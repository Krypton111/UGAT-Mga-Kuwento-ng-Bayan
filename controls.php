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
    <title>ᜂᜄᜆ᜔: Kultura’t Tradisyon — Help &amp; Guide</title>
    <link rel="icon" type="image/x-icon" href="Assets/img/favicon.ico">
    <link rel="stylesheet" href="Assets/css/style.css">
    <style>
        body.scrollable { overflow-y: auto; }
        .guide-page {
            width: min(1000px, 100%);
            margin: 0 auto;
            padding-bottom: 38px;
            opacity: 0;
            transform: translateX(70px);
            animation: guideEnter .8s cubic-bezier(.22,1,.36,1) .08s forwards;
        }
        @keyframes guideEnter {
            from { opacity: 0; transform: translateX(70px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .guide-heading {
            text-align: center;
            margin-bottom: 24px;
            color: #f8edcf;
            text-shadow: 0 3px 16px rgba(0,0,0,.6);
        }
        .guide-heading .eyebrow {
            display: block;
            color: #e5c98d;
            font-size: 17px;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .guide-heading h1 {
            font-family: 'Lavonia Classy', 'Great Vibes', cursive;
            font-size: clamp(36px, 5vw, 54px);
            font-weight: 400;
            line-height: 1.15;
        }
        .guide-heading p {
            color: rgba(247,244,237,.88);
            font-size: 19px;
            line-height: 1.6;
            margin-top: 10px;
        }
        .guide-section {
            padding: 24px;
            margin-bottom: 18px;
            border: 1px solid rgba(218,192,143,.28);
            border-radius: 16px;
            background: rgba(20,27,38,.84);
            color: #f7f0df;
            box-shadow: 0 12px 32px rgba(0,0,0,.16);
            backdrop-filter: blur(5px);
        }
        .guide-section h2 {
            color: #f4d99d;
            font-size: 27px;
            font-weight: 500;
            margin-bottom: 14px;
        }
        .guide-section > p {
            color: rgba(247,244,237,.84);
            font-size: 18px;
            line-height: 1.7;
        }
        .guide-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 12px;
        }
        .guide-tip {
            padding: 16px;
            border-radius: 11px;
            border: 1px solid rgba(218,192,143,.2);
            background: rgba(255,255,255,.045);
        }
        .guide-tip h3 {
            color: #f4d99d;
            font-size: 21px;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .guide-tip p, .guide-list li {
            color: rgba(247,244,237,.84);
            font-size: 17px;
            line-height: 1.65;
        }
        .guide-list { padding-left: 23px; }
        .guide-list li { padding: 4px 0; }
        .guide-note {
            margin-top: 14px;
            padding: 13px 16px;
            border-left: 3px solid #d6b777;
            background: rgba(218,192,143,.09);
            border-radius: 0 8px 8px 0;
            color: #f3e3bd;
            font-size: 17px;
            line-height: 1.6;
        }
        .guide-actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }
        .guide-actions .btn { min-width: 190px; }
        .back-link {
            position: fixed;
            left: 18px;
            bottom: 12px;
            z-index: 30;
            display: block;
            width: clamp(76px, 9vw, 138px);
            transition: filter .2s ease, transform .2s ease;
        }
        .back-link img { display:block; width:100%; height:auto; }
        .back-link:hover { filter: brightness(1.12) drop-shadow(0 4px 8px rgba(0,0,0,.3)); transform: translateX(-4px); }
        @media (max-width: 650px) {
            .page-wrapper { padding: 34px 5vw 90px; height: auto; min-height: 100vh; }
            .guide-grid { grid-template-columns: 1fr; }
            .guide-section { padding: 18px; }
            .guide-heading p { font-size: 17px; }
            .back-link { left: 8px; bottom: 6px; width: 68px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .guide-page { animation-duration: .01ms; animation-delay: 0ms; }
        }
    </style>
</head>
<body class="scrollable">
<div class="bg-image"></div>
<div class="bg-overlay"></div>
<div class="page-fog"></div>

<audio id="interface-music" loop preload="auto">
    <source src="Assets/mp3/system%20music.mp3" type="audio/mpeg">
</audio>

<div class="page-wrapper">
    <main class="guide-page">
        <header class="guide-heading">
            <span class="eyebrow">ᜂᜄᜆ᜔: Kultura’t Tradisyon</span>
            <h1>Help &amp; Game Guide</h1>
            <p>Explore Migs’ home, help Lola Nena, and discover everyday Filipino traditions. Here are the basics to help you play.</p>
        </header>

        <section class="guide-section">
            <h2>How to Play</h2>
            <div class="guide-grid">
                <article class="guide-tip">
                    <h3>01 · Click to Explore</h3>
                    <p>Use your mouse to click people, objects, and highlighted areas. Read the dialogue and on-screen instructions carefully; they often explain what to do next.</p>
                </article>
                <article class="guide-tip">
                    <h3>02 · Move Between Areas</h3>
                    <p>Look for the directional arrow or navigation hotspot near the edge of a scene. Hover where the game indicates, then click to move to the next area. Use the back arrow when you want to return.</p>
                </article>
                <article class="guide-tip">
                    <h3>03 · Use Your Hotbar</h3>
                    <p>Items you collect appear in the inventory hotbar. Select or drag an item to the object or target that needs it. If nothing happens, check the task instructions and try the item on the relevant target.</p>
                </article>
                <article class="guide-tip">
                    <h3>04 · Follow the Story</h3>
                    <p>Talk to characters, inspect objects, and finish the current task before moving on. Some areas or interactions become available only after you have collected an item or completed an earlier objective.</p>
                </article>
            </div>
            <div class="guide-note"><strong>Tip:</strong> This is a point-and-click adventure. Start by exploring the scene and reading dialogue rather than searching for keyboard movement controls.</div>
        </section>

        <section class="guide-section">
            <h2>Tasks &amp; Interactions</h2>
            <ul class="guide-list">
                <li><strong>Talk to characters:</strong> Click Lola Nena or another character when an interaction is available, then read the dialogue for hints and objectives.</li>
                <li><strong>Inspect important objects:</strong> Check objects that stand out or are mentioned in dialogue. A recipe, for example, can be checked on the side of the refrigerator when the game directs you there.</li>
                <li><strong>Complete chores:</strong> For the backyard chicken chores, use the cups and baskets provided in the scene, put seeds into the appropriate cup or feeder, and collect eggs when prompted.</li>
                <li><strong>Combine item and target:</strong> When a task asks you to use an item, drag it from the hotbar onto the matching target area. Make sure you release it over the target.</li>
                <li><strong>Watch for prerequisites:</strong> Rewards and new routes may depend on completing earlier interactions. If an area does not respond yet, revisit the current objective and nearby objects.</li>
            </ul>
        </section>

        <section class="guide-section">
            <h2>If Something Does Not Respond</h2>
            <ul class="guide-list">
                <li>Read the latest dialogue or instruction again; it may identify the required item or object.</li>
                <li>Try clicking the object directly, then try dragging the relevant inventory item onto it if the task calls for an item.</li>
                <li>Check that the item is dropped inside the target area, not just beside it.</li>
                <li>Use the in-game back arrow to return to the previous area and explore anything you may have missed.</li>
            </ul>
            <div class="guide-note">The game may not support every keyboard shortcut on every screen. Follow the visible cursor, prompts, and interactive objects in the game.</div>
        </section>

        <div class="guide-actions">
            <a class="btn" href="Assets/games/lair/heritage%20game.html" id="playGameBtn">Play Story</a>
            <a class="btn" href="dashboard.php" id="returnDashboardBtn">Back to Dashboard</a>
        </div>
    </main>
</div>

<a href="dashboard.php" class="back-link" id="back-link" aria-label="Back to dashboard">
    <img src="Assets/img/back%20button.png" alt="Back to dashboard">
</a>

<script src="Assets/js/particles.js"></script>
<script src="Assets/js/app.js"></script>
<script>
(function () {
    'use strict';
    const music = document.getElementById('interface-music');
    const readSetting = (key, fallback) => {
        const raw = localStorage.getItem(key);
        const value = raw === null ? fallback : Number(raw);
        return Number.isFinite(value) ? Math.max(0, Math.min(1, value)) : fallback;
    };
    if (music) {
        music.volume = readSetting('musicVolume', 0.35);
        music.play().catch(function () {});
    }
    document.querySelectorAll('audio, video').forEach(function (media) {
        if (media !== music) media.volume = readSetting('soundVolume', 0.8);
    });

    // Match the dashboard's fade/slide transition when returning or launching.
    document.querySelectorAll('#back-link, #playGameBtn, #returnDashboardBtn').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
            const target = link.getAttribute('href');
            if (!target) return;
            event.preventDefault();
            document.body.classList.add('leaving');
            const page = document.querySelector('.guide-page');
            if (page) {
                page.style.transition = 'opacity .55s ease, transform .55s ease';
                page.style.opacity = '0';
                page.style.transform = 'translateX(65px)';
            }
            window.setTimeout(function () { window.location.href = target; }, 650);
        });
    });
})();
</script>
</body>
</html>
