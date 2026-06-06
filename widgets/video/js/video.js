/**
 * Widget Vidéo — clic sur un item de playlist → change la source du lecteur.
 */
(function() {
	'use strict';

	function init() {
		document.querySelectorAll('.nf-video-widget').forEach(function(w) {
			var player = w.querySelector('video.nf-video-player');
			if (!player) return;

			w.querySelectorAll('[data-video-src]').forEach(function(item) {
				item.addEventListener('click', function() {
					player.src = item.getAttribute('data-video-src');
					player.play();
					w.querySelectorAll('[data-video-src]').forEach(function(i) { i.classList.remove('active'); });
					item.classList.add('active');
				});
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
