// music-control.js – controls the hidden music iframe
(function() {
  const toggleBtn = document.getElementById('music-toggle');
  const iframe = document.querySelector('iframe[src="music.php"]');
  let isPlaying = false;

  if (!iframe) {
    console.warn('Music iframe not found.');
    return;
  }

  function callIframeFunction(funcName) {
    if (iframe.contentWindow && typeof iframe.contentWindow[funcName] === 'function') {
      return iframe.contentWindow[funcName]();
    } else {
      // fallback postMessage
      const cmdMap = { toggleAudio: 'toggle', playAudio: 'play', pauseAudio: 'pause' };
      if (iframe.contentWindow) {
        iframe.contentWindow.postMessage(cmdMap[funcName] || 'toggle', '*');
      }
    }
  }

  function updateIcon(playing) {
    toggleBtn.textContent = playing ? '🔊' : '🔇';
    toggleBtn.classList.toggle('muted', !playing);
    isPlaying = playing;
  }

  window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'status') {
      updateIcon(event.data.playing);
    }
  });

  toggleBtn.addEventListener('click', function() {
    const newState = !isPlaying;
    updateIcon(newState);
    callIframeFunction('toggleAudio');
  });

  iframe.addEventListener('load', function() {
    console.log('🎵 Iframe loaded, attempting to play...');
    callIframeFunction('playAudio').then(() => {
      updateIcon(true);
    }).catch(() => {
      updateIcon(false);
    });
  });

  if (iframe.contentWindow && iframe.contentWindow.playAudio) {
    iframe.contentWindow.playAudio().then(() => {
      updateIcon(true);
    }).catch(() => {});
  }

  window.__musicControl = {
    iframe: iframe,
    callIframeFunction: callIframeFunction,
    toggle: () => toggleBtn.click()
  };
})();