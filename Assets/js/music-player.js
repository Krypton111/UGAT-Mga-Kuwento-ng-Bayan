// assets/js/music-player.js
(function() {
    'use strict';

    const pageName = window.location.pathname.split('/').pop().replace('.php', '') || 'dashboard';
    
    const musicMap = {
        'login': 'assets/mp3/login_music.mp3',
        'register': 'assets/mp3/register_music.mp3',
        'dashboard': 'assets/mp3/dashboard_music.mp3',
        'play_game': 'assets/mp3/game_music.mp3',
        'game_library': 'assets/mp3/library_music.mp3',
        'forgot_password': 'assets/mp3/forgot_music.mp3',
        'profile': 'assets/mp3/profile_music.mp3',
        'admin_panel': 'assets/mp3/admin_music.mp3'
    };

    const defaultMusic = 'assets/mp3/interface_music.mp3';
    const audio = document.getElementById('interface-music');
    if (!audio) return;

    const musicFile = musicMap[pageName] || defaultMusic;
    const currentSrc = audio.querySelector('source')?.src || '';
    if (!currentSrc.includes(musicFile)) {
        const source = audio.querySelector('source');
        if (source) {
            source.src = musicFile;
            audio.load();
        }
    }

    const savedVolume = localStorage.getItem('musicVolume');
    if (savedVolume !== null) {
        audio.volume = parseFloat(savedVolume);
    }

    audio.play().catch(() => {});

    window.musicPlayer = {
        audio: audio,
        play: function() { audio.play().catch(() => {}); },
        pause: function() { audio.pause(); },
        toggle: function() {
            if (audio.paused) {
                return audio.play().catch(() => {});
            } else {
                audio.pause();
            }
        },
        setVolume: function(vol) {
            audio.volume = vol;
            localStorage.setItem('musicVolume', vol);
        }
    };
})();