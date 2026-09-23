/**
 * Nebula theme — interactions légères.
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

		// Menu mobile : le bouton déroule le panneau de liens ; un lien choisi, Échap ou un clic
		// ailleurs le referment.
		var burger = document.getElementById('nb-burger');
		if (nav && burger) {
			var fermer = function () {
				nav.classList.remove('open');
				burger.setAttribute('aria-expanded', 'false');
			};

			burger.addEventListener('click', function (e) {
				e.stopPropagation();
				var open = nav.classList.toggle('open');
				burger.setAttribute('aria-expanded', open ? 'true' : 'false');
				nav.classList.add('scrolled');
			});

			nav.querySelectorAll('.nb-links a').forEach(function (a) { a.addEventListener('click', fermer); });
			document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { fermer(); } });
			document.addEventListener('click', function (e) { if (!nav.contains(e.target)) { fermer(); } });
		}


		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'fg-to-top';
		btn.setAttribute('aria-label', '<?php echo addslashes($this->lang('Retour en haut')) ?>');
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
