/**
 * Recherche instantanée (typeahead). Vanilla, sans jQuery. Interroge ajax/search/suggest
 * (débounce 200 ms, dès 2 caractères) et affiche un menu de résultats construit en DOM
 * (textContent → anti-XSS). Clavier : flèches + Entrée + Échap. Ferme au clic extérieur.
 */
(function(){
	"use strict";

	function init(input){
		if (input.__nfSearch) { return; }
		input.__nfSearch = true;

		var url   = input.getAttribute('data-suggest-url');
		var group = input.closest('.nf-search');
		var box   = group ? group.querySelector('.nf-search-suggest') : null;
		if (!url || !box) { return; }

		var timer = null, ctrl = null, active = -1, hasItems = false;

		function close(){ box.hidden = true; box.innerHTML = ''; active = -1; hasItems = false; }

		function render(results){
			box.innerHTML = '';
			active = -1;
			hasItems = results.length > 0;

			if (!hasItems) { close(); return; }

			results.forEach(function(r){
				var a = document.createElement('a');
				a.className = 'nf-search-suggest-item';
				a.setAttribute('role', 'option');
				a.href = r.url;

				var ic = document.createElement('i');
				ic.className = (r.icon || 'fas fa-file') + ' fa-fw';

				var t = document.createElement('span');
				t.className = 'nf-search-suggest-title';
				t.textContent = r.title;

				var m = document.createElement('small');
				m.className = 'nf-search-suggest-mod';
				m.textContent = r.module || '';

				a.appendChild(ic);
				a.appendChild(document.createTextNode(' '));
				a.appendChild(t);
				a.appendChild(m);
				box.appendChild(a);
			});

			box.hidden = false;
		}

		function load(q){
			if (ctrl) { try { ctrl.abort(); } catch (e) {} }
			ctrl = window.AbortController ? new AbortController() : null;

			fetch(url + (url.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(q), {
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
				credentials: 'same-origin',
				signal: ctrl ? ctrl.signal : undefined
			})
			.then(function(r){ return r.ok ? r.json() : []; })
			.then(function(d){ render(Array.isArray(d) ? d : []); })
			.catch(function(){});
		}

		input.addEventListener('input', function(){
			var q = input.value.trim();
			if (timer) { clearTimeout(timer); }
			if (q.length < 2) { close(); return; }
			timer = setTimeout(function(){ load(q); }, 200);
		});

		input.addEventListener('keydown', function(e){
			if (box.hidden) { return; }
			var links = box.querySelectorAll('.nf-search-suggest-item');
			if (!links.length) { return; }

			if (e.key === 'ArrowDown')      { e.preventDefault(); active = Math.min(active + 1, links.length - 1); }
			else if (e.key === 'ArrowUp')   { e.preventDefault(); active = Math.max(active - 1, 0); }
			else if (e.key === 'Enter')     { if (active >= 0) { e.preventDefault(); window.location.href = links[active].href; return; } return; }
			else if (e.key === 'Escape')    { close(); return; }
			else { return; }

			for (var i = 0; i < links.length; i++) { links[i].classList.toggle('active', i === active); }
		});

		input.addEventListener('focus', function(){ if (hasItems) { box.hidden = false; } });

		document.addEventListener('click', function(e){
			if (!group.contains(e.target)) { close(); }
		});
	}

	function boot(){
		var inputs = document.querySelectorAll('input[data-suggest-url]');
		for (var i = 0; i < inputs.length; i++) { init(inputs[i]); }
	}

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); }
	else { boot(); }
})();
