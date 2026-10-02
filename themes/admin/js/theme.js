(function() {
	'use strict';

	var STORAGE_THEME = 'nf-admin-theme';

	function getStored(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
	function setStored(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
	function sysDark() { return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches; }

	// ============ THEME ============
	function applyTheme(t) { document.documentElement.setAttribute('data-theme', t); document.documentElement.setAttribute('data-bs-theme', t === 'dark' ? 'dark' : 'light'); }
	function syncThemeButton(t) {
		var btn = document.querySelector('.theme-toggle');
		if (!btn) return;
		var icon = btn.querySelector('i');
		if (!icon) return;
		if (t === 'dark') {
			icon.className = 'fas fa-sun';
			btn.setAttribute('title', '<?php echo addslashes($this->lang('Mode clair')) ?>');
		} else {
			icon.className = 'fas fa-moon';
			btn.setAttribute('title', '<?php echo addslashes($this->lang('Mode sombre')) ?>');
		}
	}
	function toggleTheme() {
		var c = document.documentElement.getAttribute('data-theme') || 'light';
		var n = c === 'dark' ? 'light' : 'dark';
		document.documentElement.classList.add('no-transitions');
		applyTheme(n); setStored(STORAGE_THEME, n); syncThemeButton(n);
		requestAnimationFrame(function() {
			requestAnimationFrame(function() { document.documentElement.classList.remove('no-transitions'); });
		});
	}
	applyTheme(getStored(STORAGE_THEME) || (sysDark() ? 'dark' : 'light'));

	// ============ MOBILE SIDEBAR ============
	function initMobileSidebar() {
		var btn = document.getElementById('nfSidebarToggle');
		var sidebar = document.getElementById('nfSidebar');
		if (!btn || !sidebar) return;
		btn.addEventListener('click', function(e) {
			e.stopPropagation();
			sidebar.classList.toggle('is-open');
		});
		document.addEventListener('click', function(e) {
			if (!sidebar.classList.contains('is-open')) return;
			if (sidebar.contains(e.target)) return;
			if (btn.contains(e.target)) return;
			sidebar.classList.remove('is-open');
		});
	}

	// ============ COMMAND PALETTE ============
	var paletteOverlay, cmdInput, cmdResults, focusedIdx = 0;

	function getCommands() {
		var modules = window.nfSidebarData || [];
		var out = [];
		modules.forEach(function(m) {
			out.push({
				kind: 'module',
				title: m.title,
				icon: m.icon || 'fas fa-circle',
				url: m.url,
				section: m.section,
				sectionIcon: m.sectionIcon || 'fas fa-folder'
			});
		});
		// Static actions
		var reglages = '<?php echo addslashes($this->lang('Réglages')) ?>';
		out.push({ kind: 'action', title: '<?php echo addslashes($this->lang('Basculer le thème (clair/sombre)')) ?>', icon: 'fas fa-moon', section: reglages, sectionIcon: 'fas fa-sliders-h', action: 'toggle-theme' });
		out.push({ kind: 'action', title: '<?php echo addslashes($this->lang('Voir le site public')) ?>', icon: 'fas fa-external-link-alt', section: reglages, sectionIcon: 'fas fa-sliders-h', url: window.nfHomeUrl || '/' });
		out.push({ kind: 'action', title: '<?php echo addslashes($this->lang('Se déconnecter')) ?>', icon: 'fas fa-sign-out-alt', section: reglages, sectionIcon: 'fas fa-sliders-h', url: window.nfLogoutUrl || '#' });
		return out;
	}

	// Sans accents ni majuscules : « evenements » trouve « Événements gaming ». La décomposition (NFD)
	// puis le retrait des accents garde la longueur d'un titre composé (NFC) : les positions servent
	// telles quelles au surlignage.
	function normaliser(s) {
		return String(s == null ? '' : s).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
	}

	function surligner(titre, q) {
		var i = q ? normaliser(titre).indexOf(q) : -1;
		if (i < 0) return escapeHtml(titre);
		return escapeHtml(titre.slice(0, i)) + '<mark>' + escapeHtml(titre.slice(i, i + q.length)) + '</mark>' + escapeHtml(titre.slice(i + q.length));
	}

	function renderResults(query) {
		if (!cmdResults) return;
		var q = normaliser(query).trim();
		var all = getCommands();
		// Le titre, ou le nom de la rubrique : « gaming » liste les modules de la rubrique Gaming.
		var matched = all.filter(function(c) { return !q || normaliser(c.title).indexOf(q) !== -1 || normaliser(c.section).indexOf(q) !== -1; });
		if (matched.length === 0) {
			cmdResults.innerHTML = '<div class="nf-cmd-empty"><i class="fas fa-search"></i> <?php echo addslashes($this->lang('Aucun résultat')) ?></div>';
			return;
		}
		var grouped = {};
		var sectionIcons = {};
		matched.forEach(function(c) { (grouped[c.section] = grouped[c.section] || []).push(c); sectionIcons[c.section] = c.sectionIcon || 'fas fa-folder'; });
		var html = '';
		var idx = 0;
		Object.keys(grouped).forEach(function(sec) {
			html += '<div class="nf-cmd-section">';
			html += '<div class="nf-cmd-section-header"><i class="' + escapeAttr(sectionIcons[sec]) + '"></i><span>' + escapeHtml(sec) + '</span></div>';
			grouped[sec].forEach(function(c) {
				html += '<a class="nf-cmd-result" data-idx="' + idx + '"';
				if (c.url) html += ' href="' + escapeAttr(c.url) + '"';
				html += ' data-action="' + escapeAttr(c.action || '') + '">';
				html += '<span class="nf-cmd-result-icon"><i class="' + escapeAttr(c.icon) + '"></i></span>';
				// Le nom de la rubrique n'est plus répété à droite : l'en-tête du groupe le dit déjà.
				html += '<span class="nf-cmd-result-title">' + surligner(c.title, q) + '</span>';
				html += '</a>';
				idx++;
			});
			html += '</div>';
		});
		cmdResults.innerHTML = html;
		focusedIdx = 0;
		updateFocus();
	}

	function escapeHtml(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
			return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
		});
	}
	function escapeAttr(s) { return escapeHtml(s); }

	function updateFocus() {
		var items = cmdResults.querySelectorAll('.nf-cmd-result');
		// « is-active » : la classe que la feuille dessine. L'ancienne (« focused ») n'avait aucun style,
		// et les flèches du clavier déplaçaient une sélection invisible.
		items.forEach(function(it, i) { it.classList.toggle('is-active', i === focusedIdx); });
		if (items[focusedIdx]) items[focusedIdx].scrollIntoView({ block: 'nearest' });
	}

	function openPalette() {
		if (!paletteOverlay) return;
		paletteOverlay.classList.add('show');
		paletteOverlay.setAttribute('aria-hidden', 'false');
		cmdInput.value = '';
		renderResults('');
		setTimeout(function() { cmdInput.focus(); }, 10);
	}
	function closePalette() {
		if (!paletteOverlay) return;
		paletteOverlay.classList.remove('show');
		paletteOverlay.setAttribute('aria-hidden', 'true');
	}

	function executeResult(el) {
		var action = el.getAttribute('data-action');
		if (action === 'toggle-theme') { closePalette(); toggleTheme(); return; }
		var href = el.getAttribute('href');
		if (href) { window.location.href = href; return; }
		closePalette();
	}

	function initPalette() {
		paletteOverlay = document.getElementById('nfCmdOverlay');
		cmdInput = document.getElementById('nfCmdInput');
		cmdResults = document.getElementById('nfCmdResults');
		if (!paletteOverlay || !cmdInput || !cmdResults) return;

		cmdInput.addEventListener('input', function() { renderResults(cmdInput.value); });
		cmdInput.addEventListener('keydown', function(e) {
			var items = cmdResults.querySelectorAll('.nf-cmd-result');
			if (e.key === 'ArrowDown') { e.preventDefault(); if (items.length) { focusedIdx = (focusedIdx + 1) % items.length; updateFocus(); } }
			else if (e.key === 'ArrowUp') { e.preventDefault(); if (items.length) { focusedIdx = (focusedIdx - 1 + items.length) % items.length; updateFocus(); } }
			else if (e.key === 'Enter') { e.preventDefault(); if (items[focusedIdx]) executeResult(items[focusedIdx]); }
			else if (e.key === 'Escape') { closePalette(); }
		});
		// La souris et le clavier partagent la même sélection : jamais deux lignes surlignées à la fois.
		cmdResults.addEventListener('mousemove', function(e) {
			var item = e.target.closest('.nf-cmd-result');
			if (!item) return;
			var i = parseInt(item.getAttribute('data-idx'), 10);
			if (i !== focusedIdx) { focusedIdx = i; cmdResults.querySelectorAll('.nf-cmd-result').forEach(function(it, j) { it.classList.toggle('is-active', j === i); }); }
		});
		cmdResults.addEventListener('click', function(e) {
			var item = e.target.closest('.nf-cmd-result');
			if (!item) return;
			e.preventDefault();
			executeResult(item);
		});
		paletteOverlay.addEventListener('click', function(e) {
			if (e.target === paletteOverlay) closePalette();
		});

		var hint = document.getElementById('nfCmdHint');
		if (hint) hint.addEventListener('click', openPalette);

		document.addEventListener('keydown', function(e) {
			if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
				e.preventDefault();
				if (paletteOverlay.classList.contains('show')) closePalette();
				else openPalette();
			}
		});
	}

	// ============ INIT ============
	function init() {
		syncThemeButton(document.documentElement.getAttribute('data-theme') || 'light');
		var toggle = document.getElementById('nfThemeToggle');
		if (toggle) {
			toggle.addEventListener('click', function(e) {
				e.preventDefault();
				toggleTheme();
			});
		}
		initMobileSidebar();
		initPalette();

		// Follow system if no stored choice
		if (!getStored(STORAGE_THEME) && window.matchMedia) {
			var mql = window.matchMedia('(prefers-color-scheme: dark)');
			var handler = function(e) {
				if (getStored(STORAGE_THEME)) return;
				var t = e.matches ? 'dark' : 'light';
				applyTheme(t); syncThemeButton(t);
			};
			if (mql.addEventListener) mql.addEventListener('change', handler);
			else if (mql.addListener) mql.addListener(handler);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
