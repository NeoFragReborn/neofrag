/**
 * Paiements — crée la session Stripe Checkout puis redirige vers la page de paiement.
 */
(function() {
	'use strict';

	function init() {
		document.querySelectorAll('[data-pay-buy]').forEach(function(btn) {
			btn.addEventListener('click', function() {
				if (btn.disabled) return;
				var original = btn.innerHTML;
				btn.disabled = true;
				btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

				fetch(btn.getAttribute('data-url'), {
					method: 'POST',
					headers: { 'X-Requested-With': 'XMLHttpRequest' },
					credentials: 'same-origin'
				})
				.then(function(r) { return r.json(); })
				.then(function(d) {
					if (d && d.ok && d.url) {
						window.location = d.url;
					} else {
						btn.disabled = false;
						btn.innerHTML = original;
						window.alert(d && d.error === 'csrf' ? '<?php echo addslashes($this->lang('La page a expiré : recharge-la, puis réessaie.')) ?>' : d && d.error === 'disabled' ? '<?php echo addslashes($this->lang('Paiement indisponible.')) ?>' : (d && d.error === 'login' ? '<?php echo addslashes($this->lang('Connecte-toi pour acheter.')) ?>' : '<?php echo addslashes($this->lang('Erreur de paiement.')) ?>'));
					}
				})
				.catch(function() {
					btn.disabled = false;
					btn.innerHTML = original;
				});
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
