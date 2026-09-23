/**
 * NeoFrag — Confirmation modal global stylé.
 *
 * Remplace les `onclick="return confirm('msg')"` natifs (popup système moche)
 * par un modal Bootstrap stylé cohérent avec le theme.
 *
 * Usage HTML :
 *   <a href="..." data-confirm="Supprimer cet élément ?">Supprimer</a>
 *   <button type="submit" data-confirm="Action irréversible.">Action</button>
 *   <form data-confirm="Confirmer ?">...</form>
 *
 * Attributs supportés :
 *   data-confirm        : message obligatoire (texte du modal)
 *   data-confirm-title  : titre (default "Confirmation")
 *   data-confirm-style  : couleur du bouton confirmer ("danger" default, "warning", "primary")
 *   data-confirm-icon   : icône FontAwesome (default "fas fa-exclamation-triangle")
 *   data-confirm-ok     : label bouton OK (default "Confirmer")
 *   data-confirm-cancel : label bouton Annuler (default "Annuler")
 */
(function(){
	'use strict';

	var MODAL_ID = 'nf-confirm-modal';

	function ensureModal(){
		if (document.getElementById(MODAL_ID)) return;
		var html = ''
			+ '<div class="modal fade" id="' + MODAL_ID + '" tabindex="-1" role="dialog" aria-hidden="true">'
			+   '<div class="modal-dialog modal-dialog-centered" role="document">'
			+     '<div class="modal-content">'
			+       '<div class="modal-header">'
			+         '<h5 class="modal-title" id="nf-confirm-title"><i class="fas fa-exclamation-triangle text-warning"></i> <span class="title-text"><?php echo addslashes($this->lang('Confirmation')) ?></span></h5>'
			+         '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo addslashes($this->lang('Fermer')) ?>"></button>'
			+       '</div>'
			+       '<div class="modal-body" id="nf-confirm-body"></div>'
			+       '<div class="modal-footer">'
			+         '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="nf-confirm-cancel"><?php echo addslashes($this->lang('Annuler')) ?></button>'
			+         '<button type="button" class="btn btn-danger" id="nf-confirm-ok"><?php echo addslashes($this->lang('Confirmer')) ?></button>'
			+       '</div>'
			+     '</div>'
			+   '</div>'
			+ '</div>';
		var div = document.createElement('div');
		div.innerHTML = html;
		document.body.appendChild(div.firstChild);
	}

	var pendingTrigger = null; // élément à re-trigger après confirmation

	function openModal(opts){
		ensureModal();
		var modal  = document.getElementById(MODAL_ID);
		var $title = modal.querySelector('.title-text');
		var $icon  = modal.querySelector('.modal-title i');
		var $body  = document.getElementById('nf-confirm-body');
		var $ok    = document.getElementById('nf-confirm-ok');
		var $cancel = document.getElementById('nf-confirm-cancel');

		var validStyles = ['primary', 'success', 'warning', 'danger', 'info', 'secondary'];
		var style = (opts.style && validStyles.indexOf(opts.style) >= 0) ? opts.style : 'danger';

		$title.textContent = opts.title || '<?php echo addslashes($this->lang('Confirmation')) ?>';
		$icon.className = (opts.icon || 'fas fa-exclamation-triangle') + ' text-' + style;
		$body.innerHTML = '<p class="m-0">' + escapeHtml(opts.message) + '</p>';
		$ok.textContent = opts.ok || '<?php echo addslashes($this->lang('Confirmer')) ?>';
		$cancel.textContent = opts.cancel || '<?php echo addslashes($this->lang('Annuler')) ?>';
		$ok.className = 'btn btn-' + style;

		// Click handler one-shot
		var newOk = $ok.cloneNode(true);
		$ok.parentNode.replaceChild(newOk, $ok);
		newOk.addEventListener('click', function(){
			bootstrap.Modal.getOrCreateInstance(modal).hide();
			if (typeof opts.onConfirm === 'function') opts.onConfirm();
		});

		bootstrap.Modal.getOrCreateInstance(modal).show();
	}

	function escapeHtml(s){
		s = String(s == null ? '' : s);
		return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
	}

	// Délégation : intercepte les clicks sur [data-confirm]
	document.addEventListener('click', function(e){
		var target = e.target.closest('[data-confirm]');
		if (!target) return;
		// Le flag _nfConfirmed permet de re-trigger une fois confirmé
		if (target._nfConfirmed) return;

		e.preventDefault();
		e.stopPropagation();

		openModal({
			message: target.getAttribute('data-confirm'),
			title:   target.getAttribute('data-confirm-title'),
			icon:    target.getAttribute('data-confirm-icon'),
			style:   target.getAttribute('data-confirm-style'),
			ok:      target.getAttribute('data-confirm-ok'),
			cancel:  target.getAttribute('data-confirm-cancel'),
			onConfirm: function(){
				target._nfConfirmed = true;
				if (target.tagName === 'A') {
					if (target.target === '_blank') window.open(target.href);
					else window.location.href = target.href;
				} else if (target.tagName === 'BUTTON' || target.tagName === 'INPUT') {
					// Re-déclenche un click natif sans intercepter
					target.click();
				} else {
					target.click();
				}
				target._nfConfirmed = false;
			}
		});
	}, true); // capture phase pour intercepter avant les autres handlers

	// Délégation submit : intercepte les forms avec data-confirm
	document.addEventListener('submit', function(e){
		var form = e.target.closest('form[data-confirm]');
		if (!form) return;
		if (form._nfConfirmed) return;

		e.preventDefault();
		e.stopPropagation();

		openModal({
			message: form.getAttribute('data-confirm'),
			title:   form.getAttribute('data-confirm-title'),
			icon:    form.getAttribute('data-confirm-icon'),
			style:   form.getAttribute('data-confirm-style'),
			ok:      form.getAttribute('data-confirm-ok'),
			cancel:  form.getAttribute('data-confirm-cancel'),
			onConfirm: function(){
				form._nfConfirmed = true;
				form.submit();
			}
		});
	}, true);

	// Expose API global pour appel JS direct si besoin
	window.NFConfirm = openModal;
})();
