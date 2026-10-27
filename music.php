<?php
// music.php – just the interface music
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Music Player</title>
  <style>
    body { margin:0; background:transparent; }
  </style>
</head>
<body>
  <audio id="bg-music" loop volume="0.5" preload="auto">
    <source src="assets/mp3/interface_music.mp3" type="audio/mpeg">
  </audio>

  <script>
    (function() {
      const audio = document.getElementById('bg-music');

      // Expose a simple play function to the parent
      window.playAudio = function() {
        return audio.play();
      };

      // Try to autoplay on load (will likely fail, but the parent will retry on click)
      window.addEventListener('load', function() {
        audio.play().catch(() => {});
      });
    })();
  </script>
</body>
</html>