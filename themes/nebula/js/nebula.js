/**
 * Nebula theme — interactions légères.
 * - sélecteur de thème (footer) : pose le cookie nf_theme et recharge
 * - bouton « retour en haut » au défilement (respecte prefers-reduced-motion)
 */
(function () {
	'use strict';

	function init() {
		// Navbar : effet glass au défilement
		var nav = document.getElementById('nb-nav');
		if (nav) {
			var onScroll = function () { nav.classList.toggle('scrolled', window.scrollY > 24); };
			window.addEventListener('scroll', onScroll, { passive: true });
			onScroll();
		}

		document.querySelectorAll('[data-theme-pick]').forEach(function (el) {
			el.addEventListener('click', function () {
				document.cookie = 'nf_theme=' + encodeURIComponent(el.getAttribute('data-theme-pick')) + ';path=/;max-age=31536000;samesite=lax';
				location.reload();
			});
		});

		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'fg-to-top';
		btn.setAttribute('aria-label', 'Retour en haut');
		btn.innerHTML = '<i class="fas fa-chevron-up"></i>';
		document.body.appendChild(btn);

		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var ticking = false;

		function update() {
			btn.classList.toggle('is-visible', window.pageYOffset > 400);
			ticking = false;
		}

		window.addEventListener('scroll', function () {
			if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
		}, { passive: true });

		btn.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
		});

		update();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
