// Toasts Bootstrap 5 natifs (remplace bootstrap-notify, jQuery+BS4). `message` peut contenir du HTML
// (contrôlé par le code NeoFrag, comme avant). `type` = classe contextuelle BS ; 'error' -> 'danger'.
function notify(message, type) {
	if (typeof type == 'undefined' || type === null || type === '') {
		type = 'success';
	}
	if (type === 'error') {
		type = 'danger';
	}
	if (['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'].indexOf(type) === -1) {
		type = 'success';
	}

	var container = document.getElementById('nf-toast-container');
	if (!container) {
		container = document.createElement('div');
		container.id = 'nf-toast-container';
		container.className = 'toast-container position-fixed end-0 p-3';
		container.style.zIndex = '1090'; // au-dessus des modales (1055)
		container.style.top = 'var(--nf-haut, 0px)'; // sous les bandeaux du haut de page (gabarit principal)
		document.body.appendChild(container);
	}

	// Fond clair (warning/info/light) -> bouton de fermeture sombre ; sinon blanc.
	var closeClass = (type === 'warning' || type === 'info' || type === 'light') ? 'btn-close' : 'btn-close btn-close-white';

	var el = document.createElement('div');
	el.className = 'toast align-items-center text-bg-' + type + ' border-0';
	el.setAttribute('role', 'alert');
	el.setAttribute('aria-live', 'assertive');
	el.setAttribute('aria-atomic', 'true');
	el.innerHTML = '<div class="d-flex">'
		+ '<div class="toast-body">' + message + '</div>'
		+ '<button type="button" class="' + closeClass + ' me-2 m-auto" data-bs-dismiss="toast" aria-label="<?php echo addslashes($this->lang('Fermer')) ?>"></button>'
		+ '</div>';

	container.appendChild(el);

	el.addEventListener('hidden.bs.toast', function(){ el.remove(); });
	bootstrap.Toast.getOrCreateInstance(el, { delay: 5000 }).show();
}
