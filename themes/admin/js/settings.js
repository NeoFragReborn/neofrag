(function() {
	'use strict';

	// Mot-clé cherché dans le titre d'une section → icône. Le titre est affiché dans la langue du
	// site : les mots français passent donc par la traduction (chacun rendu par le mot qu'emploient
	// les titres traduits), pour que la correspondance tienne dans toutes les langues. Les mots
	// déjà internationaux restent écrits tels quels. L'ordre compte : le premier mot trouvé gagne.
	var ICONS = [
		['<?php echo addslashes($this->lang('inscription')) ?>', 'fas fa-sign-in-alt'],
		['message', 'far fa-envelope'],
		['<?php echo addslashes($this->lang('bienvenue')) ?>', 'fas fa-hand-paper'],
		['contact', 'fas fa-at'],
		['<?php echo addslashes($this->lang('identité')) ?>', 'fas fa-id-card'],
		['identification', 'fas fa-id-card'],
		['site', 'fas fa-globe'],
		['<?php echo addslashes($this->lang('général')) ?>', 'fas fa-cog'],
		['analytics', 'fas fa-chart-line'],
		['seo', 'fas fa-search'],
		['humans', 'fas fa-user-friends'],
		['robots', 'fas fa-robot'],
		['social', 'fas fa-share-alt'],
		['social network', 'fas fa-share-alt'],
		['<?php echo addslashes($this->lang('réseau')) ?>', 'fas fa-share-alt'],
		['<?php echo addslashes($this->lang('sécurité')) ?>', 'fas fa-shield-alt'],
		['captcha', 'fas fa-shield-alt'],
		['maintenance', 'fas fa-power-off'],
		['<?php echo addslashes($this->lang('ouverture')) ?>', 'far fa-clock'],
		['<?php echo addslashes($this->lang('apparence')) ?>', 'fas fa-paint-brush'],
		['page', 'far fa-file'],
		['logo', 'fas fa-image'],
		['<?php echo addslashes($this->lang('fond')) ?>', 'fas fa-image'],
		['image', 'fas fa-image'],
		['<?php echo addslashes($this->lang('couleur')) ?>', 'fas fa-palette'],
		['<?php echo addslashes($this->lang('titre')) ?>', 'fas fa-heading'],
		['description', 'fas fa-align-left'],
		['copyright', 'far fa-copyright'],
		['<?php echo addslashes($this->lang('équipe')) ?>', 'fas fa-users'],
		['team', 'fas fa-users'],
		['structure', 'fas fa-sitemap'],
		['<?php echo addslashes($this->lang('auteur')) ?>', 'fas fa-user-edit'],
		['staff', 'fas fa-users-cog'],
		['<?php echo addslashes($this->lang('membres')) ?>', 'fas fa-users'],
		['<?php echo addslashes($this->lang('statut')) ?>', 'fas fa-toggle-on'],
		['<?php echo addslashes($this->lang('règlement')) ?>', 'far fa-file-alt']
	];

	function pickIcon(text) {
		var t = text.toLowerCase().trim();
		for (var i = 0; i < ICONS.length; i++) {
			var mot = ICONS[i][0].toLowerCase();
			if (mot && t.indexOf(mot) !== -1) return ICONS[i][1];
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
			var allGroups = form.querySelectorAll(':scope > .nf-field');
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
		var rows = form.querySelectorAll(':scope > fieldset > .nf-field.row, :scope > fieldset > .row, :scope > .nf-field.row, :scope > .row');
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

		// Page a formulaire unique : on remplace la rangee du bouton par une barre de pied.
		var bar = document.createElement('div');
		bar.className = 'settings-save-bar';
		var inner = document.createElement('div');
		inner.className = 'settings-save-bar-inner';
		inner.innerHTML = '<div class="settings-save-bar-info" hidden><i class="far fa-edit"></i> <span><?php echo addslashes($this->lang('Modifications non enregistrées')) ?></span></div>' +
			'<div class="settings-save-bar-actions"></div>';
		var actionsContainer = inner.querySelector('.settings-save-bar-actions');
		var btns = lastRow.querySelectorAll('button, input[type=submit]');
		btns.forEach(function(btn) {
			actionsContainer.appendChild(btn);
		});
		bar.appendChild(inner);
		lastRow.parentNode.replaceChild(bar, lastRow);

		// « Modifications non enregistrees » etait affiche EN PERMANENCE, des l'ouverture de la
		// page et avant d'avoir touche quoi que ce soit : le message annoncait un etat qui
		// n'existait pas. Il ne parait desormais qu'a la premiere modification reelle d'un champ.
		var info = inner.querySelector('.settings-save-bar-info');

		var signaler = function() {
			info.hidden = false;
			form.removeEventListener('input', signaler);
			form.removeEventListener('change', signaler);
		};

		form.addEventListener('input', signaler);
		form.addEventListener('change', signaler);
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
