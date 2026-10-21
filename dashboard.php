<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';
require_once 'includes/pending_cleanup.php';

$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT level, coins, is_admin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$_SESSION['level'] = $user['level'];
$_SESSION['coins'] = $user['coins'];

$level = $user['level'];
$coins = $user['coins'];
$isAdmin = $user['is_admin'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ᜂᜄᜆ᜔: Kuwento ng Bayan — Heritage Hub</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ------------------------------------------------------
           Dashboard entry animations
           ------------------------------------------------------ */

        .dashboard-fog {
            opacity: 0;
            transform: translateX(60px);
            animation: fogEnter 1.0s cubic-bezier(.22,1,.36,1) 0.15s forwards;
        }
        @keyframes fogEnter {
            0%   { opacity: 0; transform: translateX(60px); }
            100% { opacity: 1; transform: translateX(0);    }
        }

        .menu-item {
            opacity: 0;
            transform: translateX(60px);
            animation: menuEnter 0.8s cubic-bezier(.22,1,.36,1) forwards;
        }
        .menu-item:nth-child(1) { animation-delay: 0.55s; }
        .menu-item:nth-child(2) { animation-delay: 0.70s; }
        .menu-item:nth-child(3) { animation-delay: 0.85s; }
        .menu-item:nth-child(4) { animation-delay: 1.00s; }

        @keyframes menuEnter {
            0%   { opacity: 0; transform: translateX(60px); }
            100% { opacity: 1; transform: translateX(0);    }
        }

        .menu-item {
            transition:
                transform .35s cubic-bezier(.34,1.56,.64,1),
                color .25s ease,
                text-shadow .25s ease,
                filter .25s ease;
        }
        .menu-item.entered {
            animation: none;
            opacity: 1;
            transform: translateX(0);
        }
        .menu-item:hover {
            transform: translateX(-14px) translateY(-10px) scale(1.08);
            color: #ffd86b;
            text-shadow:
                0 4px 14px rgba(26, 20, 16, 0.95),
                0 0 24px rgba(201, 162, 39, 0.75);
            filter: drop-shadow(0 8px 18px rgba(0, 0, 0, 0.45));
        }
        .menu-item:active {
            transform: translateX(-14px) translateY(-4px) scale(1.02);
            transition: transform .12s ease;
        }

        /* ------------------------------------------------------
           Fog exit (CSS-only, doesn't conflict with buttons)
           ------------------------------------------------------ */
        body.leaving .dashboard-fog {
            animation: fogExit 0.7s cubic-bezier(.55,0,.85,.3) forwards !important;
        }
        @keyframes fogExit {
            0%   { opacity: 1; transform: translateX(0);     }
            100% { opacity: 0; transform: translateX(100px); }
        }

        /* ------------------------------------------------------
           Play-Story-only exit: title + background fade to black
           ------------------------------------------------------ */
        body.leaving-story .full-title {
            animation: titleExit 0.7s cubic-bezier(.55,0,.85,.3) 0.1s forwards !important;
        }
        @keyframes titleExit {
            0%   { opacity: 1; transform: translateX(0)     translateY(0); }
            100% { opacity: 0; transform: translateX(-60px) translateY(-6px); }
        }

        body.leaving-story .bg-image {
            animation: bgBlur 0.8s ease-out forwards !important;
        }
        @keyframes bgBlur {
            0%   { filter: blur(0px);  transform: scale(1);    }
            100% { filter: blur(20px); transform: scale(1.06); }
        }

        body.leaving-story::after {
            content: '';
            position: fixed;
            inset: 0;
            background: #000;
            opacity: 0;
            z-index: 998;
            pointer-events: none;
            animation: bgBlackout 0.9s cubic-bezier(.55,0,.85,.3) 0.15s forwards;
        }
        @keyframes bgBlackout {
            0%   { opacity: 0; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
<div class="bg-image"></div>
<div class="bg-overlay"></div>
<div class="page-fog"></div>
<div class="dashboard-fog"></div>

<img src="assets/img/fulltitle.png"
     alt="ᜂᜄᜆ᜔: Mga Kuwento ng Bayan"
     class="full-title">

<audio id="interface-music" loop preload="auto">
    <source src="assets/mp3/theme.mp3" type="audio/mpeg">
</audio>

<div class="page-wrapper">
    <div class="title-screen">
        <nav class="menu" aria-label="Main menu">
            <!-- Play Story goes straight to the Godot export -->
            <a href="assets/games/lair/index.html" class="menu-item" id="playStoryBtn">Play Story</a>
            <a href="dashboard.php" class="menu-item">Help &amp; Guide</a>
            <a href="profile.php" class="menu-item">Settings</a>
            <a href="logout.php" class="menu-item">Logout</a>
        </nav>
    </div>
</div>

<div class="music-controls">
    <button id="music-toggle" aria-label="Toggle music">🔊</button>
    <input type="range" id="volume-slider" min="0" max="1" step="0.01" value="0.35">
</div>

<script>
    // ----------------------------------------------------------
    // 1) Lock resting state after each menu item's entry animation.
    // ----------------------------------------------------------
    document.querySelectorAll('.menu-item').forEach(function (item) {
        item.addEventListener('animationend', function (e) {
            if (e.animationName === 'menuEnter') {
                item.classList.add('entered');
            }
        });
    });

    // ----------------------------------------------------------
    // 2) Intercept clicks on menu items for the exit animation.
    //    Buttons are animated with the Web Animations API so the
    //    exit starts from wherever the button currently is
    //    (including its :hover transform) — no snap, no cut.
    // ----------------------------------------------------------
    document.querySelectorAll('.menu-item').forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const target = item.getAttribute('href');
            if (!target) return;

            const isPlayStory = item.id === 'playStoryBtn';

            // Trigger CSS-driven exits (fog, title, bg blur).
            document.body.classList.add('leaving');
            if (isPlayStory) {
                document.body.classList.add('leaving-story');
            }

            // ------------------------------------------------
            // BUTTON EXIT — Web Animations API
            // ------------------------------------------------
            const buttons = document.querySelectorAll('.menu-item');
            const order = Array.from(buttons).reverse(); // reverse stagger: last button leaves first

            order.forEach(function (btn, index) {
                // 1. Read current transform (includes hover).
                const cs = getComputedStyle(btn);
                const startTransform = (cs.transform === 'none')
                    ? 'translateX(0)'
                    : cs.transform;

                // 2. Bake the start transform as an inline style so
                //    the :hover rule no longer fights the animation.
                btn.style.transform = startTransform;

                // 3. Force a layout flush so the inline transform is
                //    committed before we disable the transition.
                void btn.offsetWidth;

                // 4. Now safely disable transition & pointer events.
                btn.style.transition = 'none';
                btn.style.pointerEvents = 'none';

                // 5. Animate from current position to off-screen right.
                btn.animate(
                    [
                        { opacity: 1, transform: startTransform },
                        { opacity: 0, transform: startTransform + ' translateX(80px)' }
                    ],
                    {
                        duration: 550,
                        delay: index * 80,
                        easing: 'cubic-bezier(.4,0,.6,1)',
                        fill: 'forwards'
                    }
                );
            });

            // Wait long enough for all exit animations to finish.
            const delay = 1300;
            setTimeout(function () {
                window.location.href = target;
            }, delay);
        });
    });
</script>

<script src="assets/js/particles.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>