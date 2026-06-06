(function() {
	'use strict';

	var STORAGE_THEME = 'nf-admin-theme';
	var STORAGE_COLLAPSED = 'nf-admin-collapsed-sections';
	var STORAGE_PINNED = 'nf-admin-pinned';
	var STORAGE_SIDEBAR = 'nf-admin-sidebar-collapsed';

	function getStored(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
	function setStored(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
	function sysDark() { return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches; }

	// ============ THEME ============
	function applyTheme(t) { document.documentElement.setAttribute('data-theme', t); }
	function syncThemeButton(t) {
		var btn = document.querySelector('.theme-toggle');
		if (!btn) return;
		var icon = btn.querySelector('i');
		if (!icon) return;
		if (t === 'dark') {
			icon.className = 'fas fa-sun';
			btn.setAttribute('title', 'Mode clair');
		} else {
			icon.className = 'fas fa-moon';
			btn.setAttribute('title', 'Mode sombre');
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

	// ============ SIDEBAR FILTER ============
	function initFilter() {
		var input = document.getElementById('nfNavFilter');
		if (!input) return;
		input.addEventListener('input', function() {
			var q = input.value.toLowerCase().trim();
			document.querySelectorAll('.nf-nav-section').forEach(function(sec) {
				var anyVisible = false;
				sec.querySelectorAll('.nf-nav-item').forEach(function(item) {
					var label = (item.querySelector('.nf-nav-item-label') || {}).textContent || '';
					var match = !q || label.toLowerCase().indexOf(q) !== -1;
					item.style.display = match ? '' : 'none';
					if (match) anyVisible = true;
				});
				sec.style.display = (q && !anyVisible) ? 'none' : '';
				if (q) sec.classList.remove('collapsed');
			});
		});
	}

	// ============ SECTION COLLAPSE ============
	function loadCollapsed() {
		try { return JSON.parse(getStored(STORAGE_COLLAPSED) || '[]'); } catch (e) { return []; }
	}
	function saveCollapsed(arr) { setStored(STORAGE_COLLAPSED, JSON.stringify(arr)); }
	function initSections() {
		var collapsed = loadCollapsed();
		document.querySelectorAll('.nf-nav-section').forEach(function(sec) {
			var id = sec.dataset.section;
			if (!id) return;
			if (collapsed.indexOf(id) !== -1) sec.classList.add('collapsed');
			var header = sec.querySelector('.nf-nav-section-header');
			if (!header) return;
			header.addEventListener('click', function() {
				sec.classList.toggle('collapsed');
				var c = loadCollapsed();
				if (sec.classList.contains('collapsed')) {
					if (c.indexOf(id) === -1) c.push(id);
				} else {
					c = c.filter(function(x) { return x !== id; });
				}
				saveCollapsed(c);
			});
		});
	}

	// ============ PINNED MODULES ============
	function loadPinned() {
		try { return JSON.parse(getStored(STORAGE_PINNED) || '[]'); } catch (e) { return []; }
	}
	function savePinned(arr) { setStored(STORAGE_PINNED, JSON.stringify(arr)); }

	function renderPinnedClones() {
		var pinned = loadPinned();
		var pinnedSection = document.querySelector('.nf-nav-section[data-section="pinned"] .nf-nav-section-items');
		if (!pinnedSection) return;

		// Remove old clones (anything we added)
		pinnedSection.querySelectorAll('.nf-nav-item[data-pin-clone]').forEach(function(el) { el.remove(); });

		// Mark all pinnable items with current state
		document.querySelectorAll('.nf-nav-item-pin').forEach(function(pin) {
			var item = pin.closest('.nf-nav-item');
			if (!item) return;
			var name = pin.dataset.pinName;
			var isPinned = pinned.indexOf(name) !== -1;
			item.classList.toggle('is-pinned', isPinned);
			pin.setAttribute('title', isPinned ? 'Désépingler' : 'Épingler');
			pin.setAttribute('aria-label', isPinned ? 'Désépingler' : 'Épingler');
		});

		// Insert clones for each pinned name (in user's pin order)
		pinned.forEach(function(name) {
			// Find the original item (in any section other than pinned)
			var originals = document.querySelectorAll('.nf-nav-item-pin[data-pin-name="' + cssEscape(name) + '"]');
			if (originals.length === 0) return;
			var originalItem = originals[0].closest('.nf-nav-item');
			if (!originalItem) return;

			var clone = originalItem.cloneNode(true);
			clone.setAttribute('data-pin-clone', '1');
			// Re-evaluate active state for the cloned position (same href so unchanged)
			pinnedSection.appendChild(clone);
		});
	}

	function cssEscape(s) {
		return String(s).replace(/[^a-zA-Z0-9_-]/g, function(c) { return '\\' + c; });
	}

	function togglePin(name) {
		var pinned = loadPinned();
		var idx = pinned.indexOf(name);
		if (idx === -1) pinned.push(name);
		else pinned.splice(idx, 1);
		savePinned(pinned);
		renderPinnedClones();
	}

	function initPinning() {
		// Bind sur les pins de sidebar (ancien) ET sur les sub-tab pins (nouveau)
		var allPins = document.querySelectorAll('.nf-nav-item-pin, .nf-sub-tab-pin');
		allPins.forEach(function(pin) {
			pin.addEventListener('mousedown', function(e) {
				e.preventDefault();
				e.stopPropagation();
			});
			pin.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();
				togglePin(pin.dataset.pinName);
				updateSubTabPinStates();
				return false;
			});
			pin.addEventListener('keydown', function(e) {
				if (e.key !== 'Enter' && e.key !== ' ') return;
				e.preventDefault();
				e.stopPropagation();
				togglePin(pin.dataset.pinName);
				updateSubTabPinStates();
			});
		});
		renderPinnedClones();
		updateSubTabPinStates();
	}

	// Met à jour l'état visuel "is-pinned" sur les sub-tab pins
	function updateSubTabPinStates() {
		var pinned = loadPinned();
		document.querySelectorAll('.nf-sub-tab-pin').forEach(function(pin) {
			var name = pin.dataset.pinName;
			var isPinned = pinned.indexOf(name) !== -1;
			pin.classList.toggle('is-pinned', isPinned);
			pin.setAttribute('title', isPinned ? 'Désépingler' : 'Épingler');
			pin.setAttribute('aria-label', isPinned ? 'Désépingler' : 'Épingler');
		});
	}

	// ============ SIDEBAR COLLAPSE (icons only) ============
	function applySidebarCollapse(collapsed) {
		var app = document.querySelector('.nf-app');
		if (!app) return;
		app.classList.toggle('nf-sidebar-collapsed', !!collapsed);
		var btn = document.getElementById('nfSidebarCollapseBtn');
		if (btn) {
			btn.setAttribute('title', collapsed ? 'Déplier la barre latérale' : 'Replier la barre latérale');
			btn.setAttribute('aria-label', collapsed ? 'Déplier la barre latérale' : 'Replier la barre latérale');
		}
	}

	function initSidebarCollapse() {
		var btn = document.getElementById('nfSidebarCollapseBtn');
		var stored = getStored(STORAGE_SIDEBAR) === '1';
		applySidebarCollapse(stored);
		if (!btn) return;
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			var app = document.querySelector('.nf-app');
			var newState = !app.classList.contains('nf-sidebar-collapsed');
			setStored(STORAGE_SIDEBAR, newState ? '1' : '0');
			applySidebarCollapse(newState);
		});
	}

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
		out.push({ kind: 'action', title: 'Basculer le thème (clair/sombre)', icon: 'fas fa-moon', section: 'Réglages', sectionIcon: 'fas fa-sliders-h', action: 'toggle-theme' });
		out.push({ kind: 'action', title: 'Voir le site public', icon: 'fas fa-external-link-alt', section: 'Réglages', sectionIcon: 'fas fa-sliders-h', url: window.nfHomeUrl || '/' });
		out.push({ kind: 'action', title: 'Se déconnecter', icon: 'fas fa-sign-out-alt', section: 'Réglages', sectionIcon: 'fas fa-sliders-h', url: window.nfLogoutUrl || '#' });
		return out;
	}

	function renderResults(query) {
		if (!cmdResults) return;
		var q = (query || '').toLowerCase().trim();
		var all = getCommands();
		var matched = all.filter(function(c) { return !q || c.title.toLowerCase().indexOf(q) !== -1; });
		if (matched.length === 0) {
			cmdResults.innerHTML = '<div class="nf-cmd-empty"><i class="fas fa-search"></i> Aucun résultat</div>';
			return;
		}
		var grouped = {};
		var sectionIcons = {};
		matched.forEach(function(c) { (grouped[c.section] = grouped[c.section] || []).push(c); sectionIcons[c.section] = c.sectionIcon || 'fas fa-folder'; });
		var html = '';
		var idx = 0;
		Object.keys(grouped).forEach(function(sec) {
			html += '<div class="nf-cmd-section">';
			html += '<div class="nf-cmd-section-header"><i class="' + escapeAttr(sectionIcons[sec]) + '"></i>' + escapeHtml(sec) + '</div>';
			grouped[sec].forEach(function(c) {
				html += '<a class="nf-cmd-result" data-idx="' + idx + '"';
				if (c.url) html += ' href="' + escapeAttr(c.url) + '"';
				html += ' data-action="' + escapeAttr(c.action || '') + '">';
				html += '<span class="nf-cmd-result-icon"><i class="' + escapeAttr(c.icon) + '"></i></span>';
				html += '<span class="nf-cmd-result-title">' + escapeHtml(c.title) + '</span>';
				html += '<span class="nf-cmd-result-section">' + escapeHtml(c.section) + '</span>';
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
		items.forEach(function(it, i) { it.classList.toggle('focused', i === focusedIdx); });
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
		initFilter();
		initSections();
		initPinning();
		initMobileSidebar();
		initSidebarCollapse();
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
