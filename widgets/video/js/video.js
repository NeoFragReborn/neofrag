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
					// Une adresse http(s) ou relative seulement : le lecteur n'exécute rien, mais une
					// adresse en `javascript:` ou `data:` n'a rien à faire là (relevé par CodeQL, 2026-10-04).
					var src = item.getAttribute('data-video-src') || '';
					if (!/^(https?:\/\/|\/(?!\/))/i.test(src)) {
						return;
					}
					player.src = src;
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
