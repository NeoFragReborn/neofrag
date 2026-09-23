(function () {
	'use strict';

	var EPINGLER    = '<?php echo addslashes($this->lang('Épingler')) ?>';
	var DESEPINGLER = '<?php echo addslashes($this->lang('Désépingler')) ?>';

	/* ---- Accordéon des sections -------------------------------------- */
	document.querySelectorAll('.nf-sb-sec-head').forEach(function (head) {
		head.addEventListener('click', function () {
			var sec = head.closest('.nf-sb-section');
			if (sec) sec.classList.toggle('open');
		});
	});

	/* ---- Épingles (pins) --------------------------------------------- */
	var PIN_KEY = 'nf-admin-pinned';
	var nav = document.querySelector('.nf-sb-nav');
	var pinnedList = document.querySelector('.nf-sb-section[data-section="pinned"] .nf-sb-items');

	function loadPinned() { try { return JSON.parse(localStorage.getItem(PIN_KEY) || '[]') || []; } catch (e) { return []; } }
	function savePinned(a) { try { localStorage.setItem(PIN_KEY, JSON.stringify(a)); } catch (e) {} }
	function cssEsc(s) { return String(s).replace(/[^a-zA-Z0-9_-]/g, '\\$&'); }

	function renderPins() {
		var pinned = loadPinned();
		if (pinnedList) {
			pinnedList.querySelectorAll('li[data-pin-clone]').forEach(function (el) { el.remove(); });
			pinned.forEach(function (name) {
				var orig = nav && nav.querySelector('.nf-sb-pin[data-pin="' + cssEsc(name) + '"]');
				var li = orig && orig.closest('li');
				if (!li) return;
				var clone = li.cloneNode(true);
				clone.setAttribute('data-pin-clone', '1');
				pinnedList.appendChild(clone);
			});
		}
		// Marque tous les boutons pin (originaux + clones)
		document.querySelectorAll('.nf-sb-pin').forEach(function (p) {
			var on = pinned.indexOf(p.dataset.pin) !== -1;
			p.classList.toggle('pinned', on);
			p.setAttribute('title', on ? DESEPINGLER : EPINGLER);
			p.setAttribute('aria-label', on ? DESEPINGLER : EPINGLER);
		});
	}

	if (nav) {
		// Délégation : marche aussi pour les clones ajoutés dynamiquement
		nav.addEventListener('click', function (e) {
			var pin = e.target.closest('.nf-sb-pin');
			if (!pin) return;
			e.preventDefault();
			e.stopPropagation();
			var name = pin.dataset.pin;
			if (!name) return;
			var arr = loadPinned();
			var i = arr.indexOf(name);
			if (i === -1) arr.push(name); else arr.splice(i, 1);
			savePinned(arr);
			renderPins();
		});
		renderPins();
	}

	/* ---- Aligne l'item actif dans la zone visible --------------------- */
	if (nav) {
		var active = nav.querySelector('.nf-sb-item.active');
		if (active) {
			var r = active.getBoundingClientRect(), n = nav.getBoundingClientRect();
			if (r.top < n.top + 8 || r.bottom > n.bottom - 8) active.scrollIntoView({ block: 'nearest' });
		}
	}
})();
