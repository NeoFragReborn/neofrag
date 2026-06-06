(function() {
	'use strict';

	// Map legend text patterns → icons
	var ICONS = {
		'inscription': 'fas fa-sign-in-alt',
		'message': 'far fa-envelope',
		'bienvenue': 'fas fa-hand-paper',
		'contact': 'fas fa-at',
		'identité': 'fas fa-id-card',
		'identification': 'fas fa-id-card',
		'site': 'fas fa-globe',
		'général': 'fas fa-cog',
		'analytics': 'fas fa-chart-line',
		'seo': 'fas fa-search',
		'humans': 'fas fa-user-friends',
		'robots': 'fas fa-robot',
		'social': 'fas fa-share-alt',
		'social network': 'fas fa-share-alt',
		'réseau': 'fas fa-share-alt',
		'sécurité': 'fas fa-shield-alt',
		'captcha': 'fas fa-shield-alt',
		'maintenance': 'fas fa-power-off',
		'ouverture': 'far fa-clock',
		'apparence': 'fas fa-paint-brush',
		'page': 'far fa-file',
		'logo': 'fas fa-image',
		'fond': 'fas fa-image',
		'image': 'fas fa-image',
		'couleur': 'fas fa-palette',
		'titre': 'fas fa-heading',
		'description': 'fas fa-align-left',
		'copyright': 'far fa-copyright',
		'équipe': 'fas fa-users',
		'team': 'fas fa-users',
		'structure': 'fas fa-sitemap',
		'auteur': 'fas fa-user-edit',
		'staff': 'fas fa-users-cog',
		'membres': 'fas fa-users',
		'statut': 'fas fa-toggle-on',
		'règlement': 'far fa-file-alt'
	};

	function pickIcon(text) {
		var t = text.toLowerCase().trim();
		// First try exact prefix match
		var keys = Object.keys(ICONS);
		for (var i = 0; i < keys.length; i++) {
			if (t.indexOf(keys[i]) !== -1) return ICONS[keys[i]];
		}
		return 'fas fa-cog';
	}

	function transformForm(form) {
		// Skip if already transformed
		if (form.dataset.settingsTransformed) return;
		form.dataset.settingsTransformed = '1';

		// Skip if form is already inside a custom settings-section-card (PHP-rendered)
		// In that case we just style the inputs and make the submit sticky.
		var alreadyWrapped = form.closest('.settings-section-card');

		var legends = form.querySelectorAll('legend');

		if (alreadyWrapped) {
			// Don't wrap fields in cards — but still relocate submit to sticky bar
			// AND remove visual redundancy (legends become inline section dividers, not cards)
			makeSubmitSticky(form);
			return;
		}

		if (legends.length === 0) {
			// No sections — and no parent card, wrap whole form in a single card
			var allGroups = form.querySelectorAll(':scope > .form-group, :scope > .row.form-group');
			if (allGroups.length > 0) {
				wrapInCard(form, null, Array.from(allGroups));
			}
			makeSubmitSticky(form);
			return;
		}

		legends.forEach(function(legend) {
			var sectionEls = [];
			var next = legend.nextElementSibling;
			while (next && next.tagName !== 'LEGEND' && !isSubmitArea(next)) {
				sectionEls.push(next);
				next = next.nextElementSibling;
			}
			wrapInCard(form, legend, sectionEls);
		});

		// Wrap submit area in sticky bar
		makeSubmitSticky(form);
	}

	function isSubmitArea(el) {
		if (!el) return false;
		// NeoFrag submit row pattern: .row > .col-12 > button[type=submit] OR .row.text-center
		if (el.classList.contains('text-center') && el.querySelector('button[type=submit]')) return true;
		if (el.querySelector && el.querySelector('button[type=submit]:not(.btn-link)')) {
			// Check if it's the last meaningful row (submit row)
			var nextSibling = el.nextElementSibling;
			if (!nextSibling || nextSibling.tagName === 'LEGEND') return false;
			return true;
		}
		return false;
	}

	function wrapInCard(form, legend, items) {
		if (items.length === 0 && !legend) return;

		var card = document.createElement('div');
		card.className = 'card settings-section-card';

		if (legend) {
			var header = document.createElement('div');
			header.className = 'settings-section-header';
			var titleText = legend.textContent.trim();
			var icon = pickIcon(titleText);
			header.innerHTML =
				'<div class="settings-section-icon"><i class="' + icon + '"></i></div>' +
				'<div class="settings-section-meta">' +
				'<div class="settings-section-title">' + escapeHtml(titleText) + '</div>' +
				'</div>';
			card.appendChild(header);
			legend.parentNode.removeChild(legend);
		}

		var body = document.createElement('div');
		body.className = 'settings-section-body';

		items.forEach(function(el) {
			body.appendChild(el);
		});

		card.appendChild(body);

		// Insert before any submit area at the end of form
		form.appendChild(card);
	}

	function makeSubmitSticky(form) {
		// Find the submit row at end of form. NeoFrag's form lib wraps fields in
		// a <fieldset>, so the actual rows are children of the fieldset, not the form.
		var lastRow = null;
		var rows = form.querySelectorAll(':scope > fieldset > .form-group.row, :scope > fieldset > .row, :scope > .form-group.row, :scope > .row');
		for (var i = rows.length - 1; i >= 0; i--) {
			var r = rows[i];
			if (r.querySelector('button[type=submit]')) {
				lastRow = r;
				break;
			}
		}

		if (!lastRow) return;

		// If multi-form page, just style the row as a card-footer (don't make sticky)
		var moduleEl = document.querySelector('.module-settings');
		if (moduleEl && moduleEl.dataset.settingsMultiForm === '1') {
			lastRow.classList.add('settings-form-footer');
			return;
		}

		// Single-form page: wrap in sticky bar
		var bar = document.createElement('div');
		bar.className = 'settings-save-bar';
		var inner = document.createElement('div');
		inner.className = 'settings-save-bar-inner';
		inner.innerHTML = '<div class="settings-save-bar-info"><i class="far fa-edit"></i> <span>Unsaved changes</span></div>' +
			'<div class="settings-save-bar-actions"></div>';
		var actionsContainer = inner.querySelector('.settings-save-bar-actions');
		var btns = lastRow.querySelectorAll('button, input[type=submit]');
		btns.forEach(function(btn) {
			actionsContainer.appendChild(btn);
		});
		bar.appendChild(inner);
		lastRow.parentNode.replaceChild(bar, lastRow);
	}

	function escapeHtml(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
			return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
		});
	}

	function init() {
		// Only on settings module pages
		var moduleEl = document.querySelector('.module-settings');
		if (!moduleEl) return;

		var forms = moduleEl.querySelectorAll('form');
		// Mark multi-form pages so transformForm can skip sticky save bar
		if (forms.length > 1) {
			moduleEl.dataset.settingsMultiForm = '1';
		}
		forms.forEach(transformForm);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
